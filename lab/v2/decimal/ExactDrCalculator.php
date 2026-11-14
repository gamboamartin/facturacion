<?php
declare(strict_types=1);
namespace FacturacionLabV2;

use Brick\Math\BigDecimal;
use Brick\Math\BigRational;
use Brick\Math\RoundingMode;
use Brick\Math\Exception\RoundingNecessaryException;
use InvalidArgumentException;
use RuntimeException;

final class ExactDrPolicyRequired extends RuntimeException {}

/** LAB proposal only. No IO, persistence, authorization, tax or ledger discovery. */
final class ExactDrCalculator
{
    public const POLICY = 'LAB_DR_EXACT_ONLY_V1';

    public static function propose(array $command, array $snapshot, array $policy): array
    {
        self::keys($command, ['operation_id','payment_child_id','invoice_id','amount','quote_reference','expected_payment_revision','expected_invoice_ledger_revision']);
        self::keys($snapshot, ['payment_child_id','invoice_id','payment_revision','invoice_ledger_revision','payment_currency','document_currency','equivalence','quote_reference','payment_available','previous_balance','next_partiality','predecessor_reference','target_state','source_ledger_eligible','source_fiscal_fingerprint']);
        self::keys($policy, ['id','catalog_version','rules_version','sequence_policy','payment_scale','document_scale']);
        foreach (['operation_id','quote_reference'] as $k) self::token($command[$k]);
        foreach (['quote_reference','predecessor_reference','source_fiscal_fingerprint'] as $k) self::token($snapshot[$k]);
        foreach (['id','catalog_version','rules_version','sequence_policy'] as $k) self::token($policy[$k]);
        if ($policy['id'] !== self::POLICY) throw new ExactDrPolicyRequired('Unsupported exact-only policy');
        foreach (['payment_child_id','invoice_id'] as $k) {
            self::integer($command[$k], false); self::integer($snapshot[$k], false);
            if ($command[$k] !== $snapshot[$k]) throw new InvalidArgumentException('Identity mismatch');
        }
        foreach (['payment_revision','invoice_ledger_revision'] as $k) {
            self::integer($command['expected_'.$k], true); self::integer($snapshot[$k], true);
            if ($command['expected_'.$k] !== $snapshot[$k]) throw new InvalidArgumentException('Stale revision');
        }
        if ($command['quote_reference'] !== $snapshot['quote_reference']) throw new InvalidArgumentException('Quote mismatch');
        if ($snapshot['target_state'] !== 'DRAFT' || $snapshot['source_ledger_eligible'] !== true) throw new InvalidArgumentException('Snapshot not eligible');
        foreach (['payment_currency','document_currency'] as $k) {
            if (!is_string($snapshot[$k]) || !preg_match('/^[A-Z]{3}$/D', $snapshot[$k])) throw new InvalidArgumentException('Invalid currency');
        }
        foreach (['payment_scale','document_scale'] as $k) {
            if (!is_string($policy[$k]) || !preg_match('/^[0-6]$/D', $policy[$k])) throw new InvalidArgumentException('Explicit scale 0..6 required');
        }
        $ps = (int)$policy['payment_scale']; $ds = (int)$policy['document_scale'];
        self::integer($snapshot['next_partiality'], false);
        if (strlen($snapshot['next_partiality']) > 3) throw new InvalidArgumentException('Partiality outside 1..999');
        $eq = self::number($snapshot['equivalence'], 10, true);
        if ($snapshot['payment_currency'] === $snapshot['document_currency'] && !$eq->isEqualTo('1')) throw new InvalidArgumentException('Same currency requires identity equivalence');
        $available = self::number($snapshot['payment_available'], $ps, false);
        $previous = self::number($snapshot['previous_balance'], $ds, false);
        if (!is_array($command['amount'])) throw new InvalidArgumentException('Amount object required');
        self::keys($command['amount'], ['unit','value','currency']);
        $a = $command['amount'];
        if (!in_array($a['unit'], ['PAYMENT','DOCUMENT'], true)) throw new InvalidArgumentException('Explicit amount unit required');
        $paymentMode = $a['unit'] === 'PAYMENT';
        if ($a['currency'] !== $snapshot[$paymentMode ? 'payment_currency' : 'document_currency']) throw new InvalidArgumentException('Amount currency mismatch');
        $input = self::number($a['value'], $paymentMode ? $ps : $ds, true)->toBigRational();
        $e = $eq->toBigRational();
        $payment = $paymentMode ? $input : $input->dividedBy($e);
        $document = $paymentMode ? $input->multipliedBy($e) : $input;
        // Never decide tolerances, quantization or write-offs. Nonrepresentable
        // results require an explicit future policy, including periodic ratios.
        $pay = self::emit($payment, $ps);
        $doc = self::emit($document, $ds);
        if ($payment->isGreaterThan($available) || $document->isGreaterThan($previous)) throw new InvalidArgumentException('Overpayment or insufficient payment allocation');
        $remainingPayment = self::emit($available->toBigRational()->minus($payment), $ps);
        $outstanding = self::emit($previous->toBigRational()->minus($document), $ds);
        $result = [
            'policy'=>self::POLICY, 'operation_id'=>$command['operation_id'],
            'command_digest'=>self::digest($command), 'snapshot_digest'=>self::digest($snapshot),
            'policy_digest'=>self::digest($policy),
            'payment_child_id'=>$snapshot['payment_child_id'], 'invoice_id'=>$snapshot['invoice_id'],
            'payment_revision'=>$snapshot['payment_revision'], 'invoice_ledger_revision'=>$snapshot['invoice_ledger_revision'],
            'payment_currency'=>$snapshot['payment_currency'], 'document_currency'=>$snapshot['document_currency'],
            'equivalence'=>$eq->strippedOfTrailingZeros()->toString(), 'quote_reference'=>$snapshot['quote_reference'],
            'payment_allocation'=>$pay, 'document_paid'=>$doc,
            'previous_balance'=>self::emit($previous->toBigRational(),$ds),
            'outstanding_balance'=>$outstanding, 'payment_remaining'=>$remainingPayment,
            'payment_exact'=>$payment->toString(), 'document_exact'=>$document->toString(),
            'conversion_residual_payment'=>'0',
            'partiality'=>$snapshot['next_partiality'], 'predecessor_reference'=>$snapshot['predecessor_reference'],
            'source_fiscal_fingerprint'=>$snapshot['source_fiscal_fingerprint'],
            'catalog_version'=>$policy['catalog_version'], 'rules_version'=>$policy['rules_version'],
            'sequence_policy'=>$policy['sequence_policy'], 'status'=>'PROPOSAL_NOT_PERSISTED'
        ];
        $result['proposal_digest'] = self::digest($result);
        return $result;
    }

    private static function emit(BigRational $value, int $scale): string
    {
        try { $out = $value->toScale($scale, RoundingMode::Unnecessary)->toString(); }
        catch (RoundingNecessaryException $e) { throw new ExactDrPolicyRequired('Exact result needs an unspecified rounding/residual policy', 0, $e); }
        if (strlen(explode('.', $out)[0]) > 18) throw new InvalidArgumentException('Output magnitude exceeds 18 digits');
        return $out;
    }
    private static function number(mixed $v, int $scale, bool $positive): BigDecimal
    {
        if (!is_string($v) || strlen($v)>40 || !preg_match('/^(0|[1-9][0-9]{0,17})(?:\.([0-9]+))?$/D',$v,$m) || strlen($m[2]??'')>$scale) throw new InvalidArgumentException('Invalid decimal string or precision');
        $d=BigDecimal::of($v);
        if ($positive && $d->isZero()) throw new InvalidArgumentException('Positive value required');
        return $d;
    }
    private static function integer(mixed $v, bool $zero): void
    {
        if (!is_string($v) || !preg_match($zero?'/^(0|[1-9][0-9]{0,17})$/D':'/^[1-9][0-9]{0,17}$/D',$v)) throw new InvalidArgumentException('Invalid integer string');
    }
    private static function token(mixed $v): void
    {
        if (!is_string($v) || !preg_match('/^[A-Za-z0-9][A-Za-z0-9_.:\/-]{0,127}$/D',$v)) throw new InvalidArgumentException('Invalid reference token');
    }
    private static function keys(array $a, array $keys): void
    {
        $got=array_keys($a); sort($got); sort($keys);
        if ($got!==$keys) throw new InvalidArgumentException('Missing or unknown contract fields');
    }
    private static function digest(array $a): string
    {
        $sort = static function(array $v) use (&$sort): array { ksort($v); foreach($v as &$x) if(is_array($x)) $x=$sort($x); return $v; };
        return hash('sha256',json_encode($sort($a),JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES));
    }
}
