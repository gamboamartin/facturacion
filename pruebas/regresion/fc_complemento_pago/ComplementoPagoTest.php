<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use gamboamartin\facturacion\models\fc_complemento_pago;
use gamboamartin\facturacion\controllers\controlador_fc_complemento_pago;
use gamboamartin\errores\errores;

/** Pruebas de unidad aisladas: clases reales bajo prueba, padres y validación dobles. */
final class ComplementoPagoTest extends TestCase {
    private fc_complemento_pago $modelo;
    protected function setUp(): void {
        require_once __DIR__.'/soporte_cp.php';
        $raiz=defined('RAIZ_REPOSITORIO_PRUEBAS') ? RAIZ_REPOSITORIO_PRUEBAS : getenv('RAIZ_REPO');
        if (!$raiz) throw new RuntimeException('Defina RAIZ_REPO explícitamente.');
        require_once $raiz.'/orm/fc_complemento_pago.php';
        require_once $raiz.'/controllers/controlador_fc_complemento_pago.php';
        $this->modelo=(new ReflectionClass(fc_complemento_pago::class))->newInstanceWithoutConstructor();
        $this->modelo->validacion=new \Pruebas\Complemento\ValidadorAislado();
        $this->modelo->error=new errores();
        errores::$error=false;
    }
    private function invocar(string $metodo,array $args): mixed { return (new ReflectionMethod($this->modelo,$metodo))->invokeArgs($this->modelo,$args); }
    private function tasa(float $factor): string { return $this->invocar('tasa_o_cuota',[['cat_sat_factor_factor'=>$factor]]); }
    private function impuesto(string $tipo): array {
        return (array)$this->invocar('integra_fc_row_p_part',[[
            'fc_'.$tipo.'_p_part_base_p'=>1000.00,'cat_sat_tipo_impuesto_codigo'=>'002','cat_sat_tipo_factor_codigo'=>'Tasa',
            'cat_sat_factor_factor'=>0.16,'fc_'.$tipo.'_p_part_importe_p'=>160.00],$tipo]);
    }
    private function listado(): stdClass {
        $this->modelo->tabla='fc_complemento_pago';
        $controlador=(new ReflectionClass(controlador_fc_complemento_pago::class))->newInstanceWithoutConstructor();
        $controlador->modelo=$this->modelo;
        return $controlador->init_datatable();
    }
    public function testPartidaConReferenciasFicticias(): void {
        self::assertSame(['com_producto_id'=>91002,'cantidad'=>1,'descripcion'=>'Pago','valor_unitario'=>0,'descuento'=>0,'total'=>0,'total_retenciones'=>0,'cat_sat_unidad_id'=>91001,'fc_complemento_pago_id'=>91003,'cat_sat_conf_imps_id'=>999],$this->invocar('fc_partida_cp_ins',[91001,91002,91003]));
    }
    public function testTasaCero(): void { self::assertSame('0.000000',$this->tasa(0)); }
    public function testTasaDieciseis(): void { self::assertSame('0.160000',$this->tasa(0.16)); }
    public function testTasaRedondeada(): void { self::assertSame('0.106667',$this->tasa(0.106666666)); }
    public function testTasaUnidad(): void { self::assertSame('1.000000',$this->tasa(1)); }
    public function testMapeoTraslado(): void { self::assertSame(['BaseP'=>1000.00,'ImpuestoP'=>'002','TipoFactorP'=>'Tasa','TasaOCuotaP'=>'0.160000','ImporteP'=>160.00],$this->impuesto('traslado')); }
    public function testTrasladoSinError(): void { $this->impuesto('traslado'); self::assertSame(false,errores::$error); }
    public function testMapeoRetencion(): void { self::assertSame(['BaseP'=>1000.00,'ImpuestoP'=>'002','TipoFactorP'=>'Tasa','TasaOCuotaP'=>'0.160000','ImporteP'=>160.00],$this->impuesto('retencion')); }
    public function testRetencionSinError(): void { $this->impuesto('retencion'); self::assertSame(false,errores::$error); }
    public function testColumnasCompletas(): void {
        self::assertSame(['fc_complemento_pago_id'=>['titulo'=>'Id'],'fc_complemento_pago_folio'=>['titulo'=>'Fol'],'com_cliente_razon_social'=>['titulo'=>'Cliente'],'com_cliente_rfc'=>['titulo'=>'RFC'],'fc_complemento_pago_fecha'=>['titulo'=>'Fecha'],'fc_complemento_pago_total_pagos'=>['titulo'=>'Total'],'fc_complemento_pago_uuid'=>['titulo'=>'UUID'],'fc_complemento_pago_etapa'=>['titulo'=>'Estatus']],$this->listado()->columns);
    }
    public function testFiltrosCompletos(): void { self::assertSame(['fc_complemento_pago.folio','com_cliente.razon_social','com_cliente.rfc','fc_complemento_pago.fecha','fc_complemento_pago_etapa'],$this->listado()->filtro); }
    public function testMenuHabilitado(): void { self::assertSame(true,$this->listado()->menu_active); }

    private function referenciaInvalida(array $ids,string $mensaje,int $valor): void {
        self::assertSame(['error'=>true,'mensaje'=>$mensaje,'data'=>$valor],$this->invocar('fc_partida_cp_ins',$ids));
        self::assertTrue(errores::$error);
    }
    public function testRechazaUnidadCero(): void { $this->referenciaInvalida([0,91002,91003],'Error cat_sat_unidad_id es menor a 0',0); }
    public function testRechazaUnidadNegativa(): void { $this->referenciaInvalida([-1,91002,91003],'Error cat_sat_unidad_id es menor a 0',-1); }
    public function testRechazaProductoCero(): void { $this->referenciaInvalida([91001,0,91003],'Error com_producto_id es menor a 0',0); }
    public function testRechazaProductoNegativo(): void { $this->referenciaInvalida([91001,-1,91003],'Error com_producto_id es menor a 0',-1); }
    public function testRechazaComplementoCero(): void { $this->referenciaInvalida([91001,91002,0],'Error fc_complemento_pago_id es menor a 0',0); }
    public function testRechazaComplementoNegativo(): void { $this->referenciaInvalida([91001,91002,-1],'Error fc_complemento_pago_id es menor a 0',-1); }
    public function testPropagaErrorDeDependenciaClavesSinValidarNumeros(): void {
        $this->modelo->validacion->falloForzado='claves';
        $real=$this->impuesto('traslado');
        self::assertTrue($real['error']);
        self::assertSame('Error al validar fc_row_p_part',$real['mensaje']);
        self::assertSame('Fallo ficticio de claves',$real['data']['mensaje']);
        self::assertSame(['claves'],$this->modelo->validacion->llamadas);
        self::assertTrue(errores::$error);
    }
    public function testPropagaErrorDeDependenciaNumerosSinGenerarImpuesto(): void {
        $this->modelo->validacion->falloForzado='numeros';
        $real=$this->impuesto('retencion');
        self::assertTrue($real['error']);
        self::assertSame('Error al validar fc_row_p_part',$real['mensaje']);
        self::assertSame('Fallo ficticio de numeros',$real['data']['mensaje']);
        self::assertSame(['claves','numeros'],$this->modelo->validacion->llamadas);
        self::assertArrayNotHasKey('ImporteP',$real);
        self::assertTrue(errores::$error);
    }
    public function testControladorExigeModeloInicializado(): void {
        // Contrato técnico de inicialización, no validación del formulario de usuario.
        $controlador=(new ReflectionClass(controlador_fc_complemento_pago::class))->newInstanceWithoutConstructor();
        $this->expectException(Error::class);
        $this->expectExceptionMessage('must not be accessed before initialization');
        $controlador->init_datatable();
    }
    public function testListadoEsRepetibleSinMutarTabla(): void {
        $primero=$this->listado();
        $segundo=$this->listado();
        self::assertEquals($primero,$segundo);
        self::assertSame('fc_complemento_pago',$this->modelo->tabla);
        self::assertFalse(errores::$error);
    }
}
