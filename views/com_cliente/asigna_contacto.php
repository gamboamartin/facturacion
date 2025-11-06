<?php /** @var  \gamboamartin\facturacion\controllers\controlador_com_cliente $controlador controlador en ejecucion */ ?>
<?php use config\views; ?>

<main class="main section-color-primary">
    <div class="container">

        <div class="row">

            <div class="col-lg-12">

                <div class="widget  widget-box box-container form-main widget-form-cart" id="form" >

                    <form method="post" action="<?php echo $controlador->link_asigna_contacto_bd; ?>" class="form-additional">
                        <?php include (new views())->ruta_templates . "head/title.php"; ?>
                        <?php include (new views())->ruta_templates . "head/subtitulo.php"; ?>
                        <?php include (new views())->ruta_templates . "mensajes.php"; ?>
                        <?php echo $controlador->inputs->com_tipo_contacto_id; ?>
                        <?php echo $controlador->inputs->nombre; ?>
                        <?php echo $controlador->inputs->ap; ?>
                        <?php echo $controlador->inputs->am; ?>
                        <?php echo $controlador->inputs->telefono; ?>
                        <?php echo $controlador->inputs->correo; ?>
                        <input type="hidden" name="com_cliente_id" value="<?php echo $_GET['registro_id'] ?>">
                        <div class="control-group btn-alta">
                            <div class="controls">
                                <button class="btn btn-success" role="submit">Asignar</button><br>
                            </div>
                        </div>
                    </form>
                </div>

            </div>
        </div>
        <div class="col-md-12 buttons-form">
            <?php echo $controlador->button_com_cliente_modifica; ?>
        </div>
    </div>

</main>

<main class="main section-color-primary">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="widget widget-box box-container widget-mylistings">
                    <?php echo $controlador->contenido_table; ?>
                </div> <!-- /. widget-table-->
            </div><!-- /.center-content -->
        </div>
    </div>
</main>


















