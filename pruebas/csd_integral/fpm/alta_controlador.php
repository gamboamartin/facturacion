<?php
declare(strict_types=1);
/** Ensayo SAPI privado: controlador y constructor reales; no prueba login ni dispatcher. */
$etapa = 'precondiciones';
$terminado = false;
$link = null;
$antes = [];
$salida = ['estado' => 'BLOQUEADO', 'alcance' => 'Controlador real vía FastCGI privado; sesión sintética, sin login ni navegador'];
$tablas = ['fc_csd', 'fc_csd_etapa', 'fc_cer_csd', 'fc_key_csd', 'fc_cer_pem', 'fc_key_pem', 'doc_documento'];
ob_start();
set_error_handler(static function(int $nivel, string $mensaje, string $archivo, int $linea): bool {
    if ((error_reporting() & $nivel) === 0) { return false; }
    throw new ErrorException('Incidencia PHP', 0, $nivel, $archivo, $linea);
});
register_shutdown_function(static function() use (&$salida, &$terminado, &$etapa, &$link, &$antes, $tablas): void {
    $ruido = ob_get_clean();
    $salida['etapa_final'] = $etapa;
    $salida['retorno_controlador_completado'] = $terminado;
    $salida['salida_cruda_descartada'] = $ruido !== '';
    $marcadores = ['Error al validar configuraciones', 'Error al validar views.php', 'Error al obtener accion',
        'Error al verificar usuario activo', 'Error al obtener acciones', 'Error al obtener secciones permitidas',
        'Error al obtener menu', 'Error al obtener seccion de menu del usuario', 'Error al inicializar controller'];
    $salida['marcadores_diagnosticos'] = array_values(array_filter($marcadores, static fn($m) => str_contains($ruido, $m)));
    // errores::error conserva ubicaciones estructuradas en sesión; nunca serializar data/params.
    $ubicaciones = [];
    foreach (($_SESSION['error_resultado'] ?? []) as $errorRegistrado) {
        if (!is_array($errorRegistrado)) { continue; }
        $archivo = strip_tags((string)($errorRegistrado['file'] ?? ''));
        $linea = strip_tags((string)($errorRegistrado['line'] ?? ''));
        $prefijo = '/var/www/html/facturacion/';
        if (!str_starts_with($archivo, $prefijo) || !preg_match('/^[0-9]{1,7}$/D', $linea)) { continue; }
        $relativo = substr($archivo, strlen($prefijo));
        if (!preg_match('~^[a-zA-Z0-9_./-]+\.php$~D', $relativo) || str_contains($relativo, '..')) { continue; }
        $ubicaciones[] = ['archivo' => $relativo, 'linea' => (int)$linea];
    }
    $salida['ubicaciones_error_estructuradas'] = array_slice($ubicaciones, 0, 30);
    $ultimo = error_get_last();
    $salida['fatal_presente'] = $ultimo !== null && in_array($ultimo['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true);
    if ($link instanceof PDO) {
        try {
            $salida['transaccion_pendiente'] = $link->inTransaction();
            if ($link->inTransaction()) {
                $salida['rollback_emergencia'] = $link->rollBack();
                $salida['estado'] = 'BLOQUEADO';
            }
            foreach ($tablas as $tabla) {
                $salida['delta_registros'][$tabla] = (int)$link->query("SELECT COUNT(*) FROM `$tabla`")->fetchColumn() - ($antes[$tabla] ?? 0);
            }
        } catch (Throwable) {
            $salida['verificacion_final'] = 'BLOQUEADA';
            $salida['estado'] = 'BLOQUEADO';
        }
    }
    if (!$terminado || $salida['salida_cruda_descartada'] || $salida['fatal_presente']) { $salida['estado'] = 'BLOQUEADO'; }
    header_remove('Location');
    header('Content-Type: application/json');
    http_response_code($salida['estado'] === 'PASS' ? 200 : 500);
    echo json_encode($salida, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
});
try {
    if (PHP_SAPI !== 'fpm-fcgi' || !getenv('QA_HOST_NET_NS')
        || readlink('/proc/self/ns/net') === getenv('QA_HOST_NET_NS')
        || !is_file('/qa-instance-id') || (is_dir('/home') && count(scandir('/home')) > 2) || file_exists('/etc/mysql')) {
        throw new RuntimeException('Aislamiento FPM no acreditado');
    }
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || count($_FILES) !== 2) {
        throw new RuntimeException('Petición multipart no acreditada');
    }
    foreach (['fc_cer_csd_doc', 'fc_key_csd_doc'] as $campo) {
        if (!isset($_FILES[$campo]) || $_FILES[$campo]['error'] !== UPLOAD_ERR_OK
            || !is_uploaded_file($_FILES[$campo]['tmp_name'])) {
            throw new RuntimeException('Carga SAPI no acreditada');
        }
    }
    $salida['uploads_sapi_reales'] = true;
    $raiz = '/var/www/html/facturacion/';
    require_once $raiz . 'config/database.php';
    require_once $raiz . 'config/generales.php';
    $loader = require $raiz . 'vendor/autoload.php';
    $composer = json_decode(file_get_contents($raiz . 'composer.json'), true, flags: JSON_THROW_ON_ERROR);
    foreach ($composer['autoload']['psr-4'] as $prefijo => $ruta) { $loader->addPsr4($prefijo, $raiz . $ruta, true); }
    $contexto = json_decode(file_get_contents('/tmp/fpm-qa/contexto.json'), true, flags: JSON_THROW_ON_ERROR);
    if (($contexto['solo_sintetico'] ?? false) !== true || (int)($_POST['org_sucursal_id'] ?? 0) !== $contexto['org_sucursal_id']) {
        throw new RuntimeException('Fixture ajeno a la petición');
    }
    $_SESSION = ['usuario_id' => $contexto['usuario_id'], 'grupo_id' => $contexto['grupo_id'], 'activa' => 1, 'nombre_usuario' => 'USUARIO SINTETICO CSD'];
    $_GET = ['seccion' => 'fc_csd', 'accion' => 'alta_bd', 'registro_id' => -1, 'session_id' => 'qa-csd-fpm-sintetica'];
    $cred = json_decode(file_get_contents('/qa-credenciales-ficticias.json'), true, flags: JSON_THROW_ON_ERROR);
    if ($cred['base'] !== 'qa_csd_integral') { throw new RuntimeException('Base no acreditada'); }
    $link = new PDO('mysql:unix_socket=/run/mysqld/mysqld.sock;dbname=qa_csd_integral',
        $cred['usuario'], $cred['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    if ((int)$link->query('SELECT @@skip_networking')->fetchColumn() !== 1) { throw new RuntimeException('Motor con red'); }
    $link->exec("SET sql_mode = ''");
    foreach ($tablas as $tabla) { $antes[$tabla] = (int)$link->query("SELECT COUNT(*) FROM `$tabla`")->fetchColumn(); }
    $salida['sapi'] = PHP_SAPI;
    $salida['php'] = PHP_VERSION;
    $salida['openssl'] = OPENSSL_VERSION_TEXT;
    $etapa = 'constructor_real';
    $paths = (object)['generales' => $raiz . 'config/generales.php', 'database' => $raiz . 'config/database.php',
        'views' => $raiz . 'config/views.php'];
    $controlador = new \gamboamartin\facturacion\controllers\controlador_fc_csd(link: $link, paths_conf: $paths);
    if (\gamboamartin\errores\errores::$error) { throw new RuntimeException('Constructor rechazado'); }
    $salida['constructor_real_completado'] = true;
    $etapa = 'alta_bd_real';
    $resultado = $controlador->alta_bd(header: false, ws: false);
    $terminado = true;
    $rechazado = \gamboamartin\errores\errores::$error;
    $salida['rechazado'] = $rechazado;
    $ok = !$rechazado && !$link->inTransaction();
    foreach (['fc_csd' => 1, 'fc_cer_csd' => 1, 'fc_key_csd' => 1, 'fc_cer_pem' => 1, 'fc_key_pem' => 1, 'doc_documento' => 4] as $tabla => $delta) {
        $ok = $ok && (int)$link->query("SELECT COUNT(*) FROM `$tabla`")->fetchColumn() === $antes[$tabla] + $delta;
    }
    $salida['estado'] = $ok ? 'PASS' : 'BLOQUEADO';
    $etapa = 'verificacion_post_alta';
} catch (Throwable $error) {
    $salida['tipo_error'] = get_class($error);
    // Sólo ubicación de código: nunca argumentos, POST, claves, password ni respuesta ORM.
    $salida['ubicacion_error'] = basename($error->getFile()) . ':' . $error->getLine();
    $salida['estado'] = 'BLOQUEADO';
}
