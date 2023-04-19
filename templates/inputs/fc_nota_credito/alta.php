<?php /** @var  \gamboamartin\facturacion\controllers\controlador_fc_nota_credito $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>
<?php echo $controlador->inputs->com_sucursal_id; ?>
<?php echo $controlador->inputs->com_tipo_cambio_id; ?>
<?php echo $controlador->inputs->cat_sat_tipo_de_comprobante_id; ?>
<?php echo $controlador->inputs->cat_sat_regimen_fiscal_id; ?>
<?php echo $controlador->inputs->cat_sat_forma_pago_id; ?>
<?php echo $controlador->inputs->cat_sat_metodo_pago_id; ?>
<?php echo $controlador->inputs->cat_sat_moneda_id; ?>
<?php echo $controlador->inputs->cat_sat_uso_cfdi_id; ?>
<?php echo $controlador->inputs->dp_calle_pertenece_id; ?>
<?php echo $controlador->inputs->fc_csd_id; ?>
<?php include (new views())->ruta_templates.'botons/submit/alta_bd.php';?>

