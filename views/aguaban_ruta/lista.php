<?php
$ruta_titulo = 'Rutas de medición';
require __DIR__.'/_inicio.php';
$ruta_filtros = is_array($ruta_config['filtros'] ?? null) ? $ruta_config['filtros'] : [];
$ruta_resultado = is_array($ruta_config['resultado'] ?? null) ? $ruta_config['resultado'] : [];
$ruta_filas = is_array($ruta_resultado['registros'] ?? null) ? $ruta_resultado['registros'] : [];
$ruta_pagina = max(1, (int)($ruta_resultado['pagina'] ?? 1));
$ruta_paginas = max(1, (int)($ruta_resultado['paginas'] ?? 1));
parse_str((string)parse_url($ruta_url('lista'), PHP_URL_QUERY), $ruta_query);
?>
<form method="get" action="index.php" class="rutas-tarjeta rutas-filtros" aria-label="Filtrar rutas">
    <?php foreach (['seccion','accion','session_id','adm_menu_id'] as $ruta_clave): ?><?php if (isset($ruta_query[$ruta_clave]) && is_scalar($ruta_query[$ruta_clave])): ?><input type="hidden" name="<?= $ruta_escape($ruta_clave) ?>" value="<?= $ruta_escape($ruta_query[$ruta_clave]) ?>"><?php endif; ?><?php endforeach; ?>
    <?php foreach (['codigo'=>'Código', 'descripcion'=>'Descripción', 'sector'=>'Sector'] as $ruta_campo=>$ruta_etiqueta): ?>
        <div><label for="filtro-<?= $ruta_escape($ruta_campo) ?>"><?= $ruta_escape($ruta_etiqueta) ?></label><input id="filtro-<?= $ruta_escape($ruta_campo) ?>" name="<?= $ruta_escape($ruta_campo) ?>" value="<?= $ruta_escape($ruta_filtros[$ruta_campo] ?? '') ?>" maxlength="<?= $ruta_campo === 'codigo' ? '32' : ($ruta_campo === 'sector' ? '100' : '150') ?>"></div>
    <?php endforeach; ?>
    <div><label for="filtro-operador">Operador / checador</label><select id="filtro-operador" name="adm_usuario_id"><option value="">Todos</option><?php foreach ($ruta_operadores as $ruta_operador): ?><option value="<?= $ruta_escape($ruta_operador['id'] ?? '') ?>" <?= (string)($ruta_operador['id'] ?? '') === $ruta_texto($ruta_filtros['adm_usuario_id'] ?? '') ? 'selected' : '' ?>><?= $ruta_escape($ruta_operador['descripcion'] ?? '') ?></option><?php endforeach; ?></select></div>
    <div><label for="filtro-status">Estado</label><select id="filtro-status" name="status"><option value="">Todos</option><option value="activo" <?= ($ruta_filtros['status'] ?? '') === 'activo' ? 'selected' : '' ?>>Activo</option><option value="inactivo" <?= ($ruta_filtros['status'] ?? '') === 'inactivo' ? 'selected' : '' ?>>Inactivo</option></select></div>
    <div class="rutas-acciones"><button type="submit" class="ruta-boton ruta-primario">Filtrar</button><a class="ruta-boton ruta-secundario" href="<?= $ruta_escape($ruta_url('lista')) ?>">Limpiar</a></div>
</form>
<div class="rutas-tarjeta">
    <p role="status"><?= (int)($ruta_resultado['total'] ?? 0) ?> rutas encontradas.</p>
    <div class="rutas-tabla" role="region" aria-label="Lista de rutas" tabindex="0">
        <table><caption>Rutas de medición y operadores asignados</caption><thead><tr><th scope="col">Código</th><th scope="col">Descripción</th><th scope="col">Sector</th><th scope="col">Operador / checador</th><th scope="col">Estado</th><th scope="col">Acciones</th></tr></thead><tbody>
        <?php if (!$ruta_filas): ?><tr><td colspan="6">No hay rutas que coincidan con los filtros.</td></tr><?php endif; ?>
        <?php foreach ($ruta_filas as $ruta_fila): ?>
            <tr><td><?= $ruta_escape($ruta_fila['codigo'] ?? '') ?></td><td><?= $ruta_escape($ruta_fila['descripcion'] ?? '') ?></td><td><?= $ruta_escape($ruta_fila['sector'] ?? '') ?></td><td><?= $ruta_escape($ruta_fila['operador_descripcion'] ?? 'Sin asignar') ?></td><td><span class="ruta-estado <?= ($ruta_fila['status'] ?? '') === 'activo' ? 'ruta-activo' : '' ?>"><?= ($ruta_fila['status'] ?? '') === 'activo' ? 'Activo' : 'Inactivo' ?></span></td><td><div class="rutas-acciones">
                <?php foreach (['consulta'=>'Ver', 'modifica'=>'Editar', 'elimina'=>'Eliminar'] as $ruta_accion=>$ruta_etiqueta): ?><?php if ($ruta_permitida($ruta_accion)): ?><a class="ruta-boton ruta-secundario" href="<?= $ruta_escape($ruta_url($ruta_accion, (int)$ruta_fila['id'])) ?>" aria-label="<?= $ruta_escape($ruta_etiqueta.' ruta '.($ruta_fila['codigo'] ?? '')) ?>"><?= $ruta_etiqueta ?></a><?php endif; ?><?php endforeach; ?>
            </div></td></tr>
        <?php endforeach; ?>
        </tbody></table>
    </div>
    <nav class="rutas-paginacion" aria-label="Páginas de rutas">
        <?php if ($ruta_pagina > 1): ?><a class="ruta-boton ruta-secundario" rel="prev" href="<?= $ruta_escape($ruta_url('lista', null, array_merge($ruta_filtros, ['pagina'=>$ruta_pagina-1]))) ?>">Anterior</a><?php endif; ?>
        <span>Página <?= $ruta_pagina ?> de <?= $ruta_paginas ?></span>
        <?php if ($ruta_pagina < $ruta_paginas): ?><a class="ruta-boton ruta-secundario" rel="next" href="<?= $ruta_escape($ruta_url('lista', null, array_merge($ruta_filtros, ['pagina'=>$ruta_pagina+1]))) ?>">Siguiente</a><?php endif; ?>
    </nav>
</div>
</section>
