<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
ini_set('display_errors', '0');
require dirname(__DIR__,2).'/config/database.php';
require __DIR__.'/_descargas_complemento_admin.php';
$options = getopt('', ['database:', 'apply', 'journal:']);
try {
    $db = new \config\database();
    if (($options['database'] ?? null) !== $db->db_name) {
        throw new RuntimeException('Indica el nombre exacto de la base configurada con --database.');
    }
    $aplicar = isset($options['apply']);
    $diario = $options['journal'] ?? null;
    if ($aplicar && (!is_string($diario) || !str_starts_with($diario, '/') || file_exists($diario))) {
        throw new RuntimeException('Indica un diario absoluto nuevo en un directorio privado fuera de la web.');
    }
    if ($aplicar) {
        $dir = realpath(dirname($diario));
        if ($dir === false || str_starts_with($dir.'/', realpath(dirname(__DIR__,2)).'/') || (fileperms($dir) & 0077) !== 0) {
            throw new RuntimeException('El directorio del diario debe ser privado y estar fuera de la web.');
        }
    }
    $pdo = new PDO('mysql:host='.$db->db_host.';dbname='.$db->db_name.';charset=utf8mb4',$db->db_user,$db->db_password,
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false]);
    $guardar = $aplicar ? static function(array $r) use ($diario): void {
        umask(0077);
        $f = fopen($diario,'x');
        if ($f === false) throw new RuntimeException('No se pudo crear el diario.');
        try {
            $contenido = json_encode($r,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR)."\n";
            if (fwrite($f,$contenido)!==strlen($contenido) || !fflush($f) || !fsync($f)) throw new RuntimeException('Diario incompleto.');
        } finally { fclose($f); }
    } : null;
    echo json_encode(DescargasComplementoAdmin::preparar($pdo,$aplicar,$guardar),JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR).PHP_EOL;
} catch(Throwable $e) {
    fwrite(STDERR,'No se pudo preparar la descarga: '.($e instanceof PDOException?'error de catálogo o permisos de BD':$e->getMessage()).PHP_EOL);
    exit(1);
}
