<?php /** @var  \gamboamartin\facturacion\controllers\controlador_fc_nota_credito $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>

<?php echo $controlador->inputs->fc_nota_credito_id; ?>
<?php echo $controlador->inputs->com_producto_id; ?>
<?php echo $controlador->inputs->codigo; ?>
<?php echo $controlador->inputs->descripcion; ?>
<?php echo $controlador->inputs->cantidad; ?>
<?php echo $controlador->inputs->valor_unitario; ?>
<?php echo $controlador->inputs->subtotal; ?>
<?php echo $controlador->inputs->descuento; ?>
<?php echo $controlador->inputs->total; ?>
<?php include (new views())->ruta_templates.'botons/submit/modifica_bd.php';?>
