<?php
/** @var stdClass $data */
/** @var base\controller\ $controlador */
use config\views;
use gamboamartin\template_1\nav;

$nav_html = (new nav())->lis_menu_principal(
    links: $links_menu, secciones: $controlador->secciones_permitidas);
if(\gamboamartin\errores\errores::$error){
    $error = (new \gamboamartin\errores\errores())->error(mensaje: 'Error al generar nav', data: $nav_html);
    print_r($error);
    exit;
}

$tiene_secciones = trim($nav_html) !== '';
if($tiene_secciones){
    ?>
    <aside class="clientes-sidebar">
        <div class="clientes-sidebar-title">
            <span class="sidebar-title-icon"><i class="bi bi-list"></i></span>
            Menú
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