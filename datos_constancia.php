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


$elementos = ['KEY','FC_ROW_LAYOUT_ID','CP','RFC','CURP','NOMBRE_COMPLETO'];


foreach ($elementos as $elemento) {
    if (!isset($_POST[$elemento])) {
        echo "$elemento no existe en POST";
        exit;
    }
}

$key_datos_constancia = generales::$key_datos_constancia;
$llave = md5($_POST['RFC'].$key_datos_constancia.$_POST['FC_ROW_LAYOUT_ID']);

if ($llave !== (string)$_POST['KEY']) {
    $error = (new errores())->error("Error al validar key", []);
    print_r($error);
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
    $error = (new errores())->error("Error al actualizar info fc_row_layout", $result);
    print_r($error);
    exit;
}

$result = (new _timbra_nomina())->timbra_recibo(link: $link, fc_row_layout_id: $fc_row_layout_id);
if(errores::$error) {
    $error = (new errores())->error("Error al timbrar", $result);
    print_r($error);
    exit;
}

echo 'success';exit;






