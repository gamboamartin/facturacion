<?php

use base\conexion;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\controllers\controlador_com_contacto;
use gamboamartin\facturacion\models\com_contacto;

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


$modelo = new com_contacto($link);
$filtro = [
    'com_contacto.correo' => $correo,
    'com_contacto.token_validacion' => $token
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
$com_contacto_id = $registro['com_contacto_id'];

$rs = $modelo->actualiza_estado_correo(
    registro_id: $com_contacto_id,
    estado: controlador_com_contacto::STATUS_VALIDADO,
    borrar_token: true
);
if (errores::$error) {
    print_r($rs);
    exit;
}

echo 'Correo validado';
exit;