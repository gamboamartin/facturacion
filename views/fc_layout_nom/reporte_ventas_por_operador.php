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

                    <form method="post" action="<?php echo $controlador->link_reporte_ventas_por_operador_bd; ?>" class="form-additional">

                        <?php echo $controlador->inputs->fecha_inicio ?>
                        <?php echo $controlador->inputs->fecha_fin ?>

                        <div class="control-group btn-alta">
                            <div class="controls">
                                <button class="btn btn-success" role="submit">Generar Reporte</button><br>
                            </div>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>

</main>
