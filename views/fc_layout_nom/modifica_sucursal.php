<?php /** @var  \gamboamartin\facturacion\controllers\controlador_fc_layout_nom $controlador controlador en ejecucion */ ?>
<?php use config\views; ?>

<main class="main section-color-primary">
    <div class="container">

        <div class="row">

            <div class="col-lg-12">
                <?php include (new views())->ruta_templates."head/title.php"; ?>
                <?php include (new views())->ruta_templates."mensajes.php"; ?>

                <div class="widget  widget-box box-container form-main widget-form-cart" id="form" >
                    <?php include (new views())->ruta_templates . "head/subtitulo.php"; ?>

                    <form method="post" action="<?php echo $controlador->link_modifica_sucursal_bd; ?>" class="form-additional">

                        <div class="control-group col-sm-12">
                            <label class="control-label" for="descripcion">Layout</label>
                            <div class="controls">
                                <input type="text" disabled="disabled"  value="<?php echo $controlador->descripcion_nom_layout; ?>" class="form-control descripcion" id="descripcion" title="CODIGO POSTAL">
                            </div>
                        </div>
                        <?php echo $controlador->inputs->sucursal; ?>
                        <input type="hidden" name="fc_layout_nom_id" value="<?php echo $_GET['registro_id'] ?>">
                        <div class="control-group btn-alta">
                            <div class="controls">
                                <button class="btn btn-success" role="submit">Modificar</button><br>
                            </div>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>

</main>



