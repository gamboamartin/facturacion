<?php /** @var  \gamboamartin\facturacion\controllers\controlador_com_cliente $controlador controlador en ejecucion */ ?>
<?php use config\views; ?>

<?php
$codigo_pais = $_POST['codigo_pais'] ?? '52';

$codigos_pais = array(
    array(
        'codigo' => '52',
        'pais' => 'México',
        'bandera' => '🇲🇽'
    ),
    array(
        'codigo' => '54',
        'pais' => 'Argentina',
        'bandera' => '🇦🇷'
    ),
    array(
        'codigo' => '55',
        'pais' => 'Brasil',
        'bandera' => '🇧🇷'
    ),
    array(
        'codigo' => '56',
        'pais' => 'Chile',
        'bandera' => '🇨🇱'
    ),
    array(
        'codigo' => '57',
        'pais' => 'Colombia',
        'bandera' => '🇨🇴'
    ),
    array(
        'codigo' => '58',
        'pais' => 'Venezuela',
        'bandera' => '🇻🇪'
    ),
    array(
        'codigo' => '51',
        'pais' => 'Perú',
        'bandera' => '🇵🇪'
    ),
    array(
        'codigo' => '593',
        'pais' => 'Ecuador',
        'bandera' => '🇪🇨'
    ),
    array(
        'codigo' => '591',
        'pais' => 'Bolivia',
        'bandera' => '🇧🇴'
    ),
    array(
        'codigo' => '595',
        'pais' => 'Paraguay',
        'bandera' => '🇵🇾'
    ),
    array(
        'codigo' => '598',
        'pais' => 'Uruguay',
        'bandera' => '🇺🇾'
    ),
    array(
        'codigo' => '502',
        'pais' => 'Guatemala',
        'bandera' => '🇬🇹'
    ),
    array(
        'codigo' => '503',
        'pais' => 'El Salvador',
        'bandera' => '🇸🇻'
    ),
    array(
        'codigo' => '504',
        'pais' => 'Honduras',
        'bandera' => '🇭🇳'
    ),
    array(
        'codigo' => '505',
        'pais' => 'Nicaragua',
        'bandera' => '🇳🇮'
    ),
    array(
        'codigo' => '506',
        'pais' => 'Costa Rica',
        'bandera' => '🇨🇷'
    ),
    array(
        'codigo' => '507',
        'pais' => 'Panamá',
        'bandera' => '🇵🇦'
    ),
    array(
        'codigo' => '53',
        'pais' => 'Cuba',
        'bandera' => '🇨🇺'
    ),
    array(
        'codigo' => '1',
        'pais' => 'República Dominicana',
        'bandera' => '🇩🇴'
    ),
    array(
        'codigo' => '509',
        'pais' => 'Haití',
        'bandera' => '🇭🇹'
    ),
    array(
        'codigo' => '1',
        'pais' => 'Puerto Rico',
        'bandera' => '🇵🇷'
    ),
    array(
        'codigo' => '1',
        'pais' => 'USA',
        'bandera' => '🇺🇸'
    ),
    array(
        'codigo' => '1',
        'pais' => 'Canadá',
        'bandera' => '🇨🇦'
    )
);
?>

<main class="main section-color-primary">
    <div class="container">

        <div class="row">

            <div class="col-lg-12">

                <?php include (new views())->ruta_templates . "head/title.php"; ?>
                <?php include (new views())->ruta_templates . "mensajes.php"; ?>

                <div class="widget widget-box box-container form-main widget-form-cart" id="form">

                    <?php include (new views())->ruta_templates . "head/subtitulo.php"; ?>

                    <form method="post"
                          action="<?php echo $controlador->link_asigna_contacto_bd; ?>"
                          class="form-additional">

                        <?php echo $controlador->inputs->com_tipo_contacto_id; ?>
                        <?php echo $controlador->inputs->nombre; ?>
                        <?php echo $controlador->inputs->ap; ?>
                        <?php echo $controlador->inputs->am; ?>

                        <div class="row">
                            <div class="col-sm-6">
                                <div class="control-group">
                                    <label class="control-label" for="codigo_pais">
                                        Código de país
                                    </label>
                                    <div class="controls">
                                        <select
                                            class="form-control selectpicker color-secondary codigo_pais"
                                            data-live-search="true"
                                            id="codigo_pais"
                                            name="codigo_pais"
                                            title="Selecciona una opción"
                                            required>

                                            <option value="">
                                                Selecciona una opción
                                            </option>

                                            <?php foreach ($codigos_pais as $pais) { ?>
                                                <option
                                                    value="<?php echo $pais['codigo']; ?>"
                                                    <?php echo (string)$codigo_pais === (string)$pais['codigo'] ? 'selected' : ''; ?>>
                                                    <?php
                                                    echo $pais['bandera'] . ' ';
                                                    echo $pais['pais'];
                                                    echo ' (+' . $pais['codigo'] . ')';
                                                    ?>
                                                </option>
                                            <?php } ?>

                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php echo $controlador->inputs->telefono; ?>
                        <?php echo $controlador->inputs->correo; ?>

                        <input type="hidden"
                               name="com_cliente_id"
                               value="<?php echo $_GET['registro_id']; ?>">

                        <div class="control-group btn-alta">
                            <div class="controls">
                                <button class="btn btn-success" role="submit">
                                    Asignar
                                </button><br>
                            </div>
                        </div>

                    </form>
                </div>

            </div>
        </div>

        <div class="col-md-12 buttons-form">
            <?php echo $controlador->button_com_cliente_modifica; ?>
        </div>

    </div>
</main>

<main class="main section-color-primary">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="widget widget-box box-container widget-mylistings">
                    <?php echo $controlador->contenido_table; ?>
                </div>
            </div>
        </div>
    </div>
</main>