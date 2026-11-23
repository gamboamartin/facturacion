<?php
/** @var \gamboamartin\facturacion\controllers\controlador_fc_factura $controlador */
$globalValores = is_array($controlador->valores ?? null) ? $controlador->valores : [];
if (is_object($controlador->row_upd ?? null)) {
    $globalValores = array_replace($globalValores, get_object_vars($controlador->row_upd));
}
$globalValor = static function (string $campo) use ($globalValores): string {
    $valor = array_key_exists($campo, $_POST) ? $_POST[$campo] : ($globalValores[$campo] ?? '');
    return is_string($valor) || is_int($valor) ? (string)$valor : '';
};
$globalEscape = static fn (string $valor): string => htmlspecialchars($valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$globalAnioActual = (int)(new DateTimeImmutable('now', new DateTimeZone('America/Mexico_City')))->format('Y');
$globalAnioMinimo = max(2019, $globalAnioActual - 5);
$globalPeriodicidades = ['01' => 'Diaria', '02' => 'Semanal', '03' => 'Quincenal', '04' => 'Mensual', '05' => 'Bimestral'];
$globalMeses = [
    '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
    '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
    '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre',
    '13' => 'Enero-Febrero', '14' => 'Marzo-Abril', '15' => 'Mayo-Junio',
    '16' => 'Julio-Agosto', '17' => 'Septiembre-Octubre', '18' => 'Noviembre-Diciembre',
];
$globalContexto = ['sucursales' => [], 'tipos_ingreso' => []];
foreach (array_keys($globalContexto) as $grupo) {
    foreach (($controlador->informacion_global_contexto[$grupo] ?? []) as $id) {
        if ((is_int($id) || is_string($id)) && preg_match('/^[1-9][0-9]*$/D', (string)$id)) {
            $globalContexto[$grupo][] = (string)$id;
        }
    }
}
$globalAplica = in_array($globalValor('com_sucursal_id'), $globalContexto['sucursales'], true)
    && in_array($globalValor('cat_sat_tipo_de_comprobante_id'), $globalContexto['tipos_ingreso'], true);
?>
<p id="fc-global-aviso" role="status" hidden>Se limpiaron los datos de información global porque no corresponden al cliente o al tipo de comprobante seleccionado.</p>
<section id="fc-global-information" class="fc-alta-section fc-global-information" aria-labelledby="fc-global-title"<?php echo $globalAplica ? '' : ' hidden'; ?>>
    <div class="fc-alta-section-heading">
        <div>
            <h2 id="fc-global-title">Información global (público en general)</h2>
            <p id="fc-global-help">Obligatoria para facturas de ingreso al RFC XAXX010101000 con receptor PUBLICO EN GENERAL. Captura el periodo real de las operaciones; estos datos no se completan automáticamente.</p>
        </div>
    </div>
    <div class="fc-alta-fields fc-edit-fields">
        <div class="control-group">
            <label for="global_periodicidad">Periodicidad</label>
            <select class="form-control" id="global_periodicidad" name="global_periodicidad" aria-describedby="fc-global-help fc-global-period-help"<?php echo $globalAplica ? ' required' : ''; ?>>
                <option value="">Selecciona la periodicidad</option>
                <?php foreach ($globalPeriodicidades as $codigo => $nombre): ?>
                    <?php $codigo = (string)$codigo; ?>
                    <option value="<?php echo $codigo; ?>"<?php echo $globalValor('global_periodicidad') === $codigo ? ' selected' : ''; ?>><?php echo $codigo . ' - ' . $nombre; ?></option>
                <?php endforeach; ?>
            </select>
            <p id="fc-global-period-help">La periodicidad bimestral requiere régimen 621 del emisor.</p>
        </div>
        <div class="control-group">
            <label for="global_meses">Mes o bimestre</label>
            <select class="form-control" id="global_meses" name="global_meses" aria-describedby="fc-global-help fc-global-month-help"<?php echo $globalAplica ? ' required' : ''; ?>>
                <option value="">Selecciona el mes o bimestre</option>
                <?php foreach ($globalMeses as $codigo => $nombre): ?>
                    <?php $codigo = (string)$codigo; ?>
                    <option value="<?php echo $codigo; ?>"<?php echo $globalValor('global_meses') === $codigo ? ' selected' : ''; ?>><?php echo $codigo . ' - ' . $nombre; ?></option>
                <?php endforeach; ?>
            </select>
            <p id="fc-global-month-help">Usa un mes con periodicidad diaria, semanal, quincenal o mensual; un bimestre con periodicidad bimestral.</p>
        </div>
        <div class="control-group">
            <label for="global_anio">Año de las operaciones</label>
            <input class="form-control" type="number" id="global_anio" name="global_anio" min="<?php echo $globalAnioMinimo; ?>" max="<?php echo $globalAnioActual; ?>" step="1" placeholder="AAAA" value="<?php echo $globalEscape($globalValor('global_anio')); ?>" aria-describedby="fc-global-help"<?php echo $globalAplica ? ' required' : ''; ?>>
        </div>
    </div>
</section>
<script type="application/json" id="fc-global-contexto"><?php echo json_encode($globalContexto, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR); ?></script>
<script src="js/fc_factura/informacion-global.js?v=1" defer></script>
