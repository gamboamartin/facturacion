<?php /** @var  gamboamartin\facturacion\controllers\controlador_fc_retencion_dr $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>
<?php echo $controlador->inputs->fc_impuesto_dr_id; ?>
<?php echo $controlador->inputs->codigo; ?>
<?php include (new views())->ruta_templates.'botons/submit/modifica_bd.php';?>


