<?php
declare(strict_types=1);
namespace gamboamartin\errores { final class errores { public static bool $error = false; } }
namespace gamboamartin\administrador\models {
    final class adm_accion {
        public static mixed $respuesta = true;
        public static array $llamadas = [];
        public function __construct(\PDO $link) {}
        public function permiso(string $accion, string $seccion): mixed {
            self::$llamadas[] = [$accion, $seccion];
            return self::$respuesta;
        }
    }
}
namespace gamboamartin\facturacion\models {
    final class fc_cer_csd {
        public static mixed $respuesta;
        public static array $consultas = [];
        public function __construct(\PDO $link) {}
        public function filtro_and(array $columnas, array $filtro): mixed {
            self::$consultas[] = [$columnas, $filtro];
            return self::$respuesta;
        }
    }
}
namespace {
    use gamboamartin\administrador\models\adm_accion;
    use gamboamartin\facturacion\models\{fc_cer_csd, csd_archivos_vista};
    use gamboamartin\errores\errores;
    require dirname(__DIR__, 2) . '/orm/csd_archivos_vista.php';
    final class ConexionFicticia extends PDO { public function __construct() {} }
    set_error_handler(static function(int $nivel, string $mensaje): never { throw new RuntimeException('Warning en prueba'); });
    $link = new ConexionFicticia();
    $servicio = new csd_archivos_vista();
    $casos = [];
    $exigir = static function(bool $ok, string $caso) use (&$casos): void {
        if (!$ok) { throw new RuntimeException('Fallo: '.$caso); }
        $casos[] = $caso;
    };
    $_SESSION = [];
    $exigir($servicio->cargar($link,20)['filas'] === [] && adm_accion::$llamadas === [], 'anonimo_sin_consulta');
    $_SESSION = ['activa'=>1,'usuario_id'=>2,'grupo_id'=>2];
    $exigir($servicio->cargar($link,0)['filas'] === [] && adm_accion::$llamadas === [], 'id_invalido_sin_consulta');
    adm_accion::$respuesta = false;
    $exigir($servicio->cargar($link,20)['filas'] === [] && fc_cer_csd::$consultas === [], 'denegado_sin_consulta');
    adm_accion::$respuesta = ['error'=>'simulado'];
    $exigir($servicio->cargar($link,20)['filas'] === [] && fc_cer_csd::$consultas === [], 'error_acl_cerrado');
    adm_accion::$respuesta = true;
    $fila = ['fc_cer_csd_id'=>15,'fc_cer_csd_fc_csd_id'=>20,'doc_documento_descripcion'=>'<script>alert(1)</script>',
        'fc_csd_password'=>'SECRETO_FICTICIO','ruta_absoluta'=>'/privado/key.pem','contenido'=>'PRIVATE KEY'];
    fc_cer_csd::$respuesta = (object)['registros'=>[$fila]];
    $resultado = $servicio->cargar($link,20);
    $exigir($resultado['filas'] === [['id'=>15,'descripcion'=>'<script>alert(1)</script>','tipo'=>'CER']], 'campos_publicos_exactos');
    $exigir(end(adm_accion::$llamadas) === ['get_data','fc_cer_csd'], 'acl_contrato_get_data');
    $exigir(end(fc_cer_csd::$consultas) === [['fc_cer_csd_id','fc_cer_csd_fc_csd_id','doc_documento_descripcion'],['fc_csd.id'=>20]], 'consulta_acotada_columnas_y_csd');
    $controlador = (object)['archivos_cer_vista'=>$resultado];
    ob_start();require dirname(__DIR__,2).'/views/fc_csd/archivos_cer.php';$html=ob_get_clean();
    $exigir(str_contains($html,'&lt;script&gt;') && !str_contains($html,'<script>'), 'render_escapa_xss');
    $exigir(!str_contains($html,'SECRETO') && !str_contains($html,'/privado') && !str_contains($html,'href=') && !str_contains($html,'PRIVATE KEY'), 'render_sin_secretos_ni_enlaces');
    fc_cer_csd::$respuesta = (object)['registros'=>[array_replace($fila,['fc_cer_csd_fc_csd_id'=>21])]];
    $exigir($servicio->cargar($link,20) === ['estado'=>'error','filas'=>[]], 'fila_otro_csd_rechazada');
    fc_cer_csd::$respuesta = (object)['registros'=>[]];
    $controlador->archivos_cer_vista=$servicio->cargar($link,20);
    ob_start();require dirname(__DIR__,2).'/views/fc_csd/archivos_cer.php';$html=ob_get_clean();
    $exigir(str_contains($html,'No hay certificados CER'), 'vacio_explicito');
    fc_cer_csd::$respuesta = ['error'=>'simulado'];
    $exigir($servicio->cargar($link,20) === ['estado'=>'error','filas'=>[]], 'error_modelo_sin_datos');
    $registro=json_decode(file_get_contents(__DIR__.'/registro.json'),true);
    if ($casos!==$registro['casos']) {throw new RuntimeException('Registro distinto');}
    echo json_encode(['estado'=>'PASS','casos'=>$casos,'alcance'=>'Servicio y plantilla reales, ACL/ORM doblados, sin DB/HTTP']),PHP_EOL;
}
