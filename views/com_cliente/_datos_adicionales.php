<?php use config\generales; ?>
<section class="com-cliente-card com-cliente-section"><div class="com-cliente-section-heading"><span class="com-cliente-step" aria-hidden="true">+</span><div><h2>Datos adicionales</h2><p>Información particular para la atención del cliente.</p></div></div><div class="com-cliente-fields">
<?php if (property_exists(generales::class, 'datos_adicionales_com_cliente') && generales::$datos_adicionales_com_cliente): ?>
    <div class="col-sm-6">
        <div class="control-group">
            <label class="control-label" for="fecha_cumpleanos">Fecha de cumpleaños</label>
            <div class="controls">
                <input type="date" id="fecha_cumpleanos" name="fecha_cumpleanos" class="form-control"
                       value="<?php echo htmlspecialchars((string)($controlador->inputs->fecha_cumpleanos_value ?? ''), ENT_QUOTES, 'UTF-8'); ?>" />
            </div>
        </div>
    </div>
    <?php echo $controlador->inputs->horario ?? ''; ?>
    <?php echo $controlador->inputs->telefono_emergencia ?? ''; ?>
    <?php echo $controlador->inputs->nombre_emergencia ?? ''; ?>
    <?php echo $controlador->inputs->curp ?? ''; ?>

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
                            <span id="foto-placeholder" style="color:#aaa;font-size:13px;text-align:center;">
                                Click para<br>seleccionar foto
                            </span>
                            <img id="foto-preview" src="" alt="Vista previa de la foto del cliente"
                                 style="display:none;width:100%;height:100%;object-fit:cover;" />
                        </div>
                        <button type="button" id="foto-remove"
                                style="display:none;position:absolute;top:-8px;right:-8px;
                                       background:#dc3545;color:#fff;border:none;border-radius:50%;
                                       width:24px;height:24px;font-size:14px;line-height:24px;
                                       text-align:center;cursor:pointer;padding:0;"
                                onclick="removeFoto()">✕</button>
                    </div>
                    <input type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/webp"
                           style="display:none;" onchange="previewFoto(this)" />
                    <button type="button" class="btn btn-sm btn-outline-secondary"
                            onclick="document.getElementById('foto').click()">
                        Seleccionar foto
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    function previewFoto(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('foto-preview').src = e.target.result;
                document.getElementById('foto-preview').style.display = 'block';
                document.getElementById('foto-placeholder').style.display = 'none';
                document.getElementById('foto-remove').style.display = 'block';
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
    }
    </script>
<?php endif; ?>

</div></section>
