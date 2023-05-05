<?php /** @var gamboamartin\organigrama\controllers\controlador_org_empresa $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>
<main class="main section-color-primary">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="widget  widget-box box-container form-main widget-form-cart" id="form">
                    <form method="post" action="<?php echo $controlador->link_fc_partida_modifica_bd; ?>" class="form-additional">
                        <?php include (new views())->ruta_templates."head/title.php"; ?>
                        <?php include (new views())->ruta_templates."head/subtitulo.php"; ?>
                        <?php include (new views())->ruta_templates."mensajes.php"; ?>

                        <?php echo $controlador->inputs->select->fc_factura_id; ?>
                        <?php echo $controlador->inputs->select->com_producto_id; ?>
                        <?php echo $controlador->inputs->descripcion; ?>
                        <?php echo $controlador->inputs->cantidad; ?>
                        <?php echo $controlador->inputs->valor_unitario; ?>
                        <?php echo $controlador->inputs->descuento; ?>
                        <?php echo $controlador->inputs->select->cat_sat_tipo_factor_id; ?>
                        <?php echo $controlador->inputs->select->cat_sat_factor_id; ?>
                        <?php echo $controlador->inputs->select->cat_sat_tipo_impuesto_id; ?>

                        <div class="control-group btn-alta">
                            <div class="controls">
                                <button type="submit" class="btn btn-success" value="modifica" name="btn_action_next">Modifica</button><br>
                            </div>
                        </div>

                    </form>
                </div>

            </div>

        </div>
    </div>
</main>





