<?php /** @var  \gamboamartin\facturacion\controllers\controlador_cc_tipo_medidor $controlador controlador en ejecucion */ ?>
<?php use config\views; ?>

<main class="main section-color-primary">
    <div class="container">

        <div class="row">

            <div class="col-lg-12">
                <?php include (new views())->ruta_templates."head/title.php"; ?>
                <?php include (new views())->ruta_templates."mensajes.php"; ?>

                <div class="widget  widget-box box-container form-main widget-form-cart" id="form" >
                    <?php include (new views())->ruta_templates . "head/subtitulo.php"; ?>
                    <form method="post" action="<?php echo $controlador->url_submit; ?>" class="form-additional">


                        <div class="control-group col-6">
                            <?php echo $controlador->inputs->descripcion; ?>
                        </div>


                        <div class="control-group btn-alta">
                            <div class="controls">
                                <button class="btn btn-success" role="submit">Alta</button><br>
                            </div>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>

</main>
