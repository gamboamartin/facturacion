<?php declare(strict_types=1);
namespace gamboamartin\errores {
 class errores {public static bool $error=false;public function error(string $mensaje,mixed $data):array {self::$error=true;return ['mensaje'=>$mensaje,'data'=>$data];}}
}
namespace gamboamartin\facturacion\controllers {
 class _base {
  public array $events=[]; public ?string $fail=null;public \stdClass $row_upd;public \gamboamartin\errores\errores $errores;
  public function __construct(){$this->row_upd=(object)['codigo'=>'PRESERVAR','cantidad'=>9,'total'=>99];$this->errores=new \gamboamartin\errores\errores();}
  public function init_alta():array|string { $this->events[]='init';if($this->fail==='init')return $this->errores->error('FALLO_INIT',[]);return 'TEMPLATE_ORIGINAL';}
  public function inputs(array $keys_selects):array|\stdClass {$this->events[]='inputs';if($this->fail==='inputs')return $this->errores->error('FALLO_INPUTS',[]);return (object)['html'=>'FICTICIO'];}
  public function retorno_error(string $mensaje,mixed $data,bool $header,bool $ws):array {return ['mensaje'=>$mensaje,'data'=>$data,'header'=>$header,'ws'=>$ws];}
 }
}
