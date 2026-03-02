<?php
declare(strict_types=1);

/**
 * Fase 1 (pruebas): WhatsApp Cloud API (Meta) -> Webhook NodeE (n8n)
 *
 * Endpoints:
 * - GET  /webhook   Verificación webhook de Meta (hub.challenge)
 * - POST /webhook   Recepción de mensajes (eventos)
 *
 * Requisitos:
 * - PHP 8+
 * - ext-curl
 */

// =====================
// CONFIG (ENV recomendado)
// =====================
$CFG = [
  // Meta webhook verify token (el que configuras en Meta)
  'WA_VERIFY_TOKEN' => getenv('WA_VERIFY_TOKEN') ?: 'token_secreto_2026',

  // Meta app secret (para validar firma)
  'WA_APP_SECRET' => getenv('WA_APP_SECRET') ?: '8032726a92af79c078374e1d2258d042',

  
  'NODEE_WEBHOOK_URL' => getenv('NODEE_WEBHOOK_URL') ?: 'https://richito.ivitec.mx/webhook-test/a4952549-9916-431a-bb3d-17a36184981d',
  'NODEE_WEBHOOK_TOKEN' => getenv('NODEE_WEBHOOK_TOKEN') ?: 'token_secreto_para_nodee',

  // Logs
  'LOG_FILE' => __DIR__ . '/../logs/wa_nodee.log',
];

// =====================
// Router simple
// =====================
$path   = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
  if ($path === '/webhook' && $method === 'GET') {
    handleVerify($CFG);
    exit;
  }

  if ($path === '/webhook' && $method === 'POST') {
    handleIncoming($CFG);
    exit;
  }

  if ($path === '/' && $method === 'GET') {
    jsonOut(['ok' => true, 'service' => 'wa->nodee'], 200);
    exit;
  }

  jsonOut(['error' => 'Not Found'], 404);
} catch (Throwable $e) {
  logLine($CFG, "FATAL: ".$e->getMessage());
  jsonOut(['error' => 'Internal Server Error'], 500);
}

// =====================
// Handlers
// =====================

/**
 * Verificación de Meta:
 * GET /webhook?hub.mode=subscribe&hub.verify_token=...&hub.challenge=...
 */
function handleVerify(array $CFG): void {
  $mode      = $_GET['hub_mode'] ?? $_GET['hub.mode'] ?? '';
  $token     = $_GET['hub_verify_token'] ?? $_GET['hub.verify_token'] ?? '';
  $challenge = $_GET['hub_challenge'] ?? $_GET['hub.challenge'] ?? '';

  if ($mode === 'subscribe' && hash_equals($CFG['WA_VERIFY_TOKEN'], (string)$token)) {
    header('Content-Type: text/plain; charset=utf-8');
    echo (string)$challenge;
    return;
  }

  jsonOut(['error' => 'Forbidden'], 403);
}

/**
 * Recepción de eventos:
 * - Valida firma X-Hub-Signature-256
 * - Extrae mensajes tipo text
 * - Reenvía a NodeE
 * - Responde 200 rápido a Meta
 */
function handleIncoming(array $CFG): void {
  $raw = file_get_contents('php://input') ?: '';
  if ($raw === '') {
    jsonOut(['ok' => true, 'note' => 'empty body'], 200);
    return;
  }

  // 1) Validar firma (recomendado para prod)
  if (!verifyMetaSignature($CFG['WA_APP_SECRET'], $raw)) {
    logLine($CFG, "Invalid signature. body=".substr($raw, 0, 300));
    jsonOut(['error' => 'Invalid signature'], 401);
    return;
  }

  $payload = json_decode($raw, true);
  if (!is_array($payload)) {
    jsonOut(['error' => 'Invalid JSON'], 400);
    return;
  }

  // 2) Extraer mensajes entrantes (solo texto)
  $messages = extractMetaTextMessages($payload);

  // Si no hay mensajes de texto, igual respondemos OK (pueden ser status/delivery/read)
  if (empty($messages)) {
    jsonOut(['ok' => true, 'note' => 'no text messages'], 200);
    return;
  }

  // 3) Forward a NodeE (uno por mensaje)
  foreach ($messages as $m) {
    $forwardPayload = [
      'source' => 'whatsapp_cloud_api',
      'event' => 'incoming_message',
      'received_at' => gmdate('c'),
      'message_id' => $m['message_id'],
      'from' => $m['from'],
      'text' => $m['text'],
      'timestamp' => $m['timestamp'],
      // útil para debug/relacionar:
      'contacts' => $m['contacts'] ?? null,
      'metadata' => $m['metadata'] ?? null,
    ];

    $ok = postJson(
      $CFG['NODEE_WEBHOOK_URL'],
      $forwardPayload,
      [
        'Content-Type: application/json',
        'Accept: application/json',
        'X-NodeE-Token: '.$CFG['NODEE_WEBHOOK_TOKEN'],
      ]
    );

    logLine($CFG, "Forward msg_id={$m['message_id']} ok=".($ok ? '1' : '0'));
  }

  // 4) Responder 200 a Meta
  jsonOut(['ok' => true], 200);
}

// =====================
// WhatsApp Meta helpers
// =====================

/**
 * Validación firma Meta:
 * Header: X-Hub-Signature-256: sha256=...
 */
function verifyMetaSignature(string $appSecret, string $rawBody): bool {
  $header = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
  if ($header === '' || !str_starts_with($header, 'sha256=')) {
    return false;
  }

  $sig = substr($header, 7);
  $hash = hash_hmac('sha256', $rawBody, $appSecret);
  return hash_equals($hash, $sig);
}

/**
 * Extrae mensajes tipo text de WhatsApp Cloud API.
 */
function extractMetaTextMessages(array $payload): array {
  $out = [];

  $entries = $payload['entry'] ?? [];
  if (!is_array($entries)) return $out;

  foreach ($entries as $entry) {
    $changes = $entry['changes'] ?? [];
    if (!is_array($changes)) continue;

    foreach ($changes as $change) {
      $value = $change['value'] ?? null;
      if (!is_array($value)) continue;

      $messages = $value['messages'] ?? [];
      if (!is_array($messages)) continue;

      foreach ($messages as $msg) {
        if (!is_array($msg)) continue;

        $type = (string)($msg['type'] ?? '');
        if ($type !== 'text') continue;

        $out[] = [
          'message_id' => (string)($msg['id'] ?? ''),
          'from' => (string)($msg['from'] ?? ''),
          'timestamp' => isset($msg['timestamp']) ? (int)$msg['timestamp'] : null,
          'text' => (string)($msg['text']['body'] ?? ''),
          'contacts' => $value['contacts'] ?? null,
          'metadata' => $value['metadata'] ?? null,
        ];
      }
    }
  }

  return $out;
}

// =====================
// HTTP client
// =====================

function postJson(string $url, array $data, array $headers): bool {
  $ch = curl_init($url);
  $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

  curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $json,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 8,
    CURLOPT_CONNECTTIMEOUT => 3,
  ]);

  $resp = curl_exec($ch);
  $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $err  = curl_error($ch);
  curl_close($ch);

  if ($err) return false;
  return ($code >= 200 && $code < 300);
}

// =====================
// Utils
// =====================

function jsonOut(array $data, int $code): void {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function logLine(array $CFG, string $line): void {
  $dir = dirname($CFG['LOG_FILE']);
  if (!is_dir($dir)) @mkdir($dir, 0775, true);

  @file_put_contents(
    $CFG['LOG_FILE'],
    '['.date('Y-m-d H:i:s').'] '.$line.PHP_EOL,
    FILE_APPEND
  );
}