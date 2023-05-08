<?php /** @var  gamboamartin\facturacion\controllers\controlador_fc_email_nc $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>
<?php echo $controlador->inputs->fc_nota_credito_id; ?>
<?php echo $controlador->inputs->com_email_cte_id; ?>
<?php include (new views())->ruta_templates.'botons/submit/alta_bd.php';?>