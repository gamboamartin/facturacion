<?php /** @var gamboamartin\facturacion\controllers\controlador_fc_factura $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>

<main class="main section-color-primary">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="widget  widget-box box-container form-main widget-form-cart" id="form">
                    <form method="post" action="<?php echo $controlador->link_fc_partida_alta_bd; ?>" class="form-additional">
                        <?php include (new views())->ruta_templates."head/title.php"; ?>
                        <?php include (new views())->ruta_templates."head/subtitulo.php"; ?>
                        <?php include (new views())->ruta_templates."mensajes.php"; ?>

                        <?php echo $controlador->inputs->fc_factura_id; ?>
                        <?php echo $controlador->inputs->com_producto_id; ?>
                        <?php echo $controlador->inputs->unidad; ?>
                        <?php echo $controlador->inputs->impuesto; ?>
                        <?php echo $controlador->inputs->descripcion; ?>
                        <?php echo $controlador->inputs->cantidad; ?>
                        <?php echo $controlador->inputs->valor_unitario; ?>
                        <?php echo $controlador->inputs->subtotal; ?>
                        <?php echo $controlador->inputs->descuento; ?>
                        <?php echo $controlador->inputs->total; ?>

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
                        <div class="controls">
                            <a href="<?php echo $controlador->link_modifica; ?>" class="btn btn-link btn-sm " ><b>Ir Factura</b></a><br>
                        </div>
                    </div>
                    <div class="">
                        <table id="fc_partida" class="table table-striped" >
                            <thead>
                            <tr>

                                <th>Clav Prod. Serv.</th>
                                <th>No Identificación</th>
                                <th>Cantidad</th>
                                <th>Unidad</th>
                                <th>Valor Unitario</th>
                                <th>Importe</th>
                                <th>Descuento</th>
                                <th>Objeto Impuesto</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($controlador->partidas->registros as $partida){
                                $traslados = $partida['fc_traslado'];
                                $retenciones = $partida['fc_retenido'];

                                ?>
                                <tr>

                                    <td><?php echo $partida['cat_sat_producto_codigo']; ?></td>
                                    <td><?php echo $partida['com_producto_codigo']; ?></td>
                                    <td><?php echo $partida['fc_partida_cantidad']; ?></td>
                                    <td><?php echo $partida['cat_sat_unidad_descripcion']; ?></td>
                                    <td><?php echo $partida['fc_partida_valor_unitario']; ?></td>
                                    <td><?php echo $partida['fc_partida_importe']; ?></td>
                                    <td><?php echo $partida['fc_partida_descuento']; ?></td>
                                    <td><?php echo $partida['cat_sat_obj_imp_descripcion']; ?></td>
                                </tr>
                                <tr>
                                    <td class="nested" colspan="8">
                                        <table class="table table-striped" >
                                            <thead >
                                                <tr>
                                                    <th  >Producto</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <tr>
                                                <td  ><?php echo $partida['com_producto_descripcion']; ?></td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>

                                <tr>
                                    <td class="nested" colspan="8">
                                        <table class="table table-striped" >
                                            <thead >
                                            <tr>
                                                <th colspan="4">Traslados</th>
                                            </tr>
                                            <tr>
                                                <th>Tipo Impuesto</th>
                                                <th>Tipo Factor</th>
                                                <th>Factor</th>
                                                <th>Importe</th>

                                            </tr>
                                            </thead>
                                            <tbody>
                                            <?php foreach ($traslados as $traslado){ ?>
                                            <tr>

                                                <td><?php echo $traslado['cat_sat_tipo_impuesto_descripcion']; ?></td>
                                                <td><?php echo $traslado['cat_sat_tipo_factor_descripcion']; ?></td>
                                                <td><?php echo $traslado['cat_sat_factor_factor']; ?></td>
                                                <td><?php echo $traslado['fc_traslado_importe']; ?></td>

                                            </tr>
                                            <?php } ?>
                                            </tbody>

                                        </table>
                                    </td>
                                </tr>

                                <tr>
                                    <td class="nested" colspan="8">
                                        <table class="table table-striped" >
                                            <thead >
                                            <tr>
                                                <th colspan="4">Retenidos</th>
                                            </tr>
                                            <tr>
                                                <th>Tipo Impuesto</th>
                                                <th>Tipo Factor</th>
                                                <th>Factor</th>
                                                <th>Importe</th>

                                            </tr>
                                            </thead>
                                            <tbody>
                                            <?php foreach ($retenciones as $retenido){ ?>
                                                <tr>

                                                    <td><?php echo $retenido['cat_sat_tipo_impuesto_descripcion']; ?></td>
                                                    <td><?php echo $retenido['cat_sat_tipo_factor_descripcion']; ?></td>
                                                    <td><?php echo $retenido['cat_sat_factor_factor']; ?></td>
                                                    <td><?php echo $retenido['fc_retenido_importe']; ?></td>

                                                </tr>
                                            <?php } ?>
                                            </tbody>

                                        </table>
                                    </td>
                                </tr>

                            <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>





