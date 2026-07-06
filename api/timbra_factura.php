<?php

use base\conexion;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\fc_cfdi_sellado;
use gamboamartin\facturacion\models\fc_cuenta_predial;
use gamboamartin\facturacion\models\fc_factura;
use gamboamartin\facturacion\models\fc_factura_documento;
use gamboamartin\facturacion\models\fc_factura_etapa;
use gamboamartin\facturacion\models\fc_factura_relacionada;
use gamboamartin\facturacion\models\fc_partida;
use gamboamartin\facturacion\models\fc_relacion;
use gamboamartin\facturacion\models\fc_retenido;
use gamboamartin\facturacion\models\fc_traslado;
use gamboamartin\facturacion\models\fc_uuid_fc;

chdir(__DIR__ . '/..');
require "init.php";
require 'vendor/autoload.php';
require_once __DIR__ . '/seguridad_edpoint.php';

use config\generales;

header('Content-Type: application/json; charset=utf-8');

$con  = new conexion();
$link = conexion::$link;

// TODO: crear generales::$accion_id_timbrar_factura cuando exista la acción
$accion_id = generales::$accion_id_timbrar_factura;

// ── paso 1. PARAMETROS DE ENTRADA ──

$telefono_whatsapp = trim($_GET['telefono_whatsapp'] ?? $_GET['telefono'] ?? '');
$folio             = trim($_GET['FL'] ?? $_GET['folio'] ?? '');

// ── paso 2. VALIDACIONES BASICAS ──

if ($telefono_whatsapp === '') {
    echo json_encode([
        'STS' => 'error',
        'MSG' => 'El telefono de WhatsApp es obligatorio'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($folio === '') {
    echo json_encode([
        'STS' => 'datos_incompletos',
        'MSG' => 'El folio de la factura es obligatorio para timbrar'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── paso 3. SEGURIDAD ──

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

// ── paso 4. RESOLUCION DE FACTURA POR FOLIO ──

$modelo_factura = new fc_factura($link);

$r_factura = $modelo_factura->filtro_and(filtro: [
    'fc_factura.folio' => $folio
]);

if (errores::$error || $r_factura->n_registros === 0) {
    echo json_encode([
        'STS' => 'no_encontrado',
        'MSG' => 'No se encontró ninguna factura con el folio proporcionado'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($r_factura->n_registros > 1) {
    echo json_encode([
        'STS' => 'ambiguo',
        'MSG' => 'Se encontraron múltiples facturas con ese folio'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$registro = $r_factura->registros[0];
$fc_factura_id = (int)($registro['fc_factura_id'] ?? 0);

if ($fc_factura_id <= 0) {
    echo json_encode([
        'STS' => 'error',
        'MSG' => 'No fue posible resolver el ID de la factura'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// paso 5. TIMBRADO — LLAMADA DIRECTA AL MODELO 
// Se instancian los modelos auxiliares a mano (mismo patrón que el
// controller hijo) y se llama $modelo_factura->timbra_xml() sin pasar
// por el controlador web, que depende de $_SESSION, header() y exit.
// verifica_permite_transaccion (etapa) y la guarda de duplicado en
// fc_cfdi_sellado son validaciones internas de timbra_xml() — no hay
// que replicarlas aquí.

$modelo_documento   = new fc_factura_documento($link);
$modelo_etapa       = new fc_factura_etapa($link);
$modelo_partida     = new fc_partida($link);
$modelo_predial     = new fc_cuenta_predial($link);
$modelo_relacion    = new fc_relacion($link);
$modelo_relacionada = new fc_factura_relacionada($link);
$modelo_retencion   = new fc_retenido($link);
$modelo_sello       = new fc_cfdi_sellado($link);
$modelo_traslado    = new fc_traslado($link);
$modelo_uuid_ext    = new fc_uuid_fc($link);

$r_timbra = $modelo_factura->timbra_xml(
    modelo_documento:   $modelo_documento,
    modelo_etapa:       $modelo_etapa,
    modelo_partida:     $modelo_partida,
    modelo_predial:     $modelo_predial,
    modelo_relacion:    $modelo_relacion,
    modelo_relacionada: $modelo_relacionada,
    modelo_retencion:   $modelo_retencion,
    modelo_sello:       $modelo_sello,
    modelo_traslado:    $modelo_traslado,
    modelo_uuid_ext:    $modelo_uuid_ext,
    registro_id:        $fc_factura_id
);

if (errores::$error) {
    $detalle_texto = is_string($r_timbra) ? $r_timbra : json_encode($r_timbra);
    $mensaje_usuario = 'No fue posible timbrar la factura';

    if (stripos($detalle_texto, 'ya ha sido timbrada') !== false) {
        $mensaje_usuario = 'Esta factura ya fue timbrada anteriormente';
    } elseif (stripos($detalle_texto, 'no se permite') !== false) {
        $mensaje_usuario = 'La factura no se puede timbrar en su etapa actual';
    }

    echo json_encode([
        'STS' => 'error',
        'MSG' => $mensaje_usuario,
        'DEBUG' => $detalle_texto
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── paso 6. POST-TIMBRADO — AGENTE DE OPERACION ──
// Replica lo que hace el controller hijo después de parent::timbra_xml():
// registrar qué usuario ejecutó el timbrado.

$r_agente = $modelo_factura->actualiza_agente_operacion_en_fc_factura(
    fc_factura_id: $fc_factura_id,
    campo: 'agente_operacion_timbrado_id'
);

if (errores::$error) {
    // El timbrado ya se ejecutó — no revertimos. Solo avisamos.
    echo json_encode([
        'STS' => 'ok_parcial',
        'MSG' => 'La factura se timbró correctamente pero no se registró el agente de operación',
        'FL'  => $registro['fc_factura_folio'] ?? $folio,
        'UUID' => $r_timbra['uuid'] ?? ''
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── paso 7. RESPUESTA ──

echo json_encode([
    'STS'  => 'ok',
    'MSG'  => 'Factura timbrada exitosamente',
    'FL'   => $registro['fc_factura_folio'] ?? $folio,
    'UUID' => $r_timbra['uuid'] ?? '',
    'FID'  => $fc_factura_id
], JSON_UNESCAPED_UNICODE);
exit;