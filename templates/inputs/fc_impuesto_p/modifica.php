<?php /** @var  gamboamartin\facturacion\controllers\controlador_fc_impouesto_p $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>
<?php echo $controlador->inputs->fc_pago_pago_id; ?>
<?php echo $controlador->inputs->codigo; ?>
<?php include (new views())->ruta_templates.'botons/submit/alta_bd.php';?>