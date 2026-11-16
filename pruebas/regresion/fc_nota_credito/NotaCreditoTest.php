<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class NotaCreditoTest extends TestCase
{
 protected function setUp():void {
  require_once __DIR__.'/DoblesNotaCredito.php';
  $base=defined('RAIZ_REPOSITORIO_PRUEBAS')?RAIZ_REPOSITORIO_PRUEBAS:getenv('RAIZ_REPO');
  if(!$base)throw new RuntimeException('Debe indicar RAIZ_REPO del repositorio aislado');
  require_once $base.'/orm/fc_nota_credito.php';
  require_once $base.'/controllers/controlador_fc_nota_credito.php';
  \gamboamartin\errores\errores::$error=false;
 }
 private function modelo(array $campos=[]):\gamboamartin\facturacion\models\fc_nota_credito {
  $pdo=new class extends PDO {public function __construct(){} public function prepare(string $query,array $options=[]):PDOStatement|false {throw new RuntimeException('SQL prohibido');} public function query(string $query,?int $fetchMode=null,mixed ...$fetchModeArgs):PDOStatement|false {throw new RuntimeException('SQL prohibido');} public function exec(string $statement):int|false {throw new RuntimeException('SQL prohibido');}};
  \gamboamartin\facturacion\models\_transacciones_fc::$campos_fixture=$campos;
  return new \gamboamartin\facturacion\models\fc_nota_credito($pdo);
 }
 public function testConstructorSinColumnaOpcional():void {
  $m=$this->modelo(['id']);self::assertFalse(isset($m->columnas['com_tipo_producto']),'Debe retirar JOIN inexistente');
 }
 public function testConstructorConColumnaOpcional():void {
  $m=$this->modelo(['id','com_tipo_producto_id']);self::assertTrue(isset($m->columnas['com_tipo_producto']),'Debe conservar JOIN disponible');
 }
 public function testMetadatosYCalculosDeCabecera():void {
  $m=$this->modelo();self::assertSame('fc_nota_credito',$m->captura['tabla']);self::assertSame('fc_nota_credito_id',$m->key_fc_id);self::assertSame('Notas de crédito',$m->etiqueta);
  self::assertSame(['fc_nota_credito_descuento'=>'IFNULL(fc_nota_credito.total_descuento,0)','fc_nota_credito_uuid'=>"IFNULL(fc_nota_credito.folio_fiscal,'SIN UUID')"],$m->captura['columnas_extra']);
 }
 public function testAltaPreservaDatosYActivaSaldo():void {
  $m=$this->modelo();$m->registro=['descripcion'=>'NC FICTICIA','total'=>116.00];$r=$m->alta_bd();
  self::assertSame($m->respuesta,$r);self::assertSame(['descripcion'=>'NC FICTICIA','total'=>116.00,'aplica_saldo'=>'activo'],$m->captura['registro']);
  self::assertInstanceOf(\gamboamartin\facturacion\models\fc_email_nc::class,$m->modelo_email);self::assertInstanceOf(\gamboamartin\facturacion\models\fc_nota_credito_etapa::class,$m->modelo_etapa);
 }
 public function testModificacionDelegaValoresYOpciones():void {
  $m=$this->modelo();$datos=['descripcion'=>'NC AJUSTADA FICTICIA'];$r=$m->modifica_bd($datos,501,true,false);
  self::assertSame($m->respuesta,$r);self::assertSame(['registro'=>$datos,'id'=>501,'reactiva'=>true,'verifica_permite_transaccion'=>false],$m->captura);
 }
 private function verificarAltaControlador(bool $header,bool $ws):void {
  $ref=new ReflectionClass(\gamboamartin\facturacion\controllers\controlador_fc_nota_credito::class);$c=$ref->newInstanceWithoutConstructor();$c->modelo=$this->modelo();$r=$c->alta($header,$ws);
  self::assertSame($c->modelo,$c->modelo_entidad);self::assertSame(compact('header','ws'),$c->captura);self::assertSame($c->respuesta,$r);
 }
 public function testAltaControladorSinHeaderSinWs():void {$this->verificarAltaControlador(false,false);}
 public function testAltaControladorConHeaderSinWs():void {$this->verificarAltaControlador(true,false);}
 public function testAltaControladorSinHeaderConWs():void {$this->verificarAltaControlador(false,true);}
}
