<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use gamboamartin\facturacion\models\_informacion_global;
use gamboamartin\errores\errores;

/** Servicio real, datos ficticios y paquete de errores real; sin configuración ni conexiones. */
final class InformacionGlobalTest extends TestCase
{
    private _informacion_global $servicio;
    private const REGISTRO = ['global_periodicidad'=>'01','global_meses'=>'11','global_anio'=>'2026'];
    private const NODO = ['Periodicidad'=>'01','Meses'=>'11','Año'=>'2026'];

    protected function setUp(): void
    {
        require_once RAIZ_REPOSITORIO_PRUEBAS.'/vendor/gamboa.martin/errores/src/errores.php';
        require_once RAIZ_REPOSITORIO_PRUEBAS.'/orm/_informacion_global.php';
        errores::$error = false;
        $this->servicio = new _informacion_global(2026);
    }

    private function resolver(array $registro, string $tipo='I', string $rfc='XAXX010101000',
                              string $nombre='PUBLICO EN GENERAL', string $regimen='601',
                              string $entidad='fc_factura'): array
    {
        return $this->servicio->resolver($registro, ['tipo_de_comprobante'=>$tipo],
            ['regimen_fiscal'=>$regimen], ['rfc'=>$rfc,'nombre'=>$nombre], $entidad);
    }
    private function fallo(array $resultado): void
    {
        self::assertTrue(errores::$error);
        self::assertNotEmpty($resultado['mensaje']);
        self::assertSame([], $resultado['data']);
    }
    public function testRegistroSinCamposSeConserva(): void
    { self::assertSame(['total'=>10],$this->servicio->normaliza_registro(['total'=>10])); }
    public function testCamposVaciosSeGuardanComoNull(): void
    { self::assertSame(['global_periodicidad'=>null,'global_meses'=>null,'global_anio'=>null],
        $this->servicio->normaliza_registro(['global_periodicidad'=>' ','global_meses'=>'','global_anio'=>null])); }
    public function testNormalizaSinPerderCerosNiOtrosCampos(): void
    { self::assertSame(self::REGISTRO+['total'=>10], $this->servicio->normaliza_registro(
        ['global_periodicidad'=>' 01 ','global_meses'=>'11','global_anio'=>2026,'total'=>10])); }
    public function testDatosParcialesSeRechazan(): void
    { $this->fallo($this->servicio->normaliza_registro(['global_periodicidad'=>'01'])); }
    public function testValoresCompuestosSeRechazan(): void
    { $this->fallo($this->servicio->normaliza_registro(array_replace(self::REGISTRO,['global_anio'=>['2026']]))); }
    public function testPeriodicidadFueraDeCatalogoSeRechaza(): void
    { $this->fallo($this->resolver(array_replace(self::REGISTRO,['global_periodicidad'=>'06']))); }
    public function testMesMensualNoAceptaBimestre(): void
    { $this->fallo($this->resolver(array_replace(self::REGISTRO,['global_meses'=>'13']))); }
    public function testBimestreNoAceptaMesIndividual(): void
    { $this->fallo($this->resolver(array_replace(self::REGISTRO,['global_periodicidad'=>'05']))); }
    public function testMesRequiereDosDigitos(): void
    { $this->fallo($this->resolver(array_replace(self::REGISTRO,['global_meses'=>'1']))); }
    public function testAnioFuturoSeRechaza(): void
    { $this->fallo($this->resolver(array_replace(self::REGISTRO,['global_anio'=>'2027']))); }
    public function testAnioConMasDeCincoEjerciciosSeRechaza(): void
    { $this->fallo($this->resolver(array_replace(self::REGISTRO,['global_anio'=>'2020']))); }
    public function testQuintoEjercicioAnteriorSeAcepta(): void
    { self::assertSame('2021',$this->resolver(array_replace(self::REGISTRO,['global_anio'=>'2021']))['Año']); }
    public function testPublicoEnGeneralExigePeriodoSinInventarlo(): void
    { $this->fallo($this->resolver([])); }
    public function testFacturaGlobalResuelveCamposPrefijados(): void
    { $r=[]; foreach(self::REGISTRO as $k=>$v)$r['fc_factura_'.$k]=$v; self::assertSame(self::NODO,$this->resolver($r)); }
    public function testFacturaNominativaSinGlobalNoCambia(): void
    { self::assertSame([],$this->resolver([],'I','EKU9003173C9','CLIENTE FICTICIO')); }
    public function testDatosGlobalesEnFacturaNominativaSeRechazan(): void
    { $this->fallo($this->resolver(self::REGISTRO,'I','EKU9003173C9','CLIENTE FICTICIO')); }
    public function testNotaCreditoYComplementoNoUsanInformacionGlobal(): void
    { foreach(['fc_nota_credito','fc_complemento_pago'] as $t) {
        self::assertSame([],$this->resolver(['global_anio'=>[]],'E','XAXX010101000','PUBLICO EN GENERAL','601',$t));
        self::assertFalse(errores::$error);
    } }
    public function testBimestreExigeRegimen621DelEmisor(): void
    { $this->fallo($this->resolver(['global_periodicidad'=>'05','global_meses'=>'18','global_anio'=>'2026'])); }
    public function testBimestreValidoSeConserva(): void
    { self::assertSame(['Periodicidad'=>'05','Meses'=>'18','Año'=>'2026'],
        $this->resolver(['global_periodicidad'=>'05','global_meses'=>'18','global_anio'=>'2026'],'I','XAXX010101000','PUBLICO EN GENERAL','621')); }
    public function testDeteccionToleraEspaciosSinModificarReceptor(): void
    { self::assertSame(self::NODO,$this->resolver(self::REGISTRO,'i',' xaxx010101000 ',' publico   en general ')); }
    public function testJsonIntegraAnioUtf8YConservaContenido(): void
    {
        $original=['Comprobante'=>['TipoDeComprobante'=>'I','Receptor'=>['Nombre'=>'PUBLICO EN GENERAL'],'Total'=>'1.16']];
        $json=$this->servicio->aplicar(json_encode($original),'json',self::NODO);
        self::assertIsString($json); $result=json_decode($json,true,512,JSON_THROW_ON_ERROR);
        self::assertSame(self::NODO,$result['Comprobante']['InformacionGlobal']);
        unset($result['Comprobante']['InformacionGlobal']); self::assertSame($original,$result);
    }
    public function testXmlIntegraPrimerNodoConNamespaceCorrecto(): void
    {
        $xml='<cfdi:Comprobante xmlns:cfdi="http://www.sat.gob.mx/cfd/4" Version="4.0"><cfdi:CfdiRelacionados/><cfdi:Emisor/><cfdi:Receptor/></cfdi:Comprobante>';
        $out=$this->servicio->aplicar($xml,'xml',self::NODO);self::assertIsString($out);
        $dom=new DOMDocument();self::assertTrue($dom->loadXML($out));$root=$dom->documentElement;
        self::assertSame('InformacionGlobal',$root->firstElementChild->localName);
        self::assertSame('http://www.sat.gob.mx/cfd/4',$root->firstElementChild->namespaceURI);
        self::assertSame('2026',$root->firstElementChild->getAttribute('Año'));
        self::assertSame('CfdiRelacionados',$root->firstElementChild->nextElementSibling->localName);
        self::assertSame('4.0',$root->getAttribute('Version'));
    }
    public function testXmlExistenteNoSeDuplica(): void
    { $this->fallo($this->servicio->aplicar('<c:Comprobante xmlns:c="http://www.sat.gob.mx/cfd/4"><c:InformacionGlobal/></c:Comprobante>','xml',self::NODO)); }
    public function testJsonExistenteNoSeSobrescribe(): void
    { $this->fallo($this->servicio->aplicar('{"Comprobante":{"InformacionGlobal":{"Año":"2025"}}}','json',self::NODO)); }
    public function testJsonMalformadoSeRechaza(): void
    { $this->fallo($this->servicio->aplicar('{','json',self::NODO)); }
    public function testXmlConDoctypeSeRechaza(): void
    { $this->fallo($this->servicio->aplicar('<!DOCTYPE c:Comprobante><c:Comprobante xmlns:c="http://www.sat.gob.mx/cfd/4"/>','xml',self::NODO)); }
    public function testSinGlobalConservaBytesExactos(): void
    { foreach(['json','xml'] as $tipo)self::assertSame('bytes sin cambios',$this->servicio->aplicar('bytes sin cambios',$tipo,[])); }
}
