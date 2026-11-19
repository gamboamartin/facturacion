<?php use config\views; ?>
<?php
/** @var gamboamartin\facturacion\controllers\controlador_fc_csd $controlador */
?>

<div class="col-lg-12">
    <div class="widget  widget-box box-container form-main widget-form-cart" id="form">
        <form method="post" action="" class="form-additional" enctype="multipart/form-data">
            <?php include (new views())->ruta_templates."head/title.php"; ?>
            <?php include (new views())->ruta_templates."head/subtitulo.php"; ?>
            <?php include (new views())->ruta_templates."mensajes.php"; ?>

            <?php echo $controlador->inputs->serie; ?>
            <?php echo $controlador->inputs->no_certificado; ?>
            <?php echo $controlador->inputs->password; ?>
            <?php echo $controlador->inputs->org_sucursal_id; ?>

            <?php include (new views())->ruta_templates.'botons/submit/alta_bd.php';?>

        </form>
    </div>

</div>

<?php include __DIR__ . '/archivos_cer.php'; ?>
