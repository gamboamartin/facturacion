<?php

require "init.php";
require 'vendor/autoload.php';

use config\generales;

$generales = new generales();
$secret_key = $generales->cache_secret_key;

$token = $_GET['token'] ?? '';

if ($token === '') {
    http_response_code(403);
    exit('Token requerido');
}

$partes = explode('.', $token);

if (count($partes) !== 2) {
    http_response_code(403);
    exit('Token invalido');
}

$payload = $partes[0];
$firma = $partes[1];

$firma_valida = hash_hmac('sha256', $payload, $secret_key);

if (!hash_equals($firma_valida, $firma)) {
    http_response_code(403);
    exit('Firma invalida');
}

$data = json_decode(base64_decode($payload), true);

if (!is_array($data)) {
    http_response_code(403);
    exit('Token corrupto');
}

if (!isset($data['exp']) || time() > (int)$data['exp']) {
    http_response_code(403);
    exit('Token expirado');
}

if (!isset($data['ruta_pdf'])) {
    http_response_code(403);
    exit('Ruta no valida');
}

$ruta_pdf = $data['ruta_pdf'];

if (!is_string($ruta_pdf) || !file_exists($ruta_pdf) || !is_file($ruta_pdf)) {
    http_response_code(404);
    exit('Archivo no encontrado');
}

$nombre_archivo = basename($ruta_pdf);

if (ob_get_length()) {
    ob_clean();
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $nombre_archivo . '"');
header('Content-Length: ' . filesize($ruta_pdf));
header('Cache-Control: private, max-age=0, no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

readfile($ruta_pdf);
exit;