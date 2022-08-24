<?php use config\views; ?>
<?php /** @var stdClass $row  viene de registros del controler*/ ?>
<tr>
    <td><?php echo $row->fc_factura_id; ?></td>
    <td><?php echo $row->fc_factura_codigo; ?></td>
    <td><?php echo $row->fc_factura_codigo_bis; ?></td>
    <!-- Dynamic generated -->
    <td><?php echo $row->fc_factura_descripcion; ?></td>
    <td><?php echo $row->fc_factura_descripcion_select; ?></td>
    <td><?php echo $row->fc_factura_alias; ?></td>
    <td><?php include 'templates/botons/fc_factura/link_factura_partidas.php';?></td>

    <!-- End dynamic generated -->

    <?php include (new views())->ruta_templates.'listas/action_row.php';?>
</tr>
