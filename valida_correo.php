<?php

use base\conexion;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\controllers\controlador_fc_empleado_contacto;
use gamboamartin\facturacion\models\fc_empleado_contacto;

$_SESSION['usuario_id'] = 2;

require "init.php";
require 'vendor/autoload.php';

$con = new conexion();
$link = conexion::$link;

if (!isset($_GET['correo'])) {
    echo 'no existe el correo en GET';
    exit;
}

if (!isset($_GET['token'])) {
    echo 'no existe el token en GET';
    exit;
}

$correo = $_GET['correo'];
$token = $_GET['token'];


$modelo = new fc_empleado_contacto($link);
$filtro = [
    'fc_empleado_contacto.correo' => $correo,
    'fc_empleado_contacto.token_validacion' => $token
];

$rs = $modelo->filtro_and(filtro: $filtro);
if (errores::$error) {
    print_r($rs);
    exit;
}

$n_registros = $rs->n_registros;

if ($n_registros === 0) {
    echo 'El token de validacion no existe';
    exit;
}

$registro = $rs->registros[0];
$fc_empleado_contacto_id = $registro['fc_empleado_contacto_id'];

$row_update = [
    'estatus_correo' => (controlador_fc_empleado_contacto::STATUS_VALIDADO),
    'token_validacion' => '',
];

$rs = $modelo->modifica_bd(registro: $row_update, id: $fc_empleado_contacto_id);
if (errores::$error) {
    print_r($rs);
    exit;
}

echo 'Correo validado';
exit;