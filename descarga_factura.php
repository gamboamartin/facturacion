<?php

use base\conexion;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\controllers\_doctos;
use gamboamartin\facturacion\models\fc_cfdi_sellado;
use gamboamartin\facturacion\models\fc_cuenta_predial;
use gamboamartin\facturacion\models\fc_factura;
use gamboamartin\facturacion\controllers\controlador_fc_factura;
use gamboamartin\facturacion\models\fc_factura_documento;
use gamboamartin\facturacion\models\fc_factura_relacionada;
use gamboamartin\facturacion\models\fc_partida;
use gamboamartin\facturacion\models\fc_relacion;
use gamboamartin\facturacion\models\fc_retenido;
use gamboamartin\facturacion\models\fc_traslado;
use gamboamartin\facturacion\models\fc_uuid_fc;
use Yosymfony\Toml\Toml;

header('Content-Type: application/json; charset=utf-8');

$_SESSION['usuario_id'] = 2;
$_SESSION['grupo_id'] = 2;

require "init.php";
require 'vendor/autoload.php';

$con = new conexion();
$link = conexion::$link;


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


$diccionario_path = __DIR__ . '/config/json/fc_factura.json';

if (!file_exists($diccionario_path)) {
    echo json_encode(array(
        'STS' => 'error',
        'MSG' => 'No existe el diccionario de datos'
    ), JSON_UNESCAPED_UNICODE);
    exit;
}

$diccionario_json = file_get_contents($diccionario_path);
$diccionario = json_decode($diccionario_json, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(array(
        'STS' => 'error',
        'MSG' => 'No fue posible leer el diccionario de datos'
    ), JSON_UNESCAPED_UNICODE);
    exit;
}

$campos_diccionario_exponibles = array();

if (isset($diccionario['campos']) && is_array($diccionario['campos'])) {
    foreach ($diccionario['campos'] as $clave => $campo) {
        if (isset($campo['exponer']) && $campo['exponer'] === true) {
            $campos_diccionario_exponibles[] = $clave;
        }
    }
}


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


$modelo = new fc_factura($link);


$filtro = array();

if ($folio !== '') {
    $filtro['fc_factura.folio'] = $folio;
} else {
    $filtro['fc_factura.folio_fiscal'] = $uuid;
}


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


$protocolo = 'http://';

if (
    (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
    (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
) {
    $protocolo = 'https://';
}

$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

$base_path = '/';

if (
    strpos($host, 'localhost') !== false ||
    strpos($host, '127.0.0.1') !== false
) {
    $base_path = '/facturacion/';
}

$base_url = $protocolo . $host . $base_path;


$url_pdf = '';
$url_xml = '';
$pdf_filename = '';

if ($doc === 'pdf') {

    $_GET['registro_id'] = $fc_factura_id;

    ob_start();

    $ruta_pdf = '';

    $modelo_documento = (new fc_factura_documento(link: $link));
    $modelo_entidad = $modelo;
    $modelo_partida = (new fc_partida(link: $link));
    $modelo_predial = (new fc_cuenta_predial(link: $link));
    $modelo_relacion = (new fc_relacion(link: $link));
    $modelo_relacionada = (new fc_factura_relacionada(link: $link));
    $modelo_retencion = (new fc_retenido(link: $link));
    $modelo_sello = (new fc_cfdi_sellado(link: $link));
    $modelo_traslado = (new fc_traslado(link: $link));
    $modelo_uuid_ext = (new fc_uuid_fc(link: $link));

    $pdf = (new _doctos())->pdf(descarga: false, guarda: true, modelo_documento: $modelo_documento,
        modelo_entidad:  $modelo_entidad, modelo_partida: $modelo_partida,
        modelo_predial:  $modelo_predial, modelo_relacion: $modelo_relacion,
        modelo_relacionada:  $modelo_relacionada, modelo_retencion:  $modelo_retencion,
        modelo_sello:  $modelo_sello, modelo_traslado:  $modelo_traslado,
        modelo_uuid_ext: $modelo_uuid_ext, row_entidad_id: $fc_factura_id);

    if(errores::$error){
        echo json_encode(array(
            'STS' => 'error',
            'MSG' => 'No fue posible generar el PDF de la factura',
            'DEBUG' => $pdf
        ), JSON_UNESCAPED_UNICODE);
        exit;
    }

    $ruta_pdf = $pdf->registro_obj->doc_documento_ruta_absoluta;


    $salida_pdf = ob_get_clean();

    if ($salida_pdf !== '') {
        // Evita que cualquier salida accidental rompa el JSON de n8n
    }

    if (errores::$error || !is_string($ruta_pdf) || !file_exists($ruta_pdf)) {
        echo json_encode(array(
            'STS' => 'error',
            'MSG' => 'No fue posible generar el PDF de la factura',
            'DEBUG' => $salida_pdf
        ), JSON_UNESCAPED_UNICODE);
        exit;
    }

    $pdf_filename = basename($ruta_pdf);
    $url_pdf = $base_url . 'archivos/' . $pdf_filename;
}

if ($doc === 'xml') {
    $url_xml = $base_url . 'index.php?seccion=fc_factura&accion=descarga_xml&registro_id=' . $fc_factura_id;
}

$pdf_disponible = ($url_pdf !== '');
$xml_disponible = ($url_xml !== '');


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
    'URL_XML' => $url_xml,
    'PDF_FILENAME' => $pdf_filename
);


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