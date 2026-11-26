<?php
use config\views;
$catalogo_es_tipo = $controlador->seccion === 'com_tipo_producto';
$catalogo_id = $catalogo_es_tipo ? 'tipos-producto' : 'productos';
$catalogo_tabla_id = $catalogo_es_tipo ? 'tipos-producto-tabla' : 'catalogo-productos-tabla';
?>
<section class="com-cliente com-cliente-lista com-producto-catalogo" data-producto-catalogo="<?= $catalogo_id ?>" data-producto-seccion="<?= $catalogo_es_tipo ? 'com_tipo_producto' : 'com_producto' ?>" aria-label="<?= $catalogo_es_tipo ? 'Administración de tipos de producto' : 'Administración de productos' ?>">
    <?php include __DIR__ . '/_encabezado.php'; ?>
    <?php include (new views())->ruta_templates . 'mensajes.php'; ?>
    <div class="com-cliente-card com-cliente-directory">
        <div class="com-cliente-directory-heading"><div><h2><?= $catalogo_es_tipo ? 'Tipos de tu catálogo' : 'Tu catálogo de productos' ?></h2><p><?= $catalogo_es_tipo ? 'Clasificaciones disponibles al registrar o editar productos.' : 'Encuentra un producto por código, descripción o clave SAT.' ?></p></div><span class="com-cliente-count" id="<?= $catalogo_id ?>-total" role="status" aria-live="polite">Consultando catálogo…</span></div>
        <div class="com-cliente-search-bar"><div class="com-cliente-search"><label for="<?= $catalogo_id ?>-busqueda"><?= $catalogo_es_tipo ? 'Buscar tipo de producto' : 'Buscar producto' ?></label><div><i class="bi bi-search" aria-hidden="true"></i><input type="search" id="<?= $catalogo_id ?>-busqueda" placeholder="<?= $catalogo_es_tipo ? 'Escribe un nombre o identificador…' : 'Escribe un código, nombre o clave SAT…' ?>" autocomplete="off" aria-controls="<?= $catalogo_tabla_id ?>"></div></div><button type="button" class="com-cliente-button com-cliente-button-secondary" id="<?= $catalogo_id ?>-limpiar" disabled>Limpiar búsqueda</button></div>
        <div class="com-cliente-table-wrap"><table id="<?= $catalogo_tabla_id ?>" class="datatable table table-striped" aria-label="<?= $catalogo_es_tipo ? 'Tipos de producto registrados' : 'Productos registrados' ?>"></table></div>
    </div>
    <p class="com-cliente-footnote"><i class="bi bi-shield-check" aria-hidden="true"></i> Las acciones disponibles corresponden a los permisos de tu perfil.</p>
</section>
