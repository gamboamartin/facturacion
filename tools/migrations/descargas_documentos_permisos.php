<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
ini_set('display_errors', '0');
require dirname(__DIR__, 2).'/config/database.php';
require dirname(__DIR__, 2).'/config/views.php';
require __DIR__.'/_descargas_documentos_permisos.php';
$options = getopt('', ['database:', 'interface:', 'grupo-principal-codigo:', 'apply', 'plan:', 'journal:', 'rollback:']);
try {
    $db = new \config\database();
    if (($options['database'] ?? '') !== $db->db_name || ($options['interface'] ?? '') !== parse_url((new \config\views())->url, PHP_URL_HOST)) {
        throw new RuntimeException('Confirma --database e --interface con la base y el host exactos de esta instalación.');
    }
    if (isset($options['apply'], $options['rollback'])) throw new RuntimeException('Elige aplicar o revertir.');
    $principal = $options['grupo-principal-codigo'] ?? '';
    if ($principal === '') throw new RuntimeException('Confirma --grupo-principal-codigo para el grupo 3.');
    $pdo = new PDO('mysql:host='.$db->db_host.';dbname='.$db->db_name.';charset=utf8mb4', $db->db_user, $db->db_password,
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES=>false]);
    if (isset($options['rollback'])) {
        $diario = json_decode(file_get_contents($options['rollback']), true, flags: JSON_THROW_ON_ERROR);
        $resultado = DescargasDocumentosPermisos::revertir($pdo, $diario);
    } elseif (isset($options['apply'])) {
        $plan = json_decode(file_get_contents($options['plan'] ?? ''), true, flags: JSON_THROW_ON_ERROR);
        if (($plan['version'] ?? '') !== DescargasDocumentosPermisos::VERSION || ($plan['base'] ?? '') !== $db->db_name
            || ($plan['estado'] ?? '') !== 'PLAN') throw new RuntimeException('El plan no corresponde a esta base.');
        $ruta = $options['journal'] ?? '';
        $directorio = $ruta !== '' ? realpath(dirname($ruta)) : false;
        $raiz = realpath(dirname(__DIR__, 2));
        if ($ruta === '' || !str_starts_with($ruta, '/') || !$directorio || file_exists($ruta) || is_link($ruta)
            || str_starts_with($directorio.'/', $raiz.'/') || (fileperms($directorio) & 0077) !== 0) {
            throw new RuntimeException('Indica --journal con un archivo absoluto nuevo en directorio privado fuera de la aplicación.');
        }
        $guardar = static function (array $diario) use ($ruta, $directorio): void {
            $tmp = tempnam($directorio, '.descargas-');
            if ($tmp === false) throw new RuntimeException('No se pudo reservar el diario.');
            try {
                chmod($tmp, 0600);
                $payload = json_encode($diario, JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR)."\n";
                $file = fopen($tmp, 'wb');
                if ($file === false) throw new RuntimeException('No se pudo abrir el diario.');
                try {
                    for ($offset = 0, $total = strlen($payload); $offset < $total; $offset += $bytes) {
                        $bytes = fwrite($file, substr($payload, $offset));
                        if ($bytes === false || $bytes === 0) throw new RuntimeException('Diario incompleto.');
                    }
                    if (!fflush($file) || !fsync($file)) throw new RuntimeException('No se pudo sincronizar el diario.');
                } finally { fclose($file); }
                if (!rename($tmp, $ruta)) throw new RuntimeException('No se pudo publicar el diario.');
                $dir = fopen($directorio, 'r');
                if ($dir === false) throw new RuntimeException('No se pudo abrir el directorio del diario.');
                try { if (!fsync($dir)) throw new RuntimeException('No se pudo sincronizar el directorio del diario.'); }
                finally { fclose($dir); }
            } finally { if (is_file($tmp)) unlink($tmp); }
        };
        $resultado = DescargasDocumentosPermisos::preparar($pdo, $principal, true, $guardar, $plan['identidades'], $plan['antes_sha256']);
    } else {
        $resultado = DescargasDocumentosPermisos::preparar($pdo, $principal);
    }
    echo json_encode($resultado, JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR), PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, 'No se pudo preparar la descarga: '.($e instanceof PDOException ? 'error de catálogo o permisos de BD' : $e->getMessage()).PHP_EOL);
    exit(1);
}
