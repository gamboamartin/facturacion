<?php
declare(strict_types=1);
namespace LabV2\FirstAllocation;

use Brick\Math\BigDecimal;
use DomainException;

/** Fixed arithmetic fixtures only. No catalog/history/provenance authority. */
final class ClosedFixtureScenarios
{
    public const NAMES = ['single', 'mixed', 'retention', 'exento', 'mixed_exento', 'zero'];

    public static function names(): array { return self::NAMES; }

    public static function assertSource(string $scenario, array $source): void { self::validateSource($source, $scenario); }
    public static function assertPipeline(string $scenario, array $result): void { self::validatePipeline($result, $scenario); }

    public static function definition(string $scenario): array
    {
        $transfer16 = ['class'=>'T','tax'=>'002','factor'=>'Tasa','base'=>'100','rate'=>'0.16','amount'=>'16'];
        $transfer8 = ['class'=>'T','tax'=>'002','factor'=>'Tasa','base'=>'100','rate'=>'0.08','amount'=>'8'];
        $retention10 = ['class'=>'R','tax'=>'001','factor'=>'Tasa','base'=>'100','rate'=>'0.10','amount'=>'10'];
        $exempt = ['class'=>'T','tax'=>'002','factor'=>'Exento','base'=>'100'];
        $zero = ['class'=>'T','tax'=>'002','factor'=>'Tasa','base'=>'100','rate'=>'0','amount'=>'0'];
        $line = static fn(array $taxes): array => ['base'=>'100','taxes'=>$taxes];
        [$total,$payment,$lines,$groups] = match ($scenario) {
            'single' => ['116','58',[$line([$transfer16])],[['T','002','Tasa','0.16','50','8']]],
            'mixed' => ['224','112',[$line([$transfer16]),$line([$transfer8])],[['T','002','Tasa','0.16','50','8'],['T','002','Tasa','0.08','50','4']]],
            'retention' => ['106','53',[$line([$transfer16,$retention10])],[['T','002','Tasa','0.16','50','8'],['R','001','Tasa','0.10','50','5']]],
            'exento' => ['100','50',[$line([$exempt])],[['T','002','Exento',null,'50',null]]],
            'mixed_exento' => ['216','108',[$line([$transfer16]),$line([$exempt])],[['T','002','Tasa','0.16','50','8'],['T','002','Exento',null,'50',null]]],
            'zero' => ['100','50',[$line([$zero])],[['T','002','Tasa','0','50','0']]],
            default => throw new DomainException('Unknown closed fixture scenario')
        };
        return ['scenario'=>$scenario,'currency'=>'MXN','total'=>$total,'payment'=>$payment,
            'remaining'=>$payment,'lines'=>$lines,'expected_dr_groups'=>$groups];
    }

    /** Validate shape/values only. IDs are opaque source IDs, never predefined here. */
    public static function validateSource(array $source, string $scenario): void
    {
        $definition = self::definition($scenario);
        if (($source['currency']??null)!=='MXN' || ($source['adjustments']??null)!==[]
            || ($source['prior_allocations']??null)!==[] || !is_array($source['concepts']??null)
            || !array_is_list($source['concepts']) || count($source['concepts'])!==count($definition['lines'])) {
            throw new DomainException('Source fixture shape mismatch');
        }
        self::same($source['total']??null,$definition['total']);
        $actual=[];
        foreach ($source['concepts'] as $line) {
            if (!is_array($line)) throw new DomainException('Invalid fixture concept');
            $actual[]=self::lineSignature($line);
        }
        $expected=array_map(static fn(array $line): string => self::lineSignature(['gross'=>$line['base'],'discount'=>'0','taxes'=>$line['taxes']]),$definition['lines']);
        sort($actual,SORT_STRING); sort($expected,SORT_STRING);
        if ($actual!==$expected) throw new DomainException('Source concept/tax mismatch');
    }

    /** Manual expected group values, not a second call to the calculation engine. */
    public static function validatePipeline(array $result, string $scenario): void
    {
        $d=self::definition($scenario);
        if (($result['status']??null)!=='SYNTHETIC_DRAFT_NOT_PERSISTED_NOT_FOR_TIMBRADO'
            || ($result['provenance_verified_here']??null)!==false
            || ($result['synthetic_xml_identity']??null)!==true) throw new DomainException('Synthetic flags missing');
        self::same($result['dr']['document_paid']??null,$d['payment']);
        self::same($result['dr']['previous_balance']??null,$d['total']);
        self::same($result['dr']['outstanding_balance']??null,$d['remaining']);
        self::same($result['dr']['payment_remaining']??null,'0');
        $groups=$result['allocation']['dr_tax_groups']??null;
        if (!is_array($groups)||count($groups)!==count($d['expected_dr_groups'])) throw new DomainException('DR group coverage mismatch');
        $actual=[];
        foreach($groups as $group) {
            $isExempt=($group['factor']??null)==='Exento';
            if($isExempt&&(array_key_exists('rate',$group)||array_key_exists('amount_dr',$group)))throw new DomainException('Exento must omit rate/amount');
            $actual[]=json_encode([$group['class']??null,$group['tax']??null,$group['factor']??null,
                $isExempt?null:self::number($group['rate']??null),self::number($group['base_dr']??null),
                $isExempt?null:self::number($group['amount_dr']??null)],JSON_THROW_ON_ERROR);
        }
        $expected=[];
        foreach($d['expected_dr_groups'] as [$kind,$tax,$factor,$rate,$base,$amount]) {
            $expected[]=json_encode([$kind,$tax,$factor,$rate===null?null:self::number($rate),self::number($base),$amount===null?null:self::number($amount)],JSON_THROW_ON_ERROR);
        }
        sort($actual,SORT_STRING);sort($expected,SORT_STRING);
        if($actual!==$expected)throw new DomainException('Manual DR expectations mismatch');
        $pGroups=$result['oracle']['groups']??null;
        if(!is_array($pGroups)||count($pGroups)!==count($d['expected_dr_groups']))throw new DomainException('P group coverage mismatch');
        $actualP=[];$expectedP=[];
        foreach($pGroups as $g){
            $ret=($g['clase']??null)==='R';$ex=($g['tipo_factor']??null)==='Exento';
            if($ret&&(isset($g['base_p'])||isset($g['tipo_factor'])||isset($g['tasa'])))throw new DomainException('Unexpected retention P fields');
            if($ex&&(array_key_exists('tasa',$g)||array_key_exists('importe_p',$g)))throw new DomainException('Unexpected Exento P fields');
            $actualP[]=json_encode([$g['clase']??null,$g['impuesto']??null,$ret?null:($g['tipo_factor']??null),
                $ret||$ex?null:self::number($g['tasa']??null),$ret?null:self::number($g['base_p']??null),
                $ex?null:self::number($g['importe_p']??null)],JSON_THROW_ON_ERROR);
        }
        foreach($d['expected_dr_groups'] as [$kind,$tax,$factor,$rate,$base,$amount]){
            $expectedP[]=json_encode([$kind,$tax,$kind==='R'?null:$factor,$kind==='R'||$rate===null?null:self::number($rate),
                $kind==='R'?null:self::number($base),$amount===null?null:self::number($amount)],JSON_THROW_ON_ERROR);
        }
        sort($actualP,SORT_STRING);sort($expectedP,SORT_STRING);
        if($actualP!==$expectedP)throw new DomainException('Manual P expectations mismatch');
        self::same($result['oracle']['totals']['monto_total_pagos']??null,$d['payment']);
        $expectedTotals=[
            'total_traslados_base_iva_16'=>'0','total_traslados_impuesto_iva_16'=>'0',
            'total_traslados_base_iva_08'=>'0','total_traslados_impuesto_iva_08'=>'0',
            'total_traslados_base_iva_00'=>'0','total_traslados_impuesto_iva_00'=>'0',
            'total_traslados_base_iva_exento'=>'0','total_retenciones_isr'=>'0',
            'total_retenciones_iva'=>'0','total_retenciones_ieps'=>'0'
        ];
        foreach($d['expected_dr_groups'] as [$kind,$tax,$factor,$rate,$base,$amount]) {
            if($kind==='R'){$expectedTotals['total_retenciones_isr']=$amount;continue;}
            if($factor==='Exento'){$expectedTotals['total_traslados_base_iva_exento']=$base;continue;}
            $suffix=match($rate){'0.16'=>'16','0.08'=>'08','0'=>'00'};
            $expectedTotals['total_traslados_base_iva_'.$suffix]=$base;
            $expectedTotals['total_traslados_impuesto_iva_'.$suffix]=$amount;
        }
        foreach($expectedTotals as $key=>$value)self::same($result['oracle']['totals'][$key]??null,$value);
    }

    private static function lineSignature(array $line): string
    {
        if(!is_array($line['taxes']??null)||!array_is_list($line['taxes']))throw new DomainException('Tax list required');
        $taxes=[];
        foreach($line['taxes'] as $t){
            if(!is_array($t))throw new DomainException('Tax object required');
            $ex=($t['factor']??null)==='Exento';
            if($ex&&(array_key_exists('rate',$t)||array_key_exists('amount',$t)))throw new DomainException('Exento source fields');
            $taxes[]=json_encode([$t['class']??null,$t['tax']??null,$t['factor']??null,self::number($t['base']??null),
                $ex?null:self::number($t['rate']??null),$ex?null:self::number($t['amount']??null)],JSON_THROW_ON_ERROR);
        }
        sort($taxes,SORT_STRING);
        return json_encode([self::number($line['gross']??null),self::number($line['discount']??null),$taxes],JSON_THROW_ON_ERROR);
    }
    private static function same(mixed $actual,string $expected):void
    {
        if(self::number($actual)!==self::number($expected))throw new DomainException('Manual numeric expectation mismatch');
    }
    private static function number(mixed $value):string
    {
        if(!is_string($value)||!preg_match('/^(0|[1-9][0-9]{0,17})(?:\.[0-9]{1,18})?$/D',$value))throw new DomainException('Exact decimal required');
        return BigDecimal::of($value)->strippedOfTrailingZeros()->toString();
    }
}
