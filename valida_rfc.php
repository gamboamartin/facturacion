<?php

use base\conexion;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\fc_empleado;

$_SESSION['usuario_id'] = 2;

require "init.php";
require 'vendor/autoload.php';

$con = new conexion();
$link = conexion::$link;

if (!isset($_POST['rfc'])) {
    header('Content-Type: application/json');
    $response  = [
        'success' => false,
        'error' => true,
        'mensaje' => 'no existe rfc en POST'
    ];
    echo json_encode($response, JSON_THROW_ON_ERROR);
    exit;
}

if (!isset($_POST['curp'])) {
    header('Content-Type: application/json');
    $response  = [
        'success' => false,
        'error' => true,
        'mensaje' => 'no existe curp en POST'
    ];
    echo json_encode($response, JSON_THROW_ON_ERROR);
    exit;
}

$rfc = $_POST['rfc'];
$curp = $_POST['curp'];

$rfc = strtoupper($rfc);
$curp = strtoupper($curp);

$modelo = new fc_empleado($link);
$filtro = [
    'fc_empleado.rfc' => $rfc,
    'fc_empleado.curp' => $curp
];

$rs = $modelo->filtro_and(filtro: $filtro, limit: 2);
if (errores::$error) {
    header('Content-Type: application/json');
    $response  = [
        'success' => false,
        'error' => true,
        'mensaje' => $rs['error']
    ];
    echo json_encode($response, JSON_THROW_ON_ERROR);
}

$n_registros = $rs->n_registros;

if ($n_registros === 1) {
    header('Content-Type: application/json');
    $response  = [
        'success' => true,
        'error' => false,
        'rfc' => 'valido'
    ];
    echo json_encode($response);
    exit;
}

header('Content-Type: application/json');
$response  = [
    'success' => true,
    'error' => false,
    'rfc' => 'invalido'
];
echo json_encode($response);
exit;
