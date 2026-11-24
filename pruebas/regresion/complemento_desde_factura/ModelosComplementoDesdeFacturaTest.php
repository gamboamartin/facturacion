<?php
declare(strict_types=1);
use gamboamartin\facturacion\models\{_complemento_desde_factura,RegistroModelosCP};
use gamboamartin\errores\errores;
use PHPUnit\Framework\TestCase;

/** Ejecuta crearModelos real con tres modelos dobles: acredita wiring, no cálculo fiscal. */
final class ModelosComplementoDesdeFacturaTest extends TestCase
{
    private PDOModelosCP $pdo;
    private _complemento_desde_factura $servicio;
    private array $cabecera=['codigo'=>'CPF-FICTICIO','codigo_bis'=>'HUELLA-FICTICIA','fc_csd_id'=>1];
    private array $pago=['fecha_pago'=>'2026-11-24 12:00:00','monto'=>'10.25'];
    private array $docto=['fc_factura_id'=>5,'imp_pagado'=>'10.25'];
    protected function setUp():void {
        require_once __DIR__.'/dobles_modelos.php';
        require_once RAIZ_REPOSITORIO_PRUEBAS.'/orm/_complemento_desde_factura.php';
        errores::$error=false;RegistroModelosCP::$llamadas=[];RegistroModelosCP::$fallar='';
        $this->pdo=new PDOModelosCP();$this->servicio=new _complemento_desde_factura($this->pdo,7,fn()=>true);
    }
    private function ejecutar():int {return (new ReflectionMethod($this->servicio,'crearModelos'))->invoke($this->servicio,$this->cabecera,$this->pago,$this->docto);}
    private function rechaza():void {try{$this->ejecutar();self::fail('Debe rechazar una cadena incompleta');}catch(InvalidArgumentException $e){self::assertNotSame('',$e->getMessage());}}
    public function testEncadenaIdsYConservaPayloadDeLosTresModelos():void {
        self::assertSame(11,$this->ejecutar());self::assertSame([
            ['cabecera',$this->cabecera],['pago',$this->pago+['fc_pago_id'=>22]],['docto',$this->docto+['fc_pago_pago_id'=>33]],
        ],RegistroModelosCP::$llamadas);self::assertSame([[11]],$this->pdo->args);self::assertFalse(errores::$error);
    }
    public function testFalloCabeceraNoCreaPagoNiDocto():void {
        RegistroModelosCP::$fallar='cabecera';$this->rechaza();self::assertSame(['cabecera'],array_column(RegistroModelosCP::$llamadas,0));self::assertSame([],$this->pdo->sql);
    }
    public function testSinContenedorDePagoNoCreaContrapartes():void {
        $this->pdo->contenedores=[];$this->rechaza();self::assertSame(['cabecera'],array_column(RegistroModelosCP::$llamadas,0));
    }
    public function testContenedoresDuplicadosNoEligeUnoArbitrario():void {
        $this->pdo->contenedores=[['id'=>22],['id'=>23]];$this->rechaza();self::assertSame(['cabecera'],array_column(RegistroModelosCP::$llamadas,0));
    }
    public function testFalloPagoNoCreaDocumentoRelacionado():void {
        RegistroModelosCP::$fallar='pago';$this->rechaza();self::assertSame(['cabecera','pago'],array_column(RegistroModelosCP::$llamadas,0));
    }
    public function testFalloDocumentoRelacionadoNoSeDevuelveComoExito():void {
        RegistroModelosCP::$fallar='docto';$this->rechaza();self::assertSame(['cabecera','pago','docto'],array_column(RegistroModelosCP::$llamadas,0));self::assertTrue(errores::$error);
    }
}
