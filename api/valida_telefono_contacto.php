<?php

use base\conexion;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\com_contacto;

$_SESSION['usuario_id'] = 2;
chdir(__DIR__ . '/..');
require "../init.php";
require '../vendor/autoload.php';
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

/*
 * Mensaje de confirmación por n8n.
 * Si falla n8n, no rompemos la validación ya realizada.
 */
$telefono_contacto = $registro['com_contacto_telefono'] ?? $telefono;
$codigo_pais = $registro['com_contacto_codigo_pais'] ?? '52';
$nombre = $registro['com_contacto_descripcion'] ?? '';

$telefono_whatsapp = $modelo->telefono_whatsapp(
    telefono: $telefono_contacto,
    codigo_pais: $codigo_pais
);

if (!errores::$error) {
    $n8n = new \gamboamartin\facturacion\controllers\_n8n_request();

    $r_n8n = $n8n->request_telefono_confirmado_contacto(
        com_contacto_id: $com_contacto_id,
        nombre: $nombre,
        codigo_pais: $codigo_pais,
        telefono: $telefono_whatsapp,
        servicios: 'Descarga de facturas XML y PDF, Timbrar Facturas, crear Productos, crear Clientes y crear Facturas.'
    );

}

include __DIR__ . '/mensaje_confirmacion.php';
exit;