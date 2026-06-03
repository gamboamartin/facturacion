<?php

use base\conexion;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\com_cliente;
use gamboamartin\direccion_postal\models\dp_municipio;
use Yosymfony\Toml\Toml;

chdir(__DIR__ . '/..');
require "init.php";
require 'vendor/autoload.php';
require_once __DIR__ . '/seguridad_edpoint.php';

use config\generales;

header('Content-Type: application/json; charset=utf-8');

$con  = new conexion();
$link = conexion::$link;

$accion_id = generales::$accion_id_alta_cliente;

// paso 1. PARAMETROS DE ENTRADA 

$telefono_whatsapp = trim($_GET['telefono_whatsapp'] ?? $_GET['telefono'] ?? '');
$rfc               = strtoupper(trim($_GET['RFC'] ?? $_GET['rfc'] ?? ''));
$razon_social      = trim($_GET['RS']  ?? '');
$telefono          = trim($_GET['TEL'] ?? '');
$cp                = trim($_GET['CP']  ?? '');
$calle             = trim($_GET['CAL'] ?? '');
$numero_exterior   = trim($_GET['NEX'] ?? '');
$numero_interior   = trim($_GET['NIN'] ?? '');
$colonia           = trim($_GET['COL'] ?? '');
$municipio_texto   = trim($_GET['MUN'] ?? '');
$tipo_persona_id   = (int)($_GET['TPI'] ?? 6);

// paso 2. VALIDACION DE TELEFONO WHATSAPP 

if ($telefono_whatsapp === '') {
    echo json_encode([
        'STS' => 'error',
        'MSG' => 'Para realizar el alta es necesario enviar el telefono de WhatsApp validado'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// paso 3. VALIDACION DE CAMPOS OBLIGATORIOS 

$campos_obligatorios = [
    'RFC' => $rfc,
    'RS'  => $razon_social,
    'TEL' => $telefono,
    'CP'  => $cp,
    'CAL' => $calle,
    'NEX' => $numero_exterior,
    'COL' => $colonia,
    'MUN' => $municipio_texto,
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

// paso 4. SEGURIDAD 

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

// paso 5. TOML 

$toml_path = __DIR__ . '/../config/escenario/alta_cliente.toml';

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

// paso 6. DICCIONARIO 

$diccionario_path = __DIR__ . '/../config/json/alta_cliente.json';

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

$campos_diccionario_exponibles = [];

if (isset($diccionario['campos']) && is_array($diccionario['campos'])) {
    foreach ($diccionario['campos'] as $clave => $campo) {
        if (isset($campo['exponer']) && $campo['exponer'] === true) {
            $campos_diccionario_exponibles[] = $clave;
        }
    }
}

$campos_salida_permitidos = $escenario['output']['permitidos'] ?? [];
$filtrar_campos           = (bool)($escenario['comportamiento']['filtrar_campos'] ?? false);
$verificar_duplicado      = (bool)($escenario['comportamiento']['verificar_duplicado'] ?? true);

// paso 7. VERIFICACION DE DUPLICADO 

if ($verificar_duplicado) {
    $modelo_cliente = new com_cliente($link);

    $r_existe = $modelo_cliente->filtro_and(filtro: ['com_cliente.rfc' => $rfc]);
    if (errores::$error) {
        echo json_encode([
            'STS' => 'error',
            'MSG' => 'Error al verificar si el cliente ya existe'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($r_existe->n_registros > 0) {
        echo json_encode([
            'STS' => 'ya_existe',
            'MSG' => 'Ya existe un cliente registrado con ese RFC',
            'CID' => $r_existe->registros[0]['com_cliente_id'] ?? '',
            'COD' => $r_existe->registros[0]['com_cliente_codigo'] ?? ''
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// paso 8. RESOLUCION DE MUNICIPIO 

$r_municipio = (new dp_municipio($link))->filtro_and(
    filtro: ['dp_municipio.descripcion' => strtoupper($municipio_texto)]
);

if (errores::$error || $r_municipio->n_registros === 0) {
    echo json_encode([
        'STS' => 'municipio_no_encontrado',
        'MSG' => "No se encontro el municipio: $municipio_texto"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$dp_municipio_id = (int)($r_municipio->registros[0]['dp_municipio_id'] ?? 0);

if ($dp_municipio_id <= 0) {
    echo json_encode([
        'STS' => 'municipio_no_encontrado',
        'MSG' => 'No fue posible resolver el ID del municipio'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// paso 9. ALTA DEL CLIENTE 

$modelo_alta = new com_cliente($link);

$modelo_alta->registro['rfc']                     = $rfc;
$modelo_alta->registro['razon_social']             = $razon_social;
$modelo_alta->registro['telefono']                 = $telefono;
$modelo_alta->registro['cp']                       = $cp;
$modelo_alta->registro['calle']                    = $calle;
$modelo_alta->registro['numero_exterior']          = $numero_exterior;
$modelo_alta->registro['numero_interior']          = $numero_interior;
$modelo_alta->registro['colonia']                  = $colonia;
$modelo_alta->registro['dp_municipio_id']          = $dp_municipio_id;
$modelo_alta->registro['cat_sat_tipo_persona_id']  = $tipo_persona_id;

if (!isset($_FILES['documento'])) {
    $_FILES['documento'] = ['name' => ''];
}

$r_alta = $modelo_alta->alta_bd();

if (errores::$error) {
    echo json_encode([
        'STS'   => 'error',
        'MSG'   => 'Error al insertar el cliente en el sistema',
        'DEBUG' => $r_alta
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// paso 10. RESPUESTA 

$respuesta = [
    'STS' => 'ok',
    'MSG' => 'Cliente registrado exitosamente',
    'CID' => $r_alta->registro_id        ?? '',
    'COD' => $r_alta->registro_obj->codigo ?? $r_alta->registro['com_cliente_codigo'] ?? '',
    'RFC' => $rfc,
    'RS'  => $razon_social,
];

if ($filtrar_campos) {
    if (!empty($campos_salida_permitidos)) {
        $respuesta = array_intersect_key($respuesta, array_flip($campos_salida_permitidos));
    }
    if (!empty($campos_diccionario_exponibles)) {
        $respuesta = array_intersect_key($respuesta, array_flip($campos_diccionario_exponibles));
    }
}

echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
exit;