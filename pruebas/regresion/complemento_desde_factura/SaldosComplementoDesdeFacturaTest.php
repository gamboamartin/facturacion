<?php
declare(strict_types=1);
use gamboamartin\facturacion\models\{_saldos_fc,DatosSaldosCP};
use gamboamartin\errores\errores;
use PHPUnit\Framework\TestCase;

/** Cálculo real de saldos; las lecturas ORM son dobles y la escritura PDO se captura. */
final class SaldosComplementoDesdeFacturaTest extends TestCase {
    private PDOSaldosCP $pdo;
    protected function setUp():void {
        require_once __DIR__.'/dobles_saldos.php';require_once RAIZ_REPOSITORIO_PRUEBAS.'/orm/_saldos_fc.php';
        errores::$error=false;DatosSaldosCP::$fallar='';DatosSaldosCP::$modifica=0;DatosSaldosCP::$total=100;
        DatosSaldosCP::$nc=[['fc_nota_credito_aplica_saldo'=>'activo','fc_nc_rel_monto_aplicado_factura'=>10],['fc_nota_credito_aplica_saldo'=>'inactivo','fc_nc_rel_monto_aplicado_factura'=>999]];
        DatosSaldosCP::$cp=[['fc_complemento_pago_aplica_saldo'=>'activo','fc_docto_relacionado_imp_pagado'=>30],['fc_complemento_pago_aplica_saldo'=>'inactivo','fc_docto_relacionado_imp_pagado'=>999]];
        $this->pdo=new PDOSaldosCP();
    }
    protected function tearDown():void {self::assertSame(0,DatosSaldosCP::$modifica,'Nunca debe entrar a modifica_bd ni recalcular comisiones');}
    private function ejecutar(int $id=9):mixed {return (new _saldos_fc())->regenera_saldos($id,$this->pdo);}
    public function testActualizaSoloCuatroImportesSaldoSesentaSinComisiones():void {
        $r=$this->ejecutar();self::assertFalse(errores::$error);self::assertInstanceOf(stdClass::class,$r);self::assertSame(9,$r->registro_id);
        self::assertCount(1,$this->pdo->sql);$sql=$this->pdo->sql[0];self::assertMatchesRegularExpression('/^UPDATE\s+fc_factura\s+SET\s+/i',$sql);
        preg_match('/SET\s+(.+)\s+WHERE/is',$sql,$m);$campos=[];foreach(explode(',',$m[1]) as $parte){$campos[]=trim(explode('=',$parte)[0]," `\t\n");}
        sort($campos);self::assertSame(['monto_pago_cp','monto_pago_nc','monto_saldo_aplicado','saldo'],$campos);
        self::assertSame(['monto_pago_nc'=>10.0,'monto_pago_cp'=>30.0,'monto_saldo_aplicado'=>40.0,'saldo'=>60.0,'id'=>9],$this->pdo->params[0]);self::assertSame(1,$this->pdo->aplicados);
    }
    public function testConservaRedondeoPorPagoYPoliticaAplicaSaldo():void {
        DatosSaldosCP::$cp=[['fc_complemento_pago_aplica_saldo'=>'activo','fc_docto_relacionado_imp_pagado'=>10.125],['fc_complemento_pago_aplica_saldo'=>'activo','fc_docto_relacionado_imp_pagado'=>2.344],['fc_complemento_pago_aplica_saldo'=>'inactivo','fc_docto_relacionado_imp_pagado'=>900]];
        DatosSaldosCP::$nc=[['fc_nota_credito_aplica_saldo'=>'activo','fc_nc_rel_monto_aplicado_factura'=>5.005]];
        $this->ejecutar();$p=$this->pdo->params[0];self::assertEqualsWithDelta(5.01,$p['monto_pago_nc'],0.000001);self::assertEqualsWithDelta(12.47,$p['monto_pago_cp'],0.000001);self::assertEqualsWithDelta(82.52,$p['saldo'],0.000001);
    }
    public function testSaldosIdenticosConRowCountCeroSiguenSiendoExito():void {
        $this->pdo->filas=0;$r=$this->ejecutar();self::assertFalse(errores::$error);self::assertSame(0,$r->n_registros);self::assertSame(1,$this->pdo->aplicados);
    }
    private function errorLectura(string $tipo):void {DatosSaldosCP::$fallar=$tipo;$r=$this->ejecutar();self::assertTrue(errores::$error);self::assertTrue((bool)$r['error']);self::assertSame([],$this->pdo->sql);self::assertSame(0,$this->pdo->aplicados);}
    public function testFalloFacturaNoActualizaNada():void {$this->errorLectura('factura');}
    public function testFalloNotasNoActualizaNada():void {$this->errorLectura('nc');}
    public function testFalloPagosNoActualizaNada():void {$this->errorLectura('cp');}
    public function testIdInvalidoNoActualizaNada():void {$r=$this->ejecutar(0);self::assertTrue(errores::$error);self::assertNotEmpty($r['error']);self::assertSame(0,$this->pdo->aplicados);}
    private function errorEscritura(string $modo):void {$this->pdo->modo=$modo;$r=$this->ejecutar();self::assertTrue(errores::$error);self::assertNotEmpty($r['error']);self::assertSame(0,$this->pdo->aplicados);self::assertStringNotContainsString('CREDENCIAL-FICTICIA',json_encode($r));}
    public function testPrepareFalseNoSeReportaComoExito():void {$this->errorEscritura('prepare_false');}
    public function testExecuteFalseNoSeReportaComoExito():void {$this->errorEscritura('execute_false');}
    public function testExcepcionPdoNoFiltraContenidoNiSeReportaExito():void {$this->errorEscritura('throw');}
}
