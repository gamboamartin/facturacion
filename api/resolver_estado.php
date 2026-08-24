<?php

use base\conexion;

chdir(__DIR__ . '/..');
require "init.php";
require 'vendor/autoload.php';
require_once __DIR__ . '/valida_token_interno.php';
valida_token_interno();

header('Content-Type: application/json; charset=utf-8');

$con  = new conexion();
$link = conexion::$link;


// ============================================================
// FUNCIONES DE NORMALIZACIÓN
// Convierte input de voz (transcripción STT) y texto coloquial
// en texto estructurado que el sistema puede interpretar.
// ============================================================

/**
 * Convierte números escritos como palabras a dígitos.
 * Maneja: unidades, teens, twenties, decenas, compuestos ("treinta y uno").
 * NO reemplaza "un"/"una" (demasiado ambiguo con artículos).
 */
function palabras_a_numeros(string $texto): string
{
    // --- Paso 1: compuestos "decena + y + unidad" (antes de reemplazar individuales) ---
    $decenas_compuestas = [
        'treinta'  => 30, 'cuarenta'  => 40, 'cincuenta' => 50,
        'sesenta'  => 60, 'setenta'   => 70, 'ochenta'   => 80, 'noventa'   => 90,
    ];
    $unidades_compuestas = [
        'uno' => 1, 'dos' => 2, 'tres' => 3, 'cuatro' => 4, 'cinco' => 5,
        'seis' => 6, 'siete' => 7, 'ocho' => 8, 'nueve' => 9,
    ];

    foreach ($decenas_compuestas as $dec_palabra => $dec_valor) {
        foreach ($unidades_compuestas as $uni_palabra => $uni_valor) {
            $patron = '/\b' . $dec_palabra . '\s+y\s+' . $uni_palabra . '\b/ui';
            $texto = preg_replace($patron, (string)($dec_valor + $uni_valor), $texto);
        }
    }

    // --- Paso 2: números especiales (10-29) — ordenados por longitud desc para evitar parciales ---
    $especiales = [
        'veintinueve'  => '29', 'veintiocho'   => '28', 'veintisiete'  => '27',
        'veintiséis'   => '26', 'veintiseis'   => '26', 'veinticinco'  => '25',
        'veinticuatro' => '24', 'veintitrés'   => '23', 'veintitres'   => '23',
        'veintidós'    => '22', 'veintidos'    => '22', 'veintiuno'    => '21',
        'veintiún'     => '21', 'veintún'      => '21',
        'diecinueve'   => '19', 'dieciocho'    => '18', 'diecisiete'   => '17',
        'dieciséis'    => '16', 'dieciseis'    => '16',
        'quince'       => '15', 'catorce'      => '14', 'trece'        => '13',
        'doce'         => '12', 'once'         => '11', 'diez'         => '10',
        'veinte'       => '20',
    ];

    foreach ($especiales as $palabra => $valor) {
        $texto = preg_replace('/\b' . preg_quote($palabra, '/') . '\b/ui', $valor, $texto);
    }

    // --- Paso 3: decenas solas ---
    foreach ($decenas_compuestas as $palabra => $valor) {
        $texto = preg_replace('/\b' . preg_quote($palabra, '/') . '\b/ui', (string)$valor, $texto);
    }

    // --- Paso 4: unidades (0-9) ---
    $unidades = [
        'cero' => '0', 'uno' => '1', 'dos' => '2', 'tres' => '3',
        'cuatro' => '4', 'cinco' => '5', 'seis' => '6', 'siete' => '7',
        'ocho' => '8', 'nueve' => '9',
    ];

    foreach ($unidades as $palabra => $valor) {
        $texto = preg_replace('/\b' . preg_quote($palabra, '/') . '\b/ui', $valor, $texto);
    }

    // --- Paso 5: "doble" seguido de algo → duplicar ---
    // "doble u" → "W", "doble v" → "W", "doble cero" ya se convirtió a "doble 0"
    $texto = preg_replace('/\bdoble\s+u\b/ui', 'W', $texto);
    $texto = preg_replace('/\bdoble\s+v\b/ui', 'W', $texto);
    // "doble" + dígito → duplicar el dígito: "doble 0" → "00"
    $texto = preg_replace_callback('/\bdoble\s+(\d)\b/ui', function ($m) {
        return $m[1] . $m[1];
    }, $texto);

    return $texto;
}


function limpiar_muletillas(string $texto): string
{
    $muletillas = [
        // --- Muletillas conversacionales (seguras) ---
        'este', 'pues', 'bueno', 'mira', 'oye', 'fíjate', 'fijate',
        'eh', 'mmm', 'ajá', 'aja', 'entonces', 'a ver', 'haber',
        'verdad', 'sabes', 'o sea', 'osea',
        'básicamente', 'basicamente', 'la verdad', 'por favor',
        'me puedes', 'me podrias', 'me podrías', 'quisiera',
        'sería', 'seria',
        // --- Jerga MX/LATAM (seguras — nunca son datos ni keywords) ---
        'a chile', 'chido', 'no manches', 'no mames',
    ];

    foreach ($muletillas as $m) {
        $texto = preg_replace('/\b' . preg_quote($m, '/') . '\b[,.]?\s*/ui', ' ', $texto);
    }

    // "wey" con cualquier cantidad de letras repetidas: wey, weyy, weyyy, etc.
    $texto = preg_replace('/\bw+e+y+\b[,.]?\s*/ui', ' ', $texto);

    return trim(preg_replace('/\s+/', ' ', $texto));
}

/**
 * Intenta reconstruir un folio de factura a partir de texto normalizado.
 * Entrada esperada (post normalización): "t 0 0 0 0 22" o "t-000022"
 * Salida: "T-000022"
 *
 * Si no puede reconstruir un patrón válido, retorna el texto limpio.
 */
function reconstruir_folio(string $texto): string
{
    // Eliminar palabras de contexto que no son parte del folio
    $texto = preg_replace('/\b(sí|si|es|la|el|del|factura|folio|número|numero|serie|guion|guión)\b/ui', '', $texto);
    $texto = trim(preg_replace('/\s+/', ' ', $texto));
    $texto = strtoupper($texto);

    // Si ya tiene formato válido (T-000022), retornar limpio
    $sin_espacios = preg_replace('/[\s\-]/', '', $texto);
    if (preg_match('/^([A-Z]{1,3})(\d+)$/', $sin_espacios, $m)) {
        $prefijo = $m[1];
        $numero  = str_pad($m[2], 6, '0', STR_PAD_LEFT);
        return $prefijo . '-' . $numero;
    }

    // Intentar extraer: letra(s) seguidas de dígitos separados por espacios
    // "T 0 0 0 0 2 2" → prefijo "T", dígitos "000022"
    if (preg_match('/^([A-Z]{1,3})\s+(.+)$/', $texto, $m)) {
        $prefijo = $m[1];
        $digitos = preg_replace('/\s/', '', $m[2]);
        if (preg_match('/^\d+$/', $digitos)) {
            return $prefijo . '-' . str_pad($digitos, 6, '0', STR_PAD_LEFT);
        }
    }

    // No pudo reconstruir, retornar lo que hay
    return $texto;
}


// ============================================================
// paso 1. PARAMETROS DE ENTRADA
// ============================================================

$input = json_decode(file_get_contents('php://input'), true) ?: [];

$accion            = strtolower(trim($input['accion'] ?? $_GET['accion'] ?? ''));
$telefono_whatsapp = preg_replace('/\D+/', '', trim($input['telefono_whatsapp'] ?? $_GET['telefono_whatsapp'] ?? $_GET['telefono'] ?? ''));
$mensaje           = trim($input['mensaje'] ?? $_GET['mensaje'] ?? '');

// ============================================================
// paso 2. VALIDACIONES BASICAS
// ============================================================

if ($telefono_whatsapp === '') {
    echo json_encode([
        'STS' => 'error',
        'MSG' => 'El teléfono de WhatsApp es requerido'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!in_array($accion, ['resolver', 'registrar', 'guardar_mensaje', 'buscar_mensaje'], true)) {
    echo json_encode([
        'STS' => 'error',
        'MSG' => 'Acción no válida. Usar: resolver, registrar, guardar_mensaje o buscar_mensaje'
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
            FROM n8n_estado_conversacion
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

    // Normalización del mensaje: limpio para comparaciones
    $mensaje_lower      = strtolower(trim($mensaje));
    $mensaje_normalizado = limpiar_muletillas(palabras_a_numeros($mensaje_lower));

    // ---- Detectar cancelación (keywords expandidas) ----
    $palabras_cancelar = [
        'cancelar', 'salir', 'dejalo', 'déjalo', 'olvidalo', 'olvídalo',
        'no quiero', 'ya no', 'no gracias', 'no, gracias', 'mejor no',
        'dejemos asi', 'dejemos así', 'no importa', 'olvidemos', 'nada',
        'ya no importa', 'no es necesario', 'no hace falta', 'no hace falta ya',
    ];
    foreach ($palabras_cancelar as $palabra) {
        if (mb_strpos($mensaje_normalizado, $palabra) !== false) {
            $sql_del = "DELETE FROM n8n_estado_conversacion WHERE telefono = :telefono";
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

        // Normalizar: "t cero cero cero cero veintidós" → "T-000022"
        $folio_normalizado = reconstruir_folio(
            palabras_a_numeros(limpiar_muletillas($mensaje))
        );
        $folio = strtoupper(trim($folio_normalizado));

        if ($folio === '' || !preg_match('/\d/', $folio)) {
            echo json_encode([
                'STS'          => 'esperando',
                'tiene_estado' => true,
                'accion'       => 'responder',
                'respuesta'    => 'No pude identificar el folio. Por favor envíame el folio de la factura (ej: T-000013). Puedes escribirlo o dictarlo.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $datos['folio'] = $folio;

        // Si es descargar_factura -> avanzar a esperando_formato
        if ($intent_activo === 'descarga_factura') {
            $sql_upd = "UPDATE n8n_estado_conversacion
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
                'respuesta'    => '¿Necesitas la factura ' . $folio . ' en PDF, XML o ambos?'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Si es timbra_factura -> listo, ejecutar directo
        if ($intent_activo === 'timbra_factura') {
            $sql_del = "DELETE FROM n8n_estado_conversacion WHERE telefono = :telefono";
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

        // Keywords expandidas para cada formato
        $keywords_ambos = [
            'ambos', 'los dos', 'los 2', 'las dos', 'las 2', 'both',
            'todo', 'todos', 'completo', 'completos',
            'pdf y xml', 'xml y pdf', 'pdf xml', 'xml pdf',
            'los dos archivos', 'las dos cosas', 'mándame todo', 'mandame todo',
            'dame todo', 'envíame todo', 'enviame todo', 'quiero los dos',
            'necesito los dos', 'ambas', 'los 2 archivos',
        ];

        $keywords_xml = ['xml'];
        $keywords_pdf = ['pdf'];

        $doc = null; // SIN default — si no matchea, preguntamos

        // Evaluar ambos PRIMERO (contiene "pdf" y "xml" que matchearían individual)
        foreach ($keywords_ambos as $kw) {
            if (mb_strpos($mensaje_normalizado, $kw) !== false) {
                $doc = 'ambos';
                break;
            }
        }

        if ($doc === null) {
            foreach ($keywords_xml as $kw) {
                if (mb_strpos($mensaje_normalizado, $kw) !== false) {
                    $doc = 'xml';
                    break;
                }
            }
        }

        if ($doc === null) {
            foreach ($keywords_pdf as $kw) {
                if (mb_strpos($mensaje_normalizado, $kw) !== false) {
                    $doc = 'pdf';
                    break;
                }
            }
        }

        // Si no identificó formato: PREGUNTAR en vez de asumir
        if ($doc === null) {
            echo json_encode([
                'STS'          => 'esperando',
                'tiene_estado' => true,
                'accion'       => 'responder',
                'respuesta'    => 'No logré identificar el formato. ¿Necesitas el archivo en PDF, XML o ambos?'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $datos['doc'] = $doc;

        // Listo: borrar estado y devolver datos para ejecutar
        $sql_del = "DELETE FROM n8n_estado_conversacion WHERE telefono = :telefono";
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

        // Keywords expandidas: confirmación
        $palabras_si = [
            'si', 'sí', 'correcto', 'confirmo', 'ok', 'dale', 'adelante',
            'está bien', 'esta bien', 'afirmativo', 'así es', 'asi es',
            'exacto', 'de acuerdo', 'va', 'sale', 'órale', 'orale',
            'eso es', 'positivo', 'claro', 'por supuesto', 'simón',
            'simon', 'arre', 'jalo', 'jalamos', 'perfecto', 'listo',
            'zax', 'no pos si', 'neta', 'entendido', 'de una', 'de una vez',
            'a webo', 'vale',
        ];

        // Keywords expandidas: negación explícita
        $palabras_no = [
            'no', 'nel', 'nop', 'negativo', 'nah', 'nope', 'para nada',
            'incorrecto', 'mal', 'está mal', 'esta mal', 'no es',
            'equivocado', 'error', 'falso', 'nel pastel',
        ];

        $es_confirmacion = false;
        $es_negacion     = false;

        foreach ($palabras_si as $palabra) {
            if (mb_strpos($mensaje_normalizado, $palabra) !== false) {
                $es_confirmacion = true;
                break;
            }
        }

        // Solo evaluar negación si NO hubo confirmación
        // (evita conflicto con "no, está bien" → confirmación gana)
        if (!$es_confirmacion) {
            foreach ($palabras_no as $palabra) {
                if (mb_strpos($mensaje_normalizado, $palabra) !== false) {
                    $es_negacion = true;
                    break;
                }
            }
        }

        // Negación explícita: cancelar
        if ($es_negacion) {
            $sql_del = "DELETE FROM n8n_estado_conversacion WHERE telefono = :telefono";
            $link->prepare($sql_del)->execute([':telefono' => $telefono_whatsapp]);

            echo json_encode([
                'STS'          => 'cancelado',
                'tiene_estado' => true,
                'accion'       => 'responder',
                'respuesta'    => 'Entendido, he cancelado el registro. Si necesitas algo más, dime.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Ni confirmación ni negación: PREGUNTAR en vez de cancelar
        if (!$es_confirmacion) {
            echo json_encode([
                'STS'          => 'esperando',
                'tiene_estado' => true,
                'accion'       => 'responder',
                'respuesta'    => 'No entendí tu respuesta. ¿Los datos son correctos? Responde sí o no.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Confirmó: avanzar a esperando_tel
        $sql_upd = "UPDATE n8n_estado_conversacion
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

        // Normalizar: "cinco cinco uno dos tres..." → "5 5 1 2 3..."
        $mensaje_tel = palabras_a_numeros(limpiar_muletillas($mensaje));
        $tel = preg_replace('/\D+/', '', $mensaje_tel);

        if (strlen($tel) < 7) {
            echo json_encode([
                'STS'          => 'esperando',
                'tiene_estado' => true,
                'accion'       => 'responder',
                'respuesta'    => 'No pude identificar el número. Por favor envíame el teléfono del cliente (solo números). Puedes escribirlo o dictarlo.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $datos['tel'] = $tel;

        // Listo: borrar estado y devolver datos para ejecutar
        $sql_del = "DELETE FROM n8n_estado_conversacion WHERE telefono = :telefono";
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

    // ---- PASO: esperando_rfc ----
    if ($paso_actual === 'esperando_rfc') {

        // Normalizar: mayúsculas, limpiar muletillas, números hablados
        $rfc_normalizado = strtoupper(trim(
            palabras_a_numeros(limpiar_muletillas($mensaje))
        ));

        // Eliminar espacios, guiones y caracteres no alfanuméricos
        $rfc_normalizado = preg_replace('/[^A-Z0-9Ñ&]/', '', $rfc_normalizado);

        // Validar formato RFC: 12 chars (persona moral) o 13 chars (persona física)
        if (!preg_match('/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/', $rfc_normalizado)) {
            echo json_encode([
                'STS'          => 'esperando',
                'tiene_estado' => true,
                'accion'       => 'responder',
                'respuesta'    => 'El RFC no tiene un formato válido. Debe ser de 12 o 13 caracteres (ej: DAO040125W7). ¿Podrías escribirlo de nuevo?'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $datos['rfc'] = $rfc_normalizado;

        // Listo: borrar estado y devolver datos para ejecutar
        $sql_del = "DELETE FROM n8n_estado_conversacion WHERE telefono = :telefono";
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
    $sql_del = "DELETE FROM n8n_estado_conversacion WHERE telefono = :telefono";
    $link->prepare($sql_del)->execute([':telefono' => $telefono_whatsapp]);

    echo json_encode([
        'STS'          => 'sin_estado',
        'tiene_estado' => false,
        'MSG'          => 'Estado corrupto eliminado, procesar normalmente'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}


// ============================================================
// ACCION: REGISTRAR
// Recibe el JSON del clasificador y decide automáticamente si
// hay que guardar estado o no. Solo registra cuando el intent
// necesita datos adicionales que vendrán en mensajes futuros.
// ============================================================

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

    // alta_factura sin RFC pero con campos fiscales -> esperando_rfc
    if ($intencion === 'alta_factura' && $rfc === '') {
        $fp  = strtoupper(trim($_GET['FP'] ?? ''));
        $mp  = strtoupper(trim($_GET['MP'] ?? ''));
        $uc  = strtoupper(trim($_GET['UC'] ?? ''));
        $mon = strtoupper(trim($_GET['MON'] ?? ''));

        if ($fp !== '' || $mp !== '' || $uc !== '' || $mon !== '') {
            $registrar = true;
            $intent_activo = 'alta_factura';
            $paso_actual = 'esperando_rfc';
            $datos_parciales = [
                'FP'  => $fp,
                'MP'  => $mp,
                'UC'  => $uc,
                'MON' => $mon,
            ];
        }
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
    $sql = "INSERT INTO n8n_estado_conversacion (telefono, intent_activo, paso_actual, datos_parciales)
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


// ============================================================
// ACCION: GUARDAR_MENSAJE
// Almacena cada mensaje (entrante o saliente) en
// n8n_tmp_mensajes_whatsapp para resolver citas posteriores
// ============================================================

if ($accion === 'guardar_mensaje') {

    $message_id = trim($_GET['message_id'] ?? '');
    $direccion  = strtolower(trim($_GET['direccion'] ?? 'entrante'));
    $contenido  = $mensaje; // ya viene de $_GET['mensaje']

    if ($message_id === '') {
        echo json_encode([
            'STS' => 'error',
            'MSG' => 'El message_id es requerido'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!in_array($direccion, ['entrante', 'saliente'], true)) {
        $direccion = 'entrante';
    }

    // INSERT IGNORE: si el message_id ya existe, no lo sobreescribe
    $sql = "INSERT IGNORE INTO n8n_tmp_mensajes_whatsapp (message_id, telefono, contenido, direccion)
            VALUES (:message_id, :telefono, :contenido, :direccion)";

    $stmt = $link->prepare($sql);
    $stmt->execute([
        ':message_id' => $message_id,
        ':telefono'   => $telefono_whatsapp,
        ':contenido'  => $contenido,
        ':direccion'  => $direccion
    ]);

    echo json_encode([
        'STS'      => 'ok',
        'guardado' => true,
        'MSG'      => 'Mensaje almacenado'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}


// ============================================================
// ACCION: BUSCAR_MENSAJE
// Busca el contenido de un mensaje por su message_id
// (para resolver el contenido de mensajes citados/respondidos)
// ============================================================

if ($accion === 'buscar_mensaje') {

    $message_id = trim($_GET['message_id'] ?? '');

    if ($message_id === '') {
        echo json_encode([
            'STS'        => 'error',
            'encontrado' => false,
            'MSG'        => 'El message_id es requerido'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $sql = "SELECT contenido, telefono, direccion, created_at
            FROM n8n_tmp_mensajes_whatsapp
            WHERE message_id = :message_id
            AND telefono = :telefono
            LIMIT 1";

    $stmt = $link->prepare($sql);
    $stmt->execute([
            ':message_id' => $message_id,
            ':telefono'   => $telefono_whatsapp
        ]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$resultado) {
        echo json_encode([
            'STS'        => 'no_encontrado',
            'encontrado' => false,
            'MSG'        => 'No se encontró el mensaje citado'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([
        'STS'        => 'ok',
        'encontrado' => true,
        'contenido'  => $resultado['contenido'],
        'telefono'   => $resultado['telefono'],
        'direccion'  => $resultado['direccion'],
        'fecha'      => $resultado['created_at']
    ], JSON_UNESCAPED_UNICODE);
    exit;
}