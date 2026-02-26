<?php
/** @var stdClass $links_menu viene de links  */
/** @var base\controller\controler $controlador viene de links  */
use config\views;
use gamboamartin\template_1\nav;

?>

<div class="pull-left menu" style="padding: 0!important;">
    <?php include (new views())->template_path('nav/_menu_responsive.php');?>

   <?php
    $logo_url = null;

    // 1) Igual que en _redes_sociales.php (no rompe si no existe la prop)
    $logo_url = $controlador->logo_empresa_url ?? null;

    // 2) Fallback si en este contexto no viene en el controlador
    if (empty($logo_url) && !empty($GLOBALS['logo_empresa_url'])) {
        $logo_url = $GLOBALS['logo_empresa_url'];
    }

    // 3) Fallback si lo pasan como variable suelta
    if (empty($logo_url) && !empty($logo_empresa_url)) {
        $logo_url = $logo_empresa_url;
    }
    ?>
    <nav class="navbar text-color-primary">
        <?php include (new views())->template_path('nav/_sombra_menu.php'); ?>

        <!-- Links -->
        <div class="collapse navbar-collapse" id="main-menu">
            <ul class="nav navbar-nav clearfix">
                <?php $nav =  (new nav())->lis_menu_principal(links: $links_menu, secciones: $controlador->secciones_permitidas); ?>
                <?php
                if(\gamboamartin\errores\errores::$error){
                    $error = (new \gamboamartin\errores\errores())->error(mensaje: 'Error al generar nav', data: $nav);
                    print_r($error);
                    exit;
                }
                ?>
                <?php echo $nav; ?>
            </ul>
        </div>
    </nav>

</div>
