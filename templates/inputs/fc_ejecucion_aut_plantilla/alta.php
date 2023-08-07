<?php /** @var  gamboamartin\facturacion\controllers\controlador_fc_relacion $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>
<?php echo $controlador->inputs->com_tipo_cliente_id; ?>
<?php echo $controlador->inputs->descripcion; ?>
<?php echo $controlador->hiddens->btn_action_next; ?>
<?php echo $controlador->hiddens->id_retorno; ?>
<?php include (new views())->ruta_templates.'botons/submit/alta_bd.php';?>


