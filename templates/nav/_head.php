<?php
/** @var stdClass $data Obtenido de index de la funcion data para la definicion del men  */
use config\views;
use config\generales;

$views_cfg = new views();
$path_base_template = $views_cfg->ruta_templates;

$tipo_menu = 'horizontal';
if(property_exists(generales::class, 'tipo_menu')){
    $tipo_menu = generales::$tipo_menu;
}
?>
<div class="top-box" data-toggle="sticky-onscroll">
    <div class="container">

        <?php include $views_cfg->template_path('nav/_redes_sociales.php'); ?>

        <section class="header-inner">
            <div style="display: flex; flex-direction: column; justify-content: center; padding: 0 35px;">
                <?php if($data->menu && $tipo_menu === 'horizontal'){ ?>
                    <?php include $views_cfg->template_path('nav/menu.php'); ?>
                <?php } ?>
            </div>
        </section><!-- /.menu-->
    </div>
</div>
<div class="top-box-mask"></div>