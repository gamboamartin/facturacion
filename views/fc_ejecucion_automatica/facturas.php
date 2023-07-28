<?php /** @var gamboamartin\facturacion\controllers\controlador_fc_factura $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>


<main class="main section-color-primary">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="widget widget-box box-container widget-mylistings">
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th>Id</th>
                            <th>Folio</th>
                            <th>RFC</th>
                            <th>Fecha</th>
                            <th>Total</th>
                            <th>UUID</th>
                            <th>Estatus</th>
                            <th>Accion</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        foreach ($controlador->registros as $fc_factura){
                        ?>
                        <tr>
                            <td><?php echo $fc_factura['fc_factura_id'] ?></td>
                            <td><?php echo $fc_factura['fc_factura_folio'] ?></td>
                            <td><?php echo $fc_factura['org_empresa_rfc'] ?></td>
                            <td><?php echo $fc_factura['fc_factura_fecha'] ?></td>
                            <td><?php echo $fc_factura['fc_factura_total'] ?></td>
                            <td><?php echo $fc_factura['fc_factura_folio_fiscal'] ?></td>
                            <td><?php echo $fc_factura['fc_factura_etapa'] ?></td>
                            <td>
                                <?php echo $fc_factura['fc_factura_acciones'] ?>
                            </td>
                        </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div> <!-- /. widget-table-->
            </div><!-- /.center-content -->
        </div>
    </div>
</main>

