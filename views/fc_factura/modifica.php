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
                        <?php echo $controlador->form_data_fc; ?>
                        <?php include (new views())->ruta_templates.'botons/submit/modifica_bd.php';?>

                    </form>
                </div>

            </div>

        </div>
    </div>

    <div class="container">
        <?php echo $controlador->buttons_base; ?>
    </div>

    <div class="container partidas">
        <div class="row">
            <div class="col-lg-12">
                <div class="widget  widget-box box-container form-main widget-form-cart" id="form">
                    <div class="widget-header" style="display: flex;justify-content: space-between;align-items: center;">
                        <h2>Partidas</h2>
                    </div>
                    <form method="post" action="<?php echo $controlador->link_fc_partida_alta_bd; ?>" class="form-additional" id="frm-partida">

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
                        <?php echo $controlador->inputs->partidas->cat_sat_conf_imps_id; ?>


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
                    <div class="table table-responsive">

                        <?php foreach ($controlador->partidas->registros as $partida){ ?>
                        <form method="post" action="<?php echo $partida['link_modifica_partida_bd']; ?>">

                        <table id="fc_partida" class="table table-striped" style="font-size: 12px;">
                            <?php echo $controlador->t_head_producto; ?>
                            <tbody>
                            <?php echo $partida['data_producto_html']; ?>
                            <?php echo $partida['descripcion_html']; ?>
                            <?php echo $partida['impuesto_traslado_html']; ?>
                            <?php echo $partida['impuesto_retenido_html']; ?>
                            </tbody>
                        </table>
                            <div class="control-group btn-alta">
                                <div class="controls">
                                    <button type="submit" class="btn btn-success col-md-12" value="modifica" name="btn_action_next">Modifica</button>
                                    <?php echo $partida['elimina_bd'];?>
                                </div>
                            </div>

                        </form>
                        <?php } ?>


                    </div>

                </div>
            </div>
        </div>


        <div class="row">
            <div class="col-md-12">
                <div class="widget widget-box box-container widget-mylistings">
                    <div class="widget-header" style="display: flex;justify-content: space-between;align-items: center;">
                        <h2>Correos</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                            <tr>
                                <th>Id</th>
                                <th>Correo</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($controlador->registros['fc_emails'] as $fc_email){ ?>
                            <tr>
                                <td><?php echo $fc_email['fc_email_id']; ?></td>
                                <td><?php echo $fc_email['com_email_cte_descripcion']; ?></td>
                            </tr>
                            <?php } ?>
                            </tbody>
                        </table>

                    </div>
                </div>
            </div>
        </div>

        <?php echo $controlador->buttons_base; ?>

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















