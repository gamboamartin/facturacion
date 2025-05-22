<?php /** @var gamboamartin\banco\controllers\controlador_bn_sucursal_cuenta $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>
<?php echo $controlador->inputs->org_sucursal_id; ?>
<?php echo $controlador->inputs->bn_banco_id; ?>
<?php echo $controlador->inputs->num_cuenta; ?>
<?php echo $controlador->inputs->clabe; ?>
<?php include (new views())->ruta_templates.'botons/submit/alta_bd.php';?>