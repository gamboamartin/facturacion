<?php

declare(strict_types=1);
namespace LabV2\FirstAllocation;

use Brick\Math\BigDecimal;
use FacturacionLabV2\ExactDrCalculator;
use FacturacionLabV2\ProportionalTaxAllocator;
use FacturacionLabV2\BrickOracle;
use LabV2\Xml\Serializer;
use DomainException;

/** Pure synthetic adapter, NOT provenance verification or a public endpoint.
 * Caller obtains source from readSyntheticFixture inside its owned boundary and
 * revalidates that boundary. An array/label/hash cannot prove that this happened.
 * No DB, reader call, IO, persistence, transaction, CSD or PAC. */
final class ClosedFirstAllocationPipeline
{
    public const TRUST = 'CALLER_VERIFIED_CLOSED_SYNTHETIC_BOUNDARY_ONLY';

    public static function fromCallerVerifiedSyntheticSource(array $source, string $amount, array $context): array
    {
        $keys = array_keys($context);
        $expected = ['trust', 'operation_id', 'payment_id', 'payment_revision', 'invoice_revision'];
        sort($keys); sort($expected);
        if ($keys !== $expected || $context['trust'] !== self::TRUST) {
            throw new DomainException('Closed synthetic caller contract required');
        }
        // This is an explicit limit, not an assertion that client arrays are trusted.
        if (($source['currency'] ?? null) !== 'MXN'
            || ($source['adjustments'] ?? null) !== []
            || ($source['prior_allocations'] ?? null) !== []) {
            throw new DomainException('Only intact first-allocation MXN source supported');
        }
        foreach (['total' => $source['total'] ?? null, 'amount' => $amount] as $value) {
            if (!is_string($value) || !preg_match('/^(0|[1-9][0-9]{0,17})(?:\.[0-9]{1,18})?$/D', $value)) {
                throw new DomainException('Exact decimal string required');
            }
        }
        // Reader's DECIMAL(...,18) padding is removed only if exact at scale2.
        $total = BigDecimal::of($source['total'])->toScale(2)->toString();
        $paid = BigDecimal::of($amount)->toScale(2)->toString();
        $command = [
            'operation_id' => $context['operation_id'], 'payment_child_id' => $context['payment_id'],
            'invoice_id' => $source['invoice_id'],
            'amount' => ['unit' => 'PAYMENT', 'value' => $paid, 'currency' => 'MXN'],
            'quote_reference' => 'synthetic-identity-FX1',
            'expected_payment_revision' => $context['payment_revision'],
            'expected_invoice_ledger_revision' => $context['invoice_revision']
        ];
        $snapshot = [
            'payment_child_id' => $context['payment_id'], 'invoice_id' => $source['invoice_id'],
            'payment_revision' => $context['payment_revision'], 'invoice_ledger_revision' => $context['invoice_revision'],
            'payment_currency' => 'MXN', 'document_currency' => 'MXN', 'equivalence' => '1',
            'quote_reference' => 'synthetic-identity-FX1', 'payment_available' => $paid,
            'previous_balance' => $total, 'next_partiality' => '1',
            'predecessor_reference' => 'synthetic-genesis', 'target_state' => 'DRAFT',
            'source_ledger_eligible' => true, 'source_fiscal_fingerprint' => $source['fingerprint']
        ];
        $dr = ExactDrCalculator::propose($command, $snapshot, [
            'id' => ExactDrCalculator::POLICY, 'catalog_version' => 'SYNTHETIC_V1',
            'rules_version' => 'SYNTHETIC_EXACT_ONLY_V1', 'sequence_policy' => 'SYNTHETIC_FIRST_V1',
            'payment_scale' => '2', 'document_scale' => '2'
        ]);
        $allocation = ProportionalTaxAllocator::propose($dr['document_paid'], $source, [
            'id' => ProportionalTaxAllocator::POLICY, 'rules_version' => 'RMF2026_2.7.1.32',
            'catalog_version' => 'SYNTHETIC_V1', 'scale' => '2'
        ]);
        $rows = []; $transfers = []; $retentions = [];
        foreach ($allocation['dr_tax_groups'] as $tax) {
            $row = ['pago_id' => $context['payment_id'], 'clase' => $tax['class'],
                'impuesto' => $tax['tax'], 'equivalencia' => '1'];
            $xmlTax = ['BaseDR' => $tax['base_dr'], 'ImpuestoDR' => $tax['tax'], 'TipoFactorDR' => $tax['factor']];
            if ($tax['factor'] !== 'Exento') {
                $row['importe'] = $tax['amount_dr'];
                $xmlTax['TasaOCuotaDR'] = BigDecimal::of($tax['rate'])->toScale(6)->toString();
                $xmlTax['ImporteDR'] = $tax['amount_dr'];
            }
            if ($tax['class'] === 'T') {
                $row['base'] = $tax['base_dr']; $row['tipo_factor'] = $tax['factor'];
                if ($tax['factor'] !== 'Exento') $row['tasa'] = $tax['rate'];
                $transfers[] = $xmlTax;
            } else {
                $retentions[] = $xmlTax;
            }
            $rows[] = $row;
        }
        $payment = [['id' => $context['payment_id'], 'monto' => $paid, 'tipo_cambio' => '1']];
        $oracle = BrickOracle::calculateBounded($payment, $rows);
        $xml = Serializer::serialize($oracle, [[
            'id' => $context['payment_id'],
            'attributes' => ['FechaPago' => '2026-01-15T12:00:00', 'FormaDePagoP' => '03',
                'MonedaP' => 'MXN', 'TipoCambioP' => '1', 'Monto' => $paid],
            'documents' => [[
                'attributes' => ['IdDocumento' => '00000000-0000-4000-8000-000000000001',
                    'MonedaDR' => 'MXN', 'EquivalenciaDR' => '1', 'NumParcialidad' => '1',
                    'ImpSaldoAnt' => $total, 'ImpPagado' => $dr['document_paid'],
                    'ImpSaldoInsoluto' => $dr['outstanding_balance'], 'ObjetoImpDR' => '02'],
                'traslados' => $transfers, 'retenciones' => $retentions
            ]]
        ]]);
        return [
            'status' => 'SYNTHETIC_DRAFT_NOT_PERSISTED_NOT_FOR_TIMBRADO',
            'trust_contract' => self::TRUST, 'provenance_verified_here' => false,
            'synthetic_xml_identity' => true, 'source_fingerprint' => $source['fingerprint'],
            'dr' => $dr, 'allocation' => $allocation, 'oracle' => $oracle, 'xml' => $xml
        ];
    }
}
