<?php /** @var  \gamboamartin\facturacion\controllers\controlador_com_agente $controlador controlador en ejecucion */ ?>
<?php use config\views; ?>

<main class="main section-color-primary">
    <div class="container">

        <div class="row">

            <div class="col-lg-12">
                <?php include (new views())->ruta_templates."head/title.php"; ?>
                <?php include (new views())->ruta_templates."mensajes.php"; ?>

                <div class="widget  widget-box box-container form-main widget-form-cart" id="form" >
                    <?php include (new views())->ruta_templates . "head/subtitulo.php"; ?>
                    <input id="tipo_agente_asesor_id" value="<?php echo $controlador->tipo_agente_asesor_id ?>" type="hidden">
                    <form method="post" action="<?php echo $controlador->url_submit; ?>" class="form-additional">

                        <div class="control-group col-6">
                            <?php echo $controlador->inputs->com_tipo_agente_id; ?>
                        </div>

                        <div id="num_asesor_container" class="control-group col-sm-12">
                            <label class="control-label" for="num_asesor">
                                No.Asesor
                            </label>

                            <div class="controls">
                                <input
                                        type="text"
                                        class="form-control"
                                        id="num_asesor"
                                        name="num_asesor"
                                        inputmode="numeric"
                                        pattern="[0-9]+"
                                        oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                        required
                                >
                            </div>
                        </div>
                        <div class="control-group col-6">
                            <?php echo $controlador->inputs->nombre; ?>
                        </div>
                        <div class="control-group col-6">
                            <?php echo $controlador->inputs->apellido_paterno; ?>
                        </div>
                        <div class="control-group col-6">
                            <?php echo $controlador->inputs->apellido_materno; ?>
                        </div>
                        <div class="control-group col-6">
                            <?php echo $controlador->inputs->user; ?>
                        </div>
                        <div class="control-group col-6">
                            <?php echo $controlador->inputs->password; ?>
                        </div>
                        <div class="control-group col-6">
                            <?php echo $controlador->inputs->telefono; ?>
                        </div>
                        <div class="control-group col-6">
                            <?php echo $controlador->inputs->email; ?>
                        </div>


                        <div class="control-group btn-alta">
                            <div class="controls">
                                <button class="btn btn-success" role="submit">Alta</button><br>
                            </div>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>

</main>
