<?php

use base\conexion;
use config\generales;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\_timbra_nomina;
use gamboamartin\facturacion\models\fc_row_layout;

$_SESSION['usuario_id'] = 2;
$_SESSION['grupo_id'] = 2;

require "init.php";
require 'vendor/autoload.php';

$con = new conexion();
$link = conexion::$link;

header('Content-Type: application/json; charset=utf-8');

$elementos = ['KEY','FC_ROW_LAYOUT_ID','CP','RFC','CURP','NOMBRE_COMPLETO'];


foreach ($elementos as $elemento) {
    if (!isset($_POST[$elemento])) {
        echo json_encode([
            'success' => false,
            'message' => "$elemento no existe en POST",
            'error' => $_POST
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$key_n8n = generales::$key_n8n;
$llave = md5($_POST['RFC'].$key_n8n.$_POST['FC_ROW_LAYOUT_ID']);

if ($llave !== (string)$_POST['KEY']) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al validar key',
        'error' => $_POST['KEY']
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

$modelo = new fc_row_layout(link: $link);
$fc_row_layout_id = $_POST['FC_ROW_LAYOUT_ID'];
$result = $modelo->modifica_bd(
    registro: [
        'nombre_completo' => $_POST['NOMBRE_COMPLETO'],
        'rfc' => $_POST['RFC'],
        'cp' => $_POST['CP'],
        'curp' => $_POST['CURP'],
    ],
    id: $fc_row_layout_id
);

if(errores::$error) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al actualizar info fc_row_layout',
            'error' => $result
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
}

$result = (new _timbra_nomina())->timbra_recibo(link: $link, fc_row_layout_id: $fc_row_layout_id);
if(errores::$error) {
    echo json_encode([
        'success' => false,
        'message' => "Error al timbrar",
        'error' => $result
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}
echo json_encode([
    'success' => true,
    'message' => "success",
    'error' => []
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);







