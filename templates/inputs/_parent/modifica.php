<?php /** @var gamboamartin\system\_ctl_parent $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>
<?php echo $controlador->inputs->codigo; ?>
<?php echo $controlador->inputs->descripcion; ?>

<?php include (new views())->ruta_templates.'botons/submit/modifica_bd.php';?>



