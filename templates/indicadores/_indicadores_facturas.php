<?php
/**
 * Indicadores de la lista de facturas (resumen de facturas timbradas).
 * Se incluye en views/fc_factura/lista.php, encima de los filtros.
 * Si no hay datos no pinta nada: es informativo y no debe romper la lista.
 *
 * @var \gamboamartin\facturacion\controllers\controlador_fc_factura $controlador
 */
use gamboamartin\facturacion\models\fc_factura;

$iv_kpi = (new fc_factura($controlador->link))->obtener_indicadores_listado();
if (isset($iv_kpi['error'])) {
    return;
}

$iv_e = function ($valor): string {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
};

$iv_ultima = $iv_kpi['ultima'] !== '' ? $iv_kpi['ultima'] : '—';

$iv_indicadores = [
    ['Facturas timbradas',  number_format($iv_kpi['facturas']),                'documento'],
    ['Importe facturado',   '$' . number_format($iv_kpi['importe'], 2),        'barras'],
    ['Clientes facturados', number_format($iv_kpi['clientes']),                'clientes'],
    ['Última timbrada',     $iv_ultima,                                        'documento'],
];

$iv_iconos = [
    'documento' => '<path d="M6 3h8l4 4v14H6zM14 3v5h4M9 12h6M9 16h6"/>',
    'barras'    => '<path d="M4 20V13h3v7M10 20V8h3v12M16 20V3h3v17"/>',
    'clientes'  => '<circle cx="9" cy="7" r="3"/><path d="M3 20v-3a6 6 0 0 1 12 0v3M16 4a3 3 0 0 1 0 6M17 13a5 5 0 0 1 4 5v2"/>',
];
?>
<div class="iv-kpis" aria-label="Resumen de facturación">
    <?php foreach ($iv_indicadores as $iv_item) { ?>
        <article class="iv-kpi">
            <div>
                <p class="iv-kpi__label"><?= $iv_e($iv_item[0]) ?></p>
                <strong class="iv-kpi__value"><?= $iv_e($iv_item[1]) ?></strong>
            </div>
            <span class="iv-kpi__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><?= $iv_iconos[$iv_item[2]] ?></svg>
            </span>
        </article>
    <?php } ?>
</div>