<?php /** @var  gamboamartin\facturacion\controllers\controlador_fc_layout_nom $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>
<?php
$disabled = '';
if ($controlador->disabled){
    $disabled = 'disabled';
}
?>
<div class="col-lg-12">
    <div class="widget  widget-box box-container form-main widget-form-cart" id="form">
        <form method="post" action="<?php echo $controlador->link_modifica_datos_bd; ?>" class="form-additional" >
            <?php include (new views())->ruta_templates."head/title.php"; ?>
            <?php include (new views())->ruta_templates."head/subtitulo.php"; ?>
            <?php include (new views())->ruta_templates."mensajes.php"; ?>
            
            <div class="control-group col-sm-6">
                <label class="control-label" for="fecha_emision">FECHA EMISIÓN</label>
                <div class="controls">
                    <input type="datetime-local" value="<?php echo $controlador->fecha_emision ?>" <?php echo $disabled ?> name="fecha_emision" class="form-control fecha_emision" required="" id="fecha_emision" title="Fecha Emisión">
                </div>
            </div>
            
            <div class="control-group col-sm-12">
                <div class="controls">
                    <button type="submit" <?php echo $disabled ?> class="btn btn-info">Modificar Fecha de Emisión</button>
                    <a href="index.php?seccion=fc_layout_nom&accion=ver_empleados&registro_id=<?php echo $controlador->registro_id; ?>&session_id=<?php echo $_GET['session_id']; ?>" class="btn btn-secondary">Cancelar</a>
                </div>
            </div>
            <br>
            <br>
            <br>
            <br>
            <br>
            <br><br><br>
            <br><br><br>
        </form>
    </div>
</div>
