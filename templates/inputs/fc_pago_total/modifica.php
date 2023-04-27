<?php /** @var  gamboamartin\facturacion\controllers\controlador_fc_pago $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>
<?php echo $controlador->inputs->fc_pago_id; ?>
<?php echo $controlador->inputs->codigo; ?>
<?php echo $controlador->inputs->total_traslados_base_iva_16; ?>
<?php echo $controlador->inputs->total_traslados_base_iva_08; ?>
<?php echo $controlador->inputs->total_traslados_base_iva_00; ?>
<?php echo $controlador->inputs->total_traslados_impuesto_iva_16; ?>
<?php echo $controlador->inputs->total_traslados_impuesto_iva_08; ?>
<?php echo $controlador->inputs->total_traslados_impuesto_iva_00; ?>
<?php echo $controlador->inputs->total_retenciones_iva; ?>
<?php echo $controlador->inputs->total_retenciones_ieps; ?>
<?php echo $controlador->inputs->total_retenciones_isr; ?>
<?php echo $controlador->inputs->monto_total_pagos; ?>
<?php include (new views())->ruta_templates.'botons/submit/modifica_bd.php';?>


