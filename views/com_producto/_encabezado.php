<?php
/** Encabezado común a los dos catálogos de productos. */
$catalogo_es_tipo = $controlador->seccion === 'com_tipo_producto';
$catalogo_nombre = $catalogo_es_tipo ? 'Tipos de producto' : 'Productos';
$catalogo_titulos = $catalogo_es_tipo
    ? ['lista' => 'Tipos de producto', 'alta' => 'Nuevo tipo de producto', 'modifica' => 'Editar tipo de producto']
    : ['lista' => 'Productos', 'alta' => 'Nuevo producto', 'modifica' => 'Editar producto'];
$catalogo_subtitulo = $catalogo_es_tipo
    ? 'Clasifica tus productos con nombres claros y fáciles de identificar.'
    : 'Administra la identificación, el precio y los datos fiscales de tus productos.';
?>
<link rel="stylesheet" href="css/com_cliente/clientes.css?v=<?= (int)filemtime(dirname(__DIR__, 2) . '/css/com_cliente/clientes.css') ?>">
<link rel="stylesheet" href="css/com_producto/catalogos.css?v=<?= (int)filemtime(dirname(__DIR__, 2) . '/css/com_producto/catalogos.css') ?>">
<header class="com-cliente-header">
    <div class="com-cliente-heading">
        <span class="com-cliente-icon" aria-hidden="true"><i class="bi <?= $catalogo_es_tipo ? 'bi-tags' : 'bi-box-seam' ?>"></i></span>
        <div><p class="com-cliente-eyebrow">CATÁLOGO DE PRODUCTOS</p>
            <h1><?= htmlspecialchars($catalogo_titulos[$controlador->accion] ?? $catalogo_nombre, ENT_QUOTES, 'UTF-8') ?></h1>
            <p><?= htmlspecialchars($catalogo_subtitulo, ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    </div>
    <nav class="com-cliente-actions com-producto-actions" aria-label="Acciones del catálogo">
        <?php foreach ($controlador->acciones_visibles_permitidas as $catalogo_accion): ?>
            <?php if (($catalogo_accion->adm_accion_descripcion ?? '') !== 'get_data') { echo $catalogo_accion->boton; } ?>
        <?php endforeach; ?>
    </nav>
</header>
