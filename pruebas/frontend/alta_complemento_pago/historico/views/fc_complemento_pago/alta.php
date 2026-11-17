<?php /** @var \gamboamartin\facturacion\controllers\controlador_fc_complemento_pago $controlador */ ?>
<?php use config\views; ?>
<link rel="stylesheet" href="css/fc_factura/alta.css?v=2">
<link rel="stylesheet" href="css/fc_factura/form-responsive.css?v=1">
<div class="fc-alta fc-invoice-form">
<div class="fc-alta-heading">
    <span class="fc-alta-eyebrow">FACTURACIÓN</span>
    <h1>Nuevo complemento de pago</h1>
    <p>Completa la cabecera del comprobante antes de registrar los pagos y sus documentos relacionados.</p>
</div>
<div class="widget widget-box box-container form-main widget-form-cart fc-alta-form" id="form">

    <?php include (new views())->ruta_templates."head/subtitulo.php"; ?>
    <?php include (new views())->ruta_templates."mensajes.php"; ?>

    <form method="post" action="<?php echo $controlador->link_alta_bd; ?>" class="form-additional" enctype="multipart/form-data" id="<?php echo 'form_'.$controlador->seccion."_".$controlador->accion; ?>">
        <?php include $controlador->include_inputs_alta; ?>
    </form>
</div>



</div>
