<?php /** @var  gamboamartin\facturacion\controllers\controlador_adm_reporte $controlador  controlador en ejecucion */ ?>
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
                        <?php echo $controlador->buttons['btn_ejecuta']; ?>


                    </form>
                </div>

            </div>

        </div>
    </div>
</main>













