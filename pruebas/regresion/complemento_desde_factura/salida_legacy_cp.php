<?php
declare(strict_types=1);
namespace gamboamartin\errores {final class errores {public static bool $error=false;public static array $out=['previo'];}}
namespace {
    require_once getenv('FUENTE_FACTURACION').'/orm/_complemento_desde_factura.php';
    require_once __DIR__.'/soporte_seguridad.php';
    final class ControladorSalidaLegacyCP {
        use \gamboamartin\facturacion\controllers\_complemento_desde_factura;
        public int $registro_id=1;
        public function __construct(public PDO $link){}
        private function servicio_complemento_desde_factura():\gamboamartin\facturacion\models\_complemento_desde_factura {
            return new \gamboamartin\facturacion\models\_complemento_desde_factura($this->link,7,static function():bool {
                $GLOBALS['legacy_invocado']++;
                $_SESSION['error_resultado'][]=['data'=>'XML-CREDENCIAL-FICTICIA-NO-PUBLICAR'];
                \gamboamartin\errores\errores::$out[]='XML-CREDENCIAL-FICTICIA-NO-PUBLICAR';\gamboamartin\errores\errores::$error=true;
                echo 'XML-CREDENCIAL-FICTICIA-NO-PUBLICAR';
                if(($GLOBALS['argv'][1]??'')==='exit')exit;
                throw new \InvalidArgumentException('No se pudo guardar el complemento completo.');
            });
        }
    }
    $_SERVER['REQUEST_METHOD']='POST';$_SESSION=['usuario_id'=>7,'fc_complemento_factura_csrf'=>'TOKEN-FICTICIO','error_resultado'=>['previo']];
    $_POST=['csrf'=>'TOKEN-FICTICIO','clave_solicitud'=>str_repeat('a',32),'monto'=>'116.00','fecha_pago'=>'2026-11-24T12:00','cat_sat_forma_pago_id'=>1,'com_tipo_cambio_id'=>1,'fc_csd_id'=>1];
    $estado=$argv[2]??'array';
    if($estado==='true')\gamboamartin\errores\errores::$error=true;
    if($estado==='null')$_SESSION['error_resultado']=null;
    if($estado==='ausente')unset($_SESSION['error_resultado']);
    $GLOBALS['legacy_invocado']=0;$pdo=new PDOSeguridadCP();$pdo->permisos=4;
    register_shutdown_function(static function():void {register_shutdown_function(static function():void {
        fwrite(STDERR,json_encode(['sesion_presente'=>array_key_exists('error_resultado',$_SESSION),'sesion'=>$_SESSION['error_resultado']??null,'out'=>\gamboamartin\errores\errores::$out,'error'=>\gamboamartin\errores\errores::$error,'legacy_invocado'=>$GLOBALS['legacy_invocado']]));
    });});
    (new ControladorSalidaLegacyCP($pdo))->complemento_pago();
}
