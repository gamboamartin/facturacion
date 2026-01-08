<?php /** @var \gamboamartin\facturacion\controllers\controlador_fc_factura $controlador */ ?>
<?php use config\views; ?>
<div class="widget  widget-box box-container form-main widget-form-cart" id="form">

    <?php include (new views())->ruta_templates."head/subtitulo.php"; ?>
    <?php include (new views())->ruta_templates."mensajes.php"; ?>

    <form method="post" action="<?php echo $controlador->link_alta_bd; ?>" class="form-additional" enctype="multipart/form-data" id="<?php echo 'form_'.$controlador->seccion."_".$controlador->accion; ?>">
        <?php if ($controlador->aplica_relacion_layout_factura): ?>
            <input type="hidden" id="aplica_relacion" value="1">
            <?php echo $controlador->inputs->input_select_layout; ?>
        <?php endif; ?>
        <?php include $controlador->include_inputs_alta; ?>
    </form>
</div>

<div class="col-md-12 buttons-form">
    <?php
    foreach ($controlador->buttons_parents_alta as $button){ ?>
        <div class="col-md-4">
            <?php echo $button; ?>
        </div>
    <?php } ?>
</div>
