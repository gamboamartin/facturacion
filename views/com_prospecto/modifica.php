<?php /** @var gamboamartin\comercial\controllers\controlador_com_prospecto $controlador controlador en ejecucion */ ?>
<?php use config\views; ?>
<main class="main section-color-primary">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <?php include (new views())->ruta_templates . "head/title.php"; ?>

                <?php include (new views())->ruta_templates . "mensajes.php"; ?>

                <div class="widget  widget-box box-container form-main widget-form-cart" id="form">
                    <form method="post" action="<?php echo $controlador->link_alta_etapa; ?>" class="form-additional">
                        <?php include (new views())->ruta_templates . "head/subtitulo.php"; ?>

                        <?php echo $controlador->inputs->com_tipo_prospecto_id; ?>
                        <?php echo $controlador->inputs->com_agente_id; ?>
                        <?php echo $controlador->inputs->nombre; ?>
                        <?php echo $controlador->inputs->apellido_paterno; ?>
                        <?php echo $controlador->inputs->apellido_materno; ?>
                        <?php echo $controlador->inputs->telefono; ?>
                        <?php echo $controlador->inputs->correo; ?>
                        <?php echo $controlador->inputs->razon_social; ?>

                        <?php include (new views())->ruta_templates . 'botons/submit/modifica_bd.php'; ?>
                    </form>

                </div>

            </div>
        </div>

    </div>

</main>


