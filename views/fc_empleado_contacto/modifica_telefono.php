<?php /** @var  gamboamartin\facturacion\controllers\controlador_fc_empleado_contacto $controlador  controlador en ejecucion */ ?>
<?php use config\views; ?>
<?php
    $metodo = 'modifica_telefono_bd';
    $link = "index.php?seccion=fc_empleado_contacto";
    $link .= "&accion={$metodo}&session_id={$_GET['session_id']}";

    $codigo_pais = $controlador->registro['fc_empleado_contacto_codigo_pais'];
    $contacto = $controlador->registro['fc_empleado_contacto_descripcion'];

    $codigos_pais = [

        [
            'codigo' => '52',
            'pais' => 'México',
            'bandera' => '🇲🇽'
        ],

        [
            'codigo' => '54',
            'pais' => 'Argentina',
            'bandera' => '🇦🇷'
        ],

        [
            'codigo' => '55',
            'pais' => 'Brasil',
            'bandera' => '🇧🇷'
        ],

        [
            'codigo' => '56',
            'pais' => 'Chile',
            'bandera' => '🇨🇱'
        ],

        [
            'codigo' => '57',
            'pais' => 'Colombia',
            'bandera' => '🇨🇴'
        ],

        [
            'codigo' => '58',
            'pais' => 'Venezuela',
            'bandera' => '🇻🇪'
        ],

        [
            'codigo' => '51',
            'pais' => 'Perú',
            'bandera' => '🇵🇪'
        ],

        [
            'codigo' => '593',
            'pais' => 'Ecuador',
            'bandera' => '🇪🇨'
        ],

        [
            'codigo' => '591',
            'pais' => 'Bolivia',
            'bandera' => '🇧🇴'
        ],

        [
            'codigo' => '595',
            'pais' => 'Paraguay',
            'bandera' => '🇵🇾'
        ],

        [
            'codigo' => '598',
            'pais' => 'Uruguay',
            'bandera' => '🇺🇾'
        ],

        [
            'codigo' => '502',
            'pais' => 'Guatemala',
            'bandera' => '🇬🇹'
        ],

        [
            'codigo' => '503',
            'pais' => 'El Salvador',
            'bandera' => '🇸🇻'
        ],

        [
            'codigo' => '504',
            'pais' => 'Honduras',
            'bandera' => '🇭🇳'
        ],

        [
            'codigo' => '505',
            'pais' => 'Nicaragua',
            'bandera' => '🇳🇮'
        ],

        [
            'codigo' => '506',
            'pais' => 'Costa Rica',
            'bandera' => '🇨🇷'
        ],

        [
            'codigo' => '507',
            'pais' => 'Panamá',
            'bandera' => '🇵🇦'
        ],

        [
            'codigo' => '53',
            'pais' => 'Cuba',
            'bandera' => '🇨🇺'
        ],

        [
            'codigo' => '1',
            'pais' => 'República Dominicana',
            'bandera' => '🇩🇴'
        ],

        [
            'codigo' => '509',
            'pais' => 'Haití',
            'bandera' => '🇭🇹'
        ],

        [
            'codigo' => '1',
            'pais' => 'Puerto Rico',
            'bandera' => '🇵🇷'
        ],

        [
            'codigo' => '1',
            'pais' => 'USA',
            'bandera' => '🇺🇸'
        ],

        [
            'codigo' => '1',
            'pais' => 'Canadá',
            'bandera' => '🇨🇦'
        ]

    ];
?>

<div class="col-lg-12">

    <?php include (new views())->ruta_templates."head/title.php"; ?>
    <?php include (new views())->ruta_templates."mensajes.php"; ?>

    <div class="widget  widget-box box-container form-main widget-form-cart" id="form">

        <?php include (new views())->ruta_templates."head/subtitulo.php"; ?>

        <form method="post"
              action="<?php echo $link; ?>"
              class="form-additional"
              enctype="multipart/form-data">
            <div class="row">
                <div class="control-group col-sm-6">
                    <label class="control-label" for="descripcion">Contacto</label>
                    <div class="controls">
                        <input value="<?php echo $contacto; ?>" type="text" disabled="disabled"   class="form-control descripcion" title="Contacto">
                    </div>
                </div>
            </div>
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
                                required
                            >
                                <option value="">
                                    Selecciona una opción
                                </option>
                                <?php foreach ($codigos_pais as $pais) { ?>
                                    <option
                                        value="<?php echo $pais['codigo']; ?>"
                                        <?php echo $codigo_pais === $pais['codigo'] ? 'selected' : ''; ?>
                                    >
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

            <div class="row">
                <?php echo $controlador->inputs->telefono; ?>
            </div>

            <input type="hidden"
                   name="fc_empleado_contacto_id"
                   value="<?php echo $_GET['registro_id'] ?>">

            <div class="row">
                <div class="control-group btn-alta">
                    <div class="controls">
                        <button class="btn btn-success" role="button">
                            Modificar
                        </button><br>
                    </div>
                </div>
            </div>
        </form>

    </div>
</div>
