<?php /** @var  gamboamartin\facturacion\controllers\controlador_fc_pago $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>
<?php echo $controlador->inputs->fc_pago_id; ?>
<?php echo $controlador->inputs->codigo; ?>
<?php echo $controlador->inputs->cat_sat_forma_pago_id; ?>
<?php echo $controlador->inputs->cat_sat_moneda_id; ?>
<?php echo $controlador->inputs->com_tipo_cambio_id; ?>
<?php echo $controlador->inputs->monto; ?>
<?php include (new views())->ruta_templates.'botons/submit/alta_bd.php';?>


