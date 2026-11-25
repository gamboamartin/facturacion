<?php
$ruta_titulo = 'Detalle de ruta';
require __DIR__.'/_inicio.php';
$ruta_id = (int)($ruta_registro['id'] ?? 0);
?>
<div class="rutas-tarjeta">
    <?php if ($ruta_id > 0): ?>
    <dl class="rutas-detalle">
        <?php foreach (['codigo'=>'Código', 'descripcion'=>'Descripción', 'sector'=>'Sector', 'operador_descripcion'=>'Operador / checador', 'status'=>'Estado', 'fecha_alta'=>'Fecha de alta', 'fecha_update'=>'Última modificación'] as $ruta_campo=>$ruta_etiqueta): ?>
            <div><dt><?= $ruta_escape($ruta_etiqueta) ?></dt><dd><?= $ruta_escape(($ruta_registro[$ruta_campo] ?? '') !== '' && $ruta_registro[$ruta_campo] !== null ? $ruta_registro[$ruta_campo] : 'Sin asignar') ?></dd></div>
        <?php endforeach; ?>
    </dl>
    <div class="rutas-acciones">
        <?php if ($ruta_permitida('modifica')): ?><a class="ruta-boton ruta-primario" href="<?= $ruta_escape($ruta_url('modifica', $ruta_id)) ?>">Editar ruta</a><?php endif; ?>
        <?php if ($ruta_permitida('status')): ?><form method="post" action="<?= $ruta_escape($ruta_url('status', $ruta_id)) ?>"><input type="hidden" name="csrf" value="<?= $ruta_csrf ?>"><input type="hidden" name="status" value="<?= ($ruta_registro['status'] ?? '') === 'activo' ? 'inactivo' : 'activo' ?>"><button type="submit" class="ruta-boton ruta-secundario"><?= ($ruta_registro['status'] ?? '') === 'activo' ? 'Desactivar ruta' : 'Activar ruta' ?></button></form><?php endif; ?>
        <?php if ($ruta_permitida('elimina')): ?><a class="ruta-boton ruta-peligro" href="<?= $ruta_escape($ruta_url('elimina', $ruta_id)) ?>">Eliminar ruta</a><?php endif; ?>
    </div>
    <?php else: ?><p>No se encontró la ruta solicitada.</p><?php endif; ?>
</div>
</section>
