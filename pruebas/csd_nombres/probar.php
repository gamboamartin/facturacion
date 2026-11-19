<?php
declare(strict_types=1);
namespace gamboamartin\errores {
    final class errores {
        public static bool $error = false;
        public function error(string $mensaje, mixed $data): array { return ['error'=>true,'mensaje'=>$mensaje]; }
    }
}
namespace gamboamartin\system {
    class system { public \PDO $link; public object $errores; }
}
namespace gamboamartin\documento\models {
    final class doc_extension {
        public static array $nombres = [];
        public static bool $fallar = false;
        public function __construct(\PDO $link) {}
        public function extension(string $name_file): array|string {
            self::$nombres[] = $name_file;
            if (self::$fallar) {
                \gamboamartin\errores\errores::$error = true;
                return ['error'=>true];
            }
            return pathinfo($name_file, PATHINFO_EXTENSION);
        }
    }
}
namespace {
    use gamboamartin\documento\models\doc_extension;
    use gamboamartin\errores\errores;
    require dirname(__DIR__,2).'/controllers/controlador_fc_csd.php';
    final class ConexionFicticia extends PDO { public function __construct() {} }
    set_error_handler(static function(): never { throw new RuntimeException('Warning inesperado'); });
    $ref = new ReflectionClass(\gamboamartin\facturacion\controllers\controlador_fc_csd::class);
    $controller = $ref->newInstanceWithoutConstructor();
    $controller->link = new ConexionFicticia();
    $controller->errores = new errores();
    $maqueta = $ref->getMethod('maqueta_file');
    $nombre = $ref->getMethod('file_name_extension');
    $casos = [];
    $exigir = static function(bool $ok,string $caso) use (&$casos): void {
        if (!$ok) { throw new RuntimeException('Fallo: '.$caso); }
        $casos[] = $caso;
    };
    $_FILES = ['fc_cer_csd_doc'=>['name'=>'certificado.cer','tmp_name'=>'/ficticio/cer'],
        'fc_key_csd_doc'=>['name'=>'llave.key','tmp_name'=>'/ficticio/key']];
    $originales = $_FILES;
    $cer = $maqueta->invoke($controller,'fc_cer_csd_doc')['documento'];
    $exigir(preg_match('/^[0-9]{14}\.cer$/D',$cer['name'])===1 && $cer['tmp_name']==='/ficticio/cer','cer_extension_y_contenido_independientes');
    $key = $maqueta->invoke($controller,'fc_key_csd_doc')['documento'];
    $exigir(preg_match('/^[0-9]{14}\.key$/D',$key['name'])===1 && $key['tmp_name']==='/ficticio/key','key_extension_y_contenido_independientes');
    $exigir(doc_extension::$nombres===['certificado.cer','llave.key'],'validador_recibe_archivo_correspondiente');
    $exigir($_FILES['fc_cer_csd_doc']===$originales['fc_cer_csd_doc'] && $_FILES['fc_key_csd_doc']===$originales['fc_key_csd_doc'],'entradas_originales_intactas');
    $exigir(preg_match('/^[0-9]{14}\.key$/D',$nombre->invoke($controller))===1,'default_historico_key_conservado');
    $_FILES['fc_cer_csd_doc']['name']=str_repeat('a',240).'.CER';
    $exigir(preg_match('/^[0-9]{14}\.CER$/D',$nombre->invoke($controller,'fc_cer_csd_doc'))===1,'longitud_salida_acotada_y_formato_delegado');
    unset($_FILES['fc_key_csd_doc']);
    $_FILES['fc_cer_csd_doc']['name']='certificado.cer';
    $exigir(preg_match('/^[0-9]{14}\.cer$/D',$nombre->invoke($controller,'fc_cer_csd_doc'))===1,'cer_no_requiere_nombre_key');
    doc_extension::$fallar=true;
    $fallo=$maqueta->invoke($controller,'fc_cer_csd_doc');
    $exigir(errores::$error && ($fallo['error']??false)===true && !isset($_FILES['documento']),'error_validador_no_produce_documento');
    echo json_encode(['estado'=>'PASS','casos'=>$casos,'alcance'=>'Metodos privados reales; constructor y doc_extension doblados. No cambia politica de formatos ni acredita UI.'],JSON_THROW_ON_ERROR).PHP_EOL;
}
