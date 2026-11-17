<?php
declare(strict_types=1);
namespace gamboamartin\errores { class errores { public static bool $error=false; public function error(string $mensaje,mixed $data): array { self::$error=true;return ["error"=>true,"mensaje"=>$mensaje,"data"=>$data]; } } }
namespace config { class generales { public bool $aplica_relacion_layout_factura=false; } }
namespace gamboamartin\facturacion\models {
#[\AllowDynamicProperties] class _transacciones_fc {
 public bool $fallarConsulta=false; public \gamboamartin\errores\errores $error;
 public array $registro=[]; public array $llamadas=[]; public array $registros=[];
 public function __construct(public \PDO $link, public string $tabla, public array $columnas_extra, public bool $valida_atributos_criticos) { $this->error=new \gamboamartin\errores\errores(); }
 public function filtro_and(array $filtro=[],array $filtro_rango=[],array $columnas=[]): \stdClass { $llamada=compact('filtro','filtro_rango');if($columnas)$llamada['columnas']=$columnas;$this->llamadas[]=$llamada;\gamboamartin\errores\errores::$error=$this->fallarConsulta;return (object)['registros'=>$this->registros]; }
}
class Dependencia { public function __construct(public \PDO $link){} }
class fc_partida extends Dependencia {} class fc_retenido extends Dependencia {} class fc_traslado extends Dependencia {} class fc_email extends Dependencia {}
}
namespace gamboamartin\facturacion\controllers {
#[\AllowDynamicProperties] class _base_system_fc {
 public bool $fallarPadre=false;
 public array $llamadas=[];
 public function retorno_error(string $mensaje,mixed $data,bool $header,bool $ws): array { return ["error"=>true,"mensaje"=>$mensaje,"data"=>$data,"header"=>$header,"ws"=>$ws]; }
 public function alta(bool $header,bool $ws=false): array|string { $this->llamadas[]=compact('header','ws');\gamboamartin\errores\errores::$error=$this->fallarPadre;return ['vista'=>'alta_ficticia']; }
 public function modifica(bool $header,bool $ws=false): array|\stdClass { $this->llamadas[]=compact('header','ws');\gamboamartin\errores\errores::$error=$this->fallarPadre;return (object)['vista'=>'modifica_ficticia']; }
}
class controlador_fc_partida { public function __construct(public \PDO $link){} }
}

namespace gamboamartin\comercial\models {
class com_sucursal {
 public static bool $fallar=false; public static array $solicitudes=[]; public int $registro_id;
 public function __construct(public \PDO $link){}
 public function obten_data(array $columnas): array { self::$solicitudes[]=['registro_id'=>$this->registro_id,'columnas'=>$columnas];\gamboamartin\errores\errores::$error=self::$fallar;return ['com_cliente_id'=>700001]; }
}
}
