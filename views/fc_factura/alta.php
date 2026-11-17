<?php /** @var \gamboamartin\facturacion\controllers\controlador_fc_factura $controlador */ ?>
<?php use config\generales;
use config\views; ?>
<link rel="stylesheet" href="css/fc_factura/alta.css?v=3">
<link rel="stylesheet" href="css/fc_factura/form-responsive.css?v=1">
<div class="fc-alta fc-invoice-form">
<div class="fc-alta-heading">
    <span class="fc-alta-eyebrow">FACTURACIÓN</span>
    <h1>Nueva factura</h1>
    <p>Completa los datos del comprobante para continuar con sus conceptos.</p>
</div>
<div class="widget widget-box box-container form-main widget-form-cart fc-alta-form" id="form">

    <?php include (new views())->ruta_templates."head/subtitulo.php"; ?>
    <?php include (new views())->ruta_templates."mensajes.php"; ?>

    <form method="post" action="<?php echo $controlador->link_alta_bd; ?>" class="form-additional" enctype="multipart/form-data" id="<?php echo 'form_'.$controlador->seccion."_".$controlador->accion; ?>">
        <?php if ((new generales())->aplica_relacion_layout_factura): ?>
            <section class="fc-alta-section" aria-labelledby="fc-alta-layout-title">
                <h2 id="fc-alta-layout-title">Tipo de producto</h2>
                <div class="fc-alta-fields">
            <input type="hidden" id="aplica_relacion" value="1">
            <?php //echo $controlador->inputs->input_select_layout; ?>
            <?php echo $controlador->inputs->input_select_com_tipo_producto; ?>
                </div>
            </section>
        <?php endif; ?>
        <?php include $controlador->include_inputs_alta; ?>
    </form>
</div>



</div>
