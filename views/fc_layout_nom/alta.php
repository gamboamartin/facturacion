<?php /** @var  gamboamartin\facturacion\controllers\controlador_fc_layout_nom $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>

<div class="col-lg-12">
    <?php include (new views())->ruta_templates."head/title.php"; ?>
    <?php include (new views())->ruta_templates."mensajes.php"; ?>

    <div class="widget  widget-box box-container form-main widget-form-cart" id="form">
        <?php include (new views())->ruta_templates."head/subtitulo.php"; ?>

        <form method="post" action="<?php echo $controlador->link_alta_bd; ?>" class="form-additional" enctype="multipart/form-data">
            <?php echo $controlador->inputs->fecha_pago; ?>
            <?php echo $controlador->inputs->descripcion; ?>
            <?php echo $controlador->inputs->sucursal; ?>

            <?php if ($controlador->aplica_relacion_layout_factura): ?>
                <input type="hidden" id="aplica_relacion" value="1">
                <?php echo $controlador->inputs->input_select_factura; ?>
                <?php echo $controlador->inputs->input_select_periodo; ?>
            <?php endif; ?>

            <?php echo $controlador->inputs->documento; ?>

            <?php include (new views())->ruta_templates.'botons/submit/alta_bd.php';?>
        </form>
    </div>
</div>













