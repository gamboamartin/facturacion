<?php
declare(strict_types=1);
namespace gamboamartin\errores {
    final class errores {public static bool $error=false;public static array $out=['previo'];}
}
namespace gamboamartin\facturacion\models {
    final class fc_factura {
        public function __construct($pdo) {
            $_SESSION['error_resultado'][]='XML_Y_CREDENCIALES_FICTICIAS';
            \gamboamartin\errores\errores::$out[]='XML_Y_CREDENCIALES_FICTICIAS';
            \gamboamartin\errores\errores::$error=true;
            register_shutdown_function(static function():void {
                file_put_contents($GLOBALS['argv'][2],json_encode(['sesion'=>$_SESSION['error_resultado'],
                    'out'=>\gamboamartin\errores\errores::$out,'error'=>\gamboamartin\errores\errores::$error]));
            });
            echo 'XML_Y_CREDENCIALES_FICTICIAS';exit;
        }
    }
    final class _timbrado_lote {
        private $timbrar;
        public function __construct($pdo,$usuario,$autorizar,$timbrar) {$this->timbrar=$timbrar;}
        public function procesar($lote,$id) {return ($this->timbrar)($id);}
    }
}
namespace {
    final class Fila extends \PDOStatement {
        public function __construct() {}
        public function execute(?array $params=null):bool {return true;}
        public function fetchColumn(int $column=0):mixed {return 3;}
    }
    final class Conexion extends \PDO {
        public function __construct() {}
        public function prepare(string $query,array $options=[]):\PDOStatement|false {return new Fila();}
    }
    require $argv[1];
    final class Controlador {
        use \gamboamartin\facturacion\controllers\_timbrado_masivo;
        public \PDO $link;
        public function __construct() {$this->link=new Conexion();}
    }
    $_SERVER['REQUEST_METHOD']='POST';
    $_SESSION=['usuario_id'=>2,'fc_timbrado_csrf'=>'ficticio','error_resultado'=>['previo']];
    $_POST=['csrf'=>'ficticio','operacion'=>'procesar','lote_id'=>str_repeat('a',32),'factura_id'=>'1'];
    (new Controlador())->timbrado_masivo();
}
