<?php /** @var  \gamboamartin\facturacion\controllers\controlador_fc_retenido_cp $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>
<?php echo $controlador->inputs->fc_partida_cp_id; ?>
<?php echo $controlador->inputs->descripcion; ?>
<?php echo $controlador->inputs->cat_sat_tipo_impuesto_id; ?>
<?php echo $controlador->inputs->cat_sat_tipo_factor_id; ?>
<?php echo $controlador->inputs->cat_sat_factor_id; ?>
<?php include (new views())->ruta_templates.'botons/submit/modifica_bd.php';?>