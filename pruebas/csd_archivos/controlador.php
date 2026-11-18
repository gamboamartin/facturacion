<?php
declare(strict_types=1);

namespace base\orm { class modelo { public object $error; } }
namespace config { class generales { public string $path_base; public function __construct() { $this->path_base = \qa\Estado::$raiz . "/"; } } }
namespace qa {
    final class Estado {
        public static string $raiz;
        public static string $password = 'QA-FICTICIA-NO-VALIDA-1811';
        public static array $filas = [];
        public static array $eventos = [];
        public static string $modo;
    }
    final class Transaccion extends \PDO {
        private array $previas = [];
        private bool $activa = false;
        public function inTransaction(): bool { return $this->activa; }
        public function __construct() {}
        public function beginTransaction(): bool {
            $this->activa = true;
            $this->previas = Estado::$filas;
            Estado::$eventos[] = 'begin';
            return true;
        }
        public function commit(): bool {
            Estado::$eventos[] = 'commit';
            if (Estado::$modo === 'commit_false') { return false; }
            if (Estado::$modo === 'commit_throws') { throw new \RuntimeException(Estado::$password); }
            $this->activa = false; return true;
        }
        public function rollBack(): bool {
            if (Estado::$modo === 'rollback_throws') { Estado::$eventos[] = 'rollback_incierto'; throw new \RuntimeException(Estado::$password); }
            $this->activa = false;
            Estado::$filas = $this->previas;
            Estado::$eventos[] = 'rollback';
            return true;
        }
    }
}
namespace gamboamartin\system {
    class system {
        public \PDO $link;
        public object $errores;
        public string $tabla = 'fc_csd';
        public function alta_bd(bool $header, bool $ws = false): array|\stdClass {
            if (\qa\Estado::$modo === 'cleanup_fallido') {
                $base = \qa\Estado::$raiz . '/archivos/';
                $journal = new class([$base . 'doc_documento', $base . 'temporales'])
                    extends \gamboamartin\facturacion\models\archivos_csd_operacion {
                    protected function retirar_archivo(string $ruta): bool { return false; }
                };
                $contexto = \gamboamartin\facturacion\models\contexto_archivos_csd::actual();
                (new \ReflectionProperty($contexto, 'journal'))->setValue($contexto, $journal);
            }
            \qa\Estado::$filas['csd'] = ['id' => 1];
            return (object)['registro_id' => 1];
        }
        public function out_alta(...$args): \stdClass {
            if (\qa\Estado::$modo === 'error_retorno') { \gamboamartin\errores\errores::$error = true; }
            return new \stdClass();
        }
        public function retorno_error(string $mensaje, mixed $data, bool $header, bool $ws): array {
            return ['mensaje' => $mensaje, 'data' => $data];
        }
    }
    final class actions {
        public function init_alta_bd(): string {
            if (\qa\Estado::$modo === 'error_accion') { \gamboamartin\errores\errores::$error = true; }
            return 'modifica';
        }
    }
}
namespace gamboamartin\documento\models {
    final class doc_extension {
        public function __construct(\PDO $link) {}
        public function extension(string $name_file): string { return pathinfo($name_file, PATHINFO_EXTENSION); }
    }
}
namespace gamboamartin\facturacion\models {
    class fc_key_csd {
        public string $tabla = 'fc_key_csd';
        public function __construct(\PDO $link) {}
        public function alta_registro(array $registro): \stdClass {
            $destino = \qa\Estado::$raiz . '/archivos/doc_documento/' . $this->tabla;
            $ref = new \ReflectionClass(doc_documento_csd::class);
            $doc = $ref->newInstanceWithoutConstructor();
            $doc->error = new \gamboamartin\errores\errores();
            $resultado = $ref->getMethod('guardar_archivo_alta')->invoke($doc,
                file_get_contents($_FILES['documento']['tmp_name']), $destino);
            if (is_array($resultado)) { throw new \RuntimeException('Escritura ficticia falló'); }
            \qa\Estado::$filas[$this->tabla] = ['id' => 1, 'fc_csd_id' => 1];
            return (object)['registro_id' => 1];
        }
    }
    class fc_cer_csd extends fc_key_csd { public string $tabla = 'fc_cer_csd'; }
    final class fc_csd {
        public function __construct(\PDO $link) {}
        public function genera_pems(int $fc_csd_id): array|\stdClass {
            $ssl = new \gamboamartin\plugins\ssl();
            $base = \qa\Estado::$raiz . '/archivos/doc_documento/';
            $contexto = contexto_archivos_csd::actual();
            // Este colaborador doble acredita propagación, no la validación del ORM real.
            if (\qa\Estado::$modo === 'error_par') {
                $par = $ssl->valida_par_cer_key($base . 'fc_cer_csd', $base . 'fc_key_csd', \qa\Estado::$password);
                if (\gamboamartin\errores\errores::$error) { return $par; }
                throw new \RuntimeException('El par ficticio distinto no fue rechazado');
            }
            $password = in_array(\qa\Estado::$modo, ['error_key', 'rollback_throws'], true)
                ? 'QA-ERRONEA' : \qa\Estado::$password;
            $key = $contexto->convertir_temporal(fn($ruta) => $ssl->genera_key_pem($password, $base . 'fc_key_csd', $ruta));
            if (\gamboamartin\errores\errores::$error) { return $key['resultado']; }
            $contexto->crear_documento($base . 'key.pem', file_get_contents($key['ruta']));
            $cer = $contexto->convertir_temporal(fn($ruta) => $ssl->genera_cer_pem($base . 'fc_cer_csd', $ruta));
            if (\gamboamartin\errores\errores::$error) { return $cer['resultado']; }
            $contexto->crear_documento($base . 'cer.pem', file_get_contents($cer['ruta']));
            return new \stdClass();
        }
    }
}
namespace {
    $raiz = dirname(__DIR__, 2);
    $controlador = getenv('FUENTE_CONTROLADOR_CSD');
    $errores = getenv('FUENTE_ERRORES');
    if (!$controlador || !$errores || !is_file($controlador) || !is_file($errores)) {
        throw new \RuntimeException('Se requieren fuentes reales explícitas');
    }
    require $errores;
    require getenv('FUENTE_SSL');
    require getenv('FUENTE_DOCUMENTO');
    require $raiz . '/vendor/autoload.php';
    require $controlador;
    \qa\Estado::$modo = $argv[1] ?? 'exito';
    $dir = sys_get_temp_dir() . '/qa-csd-controlador-' . bin2hex(random_bytes(8));
    mkdir($dir, 0700);
    mkdir($dir . '/archivos', 0700);
    mkdir($dir . '/archivos/doc_documento', 0700);
    mkdir($dir . '/archivos/temporales', 0700);
    \qa\Estado::$raiz = $dir;
    file_put_contents($dir . '/openssl.cnf', "[req]\ndistinguished_name=dn\n[dn]\n");
    $opciones = ['private_key_bits' => 2048, 'config' => $dir . '/openssl.cnf', 'digest_alg' => 'sha256'];
    $key = openssl_pkey_new($opciones);
    $csr = openssl_csr_new(['commonName' => 'QA CONTROLADOR FICTICIO'], $key, $opciones);
    $cert = openssl_csr_sign($csr, null, $key, 1, $opciones);
    openssl_x509_export($cert, $cerPem);
    $keyEntrada = \qa\Estado::$modo === 'error_par' ? openssl_pkey_new($opciones) : $key;
    openssl_pkey_export($keyEntrada, $keyPem, \qa\Estado::$password, $opciones);
    file_put_contents($dir . '/entrada.cer', \qa\Estado::$modo === 'error_cer' ? 'CER FICTICIO INVALIDO' : $cerPem);
    file_put_contents($dir . '/entrada.key', $keyPem);
    chmod($dir . '/entrada.key', 0600);
    $_POST = ['password' => \qa\Estado::$password];
    $_SESSION = [];
    $_FILES = [
        'fc_cer_csd_doc' => ['name' => 'ficticio.cer', 'tmp_name' => $dir . '/entrada.cer'],
        'fc_key_csd_doc' => ['name' => 'ficticio.key', 'tmp_name' => $dir . '/entrada.key'],
    ];
    ob_start();
    register_shutdown_function(static function () use ($dir, $keyPem): void {
        $salida = ob_get_clean();
        $archivos = array_map('basename', glob($dir . '/archivos/doc_documento/*'));
        sort($archivos);
        $modo = \qa\Estado::$modo;
        $error = in_array($modo, ['error_key', 'error_cer', 'rollback_throws', 'error_accion', 'error_par'], true);
        $eventosEsperados = ['begin', $modo === 'rollback_throws' ? 'rollback_incierto' : ($error ? 'rollback' : 'commit')];
        $archivosEsperados = match ($modo) {
            'error_key', 'error_cer', 'error_accion', 'error_par' => [],
            'rollback_throws' => ['fc_cer_csd', 'fc_key_csd'],
            default => ['cer.pem', 'fc_cer_csd', 'fc_key_csd', 'key.pem']
        };
        $sinSecretos = !str_contains($salida, \qa\Estado::$password)
            && !str_contains($salida, $keyPem) && !str_contains($salida, 'BEGIN ENCRYPTED PRIVATE KEY')
            && !str_contains($salida, $dir);
        $temporales = glob($dir . '/archivos/temporales/*/*');
        $recibos = glob($dir . '/archivos/temporales/recuperacion-csd-*/recibo.json');
        $reciboValido = true;
        if (in_array($modo, ['commit_throws', 'commit_false', 'rollback_throws', 'cleanup_fallido'], true)) {
            $reciboValido = count($recibos) === 1;
            if ($reciboValido) {
                $datos = json_decode(file_get_contents($recibos[0]), true, flags: JSON_THROW_ON_ERROR);
                $reciboValido = $datos['estado_sql'] === (in_array($modo, ['commit_throws', 'commit_false'], true) ? 'commit_incierto' : ($modo === 'cleanup_fallido' ? 'commit_confirmado' : 'rollback_incierto'))
                    && $datos['eliminacion_automatica_autorizada'] === false
                    && (fileperms($recibos[0]) & 0777) === 0600;
                foreach ($datos['evidencia']['archivos'] as $registro) {
                    $reciboValido = $reciboValido && file_exists($registro['ruta'])
                        && hash_file('sha256', $registro['ruta']) === $registro['sha256'];
                }
            }
        }

        $ok = \qa\Estado::$eventos === $eventosEsperados && $archivos === $archivosEsperados
            && $sinSecretos && $reciboValido && \gamboamartin\facturacion\models\contexto_archivos_csd::actual() === null;
        if (!in_array($modo, ['commit_throws', 'commit_false', 'rollback_throws', 'cleanup_fallido'], true)) {
            $ok = $ok && $temporales === [];
        }
        if ($error && $modo !== 'rollback_throws') {
            $ok = $ok && \qa\Estado::$filas === [];
        }
        $limpiar = static function (string $ruta) use (&$limpiar): void {
            foreach (glob($ruta . '/*') as $f) {
                if (is_link($f) || is_file($f)) { if (!unlink($f)) { throw new \RuntimeException('Retiro fixture falló'); } }
                else { $limpiar($f); }
            }
            if (!rmdir($ruta)) { throw new \RuntimeException('Retiro directorio falló'); }
        };
        $limpiar($dir);
        echo json_encode([
            'estado' => $ok ? 'PASS' : 'FAIL', 'escenario' => \qa\Estado::$modo,
            'eventos' => \qa\Estado::$eventos, 'filas_finales' => count(\qa\Estado::$filas),
            'archivos_antes_limpieza' => $archivos, 'salida_sin_secretos_ficticios' => $sinSecretos,
            'limpieza' => !file_exists($dir),
            'metodo_real' => 'controlador_fc_csd::alta_bd e inserta_files_doc',
            'temporales_antes_limpieza' => count($temporales), 'recibo_verificado' => $reciboValido,
            'limite' => 'Caracterización de residuos. Transacción, modelos y almacén dobles. No alta integral ni recuperación aprobada.'
        ], JSON_UNESCAPED_SLASHES), PHP_EOL;
        if (!$ok) { exit(1); }
    });
    $ref = new \ReflectionClass(\gamboamartin\facturacion\controllers\controlador_fc_csd::class);
    $c = $ref->newInstanceWithoutConstructor();
    $c->link = new \qa\Transaccion();
    $c->errores = new \gamboamartin\errores\errores();
    echo json_encode($c->alta_bd(false));
}
