<?php
declare(strict_types=1);

// Instalador explícito. Nunca se ejecuta desde una pantalla ni constructor del CRUD.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
ini_set('display_errors', '0');
$opciones = getopt('', ['database:', 'apply', 'development']);
require dirname(__DIR__, 2).'/config/database.php';
require __DIR__.'/_aguaban_ruta.php';
try {
    $db = new \config\database();
    if (($opciones['database'] ?? null) !== $db->db_name) {
        throw new RuntimeException('Indica --database con la base configurada exacta.');
    }
    $aplicar = isset($opciones['apply']);
    if ($aplicar && (!isset($opciones['development']) || $db->db_name !== 'facturacion'
            || !in_array($db->db_host, ['localhost', '127.0.0.1', '::1'], true))) {
        throw new RuntimeException('Este instalador solo aplica en la base local facturacion con --development.');
    }
    $pdo = new PDO('mysql:host='.$db->db_host.';dbname='.$db->db_name.';charset=utf8mb4', $db->db_user, $db->db_password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]);
    echo json_encode(AguabanRutaMigracion::preparar($pdo, $aplicar), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR), PHP_EOL;
} catch (Throwable $error) {
    fwrite(STDERR, 'No se pudo preparar rutas de Aguaban: '.($error instanceof PDOException ? 'revisa el catálogo o los permisos de BD.' : $error->getMessage()).PHP_EOL);
    exit(1);
}
