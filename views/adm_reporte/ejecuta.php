<?php /** @var  \gamboamartin\facturacion\controllers\controlador_fc_key_csd $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>

<main class="main section-color-primary">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="widget  widget-box box-container form-main widget-form-cart" id="form">
                    <form method="post" action="<?php echo $controlador->link_ejecuta_reporte; ?>" class="form-additional" enctype="multipart/form-data">
                        <?php include (new views())->ruta_templates."head/title.php"; ?>
                        <?php include (new views())->ruta_templates."head/subtitulo.php"; ?>
                        <?php include (new views())->ruta_templates."mensajes.php"; ?>

                        <?php echo $controlador->filtros; ?>

                        <?php include (new views())->ruta_templates.'botons/submit/alta_bd.php';?>
                    </form>
                </div>

            </div>

        </div>
    </div>
</main>













