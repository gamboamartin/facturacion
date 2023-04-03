<?php /** @var  \gamboamartin\facturacion\controllers\controlador_fc_factura $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>

<main class="main section-color-primary">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="widget  widget-box box-container form-main widget-form-cart" id="form">
                    <form method="post" action="<?php echo $controlador->link_modifica_bd; ?>" class="form-additional">
                        <?php include (new views())->ruta_templates."head/title.php"; ?>
                        <?php include (new views())->ruta_templates."head/subtitulo.php"; ?>
                        <?php include (new views())->ruta_templates."mensajes.php"; ?>

                        <?php echo $controlador->inputs->fc_csd_id; ?>
                        <?php echo $controlador->inputs->com_sucursal_id; ?>
                        <?php echo $controlador->inputs->serie; ?>
                        <?php echo $controlador->inputs->folio; ?>
                        <?php echo $controlador->inputs->exportacion; ?>
                        <?php echo $controlador->inputs->fecha; ?>
                        <?php echo $controlador->inputs->impuestos_trasladados; ?>
                        <?php echo $controlador->inputs->impuestos_retenidos; ?>
                        <?php echo $controlador->inputs->subtotal; ?>
                        <?php echo $controlador->inputs->descuento; ?>
                        <?php echo $controlador->inputs->total; ?>
                        <?php echo $controlador->inputs->cat_sat_tipo_de_comprobante_id; ?>
                        <?php echo $controlador->inputs->cat_sat_forma_pago_id; ?>
                        <?php echo $controlador->inputs->cat_sat_metodo_pago_id; ?>
                        <?php echo $controlador->inputs->cat_sat_moneda_id; ?>
                        <?php echo $controlador->inputs->com_tipo_cambio_id; ?>
                        <?php echo $controlador->inputs->cat_sat_uso_cfdi_id; ?>
                        <?php echo $controlador->inputs->observaciones; ?>
                        <?php include (new views())->ruta_templates.'botons/submit/modifica_bd.php';?>
                        
                        <div class="control-group btn-modifica">
                            <div class="controls">
                                <a href="<?php echo $controlador->link_factura_timbra_xml;?>" class="btn btn-success" >Timbra XML</a><br>
                            </div>
                        </div>
                    </form>
                </div>

            </div>

        </div>
    </div>

    <div class="container partidas">
        <div class="row">
            <div class="col-lg-12">
                <div class="widget  widget-box box-container form-main widget-form-cart" id="form">
                    <div class="widget-header" style="display: flex;justify-content: space-between;align-items: center;">
                        <h2>Partidas</h2>
                    </div>
                    <form method="post" action="<?php echo $controlador->link_fc_partida_alta_bd; ?>" class="form-additional">

                        <?php echo $controlador->inputs->partidas->com_producto_id; ?>
                        <?php echo $controlador->inputs->partidas->unidad; ?>
                        <?php echo $controlador->inputs->partidas->impuesto; ?>
                        <?php echo $controlador->inputs->partidas->cuenta_predial; ?>
                        <?php echo $controlador->inputs->partidas->descripcion; ?>
                        <?php echo $controlador->inputs->partidas->cantidad; ?>
                        <?php echo $controlador->inputs->partidas->valor_unitario; ?>
                        <?php echo $controlador->inputs->partidas->subtotal; ?>
                        <?php echo $controlador->inputs->partidas->descuento; ?>
                        <?php echo $controlador->inputs->partidas->total; ?>


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
                        <h2>Partidas</h2>
                    </div>
                    <div class="">

                        <?php foreach ($controlador->partidas->registros as $partida){


                        ?>
                        <table id="fc_partida" class="table table-striped" style="font-size: 12px; border: 2px solid #e0e0e0; border-radius: 0.5rem !important;">
                            <?php echo $controlador->t_head_producto; ?>
                            <tbody>
                            <?php echo $partida['data_producto_html']; ?>
                            <tr>
                                <td class="nested" colspan="9" style="padding: 0;">
                                    <table class="table table-striped" style='font-size: 14px; vertical-align: middle; background-color: #dfe7f6; color: #2c58a0; margin-bottom: 0; '>
                                        <thead><tr><th>Producto</th></tr></thead>
                                        <tbody>
                                            <tr><td><?php echo $partida['fc_partida_descripcion']; ?></td></tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>

                            <?php echo $partida['impuesto_traslado_html']; ?>
                            <?php echo $partida['impuesto_retenido_html']; ?>

                            </tbody>
                        </table>
                        <?php } ?>


                    </div>

                </div>
            </div>
        </div>

        <div class="col-md-12 buttons-form">
            <?php
            foreach ($controlador->buttons_parents_alta as $button){ ?>
                <div class="col-md-4">
                    <?php echo $button; ?>
                </div>
            <?php } ?>
        </div>

    </div>



</main>















