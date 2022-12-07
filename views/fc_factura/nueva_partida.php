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
                                <th></th>
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
                            <?php foreach ($controlador->partidas->registros as $partida){?>
                                <tr>
                                    <td class="details-control"></td>
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
                                    <td class="nested" colspan="9">
                                        <table class="table table-striped" >
                                            <thead >
                                                <tr>
                                                    <th colspan="8" >Producto</th>
                                                </tr>
                                                <tr>
                                                    <th colspan="8" ><?php echo $partida['com_producto_descripcion']; ?></th>
                                                </tr>
                                                <tr>
                                                    <th>Tipo Impuesto</th>
                                                    <th>Factor</th>
                                                    <th>Importe</th>
                                                    <th>Tipo Impuesto</th>
                                                    <th>Factor</th>
                                                    <th>Importe</th>
                                                </tr>
                                            </thead>
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





