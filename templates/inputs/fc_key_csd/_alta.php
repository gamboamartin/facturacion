<?php /** @var  \gamboamartin\facturacion\controllers\controlador_fc_key_csd $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>
<?php echo $controlador->inputs->codigo; ?>
<?php echo $controlador->inputs->codigo_bis; ?>
<?php echo $controlador->inputs->documento; ?>
<?php echo $controlador->inputs->fc_csd_id; ?>
<?php include (new views())->ruta_templates.'botons/submit/alta_bd_otro.php';?>