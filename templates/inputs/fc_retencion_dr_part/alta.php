<?php /** @var  gamboamartin\facturacion\controllers\controlador_fc_retencion_dr_part $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>
<?php echo $controlador->inputs->fc_retencion_dr_id; ?>
<?php echo $controlador->inputs->codigo; ?>
<?php echo $controlador->inputs->cat_sat_tipo_impuesto_id; ?>
<?php echo $controlador->inputs->cat_sat_tipo_factor_id; ?>
<?php echo $controlador->inputs->cat_sat_factor_id; ?>
<?php echo $controlador->inputs->base_dr; ?>
<?php echo $controlador->inputs->importe_dr; ?>
<?php include (new views())->ruta_templates.'botons/submit/alta_bd.php';?>


