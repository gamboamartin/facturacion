<?php
declare(strict_types=1);
require_once __DIR__.'/errores_seguridad.php';
require_once (defined('RAIZ_REPOSITORIO_PRUEBAS') ? RAIZ_REPOSITORIO_PRUEBAS : getenv('FUENTE_FACTURACION')).'/controllers/_complemento_desde_factura.php';
final class StatementSeguridadCP extends PDOStatement {
    public function __construct(private PDOSeguridadCP $pdo){}
    public function execute(?array $params=null):bool {$this->pdo->args[]=$params;return true;}
    public function fetchColumn(int $column=0):mixed {return $this->pdo->permisos;}
    public function fetchAll(int $mode=PDO::FETCH_DEFAULT,mixed ...$args):array {
        $filas=[['seccion'=>'fc_factura','accion'=>'lista'],['seccion'=>'fc_factura','accion'=>'complemento_pago'],
            ['seccion'=>'fc_complemento_pago','accion'=>'alta_bd'],['seccion'=>'fc_complemento_pago','accion'=>'modifica']];
        return array_slice($filas,0,$this->pdo->permisos);
    }
}
final class PDOSeguridadCP extends PDO {
    public int $permisos=0;public array $sql=[];public array $args=[];public bool $transaccion=false;
    public function __construct(){}
    public function inTransaction():bool {return $this->transaccion;}
    public function prepare(string $query,array $options=[]):PDOStatement|false {$this->sql[]=$query;return new StatementSeguridadCP($this);}
}
final class ControladorSeguridadCP {
    use gamboamartin\facturacion\controllers\_complemento_desde_factura {permiso_complemento_desde_factura as public permiso;}
    public int $registro_id=1;
    public function __construct(public PDO $link){}
}
