<?php /** @var  \gamboamartin\facturacion\controllers\controlador_fc_factura $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>
<?php echo $controlador->inputs->fc_csd_id; ?>
<?php echo $controlador->inputs->com_sucursal_id; ?>
<?php echo $controlador->inputs->cat_sat_tipo_de_comprobante_id; ?>
<?php echo $controlador->inputs->uuid; ?>
<?php echo $controlador->inputs->fecha; ?>
<?php echo $controlador->inputs->folio; ?>
<?php echo $controlador->inputs->total; ?>
<?php include (new views())->ruta_templates.'botons/submit/alta_bd.php';?>


