<?php /** @var gamboamartin\facturacion\controllers\controlador_com_producto $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>

<main class="main section-color-primary">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="widget  widget-box box-container form-main widget-form-cart" id="form">
                    <form method="post" action="<?php echo $controlador->link_fc_conf_traslado_alta_bd; ?>" class="form-additional">
                        <?php include (new views())->ruta_templates."head/title.php"; ?>
                        <?php include (new views())->ruta_templates."head/subtitulo.php"; ?>
                        <?php include (new views())->ruta_templates."mensajes.php"; ?>

                        <?php echo $controlador->inputs->com_tipo_producto_id; ?>
                        <?php echo $controlador->inputs->cat_sat_tipo_impuesto_id; ?>
                        <?php echo $controlador->inputs->com_producto_id; ?>
                        <?php echo $controlador->inputs->cat_sat_tipo_factor_id; ?>
                        <?php echo $controlador->inputs->cat_sat_factor_id; ?>

                        <div class="control-group btn-alta">
                            <div class="controls">
                                <button type="submit" class="btn btn-success" value="modifica" name="btn_action_next">Alta</button><br>
                            </div>
                        </div>

                    </form>
                </div>

            </div>

        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-md-12">

                <div class="widget widget-box box-container widget-mylistings">
                    <div class="widget-header" style="display: flex;justify-content: space-between;align-items: center;">
                        <h2>Configuraciones de Traslados</h2>
                        <div class="controls">
                            <a href="<?php echo $controlador->link_lista; ?>" class="btn btn-primary btn-sm " ><b>Regresar</b></a><br>
                        </div>
                    </div>
                    <div class="">
                        <table id="fc_conf_traslado" class="table table-striped" >
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>





