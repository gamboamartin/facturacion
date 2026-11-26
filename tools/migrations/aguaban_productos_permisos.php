<?php
declare(strict_types=1);

// Opt-in: agrega acceso básico sin ejecutar controladores ni operaciones de producto.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
ini_set('display_errors', '0');
$opciones = getopt('', ['database:', 'apply', 'rollback:', 'report:', 'production-vpn', 'development']);
require dirname(__DIR__, 2).'/vendor/autoload.php';
require __DIR__.'/_aguaban_productos_permisos.php';
try {
    $db = new \config\database();
    if (($opciones['database'] ?? null) !== $db->db_name) {
        throw new RuntimeException('Indica --database con la base configurada exacta.');
    }
    $escritura = isset($opciones['apply']) || isset($opciones['rollback']);
    $desarrollo = isset($opciones['development']);
    if ($escritura && !in_array($db->db_host, ['localhost', '127.0.0.1', '::1'], true)) {
        throw new RuntimeException('Esta autorización necesita una base local del entorno seleccionado.');
    }
    if ($escritura && !$desarrollo && (!isset($opciones['production-vpn'])
            || $db->db_name !== 'aguaban'
            || parse_url((new \config\views())->url, PHP_URL_HOST) !== '10.99.0.1')) {
        throw new RuntimeException('Confirma --production-vpn en la interfaz Aguaban 10.99.0.1.');
    }
    if (isset($opciones['apply'], $opciones['rollback'])) {
        throw new RuntimeException('Elige aplicar o revertir, no ambas operaciones.');
    }
    $pdo = new PDO('mysql:host='.$db->db_host.';dbname='.$db->db_name.';charset=utf8mb4', $db->db_user, $db->db_password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]);
    if (isset($opciones['rollback'])) {
        $diario = json_decode(file_get_contents($opciones['rollback']), true, flags: JSON_THROW_ON_ERROR);
        $resultado = AguabanProductosPermisos::revertir($pdo, $diario, $desarrollo);
    } elseif (isset($opciones['apply'])) {
        $ruta = $opciones['report'] ?? '';
        $directorio = $ruta !== '' ? realpath(dirname($ruta)) : false;
        $raiz = realpath(dirname(__DIR__, 2));
        if ($ruta === '' || !str_starts_with($ruta, '/') || !$directorio || file_exists($ruta) || is_link($ruta)
            || str_starts_with($directorio.'/', $raiz.'/')) {
            throw new RuntimeException('Indica --report con un archivo nuevo, absoluto y fuera de la aplicación.');
        }
        $guardar = static function (array $diario) use ($ruta, $directorio): void {
            $temporal = tempnam($directorio, '.aguaban-productos-');
            if ($temporal === false) {
                throw new RuntimeException('No se pudo reservar el diario de autorización.');
            }
            try {
                chmod($temporal, 0600);
                $contenido = json_encode($diario, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)."\n";
                $archivo = fopen($temporal, 'wb');
                if ($archivo === false) {
                    throw new RuntimeException('No se pudo abrir el diario de autorización.');
                }
                try {
                    $total = strlen($contenido);
                    for ($escritos = 0; $escritos < $total; $escritos += $cantidad) {
                        $cantidad = fwrite($archivo, substr($contenido, $escritos));
                        if ($cantidad === false || $cantidad === 0) {
                            throw new RuntimeException('La escritura del diario quedó incompleta.');
                        }
                    }
                    if (!fflush($archivo) || !fsync($archivo)) {
                        throw new RuntimeException('No se pudo sincronizar el diario de autorización.');
                    }
                } finally {
                    fclose($archivo);
                }
                if (!rename($temporal, $ruta)) {
                    throw new RuntimeException('No se pudo publicar el diario de autorización.');
                }
                $carpeta = fopen($directorio, 'r');
                if ($carpeta === false) {
                    throw new RuntimeException('No se pudo abrir el directorio del diario.');
                }
                try {
                    if (!fsync($carpeta)) {
                        throw new RuntimeException('No se pudo sincronizar el directorio del diario.');
                    }
                } finally {
                    fclose($carpeta);
                }
            } finally {
                if (is_file($temporal)) {
                    unlink($temporal);
                }
            }
        };
        $resultado = AguabanProductosPermisos::preparar($pdo, true, $desarrollo, $guardar);
    } else {
        $resultado = AguabanProductosPermisos::preparar($pdo, false, $desarrollo);
    }
    echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR), PHP_EOL;
} catch (Throwable $error) {
    fwrite(STDERR, 'No se pudo autorizar productos de Aguaban: '.($error instanceof PDOException
            ? 'revisa el catálogo o los permisos de BD.' : $error->getMessage()).PHP_EOL);
    exit(1);
}
