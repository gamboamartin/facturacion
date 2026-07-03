<?php

use base\conexion;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\fc_factura;
use gamboamartin\facturacion\models\fc_csd;
use gamboamartin\comercial\models\com_cliente;
use gamboamartin\comercial\models\com_sucursal;
use Yosymfony\Toml\Toml;

chdir(__DIR__ . '/..');
require "init.php";
require 'vendor/autoload.php';
require_once __DIR__ . '/seguridad_edpoint.php';
require_once __DIR__ . '/catalogos_sat.php';

use config\generales;

header('Content-Type: application/json; charset=utf-8');

$con  = new conexion();
$link = conexion::$link;

$accion_id = generales::$accion_id_alta_factura;

// =========================================================
// PASO 1. PARÁMETROS DE ENTRADA
// =========================================================

$telefono_whatsapp = trim($_GET['telefono_whatsapp'] ?? $_GET['telefono'] ?? '');
$rfc_cliente       = strtoupper(trim($_GET['RFC'] ?? ''));
$forma_pago        = trim($_GET['FP']  ?? '');  // código SAT ej: 03
$metodo_pago       = trim($_GET['MP']  ?? '');  // código SAT ej: PUE
$uso_cfdi          = trim($_GET['UC']  ?? '');  // código SAT ej: G03
$moneda            = trim($_GET['MON'] ?? '');  // código SAT ej: MXN

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
    'RFC' => $rfc_cliente,
    'FP'  => $forma_pago,
    'MP'  => $metodo_pago,
    'UC'  => $uso_cfdi,
    'MON' => $moneda,
];

foreach ($campos_obligatorios as $clave => $valor) {
    if ($valor === '') {
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

$toml_path = __DIR__ . '/../config/escenario/alta_factura.toml';

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

$org_sucursal_id = (int)($escenario['emisor']['org_sucursal_id'] ?? 0);

if ($org_sucursal_id <= 0) {
    echo json_encode([
        'STS' => 'error',
        'MSG' => 'El escenario TOML no tiene configurado org_sucursal_id'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// =========================================================
// PASO 6. DICCIONARIO DE DATOS
// =========================================================

$diccionario_path = __DIR__ . '/../config/json/alta_factura.json';

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

$campos_salida_permitidos  = $escenario['output']['permitidos'] ?? [];
$filtrar_campos            = (bool)($escenario['comportamiento']['filtrar_campos'] ?? false);

// =========================================================
// PASO 7. RESOLUCIÓN AUTOMÁTICA — EMISOR

// =========================================================

// 6a. CSD activo de la sucursal emisora
$r_csd = (new fc_csd($link))->filtro_and(
    filtro: [
        'fc_csd.org_sucursal_id' => $org_sucursal_id,
        'fc_csd.etapa'           => 'LISTO USO'
    ]
);

if (errores::$error || $r_csd->n_registros === 0) {
    echo json_encode([
        'STS' => 'error',
        'MSG' => 'No se encontró un CSD activo para la sucursal emisora'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$fc_csd_id              = (int)$r_csd->registros[0]['fc_csd_id'];
$dp_calle_pertenece_id  = (int)($r_csd->registros[0]['org_sucursal_dp_calle_pertenece_id'] ?? 0);
$cat_sat_regimen_fiscal_id = (int)($r_csd->registros[0]['org_empresa_cat_sat_regimen_fiscal_id'] ?? 0);

if ($fc_csd_id <= 0) {
    echo json_encode([
        'STS' => 'error',
        'MSG' => 'No fue posible resolver el CSD de la sucursal emisora'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// =========================================================
// PASO 8. RESOLUCIÓN AUTOMÁTICA — RECEPTOR (por RFC)
// =========================================================

$r_cliente = (new com_cliente($link))->filtro_and(
    filtro: ['com_cliente.rfc' => $rfc_cliente]
);

if (errores::$error || $r_cliente->n_registros === 0) {
    echo json_encode([
        'STS' => 'cliente_no_encontrado',
        'MSG' => "No se encontró un cliente con RFC: $rfc_cliente"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$com_cliente_id = (int)$r_cliente->registros[0]['com_cliente_id'];

// Sucursal del cliente (preferimos la predeterminada)
$r_sucursal = (new com_sucursal($link))->filtro_and(
    filtro: [
        'com_sucursal.com_cliente_id' => $com_cliente_id,
        'com_sucursal.predeterminado' => 'activo'
    ]
);

if (errores::$error || $r_sucursal->n_registros === 0) {
    // Si no tiene predeterminada, tomamos cualquiera
    $r_sucursal = (new com_sucursal($link))->filtro_and(
        filtro: ['com_sucursal.com_cliente_id' => $com_cliente_id]
    );

    if (errores::$error || $r_sucursal->n_registros === 0) {
        echo json_encode([
            'STS' => 'error',
            'MSG' => "No se encontró sucursal para el cliente con RFC: $rfc_cliente"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

$com_sucursal_id = (int)$r_sucursal->registros[0]['com_sucursal_id'];

// =========================================================
// PASO 9. RESOLUCIÓN DE CATÁLOGOS SAT
// =========================================================


$cat_sat_forma_pago_id  = resuelve_catalogo($link, 'cat_sat_forma_pago', $forma_pago);
$cat_sat_metodo_pago_id = resuelve_catalogo($link, 'cat_sat_metodo_pago', $metodo_pago);
$cat_sat_uso_cfdi_id    = resuelve_catalogo($link, 'cat_sat_uso_cfdi', $uso_cfdi);
$cat_sat_moneda_id      = resuelve_catalogo($link, 'cat_sat_moneda', $moneda);

$catalogos = [
    'FP  → cat_sat_forma_pago'  => $cat_sat_forma_pago_id,
    'MP  → cat_sat_metodo_pago' => $cat_sat_metodo_pago_id,
    'UC  → cat_sat_uso_cfdi'    => $cat_sat_uso_cfdi_id,
    'MON → cat_sat_moneda'      => $cat_sat_moneda_id,
];

foreach ($catalogos as $label => $id) {
    if ($id <= 0) {
        echo json_encode([
            'STS' => 'catalogo_no_encontrado',
            'MSG' => "No se encontró el catálogo SAT: $label"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// =========================================================
// PASO 10. ALTA DE LA FACTURA
// =========================================================

$stmt_tc = $link->prepare("SELECT id FROM com_tipo_cambio WHERE cat_sat_moneda_id = :moneda_id ORDER BY fecha DESC LIMIT 1");
$stmt_tc->execute([':moneda_id' => $cat_sat_moneda_id]);
$row_tc = $stmt_tc->fetch(PDO::FETCH_ASSOC);

if (!$row_tc) {
    echo json_encode(['STS' => 'error', 'MSG' => 'No se encontró tipo de cambio para la moneda seleccionada'], JSON_UNESCAPED_UNICODE);
    exit;
}

$com_tipo_cambio_id = (int)$row_tc['id'];

$modelo_factura = new fc_factura($link);

$modelo_factura->registro['version']                        = '4.0';
$modelo_factura->registro['exportacion']                    = '01';
$modelo_factura->registro['com_sucursal_id']                = $com_sucursal_id;
$modelo_factura->registro['fc_csd_id']                      = $fc_csd_id;
$modelo_factura->registro['dp_calle_pertenece_id']          = $dp_calle_pertenece_id;
$modelo_factura->registro['cat_sat_regimen_fiscal_id']      = $cat_sat_regimen_fiscal_id;
$modelo_factura->registro['cat_sat_forma_pago_id']          = $cat_sat_forma_pago_id;
$modelo_factura->registro['cat_sat_metodo_pago_id']         = $cat_sat_metodo_pago_id;
$modelo_factura->registro['cat_sat_uso_cfdi_id']            = $cat_sat_uso_cfdi_id;
$modelo_factura->registro['cat_sat_moneda_id']              = $cat_sat_moneda_id;
$modelo_factura->registro['com_tipo_cambio_id']             = $com_tipo_cambio_id;
$modelo_factura->registro['cat_sat_tipo_de_comprobante_id'] = 1;

if (!isset($_FILES['documento'])) {
    $_FILES['documento'] = ['name' => ''];
}

$r_alta = $modelo_factura->alta_bd();

if (errores::$error) {
    echo json_encode([
        'STS'   => 'error',
        'MSG'   => 'Error al insertar la factura en el sistema',
        'DEBUG' => $r_alta
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$fc_factura_id = (int)$r_alta->registro_id;


// =========================================================
// PASO 11. RESPUESTA
// =========================================================

echo json_encode([
    'STS' => 'ok',
    'MSG' => 'Factura creada exitosamente',
    'FID' => $fc_factura_id,
    'RFC' => $rfc_cliente,
], JSON_UNESCAPED_UNICODE);
exit;