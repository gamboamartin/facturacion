<?php

use base\conexion;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\adm_usuario;

$_SESSION['usuario_id'] = 2;

chdir(__DIR__ . '/..');

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

$modelo = new adm_usuario($link);

$filtro = [
    'adm_usuario.telefono' => $telefono,
    'adm_usuario.token_telefono' => $token,
    'adm_usuario.status' => 'activo',
    'adm_grupo.status' => 'activo',
    'adm_grupo.root' => 'activo',
    'adm_grupo.codigo' => 'PA',
    'adm_grupo.alias' => 'SA',
];

$rs = $modelo->filtro_and(filtro: $filtro);
if (errores::$error) {
    print_r($rs);
    exit;
}

if ((int)$rs->n_registros === 0) {
    echo 'El token de validacion no existe o el usuario no es administrador del sistema';
    exit;
}

$registro = $rs->registros[0];
$adm_usuario_id = (int)$registro['adm_usuario_id'];

$rs = $modelo->valida_token_telefono(adm_usuario_id: $adm_usuario_id);
if (errores::$error) {
    print_r($rs);
    exit;
}


$telefono_usuario = $registro['adm_usuario_telefono'] ?? $telefono;
$codigo_pais = $registro['adm_usuario_codigo_pais'] ?? '52';

$nombre = trim(
    ($registro['adm_usuario_nombre'] ?? '') . ' ' .
    ($registro['adm_usuario_ap'] ?? '') . ' ' .
    ($registro['adm_usuario_am'] ?? '')
);

if ($nombre === '') {
    $nombre = $registro['adm_usuario_user'] ?? 'Administrador';
}

$telefono_whatsapp = $modelo->telefono_whatsapp(
    telefono: $telefono_usuario,
    codigo_pais: $codigo_pais
);

if (!errores::$error) {
    $n8n = new \gamboamartin\facturacion\controllers\_n8n_request();

  
    $r_n8n = $n8n->request_telefono_confirmado_adm_usuario(
        adm_usuario_id: $adm_usuario_id,
        nombre: $nombre,
        codigo_pais: $codigo_pais,
        telefono: $telefono_whatsapp,
        servicios: 'Administración del sistema, descarga de facturas XML y PDF, timbrado de facturas, creación de productos, clientes y facturas.'
    );
}

include __DIR__ . '/mensaje_confirmacion.php';
exit;