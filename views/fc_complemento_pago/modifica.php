<?php /** @var  gamboamartin\facturacion\controllers\controlador_fc_complemento_pago $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>

<input type="hidden"  value="<?php echo $controlador->total_pagos ?>" id="total_pagos">

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


    <?php if(!$controlador->tiene_pago) { ?>
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="widget  widget-box box-container form-main widget-form-cart" id="form">
                    <form method="post" action="<?php echo $controlador->link_fc_pago_pago_alta_bd; ?>" class="form-additional">

                        <?php echo $controlador->inputs->fecha_pago; ?>
                        <?php echo $controlador->inputs->monto; ?>
                        <?php echo $controlador->inputs->cat_sat_forma_pago_id_full; ?>
                        <?php echo $controlador->inputs->com_tipo_cambio_id; ?>
                        <?php echo $controlador->inputs->fc_pago_id; ?>
                        <?php echo $controlador->inputs->seccion_retorno; ?>
                        <?php echo $controlador->inputs->id_retorno; ?>

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
    <?php } ?>


    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="widget  widget-box box-container form-main widget-form-cart" id="form">
                    <h2>Pago</h2>
                    <div class="table table-responsive">
                        <form method="post" action="<?php echo $controlador->link_fc_docto_relacionado_alta_bd; ?>" class="form-additional">
                            <table class="table table-striped">
                                <tbody>
                                    <?php foreach ($controlador->fc_pagos as $fc_pago){
                                        $fc_pago_totales = $fc_pago['fc_pago_totales'];
                                        $fc_pago_pagos = $fc_pago['fc_pago_pagos'];
                                        ?>

                                        <tr>
                                            <td>
                                                <h3>Detalle</h3>
                                                <div class="table table-responsive">
                                                    <table class="table table-striped">
                                                        <thead>
                                                        <tr>
                                                            <th>Id</th>
                                                            <th>Fecha Pago</th>
                                                            <th>Forma Pago</th>
                                                            <th>Moneda</th>
                                                            <th>Tipo Cambio</th>
                                                            <th>Monto</th>
                                                        </tr>
                                                        </thead>
                                                        <tbody>
                                                        <?php foreach ($fc_pago_pagos as $fc_pago_pago){
                                                            $fc_doctos_relacionados = $fc_pago_pago['fc_doctos_relacionados'];
                                                            ?>
                                                            <tr>
                                                                <td><?php echo $fc_pago_pago['fc_pago_pago_id'] ?></td>
                                                                <td><?php echo $fc_pago_pago['fc_pago_pago_fecha_pago'] ?></td>
                                                                <td><?php echo $fc_pago_pago['cat_sat_forma_pago_codigo'] ?></td>
                                                                <td><?php echo $fc_pago_pago['cat_sat_moneda_codigo'] ?></td>
                                                                <td><?php echo $fc_pago_pago['com_tipo_cambio_monto'] ?></td>
                                                                <td><?php echo $fc_pago_pago['fc_pago_pago_monto'] ?></td>
                                                            </tr>
                                                            <tr>
                                                                <td colspan="6">
                                                                    <h4>Documento</h4>
                                                                    <div class="table table-responsive">
                                                                        <table class="table table-striped">
                                                                            <thead>
                                                                                <tr>
                                                                                    <th>Folio</th>
                                                                                    <th>Fecha</th>
                                                                                    <th>UUID</th>
                                                                                    <th>Total</th>
                                                                                    <th>Total TC</th>
                                                                                    <th>Monto Pagado</th>
                                                                                    <th>Monto Pagado TC</th>
                                                                                    <th>Saldo</th>
                                                                                    <th>Saldo TC</th>
                                                                                    <th>Monto</th>
                                                                                    <th>Selecciona</th>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody>
                                                                            <?php foreach ($controlador->fc_facturas as $fc_factura){ ?>
                                                                                <tr>
                                                                                    <td><?php echo $fc_factura['fc_factura_folio'] ?></td>
                                                                                    <td><?php echo $fc_factura['fc_factura_fecha'] ?></td>
                                                                                    <td><?php echo $fc_factura['fc_factura_uuid'] ?></td>
                                                                                    <td><?php echo $fc_factura['fc_factura_total'] ?></td>
                                                                                    <td><?php echo $fc_factura['total_factura_tc'] ?></td>
                                                                                    <td><?php echo $fc_factura['fc_factura_monto_pagado'] ?></td>
                                                                                    <td><?php echo $fc_factura['imp_pagado_tc'] ?></td>
                                                                                    <td><?php echo $fc_factura['fc_factura_saldo'] ?></td>
                                                                                    <td><?php echo $fc_factura['saldo_factura_tc'] ?></td>
                                                                                    <td>
                                                                                        <input type="text" class="form-text"
                                                                                               name="monto[][<?php echo $fc_factura['fc_factura_id'] ?>][<?php echo $fc_pago_pago['fc_pago_pago_id'] ?>]"
                                                                                        value="0" id="<?php echo $fc_factura['fc_factura_id'] ?>">
                                                                                    </td>
                                                                                    <td>
                                                                                        <input type="checkbox" class="selecciona" value="<?php echo $fc_factura['fc_factura_id'] ?>"  data-saldo="<?php echo $fc_factura['saldo_factura_tc'] ?>">
                                                                                    </td>
                                                                                </tr>
                                                                            <?php }?>
                                                                            </tbody>
                                                                        </table>
                                                                    </div>
                                                                </td>
                                                            </tr>

                                                            <tr>
                                                                <td colspan="6">
                                                                    <h4>Documentos Relacionados</h4>
                                                                    <div class="table table-responsive">
                                                                        <table class="table table-striped">
                                                                            <thead>
                                                                            <tr>
                                                                                <th>UUID</th>
                                                                                <th>Total</th>
                                                                                <th>Monto Pagado</th>
                                                                                <th>Saldo</th>
                                                                                <th>Monto</th>
                                                                                <th>Elimina</th>
                                                                            </tr>
                                                                            </thead>
                                                                            <tbody>
                                                                            <?php foreach ($fc_doctos_relacionados as $fc_docto_relacionado){ ?>
                                                                                <tr>
                                                                                    <td><?php echo $fc_docto_relacionado['fc_factura_uuid'] ?></td>
                                                                                    <td><?php echo $fc_docto_relacionado['fc_factura_total'] ?></td>
                                                                                    <td><?php echo $fc_docto_relacionado['fc_factura_monto_pagado'] ?></td>
                                                                                    <td><?php echo $fc_docto_relacionado['fc_factura_saldo'] ?></td>
                                                                                    <td><?php echo $fc_docto_relacionado['fc_docto_relacionado_imp_pagado'] ?></td>
                                                                                    <td><?php echo $fc_docto_relacionado['elimina_bd'] ?></td>
                                                                                </tr>
                                                                            <?php }?>
                                                                            </tbody>
                                                                        </table>
                                                                    </div>
                                                                </td>
                                                            </tr>

                                                        <?php }?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php }?>
                                </tbody>
                            </table>
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

    </div>


</main>















