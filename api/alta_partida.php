<?php

use base\conexion;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\fc_partida;
use gamboamartin\comercial\models\com_producto;
use Yosymfony\Toml\Toml;

chdir(__DIR__ . '/..');
require "init.php";
require 'vendor/autoload.php';
require_once __DIR__ . '/seguridad_edpoint.php';

use config\generales;

header('Content-Type: application/json; charset=utf-8');

$con  = new conexion();
$link = conexion::$link;

$accion_id = generales::$accion_id_alta_partida;

// =========================================================
// PASO 1. PARÁMETROS DE ENTRADA
// =========================================================

$telefono_whatsapp = trim($_GET['telefono_whatsapp'] ?? $_GET['telefono'] ?? '');
$fc_factura_id     = (int)($_GET['FID']  ?? 0);
$codigo_producto   = trim($_GET['PRD']   ?? '');
$descripcion       = trim($_GET['DES']   ?? '');
$cantidad          = (float)($_GET['QTY'] ?? 0);
$valor_unitario    = (float)($_GET['VU']  ?? 0);
$descuento         = (float)($_GET['DESC'] ?? 0);

// =========================================================
// PASO 2. VALIDACIÓN DE TELÉFONO WHATSAPP
// =========================================================

if ($telefono_whatsapp === '') {
    echo json_encode([
        'STS' => 'error',
        'MSG' => 'El telefono de WhatsApp es obligatorio'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// =========================================================
// PASO 3. VALIDACIÓN DE CAMPOS OBLIGATORIOS
// =========================================================

$campos_obligatorios = [
    'FID' => $fc_factura_id,
    'PRD' => $codigo_producto,
    'DES' => $descripcion,
    'QTY' => $cantidad,
    'VU'  => $valor_unitario,
];

foreach ($campos_obligatorios as $clave => $valor) {
    if ($valor === '' || $valor === 0 || $valor === 0.0) {
        echo json_encode([
            'STS' => 'datos_incompletos',
            'MSG' => "El campo $clave es obligatorio y no fue enviado"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// =========================================================
// PASO 4. SEGURIDAD
// =========================================================

$seguridad_endpoint = new SeguridadEndpoint($link);

$r_seguridad = $seguridad_endpoint->valida_adm_usuario(
    telefono_whatsapp: $telefono_whatsapp,
    accion_id: $accion_id
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

// =========================================================
// PASO 5. TOML DEL ESCENARIO
// =========================================================

$toml_path = __DIR__ . '/../config/escenario/alta_partida.toml';

if (!file_exists($toml_path)) {
    echo json_encode([
        'STS' => 'error',
        'MSG' => 'No existe el archivo de escenario TOML'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $escenario = Toml::ParseFile($toml_path);
} catch (Throwable $e) {
    echo json_encode([
        'STS' => 'error',
        'MSG' => 'No fue posible leer el archivo TOML del escenario'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$cat_sat_obj_imp_codigo = $escenario['defaults']['cat_sat_obj_imp_codigo'] ?? '02';

// =========================================================
// PASO 6. DICCIONARIO DE DATOS
// =========================================================

$diccionario_path = __DIR__ . '/../config/json/alta_partida.json';

if (!file_exists($diccionario_path)) {
    echo json_encode([
        'STS' => 'error',
        'MSG' => 'No existe el diccionario de datos'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$diccionario_json = file_get_contents($diccionario_path);
$diccionario      = json_decode($diccionario_json, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        'STS' => 'error',
        'MSG' => 'No fue posible leer el diccionario de datos'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// =========================================================
// PASO 7. VERIFICAR QUE LA FACTURA EXISTE
// =========================================================

$stmt_fc = $link->prepare("SELECT id FROM fc_factura WHERE id = :id LIMIT 1");
$stmt_fc->execute([':id' => $fc_factura_id]);
$row_fc = $stmt_fc->fetch(PDO::FETCH_ASSOC);

if (!$row_fc) {
    echo json_encode([
        'STS' => 'factura_no_encontrada',
        'MSG' => "No se encontró la factura con ID: $fc_factura_id"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// =========================================================
// PASO 8. RESOLUCIÓN DE PRODUCTO
// =========================================================

$r_producto = (new com_producto($link))->filtro_and(
    filtro: ['com_producto.codigo' => $codigo_producto]
);

if (errores::$error || $r_producto->n_registros === 0) {
    echo json_encode([
        'STS' => 'producto_no_encontrado',
        'MSG' => "No se encontró un producto con código: $codigo_producto"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$com_producto_id = (int)$r_producto->registros[0]['com_producto_id'];

// =========================================================
// PASO 9. RESOLUCIÓN DE OBJETO DE IMPUESTO
// =========================================================

$stmt_obj = $link->prepare("SELECT id FROM cat_sat_obj_imp WHERE codigo = :codigo LIMIT 1");
$stmt_obj->execute([':codigo' => $cat_sat_obj_imp_codigo]);
$row_obj = $stmt_obj->fetch(PDO::FETCH_ASSOC);

if (!$row_obj) {
    echo json_encode([
        'STS' => 'error',
        'MSG' => "No se encontró el objeto de impuesto con código: $cat_sat_obj_imp_codigo"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$cat_sat_obj_imp_id = (int)$row_obj['id'];

// =========================================================
// PASO 10. ALTA DE LA PARTIDA
// =========================================================

$modelo_partida = new fc_partida($link);

$modelo_partida->registro['fc_factura_id']      = $fc_factura_id;
$modelo_partida->registro['com_producto_id']    = $com_producto_id;
$modelo_partida->registro['descripcion']        = $descripcion;
$modelo_partida->registro['cantidad']           = $cantidad;
$modelo_partida->registro['valor_unitario']     = $valor_unitario;
$modelo_partida->registro['descuento']          = $descuento;
$modelo_partida->registro['cat_sat_obj_imp_id'] = $cat_sat_obj_imp_id;

if (!isset($_FILES['documento'])) {
    $_FILES['documento'] = ['name' => ''];
}

$r_alta = $modelo_partida->alta_bd();

if (errores::$error) {
    echo json_encode([
        'STS'   => 'error',
        'MSG'   => 'Error al insertar la partida en el sistema',
        'DEBUG' => $r_alta
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// =========================================================
// PASO 11. RESPUESTA
// =========================================================

echo json_encode([
    'STS' => 'ok',
    'MSG' => 'Partida creada exitosamente',
    'PID' => (int)$r_alta->registro_id,
    'FID' => $fc_factura_id,
], JSON_UNESCAPED_UNICODE);
exit;