<?php
/** @var \gamboamartin\facturacion\controllers\controlador_aguaban_ruta $controlador */
$ruta_config = $controlador->ruta_config;
$ruta_texto = static fn(mixed $valor): string => is_scalar($valor) ? (string)$valor : '';
$ruta_escape = static fn(mixed $valor): string => htmlspecialchars($ruta_texto($valor), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$ruta_permitida = static fn(string $accion): bool => ($ruta_config['permisos'][$accion] ?? false) === true;
$ruta_url = static fn(string $accion, ?int $id = null, array $extra = []): string => $controlador->url_ruta($accion, $id, $extra);
$ruta_registro = is_array($ruta_config['registro'] ?? null) ? $ruta_config['registro'] : [];
$ruta_operadores = is_array($ruta_config['operadores'] ?? null) ? $ruta_config['operadores'] : [];
$ruta_csrf = $ruta_escape($ruta_config['csrf'] ?? '');
?>
<link rel="stylesheet" href="css/aguaban_ruta/aguaban_ruta.css?v=1">
<section id="aguaban-rutas" aria-labelledby="rutas-titulo">
    <header class="rutas-encabezado">
        <div><p class="rutas-contexto">Aguaban</p><h1 id="rutas-titulo"><?= $ruta_escape($ruta_titulo ?? 'Rutas de medición') ?></h1></div>
        <nav class="rutas-acciones" aria-label="Acciones de rutas">
            <?php if ($ruta_permitida('lista')): ?><a class="ruta-boton ruta-secundario" href="<?= $ruta_escape($ruta_url('lista')) ?>">Lista de rutas</a><?php endif; ?>
            <?php if ($ruta_permitida('alta')): ?><a class="ruta-boton ruta-primario" href="<?= $ruta_escape($ruta_url('alta')) ?>">Nueva ruta</a><?php endif; ?>
        </nav>
    </header>
    <?php if ($ruta_texto($ruta_config['error'] ?? '') !== ''): ?><p class="rutas-aviso rutas-error" role="alert"><?= $ruta_escape($ruta_config['error']) ?></p><?php endif; ?>
    <?php if ($ruta_texto($ruta_config['exito'] ?? '') !== ''): ?><p class="rutas-aviso rutas-exito" role="status"><?= $ruta_escape($ruta_config['exito']) ?></p><?php endif; ?>
