<?php
namespace base\orm {
 class _modelo_parent {
  public array $registro=[]; public int $calls=0; public array $keys=[]; public array|\stdClass $result;
  public function __construct() { $this->result=(object)['registro_id'=>7]; }
  public function get_codigo_aleatorio():string{return 'SYNTHETIC-CODE';}
  public function alta_bd(array $keys_integra_ds=['codigo','descripcion']):array|\stdClass {$this->calls++;$this->keys=$keys_integra_ds;return $this->result;}
 }
}
namespace gamboamartin\errores { class errores { public function error(string $mensaje,array $data):array{return ['error'=>true,'mensaje'=>$mensaje];} } }
namespace {
 require dirname(__DIR__,3).'/orm/cc_medidor.php';
 function check(bool $ok):void {if(!$ok)throw new \RuntimeException('pure_contract_failed');}
 function fresh():\gamboamartin\facturacion\models\cc_medidor {
  $r=new \ReflectionClass(\gamboamartin\facturacion\models\cc_medidor::class);
  $m=$r->newInstanceWithoutConstructor(); $m->result=(object)['registro_id'=>7];return $m;
 }
 $n=0;
 foreach([null,0,-1,'0','01','1.0','1e2',' 1','2147483648',[],true,1.5] as $bad){$m=fresh();$m->registro=['cc_tipo_medidor_id'=>$bad];check(is_array($m->alta_bd()) && $m->calls===0);$n++;}
 foreach([1,'2','2147483647'] as $valid){$m=fresh();$m->registro=['cc_tipo_medidor_id'=>$valid,'descripcion'=>'FICTICIO'];$_POST=['cc_tipo_medidor_id'=>999];$expected=$m->result;$r=$m->alta_bd(['descripcion']);check($r===$expected && $m->calls===1 && $m->registro['cc_tipo_medidor_id']===(int)$valid && $m->registro['codigo']==='SYNTHETIC-CODE' && $m->keys===['descripcion']);$n++;}
 $m=fresh();$m->registro=['cc_tipo_medidor_id'=>1,'codigo'=>'EXPLICIT'];$m->result=['error'=>true];check($m->alta_bd()===['error'=>true] && $m->registro['codigo']==='EXPLICIT');$n++;
 check(!isset($_SESSION));
 echo json_encode(['pure_cases'=>$n,'passed'=>true,'db_connections'=>0,'real_orm_verified'=>false]),"\n";
}
