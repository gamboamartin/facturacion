<?php /** @var  \gamboamartin\facturacion\controllers\controlador_fc_factura $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>

<?php echo $controlador->inputs->registro_id; ?>
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
                        <?php echo $controlador->inputs->partidas->cat_sat_conf_imps_id; ?>


                        <div class="control-group btn-alta">
                            <div class="controls">
                                <button type="button" class="btn btn-success" value="modifica" name="btn_action_next" id="btn-alta-partida">Alta</button><br>
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

                    <div class="table table-responsive" id="row-partida">

                        <?php    foreach ($controlador->partidas->registros as $partida){ ?>

                            <table class='table table-striped data-partida' style='border: 2px solid'>
                                <tbody>
                                <tr class="tr_fc_partida_descripcion">
                                    <td colspan='5' class="td_fc_partida_descripcion" data-fc_partida_factura_id="<?php echo $partida['fc_partida_nc_id']; ?>">
                                        <input type="text" class="form-control form-control-sm fc_partida_descripcion" name="descripcion" value="<?php echo $partida['fc_partida_nc_descripcion']; ?>" />
                                    </td>
                                </tr>

                                <tr>
                                    <td><b>CVE SAT: </b><td><?php echo $partida['com_producto_codigo']; ?></td>
                                    <td><b>Unidad: </b><?php echo $partida['cat_sat_unidad_descripcion']; ?></td>
                                    <td><b>Obj Imp: </b><?php echo $partida['cat_sat_obj_imp_descripcion']; ?></td>
                                </tr>

                                <tr>
                                    <td><b>Cantidad</b></td>
                                    <td><b>Valor Unitario</b></td>
                                    <td><b>Importe</b></td>
                                    <td><b>Descuento</b></td>
                                </tr>

                                <tr class="tr_data_partida">
                                    <td class="td_fc_partida_cantidad">
                                        <input type="text" class="form-control form-control-sm fc_partida_cantidad" name="cantidad" value="<?php echo $partida['fc_partida_nc_cantidad']; ?>" />
                                    </td>
                                    <td class="td_fc_partida_valor_unitario">
                                        <input type="text" class="form-control form-control-sm fc_partida_valor_unitario" name="valor_unitario" value="<?php echo $partida['fc_partida_nc_valor_unitario']; ?>" />
                                    </td>
                                    <td class="td_fc_partida_sub_total_base">
                                        <input type="text" class="form-control form-control-sm fc_partida_sub_total_base" disabled value="<?php echo $partida['fc_partida_nc_sub_total_base']; ?>" />
                                    </td>
                                    <td class="td_fc_partida_descuento">
                                        <input type="text" class="form-control form-control-sm fc_partida_descuento" name="descuento" value="<?php echo $partida['fc_partida_nc_descuento']; ?>" />
                                    </td>

                                </tr>

                                <tr>
                                    <td><b>Sub Total: </b><?php echo $partida['fc_partida_nc_sub_total']; ?></td>
                                    <td><b>Traslados: </b><?php echo $partida['fc_partida_nc_total_traslados']; ?></td>
                                    <td><b>Retenciones: </b><?php echo $partida['fc_partida_nc_total_retenciones']; ?></td>
                                    <td><b>Total: </b><?php echo $partida['fc_partida_nc_total']; ?></td>
                                </tr>
                                <tr class='tr_elimina_partida'>
                                    <td colspan='5' class='td_elimina_partida'>
                                        <button type='button' class='btn btn-danger col-md-12 elimina_partida' data-fc_partida_factura_id='<?php echo $partida['fc_partida_nc_id']; ?>' value='elimina' name='btn_action_next'>Elimina</button>
                                    </td>
                                </tr>
                                </tbody>
                            </table>


                        <?php }  ?>

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
                                <td><?php echo $fc_email['fc_email_nc_id']; ?></td>
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
<script src="<?php echo (new \config\generales())->url_base."js/_facturacion.js" ?>"></script>















