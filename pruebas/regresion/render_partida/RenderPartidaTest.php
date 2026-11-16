<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use gamboamartin\errores\errores;
final class RenderPartidaTest extends TestCase {
 protected function setUp():void {require_once __DIR__.'/soporte_render.php';require_once RAIZ_REPOSITORIO_PRUEBAS.'/controllers/_ctl_partida.php';require_once __DIR__.'/doble_render.php';errores::$error=false;}
 public function testAltaConservaTemplateYRenderUnico():void {$c=new Pruebas\Render\PartidaDoble();self::assertSame('TEMPLATE_ORIGINAL',$c->alta(false));self::assertSame(['init','selects','inputs'],$c->events);}
 public function testPreparacionNoRenderiza():void {$c=new Pruebas\Render\PartidaDoble();$p=$c->prepara_alta(false);self::assertSame('TEMPLATE_ORIGINAL',$p->template);self::assertSame(['init','selects'],$c->events);self::assertSame(-1,$p->keys_selects['fc_factura_id']->id_selected);}
 public function testDefaultsYCamposPreviosConservados():void {$c=new Pruebas\Render\PartidaDoble();$c->prepara_alta(false);self::assertSame('PRESERVAR',$c->row_upd->codigo);self::assertSame(1,$c->row_upd->cantidad);foreach(['valor_unitario','descuento','subtotal','total'] as $k)self::assertSame(0,$c->row_upd->$k);}
 public function testFalloInitDetieneYConservaFlags():void {$c=new Pruebas\Render\PartidaDoble();$c->fail='init';$r=$c->alta(true,true);self::assertTrue(errores::$error);self::assertSame(['init'],$c->events);self::assertSame('Error al inicializar alta',$r['mensaje']);self::assertTrue($r['header']);self::assertTrue($r['ws']);}
 public function testFalloSelectsNoRenderiza():void {$c=new Pruebas\Render\PartidaDoble();$c->fail='selects';$r=$c->alta(false,true);self::assertSame(['init','selects'],$c->events);self::assertSame('Error al inicializar selects',$r['mensaje']);self::assertFalse($r['header']);self::assertTrue($r['ws']);}
 public function testFalloInputsConservaContrato():void {$c=new Pruebas\Render\PartidaDoble();$c->fail='inputs';$r=$c->alta(false,false);self::assertSame(['init','selects','inputs'],$c->events);self::assertSame('Error al obtener inputs',$r['mensaje']);self::assertFalse($r['header']);self::assertFalse($r['ws']);}
 public function testAltaRepetidaReinicializaSinDuplicarRender():void {$c=new Pruebas\Render\PartidaDoble();$c->alta(false);$c->row_upd->cantidad=50;$c->alta(false);self::assertSame(1,$c->row_upd->cantidad);self::assertSame(['init','selects','inputs','init','selects','inputs'],$c->events);}
}
