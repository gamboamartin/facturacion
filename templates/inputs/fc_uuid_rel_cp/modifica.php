<?php /** @var  \gamboamartin\facturacion\controllers\controlador_fc_uuid_rel_cp $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>

<?php echo $controlador->inputs->fc_cfdi_id; ?>
<?php echo $controlador->inputs->fc_relacion_cp_id; ?>
<?php echo $controlador->inputs->descripcion; ?>
<?php include (new views())->ruta_templates.'botons/submit/modifica_bd.php';?>