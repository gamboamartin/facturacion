<?php
declare(strict_types=1);
/** Integración ORM real; no sustituye una petición HTTP ni el controlador completo. */
use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\{fc_csd,fc_key_csd,fc_cer_csd,contexto_archivos_csd};
require_once '/qa/bootstrap.php';
require __DIR__ . '/maestros.php';
function qa_inventario_archivos(): array {
    $inventario = [];
    foreach (['doc_documento', 'temporales'] as $raiz) {
        $base = '/var/www/html/facturacion/archivos/' . $raiz;
        $iterador = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterador as $archivo) {
            $ruta = $archivo->getPathname();
            $inventario[substr($ruta, strlen('/var/www/html/facturacion/archivos/'))] =
                $archivo->isDir() ? 'directorio' : hash_file('sha256', $ruta);
        }
    }
    ksort($inventario);
    return $inventario;
}
$cred = json_decode(file_get_contents('/qa-credenciales-ficticias.json'), true, flags: JSON_THROW_ON_ERROR);
if ($cred['base'] !== 'qa_csd_integral') { throw new RuntimeException('Base no autorizada'); }
$link = new PDO('mysql:unix_socket=/run/mysqld/mysqld.sock;dbname=qa_csd_integral', $cred['usuario'], $cred['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$link->exec("SET sql_mode = ''");
$etapa = 'maestros';
$contexto = null;
$fuentes = [];
foreach ([fc_csd::class, fc_key_csd::class, fc_cer_csd::class, contexto_archivos_csd::class,\gamboamartin\documento\models\doc_documento::class,\gamboamartin\plugins\ssl::class] as $clase) {
    $archivo = (new ReflectionClass($clase))->getFileName();
    $fuentes[$clase] = ['sha256' => hash_file('sha256', $archivo), 'archivo' => $archivo];
}
$salida = [];
$codigo = 1;
ob_start();
try {
    $maestros = qa_crear_maestros($link);
    $_SESSION = ['usuario_id' => $maestros['usuario_id'], 'grupo_id' => $maestros['grupo_id'], 'activa' => 1];
    $password = 'QA-' . bin2hex(random_bytes(18));
    $opciones = ['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA];
    $clave = openssl_pkey_new($opciones);
    $otra = openssl_pkey_new($opciones);
    $csr = openssl_csr_new(['commonName' => 'CSD FICTICIO SIN VALIDEZ'], $clave, ['digest_alg' => 'sha256']);
    $cert = openssl_csr_sign($csr, null, $clave, 1, ['digest_alg' => 'sha256']);
    if (!$clave || !$otra || !$csr || !$cert || !openssl_x509_export($cert, $pem)) { throw new RuntimeException('fixture_criptografico'); }
    $der = base64_decode(preg_replace('/-----[^-]+-----|\s/', '', $pem), true);
    $entrada = '/tmp/qa-csd-entradas-' . bin2hex(random_bytes(8));
    if (!mkdir($entrada, 0700)) { throw new RuntimeException('fixture_directorio'); }
    file_put_contents($entrada . '/ficticio.cer', $der);
    foreach (['exito', 'password_incorrecta', 'par_distinto'] as $caso) {
        $etapa = $caso . ':preparacion';
        if (!openssl_pkey_export($caso === 'par_distinto' ? $otra : $clave, $keyPem, $password)) { throw new RuntimeException('fixture_key'); }
        file_put_contents($entrada . '/ficticio.key', $keyPem);
        chmod($entrada . '/ficticio.key', 0600);
        $antes = [];
        foreach (['fc_csd','fc_csd_etapa','fc_cer_csd','fc_key_csd','fc_cer_pem','fc_key_pem','doc_documento','doc_documento_etapa'] as $tabla) {
            $antes[$tabla] = (int)$link->query('SELECT COUNT(*) FROM `' . $tabla . '`')->fetchColumn();
        }
        $archivos_antes = qa_inventario_archivos();
        $contexto = contexto_archivos_csd::iniciar('/var/www/html/facturacion/');
        $link->beginTransaction();
        $etapa = $caso . ':alta_csd';
        $alta = (new fc_csd($link))->alta_registro([
            'codigo' => 'QA-CSD-' . $caso, 'descripcion' => 'CSD FICTICIO SIN VALIDEZ',
            'serie' => 'QA' . count($salida), 'org_sucursal_id' => $maestros['org_sucursal_id'],
            'no_certificado' => '00000000000000000000',
            'password' => $caso === 'password_incorrecta' ? 'QA-INCORRECTA' : $password
        ]);
        if (errores::$error) { throw new RuntimeException('alta_csd'); }
        foreach (['key' => fc_key_csd::class, 'cer' => fc_cer_csd::class] as $extension => $clase) {
            $etapa = $caso . ':documento_' . $extension;
            $_FILES['documento'] = ['name' => 'ficticio.' . $extension, 'tmp_name' => $entrada . '/ficticio.' . $extension,
                'error' => UPLOAD_ERR_OK, 'size' => filesize($entrada . '/ficticio.' . $extension), 'type' => 'application/octet-stream'];
            (new $clase($link))->alta_registro(['fc_csd_id' => $alta->registro_id]);
            if (errores::$error) { throw new RuntimeException('alta_documento'); }
        }
        $etapa = $caso . ':genera_pems';
        $archivos_originales = qa_inventario_archivos();
        $pem_antes = (int)$link->query('SELECT (SELECT COUNT(*) FROM fc_cer_pem) + (SELECT COUNT(*) FROM fc_key_pem)')->fetchColumn();
        (new fc_csd($link))->genera_pems($alta->registro_id);
        $rechazado = errores::$error;
        $esperado = $caso !== 'exito';
        $sin_pem_nuevo_en_rechazo = !$esperado || ($pem_antes === (int)$link->query('SELECT (SELECT COUNT(*) FROM fc_cer_pem) + (SELECT COUNT(*) FROM fc_key_pem)')->fetchColumn() && qa_inventario_archivos() === $archivos_originales);
        if (!$link->rollBack()) { throw new RuntimeException('rollback_no_confirmado'); }
        $limpieza = $contexto->revertir();
        $contexto->finalizar();
        $contexto = null;
        $iguales = true;
        foreach ($antes as $tabla => $cantidad) {
            $iguales = $iguales && (int)$link->query('SELECT COUNT(*) FROM `' . $tabla . '`')->fetchColumn() === $cantidad;
        }
        $salida[] = ['caso' => $caso, 'rechazado' => $rechazado, 'rechazo_esperado' => $esperado,
            'sin_pem_nuevo_en_rechazo' => $sin_pem_nuevo_en_rechazo, 'rollback_conteos' => $iguales, 'archivos_retirados' => $limpieza['completo'], 'inventario_identico' => qa_inventario_archivos() === $archivos_antes];
        if ($rechazado !== $esperado || !$sin_pem_nuevo_en_rechazo || !$iguales || !$limpieza['completo'] || qa_inventario_archivos() !== $archivos_antes) { throw new RuntimeException('expectativa_no_cumplida'); }
        // Nueva operación independiente después de comprobar el resultado esperado.
        errores::$error = false;
    }
    $codigo = 0;
} catch (Throwable $error) {
    $fallo = ['etapa' => $etapa, 'codigo' => 'ensayo_bloqueado', 'tipo' => get_class($error)];
    if (preg_match('/^(Fixture incompleto: |Columna no acreditada: |Se esperaba tabla efímera vacía: )[a-z_.]+$/D', $error->getMessage())) { $fallo['detalle_seguro'] = $error->getMessage(); }
    if ($error instanceof PDOException) { $fallo['sqlstate'] = $error->getCode(); }
} finally {
    if ($link->inTransaction()) {
        $rollback_final = $link->rollBack();
        if ($rollback_final && $contexto !== null) { $contexto->revertir(); }
    }
    if ($contexto !== null) { $contexto->finalizar(); }
    if (isset($entrada)) {
        foreach (['ficticio.key', 'ficticio.cer'] as $nombre) { if (is_file($entrada . '/' . $nombre)) { unlink($entrada . '/' . $nombre); } }
        rmdir($entrada);
    }
}
$ruido = ob_get_clean();
// Nunca emitir respuestas ORM sin filtrar: podrían incluir la contraseña ficticia.
echo json_encode(['estado' => $codigo === 0 ? 'PASS' : 'BLOQUEADO', 'casos_ejecutados' => count($salida),
    'fuentes' => $fuentes, 'casos' => $salida, 'fallo' => $fallo ?? null, 'salida_interna_presente' => $ruido !== '',
    'alcance' => 'ORM real y transaccion revertida; no controlador HTTP ni commit de alta']), PHP_EOL;
exit($codigo);
