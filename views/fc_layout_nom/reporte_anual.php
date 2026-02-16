<?php /** @var  \gamboamartin\facturacion\controllers\controlador_fc_layout_nom $controlador controlador en ejecucion */ ?>
<?php use config\views; ?>

<main class="main section-color-primary">
    <div class="container">

        <div class="row">

            <div class="col-lg-12">
                <?php include (new views())->ruta_templates."head/title.php"; ?>
                <?php include (new views())->ruta_templates."mensajes.php"; ?>

                <div class="widget  widget-box box-container form-main widget-form-cart" id="form" >
                    <?php include (new views())->ruta_templates . "head/subtitulo.php"; ?>

                    <form method="post" action="<?php echo $controlador->link_reporte_anual_bd; ?>" class="form-additional">

                        <div class="control-group col-sm-12">
                            <label class="control-label" for="anio">Año</label>
                            <div class="controls">
                                <select
                                        name="year"
                                        id="year"
                                        class="form-control color-secondary selectpicker selectpicker-primary"
                                        data-live-search="true"
                                        title="Selecciona una opción"
                                        required
                                >
                                    <option value="">Selecciona una opcion</option>
                                    <?php
                                    $anio_actual = date('Y');
                                    $inicio = $anio_actual - 5;
                                    $fin = $anio_actual + 5;

                                    for($i = $inicio; $i <= $fin; $i++) {
                                        $selected = ($i == $anio_actual) ? 'selected' : '';
                                        echo "<option value='$i' $selected>$i</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>


                        <div class="control-group btn-alta">
                            <div class="controls">
                                <button class="btn btn-success" role="submit">Generar Reporte</button><br>
                            </div>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>

</main>
