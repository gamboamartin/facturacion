<?php
/** @var stdClass $data */
/** @var base\controller\controler $controlador */
use gamboamartin\template_1\nav;

require_once __DIR__ . '/../logo_service.php';

$nav_html = (new nav())->lis_menu_principal(
    links: $links_menu, secciones: $controlador->secciones_permitidas);
if (\gamboamartin\errores\errores::$error) {
    $error = (new \gamboamartin\errores\errores())->error(mensaje: 'Error al generar nav', data: $nav_html);
    print_r($error);
    exit;
}

$titulo_menu = 'Menú';
foreach ($controlador->menu_permitido as $menu_item) {
    if (!isset($menu_item['adm_menu_id'], $menu_item['adm_menu_titulo']) ||
        (int)$menu_item['adm_menu_id'] !== (int)$controlador->adm_menu_id) {
        continue;
    }
    $titulo = trim((string)$menu_item['adm_menu_titulo']);
    if ($titulo !== '') {
        $titulo_menu = $titulo;
    }
    break;
}

if (trim($nav_html) === '') {
    return;
}
$sidebar_logo = logo_empresa_url_framework($controlador->link);
$sidebar_total = count($controlador->secciones_permitidas);
$sidebar_escape = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$sidebar_root = dirname(__DIR__, 2);
?>
<link rel="stylesheet" href="css/nav/sidebar.css?v=<?= (int)filemtime($sidebar_root . '/css/nav/sidebar.css') ?>">
<aside class="clientes-sidebar iv-sidebar" data-sidebar data-current-section="<?= $sidebar_escape((string)$controlador->seccion) ?>" aria-label="Menú de <?= $sidebar_escape($titulo_menu) ?>">
    <?php if ($sidebar_logo !== null && $sidebar_logo !== ''): ?>
        <div class="iv-sidebar__brand">
            <img src="<?= $sidebar_escape($sidebar_logo) ?>" alt="Logo de la empresa" width="180" height="58">
        </div>
    <?php endif; ?>
    <div class="iv-sidebar__header">
        <span class="iv-sidebar__module-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/></svg></span>
        <div class="iv-sidebar__heading"><p>Navegación</p><h2><?= $sidebar_escape($titulo_menu) ?></h2></div>
        <button type="button" class="iv-sidebar__mobile-toggle" aria-controls="iv-sidebar-panel" aria-expanded="true" aria-label="Mostrar u ocultar opciones de <?= $sidebar_escape($titulo_menu) ?>">
            <span data-sidebar-toggle-label>Opciones</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
        </button>
    </div>
    <div class="iv-sidebar__panel" id="iv-sidebar-panel">
        <?php if ($sidebar_total > 6): ?>
            <div class="iv-sidebar__search" data-sidebar-search-wrapper>
                <label for="iv-sidebar-search">Buscar en el menú</label>
                <div class="iv-sidebar__search-field">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 4.5 4.5"/></svg>
                    <input type="search" id="iv-sidebar-search" placeholder="Nombre de opción…" autocomplete="off" aria-controls="iv-sidebar-navigation">
                    <button type="button" class="iv-sidebar__clear" aria-label="Limpiar búsqueda del menú" hidden>×</button>
                </div>
            </div>
        <?php endif; ?>
        <div class="iv-sidebar__section-label"><span>Opciones disponibles</span><span data-sidebar-count aria-live="polite"><?= $sidebar_total ?></span></div>
        <nav class="clientes-sidebar-nav" id="iv-sidebar-navigation" aria-label="Opciones de <?= $sidebar_escape($titulo_menu) ?>">
            <ul><?= $nav_html ?></ul>
        </nav>
        <p class="iv-sidebar__empty" role="status" hidden>No encontramos opciones. Prueba otra búsqueda.</p>
    </div>
</aside>
<script src="js/nav/sidebar.js?v=<?= (int)filemtime($sidebar_root . '/js/nav/sidebar.js') ?>" defer></script>
