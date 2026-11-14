<?php
declare(strict_types=1);

namespace FacturacionLabV2;

use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use Brick\Math\BigRational;
use Brick\Math\RoundingMode;
use InvalidArgumentException;
use RuntimeException;

final class ComplexityLimitExceeded extends RuntimeException {}

/** Pure-array LAB oracle. Caller provides the pinned Brick Math autoloader.
 * No PDO, SQL, filesystem, network, process, transactions or external effects.
 * P6 HALF_UP remains an explicit provisional laboratory serialization policy.
 */
final class BrickOracle
{
    public const POLICY = 'LAB_P6_HALF_UP_V1';
    public const MAX_EQ_DIGITS_PER_GROUP = 512;
    public const MAX_EQ_DIGITS_TOTAL = 2048;
    private const MAX_ROWS = 1000;
    private const RATE_SUFFIX = ['0' => '00', '0.08' => '08', '0.16' => '16'];
    private const RETENTIONS = ['001' => 'isr', '002' => 'iva', '003' => 'ieps'];

    public static function calculate(array $payments, array $rows): array
    {
        self::validate($payments, $rows);
        $pay = [];
        $paymentMxn = BigDecimal::zero();
        foreach ($payments as $p) {
            $pay[$p['id']] = BigDecimal::of($p['tipo_cambio']);
            $paymentMxn = $paymentMxn->plus(BigDecimal::of($p['monto'])->multipliedBy($pay[$p['id']]));
        }
        $accumulated = [];
        foreach ($rows as $r) {
            $ret = $r['clase'] === 'R';
            $factor = $ret ? null : $r['tipo_factor'];
            $rate = ($ret || $factor === 'Exento') ? null : self::decimal($r['tasa']);
            $key = json_encode([$r['pago_id'],$r['clase'],$r['impuesto'],$factor,$rate], JSON_THROW_ON_ERROR);
            if (!isset($accumulated[$key])) {
                $accumulated[$key] = ['pago_id'=>$r['pago_id'],'clase'=>$r['clase'],
                    'impuesto'=>$r['impuesto'],'factor'=>$factor,'rate'=>$rate,
                    'base'=>BigRational::zero(),'amount'=>BigRational::zero(),'count'=>0,'equivalences'=>[]];
            }
            $g = &$accumulated[$key];
            $eq = self::decimal($r['equivalencia'],true);
            if (!isset($g['equivalences'][$eq])) {
                $g['equivalences'][$eq] = ['base'=>BigDecimal::zero(),'amount'=>BigDecimal::zero()];
            }
            // Exact decimal preaggregation, no rounding: one division per
            // distinct normalized equivalence instead of one per source row.
            if (!$ret) {
                $g['equivalences'][$eq]['base'] = $g['equivalences'][$eq]['base']->plus($r['base']);
            }
            if ($factor !== 'Exento') {
                $g['equivalences'][$eq]['amount'] = $g['equivalences'][$eq]['amount']->plus($r['importe']);
            }
            $g['count']++;
            unset($g);
        }
        foreach ($accumulated as &$g) {
            foreach ($g['equivalences'] as $eq=>$sums) {
                if ($g['clase']==='T') $g['base']=$g['base']->plus($sums['base']->toBigRational()->dividedBy((string)$eq));
                if ($g['factor']!=='Exento') $g['amount']=$g['amount']->plus($sums['amount']->toBigRational()->dividedBy((string)$eq));
            }
            unset($g['equivalences']);
        }
        unset($g);
        $accumulated = array_values($accumulated);
        usort($accumulated, static function(array $a,array $b): int {
            $cmp = strlen($a['pago_id']) <=> strlen($b['pago_id']);
            if ($cmp !== 0) return $cmp;
            foreach (['pago_id','clase','impuesto','factor'] as $key) {
                $cmp = strcmp($a[$key] ?? '', $b[$key] ?? '');
                if ($cmp !== 0) return $cmp;
            }
            return BigRational::of($a['rate'] ?? '0')->compareTo($b['rate'] ?? '0');
        });
        $totals = ['monto_total_pagos'=>$paymentMxn,'total_traslados_base_iva_exento'=>BigDecimal::zero()];
        foreach (self::RATE_SUFFIX as $suffix) {
            $totals['total_traslados_base_iva_'.$suffix] = BigDecimal::zero();
            $totals['total_traslados_impuesto_iva_'.$suffix] = BigDecimal::zero();
        }
        foreach (self::RETENTIONS as $name) $totals['total_retenciones_'.$name] = BigDecimal::zero();
        $groups = [];
        foreach ($accumulated as $a) {
            $g = ['pago_id'=>$a['pago_id'],'clase'=>$a['clase'],'impuesto'=>$a['impuesto'],'filas_dr'=>$a['count']];
            $fx = $pay[$a['pago_id']];
            if ($a['clase'] === 'T') {
                $g['tipo_factor'] = $a['factor'];
                $g['base_p'] = self::emit($a['base'],6);
                $convertedBase = BigDecimal::of($g['base_p'])->multipliedBy($fx);
                if ($a['factor'] === 'Exento') {
                    $key = 'total_traslados_base_iva_exento';
                    $totals[$key] = $totals[$key]->plus($convertedBase);
                } else {
                    $g['tasa'] = self::emit(BigDecimal::of($a['rate']),6);
                    $g['importe_p'] = self::emit($a['amount'],6);
                    $suffix = self::RATE_SUFFIX[$a['rate']];
                    $key = 'total_traslados_base_iva_'.$suffix;
                    $totals[$key] = $totals[$key]->plus($convertedBase);
                    $key = 'total_traslados_impuesto_iva_'.$suffix;
                    $totals[$key] = $totals[$key]->plus(BigDecimal::of($g['importe_p'])->multipliedBy($fx));
                }
            } else {
                $g['importe_p'] = self::emit($a['amount'],6);
                $key = 'total_retenciones_'.self::RETENTIONS[$a['impuesto']];
                $totals[$key] = $totals[$key]->plus(BigDecimal::of($g['importe_p'])->multipliedBy($fx));
            }
            $groups[] = $g;
        }
        foreach ($totals as $key=>$number) $totals[$key] = self::emit($number,2);
        return ['policy'=>self::POLICY,'groups'=>$groups,'totals'=>$totals];
    }

    /** Operational LAB entry point. Reject complex input before aggregate rational work.
     * The exact calculate() oracle remains available without this extra budget.
     * These limits are provisional resource policy, NOT a proven latency bound.
     */
    public static function calculateBounded(array $payments,array $rows): array
    {
        self::validate($payments,$rows);
        $seen=[];$groupCost=[];$total=0;
        foreach ($rows as $r) {
            $ret=$r['clase']==='R';
            $factor=$ret?null:$r['tipo_factor'];
            $rate=($ret||$factor==='Exento')?null:self::decimal($r['tasa']);
            $key=json_encode([$r['pago_id'],$r['clase'],$r['impuesto'],$factor,$rate],JSON_THROW_ON_ERROR);
            $eq=self::decimal($r['equivalencia'],true);
            if (isset($seen[$key][$eq])) continue;
            $seen[$key][$eq]=true;
            // Each input has at most36 significant decimal digits. Reducing
            // this single equivalence is bounded; large group sums are not run.
            $cost=strlen(BigRational::of($eq)->getNumerator()->toString());
            $groupCost[$key]=($groupCost[$key]??0)+$cost;
            $total+=$cost;
            if ($groupCost[$key]>self::MAX_EQ_DIGITS_PER_GROUP||$total>self::MAX_EQ_DIGITS_TOTAL) {
                throw new ComplexityLimitExceeded('LAB rational complexity budget exceeded:512 per group or2048 total');
            }
        }
        return self::calculate($payments,$rows);
    }

    private static function emit(BigNumber $number, int $scale): string
    {
        $result = $number->toScale($scale,RoundingMode::HalfUp)->toString();
        // Check AFTER rounding: 24 integer digits can carry into a forbidden25th.
        if (preg_match('/\A(?:0|[1-9][0-9]{0,23})\.[0-9]{'.$scale.'}\z/D',$result)!==1) {
            throw new RuntimeException('Laboratory output range exceeded');
        }
        return $result;
    }

    /** Same strict input contract as Python/SQL providers, without SQL dependency. */
    public static function validate(array $payments, array $rows): void
    {
        if (!array_is_list($payments) || !array_is_list($rows)
            || count($payments)>self::MAX_ROWS || count($rows)>self::MAX_ROWS) {
            throw new InvalidArgumentException('Expected bounded laboratory lists');
        }
        $ids=[];
        foreach ($payments as $p) {
            self::record($p,['id','monto','tipo_cambio'],[]);
            $id=self::id($p['id']);
            if (isset($ids[$id])) throw new InvalidArgumentException('Duplicate payment ID');
            $ids[$id]=true;
            self::decimal($p['monto']);self::decimal($p['tipo_cambio'],true);
        }
        foreach ($rows as $r) {
            self::record($r,['pago_id','clase','impuesto','equivalencia'],['tipo_factor','tasa','base','importe']);
            if (!isset($ids[self::id($r['pago_id'])])) throw new InvalidArgumentException('Unknown payment reference');
            self::decimal($r['equivalencia'],true);
            if (!is_string($r['impuesto']) || !array_key_exists($r['impuesto'],self::RETENTIONS)) {
                throw new InvalidArgumentException('Unknown tax code');
            }
            if ($r['clase']==='T') {
                if ($r['impuesto']!=='002' || !array_key_exists('base',$r)) {
                    throw new InvalidArgumentException('Transfer requires IVA002 and base');
                }
                self::decimal($r['base']);
                if (($r['tipo_factor']??null)==='Exento') {
                    if (array_key_exists('tasa',$r)||array_key_exists('importe',$r)) {
                        throw new InvalidArgumentException('Exento must omit rate and amount');
                    }
                } elseif (($r['tipo_factor']??null)==='Tasa') {
                    if (!array_key_exists('tasa',$r)||!array_key_exists('importe',$r)) {
                        throw new InvalidArgumentException('Tasa requires rate and amount');
                    }
                    if (!array_key_exists(self::decimal($r['tasa']),self::RATE_SUFFIX)) {
                        throw new InvalidArgumentException('Unsupported IVA rate');
                    }
                    self::decimal($r['importe']);
                } else throw new InvalidArgumentException('Unsupported transfer factor');
            } elseif ($r['clase']==='R') {
                if (!array_key_exists('importe',$r)) throw new InvalidArgumentException('Retention amount missing');
                self::decimal($r['importe']);
                if (array_key_exists('base',$r)) self::decimal($r['base']);
                if (array_key_exists('tipo_factor',$r)&&$r['tipo_factor']!=='Tasa') {
                    throw new InvalidArgumentException('Unsupported retention factor');
                }
                if (array_key_exists('tasa',$r)) {
                    $rate=self::decimal($r['tasa']);[$whole]=explode('.',$rate,2);
                    if (strlen($whole)>1||strcmp($whole,'1')>0||($whole==='1'&&$rate!=='1')) {
                        throw new InvalidArgumentException('Retention rate exceeds laboratory range');
                    }
                }
            } else throw new InvalidArgumentException('Unknown tax class');
        }
    }

    private static function record(mixed $row,array $required,array $optional): void
    {
        if (!is_array($row)||array_diff(array_keys($row),array_merge($required,$optional))!==[]
            ||array_diff($required,array_keys($row))!==[]) {
            throw new InvalidArgumentException('Missing/unknown laboratory record fields');
        }
    }
    private static function id(mixed $value): string
    {
        if (!is_string($value)||preg_match('/\A[1-9][0-9]{0,17}\z/D',$value)!==1) {
            throw new InvalidArgumentException('Expected positive canonical ID string');
        }
        return $value;
    }
    private static function decimal(mixed $value,bool $positive=false): string
    {
        if (!is_string($value)||preg_match('/\A(?:0|[1-9][0-9]{0,17})(?:\.[0-9]{1,18})?\z/D',$value)!==1) {
            throw new InvalidArgumentException('Expected unsigned decimal string within laboratory range');
        }
        $canonical=str_contains($value,'.')?rtrim(rtrim($value,'0'),'.'):$value;
        if ($positive&&$canonical==='0') throw new InvalidArgumentException('Expected positive decimal');
        return $canonical;
    }
}
