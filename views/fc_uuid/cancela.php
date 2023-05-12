<?php /** @var  \gamboamartin\facturacion\controllers\controlador_fc_factura $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>

<main class="main section-color-primary">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="widget  widget-box box-container form-main widget-form-cart" id="form">
                    <form method="post" action="<?php echo $controlador->link_fc_uuid_cancela_bd; ?>" class="form-additional">
                        <?php include (new views())->ruta_templates."head/title.php"; ?>
                        <?php include (new views())->ruta_templates."head/subtitulo.php"; ?>
                        <?php include (new views())->ruta_templates."mensajes.php"; ?>

                        <?php echo $controlador->inputs->fc_uuid_id; ?>
                        <?php echo $controlador->inputs->cat_sat_motivo_cancelacion_id; ?>
                        <div class="control-group btn-alta">
                            <div class="controls">
                                <button class="btn btn-danger" role="submit">Cancelar</button><br>
                            </div>
                        </div>
                    </form>
                </div>

            </div>

        </div>
    </div>




</main>















