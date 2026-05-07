<?php /** @var  gamboamartin\facturacion\controllers\controlador_fc_empleado_contacto $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>
<?php
    $metodo = 'modifica_correo_bd';
    $link = "index.php?seccion=fc_empleado_contacto";
    $link .= "&accion={$metodo}&session_id={$_GET['session_id']}";

    $contacto = $controlador->registro['fc_empleado_contacto_descripcion'];


?>

<div class="col-lg-12">

    <?php include (new views())->ruta_templates."head/title.php"; ?>
    <?php include (new views())->ruta_templates."mensajes.php"; ?>

    <div class="widget  widget-box box-container form-main widget-form-cart" id="form">

        <?php include (new views())->ruta_templates."head/subtitulo.php"; ?>

        <form method="post"
              action="<?php echo $link; ?>"
              class="form-additional"
              enctype="multipart/form-data">
            <div class="row">
                <div class="control-group col-sm-6">
                    <label class="control-label" for="descripcion">Contacto</label>
                    <div class="controls">
                        <input value="<?php echo $contacto; ?>" type="text" disabled="disabled"   class="form-control descripcion" title="Contacto">
                    </div>
                </div>
            </div>

            <div class="row">
                <?php echo $controlador->inputs->correo; ?>
            </div>

            <input type="hidden"
                   name="fc_empleado_contacto_id"
                   value="<?php echo $_GET['registro_id'] ?>">

            <div class="row">
                <div class="control-group btn-alta">
                    <div class="controls">
                        <button class="btn btn-success" role="button">
                            Modificar
                        </button><br>
                    </div>
                </div>
            </div>
        </form>

    </div>
</div>
