<?php
/**
 * Menú superior IVITEC (se activa con generales::$menu_superior_v2).
 *
 * Misma fuente de datos y mismos enlaces que el header original:
 *  - módulos: $controlador->menu_permitido (ya filtrado por permisos)
 *  - href:    idéntico a controlador_base::href_menu() de vendor
 *  - Inicio / Salir: mismos links que nav/_redes_sociales.php
 *
 * @var base\controller\controlador_base $controlador
 * @var stdClass $links_menu
 */
require_once __DIR__ . '/../logo_service.php';
use gamboamartin\system\links_menu;

$iv_menus = is_array($controlador->menu_permitido ?? null) ? $controlador->menu_permitido : [];

/* Cuántos módulos van en la fila principal; el resto va en la fila lavanda. */
$iv_limite_primarios = 12;

/* Icono por prefijo del título (sin acentos, minúsculas). Lo que no coincida usa 'grid'. */
$iv_mapa_iconos = [
    'seguridad' => 'shield', 'certificado' => 'shield',
    'empresa' => 'building',
    'cliente' => 'users', 'nomina' => 'users',
    'region' => 'pin',
    'divisa' => 'coin', 'pago' => 'coin',
    'producto' => 'box',
    'config' => 'settings', 'proceso' => 'process',
    'notificacion' => 'bell',
    'egreso' => 'card', 'banco' => 'card',
    'etapa' => 'layers',
    'reporte' => 'chart',
    'sat' => 'document', 'factura' => 'document', 'nota' => 'document', 'documento' => 'document',
];

$iv_svg = [
    'shield'   => '<path d="M12 3 20 6v6c0 5-8 9-8 9s-8-4-8-9V6z"/>',
    'building' => '<path d="M5 21V3h10v18M15 10h4v11M3 21h18M8 7h1m2 0h1M8 11h1m2 0h1M8 15h1m2 0h1M9 21v-3h3v3"/>',
    'document' => '<path d="M6 3h8l4 4v14H6zM14 3v5h4M9 12h6M9 16h6"/>',
    'users'    => '<circle cx="9" cy="7" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M17 4a3 3 0 0 1 0 6M21 21v-3a6 6 0 0 0-4-5"/>',
    'pin'      => '<path d="M19 10c0 5-7 11-7 11S5 15 5 10a7 7 0 0 1 14 0z"/><circle cx="12" cy="10" r="2"/>',
    'coin'     => '<circle cx="12" cy="12" r="9"/><path d="M15 8h-4a2 2 0 0 0 0 4h2a2 2 0 0 1 0 4H9M12 6v12"/>',
    'box'      => '<path d="m12 3 9 5v9l-9 5-9-5V8zM3 8l9 5 9-5M12 13v9M7 6l10 5"/>',
    'settings' => '<circle cx="12" cy="12" r="3"/><path d="m9 3-1 3-3 1-2 3 2 2-1 3 3 2 2-1 3 2 3-2 2 1 3-2-1-3 2-2-2-3-3-1-1-3z"/>',
    'process'  => '<path d="M20 11a8 8 0 0 0-14-4M4 4v4h4M4 13a8 8 0 0 0 14 4M20 20v-4h-4"/>',
    'bell'     =>'<path d="M5 17h14l-2-3V9a5 5 0 0 0-10 0v5zM10 21h4M12 2v2"/>',
    'card'     => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h4"/>',
    'layers'   => '<path d="m12 3 10 5-10 5L2 8zM2 12l10 5 10-5M2 16l10 5 10-5"/>',
    'chart'    => '<path d="M4 21V12M9 21V6M14 21V9M19 21V3"/>',
    'home'     => '<path d="m3 10 9-8 9 8M5 8v13h14V8M9 21v-8h6v8"/>',
    'logout'   => '<path d="M10 3H4v18h6M9 12h12m-4-4 4 4-4 4"/>',
    'grid'     => '<rect x="4" y="4" width="6" height="6" rx="1"/><rect x="14" y="4" width="6" height="6" rx="1"/><rect x="4" y="14" width="6" height="6" rx="1"/><rect x="14" y="14" width="6" height="6" rx="1"/>',
];

$iv_e = function ($valor): string {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
};

$iv_icono = function (string $nombre) use ($iv_svg): string {
    $path = $iv_svg[$nombre] ?? $iv_svg['grid'];
    return '<svg class="iv-nav__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" '
        . 'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
};

$iv_nombre_icono = function (string $titulo) use ($iv_mapa_iconos): string {
    $t = strtr(mb_strtolower($titulo, 'UTF-8'), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n']);
    foreach ($iv_mapa_iconos as $prefijo => $icono) {
        if (strpos($t, $prefijo) === 0) {
            return $icono;
        }
    }
    return 'grid';
};

$iv_link_menu = function (array $menu) use ($controlador, $iv_e, $iv_icono, $iv_nombre_icono): string {
    $id = (int)($menu['adm_menu_id'] ?? 0);
    $titulo = (string)($menu['adm_menu_titulo'] ?? '');
    if ($id <= 0 || $titulo === '') {
        return '';
    }
    /* Mismo href que controlador_base::href_menu() */
    $href = "index.php?seccion=adm_session&accion=inicio&session_id=$controlador->session_id&adm_menu_id=$id";
    $activo = (int)($controlador->adm_menu_id ?? 0) === $id;
    $clase = 'iv-nav__link' . ($activo ? ' is-active' : '');
    $aria = $activo ? ' aria-current="page"' : '';
    return '<a class="' . $clase . '" href="' . $iv_e($href) . '"' . $aria . '>'
        . $iv_icono($iv_nombre_icono($titulo)) . '<span>' . $iv_e($titulo) . '</span></a>';
};

$iv_primarios = array_slice($iv_menus, 0, $iv_limite_primarios);
$iv_secundarios = array_slice($iv_menus, $iv_limite_primarios);

$iv_logo = $controlador->logo_empresa_url ?? null;
if (empty($iv_logo)) {
    $iv_logo = logo_empresa_url_framework($controlador->link);
}
$iv_href_inicio = $links_menu->adm_session->inicio;
$iv_sesion_activa = isset($_SESSION['activa']) && (int)$_SESSION['activa'] === 1;
?>
<div class="iv-nav" data-iv-nav>
    <div class="iv-nav__top">
        <a class="iv-nav__brand" href="<?= $iv_e($iv_href_inicio) ?>" aria-label="Inicio">
            <?php if (!empty($iv_logo)) { ?>
                <img src="<?= $iv_e($iv_logo) ?>" alt="Logo empresa">
            <?php } ?>
        </a>

        <button class="iv-nav__toggle" type="button" aria-label="Menú" aria-controls="iv-navigation" aria-expanded="true">
            <svg class="iv-nav__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
            <span>Menú</span>
        </button>

        <div class="iv-nav__navigation" id="iv-navigation">
            <nav class="iv-nav__primary" aria-label="Módulos principales">
                <?php foreach ($iv_primarios as $iv_menu) { echo $iv_link_menu($iv_menu); } ?>
            </nav>
            <?php if (count($iv_secundarios) > 0) { ?>
                <nav class="iv-nav__secondary" aria-label="Módulos adicionales">
                    <?php foreach ($iv_secundarios as $iv_menu) { echo $iv_link_menu($iv_menu); } ?>
                </nav>
            <?php } ?>
        </div>

        <?php if ($iv_sesion_activa) { ?>
            <?php $iv_href_salir = (new links_menu(link: $controlador->link, registro_id: $controlador->registro_id))->links->adm_session->logout; ?>
            <nav class="iv-nav__actions" aria-label="Inicio y sesión">
                <a class="iv-nav__link" href="<?= $iv_e($iv_href_inicio) ?>"><?= $iv_icono('home') ?><span>Inicio</span></a>
                <a class="iv-nav__link" href="<?= $iv_e($iv_href_salir) ?>"><?= $iv_icono('logout') ?><span>Salir</span></a>
            </nav>
        <?php } ?>
    </div>
</div>
<script>
(function () {
    var header = document.querySelector('[data-iv-nav]');
    if (!header) { return; }
    var button = header.querySelector('.iv-nav__toggle');
    var mobile = window.matchMedia('(max-width: 900px)');
    var storageKey = 'iv_nav_escritorio_abierto';

    /* Móvil y escritorio: recogido por defecto; .is-open lo despliega. */
    function setOpen(open) {
        header.classList.toggle('is-open', open);
        button.setAttribute('aria-expanded', String(open));
    }
    function readOpen() {
        try { return window.localStorage.getItem(storageKey) === '1'; } catch (e) { return false; }
    }
    function saveOpen(open) {
        try { window.localStorage.setItem(storageKey, open ? '1' : '0'); } catch (e) {}
    }
    function sync() {
        setOpen(mobile.matches ? false : readOpen());
    }
    button.addEventListener('click', function () {
        var open = !header.classList.contains('is-open');
        setOpen(open);
        if (!mobile.matches) { saveOpen(open); }
    });
    header.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && mobile.matches && header.classList.contains('is-open')) {
            setOpen(false);
            button.focus();
        }
    });
    sync();
    if (mobile.addEventListener) {
        mobile.addEventListener('change', sync);
        return;
    }
    mobile.addListener(sync);
})();
</script>