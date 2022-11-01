<?php /** @var  \gamboamartin\facturacion\controllers\controlador_fc_csd $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>
<?php echo $controlador->inputs->org_sucursal_id; ?>
<?php echo $controlador->inputs->codigo; ?>
<?php echo $controlador->inputs->codigo_bis; ?>
<?php echo $controlador->inputs->serie; ?>
<?php include (new views())->ruta_templates.'botons/submit/modifica_bd.php';?>