<?php /** @var gamboamartin\comercial\controllers\controlador_com_cliente_cuenta $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>
<?php echo $controlador->inputs->com_cliente_id; ?>
<?php echo $controlador->inputs->bn_banco_id; ?>
<?php echo $controlador->inputs->num_cuenta; ?>
<?php echo $controlador->inputs->clabe; ?>
<?php include (new views())->ruta_templates.'botons/submit/alta_bd.php';?>