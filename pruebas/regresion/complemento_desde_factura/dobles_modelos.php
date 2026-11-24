<?php
declare(strict_types=1);
namespace gamboamartin\errores {
    final class errores {public static bool $error=false;}
}
namespace gamboamartin\facturacion\models {
    final class RegistroModelosCP {
        public static array $llamadas=[];
        public static string $fallar='';
        public static function registrar(string $clase,array $registro,int $id):object|array {
            self::$llamadas[]=[$clase,$registro];
            if(self::$fallar===$clase){\gamboamartin\errores\errores::$error=true;return ['error'=>true];}
            return (object)['registro_id'=>$id];
        }
    }
    class fc_complemento_pago {
        public function __construct(\PDO $link){}
        public function alta_registro(array $registro):object|array {return RegistroModelosCP::registrar('cabecera',$registro,11);}
    }
    class fc_pago_pago {
        public function __construct(\PDO $link){}
        public function alta_registro(array $registro):object|array {return RegistroModelosCP::registrar('pago',$registro,33);}
    }
    class fc_docto_relacionado {
        public function __construct(\PDO $link){}
        public function alta_registro(array $registro):object|array {return RegistroModelosCP::registrar('docto',$registro,44);}
    }
}
namespace {
    final class StatementModelosCP extends \PDOStatement {
        public function __construct(private PDOModelosCP $pdo){}
        public function execute(?array $params=null):bool {$this->pdo->args[]=$params;return true;}
        public function fetchAll(int $mode=\PDO::FETCH_DEFAULT,mixed ...$args):array {return $this->pdo->contenedores;}
    }
    final class PDOModelosCP extends \PDO {
        public array $sql=[];
        public array $args=[];
        public array $contenedores=[['id'=>22]];
        public function __construct(){}
        public function prepare(string $query,array $options=[]):\PDOStatement|false {$this->sql[]=$query;return new StatementModelosCP($this);}
    }
}
