<?php /** @var  gamboamartin\facturacion\controllers\controlador_fc_retencion_p_part $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>
<?php echo $controlador->inputs->fc_retencion_p_id; ?>
<?php echo $controlador->inputs->cat_sat_tipo_impuesto_id; ?>
<?php echo $controlador->inputs->codigo; ?>
<?php echo $controlador->inputs->base_p; ?>
<?php echo $controlador->inputs->importe_p; ?>
<?php include (new views())->ruta_templates.'botons/submit/alta_bd.php';?>