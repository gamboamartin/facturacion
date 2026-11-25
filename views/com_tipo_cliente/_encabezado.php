<?php
/** @var \gamboamartin\facturacion\controllers\controlador_com_tipo_cliente $controlador */
$tipo_titulos = [
    'lista' => ['Tipos de cliente', 'Organiza tu catálogo y encuentra cada clasificación fácilmente.'],
    'alta' => ['Nuevo tipo de cliente', 'Define un nombre claro para clasificar a tus clientes.'],
    'modifica' => ['Editar tipo de cliente', 'Mantén organizada la clasificación de tus clientes.'],
];
[$tipo_titulo, $tipo_descripcion] = $tipo_titulos[$controlador->accion] ?? ['Tipos de cliente', 'Administración del catálogo de clientes.'];
?>
<link rel="stylesheet" href="css/com_cliente/clientes.css?v=<?php echo (int)filemtime(dirname(__DIR__, 2) . '/css/com_cliente/clientes.css'); ?>">
<link rel="stylesheet" href="css/com_tipo_cliente/tipos.css?v=<?php echo (int)filemtime(dirname(__DIR__, 2) . '/css/com_tipo_cliente/tipos.css'); ?>">
<header class="com-cliente-header">
    <div class="com-cliente-heading">
        <span class="com-cliente-icon" aria-hidden="true"><i class="bi bi-tags"></i></span>
        <div><p class="com-cliente-eyebrow">CATÁLOGO DE CLIENTES</p>
            <h1><?php echo htmlspecialchars($tipo_titulo, ENT_QUOTES, 'UTF-8'); ?></h1>
            <p><?php echo htmlspecialchars($tipo_descripcion, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
    </div>
    <nav class="com-cliente-actions com-tipo-actions" aria-label="Acciones de tipos de cliente">
        <?php foreach ($controlador->acciones_visibles_permitidas as $tipo_accion): ?>
            <?php if (($tipo_accion->adm_accion_descripcion ?? '') !== 'get_data') { echo $tipo_accion->boton; } ?>
        <?php endforeach; ?>
    </nav>
</header>
