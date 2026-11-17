<?php /** @var  \gamboamartin\facturacion\controllers\controlador_fc_factura $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>
<section class="fc-alta-section" aria-labelledby="fc-alta-issuer-title">
    <div class="fc-alta-section-heading"><span class="fc-alta-step" aria-hidden="true">01</span><div>
        <h2 id="fc-alta-issuer-title">Emisor y cliente</h2>
        <p>Selecciona el certificado y la sucursal del cliente.</p>
    </div></div>
    <div class="fc-alta-fields">
<?php echo $controlador->inputs->plantillas; ?>
<?php echo $controlador->inputs->fc_csd_id; ?>
<?php echo $controlador->inputs->com_sucursal_id; ?>
    </div>
</section>
<section class="fc-alta-section" aria-labelledby="fc-alta-document-title">
    <div class="fc-alta-section-heading"><span class="fc-alta-step" aria-hidden="true">02</span><div>
        <h2 id="fc-alta-document-title">Datos del comprobante</h2>
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
<section class="fc-alta-section" aria-labelledby="fc-alta-payment-title">
    <div class="fc-alta-section-heading"><span class="fc-alta-step" aria-hidden="true">03</span><div>
        <h2 id="fc-alta-payment-title">Pago y detalles</h2>
        <p>Confirma las condiciones de pago y el uso del CFDI.</p>
    </div></div>
    <div class="fc-alta-fields">
<?php echo $controlador->inputs->cat_sat_metodo_pago_id; ?>
<?php echo $controlador->inputs->cat_sat_forma_pago_id; ?>
<?php echo $controlador->inputs->cat_sat_moneda_id; ?>
<?php echo $controlador->inputs->com_tipo_cambio_id; ?>
<?php echo $controlador->inputs->cat_sat_uso_cfdi_id; ?>
<?php echo $controlador->inputs->observaciones; ?>
<?php if (isset($controlador->inputs->input_select_agente_operador)): ?>
    <?php echo $controlador->inputs->input_select_agente_operador; ?>
<?php endif; ?>

<?php if (isset($controlador->inputs->input_select_agente_asesor)): ?>
    <?php echo $controlador->inputs->input_select_agente_asesor; ?>
<?php endif; ?>
    </div>
</section>
<div class="fc-alta-actions">
<?php include (new views())->ruta_templates.'botons/submit/alta_bd.php';?>
</div>

<!-- Modal -->
<div id="myModal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-lg" style="top: 90px;">
        <div class="modal-content">
    <div class="modal-header bg-danger" style="border-top-left-radius: 8px;border-top-right-radius: 8px;">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title" style="padding-bottom: 10px;">Error</h4>
    </div>
    <div class="modal-body" style = "padding: 20px !important;background-color: #fff;border-bottom-left-radius: 8px;border-bottom-right-radius: 8px;">
        <h5>Seleccione una forma de pago valida</h5>
    </div>
</div>
</div>
</div>


