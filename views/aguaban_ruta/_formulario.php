<?php
$ruta_edita = $ruta_modo === 'modifica';
$ruta_accion = $ruta_edita ? 'modifica_bd' : 'alta_bd';
$ruta_id = $ruta_edita ? (int)($ruta_registro['id'] ?? 0) : null;
$ruta_estado_bloqueado = $ruta_edita && !$ruta_permitida('status');
?>
<form class="rutas-tarjeta rutas-formulario" method="post" action="<?= $ruta_escape($ruta_url($ruta_accion, $ruta_id)) ?>">
    <input type="hidden" name="csrf" value="<?= $ruta_csrf ?>">
    <p class="rutas-ayuda rutas-ancho">Los campos con * son obligatorios.</p>
    <div><label for="ruta-codigo">Código *</label><input id="ruta-codigo" name="codigo" required maxlength="32" value="<?= $ruta_escape($ruta_registro['codigo'] ?? '') ?>" placeholder="Ej. RUTA-CENTRO-01"></div>
    <div><label for="ruta-descripcion">Descripción *</label><input id="ruta-descripcion" name="descripcion" required maxlength="150" value="<?= $ruta_escape($ruta_registro['descripcion'] ?? '') ?>"></div>
    <div><label for="ruta-sector">Sector</label><input id="ruta-sector" name="sector" maxlength="100" value="<?= $ruta_escape($ruta_registro['sector'] ?? '') ?>"></div>
    <div><label for="ruta-operador">Operador / checador asignado</label><select id="ruta-operador" name="adm_usuario_id">
        <option value="">Sin asignar</option>
        <?php foreach ($ruta_operadores as $ruta_operador): ?>
            <option value="<?= $ruta_escape($ruta_operador['id'] ?? '') ?>" <?= (string)($ruta_operador['id'] ?? '') === $ruta_texto($ruta_registro['adm_usuario_id'] ?? '') ? 'selected' : '' ?>><?= $ruta_escape($ruta_operador['descripcion'] ?? '') ?></option>
        <?php endforeach; ?>
    </select></div>
    <div><label for="ruta-status">Estado *</label><select id="ruta-status" name="status" required <?= $ruta_estado_bloqueado ? 'disabled' : '' ?>>
        <option value="activo" <?= ($ruta_registro['status'] ?? 'activo') === 'activo' ? 'selected' : '' ?>>Activo</option>
        <option value="inactivo" <?= ($ruta_registro['status'] ?? '') === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
    </select><?php if ($ruta_estado_bloqueado): ?><input type="hidden" name="status" value="<?= $ruta_escape($ruta_registro['status'] ?? '') ?>"><?php endif; ?></div>
    <div class="rutas-acciones rutas-ancho">
        <?php if ($ruta_permitida($ruta_accion)): ?><button class="ruta-boton ruta-primario" type="submit"><?= $ruta_edita ? 'Guardar cambios' : 'Crear ruta' ?></button><?php endif; ?>
        <a class="ruta-boton ruta-secundario" href="<?= $ruta_escape($ruta_url('lista')) ?>">Cancelar</a>
    </div>
</form>
