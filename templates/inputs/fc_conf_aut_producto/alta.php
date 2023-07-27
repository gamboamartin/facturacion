<?php /** @var  gamboamartin\facturacion\controllers\controlador_fc_relacion $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>
<?php echo $controlador->inputs->com_producto_id; ?>
<?php echo $controlador->inputs->fc_conf_automatico_id; ?>
<?php echo $controlador->inputs->cantidad; ?>
<?php include (new views())->ruta_templates.'botons/submit/alta_bd.php';?>


