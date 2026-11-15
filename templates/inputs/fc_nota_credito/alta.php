<?php /** @var  \gamboamartin\facturacion\controllers\controlador_fc_factura $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>
<section class="fc-alta-section" aria-labelledby="nc-alta-issuer-title">
    <div class="fc-alta-section-heading"><span class="fc-alta-step" aria-hidden="true">01</span><div>
        <h2 id="nc-alta-issuer-title">Emisor y cliente</h2>
        <p>Selecciona el certificado y la sucursal del cliente.</p>
    </div></div>
    <div class="fc-alta-fields">
<?php echo $controlador->inputs->fc_csd_id; ?>
<?php echo $controlador->inputs->com_sucursal_id; ?>
    </div>
</section>
<section class="fc-alta-section" aria-labelledby="nc-alta-document-title">
    <div class="fc-alta-section-heading"><span class="fc-alta-step" aria-hidden="true">02</span><div>
        <h2 id="nc-alta-document-title">Datos del comprobante</h2>
        <p>Revisa la serie, el folio y la fecha de emisión.</p>
    </div></div>
    <div class="fc-alta-fields">
<?php echo $controlador->inputs->serie; ?>
<?php echo $controlador->inputs->folio; ?>
<?php echo $controlador->inputs->exportacion; ?>
<?php echo $controlador->inputs->fecha; ?>
<?php echo $controlador->inputs->cat_sat_tipo_de_comprobante_id; ?>
    </div>
</section>
<section class="fc-alta-section" aria-labelledby="nc-alta-payment-title">
    <div class="fc-alta-section-heading"><span class="fc-alta-step" aria-hidden="true">03</span><div>
        <h2 id="nc-alta-payment-title">Pago y detalles</h2>
        <p>Confirma las condiciones de pago y el uso del CFDI.</p>
    </div></div>
    <div class="fc-alta-fields">
<?php echo $controlador->inputs->cat_sat_forma_pago_id; ?>
<?php echo $controlador->inputs->cat_sat_metodo_pago_id; ?>
<?php echo $controlador->inputs->cat_sat_moneda_id; ?>
<?php echo $controlador->inputs->com_tipo_cambio_id; ?>
<?php echo $controlador->inputs->cat_sat_uso_cfdi_id; ?>
<?php echo $controlador->inputs->observaciones; ?>
    </div>
</section>
<div class="fc-alta-actions">
<?php include (new views())->ruta_templates.'botons/submit/alta_bd.php';?>
</div>
