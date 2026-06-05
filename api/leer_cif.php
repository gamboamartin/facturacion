<?php

use base\conexion;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\com_cliente;

chdir(__DIR__ . '/..');
require "init.php";
require 'vendor/autoload.php';
require_once __DIR__ . '/seguridad_edpoint.php';

use config\generales;

header('Content-Type: application/json; charset=utf-8');

$con  = new conexion();
$link = conexion::$link;

// paso 1. VALIDAR ARCHIVO

if (!isset($_FILES['documento']) || $_FILES['documento']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode([
        'STS' => 'error',
        'MSG' => 'No se recibió ningún archivo PDF o hubo un error al subirlo'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_FILES['documento']['type'] !== 'application/pdf') {
    echo json_encode([
        'STS' => 'error',
        'MSG' => 'El archivo debe ser un PDF'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// paso 2. VALIDACION DE TELEFONO WHATSAPP

$telefono_whatsapp = trim($_POST['telefono_whatsapp'] ?? $_POST['from'] ?? '');

if ($telefono_whatsapp === '') {
    echo json_encode([
        'STS' => 'error',
        'MSG' => 'Para leer el CIF es necesario enviar el telefono de WhatsApp validado'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// paso 3. SEGURIDAD

$seguridad_endpoint = new SeguridadEndpoint($link);

$r_seguridad = $seguridad_endpoint->valida_adm_usuario(
    telefono_whatsapp: $telefono_whatsapp,
    accion_id: generales::$accion_id_alta_cliente
);

if (!$r_seguridad['autorizado']) {
    echo json_encode([
        'STS' => 'no_autorizado',
        'MSG' => $r_seguridad['mensaje']
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$_SESSION['usuario_id'] = $r_seguridad['adm_usuario_id'];
$_SESSION['grupo_id']   = $r_seguridad['adm_grupo_id'];

// paso 4. LEER CIF
$_GET['registro_id'] = 'tmp_' . time();
$modelo = new com_cliente($link);
$resultado = $modelo->leer_codigo_qr();
if (errores::$error) {
    echo json_encode([
        'STS' => 'error',
        'MSG' => 'Error al leer el código QR del CIF',
        'detalle' => $resultado  
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// paso 5. RESPUESTA

echo json_encode([
    'STS'  => 'ok',
    'MSG'  => 'CIF leído correctamente',
    'data' => $resultado
], JSON_UNESCAPED_UNICODE);
exit;