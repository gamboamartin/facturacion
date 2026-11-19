<?php
declare(strict_types=1);
// Generador de una petición multipart enteramente ficticia dentro del namespace.
require_once '/qa/bootstrap.php';
require dirname(__DIR__) . '/maestros.php';
require __DIR__ . '/maestros_administrativos.php';
$cred = json_decode(file_get_contents('/qa-credenciales-ficticias.json'), true, flags: JSON_THROW_ON_ERROR);
if ($cred['base'] !== 'qa_csd_integral') { throw new RuntimeException('Base ajena'); }
$link = new PDO('mysql:unix_socket=/run/mysqld/mysqld.sock;dbname=qa_csd_integral',
    $cred['usuario'], $cred['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$link->exec("SET sql_mode = ''");
$maestros = qa_crear_maestros($link);
$metadatos_fpm = qa_completar_metadatos_fpm($link);
$password = 'QA-' . bin2hex(random_bytes(20));
$clave = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
$csr = openssl_csr_new(['commonName' => 'QA FPM CSD SIN VALIDEZ'], $clave, ['digest_alg' => 'sha256']);
$cert = openssl_csr_sign($csr, null, $clave, 1, ['digest_alg' => 'sha256']);
if (!$cert || !openssl_x509_export($cert, $pem) || !openssl_pkey_export($clave, $key, $password)) {
    throw new RuntimeException('Fixture criptografico no generado');
}
$cer = base64_decode(preg_replace('/-----[^-]+-----|\s/', '', $pem), true);
$campos = ['codigo' => 'QA-FPM-CSD', 'descripcion' => 'CSD FICTICIO FPM SIN VALIDEZ', 'serie' => 'QAFPM',
    'org_sucursal_id' => (string)$maestros['org_sucursal_id'], 'no_certificado' => '00000000000000000000',
    'password' => $password];
$boundary = 'qa-csd-' . bin2hex(random_bytes(16));
$body = '';
foreach ($campos as $nombre => $valor) {
    $body .= '--' . $boundary . "\r\nContent-Disposition: form-data; name=\"$nombre\"\r\n\r\n" . $valor . "\r\n";
}
foreach (['fc_cer_csd_doc' => ['ficticio.cer', $cer], 'fc_key_csd_doc' => ['ficticio.key', $key]] as $nombre => [$archivo, $contenido]) {
    $body .= '--' . $boundary . "\r\nContent-Disposition: form-data; name=\"$nombre\"; filename=\"$archivo\"\r\nContent-Type: application/octet-stream\r\n\r\n" . $contenido . "\r\n";
}
$body .= '--' . $boundary . "--\r\n";
$directorio = '/tmp/fpm-qa';
if (!is_dir($directorio) || is_link($directorio)) { throw new RuntimeException('Directorio privado ausente'); }
$ruta = $directorio . '/peticion.body';
$handle = fopen($ruta, 'x+b');
if (!$handle || !chmod($ruta, 0600) || fwrite($handle, $body) !== strlen($body) || !fclose($handle)) {
    throw new RuntimeException('Peticion privada incompleta');
}
$contexto = ['usuario_id' => $maestros['usuario_id'], 'grupo_id' => $maestros['grupo_id'],
    'org_sucursal_id' => $maestros['org_sucursal_id'], 'solo_sintetico' => true];
$archivoContexto = $directorio . '/contexto.json';
$h = fopen($archivoContexto, 'x+b');
$datos = json_encode($contexto, JSON_THROW_ON_ERROR);
if (!$h || !chmod($archivoContexto, 0600) || fwrite($h, $datos) !== strlen($datos) || !fclose($h)) {
    throw new RuntimeException('Contexto privado incompleto');
}
echo json_encode(['estado' => 'PETICION_SINTETICA_PREPARADA', 'body' => $ruta,
    'content_type' => 'multipart/form-data; boundary=' . $boundary, 'bytes' => strlen($body)], JSON_THROW_ON_ERROR), PHP_EOL;
