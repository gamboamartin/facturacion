<?php
/**
 * @var base\controller\controlador_base $controlador viene de links
 * @var links_menu $links_menu
 *
 * */

use gamboamartin\system\links_menu;

$seccion = $_GET['seccion'] ?? '';
$accion  = $_GET['accion'] ?? '';
$es_login = ($seccion === 'adm_session' && in_array($accion, ['login'], true));
?>
<?php error_log('[LOCAL] cargue _redes_sociales.php: '.__FILE__); ?>


<?php if (!$es_login): ?>
<link rel="stylesheet" href="/facturacion/css/overrides.css?v=1">
<div class="top-bar color-primary">
    <div class="clearfix">
        <div class="pull-right" style="display: flex; justify-content: space-between; width: 100%; padding: 0 35px;">

        <!-- aca comienza lo nuevo -->
        <?php $logo_url = $controlador->logo_empresa_url ?? null; ?>

        <?php if (!empty($logo_url)): ?>

            <?php
            // Si hay sesión, manda al inicio real (igual que tu botón "Inicio")
            // Si NO hay sesión, manda al login (evitas el rebote raro)
            $href_logo = (isset($_SESSION['activa']) && (int)$_SESSION['activa'] === 1)
                ? $links_menu->adm_session->inicio
                : '/facturacion/index.php?seccion=adm_session&accion=login';
            ?>

            <a href="<?= htmlspecialchars($href_logo, ENT_QUOTES, 'UTF-8') ?>"
               style="display:inline-flex;align-items:center;margin-right:12px;">
                <img src="<?= htmlspecialchars($logo_url, ENT_QUOTES, 'UTF-8') ?>"
                     alt="Logo Empresa"
                     style="height:60px;width:auto;display:block;">
            </a>

        <?php endif; ?>
        <!-- aca termina -->

            <ul class="social-nav clearfix">
                <?php echo $controlador->menu_header; ?>
            </ul>
            <?php
            if(isset($_SESSION['activa']) && (int)$_SESSION['activa'] === 1){ ?>
                <div class="pull-right col-md-12" style="text-align: right;">
                    <a role="button" class="btn btn-info cerrar-session" href="<?php echo $links_menu->adm_session->inicio ?>"> Inicio </a>
                    <a role="button" class="btn btn-danger cerrar-session" href="<?php echo (new links_menu(link: $controlador->link,registro_id: $controlador->registro_id))->links->adm_session->logout ;?>"> Salir </a>
                </div>
            <?php }
            ?>
        </div>

    </div>
</div>
<?php endif; ?>