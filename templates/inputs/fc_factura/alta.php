<?php /** @var  \gamboamartin\facturacion\controllers\controlador_fc_factura $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>
<?php echo $controlador->inputs->plantillas; ?>
<?php echo $controlador->inputs->fc_csd_id; ?>
<?php echo $controlador->inputs->com_sucursal_id; ?>
<?php echo $controlador->inputs->serie; ?>
<?php echo $controlador->inputs->folio; ?>
<?php echo $controlador->inputs->exportacion; ?>
<?php echo $controlador->inputs->fecha; ?>
<?php echo $controlador->inputs->cat_sat_tipo_de_comprobante_id; ?>
<?php echo $controlador->inputs->cat_sat_metodo_pago_id; ?>
<?php echo $controlador->inputs->cat_sat_forma_pago_id; ?>
<?php echo $controlador->inputs->cat_sat_moneda_id; ?>
<?php echo $controlador->inputs->com_tipo_cambio_id; ?>
<?php echo $controlador->inputs->cat_sat_uso_cfdi_id; ?>
<?php echo $controlador->inputs->observaciones; ?>
<?php include (new views())->ruta_templates.'botons/submit/alta_bd.php';?>

<!-- Modal -->
<div id="myModal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-lg" style="top: 90px;>
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


