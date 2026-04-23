<?php

use base\conexion;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\fc_factura;
use Yosymfony\Toml\Toml;

header('Content-Type: application/json; charset=utf-8');

$_SESSION['usuario_id'] = 2;

require "init.php";
require 'vendor/autoload.php';

$con = new conexion();
$link = conexion::$link;

/**
 * =========================================================
 * CARGA DE ESCENARIO TOML
 * =========================================================
 */

$toml_path = __DIR__ . '/config/escenario/descarga_factura.toml';

if (!file_exists($toml_path)) {
    echo json_encode(array(
        'STS' => 'error',
        'MSG' => 'No existe el archivo de escenario TOML'
    ), JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $escenario = Toml::ParseFile($toml_path);
} catch (Throwable $e) {
    echo json_encode(array(
        'STS' => 'error',
        'MSG' => 'No fue posible leer el archivo TOML del escenario'
    ), JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * =========================================================
 * CONFIG TOML
 * =========================================================
 */

$documentos_permitidos = array('pdf', 'xml');
if (isset($escenario['input']['documentos_permitidos']) && is_array($escenario['input']['documentos_permitidos'])) {
    $documentos_permitidos = $escenario['input']['documentos_permitidos'];
}

$etapas_validas = array('TIMBRADO', 'ACTIVO');
if (isset($escenario['reglas']['etapas_validas']) && is_array($escenario['reglas']['etapas_validas'])) {
    $etapas_validas = $escenario['reglas']['etapas_validas'];
}

$campos_salida_permitidos = array();
if (isset($escenario['output']['permitidos']) && is_array($escenario['output']['permitidos'])) {
    $campos_salida_permitidos = $escenario['output']['permitidos'];
}

$filtrar_campos = false;
if (isset($escenario['comportamiento']['filtrar_campos'])) {
    $filtrar_campos = (bool)$escenario['comportamiento']['filtrar_campos'];
}

/**
 * =========================================================
 * VALIDACIONES DE ENTRADA
 * =========================================================
 */

if (!isset($_GET['doc'])) {
    echo 'no existe doc en GET';
    exit;
}

$doc = trim($_GET['doc']);

if ($doc === '') {
    echo 'doc vacio';
    exit;
}

$doc = strtolower($doc);

if (!in_array($doc, $documentos_permitidos, true)) {
    echo 'doc no valido';
    exit;
}

$folio = '';
$uuid = '';
$rfc = '';

if (isset($_GET['folio'])) {
    $folio = trim($_GET['folio']);
}

if (isset($_GET['uuid'])) {
    $uuid = trim($_GET['uuid']);
}

if (isset($_GET['rfc'])) {
    $rfc = trim($_GET['rfc']);
}

if ($folio === '' && $uuid === '') {
    echo json_encode(array(
        'STS' => 'error',
        'MSG' => 'Debes enviar el folio de la factura'
    ), JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * =========================================================
 * MODELO
 * =========================================================
 */

$modelo = new fc_factura($link);

/**
 * =========================================================
 * FILTRO PRINCIPAL
 * =========================================================
 */

$filtro = array();

if ($folio !== '') {
    $filtro['fc_factura.folio'] = $folio;
} else {
    $filtro['fc_factura.folio_fiscal'] = $uuid;
}

/**
 * =========================================================
 * CONSULTA
 * =========================================================
 */

$rs = $modelo->filtro_and(filtro: $filtro);
if (errores::$error) {
    print_r($rs);
    exit;
}

$n_registros = $rs->n_registros;

if ($n_registros === 0) {
    echo json_encode(array(
        'STS' => 'no_encontrado',
        'MSG' => 'No se encontro ninguna factura con los datos proporcionados'
    ), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($n_registros > 1) {
    echo json_encode(array(
        'STS' => 'ambiguo',
        'MSG' => 'Se encontraron multiples facturas con los datos proporcionados'
    ), JSON_UNESCAPED_UNICODE);
    exit;
}

$registro = $rs->registros[0];

/**
 * =========================================================
 * RFC SECUNDARIO
 * =========================================================
 */

if ($rfc !== '') {
    $rfc_bd = '';

    if (isset($registro['com_cliente_rfc'])) {
        $rfc_bd = trim($registro['com_cliente_rfc']);
    }

    if ($rfc_bd !== '' && strtoupper($rfc_bd) !== strtoupper($rfc)) {
        echo json_encode(array(
            'STS' => 'no_encontrado',
            'MSG' => 'La factura existe pero el RFC no coincide'
        ), JSON_UNESCAPED_UNICODE);
        exit;
    }
}

/**
 * =========================================================
 * VALIDAR ETAPA DESDE TOML
 * =========================================================
 */

$etapa = '';

if (isset($registro['fc_factura_etapa'])) {
    $etapa = trim($registro['fc_factura_etapa']);
}

$etapas_validas_normalizadas = array_map('strtoupper', $etapas_validas);

if ($etapa === '' || !in_array(strtoupper($etapa), $etapas_validas_normalizadas, true)) {
    echo json_encode(array(
        'STS' => 'no_disponible',
        'MSG' => 'La factura existe pero no esta lista para descarga'
    ), JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * =========================================================
 * ID DE FACTURA
 * =========================================================
 */

$fc_factura_id = 0;

if (isset($registro['fc_factura_id'])) {
    $fc_factura_id = (int)$registro['fc_factura_id'];
}

if ($fc_factura_id <= 0) {
    echo json_encode(array(
        'STS' => 'error',
        'MSG' => 'No fue posible obtener el id interno de la factura'
    ), JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * =========================================================
 * URLS OFICIALES DEL SISTEMA
 * =========================================================
 */

$base_url = 'http://localhost/facturacion/';
$url_pdf = '';
$url_xml = '';

if ($doc === 'pdf') {
    $url_pdf = $base_url . 'index.php?seccion=fc_factura&accion=genera_pdf&registro_id=' . $fc_factura_id;
}

if ($doc === 'xml') {
    $url_xml = $base_url . 'index.php?seccion=fc_factura&accion=descarga_xml&registro_id=' . $fc_factura_id;
}

$pdf_disponible = ($url_pdf !== '');
$xml_disponible = ($url_xml !== '');

/**
 * =========================================================
 * RESPUESTA BASE
 * =========================================================
 */

$respuesta = array(
    'STS' => 'ok',
    'MSG' => 'Factura localizada',
    'FL' => $registro['fc_factura_folio'] ?? '',
    'TOT' => $registro['fc_factura_total'] ?? '',
    'FEC' => $registro['fc_factura_fecha'] ?? '',
    'RS' => $registro['com_cliente_razon_social'] ?? '',
    'RFC' => $registro['com_cliente_rfc'] ?? '',
    'ETP' => $registro['fc_factura_etapa'] ?? '',
    'PDF' => $pdf_disponible,
    'XML' => $xml_disponible,
    'URL_PDF' => $url_pdf,
    'URL_XML' => $url_xml
);

/**
 * =========================================================
 * FILTRAR RESPUESTA SEGUN TOML
 * =========================================================
 */

if ($filtrar_campos && !empty($campos_salida_permitidos)) {
    $respuesta = array_intersect_key($respuesta, array_flip($campos_salida_permitidos));
}

/**
 * =========================================================
 * RESPUESTA FINAL
 * =========================================================
 */

echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);

exit;