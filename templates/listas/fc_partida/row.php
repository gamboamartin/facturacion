<?php use config\views; ?>
<?php /** @var stdClass $row  viene de registros del controler*/ ?>
<tr>
    <td><?php echo $row->fc_partida_id; ?></td>
    <td><?php echo $row->com_producto_descripcion; ?></td>
    <td><?php echo $row->fc_partida_cantidad; ?></td>
    <td><?php echo $row->fc_partida_valor_unitario; ?></td>
    <td><?php echo $row->fc_partida_descuento; ?></td>
    <!-- Dynamic generated -->
    <td><?php echo $row->fc_factura_folio; ?></td>
    <td><?php echo $row->fc_factura_fecha; ?></td>

    <!-- End dynamic generated -->

    <?php include (new views())->ruta_templates.'listas/action_row.php';?>
</tr>
