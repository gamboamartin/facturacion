<?php /** @var  \gamboamartin\facturacion\controllers\controlador_fc_factura $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>

<main class="main section-color-primary">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="widget  widget-box box-container form-main widget-form-cart" id="form">
                    <form method="post" action="<?php echo $controlador->link_im_registro_patronal_alta_bd; ?>" class="form-additional">
                        <?php include (new views())->ruta_templates."head/title.php"; ?>
                        <?php include (new views())->ruta_templates."head/subtitulo.php"; ?>
                        <?php include (new views())->ruta_templates."mensajes.php"; ?>

                        <?php echo $controlador->inputs->fc_csd_id; ?>
                        <?php echo $controlador->inputs->com_sucursal_id; ?>
                        <?php echo $controlador->inputs->folio; ?>
                        <?php echo $controlador->inputs->fecha; ?>
                        <?php echo $controlador->inputs->exportacion; ?>
                        <?php echo $controlador->inputs->serie; ?>
                        <?php echo $controlador->inputs->subtotal; ?>
                        <?php echo $controlador->inputs->descuento; ?>
                        <?php echo $controlador->inputs->impuestos_trasladados; ?>
                        <?php echo $controlador->inputs->impuestos_retenidos; ?>
                        <?php echo $controlador->inputs->total; ?>
                        <?php echo $controlador->inputs->cat_sat_tipo_de_comprobante_id; ?>
                        <?php echo $controlador->inputs->cat_sat_forma_pago_id; ?>
                        <?php echo $controlador->inputs->cat_sat_metodo_pago_id; ?>
                        <?php echo $controlador->inputs->cat_sat_moneda_id; ?>
                        <?php echo $controlador->inputs->com_tipo_cambio_id; ?>
                        <?php echo $controlador->inputs->cat_sat_uso_cfdi_id; ?>
                        <?php include (new views())->ruta_templates.'botons/submit/modifica_bd.php';?>

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
                        <h2>Partidas</h2>
                        <a href="<?php echo $controlador->link_fc_factura_nueva_partida; ?>" class="btn btn-info"><i class="icon-edit"></i>
                            Nueva Partida
                        </a>
                    </div>
                    <div class="">
                        <table id="fc_partida" class="table table-striped" >
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</main>













