<?php
/** @var gamboamartin\facturacion\controllers\controlador_adm_usuario $controlador controlador en ejecucion */
?>
<?php use config\views; ?>
<?php
$metodo = 'modifica_telefono_bd';

$adm_usuario_id = (int)($controlador->registro['adm_usuario_id'] ?? $_GET['registro_id'] ?? 0);

$link = "index.php?seccion=adm_usuario";
$link .= "&accion={$metodo}";
$link .= "&registro_id={$adm_usuario_id}";

if (isset($_GET['session_id'])) {
    $link .= "&session_id={$_GET['session_id']}";
}

if (isset($_GET['adm_menu_id'])) {
    $link .= "&adm_menu_id={$_GET['adm_menu_id']}";
}

$codigo_pais = $controlador->registro['adm_usuario_codigo_pais'] ?? '52';

$usuario = trim(
    ($controlador->registro['adm_usuario_nombre'] ?? '') . ' ' .
    ($controlador->registro['adm_usuario_ap'] ?? '') . ' ' .
    ($controlador->registro['adm_usuario_am'] ?? '')
);

if ($usuario === '') {
    $usuario = $controlador->registro['adm_usuario_user'] ?? '';
}

$codigos_pais = [
    ['codigo' => '52', 'pais' => 'México', 'bandera' => '🇲🇽'],
    ['codigo' => '54', 'pais' => 'Argentina', 'bandera' => '🇦🇷'],
    ['codigo' => '55', 'pais' => 'Brasil', 'bandera' => '🇧🇷'],
    ['codigo' => '56', 'pais' => 'Chile', 'bandera' => '🇨🇱'],
    ['codigo' => '57', 'pais' => 'Colombia', 'bandera' => '🇨🇴'],
    ['codigo' => '58', 'pais' => 'Venezuela', 'bandera' => '🇻🇪'],
    ['codigo' => '51', 'pais' => 'Perú', 'bandera' => '🇵🇪'],
    ['codigo' => '593', 'pais' => 'Ecuador', 'bandera' => '🇪🇨'],
    ['codigo' => '591', 'pais' => 'Bolivia', 'bandera' => '🇧🇴'],
    ['codigo' => '595', 'pais' => 'Paraguay', 'bandera' => '🇵🇾'],
    ['codigo' => '598', 'pais' => 'Uruguay', 'bandera' => '🇺🇾'],
    ['codigo' => '502', 'pais' => 'Guatemala', 'bandera' => '🇬🇹'],
    ['codigo' => '503', 'pais' => 'El Salvador', 'bandera' => '🇸🇻'],
    ['codigo' => '504', 'pais' => 'Honduras', 'bandera' => '🇭🇳'],
    ['codigo' => '505', 'pais' => 'Nicaragua', 'bandera' => '🇳🇮'],
    ['codigo' => '506', 'pais' => 'Costa Rica', 'bandera' => '🇨🇷'],
    ['codigo' => '507', 'pais' => 'Panamá', 'bandera' => '🇵🇦'],
    ['codigo' => '53', 'pais' => 'Cuba', 'bandera' => '🇨🇺'],
    ['codigo' => '1', 'pais' => 'República Dominicana', 'bandera' => '🇩🇴'],
    ['codigo' => '509', 'pais' => 'Haití', 'bandera' => '🇭🇹'],
    ['codigo' => '1', 'pais' => 'Puerto Rico', 'bandera' => '🇵🇷'],
    ['codigo' => '1', 'pais' => 'USA', 'bandera' => '🇺🇸'],
    ['codigo' => '1', 'pais' => 'Canadá', 'bandera' => '🇨🇦'],
];
?>

<div class="col-lg-12">

    <?php include (new views())->ruta_templates . "head/title.php"; ?>
    <?php include (new views())->ruta_templates . "mensajes.php"; ?>

    <div class="widget widget-box box-container form-main widget-form-cart" id="form">

        <?php include (new views())->ruta_templates . "head/subtitulo.php"; ?>

        <form method="post"
              action="<?php echo $link; ?>"
              class="form-additional"
              enctype="multipart/form-data">

            <div class="row">
                <div class="control-group col-sm-6">
                    <label class="control-label" for="usuario">Usuario</label>
                    <div class="controls">
                        <input value="<?php echo $usuario; ?>"
                               type="text"
                               disabled="disabled"
                               class="form-control descripcion"
                               title="Usuario">
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
                   name="adm_usuario_id"
                   value="<?php echo $adm_usuario_id; ?>">

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