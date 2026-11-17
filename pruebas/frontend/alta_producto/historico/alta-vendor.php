<?php /** @var gamboamartin\comercial\controllers\controlador_com_producto $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>
<main class="main section-color-primary">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <?php include (new views())->ruta_templates."head/title.php"; ?>

                <?php include (new views())->ruta_templates."mensajes.php"; ?>

                <div class="widget  widget-box box-container form-main widget-form-cart" id="form">
                    <form method="post" action="<?php echo $controlador->link_com_producto_alta_bd; ?>" class="form-additional">
                        <?php include (new views())->ruta_templates."head/subtitulo.php"; ?>

                        <?php echo $controlador->inputs->com_tipo_producto_id; ?>
                        <?php echo $controlador->inputs->cat_sat_producto; ?>
                        <div class="col-md-12 table table-responsive">
                            <table class="table">
                                <thead>
                                    <th>Producto</th>
                                    <th>Selecciona</th>
                                </thead>
                                <tbody id="datos_producto">

                                </tbody>
                            </table>
                        </div>
                        <?php echo $controlador->inputs->cat_sat_unidad_id; ?>
                        <?php echo $controlador->inputs->cat_sat_obj_imp_id; ?>
                        <?php echo $controlador->inputs->codigo; ?>
                        <?php echo $controlador->inputs->precio; ?>
                        <?php echo $controlador->inputs->descripcion; ?>

                        <div class="controls">
                            <button type="submit" class="btn btn-success" value="modifica" name="btn_action_next">Alta</button><br>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>



