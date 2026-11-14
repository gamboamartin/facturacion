<?php

declare(strict_types=1);

namespace FacturacionLabV2;

use Brick\Math\BigDecimal as D;
use Brick\Math\BigRational as R;
use Brick\Math\RoundingMode;
use Brick\Math\Exception\RoundingNecessaryException;
use DomainException;

/**
 * LAB: first allocation on an intact fiscal face, proportional by concept.
 *
 * paidDocument is in document currency, for example ExactDr document_paid.
 * The caller must bind invoice/currency/fingerprint/revisions to trusted source.
 * No persistence, IO, FX, XML or fiscal compliance certification.
 */
final class ProportionalTaxAllocator
{
    public const POLICY = 'LAB_RMF2026_PROPORTIONAL_EXACT_ONLY_V1';

    public static function propose(string $paidDocument, array $source, array $policy): array
    {
        self::keys($source, [
            'invoice_id', 'currency', 'fingerprint', 'total',
            'adjustments', 'prior_allocations', 'concepts'
        ]);
        self::keys($policy, ['id', 'rules_version', 'catalog_version', 'scale']);

        // Version labels describe provenance; they do not validate SAT catalogs.
        if (
            $policy['id'] !== self::POLICY
            || $policy['rules_version'] !== 'RMF2026_2.7.1.32'
            || !is_string($policy['catalog_version'])
            || !preg_match('/^[A-Za-z0-9_.-]{1,128}$/D', $policy['catalog_version'])
            || !is_string($policy['scale'])
            || !preg_match('/^[0-6]$/D', $policy['scale'])
        ) throw new DomainException('Explicit supported policy required');

        if (
            !is_string($source['invoice_id'])
            || !preg_match('/^[1-9][0-9]{0,17}$/D', $source['invoice_id'])
            || !is_string($source['currency'])
            || !preg_match('/^[A-Z]{3}$/D', $source['currency'])
            || !is_string($source['fingerprint'])
            || !preg_match('/^[a-f0-9]{64}$/D', $source['fingerprint'])
        ) throw new DomainException('Invalid source identity');

        // A trusted reader, not an HTTP client, must establish absent history.
        if ($source['adjustments'] !== [] || $source['prior_allocations'] !== [])
            throw new DomainException('Adjusted or historical source unsupported');

        if (
            !is_array($source['concepts'])
            || !array_is_list($source['concepts'])
            || count($source['concepts']) < 1
            || count($source['concepts']) > 1000
        ) throw new DomainException('Concept list required');

        $scale = (int) $policy['scale'];
        $paid = self::num($paidDocument);
        $total = self::num($source['total']);
        if ($total->isZero() || $paid->isZero() || $paid->isGreaterThan($total))
            throw new DomainException('Invalid payment/total');

        self::emit($paid->toBigRational(), $scale);
        self::emit($total->toBigRational(), $scale);

        $ratio = $paid->toBigRational()->dividedBy($total);
        $face = D::zero();
        $out = [];
        $groups = [];
        $seen = [];
        $rows = 0;

        foreach ($source['concepts'] as $c) {
            if (!is_array($c)) throw new DomainException('Concept map required');
            self::keys($c, ['id', 'gross', 'discount', 'taxes']);
            if (
                !is_string($c['id'])
                || !preg_match('/^[1-9][0-9]{0,17}$/D', $c['id'])
                || isset($seen[$c['id']])
            ) throw new DomainException('Invalid/duplicate concept');
            $seen[$c['id']] = true;

            // Discounts enter the net base once, never again during allocation.
            $net = self::num($c['gross'])->minus(self::num($c['discount']));
            if ($net->isNegative()) throw new DomainException('Discount exceeds gross');
            if (!is_array($c['taxes']) || !array_is_list($c['taxes']) || !$c['taxes'])
                throw new DomainException('Unclassified tax-free concepts unsupported');

            $ct = $net;
            $taxOut = [];
            $taxSeen = [];
            foreach ($c['taxes'] as $t) {
                if (++$rows > 2000 || !is_array($t))
                    throw new DomainException('Tax complexity/type');

                $ex = ($t['factor'] ?? null) === 'Exento';
                self::keys($t, $ex
                    ? ['class', 'tax', 'factor', 'base']
                    : ['class', 'tax', 'factor', 'base', 'rate', 'amount']);
                if (
                    !in_array($t['class'], ['T', 'R'], true)
                    || !in_array($t['tax'], ['001', '002', '003'], true)
                    || !in_array($t['factor'], ['Tasa', 'Exento'], true)
                    || ($ex && $t['class'] !== 'T')
                ) throw new DomainException('Unsupported tax identity/factor');

                $base = self::num($t['base']);
                if (!$base->isEqualTo($net) || $base->isZero())
                    throw new DomainException('Non-net/compound/zero base unsupported');

                $rate = $ex ? null : self::num($t['rate']);
                if ($rate !== null && ($rate->isGreaterThan('1') || $rate->getScale() > 6))
                    throw new DomainException('Rate outside narrow lab envelope');

                $amount = $ex ? null : self::num($t['amount']);
                if (!$ex && !$base->multipliedBy($rate)->isEqualTo($amount))
                    throw new DomainException('Source tax rounding/inconsistency requires separate policy');

                // Identity includes the rate: distinct taxes/rates stay distinct.
                $rateString = $rate?->strippedOfTrailingZeros()->toString();
                $key = json_encode(
                    [$t['class'], $t['tax'], $t['factor'], $rateString],
                    JSON_THROW_ON_ERROR
                );
                if (isset($taxSeen[$key])) throw new DomainException('Duplicate concept tax');
                $taxSeen[$key] = true;

                $b = $base->toBigRational()->multipliedBy($ratio);
                $a = $amount?->toBigRational()->multipliedBy($ratio);
                $entry = [
                    'class' => $t['class'],
                    'tax' => $t['tax'],
                    'factor' => $t['factor'],
                    'base_dr' => self::emit($b, $scale)
                ];
                if (!$ex) {
                    $entry['rate'] = $rateString;
                    $entry['amount_dr'] = self::emit($a, $scale);
                    $ct = $t['class'] === 'T' ? $ct->plus($amount) : $ct->minus($amount);
                }
                $taxOut[$key] = $entry;

                // Aggregate rational values, never already-rounded group values.
                if (!isset($groups[$key])) $groups[$key] = [
                    'identity' => $entry, 'base' => R::zero(), 'amount' => R::zero()
                ];
                $groups[$key]['base'] = $groups[$key]['base']->plus($b);
                if ($a !== null) $groups[$key]['amount'] = $groups[$key]['amount']->plus($a);
            }

            if ($ct->isNegative()) throw new DomainException('Negative concept total');
            $face = $face->plus($ct);
            ksort($taxOut, SORT_STRING);
            $out[$c['id']] = [
                'id' => $c['id'],
                'net_paid' => self::emit($net->toBigRational()->multipliedBy($ratio), $scale),
                'gross_paid' => self::emit(self::num($c['gross'])->toBigRational()->multipliedBy($ratio), $scale),
                'discount_paid' => self::emit(self::num($c['discount'])->toBigRational()->multipliedBy($ratio), $scale),
                'total_paid' => self::emit($ct->toBigRational()->multipliedBy($ratio), $scale),
                'taxes' => array_values($taxOut)
            ];
        }

        if (!$face->isEqualTo($total)) throw new DomainException('Fiscal face total mismatch');

        // Stable output does not depend on the source's concept or tax ordering.
        ksort($out, SORT_STRING);
        ksort($groups, SORT_STRING);
        $taxGroups = [];
        foreach ($groups as $g) {
            $t = $g['identity'];
            $t['base_dr'] = self::emit($g['base'], $scale);
            if ($t['factor'] !== 'Exento') $t['amount_dr'] = self::emit($g['amount'], $scale);
            $taxGroups[] = $t;
        }

        return [
            'policy' => self::POLICY,
            'rules_version' => $policy['rules_version'],
            'catalog_version' => $policy['catalog_version'],
            'scale' => $policy['scale'],
            'invoice_id' => $source['invoice_id'],
            'currency' => $source['currency'],
            'source_fingerprint' => $source['fingerprint'],
            'paid_document' => self::emit($paid->toBigRational(), $scale),
            'ratio' => $ratio->toString(),
            'concepts' => array_values($out),
            'dr_tax_groups' => $taxGroups,
            'status' => 'PROPOSAL_NOT_PERSISTED'
        ];
    }

    private static function keys(array $v, array $k): void
    {
        $a = array_keys($v);
        sort($a);
        sort($k);
        if ($a !== $k) throw new DomainException('Missing/unknown fields');
    }

    private static function num(mixed $v): D
    {
        if (!is_string($v) || !preg_match('/^(0|[1-9][0-9]{0,17})(?:\.[0-9]{1,18})?$/D', $v))
            throw new DomainException('Exact nonnegative decimal required');
        return D::of($v);
    }

    private static function emit(R $v, int $s): string
    {
        // Exact-only: no default rounding, tolerance or residual redistribution.
        try {
            $x = $v->toScale($s, RoundingMode::Unnecessary)->toString();
        } catch (RoundingNecessaryException $e) {
            throw new DomainException('Undefined rounding policy: allocation rejected', 0, $e);
        }
        if (strlen(explode('.', $x)[0]) > 18) throw new DomainException('Output range');
        return $x;
    }
}
