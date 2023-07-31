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
                        <?php echo $controlador->inputs->aplica_saldo; ?>

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
                                        <?php echo $controlador->thead_relacion; ?>

                                        <tbody>
                                        <?php foreach ($fc_relacion['fc_facturas'] as $fc_factura){

                                            ?>

                                            <?php echo $controlador->tr_relacion(aplica_monto: $controlador->aplica_monto_relacion,
                                                fc_factura: $fc_factura, key_etapa: $fc_factura['key_etapa'],
                                                key_fecha: $fc_factura['key_fecha'], key_folio: $fc_factura['key_folio'],
                                                key_saldo: $fc_factura['key_saldo'],
                                                key_total: $fc_factura['key_total'], key_uuid: $fc_factura['key_uuid']); ?>

                                        <?php } ?>

                                        <?php foreach ($fc_relacion['fc_facturas_relacionadas'] as $fc_factura){ ?>
                                            <tr>
                                                <td><?php echo $fc_factura[$controlador->key_uuid]; ?></td>
                                                <td><?php echo $fc_factura['com_cliente_rfc']; ?></td>
                                                <td><?php echo $fc_factura[$controlador->key_folio]; ?></td>
                                                <td><?php echo $fc_factura[$controlador->key_fecha]; ?></td>
                                                <td><?php echo $fc_factura[$controlador->key_total]; ?></td>
                                                <td><?php echo $fc_factura[$controlador->key_etapa]; ?></td>
                                                <td><?php echo $fc_factura['cat_sat_tipo_de_comprobante_descripcion']; ?></td>
                                                <td><?php echo $fc_factura['elimina_bd']; ?></td>
                                            </tr>
                                        <?php } ?>

                                        <?php foreach ($fc_relacion['fc_facturas_relacionadas_factura'] as $fc_factura){
                                            ?>
                                            <tr>
                                                <td><?php echo $fc_factura['fc_factura_uuid']; ?></td>
                                                <td><?php echo $fc_factura['com_cliente_rfc']; ?></td>
                                                <td><?php echo $fc_factura['fc_factura_folio']; ?></td>
                                                <td><?php echo $fc_factura['fc_factura_fecha']; ?></td>
                                                <td><?php echo $fc_factura['fc_factura_total']; ?></td>
                                                <td><?php echo $fc_factura['fc_factura_etapa']; ?></td>
                                                <td><?php echo $fc_factura['cat_sat_tipo_de_comprobante_descripcion']; ?></td>
                                                <td><?php echo $fc_factura['elimina_bd']; ?></td>
                                            </tr>
                                        <?php } ?>

                                        </tbody>
                                    </table>

                                    <table id="fc_partida" class="table table-striped">
                                        <thead>
                                        <tr>
                                            <th>EXTERNOS</th>

                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr>
                                            <td colspan="2" class="table-responsive">
                                                <table class="table table-striped">

                                                    <?php echo $controlador->thead_relacion; ?>

                                                    <tbody>
                                                    <?php foreach ($fc_relacion['fc_externas'] as $fc_factura){ ?>

                                                        <?php echo $controlador->tr_relacion(aplica_monto: $controlador->aplica_monto_relacion,
                                                            fc_factura: $fc_factura, key_etapa: 'fc_uuid_etapa',
                                                            key_fecha: 'fc_uuid_fecha', key_folio: 'fc_uuid_folio',
                                                            key_saldo: 'fc_uuid_saldo', key_total: 'fc_uuid_total',
                                                            key_uuid: 'fc_uuid_uuid'); ?>

                                                    <?php } ?>

                                                    </tbody>
                                                </table>

                                            </td>
                                        </tr>

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















