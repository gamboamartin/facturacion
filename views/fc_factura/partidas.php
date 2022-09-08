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

                        <?php echo $controlador->inputs->select->fc_factura_id; ?>
                        <?php echo $controlador->inputs->select->com_producto_id; ?>
                        <?php echo $controlador->inputs->descripcion; ?>
                        <?php echo $controlador->inputs->cantidad; ?>
                        <?php echo $controlador->inputs->valor_unitario; ?>
                        <?php echo $controlador->inputs->descuento; ?>
                        <?php echo $controlador->inputs->select->cat_sat_tipo_factor_id; ?>
                        <?php echo $controlador->inputs->select->cat_sat_factor_id; ?>
                        <?php echo $controlador->inputs->select->cat_sat_tipo_impuesto_id; ?>

                        <div class="control-group btn-alta">
                            <div class="controls">
                                <button type="submit" class="btn btn-success" value="partidas" name="btn_action_next">Alta</button><br>
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

                    <div class="">
                        <table class="table table-striped footable-sort" data-sorting="true">
                            <th>Id</th>
                            <th>Codigo</th>
                            <th>Descripcion</th>
                            <th>Producto SAT</th>
                            <th>Unidad</th>
                            <th>Cantidad</th>
                            <th>Valor Unitario</th>
                            <th>Descuento</th>
                            <th>Ver</th>
                            <th>Modifica</th>
                            <th>Elimina</th>

                            <tbody>
                            <?php foreach ($controlador->partidas->registros as $partida){
                                ?>
                            <tr>
                                <td><?php echo $partida['fc_partida_id']; ?></td>
                                <td><?php echo $partida['fc_partida_codigo']; ?></td>
                                <td><?php echo $partida['fc_partida_descripcion']; ?></td>
                                <td><?php echo $partida['cat_sat_producto_descripcion']; ?></td>
                                <td><?php echo $partida['cat_sat_unidad_descripcion']; ?></td>
                                <td><?php echo $partida['fc_partida_cantidad']; ?></td>
                                <td><?php echo $partida['fc_partida_valor_unitario']; ?></td>
                                <td><?php echo $partida['fc_partida_descuento']; ?></td>
                                <td><?php echo $partida['link_ve']; ?></td>
                                <td><?php echo $partida['link_modifica']; ?></td>
                                <td><?php echo $partida['link_elimina']; ?></td>

                            </tr>
                            <?php } ?>
                            </tbody>
                        </table>
                        <div class="box-body">
                            * Total registros: <?php echo $controlador->partidas->n_registros; ?><br />
                            * Fecha Hora: <?php echo $controlador->fecha_hoy; ?>
                        </div>
                    </div>
                </div> <!-- /. widget-table-->
            </div><!-- /.center-content -->
        </div>
    </div>


</main>





