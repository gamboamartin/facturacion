<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class NotaCreditoErroresTest extends TestCase {
 private PDO $pdo;
 protected function setUp():void {
  require_once __DIR__.'/DoblesNotaCreditoErrores.php';
  $base=defined('RAIZ_REPOSITORIO_PRUEBAS')?RAIZ_REPOSITORIO_PRUEBAS:getenv('RAIZ_REPO');
  if(!$base)throw new RuntimeException('Debe indicar RAIZ_REPO aislada');
  require_once $base.'/orm/fc_nota_credito.php';require_once $base.'/controllers/controlador_fc_nota_credito.php';
  \gamboamartin\errores\errores::$error=false;
  $this->pdo=new class extends PDO {public function __construct(){} public function prepare(string $query,array $options=[]):PDOStatement|false {throw new RuntimeException('SQL prohibido');}public function query(string $query,?int $fetchMode=null,mixed ...$fetchModeArgs):PDOStatement|false {throw new RuntimeException('SQL prohibido');}public function exec(string $statement):int|false {throw new RuntimeException('SQL prohibido');}};
 }
 private function modelo():\gamboamartin\facturacion\models\fc_nota_credito {return new \gamboamartin\facturacion\models\fc_nota_credito($this->pdo);}
 private function modeloFallido():\gamboamartin\facturacion\models\fc_nota_credito {$m=$this->modelo();$m->fallar=true;$m->respuesta=['codigo'=>'ERROR_FICTICIO'];return $m;}
 public function testAltaPropagaErrorConContexto():void {$m=$this->modeloFallido();self::assertSame(['mensaje'=>'Error al insertar','data'=>$m->respuesta],$m->alta_bd());self::assertTrue(\gamboamartin\errores\errores::$error);}
 public function testModificacionPropagaErrorConContexto():void {$m=$this->modeloFallido();self::assertSame(['mensaje'=>'Error al modificar','data'=>$m->respuesta],$m->modifica_bd(['descripcion'=>'FICTICIO'],501));self::assertTrue(\gamboamartin\errores\errores::$error);}
 public function testEliminacionPropagaErrorConContexto():void {$m=$this->modeloFallido();self::assertSame(['mensaje'=>'Error al eliminar','data'=>$m->respuesta],$m->elimina_bd(501));self::assertTrue(\gamboamartin\errores\errores::$error);}
 public function testEliminacionExitosaPreparaNueveDependenciasNc():void {
  $m=$this->modelo();self::assertSame($m->respuesta,$m->elimina_bd(501));self::assertSame(['id'=>501],$m->captura);
  foreach(['modelo_etapa'=>'fc_nota_credito_etapa','modelo_partida'=>'fc_partida_nc','modelo_documento'=>'fc_nota_credito_documento','modelo_email'=>'fc_email_nc','modelo_sello'=>'fc_cfdi_sellado_nc','modelo_relacionada'=>'fc_nota_credito_relacionada','modelo_notificacion'=>'fc_notificacion_nc','modelo_relacion'=>'fc_relacion_nc','modelo_uuid_ext'=>'fc_uuid_nc'] as $prop=>$clase){self::assertInstanceOf('gamboamartin\\facturacion\\models\\'.$clase,$m->$prop);self::assertSame($this->pdo,$m->$prop->link);}
 }
 public function testModificacionConservaOpcionesPorDefecto():void {$m=$this->modelo();$m->modifica_bd(['descripcion'=>'FICTICIO'],501);self::assertSame(['registro'=>['descripcion'=>'FICTICIO'],'id'=>501,'reactiva'=>false,'verifica_permite_transaccion'=>true],$m->captura);self::assertSame($this->pdo,$m->modelo_etapa->link);}
 public function testConstructorPropagaDesactivacionAtributosCriticos():void {$m=new \gamboamartin\facturacion\models\fc_nota_credito($this->pdo,false);self::assertSame(['valida_atributos_criticos'=>false],$m->captura);}
 private function errorControlador(bool $header,bool $ws):void {
  $r=new ReflectionClass(\gamboamartin\facturacion\controllers\controlador_fc_nota_credito::class);$c=$r->newInstanceWithoutConstructor();$c->modelo=$this->modelo();$c->fallar=true;
  self::assertSame(['mensaje'=>'Error al generar template','data'=>$c->respuesta,'header'=>$header,'ws'=>$ws],$c->alta($header,$ws));self::assertSame($c->modelo,$c->modelo_entidad);self::assertTrue(\gamboamartin\errores\errores::$error);
 }
 public function testErrorVistaSinHeaderSinWs():void {$this->errorControlador(false,false);}
 public function testErrorVistaConHeaderSinWs():void {$this->errorControlador(true,false);}
 public function testErrorVistaSinHeaderConWs():void {$this->errorControlador(false,true);}
}
