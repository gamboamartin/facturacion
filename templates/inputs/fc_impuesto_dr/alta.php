<?php /** @var  gamboamartin\facturacion\controllers\controlador_fc_impuesto_dr $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>
<?php echo $controlador->inputs->codigo; ?>
<?php echo $controlador->inputs->fc_docto_relacionado_id; ?>
<?php include (new views())->ruta_templates.'botons/submit/alta_bd.php';?>