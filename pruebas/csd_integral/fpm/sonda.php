<?php
declare(strict_types=1);
// Sólo confirma el SAPI dentro del futuro namespace. No abre la base ni carga certificados.
$exterior = getenv('QA_HOST_NET_NS');
if (PHP_SAPI !== 'fpm-fcgi' || !$exterior || readlink('/proc/self/ns/net') === $exterior
    || (is_dir('/home') && count(scandir('/home')) > 2) || file_exists('/etc/mysql') || !is_file('/qa-instance-id')) {
    http_response_code(403);
    exit;
}
$instancia = trim(file_get_contents('/qa-instance-id'));
if (!preg_match('/^[a-f0-9]{32}$/', $instancia)) {
    http_response_code(403);
    exit;
}
header('Content-Type: application/json');
echo json_encode([
    'estado' => 'SAPI_AISLADO_NO_PRUEBA_ALTA',
    'sapi' => PHP_SAPI,
    'php' => PHP_VERSION,
    'openssl' => OPENSSL_VERSION_TEXT,
    'comandos_externos_deshabilitados' => !function_exists('shell_exec') && !function_exists('exec'),
    'url_fopen_deshabilitado' => !filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN),
], JSON_THROW_ON_ERROR), PHP_EOL;
