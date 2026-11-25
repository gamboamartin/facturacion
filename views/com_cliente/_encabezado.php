<?php
/** @var \gamboamartin\facturacion\controllers\controlador_com_cliente $controlador */
$cliente_titulos = [
    'lista' => ['Clientes', 'Tu directorio de clientes, claro y organizado.'],
    'alta' => ['Nuevo cliente', 'Carga su CIF o captura sus datos. Revisa la información antes de guardar.'],
    'modifica' => ['Editar cliente', 'Mantén al día sus datos de contacto, domicilio e información fiscal.'],
];
[$cliente_titulo, $cliente_descripcion] = $cliente_titulos[$controlador->accion] ?? ['Clientes', 'Administración de clientes.'];
?>
<link rel="stylesheet" href="css/com_cliente/clientes.css?v=<?php echo (int)filemtime(dirname(__DIR__, 2) . '/css/com_cliente/clientes.css'); ?>">
<header class="com-cliente-header">
    <div class="com-cliente-heading">
        <span class="com-cliente-icon" aria-hidden="true"><i class="bi bi-people"></i></span>
        <div><p class="com-cliente-eyebrow">DIRECTORIO COMERCIAL</p>
            <h1><?php echo htmlspecialchars($cliente_titulo, ENT_QUOTES, 'UTF-8'); ?></h1>
            <p><?php echo htmlspecialchars($cliente_descripcion, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
    </div>
    <nav class="com-cliente-actions" aria-label="Acciones de clientes">
        <?php foreach ($controlador->acciones_visibles_permitidas as $cliente_accion): ?>
            <?php echo $cliente_accion->boton; ?>
        <?php endforeach; ?>
    </nav>
</header>
