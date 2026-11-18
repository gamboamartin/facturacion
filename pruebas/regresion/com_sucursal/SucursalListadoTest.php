<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
final class SucursalListadoTest extends TestCase {
 private function controller():object {
  require_once __DIR__.'/soporte.php';
  require_once RAIZ_REPOSITORIO_PRUEBAS.'/controllers/controlador_com_sucursal.php';
  return new gamboamartin\facturacion\controllers\controlador_com_sucursal();
 }
 public function testOmitePrecargaDescartada():void {$c=$this->controller();$c->lista();self::assertTrue($c->lista_get_data);self::assertSame(0,$c->precargas);}
 public function testRepetirListaNoPrecarga():void {$c=$this->controller();$c->lista();$c->lista();self::assertSame(0,$c->precargas);}
 public function testGetDataConservaHerencia():void {$c=$this->controller();self::assertSame(['permisos'=>'heredados','pagina'=>10],$c->get_data());self::assertSame('gamboamartin\\comercial\\controllers\\controlador_com_sucursal',(new ReflectionMethod($c,'get_data'))->getDeclaringClass()->getName());}
 public function testNoSobrescribeOperaciones():void {$c=$this->controller();$r=new ReflectionClass($c);$own=array_filter($r->getMethods(),fn($m)=>$m->getDeclaringClass()->getName()===$r->getName());self::assertSame([],$own);}
}
