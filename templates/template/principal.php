<?php /** @var stdClass $data */
/** @var base\controller\ $controlador */
use config\views;
use gamboamartin\system\links_menu;


$path_base_template = (new views())->ruta_templates;
$links_menu = (new links_menu(link:$controlador->link, registro_id: -1))->links;



?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title><?php echo (new views())->titulo_sistema; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta http-equiv="x-ua-compatible" content="ie=edge" />
   <?php include (new \config\views())->template_path('template/css.php'); ?>
    <?php echo $data->css_custom->css; ?>

</head>

<body class="">
<div id="fb-root"></div>
<div class="container container-wrapper">
    <header class="header">
       <?php include (new \config\views())->template_path('nav/_head.php'); ?>
    </header><!-- /.header-->

    <main class="main section-color-primary">
        <div style="display: flex; flex-direction: column; justify-content: center; padding: 15px 35px;">
            <?php  include($data->include_action); ?>
        </div>
    </main><!-- /.main-part-->

    <footer class="footer">
        <?php include $path_base_template.'footer/_footer.php' ?>
    </footer>
    <a class="btn btn-scoll-up color-secondary" id="btn-scroll-up"></a>

    <?php include $path_base_template.'java.php'; ?>
    <?php
    if($data->js_view_aplica_include){
        include $data->js_view;
    }
    else{
        echo $data->js_view;
    }
    ?>
    <?php if (isset($controlador->datatables)):?>
        <?php foreach ($controlador->datatables as $datatable) {
            $objeto = json_encode($datatable);
            print_r("<script> datatable($objeto.identificador, $objeto.columns, $objeto.columnDefs, $objeto.data,$objeto.in) </script>");
        } ?>
    <?php endif;?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const formulario = document.querySelector('.form-additional');

            if (formulario) {
                const boton = formulario.querySelector('button[type="submit"]');

                if (boton) {
                    formulario.addEventListener('submit', function (e) {
                        boton.disabled = true;
                        boton.textContent = 'Procesando...';
                        boton.classList.add('btn-disabled');
                    });
                }
            }
        });
    </script>
</body>
</html>
