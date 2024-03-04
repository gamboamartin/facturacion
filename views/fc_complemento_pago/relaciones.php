<?php /** @var  gamboamartin\facturacion\controllers\controlador_fc_factura $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>

<main class="main section-color-primary">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="widget  widget-box box-container form-main widget-form-cart" id="form">
                    <form method="post" action="<?php echo $controlador->link_fc_relacion_alta_bd; ?>" class="form-additional">
                        <?php include (new views())->ruta_templates."head/title.php"; ?>
                        <?php include (new views())->ruta_templates."head/subtitulo.php"; ?>
                        <?php include (new views())->ruta_templates."mensajes.php"; ?>

                        <?php echo $controlador->inputs->fc_csd_id; ?>
                        <?php echo $controlador->inputs->com_sucursal_id; ?>
                        <?php echo $controlador->inputs->serie; ?>
                        <?php echo $controlador->inputs->folio; ?>
                        <?php echo $controlador->inputs->impuestos_trasladados; ?>
                        <?php echo $controlador->inputs->impuestos_retenidos; ?>
                        <?php echo $controlador->inputs->subtotal; ?>
                        <?php echo $controlador->inputs->descuento; ?>
                        <?php echo $controlador->inputs->total; ?>
                        <?php echo $controlador->inputs->cat_sat_tipo_relacion_id; ?>


                        <div class="control-group btn-alta">
                            <div class="controls">
                                <button type="submit" class="btn btn-success" value="Asigna Tipo Relacion" name="btn_action_next">Asigna Relacion</button><br>
                            </div>
                        </div>
                    </form>
                </div>

            </div>

        </div>
    </div>

    <div class="container">

    <div class="col-md-12 buttons-form">
        <?php echo $controlador->button_fc_factura_modifica; ?>
    </div>
    </div>

    <div class="container">

        <div class="row">
            <div class="col-md-12">

                <div class="widget widget-box box-container widget-mylistings">
                    <form method="post" action="<?php echo $controlador->link_fc_factura_relacionada_alta_bd; ?>">
                    <div class="widget-header" style="display: flex;justify-content: space-between;align-items: center;">
                        <h2>Relaciones</h2>
                    </div>
                    <div class="">

                        <?php foreach ($controlador->relaciones as $fc_relacion){

                        ?>
                        <table id="fc_partida" class="table table-striped">
                            <thead>
                            <tr>
                                <th>Cod Relacion: <?php echo $fc_relacion['cat_sat_tipo_relacion_codigo'] ?></th>
                                <th>Relacion: <?php echo $fc_relacion['cat_sat_tipo_relacion_descripcion'] ?></th>
                                <th><?php echo $fc_relacion['elimina_bd'] ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td colspan="2">
                                    <table class="table table-striped">
                                        <thead>
                                        <tr>
                                            <th>UUID</th>
                                            <th>Cliente</th>
                                            <th>Folio</th>
                                            <th>Fecha</th>
                                            <th>Estatus</th>
                                            <th>Selecciona</th>
                                        </tr>
                                        </thead>
                                        <tbody>


                                        <?php foreach ($fc_relacion['fc_complementos_pago'] as $fc_complemento_pago){
                                            //print_r($fc_relacion['fc_complementos_pago']);exit;
                                            ?>
                                            <tr>
                                                <td><?php echo $fc_complemento_pago['fc_complemento_pago_uuid']; ?></td>
                                                <td><?php echo $fc_complemento_pago['com_cliente_rfc']; ?></td>
                                                <td><?php echo $fc_complemento_pago['fc_complemento_pago_folio']; ?></td>
                                                <td><?php echo $fc_complemento_pago['fc_complemento_pago_fecha']; ?></td>
                                                <td><?php echo $fc_complemento_pago['fc_complemento_pago_etapa']; ?></td>
                                                <td>
                                                    <?php echo $fc_complemento_pago['seleccion']; ?>
                                                </td>
                                            </tr>
                                        <?php } ?>

                                        <?php foreach ($fc_relacion['fc_facturas_relacionadas'] as $fc_factura){ ?>
                                            <tr>
                                                <td><?php echo $fc_factura['fc_complemento_pago_uuid']; ?></td>
                                                <td><?php echo $fc_factura['com_cliente_rfc']; ?></td>
                                                <td><?php echo $fc_factura['fc_complemento_pago_folio']; ?></td>
                                                <td><?php echo $fc_factura['fc_complemento_pago_fecha']; ?></td>
                                                <td><?php echo $fc_factura['fc_complemento_pago_etapa']; ?></td>
                                                <td><?php echo $fc_factura['elimina_bd']; ?></td>
                                            </tr>
                                        <?php } ?>



                                        </tbody>
                                    </table>

                                </td>
                            </tr>

                            </tbody>
                        </table>
                        <?php } ?>


                    </div>
                        <div>
                            <div class="controls">
                                <button type="submit" class="btn btn-success" value="Asigna Tipo Relacion" name="btn_action_next">Asigna Relacion</button><br>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>



        <div class="col-md-12 buttons-form">
                <?php echo $controlador->button_fc_factura_modifica; ?>
        </div>


    </div>



</main>















