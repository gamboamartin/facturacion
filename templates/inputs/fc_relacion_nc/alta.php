<?php /** @var  \gamboamartin\facturacion\controllers\controlador_fc_relacion_nc $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>

<?php echo $controlador->inputs->fc_nota_credito_id; ?>
<?php echo $controlador->inputs->cat_sat_tipo_relacion_id; ?>
<?php echo $controlador->inputs->descripcion; ?>

<?php include (new views())->ruta_templates.'botons/submit/alta_bd.php';?>

