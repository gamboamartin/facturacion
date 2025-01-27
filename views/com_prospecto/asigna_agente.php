<?php /** @var  \gamboamartin\ks_ops\controllers\controlador_com_cliente $controlador controlador en ejecucion */ ?>
<?php use config\views; ?>

<main class="main section-color-primary">
    <div class="container">

        <div class="row">

            <div class="col-lg-12">

                <div class="widget  widget-box box-container form-main widget-form-cart" id="form" >

                    <form method="post" action="<?php echo $controlador->link_com_rel_agente_prospecto_bd; ?>" class="form-additional">
                        <?php include (new views())->ruta_templates . "head/title.php"; ?>
                        <?php include (new views())->ruta_templates . "head/subtitulo.php"; ?>
                        <?php include (new views())->ruta_templates . "mensajes.php"; ?>
                        <?php echo $controlador->inputs->com_agente_id; ?>
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
            <?php echo $controlador->button_com_prospecto_modifica; ?>
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


















