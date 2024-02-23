<?php /** @var  \gamboamartin\facturacion\controllers\controlador_fc_csd $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>
<?php echo $controlador->inputs->serie; ?>
<?php echo $controlador->inputs->no_certificado; ?>
<?php echo $controlador->inputs->password; ?>
<?php echo $controlador->inputs->org_sucursal_id; ?>
<?php echo $controlador->inputs->fc_cer_csd; ?>
<?php echo $controlador->inputs->fc_key_csd; ?>
<?php include (new views())->ruta_templates.'botons/submit/alta_bd.php';?>


