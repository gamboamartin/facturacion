<?php /** @var \gamboamartin\facturacion\controllers\controlador_com_cliente $controlador */ ?>
<?php use config\generales; ?>
<?php use config\views; ?>
<?php echo $controlador->inputs->documento; ?>
<?php echo $controlador->inputs->com_tipo_cliente_id; ?>
<?php echo $controlador->inputs->codigo; ?>
<?php echo $controlador->inputs->razon_social; ?>
<?php echo $controlador->inputs->com_cliente_rfc; ?>
<?php echo $controlador->inputs->telefono; ?>
<?php echo $controlador->inputs->cat_sat_tipo_persona_id; ?>
<?php echo $controlador->inputs->cat_sat_regimen_fiscal_id; ?>
<?php echo $controlador->inputs->dp_pais_id; ?>
<?php echo $controlador->inputs->dp_estado_id; ?>
<?php echo $controlador->inputs->dp_municipio_id; ?>
<?php echo $controlador->inputs->cp; ?>
<?php echo $controlador->inputs->colonia; ?>
<?php echo $controlador->inputs->calle; ?>
<?php echo $controlador->inputs->numero_exterior; ?>
<?php echo $controlador->inputs->numero_interior; ?>
<?php echo $controlador->inputs->cat_sat_uso_cfdi_id; ?>
<?php echo $controlador->inputs->cat_sat_metodo_pago_id; ?>
<?php echo $controlador->inputs->cat_sat_forma_pago_id; ?>
<?php echo $controlador->inputs->cat_sat_tipo_de_comprobante_id; ?>
<?php echo $controlador->inputs->cat_sat_moneda_id; ?>
<div class="col-sm-6">
    <div class="control-group">
        <label class="control-label">Fecha de Cumpleaños</label>
        <div class="controls">
            <input type="date" name="fecha_cumpleanos" class="form-control"
                   value="<?php echo $controlador->inputs->fecha_cumpleanos_value ?? ''; ?>" />
        </div>
    </div>
</div>

<?php if (property_exists(generales::class, 'datos_adicionales_com_cliente') && generales::$datos_adicionales_com_cliente): ?>
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
                            <img id="foto-preview" src=""
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

<?php include (new views())->ruta_templates.'botons/submit/alta_bd.php';?>