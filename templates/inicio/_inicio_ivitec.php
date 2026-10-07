<?php
/**
 * Inicio IVITEC (se activa con generales::$inicio_v2).
 *
 * Los accesos salen de $controlador->menu_permitido (ya filtrado por permisos del usuario),
 * con el mismo href que usa el menú de vendor. Un acceso sin permiso no se muestra.
 * Crear/Consultar factura solo aparecen si el módulo de facturación está permitido
 * y existen los links de fc_factura.
 *
 * @var base\controller\controlador_base $controlador
 * @var stdClass $links_menu
 */
$iv_e = function ($valor): string {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
};

$iv_menus = is_array($controlador->menu_permitido ?? null) ? $controlador->menu_permitido : [];

/* Busca el módulo permitido cuyo título (sin acentos, minúsculas) empieza con $prefijo */
$iv_modulo = function (string $prefijo) use ($iv_menus): ?array {
    foreach ($iv_menus as $menu) {
        $titulo = strtr(mb_strtolower((string)($menu['adm_menu_titulo'] ?? ''), 'UTF-8'),
            ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n']);
        if (strpos($titulo, $prefijo) === 0 && (int)($menu['adm_menu_id'] ?? 0) > 0) {
            return $menu;
        }
    }
    return null;
};

$iv_href = function (array $menu) use ($controlador): string {
    $id = (int)$menu['adm_menu_id'];
    return "index.php?seccion=adm_session&accion=inicio&session_id=$controlador->session_id&adm_menu_id=$id";
};

$iv_svg = [
    'factura'   => '<path d="M6 3h8l4 4v14H6zM14 3v5h4M9 12h6M9 16h6"/>',
    'clientes'  => '<circle cx="9" cy="7" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M17 4a3 3 0 0 1 0 6M21 21v-3a6 6 0 0 0-4-5"/>',
    'productos' => '<path d="m12 3 9 5v9l-9 5-9-5V8zM3 8l9 5 9-5M12 13v9M7 6l10 5"/>',
    'pagos'     => '<circle cx="12" cy="12" r="9"/><path d="M15 8h-4a2 2 0 0 0 0 4h2a2 2 0 0 1 0 4H9M12 6v12"/>',
    'cert'      => '<path d="M12 3 20 6v6c0 5-8 9-8 9s-8-4-8-9V6z"/>',
    'empresas'  => '<path d="M5 21V3h10v18M15 10h4v11M3 21h18M8 7h1m2 0h1M8 11h1m2 0h1M8 15h1m2 0h1"/>',
    'sat'       => '<path d="M6 3h8l4 4v14H6zM14 3v5h4M9 12h6M9 16h6"/>',
    'config'    => '<circle cx="12" cy="12" r="3"/><path d="m9 3-1 3-3 1-2 3 2 2-1 3 3 2 2-1 3 2 3-2 2 1 3-2-1-3 2-2-2-3-3-1-1-3z"/>',
    'inicio'    => '<path d="m3 10 9-8 9 8M5 8v13h14V8M9 21v-8h6v8"/>',
    'crear'     => '<path d="M6 3h8l4 4v14H6zM14 3v5h4M9 15h6M12 12v6"/>',
    'consultar' => '<path d="M6 3h8l4 4v5M6 21V3M6 21h5M14 3v5h4"/><circle cx="16" cy="16" r="4"/><path d="m19 19 3 3"/>',
    'flecha'    => '<path d="m9 5 7 7-7 7"/>',
];
$iv_icono = function (string $clave) use ($iv_svg): string {
    return '<svg class="iv-home__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" '
        . 'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($iv_svg[$clave] ?? '') . '</svg>';
};

/* Tarjetas: prefijo del título del módulo => [descripción, icono] */
$iv_definicion_tarjetas = [
    'factura'  => ['Emite y consulta tus comprobantes', 'factura'],
    'cliente'  => ['Administra tu catálogo de clientes', 'clientes'],
    'producto' => ['Consulta productos y servicios', 'productos'],
    'pago'     => ['Consulta tus movimientos', 'pagos'],
];
$iv_tarjetas = [];
foreach ($iv_definicion_tarjetas as $prefijo => $def) {
    $modulo = $iv_modulo($prefijo);
    if ($modulo !== null) {
        $iv_tarjetas[] = ['titulo' => $modulo['adm_menu_titulo'], 'texto' => $def[0], 'icono' => $def[1], 'href' => $iv_href($modulo)];
    }
}

/* Configuración: prefijo => icono */
$iv_definicion_config = ['certificado' => 'cert', 'empresa' => 'empresas', 'sat' => 'sat'];
$iv_config = [];
foreach ($iv_definicion_config as $prefijo => $icono) {
    $modulo = $iv_modulo($prefijo);
    if ($modulo !== null) {
        $iv_config[] = ['titulo' => $modulo['adm_menu_titulo'], 'icono' => $icono, 'href' => $iv_href($modulo)];
    }
}

/* Botones Crear / Consultar factura: solo si el módulo de facturación está permitido */
$iv_botones = [];
if ($iv_modulo('factura') !== null) {
    if (isset($links_menu->fc_factura->alta)) {
        $iv_botones[] = ['titulo' => 'Crear factura', 'icono' => 'crear', 'href' => $links_menu->fc_factura->alta, 'primario' => true];
    }
    if (isset($links_menu->fc_factura->lista)) {
        $iv_botones[] = ['titulo' => 'Consultar facturas', 'icono' => 'consultar', 'href' => $links_menu->fc_factura->lista, 'primario' => false];
    }
}
?>
<section class="iv-home" aria-labelledby="iv-home-title">
    <div class="iv-home__container">
        <div class="iv-home__breadcrumb">
            <?= $iv_icono('inicio') ?><span aria-hidden="true">›</span><span>Inicio</span>
        </div>

        <div class="iv-home__heading">
            <div>
                <h1 id="iv-home-title">Bienvenido a tu sistema de facturación</h1>
                <p>Selecciona un módulo para comenzar a trabajar.</p>
            </div>
            <?php if (count($iv_botones) > 0) { ?>
                <div class="iv-home__actions">
                    <?php foreach ($iv_botones as $boton) { ?>
                        <a class="iv-home__button<?= $boton['primario'] ? ' iv-home__button--primary' : '' ?>" href="<?= $iv_e($boton['href']) ?>">
                            <?= $iv_icono($boton['icono']) ?><span><?= $iv_e($boton['titulo']) ?></span>
                        </a>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>

        <h2>Accesos rápidos</h2>
        <?php if (count($iv_tarjetas) > 0) { ?>
            <div class="iv-home__grid">
                <?php foreach ($iv_tarjetas as $tarjeta) { ?>
                    <a class="iv-home__card" href="<?= $iv_e($tarjeta['href']) ?>">
                        <span class="iv-home__tile"><?= $iv_icono($tarjeta['icono']) ?></span>
                        <span class="iv-home__copy">
                            <strong><?= $iv_e($tarjeta['titulo']) ?></strong>
                            <span><?= $iv_e($tarjeta['texto']) ?></span>
                        </span>
                        <span class="iv-home__arrow"><?= $iv_icono('flecha') ?></span>
                    </a>
                <?php } ?>
            </div>
        <?php } ?>
        <?php if (count($iv_tarjetas) === 0) { ?>
            <p class="iv-home__empty">No hay accesos rápidos disponibles para tu usuario. Usa el menú superior para navegar.</p>
        <?php } ?>

        <?php if (count($iv_config) > 0) { ?>
            <div class="iv-home__config">
                <span class="iv-home__tile"><?= $iv_icono('config') ?></span>
                <div class="iv-home__copy">
                    <strong>Configuración del sistema</strong>
                    <span>Administra la configuración básica de tu sistema.</span>
                </div>
                <nav class="iv-home__config-links" aria-label="Configuración del sistema">
                    <?php foreach ($iv_config as $item) { ?>
                        <a href="<?= $iv_e($item['href']) ?>"><?= $iv_icono($item['icono']) ?><span><?= $iv_e($item['titulo']) ?></span></a>
                    <?php } ?>
                </nav>
            </div>
        <?php } ?>
    </div>
</section>