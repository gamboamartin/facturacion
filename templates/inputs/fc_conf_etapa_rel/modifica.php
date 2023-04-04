<?php /** @var  gamboamartin\facturacion\controllers\controlador_fc_relacion $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>
<?php echo $controlador->inputs->pr_etapa_proceso_id; ?>
<?php include (new views())->ruta_templates.'botons/submit/modifica_bd.php';?>


