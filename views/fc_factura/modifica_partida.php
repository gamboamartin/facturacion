<?php /** @var gamboamartin\organigrama\controllers\controlador_org_empresa $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>

<main class="main section-color-primary">
    <div class="container">
        <div class="row">
            <form method="post" action="<?php echo $controlador->link_fc_partida_modifica_bd; ?>" class="form-additional">
                <div class="col-lg-12">


                    <?php include (new views())->ruta_templates."head/title.php"; ?>
                    <?php include (new views())->ruta_templates."head/subtitulo.php"; ?>
                    <?php include (new views())->ruta_templates."mensajes.php"; ?>

                    <?php echo $controlador->inputs->select->fc_factura_id; ?>
                </div>
                <div class="col-lg-12">

                    <?php echo $controlador->inputs->select->com_producto_id; ?>
                    <?php echo $controlador->inputs->descripcion; ?>
                    <?php echo $controlador->inputs->cantidad; ?>
                    <?php echo $controlador->inputs->valor_unitario; ?>
                    <?php echo $controlador->inputs->descuento; ?>
                    <?php include (new views())->ruta_templates.'botons/submit/modifica_bd.php';?>
                </div>
            </form>
        </div>

    </div>
    <br>
</main>






