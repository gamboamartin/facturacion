<?php declare(strict_types=1);
namespace Pruebas\Catalogo {
 class ConexionSinRed extends \PDO { public function __construct() {} }
 class Estado {
  public static array $clientes=[],$tipos=[],$precios=[],$calls=[]; public static ?string $fallo=null; public static $despues=null;
  public static function resultado(string $etapa,mixed $valor):mixed {self::$calls[]=$etapa;if(self::$fallo===$etapa)return (new \gamboamartin\errores\errores())->error('FALLO_SQL_FICTICIO_'.$etapa,['causa'=>'FICTICIA']);if(self::$despues!==null)(self::$despues)($etapa);return $valor;}
 }
}
namespace gamboamartin\errores {
 class errores { public static bool $error=false;public function error(string $mensaje,mixed $data):array {self::$error=true;return ['error'=>true,'mensaje_limpio'=>$mensaje,'data'=>$data];} }
}
namespace gamboamartin\comercial\models {
 class com_producto {
  public \PDO $link;public \gamboamartin\errores\errores $error;
  public function __construct(\PDO $link){$this->link=$link;$this->error=new \gamboamartin\errores\errores();}
  public function precio(int $com_cliente_id,int $com_producto_id):float|array {throw new \LogicException('No releer precio en catálogo');}
 }
 class com_cliente {
  public function __construct(public \PDO $link){}
  public function registro(int $registro_id,bool $columnas_en_bruto,bool $retorno_obj):mixed {
   \Pruebas\Catalogo\Estado::$calls[]=['cliente_filtro',$registro_id,$columnas_en_bruto,$retorno_obj];
   return \Pruebas\Catalogo\Estado::resultado('cliente',\Pruebas\Catalogo\Estado::$clientes[$registro_id]??(object)['com_tipo_cliente_id'=>1]);
  }
 }
 class com_conf_precio {
  public function __construct(public \PDO $link){}
  public function filtro_and(array $filtro,array $in=[]):mixed {\Pruebas\Catalogo\Estado::$calls[]=['tipo_filtro',$filtro];$rows=\Pruebas\Catalogo\Estado::$tipos[$filtro['com_tipo_cliente.id']]??[];\Pruebas\Catalogo\Estado::$calls[]=['in_catalogo',$in];if($in){$rows=array_values(array_filter($rows,fn($r)=>in_array((int)$r['com_producto_id'],$in['values'],true)));}return \Pruebas\Catalogo\Estado::resultado('tipo',(object)['registros'=>$rows,'n_registros'=>count($rows)]);}
 }
 class com_precio_cliente {
  public function __construct(public \PDO $link){}
  public function filtro_and(array $filtro,array $in=[]):mixed {\Pruebas\Catalogo\Estado::$calls[]=['precio_filtro',$filtro];$rows=\Pruebas\Catalogo\Estado::$precios[$filtro['com_cliente.id']]??[];\Pruebas\Catalogo\Estado::$calls[]=['in_catalogo',$in];if($in){$rows=array_values(array_filter($rows,fn($r)=>in_array((int)$r['com_producto_id'],$in['values'],true)));}return \Pruebas\Catalogo\Estado::resultado('precio',(object)['registros'=>$rows,'n_registros'=>count($rows)]);}
 }
}
namespace gamboamartin\facturacion\controllers {
 class _base_system {}
 class _ctl_partida {}
 class controlador_fc_partida extends _ctl_partida {}
 class controlador_fc_partida_nc extends _ctl_partida {}
 class controlador_fc_partida_cp extends _ctl_partida {}
 class PartidaPersonalizada extends controlador_fc_partida {}
}
