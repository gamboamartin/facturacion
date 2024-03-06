<?php /** @var gamboamartin\facturacion\controllers\controlador_fc_factura $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>
<main class="main section-color-primary">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <?php include (new views())->ruta_templates."head/title.php"; ?>

                <?php include (new views())->ruta_templates."mensajes.php"; ?>

                <div class="widget  widget-box box-container form-main widget-form-cart" id="form">
                    <form method="post" action="<?php echo $controlador->link_adjunta_bd; ?>" class="form-additional" enctype="multipart/form-data">
                        <?php include (new views())->ruta_templates."head/subtitulo.php"; ?>

                        <?php echo $controlador->inputs->fc_complemento_pago_id; ?>
                        <?php echo $controlador->inputs->fc_complemento_pago_folio; ?>
                        <?php echo $controlador->inputs->adjunto; ?>


                        <?php echo $controlador->inputs->hidden_row_id; ?>
                        <?php echo $controlador->inputs->hidden_seccion_retorno; ?>
                        <?php echo $controlador->inputs->hidden_id_retorno; ?>
                        <div class="controls">
                            <button type="submit" class="btn btn-success" value="correo" name="btn_action_next">Alta</button><br>
                        </div>
                    </form>

                </div>

            </div>
        </div>
        <div class="col-md-12 buttons-form">
            <?php echo $controlador->button_fc_factura_modifica; ?>
        </div>
    </div>

</main>

<main class="main section-color-primary">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="widget widget-box box-container widget-mylistings">
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th>Id</th>
                            <th>Documento</th>
                            <th>Elimina</th>
                            <th>Descarga</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        foreach ($controlador->documentos as $documento){
                        ?>
                        <tr>
                            <td><?php echo $documento['id'] ?></td>
                            <td><?php echo $documento['doc_documento_name_out'] ?></td>
                            <td><?php echo $documento['del'] ?></td>
                            <td><?php echo $documento['descarga'] ?></td>
                        </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div> <!-- /. widget-table-->
            </div><!-- /.center-content -->
        </div>
        <div class="col-md-12 buttons-form">
            <?php echo $controlador->button_fc_factura_modifica; ?>
        </div>
    </div>
</main>

