<?php /** @var  gamboamartin\cat_sat\controllers\controlador_cat_sat_motivo_cancelacion $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>
<?php echo $controlador->inputs->fc_factura_id; ?>
<?php echo $controlador->inputs->cat_sat_motivo_cancelacion_id; ?>
<?php include (new views())->ruta_templates.'botons/submit/alta_bd.php';?>


