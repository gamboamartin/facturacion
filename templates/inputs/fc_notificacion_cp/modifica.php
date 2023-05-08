<?php /** @var  gamboamartin\facturacion\controllers\controlador_fc_notificacion_cp $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>
<?php echo $controlador->inputs->fc_complemento_pago_id; ?>
<?php echo $controlador->inputs->not_mensaje_id; ?>
<?php include (new views())->ruta_templates.'botons/submit/modifica_bd.php';?>


