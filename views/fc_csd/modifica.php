<?php use config\views; ?>
<?php
/** @var gamboamartin\facturacion\controllers\controlador_fc_csd $controlador */
?>
<main class="main section-color-primary">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="widget  widget-box box-container form-main widget-form-cart" id="form">
                    <form method="post" action="" class="form-additional" enctype="multipart/form-data">
                        <?php include (new views())->ruta_templates."head/title.php"; ?>
                        <?php include (new views())->ruta_templates."head/subtitulo.php"; ?>
                        <?php include (new views())->ruta_templates."mensajes.php"; ?>

                        <?php echo $controlador->inputs->serie; ?>
                        <?php echo $controlador->inputs->no_certificado; ?>
                        <?php echo $controlador->inputs->password; ?>
                        <?php echo $controlador->inputs->org_sucursal_id; ?>

                        <?php include (new views())->ruta_templates.'botons/submit/alta_bd.php';?>

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
                        <h2>Archivos</h2>
                    </div>
                    <div class="">
                        <table id="fc_cer_csd" class="table table-striped" >
                            <thead>
                            <tr>
                                <th>Id</th>
                                <th>File</th>
                                <th>Tipo</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td>Id</td>
                                <td>File</td>
                                <td>Tipo</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</main>


