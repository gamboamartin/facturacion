<?php /** @var  gamboamartin\facturacion\controllers\controlador_fc_docto_relacionado $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>
<?php echo $controlador->inputs->fc_factura_id; ?>
<?php echo $controlador->inputs->cat_sat_obj_imp_id; ?>
<?php echo $controlador->inputs->fc_pago_pago_id; ?>
<?php echo $controlador->inputs->codigo; ?>
<?php echo $controlador->inputs->equivalencia_dr; ?>
<?php echo $controlador->inputs->num_parcialidad; ?>
<?php echo $controlador->inputs->imp_saldo_ant; ?>
<?php echo $controlador->inputs->imp_pagado; ?>
<?php echo $controlador->inputs->imp_saldo_insoluto; ?>
<?php include (new views())->ruta_templates.'botons/submit/alta_bd.php';?>