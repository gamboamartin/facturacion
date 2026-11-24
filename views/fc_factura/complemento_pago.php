<?php
/** @var \gamboamartin\facturacion\controllers\controlador_fc_factura $controlador */
$cdf_config = is_array($controlador->complemento_desde_factura_config ?? null)
    ? $controlador->complemento_desde_factura_config : [];
$cdf_texto = static fn(mixed $valor): string => is_scalar($valor) ? (string)$valor : '';
$cdf_escape = static fn(mixed $valor): string => htmlspecialchars(
    $cdf_texto($valor), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$cdf_factura = is_array($cdf_config['factura'] ?? null) ? $cdf_config['factura'] : [];
$cdf_valores = is_array($cdf_config['valores'] ?? null) ? $cdf_config['valores'] : [];
$cdf_formas = is_array($cdf_config['formas_pago'] ?? null) ? $cdf_config['formas_pago'] : [];
$cdf_cambios = is_array($cdf_config['tipos_cambio'] ?? null) ? $cdf_config['tipos_cambio'] : [];
$cdf_csds = is_array($cdf_config['csds'] ?? null) ? $cdf_config['csds'] : [];
$cdf_id = static fn(mixed $valor): bool => (bool)preg_match('/^[1-9][0-9]*$/D', $cdf_texto($valor));
$cdf_formas = array_values(array_filter($cdf_formas, static fn(mixed $forma): bool =>
    is_array($forma) && $cdf_id($forma['id'] ?? '') && $cdf_texto($forma['codigo'] ?? '') !== '99'));
$cdf_cambios = array_values(array_filter($cdf_cambios, static fn(mixed $cambio): bool =>
    is_array($cambio) && $cdf_id($cambio['id'] ?? '') && $cdf_texto($cambio['moneda'] ?? '') !== 'XXX'));
$cdf_csds = array_values(array_filter($cdf_csds, static fn(mixed $csd): bool =>
    is_array($csd) && $cdf_id($csd['id'] ?? '')));
$cdf_url_lista = $cdf_texto($cdf_config['link_lista'] ?? '');
if (!preg_match('~^(?:\./)?index\.php\?[^\x00-\x20\x7f\\\\]*$~D', $cdf_url_lista)) {
    $cdf_url_lista = 'index.php?seccion=fc_factura&accion=lista';
}
$cdf_publica = [
    'endpoint' => $cdf_texto($cdf_config['endpoint'] ?? ''),
    'csrf' => $cdf_texto($cdf_config['csrf'] ?? ''),
    'clave_solicitud' => $cdf_texto($cdf_config['clave_solicitud'] ?? ''),
    'moneda_factura' => $cdf_texto($cdf_factura['moneda'] ?? ''),
];
$cdf_json = json_encode($cdf_publica, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
?>
<link rel="stylesheet" href="css/fc_factura/complemento_pago.css?v=1">
<div id="vista_fc_factura_complemento_pago" class="agentes-page cdf-page">
    <div class="agentes-header">
        <div class="agentes-header-info">
            <span class="page-icon" aria-hidden="true"><i class="bi bi-cash-coin"></i></span>
            <h1>Crear complemento de pago</h1>
        </div>
        <div class="agentes-header-actions">
            <a class="iv-btn iv-btn-secondary" href="<?= $cdf_escape($cdf_url_lista) ?>">Volver a facturas</a>
        </div>
    </div>

    <p class="cdf-introduccion">Revisa la factura y captura los datos del cobro. Al guardar se creará el complemento con el pago y la factura relacionada, para continuar su revisión.</p>
    <p id="cdf-aviso" class="cdf-aviso" role="status" aria-live="polite" tabindex="-1" hidden></p>
    <noscript><p class="cdf-aviso">Activa JavaScript para revisar y guardar el complemento de pago.</p></noscript>

    <section class="cdf-panel" aria-labelledby="cdf-factura-titulo">
        <h2 id="cdf-factura-titulo">Factura relacionada</h2>
        <dl class="cdf-referencia">
            <div><dt>Serie y folio</dt><dd><?= $cdf_escape($cdf_factura['folio'] ?? '') ?></dd></div>
            <div><dt>UUID de la factura</dt><dd><?= $cdf_escape($cdf_factura['uuid'] ?? '') ?></dd></div>
            <div><dt>Emisor</dt><dd><?= $cdf_escape($cdf_factura['emisor'] ?? '') ?></dd></div>
            <div><dt>Cliente</dt><dd><?= $cdf_escape($cdf_factura['cliente'] ?? '') ?></dd></div>
            <div><dt>Moneda de la factura</dt><dd><?= $cdf_escape($cdf_factura['moneda'] ?? '') ?></dd></div>
            <div><dt>Saldo pendiente</dt><dd><?= $cdf_escape($cdf_factura['saldo'] ?? '') ?> <?= $cdf_escape($cdf_factura['moneda'] ?? '') ?></dd></div>
            <div><dt>Siguiente parcialidad</dt><dd><?= $cdf_escape($cdf_factura['parcialidad'] ?? '') ?></dd></div>
        </dl>
        <p class="cdf-ayuda">El saldo y la parcialidad se volverán a comprobar al guardar.</p>
    </section>

    <section class="cdf-panel" aria-labelledby="cdf-pago-titulo">
        <h2 id="cdf-pago-titulo">Datos del pago recibido</h2>
        <p>Indica la fecha y la forma en que recibiste el pago. Puedes ajustar el importe propuesto para registrar un pago parcial.</p>
        <?php if (!$cdf_formas || !$cdf_cambios): ?>
            <p class="cdf-aviso" role="alert">Faltan formas de pago o tipos de cambio disponibles. Revisa la configuración antes de crear el complemento.</p>
        <?php endif; ?>
        <form id="cdf-formulario" method="post" action="index.php" data-catalogos-disponibles="<?= $cdf_formas && $cdf_cambios ? '1' : '0' ?>">
            <div class="cdf-campos">
                <?php if ($cdf_csds): ?>
                    <div class="cdf-campo cdf-campo-ancho">
                        <label for="cdf-csd">Certificado del emisor</label>
                        <select id="cdf-csd" name="fc_csd_id" required>
                            <option value="">Selecciona un certificado</option>
                            <?php foreach ($cdf_csds as $cdf_csd): ?>
                                <option value="<?= $cdf_escape($cdf_csd['id']) ?>"<?= $cdf_texto($cdf_valores['fc_csd_id'] ?? '') === $cdf_texto($cdf_csd['id']) ? ' selected' : '' ?>><?= $cdf_escape($cdf_csd['descripcion'] ?? $cdf_csd['numero_serie'] ?? $cdf_csd['id']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="cdf-ayuda">Los certificados disponibles corresponden al emisor de la factura.</p>
                    </div>
                <?php endif; ?>
                <div class="cdf-campo">
                    <label for="cdf-fecha">Fecha y hora del pago</label>
                    <input id="cdf-fecha" name="fecha_pago" type="datetime-local" step="1" required value="<?= $cdf_escape($cdf_valores['fecha_pago'] ?? '') ?>" aria-describedby="cdf-fecha-ayuda">
                    <p id="cdf-fecha-ayuda" class="cdf-ayuda">Captura la fecha real del cobro.</p>
                </div>
                <div class="cdf-campo">
                    <label for="cdf-forma">Forma de pago recibida</label>
                    <select id="cdf-forma" name="cat_sat_forma_pago_id" required>
                        <option value="">Selecciona la forma de pago</option>
                        <?php foreach ($cdf_formas as $cdf_forma): ?>
                            <option value="<?= $cdf_escape($cdf_forma['id']) ?>"<?= $cdf_texto($cdf_valores['cat_sat_forma_pago_id'] ?? '') === $cdf_texto($cdf_forma['id']) ? ' selected' : '' ?>><?= $cdf_escape($cdf_forma['codigo'] ?? '') ?> · <?= $cdf_escape($cdf_forma['descripcion'] ?? '') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="cdf-campo">
                    <label for="cdf-cambio">Moneda y tipo de cambio del pago</label>
                    <select id="cdf-cambio" name="com_tipo_cambio_id" required aria-describedby="cdf-cambio-ayuda">
                        <option value="">Selecciona la moneda y el tipo de cambio</option>
                        <?php foreach ($cdf_cambios as $cdf_cambio): ?>
                            <option value="<?= $cdf_escape($cdf_cambio['id']) ?>" data-moneda="<?= $cdf_escape($cdf_cambio['moneda'] ?? '') ?>"<?= $cdf_texto($cdf_valores['com_tipo_cambio_id'] ?? '') === $cdf_texto($cdf_cambio['id']) ? ' selected' : '' ?>><?= $cdf_escape($cdf_cambio['moneda'] ?? '') ?> · <?= $cdf_escape($cdf_cambio['monto'] ?? '') ?> · <?= $cdf_escape($cdf_cambio['fecha'] ?? '') ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p id="cdf-cambio-ayuda" class="cdf-ayuda">Confirma el tipo de cambio que corresponda al pago recibido.</p>
                </div>
                <div class="cdf-campo">
                    <label for="cdf-monto">Importe del pago <span id="cdf-moneda-pago"><?= $cdf_escape($cdf_factura['moneda'] ?? '') ?></span></label>
                    <input id="cdf-monto" name="monto" type="number" min="0.01" step="0.01" required inputmode="decimal" value="<?= $cdf_escape($cdf_valores['monto'] ?? $cdf_factura['saldo'] ?? '') ?>" aria-describedby="cdf-monto-ayuda">
                    <p id="cdf-monto-ayuda" class="cdf-ayuda" aria-live="polite">Importe propuesto con el saldo pendiente de la factura. Revisa el importe en la moneda del pago.</p>
                </div>
            </div>
            <label class="cdf-confirmacion" for="cdf-confirmar">
                <input id="cdf-confirmar" type="checkbox" required>
                <span>Confirmo que la fecha, la forma de pago y el importe corresponden al pago recibido.</span>
            </label>
            <p class="cdf-ayuda">Al guardar se registrará el pago y se actualizará el saldo de la factura. El complemento quedará pendiente de timbrado.</p>
            <div class="cdf-acciones">
                <button id="cdf-guardar" type="submit" class="iv-btn iv-btn-primary" disabled>Crear complemento de pago</button>
            </div>
        </form>
    </section>
    <script id="cdf-config" type="application/json"><?= $cdf_json ?></script>
</div>
