<?php /** @var \gamboamartin\facturacion\controllers\controlador_com_cliente $controlador */ ?>
<?php use config\views; ?>

<main class="main section-color-primary">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">

                <?php include (new views())->ruta_templates . "head/title.php"; ?>
                <?php include (new views())->ruta_templates . "mensajes.php"; ?>

                <div class="widget widget-box box-container form-main widget-form-cart" id="form">

                    <?php include (new views())->ruta_templates . "head/subtitulo.php"; ?>

                    <form method="post"
                          action="index.php?seccion=com_cliente&accion=modifica_datos_adicionales_bd&registro_id=<?php echo $_GET['registro_id']; ?>&session_id=<?php echo $_GET['session_id']; ?><?php echo isset($_GET['adm_menu_id']) ? '&adm_menu_id=' . $_GET['adm_menu_id'] : ''; ?>"
                          class="form-additional" enctype="multipart/form-data">

                        <?php echo $controlador->inputs->horario; ?>
                        <?php echo $controlador->inputs->telefono_emergencia; ?>
                        <?php echo $controlador->inputs->nombre_emergencia; ?>
                        <?php echo $controlador->inputs->curp; ?>

                        <?php
                            $style_remove = 'position:absolute;top:-8px;right:-8px;background:#dc3545;color:#fff;border:none;border-radius:50%;width:24px;height:24px;font-size:14px;line-height:24px;text-align:center;cursor:pointer;padding:0;';
                            if (empty($controlador->foto_actual)) {
                                $style_remove = 'display:none;' . $style_remove;
                            }
                        ?>

                        <div class="row">
                            <div class="col-sm-6">
                                <div class="control-group">
                                    <label class="control-label">Foto del Estudiante</label>
                                    <div class="controls">
                                        <div style="position:relative;width:150px;height:150px;margin-bottom:10px;">
                                            <div id="foto-preview-container" style="width:150px;height:150px;border:2px dashed #ccc;
                                                 display:flex;align-items:center;justify-content:center;
                                                 background:#f9f9f9;cursor:pointer;overflow:hidden;"
                                                 onclick="document.getElementById('foto').click()">
                                                <?php if (!empty($controlador->foto_actual)): ?>
                                                    <span id="foto-placeholder" style="display:none;color:#aaa;font-size:13px;text-align:center;">
                                                        Click para<br>seleccionar foto
                                                    </span>
                                                    <img id="foto-preview" src="<?php echo $controlador->foto_actual; ?>"
                                                         style="width:100%;height:100%;object-fit:cover;" />
                                                <?php else: ?>
                                                    <span id="foto-placeholder" style="color:#aaa;font-size:13px;text-align:center;">
                                                        Click para<br>seleccionar foto
                                                    </span>
                                                    <img id="foto-preview" src=""
                                                         style="display:none;width:100%;height:100%;object-fit:cover;" />
                                                <?php endif; ?>
                                            </div>
                                            <button type="button" id="foto-remove"
                                                    style="<?php echo $style_remove; ?>"
                                                    onclick="removeFoto()">✕</button>
                                        </div>
                                        <input type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/webp"
                                               style="display:none;" onchange="previewFoto(this)" />
                                        <input type="hidden" id="eliminar_foto" name="eliminar_foto" value="0" />
                                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                                onclick="document.getElementById('foto').click()">
                                            Cambiar foto
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="control-group btn-alta">
                            <div class="controls">
                                <button class="btn btn-success" role="submit">
                                    Guardar
                                </button>
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

<script>
function previewFoto(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('foto-preview').src = e.target.result;
            document.getElementById('foto-preview').style.display = 'block';
            document.getElementById('foto-placeholder').style.display = 'none';
            document.getElementById('foto-remove').style.display = 'block';
            document.getElementById('eliminar_foto').value = '0';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
function removeFoto() {
    document.getElementById('foto').value = '';
    document.getElementById('foto-preview').src = '';
    document.getElementById('foto-preview').style.display = 'none';
    document.getElementById('foto-placeholder').style.display = 'block';
    document.getElementById('foto-remove').style.display = 'none';
    document.getElementById('eliminar_foto').value = '1';
}
</script>