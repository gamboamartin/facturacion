<?php /** @var  \gamboamartin\facturacion\controllers\controlador_fc_csd $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>
<?php echo $controlador->inputs->serie; ?>
<?php echo $controlador->inputs->org_sucursal_id; ?>
<?php include (new views())->ruta_templates.'botons/submit/alta_bd.php';?>