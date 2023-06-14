<?php /** @var  \gamboamartin\facturacion\controllers\controlador_fc_key_csd $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>

<main class="main section-color-primary">
    <div class="container">
        <div class="row">

            <div class="col-lg-12 table-responsive">
                <?php include (new views())->ruta_templates."head/title.php"; ?>
                <?php include (new views())->ruta_templates."head/subtitulo.php"; ?>
                <?php include (new views())->ruta_templates."mensajes.php"; ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <?php echo $controlador->ths; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php echo $controlador->trs; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>













