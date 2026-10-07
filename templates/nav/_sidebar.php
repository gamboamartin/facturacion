<?php
/** @var stdClass $data */
/** @var base\controller\ $controlador */
use gamboamartin\template_1\nav;

$nav_html = (new nav())->lis_menu_principal(
    links: $links_menu, secciones: $controlador->secciones_permitidas);
if(\gamboamartin\errores\errores::$error){
    $error = (new \gamboamartin\errores\errores())->error(mensaje: 'Error al generar nav', data: $nav_html);
    print_r($error);
    exit;
}

$titulo_menu = 'Menú';
foreach ($controlador->menu_permitido as $menu_item) {
    if (!isset($menu_item['adm_menu_id'], $menu_item['adm_menu_titulo'])) {
        continue;
    }
    if ((int)$menu_item['adm_menu_id'] !== (int)$controlador->adm_menu_id) {
        continue;
    }
    $titulo = trim((string)$menu_item['adm_menu_titulo']);
    if ($titulo !== '') {
        $titulo_menu = $titulo;
    }
    break;
}

$tiene_secciones = trim($nav_html) !== '';
if($tiene_secciones){
    ?>
    <aside class="clientes-sidebar">
        <div class="clientes-sidebar-title">
            <span class="sidebar-title-icon"><i class="bi-grid-fill"></i></span>
            <?php echo htmlspecialchars($titulo_menu, ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <nav class="clientes-sidebar-nav">
            <?php echo $nav_html; ?>
        </nav>
    </aside>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var actual = window.location.href;
        document.querySelectorAll('.clientes-sidebar-nav .nav-link').forEach(function (a) {
            if (a.href === actual) {
                a.classList.add('active');
                a.closest('li').classList.add('active');
            }
        });
    });
    </script>
    <?php
}