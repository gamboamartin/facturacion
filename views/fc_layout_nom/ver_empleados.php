<?php /** @var  gamboamartin\facturacion\controllers\controlador_fc_layout_nom $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>

<div class="col-lg-12">
    <div class="widget  widget-box box-container form-main widget-form-cart" id="form">
        <?php include (new views())->ruta_templates."head/title.php"; ?>
        <?php include (new views())->ruta_templates."head/subtitulo.php"; ?>
        <?php include (new views())->ruta_templates."mensajes.php"; ?>
    </div>
</div>
<div class="col-lg-12">
    <div class="table table-responsive">
        <table class="table table-striped">
            <thead>
            <tr>
                <th>Id</th>
                <th>Clave Empleado</th>
                <th>Codigo Postal</th>
                <th>NSS</th>
                <th>RFC</th>
                <th>CURP</th>
                <th>NOMBRE COMPLETO</th>
                <th>NETO A DEPOSITAR</th>
                <th>BANCO</th>
                <th>CUENTA</th>
                <th>CLABE INTERBANCARIA</th>
                <th>TARJETA</th>
                <th>EMAIL</th>
                <th>UUID</th>
                <th>ERROR</th>
                <th>TIMBRA</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($controlador->fc_rows_layout as $fc_row_layout){ ?>
                <tr>
                    <td><?php echo $fc_row_layout->fc_row_layout_id; ?></td>
                    <td><?php echo $fc_row_layout->fc_row_layout_cve_empleado; ?></td>
                    <td><?php echo $fc_row_layout->fc_row_layout_cp; ?></td>
                    <td><?php echo $fc_row_layout->fc_row_layout_nss; ?></td>
                    <td><?php echo $fc_row_layout->fc_row_layout_rfc; ?></td>
                    <td><?php echo $fc_row_layout->fc_row_layout_curp; ?></td>
                    <td><?php echo $fc_row_layout->fc_row_layout_nombre_completo; ?></td>
                    <td><?php echo $fc_row_layout->fc_row_layout_neto_depositar; ?></td>
                    <td><?php echo $fc_row_layout->fc_row_layout_banco; ?></td>
                    <td><?php echo $fc_row_layout->fc_row_layout_cuenta; ?></td>
                    <td><?php echo $fc_row_layout->fc_row_layout_clabe; ?></td>
                    <td><?php echo $fc_row_layout->fc_row_layout_tarjeta; ?></td>
                    <td><?php echo $fc_row_layout->fc_row_layout_email; ?></td>
                    <td><?php echo $fc_row_layout->fc_row_layout_uuid; ?></td>
                    <td><?php echo $fc_row_layout->fc_row_layout_error; ?></td>
                    <td><?php echo $fc_row_layout->btn_timbra; ?></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>
