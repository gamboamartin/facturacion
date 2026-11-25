<?php
$ruta_titulo = 'Eliminar ruta';
require __DIR__.'/_inicio.php';
$ruta_id = (int)($ruta_registro['id'] ?? 0);
?>
<div class="rutas-tarjeta">
    <?php if ($ruta_id > 0): ?>
        <h2>Confirma la eliminación</h2>
        <p>Vas a eliminar la ruta <strong><?= $ruta_escape($ruta_registro['codigo'] ?? '') ?></strong>: <?= $ruta_escape($ruta_registro['descripcion'] ?? '') ?>.</p>
        <p>Esta operación elimina el registro. Si deseas conservarlo, puedes desactivarlo desde su detalle.</p>
        <form method="post" action="<?= $ruta_escape($ruta_url('elimina_bd', $ruta_id)) ?>" class="rutas-acciones">
            <input type="hidden" name="csrf" value="<?= $ruta_csrf ?>">
            <?php if ($ruta_permitida('elimina_bd')): ?><button type="submit" class="ruta-boton ruta-peligro">Confirmar eliminación</button><?php endif; ?>
            <a class="ruta-boton ruta-secundario" href="<?= $ruta_escape($ruta_url('consulta', $ruta_id)) ?>">Cancelar</a>
        </form>
    <?php else: ?><p>No se encontró la ruta solicitada.</p><?php endif; ?>
</div>
</section>
