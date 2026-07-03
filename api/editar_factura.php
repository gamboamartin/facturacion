<?php
use base\conexion;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\fc_factura;

chdir(__DIR__ . '/..');
require "init.php";
require 'vendor/autoload.php';
require_once __DIR__ . '/seguridad_edpoint.php';
require_once __DIR__ . '/catalogos_sat.php';

use config\generales;

header('Content-Type: application/json; charset=utf-8');

$con  = new conexion();
$link = conexion::$link;

// TODO: crear generales::$accion_id_editar_factura cuando exista la acción
$accion_id = 0;

// paso 1. PARAMETROS DE ENTRADA

$telefono_whatsapp = trim($_GET['telefono_whatsapp'] ?? $_GET['telefono'] ?? '');
$folio              = trim($_GET['FL'] ?? $_GET['folio'] ?? '');
$rfc_cliente        = strtoupper(trim($_GET['RFC'] ?? ''));
$forma_pago         = trim($_GET['FP'] ?? '');
$metodo_pago        = trim($_GET['MP'] ?? '');
$uso_cfdi           = trim($_GET['UC'] ?? '');

// paso 2. VALIDACIONES BASICAS

if ($telefono_whatsapp === '') {
    echo json_encode([
        'STS' => 'error',
        'MSG' => 'El telefono de WhatsApp es obligatorio'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($folio === '' && $rfc_cliente === '') {
    echo json_encode([
        'STS' => 'datos_incompletos',
        'MSG' => 'Indica el folio de la factura o el RFC del cliente para identificarla'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// paso 3. SEGURIDAD

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

// paso 4. RESOLUCION DE fc_factura_id — FOLIO O RFC
// Si viene folio, se filtra por folio (con RFC como cruce si también viene).
// Si no viene folio pero sí RFC, se filtra solo por RFC — si resulta en más
// de una factura, se responde 'ambiguo' pidiendo también el folio. Esto
// garantiza que modifica_bd() SIEMPRE opera sobre un único ID, nunca sobre
// un conjunto.

$filtro_factura = [];

if ($folio !== '') {
    $filtro_factura['fc_factura.folio'] = $folio;
    if ($rfc_cliente !== '') {
        $filtro_factura['com_cliente.rfc'] = $rfc_cliente;
    }
} else {
    $filtro_factura['com_cliente.rfc'] = $rfc_cliente;
}

$modelo_factura = new fc_factura($link);
$r_factura = $modelo_factura->filtro_and(filtro: $filtro_factura);

if (errores::$error || $r_factura->n_registros === 0) {
    echo json_encode([
        'STS' => 'no_encontrado',
        'MSG' => 'No se encontró ninguna factura con los datos proporcionados'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($r_factura->n_registros > 1) {
    echo json_encode([
        'STS' => 'ambiguo',
        'MSG' => 'Ese cliente tiene más de una factura. Indica también el folio para identificar cuál editar.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$fc_factura_id = (int)($r_factura->registros[0]['fc_factura_id'] ?? 0);

if ($fc_factura_id <= 0) {
    echo json_encode([
        'STS' => 'error',
        'MSG' => 'No fue posible resolver el ID de la factura'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// paso 5. CONSTRUCCION DEL REGISTRO PARCIAL + RESOLUCION DE CATALOGOS
// Whitelist confirmado por la empresa: solo FP, MP, UC.

$registro = [];

if ($forma_pago !== '') {
    $cat_sat_forma_pago_id = resuelve_catalogo($link, 'cat_sat_forma_pago', $forma_pago);
    if ($cat_sat_forma_pago_id <= 0) {
        echo json_encode([
            'STS' => 'catalogo_no_encontrado',
            'MSG' => "No se encontró la forma de pago: $forma_pago"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $registro['cat_sat_forma_pago_id'] = $cat_sat_forma_pago_id;
}

if ($metodo_pago !== '') {
    $cat_sat_metodo_pago_id = resuelve_catalogo($link, 'cat_sat_metodo_pago', $metodo_pago);
    if ($cat_sat_metodo_pago_id <= 0) {
        echo json_encode([
            'STS' => 'catalogo_no_encontrado',
            'MSG' => "No se encontró el método de pago: $metodo_pago"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $registro['cat_sat_metodo_pago_id'] = $cat_sat_metodo_pago_id;
}

if ($uso_cfdi !== '') {
    $cat_sat_uso_cfdi_id = resuelve_catalogo($link, 'cat_sat_uso_cfdi', $uso_cfdi);
    if ($cat_sat_uso_cfdi_id <= 0) {
        echo json_encode([
            'STS' => 'catalogo_no_encontrado',
            'MSG' => "No se encontró el uso CFDI: $uso_cfdi"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $registro['cat_sat_uso_cfdi_id'] = $cat_sat_uso_cfdi_id;
}

if (count($registro) === 0) {
    echo json_encode([
        'STS' => 'datos_incompletos',
        'MSG' => 'No se recibió ningún campo para editar (FP, MP o UC)'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// paso 6. EJECUTAR modifica_bd
// verifica_permite_transaccion queda en su default (true): el framework
// bloquea automáticamente edición si la factura está en etapa TIMBRADO
// o CANCELADO (confirmado en _transacciones_fc::valida_permite_transaccion).
// NO desactivar este parámetro bajo ninguna circunstancia.

$r_modifica = $modelo_factura->modifica_bd(
    registro: $registro,
    id: $fc_factura_id
);

if (errores::$error) {
    $detalle_texto = json_encode($r_modifica);
    $mensaje_usuario = 'No se pudo actualizar la factura';

    if (stripos($detalle_texto, 'no se permite') !== false) {
        $mensaje_usuario = 'Esta factura ya no se puede editar porque está timbrada o cancelada';
    }

    echo json_encode([
        'STS' => 'error',
        'MSG' => $mensaje_usuario
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// paso 7. RESPUESTA

echo json_encode([
    'STS' => 'ok',
    'MSG' => 'Factura actualizada exitosamente',
    'FID' => $fc_factura_id,
], JSON_UNESCAPED_UNICODE);
exit;