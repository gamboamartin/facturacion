<?php

use base\conexion;

chdir(__DIR__ . '/..');
require "init.php";
require 'vendor/autoload.php';

header('Content-Type: application/json; charset=utf-8');

$con  = new conexion();
$link = conexion::$link;

// paso 1. PARAMETROS DE ENTRADA

$accion            = strtolower(trim($_GET['accion'] ?? ''));
$telefono_whatsapp = preg_replace('/\D+/', '', trim($_GET['telefono_whatsapp'] ?? $_GET['telefono'] ?? ''));
$mensaje           = trim($_GET['mensaje'] ?? '');

// paso 2. VALIDACIONES BASICAS

if ($telefono_whatsapp === '') {
    echo json_encode([
        'STS' => 'error',
        'MSG' => 'El teléfono de WhatsApp es requerido'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!in_array($accion, ['resolver', 'registrar'], true)) {
    echo json_encode([
        'STS' => 'error',
        'MSG' => 'Acción no válida. Usar: resolver o registrar'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ============================================================
// ACCION: RESOLVER
// Consulta si hay estado activo y resuelve el paso actual
// ============================================================

if ($accion === 'resolver') {

    // Consultar estado
    $sql = "SELECT intent_activo, paso_actual, datos_parciales
            FROM tmp_conversacion_estado
            WHERE telefono = :telefono
            LIMIT 1";

    $stmt = $link->prepare($sql);
    $stmt->execute([':telefono' => $telefono_whatsapp]);
    $estado = $stmt->fetch(PDO::FETCH_ASSOC);

    // Sin estado activo: flujo normal
    if (!$estado) {
        echo json_encode([
            'STS'          => 'sin_estado',
            'tiene_estado' => false,
            'MSG'          => 'No hay estado activo para este teléfono'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $intent_activo  = $estado['intent_activo'];
    $paso_actual    = $estado['paso_actual'];
    $datos          = json_decode($estado['datos_parciales'] ?? '{}', true) ?: [];
    $mensaje_lower  = strtolower(trim($mensaje));

    // Detectar si el usuario quiere cancelar/salir del flujo
    $palabras_cancelar = ['cancelar', 'salir', 'dejalo', 'olvidalo', 'no quiero'];
    foreach ($palabras_cancelar as $palabra) {
        if (strpos($mensaje_lower, $palabra) !== false) {
            $sql_del = "DELETE FROM tmp_conversacion_estado WHERE telefono = :telefono";
            $link->prepare($sql_del)->execute([':telefono' => $telefono_whatsapp]);

            echo json_encode([
                'STS'          => 'cancelado',
                'tiene_estado' => true,
                'accion'       => 'responder',
                'respuesta'    => 'Entendido, he cancelado la operación. ¿En qué más puedo ayudarte?'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    // ---- PASO: esperando_folio ----
    if ($paso_actual === 'esperando_folio') {

        $folio = strtoupper(trim($mensaje));

        if ($folio === '') {
            echo json_encode([
                'STS'          => 'esperando',
                'tiene_estado' => true,
                'accion'       => 'responder',
                'respuesta'    => 'No pude identificar el folio. Por favor envíame el folio de la factura (ej: T-000013).'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $datos['folio'] = $folio;

        // Si es descargar_factura -> avanzar a esperando_formato
        if ($intent_activo === 'descarga_factura') {
            $sql_upd = "UPDATE tmp_conversacion_estado
                        SET paso_actual = 'esperando_formato',
                            datos_parciales = :datos,
                            updated_at = NOW()
                        WHERE telefono = :telefono";
            $stmt_upd = $link->prepare($sql_upd);
            $stmt_upd->execute([
                ':datos'    => json_encode($datos, JSON_UNESCAPED_UNICODE),
                ':telefono' => $telefono_whatsapp
            ]);

            echo json_encode([
                'STS'          => 'avanzado',
                'tiene_estado' => true,
                'accion'       => 'responder',
                'respuesta'    => '¿Necesitas la factura ' . $folio . ' en PDF o XML?'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Si es timbra_factura -> listo, ejecutar directo
        if ($intent_activo === 'timbra_factura') {
            $sql_del = "DELETE FROM tmp_conversacion_estado WHERE telefono = :telefono";
            $link->prepare($sql_del)->execute([':telefono' => $telefono_whatsapp]);

            echo json_encode([
                'STS'            => 'listo',
                'tiene_estado'   => true,
                'accion'         => 'ejecutar',
                'intent_activo'  => $intent_activo,
                'datos'          => $datos
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    // ---- PASO: esperando_formato ----
    if ($paso_actual === 'esperando_formato') {

        $doc = 'pdf'; // default
        if (strpos($mensaje_lower, 'xml') !== false) {
            $doc = 'xml';
        }

        $datos['doc'] = $doc;

        // Listo: borrar estado y devolver datos para ejecutar
        $sql_del = "DELETE FROM tmp_conversacion_estado WHERE telefono = :telefono";
        $link->prepare($sql_del)->execute([':telefono' => $telefono_whatsapp]);

        echo json_encode([
            'STS'            => 'listo',
            'tiene_estado'   => true,
            'accion'         => 'ejecutar',
            'intent_activo'  => $intent_activo,
            'datos'          => $datos
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ---- PASO: esperando_confirmacion ----
    if ($paso_actual === 'esperando_confirmacion') {

        $palabras_si = ['si', 'sí', 'correcto', 'confirmo', 'ok', 'dale', 'adelante', 'está bien', 'esta bien'];
        $es_confirmacion = false;

        foreach ($palabras_si as $palabra) {
            if (strpos($mensaje_lower, $palabra) !== false) {
                $es_confirmacion = true;
                break;
            }
        }

        if (!$es_confirmacion) {
            // No confirmó, cancelar
            $sql_del = "DELETE FROM tmp_conversacion_estado WHERE telefono = :telefono";
            $link->prepare($sql_del)->execute([':telefono' => $telefono_whatsapp]);

            echo json_encode([
                'STS'          => 'cancelado',
                'tiene_estado' => true,
                'accion'       => 'responder',
                'respuesta'    => 'Entendido, he cancelado el registro. Si necesitas algo más, dime.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Confirmó: avanzar a esperando_tel
        $sql_upd = "UPDATE tmp_conversacion_estado
                    SET paso_actual = 'esperando_tel',
                        updated_at = NOW()
                    WHERE telefono = :telefono";
        $link->prepare($sql_upd)->execute([':telefono' => $telefono_whatsapp]);

        echo json_encode([
            'STS'          => 'avanzado',
            'tiene_estado' => true,
            'accion'       => 'responder',
            'respuesta'    => 'Datos confirmados. ¿Cuál es el número de teléfono de contacto del cliente?'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ---- PASO: esperando_tel ----
    if ($paso_actual === 'esperando_tel') {

        $tel = preg_replace('/\D+/', '', $mensaje);

        if (strlen($tel) < 7) {
            echo json_encode([
                'STS'          => 'esperando',
                'tiene_estado' => true,
                'accion'       => 'responder',
                'respuesta'    => 'No pude identificar el número. Por favor envíame el teléfono del cliente (solo números).'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $datos['tel'] = $tel;

        // Listo: borrar estado y devolver datos para ejecutar
        $sql_del = "DELETE FROM tmp_conversacion_estado WHERE telefono = :telefono";
        $link->prepare($sql_del)->execute([':telefono' => $telefono_whatsapp]);

        echo json_encode([
            'STS'            => 'listo',
            'tiene_estado'   => true,
            'accion'         => 'ejecutar',
            'intent_activo'  => $intent_activo,
            'datos'          => $datos
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ---- PASO NO RECONOCIDO: limpiar y mandar al flujo normal ----
    $sql_del = "DELETE FROM tmp_conversacion_estado WHERE telefono = :telefono";
    $link->prepare($sql_del)->execute([':telefono' => $telefono_whatsapp]);

    echo json_encode([
        'STS'          => 'sin_estado',
        'tiene_estado' => false,
        'MSG'          => 'Estado corrupto eliminado, procesar normalmente'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}


// ACCION: REGISTRAR
// Recibe el JSON del clasificador y decide automáticamente si
// hay que guardar estado o no. Solo registra cuando el intent
// necesita datos adicionales que vendrán en mensajes futuros.

if ($accion === 'registrar') {

    $intencion = strtolower(trim($_GET['intencion'] ?? ''));
    $folio     = trim($_GET['folio'] ?? '');
    $rfc       = strtoupper(trim($_GET['rfc'] ?? ''));
    $doc       = strtolower(trim($_GET['doc'] ?? 'pdf'));

    // Reglas de decisión: ¿necesita estado?
    $registrar = false;
    $intent_activo = '';
    $paso_actual = '';
    $datos_parciales = [];

    // descargar_factura sin folio -> esperando_folio
    if ($intencion === 'descarga_factura' && $folio === '') {
        $registrar = true;
        $intent_activo = 'descarga_factura';
        $paso_actual = 'esperando_folio';
        $datos_parciales = ['doc' => $doc];
    }

    // timbra_factura sin folio -> esperando_folio
    if ($intencion === 'timbra_factura' && $folio === '') {
        $registrar = true;
        $intent_activo = 'timbra_factura';
        $paso_actual = 'esperando_folio';
    }

    // confirmacion -> esperando_tel (flujo CIF -> alta_cliente)
    if ($intencion === 'confirmacion') {
        $registrar = true;
        $intent_activo = 'recibir_tel';
        $paso_actual = 'esperando_tel';
    }

    // No necesita estado: responder sin hacer nada
    if (!$registrar) {
        echo json_encode([
            'STS'        => 'no_requerido',
            'registrado' => false,
            'MSG'        => 'Este intent no requiere estado'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // UPSERT: si ya existe estado para este teléfono, lo reemplaza
    $sql = "INSERT INTO tmp_conversacion_estado (telefono, intent_activo, paso_actual, datos_parciales)
            VALUES (:telefono, :intent, :paso, :datos)
            ON DUPLICATE KEY UPDATE
                intent_activo   = VALUES(intent_activo),
                paso_actual     = VALUES(paso_actual),
                datos_parciales = VALUES(datos_parciales),
                updated_at      = NOW()";

    $stmt = $link->prepare($sql);
    $stmt->execute([
        ':telefono' => $telefono_whatsapp,
        ':intent'   => $intent_activo,
        ':paso'     => $paso_actual,
        ':datos'    => json_encode($datos_parciales, JSON_UNESCAPED_UNICODE)
    ]);

    echo json_encode([
        'STS'        => 'ok',
        'registrado' => true,
        'MSG'        => 'Estado registrado',
        'intent'     => $intent_activo,
        'paso'       => $paso_actual
    ], JSON_UNESCAPED_UNICODE);
    exit;
}