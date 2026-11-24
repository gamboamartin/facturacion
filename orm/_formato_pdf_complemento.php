<?php
declare(strict_types=1);

namespace gamboamartin\facturacion\models;

use InvalidArgumentException;

/** Selección propia del complemento; no altera los formatos de factura o nota de crédito. */
final class _formato_pdf_complemento
{
    public static function seleccionar(object $configuracion): string
    {
        $seleccion = $configuracion->formato_pdf_complemento_pago ?? '';
        if (!is_string($seleccion)) {
            throw new InvalidArgumentException('El formato PDF de complemento debe ser aguaban o estandar.');
        }
        $seleccion = trim($seleccion);
        if ($seleccion === '') {
            return ($configuracion->proyecto_custom_pdf ?? '') === 'aguaban' ? 'aguaban' : 'estandar';
        }
        if (!in_array($seleccion, ['aguaban', 'estandar'], true)) {
            throw new InvalidArgumentException('El formato PDF de complemento debe ser aguaban o estandar.');
        }
        return $seleccion;
    }
}
