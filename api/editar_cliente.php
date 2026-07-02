<?php
use base\conexion;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\com_cliente;
use gamboamartin\direccion_postal\models\dp_municipio;

chdir(__DIR__ . '/..');
require "init.php";
require 'vendor/autoload.php';
require_once __DIR__ . '/seguridad_edpoint.php';

use config\generales;

header('Content-Type: application/json; charset=utf-8');

$con  = new conexion();
$link = conexion::$link;

$accion_id = generales::$accion_id_editar_cliente;

// paso 1. PARAMETROS DE ENTRADA

$telefono_whatsapp = trim($_GET['telefono_whatsapp'] ?? $_GET['telefono'] ?? '');
$rfc                = strtoupper(trim($_GET['RFC'] ?? $_GET['rfc'] ?? ''));
$razon_social       = trim($_GET['RS']  ?? '');
$telefono           = trim($_GET['TEL'] ?? '');
$cp                 = trim($_GET['CP']  ?? '');
$calle              = trim($_GET['CAL'] ?? '');
$numero_exterior    = trim($_GET['NEX'] ?? '');
$numero_interior    = trim($_GET['NIN'] ?? '');
$colonia            = trim($_GET['COL'] ?? '');
$municipio_texto    = trim($_GET['MUN'] ?? '');

// paso 2. VALIDACION DE TELEFONO WHATSAPP Y RFC

if ($telefono_whatsapp === '') {
    echo json_encode([
        'STS' => 'error',
        'MSG' => 'Para editar el cliente es necesario enviar el telefono de WhatsApp validado'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($rfc === '') {
    echo json_encode([
        'STS' => 'datos_incompletos',
        'MSG' => 'El RFC del cliente es obligatorio para identificar a quién editar'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// paso 3. SEGURIDAD (idéntico patrón a alta_cliente)

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

// CRÍTICO: com_cliente::aplica_seguridad() lee $_SESSION['grupo_id']
// directamente en su constructor.
$_SESSION['usuario_id'] = $r_seguridad['adm_usuario_id'];
$_SESSION['grupo_id']   = $r_seguridad['adm_grupo_id'];

// paso 4. RESOLUCION DE com_cliente_id POR RFC

$modelo_cliente = new com_cliente($link);

$r_existe = $modelo_cliente->filtro_and(filtro: ['com_cliente.rfc' => $rfc]);
if (errores::$error) {
    echo json_encode([
        'STS' => 'error',
        'MSG' => 'Error al buscar el cliente'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($r_existe->n_registros === 0) {
    echo json_encode([
        'STS' => 'no_encontrado',
        'MSG' => 'No se encontró ningún cliente con ese RFC'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$com_cliente_id = (int)($r_existe->registros[0]['com_cliente_id'] ?? 0);

if ($com_cliente_id <= 0) {
    echo json_encode([
        'STS' => 'error',
        'MSG' => 'No fue posible resolver el ID del cliente'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// paso 5. CONSTRUCCION DEL REGISTRO PARCIAL

$mapa_campos = [
    'RS'  => 'razon_social',
    'CP'  => 'cp',
    'CAL' => 'calle',
    'NEX' => 'numero_exterior',
    'NIN' => 'numero_interior',
    'COL' => 'colonia',
    'MUN' => 'municipio',
    'TEL' => 'telefono',
];

$registro = [];
foreach ($mapa_campos as $campo_whatsapp => $campo_modelo) {
    if (isset($_GET[$campo_whatsapp]) && trim($_GET[$campo_whatsapp]) !== '') {
        $registro[$campo_modelo] = trim($_GET[$campo_whatsapp]);
    }
}

if (count($registro) === 0) {
    echo json_encode([
        'STS' => 'datos_incompletos',
        'MSG' => 'No se recibió ningún campo para editar'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// paso 6. RESOLUCION CONDICIONAL DE dp_municipio_id
// modifica_bd() NO resuelve dp_municipio_id (confirmado por código:
// no está en la lista $foraneas de inicializa_foraneas ni en
// limpia_campos). Si se edita MUN, hay que resolver y enviar
// dp_municipio_id explícitamente o queda desincronizado del texto.

if ($municipio_texto !== '') {
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

    $registro['dp_municipio_id'] = $dp_municipio_id;
}

// paso 7. EJECUTAR modifica_bd

// upd_sucursales() se dispara automáticamente dentro de modifica_bd().
// Comportamiento CONFIRMADO como esperado.
$r_modifica = $modelo_cliente->modifica_bd(
    registro: $registro,
    id: $com_cliente_id
);

if (errores::$error) {
    echo json_encode([
        'STS'   => 'error',
        'MSG'   => 'No se pudo actualizar el cliente',
        'DEBUG' => $r_modifica
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// paso 8. RESPUESTA

echo json_encode([
    'STS' => 'ok',
    'MSG' => 'Cliente actualizado exitosamente',
    'CID' => $com_cliente_id,
    'RFC' => $rfc,
], JSON_UNESCAPED_UNICODE);
exit;