<?php

use base\conexion;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\com_contacto;

$_SESSION['usuario_id'] = 2;

require "init.php";
require 'vendor/autoload.php';

$con = new conexion();
$link = conexion::$link;

if (!isset($_GET['telefono'])) {
    echo 'no existe el telefono en GET';
    exit;
}

if (!isset($_GET['token'])) {
    echo 'no existe el token en GET';
    exit;
}

$telefono = trim($_GET['telefono']);
$token = trim($_GET['token']);

if ($telefono === '') {
    echo 'telefono vacio';
    exit;
}

if ($token === '') {
    echo 'token vacio';
    exit;
}

$modelo = new com_contacto($link);

$filtro = [
    'com_contacto.telefono' => $telefono,
    'com_contacto.token_telefono' => $token
];

$rs = $modelo->filtro_and(filtro: $filtro);
if (errores::$error) {
    print_r($rs);
    exit;
}

if ((int)$rs->n_registros === 0) {
    echo 'El token de validacion no existe';
    exit;
}

$registro = $rs->registros[0];
$com_contacto_id = (int)$registro['com_contacto_id'];

$rs = $modelo->valida_token_telefono(com_contacto_id: $com_contacto_id);
if (errores::$error) {
    print_r($rs);
    exit;
}

echo 'Telefono validado';
exit;