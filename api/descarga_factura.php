<?php

use base\conexion;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\controllers\_doctos;
use config\pac;
use gamboamartin\facturacion\models\fc_cfdi_sellado;
use gamboamartin\facturacion\models\fc_cuenta_predial;
use gamboamartin\facturacion\models\fc_factura;
use gamboamartin\facturacion\controllers\controlador_fc_factura;
use gamboamartin\facturacion\models\fc_factura_documento;
use gamboamartin\facturacion\models\fc_factura_etapa;
use gamboamartin\facturacion\models\fc_factura_relacionada;
use gamboamartin\facturacion\models\fc_partida;
use gamboamartin\facturacion\models\fc_relacion;
use gamboamartin\facturacion\models\fc_retenido;
use gamboamartin\facturacion\models\fc_traslado;
use gamboamartin\facturacion\models\fc_uuid_fc;
use Yosymfony\Toml\Toml;

chdir(__DIR__ . '/..');
require "init.php";
require 'vendor/autoload.php';
require_once __DIR__ . '/seguridad_edpoint.php';

use config\generales;

$generales   = new generales();
$secret_key  = $generales->cache_secret_key;

$accion_id = generales::$accion_id_descarga_factura; 

header('Content-Type: application/json; charset=utf-8');

$con  = new conexion();
$link = conexion::$link;


if (!isset($_GET['doc'])) {
    echo json_encode(['STS' => 'error', 'MSG' => 'no existe doc en GET'], JSON_UNESCAPED_UNICODE);
    exit;
}

$doc = strtolower(trim($_GET['doc']));

if ($doc === '') {
    echo json_encode(['STS' => 'error', 'MSG' => 'doc vacio'], JSON_UNESCAPED_UNICODE);
    exit;
}

$folio             = trim($_GET['folio']             ?? '');
$uuid              = trim($_GET['uuid']              ?? '');
$rfc               = trim($_GET['rfc']               ?? '');
$telefono_whatsapp = trim($_GET['telefono_whatsapp'] ?? $_GET['telefono'] ?? '');

if ($folio === '' && $uuid === '') {
    echo json_encode(['STS' => 'error', 'MSG' => 'Debes enviar el folio de la factura'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($telefono_whatsapp === '') {
    echo json_encode(['STS' => 'telefono_requerido', 'MSG' => 'Para descargar la factura es necesario enviar el telefono de WhatsApp validado'], JSON_UNESCAPED_UNICODE);
    exit;
}


$seguridad_endpoint = new SeguridadEndpoint($link);




$r_seguridad = $seguridad_endpoint->valida_adm_usuario_factura(
    telefono_whatsapp: $telefono_whatsapp,
    accion_id: $accion_id,
    folio: $folio !== '' ? $folio : $uuid,
    rfc: strtoupper(trim($rfc)),
    
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


$toml_path = __DIR__ . '/../config/escenario/descarga_factura.toml';

if (!file_exists($toml_path)) {
    echo json_encode(['STS' => 'error', 'MSG' => 'No existe el archivo de escenario TOML'], JSON_UNESCAPED_UNICODE);   
    exit;
}

try {
    $escenario = Toml::ParseFile($toml_path);
} catch (Throwable $e) {
    echo json_encode(['STS' => 'error', 'MSG' => 'No fue posible leer el archivo TOML del escenario'], JSON_UNESCAPED_UNICODE);
    exit;
}


$diccionario_path = __DIR__ . '/../config/json/fc_factura.json';

if (!file_exists($diccionario_path)) {
    echo json_encode(['STS' => 'error', 'MSG' => 'No existe el diccionario de datos'], JSON_UNESCAPED_UNICODE);
    exit;
}

$diccionario_json = file_get_contents($diccionario_path);
$diccionario      = json_decode($diccionario_json, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['STS' => 'error', 'MSG' => 'No fue posible leer el diccionario de datos'], JSON_UNESCAPED_UNICODE);
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

$documentos_permitidos = $escenario['input']['documentos_permitidos'] ?? ['pdf', 'xml'];
$etapas_validas        = $escenario['reglas']['etapas_validas']        ?? ['TIMBRADO', 'ACTIVO'];
$campos_salida_permitidos = $escenario['output']['permitidos']         ?? [];
$filtrar_campos        = (bool)($escenario['comportamiento']['filtrar_campos'] ?? false);

if (!in_array($doc, $documentos_permitidos, true)) {
    echo json_encode(['STS' => 'error', 'MSG' => 'doc no valido'], JSON_UNESCAPED_UNICODE);
    exit;
}


$modelo = new fc_factura($link);

$filtro = $folio !== ''
    ? ['fc_factura.folio'        => $folio]
    : ['fc_factura.folio_fiscal' => $uuid];

$rs = $modelo->filtro_and(filtro: $filtro);
if (errores::$error) {
    print_r($rs);
    exit;
}

if ($rs->n_registros === 0) {
    echo json_encode(['STS' => 'no_encontrado', 'MSG' => 'No se encontro ninguna factura con los datos proporcionados'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($rs->n_registros > 1) {
    echo json_encode(['STS' => 'ambiguo', 'MSG' => 'Se encontraron multiples facturas con los datos proporcionados'], JSON_UNESCAPED_UNICODE);
    exit;
}

$registro = $rs->registros[0];

if ($rfc !== '') {
    $rfc_bd = strtoupper(trim($registro['com_cliente_rfc'] ?? ''));
    if ($rfc_bd !== '' && $rfc_bd !== strtoupper($rfc)) {
        echo json_encode(['STS' => 'no_encontrado', 'MSG' => 'La factura existe pero el RFC no coincide'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}


$etapa = strtoupper(trim($registro['fc_factura_etapa'] ?? ''));
$etapas_validas_normalizadas = array_map('strtoupper', $etapas_validas);

if ($etapa === '' || !in_array($etapa, $etapas_validas_normalizadas, true)) {
    echo json_encode(['STS' => 'no_disponible', 'MSG' => 'La factura existe pero no esta lista para descarga'], JSON_UNESCAPED_UNICODE);
    exit;
}


$fc_factura_id = (int)($registro['fc_factura_id'] ?? 0);

if ($fc_factura_id <= 0) {
    echo json_encode(['STS' => 'error', 'MSG' => 'No fue posible obtener el id interno de la factura'], JSON_UNESCAPED_UNICODE);
    exit;
}

$protocolo = (
    (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
    (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
) ? 'https://' : 'http://';

$host      = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base_path = (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false)
    ? '/facturacion/'
    : '/';

$base_url = $protocolo . $host . $base_path;


$url_pdf      = '';
$url_xml      = '';
$pdf_filename = '';

if ($doc === 'pdf') {

    $_GET['registro_id'] = $fc_factura_id;
    ob_start();

    $pdf = (new _doctos())->pdf(
        descarga: false,
        guarda: true,
        modelo_documento:  new fc_factura_documento(link: $link),
        modelo_entidad:    $modelo,
        modelo_partida:    new fc_partida(link: $link),
        modelo_predial:    new fc_cuenta_predial(link: $link),
        modelo_relacion:   new fc_relacion(link: $link),
        modelo_relacionada: new fc_factura_relacionada(link: $link),
        modelo_retencion:  new fc_retenido(link: $link),
        modelo_sello:      new fc_cfdi_sellado(link: $link),
        modelo_traslado:   new fc_traslado(link: $link),
        modelo_uuid_ext:   new fc_uuid_fc(link: $link),
        row_entidad_id:    $fc_factura_id
    );

    $salida_pdf = ob_get_clean();

    if (errores::$error) {
        echo json_encode(['STS' => 'error', 'MSG' => 'No fue posible generar el PDF de la factura', 'DEBUG' => $pdf], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $ruta_pdf = $pdf->registro_obj->doc_documento_ruta_absoluta ?? '';

    if (!is_string($ruta_pdf) || !file_exists($ruta_pdf)) {
        echo json_encode(['STS' => 'error', 'MSG' => 'No fue posible generar el PDF de la factura', 'DEBUG' => $salida_pdf], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $payload = base64_encode(json_encode([
        'fc_factura_id' => $fc_factura_id,
        'folio'         => $registro['fc_factura_folio'] ?? '',
        'ruta_pdf'      => $ruta_pdf,
        'exp'           => time() + 600
    ]));

    $firma    = hash_hmac('sha256', $payload, $secret_key);
    $token    = $payload . '.' . $firma;
    $url_pdf  = $base_url . 'api/descarga_factura_archivo.php?token=' . urlencode($token);
    $pdf_filename = basename($ruta_pdf);
}

if ($doc === 'xml') {

    $r_xml_doc = (new fc_factura_documento(link: $link))->filtro_and(filtro: [
        'fc_factura.id'        => $fc_factura_id,
        'doc_tipo_documento.id' => 1
    ]);

    if (errores::$error || $r_xml_doc->n_registros === 0) {
        echo json_encode(['STS' => 'no_disponible', 'MSG' => 'La factura no tiene XML disponible', 'DEBUG' => $r_xml_doc], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $ruta_xml = $r_xml_doc->registros[0]['doc_documento_ruta_absoluta'] ?? '';

    if (!is_string($ruta_xml) || !file_exists($ruta_xml)) {
        echo json_encode(['STS' => 'error', 'MSG' => 'El XML existe en BD pero no físicamente'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $payload = base64_encode(json_encode([
        'fc_factura_id' => $fc_factura_id,
        'folio'         => $registro['fc_factura_folio'] ?? '',
        'ruta_xml'      => $ruta_xml,
        'exp'           => time() + 600
    ]));

    $firma   = hash_hmac('sha256', $payload, $secret_key);
    $token   = $payload . '.' . $firma;
    $url_xml = $base_url . 'api/descarga_factura_archivo.php?token=' . urlencode($token);
}


$respuesta = [
    'STS'          => 'ok',
    'MSG'          => 'Factura localizada',
    'FL'           => $registro['fc_factura_folio']        ?? '',
    'TOT'          => $registro['fc_factura_total']         ?? '',
    'FEC'          => $registro['fc_factura_fecha']         ?? '',
    'RS'           => $registro['com_cliente_razon_social'] ?? '',
    'RFC'          => $registro['com_cliente_rfc']          ?? '',
    'ETP'          => $registro['fc_factura_etapa']         ?? '',
    'PDF'          => ($url_pdf !== ''),
    'XML'          => ($url_xml !== ''),
    'URL_PDF'      => $url_pdf,
    'URL_XML'      => $url_xml,
    'PDF_FILENAME' => $pdf_filename
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