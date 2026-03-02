<?php
/** @var stdClass $links_menu viene de links  */
/** @var base\controller\controler $controlador viene de links  */
use config\views;
use gamboamartin\template_1\nav;

?>

<div class="pull-left menu" style="padding: 0!important;">
    <?php include (new views())->template_path('nav/_menu_responsive.php');?>



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
