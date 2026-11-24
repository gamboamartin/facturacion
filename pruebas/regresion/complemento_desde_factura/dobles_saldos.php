<?php
declare(strict_types=1);
namespace gamboamartin\errores {
    final class errores {public static bool $error=false;public function error(string $mensaje,mixed $data):array {self::$error=true;return ['error'=>true,'mensaje'=>$mensaje,'data'=>$data];}}
}
namespace gamboamartin\facturacion\models {
    final class DatosSaldosCP {
        public static string $fallar='';public static int $modifica=0;public static float $total=100.0;
        public static array $nc=[];public static array $cp=[];
        public static function error(string $tipo):bool {if(self::$fallar===$tipo){\gamboamartin\errores\errores::$error=true;return true;}return false;}
    }
    final class fc_factura {
        public mixed $modelo_etapa;
        public function __construct(\PDO $link){}
        public function registro(int $registro_id):array {if(DatosSaldosCP::error('factura'))return ['error'=>true];return ['fc_factura_total'=>DatosSaldosCP::$total,'fc_factura_comision_cliente'=>999];}
        public function modifica_bd(mixed ...$args):never {DatosSaldosCP::$modifica++;throw new \LogicException('No deben ejecutarse comisiones ni modificar la cabecera');}
    }
    final class fc_factura_etapa {public function __construct(\PDO $link){}}
    final class fc_nc_rel {
        public function __construct(\PDO $link){}
        public function filtro_and(array $filtro):\stdClass {DatosSaldosCP::error('nc');return (object)['registros'=>DatosSaldosCP::$nc];}
    }
    final class fc_docto_relacionado {
        public function __construct(\PDO $link){}
        public function filtro_and(array $filtro):\stdClass {DatosSaldosCP::error('cp');return (object)['registros'=>DatosSaldosCP::$cp];}
    }
}
namespace {
    final class StatementSaldosCP extends \PDOStatement {
        public function __construct(private PDOSaldosCP $pdo){}
        public function execute(?array $params=null):bool {$this->pdo->params[]=$params;if($this->pdo->modo==='throw')throw new \PDOException('CREDENCIAL-FICTICIA-NO-PUBLICAR');if($this->pdo->modo==='execute_false')return false;$this->pdo->aplicados++;return true;}
        public function rowCount():int {return $this->pdo->filas;}
    }
    final class PDOSaldosCP extends \PDO {
        public int $filas=1;public string $modo='ok';public array $sql=[];public array $params=[];public int $aplicados=0;
        public function __construct(){}
        public function prepare(string $query,array $options=[]):\PDOStatement|false {$this->sql[]=$query;return $this->modo==='prepare_false'?false:new StatementSaldosCP($this);}
    }
}
