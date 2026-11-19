<?php
declare(strict_types=1);

namespace base\orm { class modelo {} }
namespace config {
    final class generales {
        public static string $raiz;
        public string $path_base;
        public function __construct() { $this->path_base = self::$raiz; }
    }
}
namespace gamboamartin\validacion { final class validacion {} }
namespace gamboamartin\facturacion\models {
    class fc_key_csd {
        public string $tabla = 'fc_key_csd';
        public string $archivo;
        public string $password;
        public function registro(int $registro_id): array {
            return ['doc_documento_ruta_absoluta' => $this->archivo, 'fc_csd_password' => $this->password];
        }
    }
    class fc_cer_csd extends fc_key_csd { public string $tabla = 'fc_cer_csd'; }
}
namespace {
    use gamboamartin\errores\errores;
    use gamboamartin\facturacion\models\_cert;
    use gamboamartin\facturacion\models\fc_key_csd;
    use gamboamartin\facturacion\models\fc_cer_csd;

    function comprobar(bool $valor, string $nombre): void {
        if (!$valor) { throw new \RuntimeException($nombre); }
        echo "PASS $nombre\n";
    }

    $raiz = dirname(__DIR__, 2);
    $fuenteErrores = getenv('FUENTE_ERRORES');
    $fuenteCert = $raiz . '/orm/_cert.php';
    if (!$fuenteErrores || !$fuenteCert
        || hash_file('sha256', $fuenteErrores) !== '4e307a08c4bea98be8a52a3789cb8f9a18e76c725b4112fc07ba84b2aa4911ad') {
        throw new \RuntimeException('Fuentes reales requeridas con hashes documentados');
    }
    foreach (['shell_exec', 'exec', 'system', 'proc_open', 'popen', 'fsockopen', 'stream_socket_client'] as $funcion) {
        if (function_exists($funcion)) { throw new \RuntimeException('Falta aislamiento del runner'); }
    }
    require $fuenteErrores;
    require getenv('FUENTE_SSL');
    require getenv('FUENTE_DOCUMENTO');
    require $raiz . '/vendor/autoload.php';
    require $fuenteCert;
    $directorio = sys_get_temp_dir() . '/qa-csd-consumidor-' . bin2hex(random_bytes(8));
    mkdir($directorio, 0700);
    mkdir($directorio . '/archivos', 0700);
    mkdir($directorio . '/archivos/doc_documento', 0700);
    mkdir($directorio . '/archivos/temporales', 0700);
    $contexto = \gamboamartin\facturacion\models\contexto_archivos_csd::iniciar($directorio);
    \config\generales::$raiz = $directorio . '/';
    $_SESSION = [];
    $fallo = null;
    try {
        $cnf = $directorio . '/openssl.cnf';
        file_put_contents($cnf, "[req]\ndistinguished_name=dn\n[dn]\n");
        $opciones = ['private_key_bits' => 2048, 'config' => $cnf, 'digest_alg' => 'sha256'];
        $clave = openssl_pkey_new($opciones);
        $csr = openssl_csr_new(['commonName' => 'QA FC3 FICTICIO SIN VALIDEZ'], $clave, $opciones);
        $cer = openssl_csr_sign($csr, null, $clave, 1, $opciones);
        openssl_x509_export($cer, $cerPem);
        $password = 'QA-ficticia-NO-REGISTRAR-$;&';
        openssl_pkey_export($clave, $keyPem, $password, $opciones);
        $der = fn(string $pem): string => base64_decode(preg_replace('/-----[^-]+-----|\s/', '', $pem), true);
        file_put_contents($directorio . '/cer.der', $der($cerPem));
        file_put_contents($directorio . '/key.der', $der($keyPem));
        $metodo = new \ReflectionMethod(_cert::class, 'genera_pem');
        $consumidor = new _cert();
        $modeloCer = new fc_cer_csd();
        $modeloCer->archivo = $directorio . '/cer.der';
        $modeloCer->password = $password;
        $modeloKey = new fc_key_csd();
        $modeloKey->archivo = $directorio . '/key.der';
        $modeloKey->password = $password;
        errores::$error = false;
        $resultadoCer = $metodo->invoke($consumidor, 1, $modeloCer);
        comprobar(!errores::$error && is_object($resultadoCer) && is_file($resultadoCer->file), 'consumidor_real_certificado_archivo');
        comprobar($resultadoCer->contenido === openssl_pkey_get_details($clave)['key'] . $cerPem, 'consumidor_real_contenido_historico');
        $resultadoKey = $metodo->invoke($consumidor, 2, $modeloKey);
        comprobar(!errores::$error && is_object($resultadoKey) && is_file($resultadoKey->file), 'consumidor_real_clave_archivo');
        $claveSalida = openssl_pkey_get_private($resultadoKey->contenido);
        comprobar($claveSalida !== false && openssl_x509_check_private_key($cer, $claveSalida), 'consumidor_real_par_exportado');
        $antes = glob($directorio . '/archivos/temporales/*/convertido.pem');
        $modeloKey->password = 'incorrecta';
        $resultadoError = $metodo->invoke($consumidor, 3, $modeloKey);
        comprobar(is_array($resultadoError) && errores::$error && glob($directorio . '/archivos/temporales/*/convertido.pem') === $antes, 'error_real_sin_salida_parcial');
        $serializado = json_encode([$resultadoError, $_SESSION, errores::$out]);
        comprobar(!str_contains($serializado, $password) && !str_contains($serializado, 'incorrecta')
            && !str_contains($serializado, 'PRIVATE KEY') && !str_contains($serializado, base64_encode($der($keyPem))), 'error_real_sesion_salida_sin_secretos');
        comprobar($contexto->revertir()['completo'] && glob($directorio . '/archivos/temporales/*') === [], 'contexto_retiro_temporales_consumer_real');
    } catch (\Throwable $error) {
        $fallo = 'Fallo de contrato: ' . get_class($error);
    } finally {
        $contexto->finalizar();
        $entradas = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directorio, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($entradas as $entrada) {
            if ($entrada->isLink() || $entrada->isFile()) { unlink($entrada->getPathname()); }
            else { rmdir($entrada->getPathname()); }
        }
        rmdir($directorio);
    }
    $informe = ['estado' => $fallo === null ? 'PASS' : 'BLOQUEADO', 'casos' => $fallo === null ? 7 : null,
        'fallo' => $fallo, 'limpieza' => !file_exists($directorio), 'sin_sql' => true,
        'sha256_fuente' => hash_file('sha256', getenv('FUENTE_SSL'))];

    echo json_encode($informe) . "\n";
    if ($informe['limpieza']) { echo "LIMPIEZA_VERIFICADA\n"; }
    exit($fallo === null && $informe['limpieza'] ? 0 : 1);
}
