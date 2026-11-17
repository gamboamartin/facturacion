<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
final class TransicionPartidaTest extends TestCase {
 private function build(bool $baseOld,bool $ctlOld,bool $invoice=false):object {$_GET=$invoice?['seccion'=>'fc_factura','accion'=>'modifica']:['seccion'=>'fc_partida','accion'=>'lista'];require_once __DIR__.'/../fc_partida/soporte_partida.php';require_once $baseOld?__DIR__.'/_base_original.php':RAIZ_REPOSITORIO_PRUEBAS.'/controllers/_base.php';require_once $ctlOld?__DIR__.'/_ctl_partida_original.php':RAIZ_REPOSITORIO_PRUEBAS.'/controllers/_ctl_partida.php';require_once RAIZ_REPOSITORIO_PRUEBAS.'/controllers/controlador_fc_partida.php';return new gamboamartin\facturacion\controllers\controlador_fc_partida(new Pruebas\Partida\ConexionSinRed());}
 public function testBaseAnteriorNoRecibeParametroDesconocido():void {$c=$this->build(true,false);self::assertFalse($c->lista_get_data);self::assertSame([],(array)$c->config_recibida);self::assertSame(1,$c->inicializaciones);}
 public function testPadreAnteriorNoInvocaMetodoAusente():void {$c=$this->build(false,true);self::assertFalse($c->lista_get_data);self::assertSame([],(array)$c->config_recibida);self::assertSame(1,$c->inicializaciones);}
 public function testAmbosPadresAnterioresConservanRuta():void {$c=$this->build(true,true);self::assertFalse($c->lista_get_data);self::assertSame([],(array)$c->config_recibida);self::assertSame(1,$c->inicializaciones);}
 public function testContextoFacturaConPadresAnteriores():void {$c=$this->build(true,true,true);self::assertFalse($c->lista_get_data);self::assertSame('fc_factura',$_GET['seccion']);self::assertSame(1,$c->inicializaciones);}
}
