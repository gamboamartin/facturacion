<?php
/** @var \gamboamartin\facturacion\controllers\controlador_fc_factura $controlador */
$tm_config = isset($controlador->timbrado_masivo_config) && is_array($controlador->timbrado_masivo_config)
    ? $controlador->timbrado_masivo_config : [];
$tm_texto = static fn(mixed $valor): string => is_scalar($valor) ? (string)$valor : '';
$tm_escape = static fn(string $valor): string => htmlspecialchars($valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$tm_link_lista = trim($tm_texto($tm_config['link_lista'] ?? ''));
$tm_scheme = parse_url($tm_link_lista, PHP_URL_SCHEME);
if ($tm_link_lista === '' || preg_match('/[\x00-\x20\x7f]/', $tm_link_lista) ||
    str_contains($tm_link_lista, '\\') || $tm_scheme === false ||
    ($tm_scheme !== null && !in_array(strtolower($tm_scheme), ['http', 'https'], true))) {
    $tm_link_lista = 'index.php?seccion=fc_factura&accion=lista';
}
$tm_publica = [
    'endpoint' => $tm_texto($tm_config['endpoint'] ?? ''),
    'csrf' => $tm_texto($tm_config['csrf'] ?? ''),
    'maximo' => max(1, min(100, (int)($tm_config['maximo'] ?? 100))),
    'link_lista' => $tm_link_lista,
    'lote_id' => $tm_texto($tm_config['lote_id'] ?? ''),
];
$tm_json = json_encode($tm_publica, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT |
    JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
$tm_emisores = is_array($tm_config['emisores'] ?? null) ? $tm_config['emisores'] : [];
?>
<link rel="stylesheet" href="css/fc_factura/timbrado_masivo.css?v=1">
<div id="vista_fc_factura_timbrado_masivo" class="agentes-page">
    <div class="agentes-header">
        <div class="agentes-header-info">
            <span class="page-icon" aria-hidden="true"><i class="bi bi-receipt"></i></span>
            <h1 id="tm-titulo">Timbrado masivo</h1>
        </div>
        <div class="agentes-header-actions">
            <a class="iv-btn iv-btn-secondary" href="<?= $tm_escape($tm_link_lista) ?>">Volver a facturas</a>
        </div>
    </div>

    <p id="tm-aviso" class="tm-aviso" role="status" aria-live="polite" hidden></p>
    <noscript><p class="tm-aviso">Activa JavaScript para consultar y seleccionar las facturas.</p></noscript>

    <section id="tm-pendientes" aria-labelledby="tm-pendientes-titulo">
        <h2 id="tm-pendientes-titulo">Facturas pendientes de timbrar</h2>
        <p>Filtra las facturas y revisa la selección antes de timbrar. Máximo <?= $tm_publica['maximo'] ?> facturas por lote.</p>
        <form id="tm-filtros" class="tm-filtros">
            <div><label for="tm-fecha-inicio">Fecha inicial</label><input id="tm-fecha-inicio" name="fecha_inicio" type="date"></div>
            <div><label for="tm-fecha-fin">Fecha final</label><input id="tm-fecha-fin" name="fecha_fin" type="date"></div>
            <div><label for="tm-serie">Serie</label><input id="tm-serie" name="serie" type="text" maxlength="100"></div>
            <div><label for="tm-folio">Folio</label><input id="tm-folio" name="folio" type="text" maxlength="100"></div>
            <div><label for="tm-rfc">RFC del cliente</label><input id="tm-rfc" name="rfc" type="text" maxlength="13" autocomplete="off"></div>
            <div><label for="tm-cliente">Cliente</label><input id="tm-cliente" name="cliente" type="text" maxlength="255"></div>
            <div><label for="tm-emisor">Emisor</label><select id="tm-emisor" name="emisor_id">
                <option value="">Todos los emisores</option>
                <?php foreach ($tm_emisores as $tm_emisor): ?>
                    <?php if (!is_array($tm_emisor) || !preg_match('/^[1-9][0-9]*$/D', $tm_texto($tm_emisor['id'] ?? ''))) continue; ?>
                    <option value="<?= $tm_escape($tm_texto($tm_emisor['id'])) ?>"><?= $tm_escape($tm_texto($tm_emisor['nombre'] ?? '')) ?></option>
                <?php endforeach; ?>
            </select></div>
            <div><label for="tm-total-min">Total desde</label><input id="tm-total-min" name="total_min" type="number" min="0" step="0.01"></div>
            <div><label for="tm-total-max">Total hasta</label><input id="tm-total-max" name="total_max" type="number" min="0" step="0.01"></div>
            <div class="tm-filtros-acciones">
                <button id="tm-filtrar" type="submit" class="iv-btn iv-btn-primary">Aplicar filtros</button>
                <button id="tm-limpiar" type="button" class="iv-btn iv-btn-secondary">Limpiar</button>
            </div>
        </form>

        <div class="tm-seleccion">
            <p id="tm-conteo" aria-live="polite">Consultando facturas…</p>
            <div class="tm-acciones">
                <button id="tm-todas" type="button" class="iv-btn iv-btn-secondary" disabled>Todas las que cumplen el filtro</button>
                <button id="tm-quitar-seleccion" type="button" class="iv-btn iv-btn-secondary" disabled>Quitar selección</button>
                <button id="tm-revisar" type="button" class="iv-btn iv-btn-primary" disabled>Revisar selección</button>
            </div>
            <p id="tm-seleccion-detalle" class="tm-ayuda" aria-live="polite">Ninguna factura seleccionada.</p>
        </div>

        <div class="agents-table-card">
            <div class="table-scroll" role="region" aria-label="Facturas pendientes" tabindex="0">
                <table class="table agents-table tm-tabla">
                    <caption class="tm-sr-only">Facturas pendientes que cumplen los filtros</caption>
                    <thead><tr>
                        <th scope="col"><label class="tm-checkbox"><input id="tm-pagina-todas" type="checkbox" disabled><span>Seleccionar página</span></label></th>
                        <th scope="col">Serie / folio</th><th scope="col">Cliente</th><th scope="col">RFC</th>
                        <th scope="col">Emisor</th><th scope="col">Fecha</th><th scope="col">Total</th><th scope="col">Estado</th>
                    </tr></thead>
                    <tbody id="tm-filas"><tr><td colspan="8">Consultando facturas…</td></tr></tbody>
                </table>
            </div>
        </div>
        <nav class="tm-paginacion" aria-label="Páginas de facturas">
            <button id="tm-anterior" type="button" class="iv-btn iv-btn-secondary" disabled>Anterior</button>
            <span id="tm-pagina-estado">Página 1</span>
            <button id="tm-siguiente" type="button" class="iv-btn iv-btn-secondary" disabled>Siguiente</button>
        </nav>

        <section id="tm-confirmacion" class="tm-confirmacion" aria-labelledby="tm-confirmacion-titulo" hidden>
            <h2 id="tm-confirmacion-titulo" tabindex="-1">Revisar timbrado</h2>
            <p id="tm-confirmacion-resumen"></p>
            <ul id="tm-confirmacion-filtros"></ul>
            <p>Al confirmar se inicia el timbrado de las facturas seleccionadas. Mantén esta página abierta mientras se procesa el lote.</p>
            <div class="tm-acciones">
                <button id="tm-confirmar" type="button" class="iv-btn iv-btn-primary">Confirmar y timbrar</button>
                <button id="tm-cancelar" type="button" class="iv-btn iv-btn-secondary">Volver a la selección</button>
            </div>
        </section>
    </section>

    <section id="tm-resultados" aria-labelledby="tm-resultados-titulo" hidden>
        <h2 id="tm-resultados-titulo" tabindex="-1">Resultados de timbrado</h2>
        <p class="tm-ayuda">Mantén esta página abierta para procesar el lote. Si sales, vuelve a esta dirección y pulsa «Consultar y reanudar pendientes». Al abrirla no se timbra automáticamente.</p>
        <div id="tm-resumen" class="tm-resumen" aria-live="polite"></div>
        <label for="tm-progreso">Avance del lote</label>
        <progress id="tm-progreso" value="0" max="1"></progress>
        <p id="tm-progreso-texto" role="status" aria-live="polite">Consultando estado del lote…</p>
        <div class="tm-acciones">
            <button id="tm-consultar" type="button" class="iv-btn iv-btn-secondary" disabled>Consultar estado</button>
            <button id="tm-reanudar" type="button" class="iv-btn iv-btn-primary" disabled>Consultar y reanudar pendientes</button>
            <button id="tm-pausar" type="button" class="iv-btn iv-btn-secondary" hidden>Pausar después de la factura actual</button>
        </div>
        <div class="agents-table-card">
            <div class="table-scroll" role="region" aria-label="Resultados por factura" tabindex="0">
                <table class="table agents-table tm-tabla">
                    <caption class="tm-sr-only">Estado persistido de cada factura del lote</caption>
                    <thead><tr><th scope="col">Factura</th><th scope="col">Folio</th><th scope="col">Estado</th><th scope="col">Resultado</th><th scope="col">UUID</th></tr></thead>
                    <tbody id="tm-resultados-filas"><tr><td colspan="5">Consultando estado del lote…</td></tr></tbody>
                </table>
            </div>
        </div>
    </section>
    <script type="application/json" id="tm-config"><?= $tm_json ?></script>
</div>
