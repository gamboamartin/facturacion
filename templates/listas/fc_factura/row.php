<?php use config\views; ?>
<?php /** @var stdClass $row  viene de registros del controler*/ ?>
<tr>
    <td><?php echo $row->fc_factura_id; ?></td>
    <td><?php echo $row->fc_factura_folio; ?></td>
    <td><?php echo $row->fc_factura_descripcion; ?></td>
    <!-- Dynamic generated -->
    <td><?php echo $row->com_cliente_rfc; ?></td>
    <td><?php echo $row->com_cliente_razon_social; ?></td>
    <td><?php echo $row->fc_factura_fecha; ?></td>
    <td><?php include 'templates/botons/fc_factura/link_genera_xml.php';?></td>
    <td><?php include 'templates/botons/fc_factura/link_factura_partidas.php';?></td>

    <!-- End dynamic generated -->

    <?php include (new views())->ruta_templates.'listas/action_row.php';?>
</tr>
