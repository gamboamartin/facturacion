<?php /** @var  \gamboamartin\facturacion\controllers\controlador_fc_uuid_rel_nc $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>

<?php echo $controlador->inputs->fc_cfdi_id; ?>
<?php echo $controlador->inputs->fc_relacion_nc_id; ?>
<?php include (new views())->ruta_templates.'botons/submit/alta_bd.php';?>

