<?php /** @var  gamboamartin\facturacion\controllers\controlador_fc_empleado_contacto $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>

<div class="col-lg-12">

    <?php include (new views())->ruta_templates."head/title.php"; ?>
    <?php include (new views())->ruta_templates."mensajes.php"; ?>

    <div class="widget  widget-box box-container form-main widget-form-cart" id="form">

        <?php include (new views())->ruta_templates."head/subtitulo.php"; ?>

        <form method="post" action="<?php echo $controlador->link_alta_bd; ?>" class="form-additional" enctype="multipart/form-data">

            <?php echo $controlador->inputs->empleado ?>
            <?php echo $controlador->inputs->tipo_contacto; ?>
            <?php echo $controlador->inputs->nombre; ?>
            <?php echo $controlador->inputs->ap; ?>
            <?php echo $controlador->inputs->am; ?>
            <?php echo $controlador->inputs->telefono; ?>
            <?php echo $controlador->inputs->correo; ?>

            <?php include (new views())->ruta_templates.'botons/submit/alta_bd.php';?>
        </form>
    </div>
</div>













