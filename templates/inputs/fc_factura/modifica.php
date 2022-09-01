<?php /** @var  \gamboamartin\facturacion\controllers\controlador_fc_factura $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>
<?php echo $controlador->inputs->select->fc_csd_id; ?>
<?php echo $controlador->inputs->select->com_sucursal_id; ?>
<?php echo $controlador->inputs->folio; ?>
<?php echo $controlador->inputs->fecha; ?>
<?php echo $controlador->inputs->select->exportacion; ?>
<?php echo $controlador->inputs->serie; ?>

<?php echo $controlador->inputs->subtotal; ?>
<?php echo $controlador->inputs->descuento; ?>
<?php echo $controlador->inputs->total; ?>
<?php echo $controlador->inputs->impuestos_trasladados; ?>
<?php echo $controlador->inputs->impuestos_retenidos; ?>
<?php echo $controlador->inputs->select->cat_sat_tipo_de_comprobante_id; ?>
<?php echo $controlador->inputs->select->cat_sat_forma_pago_id; ?>
<?php echo $controlador->inputs->select->cat_sat_metodo_pago_id; ?>
<?php echo $controlador->inputs->select->cat_sat_moneda_id; ?>
<?php echo $controlador->inputs->select->com_tipo_cambio_id; ?>
<?php echo $controlador->inputs->select->cat_sat_uso_cfdi_id; ?>
<?php include (new views())->ruta_templates.'botons/submit/modifica_bd.php';?>
<a href="<?php echo $controlador->link_fc_factura_partidas; ?>" class="btn btn-info"><i class="icon-edit"></i>
    Nueva Partida
</a>

            <div class="widget widget-box box-container widget-mylistings">
                <div class="widget-header">
                    <h2>Partidas</h2>
                </div>
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
            </div>
