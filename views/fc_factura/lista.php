<?php /** @var  \gamboamartin\facturacion\controllers\controlador_fc_factura $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>

<main class="main section-color-primary">
<div class="container">
    <div class="row">
        <div class="col-lg-12">
            <?php include (new views())->ruta_templates."head/title.php"; ?>
            <?php include (new views())->ruta_templates."mensajes.php"; ?>
            <div class="widget widget-box box-container widget-mylistings" >
                <div class="widget-header text-uppercase" style="display: flex;justify-content: space-between;align-items: center;">
                    <h2>Registro de Facturaciones</h2>
                    <div class="controls">
                        <a href="<?php echo $controlador->link_com_producto; ?>" class="btn btn-primary btn-sm active" >Productos</a><br>
                    </div>
                </div>
                <table  class="table datatable table-striped" ></table>
            </div>
        </div>
    </div>
</div>

</main>























