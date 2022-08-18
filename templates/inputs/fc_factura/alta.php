<?php /** @var  \gamboamartin\facturacion\controllers\controlador_fc_factura $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>
<?php echo $controlador->inputs->select->org_sucursal_id; ?>
<?php echo $controlador->inputs->select->com_sucursal_id; ?>
<?php echo $controlador->inputs->folio; ?>
<?php echo $controlador->inputs->fecha; ?>
<?php echo $controlador->inputs->exportacion; ?>
<?php echo $controlador->inputs->version; ?>
<?php echo $controlador->inputs->serie; ?>
<?php echo $controlador->inputs->subtotal; ?>
<?php echo $controlador->inputs->descuento; ?>
<?php echo $controlador->inputs->total; ?>
<?php echo $controlador->inputs->select->cat_sat_tipo_de_comprobante_id; ?>
<?php echo $controlador->inputs->select->cat_sat_forma_pago_id; ?>
<?php echo $controlador->inputs->select->cat_sat_metodo_pago_id; ?>
<?php echo $controlador->inputs->select->cat_sat_moneda_id; ?>
<?php echo $controlador->inputs->select->cat_sat_uso_cfdi_id; ?>
<?php include (new views())->ruta_templates.'botons/submit/alta_bd.php';?>