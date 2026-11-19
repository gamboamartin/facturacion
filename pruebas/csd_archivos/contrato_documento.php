<?php
declare(strict_types=1);
namespace base\orm { class modelo { public object $error; } }
namespace {
    $raiz = dirname(__DIR__, 2);
    $documento = getenv('FUENTE_DOCUMENTO');
    $dependencias = getenv('AUTOLOAD_PRUEBAS');
    $plugins = getenv('FUENTE_PLUGIN_FILES');
    if (!$documento || !$dependencias || !$plugins) { throw new RuntimeException('Faltan fuentes explícitas'); }
    require $dependencias;
    require $plugins;
    require $documento;
    require $raiz . '/orm/archivos_csd_operacion.php';
    require $raiz . '/orm/contexto_archivos_csd.php';
    require $raiz . '/orm/doc_documento_csd.php';
    $_SESSION = [];
    function verificar(bool $v, string $caso): void {
        if (!$v) { throw new RuntimeException($caso); }
        echo "PASS $caso\n";
    }
    $d = sys_get_temp_dir() . '/qa-contexto-' . bin2hex(random_bytes(8));
    mkdir($d, 0700); mkdir("$d/archivos", 0700);
    mkdir("$d/archivos/doc_documento", 0700); mkdir("$d/archivos/temporales", 0700);
    $contexto = null;
    try {
        $r = new ReflectionClass(gamboamartin\documento\models\doc_documento::class);
        $obj = $r->newInstanceWithoutConstructor();
        $obj->error = new gamboamartin\errores\errores();
        $metodo = $r->getMethod('guardar_archivo_alta');
        $ruta = "$d/archivos/doc_documento/BASE";
        verificar($metodo->invoke($obj, 'FICTICIO', $ruta) === strtolower($ruta)
            && file_get_contents(strtolower($ruta)) === 'FICTICIO', 'hook_default_contrato_historico');
        $contexto = gamboamartin\facturacion\models\contexto_archivos_csd::iniciar($d);
        verificar(gamboamartin\facturacion\models\contexto_archivos_csd::actual() === $contexto, 'contexto_explicito');
        $rs = new ReflectionClass(gamboamartin\facturacion\models\doc_documento_csd::class);
        $sub = $rs->newInstanceWithoutConstructor(); $sub->error = new gamboamartin\errores\errores();
        $guardar = $rs->getMethod('guardar_archivo_alta');
        $rutaNueva = "$d/archivos/doc_documento/NUEVO";
        verificar($guardar->invoke($sub, 'FICTICIO PRIVADO', $rutaNueva) === strtolower($rutaNueva)
            && (fileperms(strtolower($rutaNueva)) & 0777) === 0600, 'subclass_privado_normalizado');
        $rechazo = $guardar->invoke($sub, 'NO REEMPLAZAR', $rutaNueva);
        verificar(is_array($rechazo) && file_get_contents(strtolower($rutaNueva)) === 'FICTICIO PRIVADO', 'colision_conserva_previo');
        gamboamartin\errores\errores::$error = false;
        $t = $contexto->convertir_temporal(static function (string $ruta): null {
            file_put_contents($ruta, 'PEM FICTICIO'); chmod($ruta, 0600); return null;
        });
        verificar(is_file($t['ruta']) && (fileperms(dirname($t['ruta'])) & 0777) === 0700, 'temporal_directorio_privado');
        $fin = $contexto->confirmar();
        verificar($fin['completo'] && !file_exists($t['ruta']) && file_exists(strtolower($rutaNueva)), 'commit_limpia_temporal_conserva_documento');
        $contexto->finalizar();
        verificar(gamboamartin\facturacion\models\contexto_archivos_csd::actual() === null, 'contexto_finalizado');
        $contexto = gamboamartin\facturacion\models\contexto_archivos_csd::iniciar($d);
        try {
            $contexto->convertir_temporal(static function (string $ruta): never {
                file_put_contents($ruta, 'PARCIAL FICTICIO'); chmod($ruta, 0600);
                throw new RuntimeException('Fallo sintético');
            });
        } catch (RuntimeException) {}
        $fin = $contexto->revertir();
        verificar($fin['completo'] && glob("$d/archivos/temporales/*") === [], 'excepcion_temporal_compensada');
        $journal = new class(["$d/archivos/doc_documento", "$d/archivos/temporales"])
            extends \gamboamartin\facturacion\models\archivos_csd_operacion {
            protected function retirar_archivo(string $ruta): bool { return false; }
        };
        $propiedad = new ReflectionProperty($contexto, 'journal');
        $propiedad->setValue($contexto, $journal);
        $contexto->crear_documento("$d/archivos/doc_documento/confirmado", 'DOCUMENTO FICTICIO');
        $t = $contexto->convertir_temporal(static function (string $ruta): null {
            file_put_contents($ruta, 'CLAVE FICTICIA NO REAL'); chmod($ruta, 0600); return null;
        });
        $fin = $contexto->confirmar();
        verificar(!$fin['completo'] && is_file($t['ruta']), 'cleanup_fallido_no_silenciado');
        $recibo = $contexto->preservar_recuperacion('commit_confirmado');
        $rutaRecibo = "$d/archivos/temporales/recuperacion-csd-" . $recibo['id'] . '/recibo.json';
        $datos = json_decode(file_get_contents($rutaRecibo), true, flags: JSON_THROW_ON_ERROR);
        verificar($recibo['guardado'] && (fileperms($rutaRecibo) & 0777) === 0600
            && (fileperms(dirname($rutaRecibo)) & 0777) === 0700, 'recibo_privado_verificado');
        verificar(count($datos['evidencia']['archivos']) === 1
            && $datos['evidencia']['archivos'][0]['ruta'] === $t['ruta']
            && $datos['evidencia']['archivos'][0]['sha256'] === hash_file('sha256', $t['ruta'])
            && $datos['eliminacion_automatica_autorizada'] === false, 'recibo_exacto_solo_temporal_pendiente');
        verificar(!str_contains(json_encode($recibo), $d)
            && !str_contains(file_get_contents($rutaRecibo), 'CLAVE FICTICIA NO REAL'), 'recibo_sin_contenido_salida_sin_ruta');
        $contexto->revertir();
        verificar(is_file("$d/archivos/doc_documento/confirmado"), 'commit_impide_revertir_documentos');
        chmod("$d/archivos/temporales", 0500);
        ob_start();
        $fallido = $contexto->preservar_recuperacion('commit_confirmado');
        $salidaFallida = ob_get_clean();
        chmod("$d/archivos/temporales", 0700);
        verificar(!$fallido['guardado'] && $fallido['codigo'] === 'recuperacion_no_acreditada'
            && $salidaFallida === '' && !str_contains(json_encode($fallido), $d), 'fallo_recibo_sin_ruta_publica');


    } finally {
        $contexto?->finalizar();
        $limpiar = static function (string $ruta) use (&$limpiar): void {
            foreach (glob($ruta . '/*') as $f) {
                if (is_link($f) || is_file($f)) { if (!unlink($f)) { throw new RuntimeException('Retiro fixture falló'); } }
                else { $limpiar($f); }
            }
            if (!rmdir($ruta)) { throw new RuntimeException('Retiro directorio falló'); }
        };
        $limpiar($d);
        if (file_exists($d)) { throw new RuntimeException('Fixture no retirado'); }
    }
    echo "LIMPIEZA_VERIFICADA\n";
}
