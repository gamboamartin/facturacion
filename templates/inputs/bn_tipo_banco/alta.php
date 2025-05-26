<?php /** @var  \gamboamartin\banco\controllers\controlador_adm_session $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>


<?php echo $controlador->inputs->descripcion; ?>


<?php include (new views())->ruta_templates.'botons/submit/alta_bd_otro.php';?>