<?php
/** @var \gamboamartin\facturacion\controllers\controlador_com_cliente $controlador */
/** @var bool $cliente_es_alta */
?>
<?php if ($cliente_es_alta): ?>
<section class="com-cliente-cif" aria-labelledby="cliente-cif-titulo">
    <div class="com-cliente-cif-intro"><span class="com-cliente-cif-icon" aria-hidden="true"><i class="bi bi-file-earmark-pdf"></i></span><div>
        <span class="com-cliente-pill">CAPTURA MÁS RÁPIDO</span><h2 id="cliente-cif-titulo">Comienza con su CIF</h2>
        <p>Selecciona el PDF de la constancia fiscal con código QR para precargar los datos disponibles. También puedes capturarlos manualmente.</p>
    </div></div>
    <div class="com-cliente-cif-upload"><?php echo $controlador->inputs->documento; ?>
        <p class="com-cliente-help">La lectura del documento no guarda al cliente. Revisa la información y pulsa «Guardar cliente».</p>
        <p id="cliente-cif-estado" class="com-cliente-cif-status" role="status" aria-live="polite">PDF con QR legible · Opcional</p>
    </div>
</section>
<?php endif; ?>
<div id="cliente-form-estado" class="com-cliente-form-status" role="alert" hidden></div>
<section class="com-cliente-card com-cliente-section" aria-labelledby="cliente-identidad-titulo">
    <div class="com-cliente-section-heading"><span class="com-cliente-step" aria-hidden="true">01</span><div><h2 id="cliente-identidad-titulo">Identidad y contacto</h2><p>Los datos que te ayudarán a identificar y localizar al cliente.</p></div></div>
    <div class="com-cliente-fields">
        <?php if ($cliente_es_alta && $controlador->aplica_relacion_agentes) { echo $controlador->inputs->input_select_agente_asesor; } ?>
        <?php echo $controlador->inputs->com_tipo_cliente_id; ?>
        <?php echo $controlador->inputs->codigo; ?>
        <?php echo $controlador->inputs->razon_social; ?>
        <?php echo $controlador->inputs->com_cliente_rfc; ?>
        <?php echo $controlador->inputs->telefono; ?>
    </div>
</section>
<section class="com-cliente-card com-cliente-section" aria-labelledby="cliente-fiscal-titulo">
    <div class="com-cliente-section-heading"><span class="com-cliente-step" aria-hidden="true">02</span><div><h2 id="cliente-fiscal-titulo">Información fiscal</h2><p>Comprueba que corresponda a la constancia fiscal del cliente.</p></div></div>
    <div class="com-cliente-fields">
        <?php echo $controlador->inputs->cat_sat_tipo_persona_id; ?>
        <?php echo $controlador->inputs->cat_sat_regimen_fiscal_id; ?>
        <?php echo $controlador->inputs->cat_sat_uso_cfdi_id; ?>
    </div>
</section>
<section class="com-cliente-card com-cliente-section" aria-labelledby="cliente-domicilio-titulo">
    <div class="com-cliente-section-heading"><span class="com-cliente-step" aria-hidden="true">03</span><div><h2 id="cliente-domicilio-titulo">Domicilio fiscal</h2><p>Selecciona país, estado y municipio; después completa la dirección.</p></div></div>
    <div class="com-cliente-fields">
        <?php echo $controlador->inputs->dp_pais_id; ?>
        <?php echo $controlador->inputs->dp_estado_id; ?>
        <?php echo $controlador->inputs->dp_municipio_id; ?>
        <?php echo $controlador->inputs->cp; ?>
        <?php echo $controlador->inputs->colonia; ?>
        <?php echo $controlador->inputs->calle; ?>
        <?php echo $controlador->inputs->numero_exterior; ?>
        <?php echo $controlador->inputs->numero_interior; ?>
    </div>
</section>
<section class="com-cliente-card com-cliente-section" aria-labelledby="cliente-pago-titulo">
    <div class="com-cliente-section-heading"><span class="com-cliente-step" aria-hidden="true">04</span><div><h2 id="cliente-pago-titulo">Preferencias de facturación</h2><p>Valores de referencia para los comprobantes del cliente.</p></div></div>
    <div class="com-cliente-fields">
        <?php echo $controlador->inputs->cat_sat_metodo_pago_id; ?>
        <?php echo $controlador->inputs->cat_sat_forma_pago_id; ?>
        <?php echo $controlador->inputs->cat_sat_tipo_de_comprobante_id; ?>
        <?php echo $controlador->inputs->cat_sat_moneda_id; ?>
    </div>
    <p class="com-cliente-help" id="cliente-pago-ayuda" aria-live="polite">La forma de pago debe ser compatible con el método seleccionado.</p>
</section>
