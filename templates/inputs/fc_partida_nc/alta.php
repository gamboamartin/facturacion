<?php /** @var  \gamboamartin\facturacion\controllers\controlador_fc_nota_credito $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>

<?php echo $controlador->inputs->fc_nota_credito_id; ?>
<?php echo $controlador->inputs->com_producto_id; ?>
<?php include (new views())->ruta_templates.'botons/submit/alta_bd.php';?>

