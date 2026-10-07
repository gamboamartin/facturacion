<?php
/**
 * Vista de inicio (adm_session / inicio).
 * Con generales::$inicio_v2 = true muestra el diseño nuevo; si no, el original de vendor.
 *
 * @var gamboamartin\controllers\ $controlador
 * @var stdClass $links_menu
 */
use config\generales;
use config\views;
use gamboamartin\system\links_menu;

$inicio_nuevo = false;
if (property_exists(generales::class, 'inicio_v2')) {
    $inicio_nuevo = (bool)generales::$inicio_v2;
}
?>
<?php if ($inicio_nuevo) { ?>
    <?php include (new views())->template_path('inicio/_inicio_ivitec.php'); ?>
<?php } ?>
<?php if (!$inicio_nuevo) { ?>
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="top-title">
                <ul class="breadcrumb">
                    <?php include (new views())->ruta_templates."breadcrumb/adm_session/inicio.php"; ?>

                </ul>
                <h1 class="h-side-title page-title page-title-big text-color-primary">
                    Bienvenido a <?php echo (new views())->titulo_sistema ;?>
                </h1>
            </div> <!-- /. content-header -->
            <!-- /. widget-AVAILABLE PACKAGES -->
        </div><!-- /.center-content -->
    </div>
</div>
<?php } ?>