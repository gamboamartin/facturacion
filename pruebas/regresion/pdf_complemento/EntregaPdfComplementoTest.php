<?php
declare(strict_types=1);

use config\generales;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\_pdf;
use gamboamartin\facturacion\models\_PDF\aguaban\_complemento_pago;
use gamboamartin\facturacion\models\_PDF\aguaban\_factura_and_nota_credito;
use PHPUnit\Framework\TestCase;

/** Prueba el dispatch/salida real con las fronteras de lectura y maquetación simuladas. */
final class EntregaPdfComplementoTest extends TestCase
{
    private string $base;
    private PDO $pdo;
    protected function setUp():void
    {
        require_once __DIR__.'/dobles_dispatch.php';
        require_once RAIZ_REPOSITORIO_PRUEBAS.'/orm/_formato_pdf_complemento.php';
        require_once RAIZ_REPOSITORIO_PRUEBAS.'/orm/_pdf.php';
        require_once RAIZ_REPOSITORIO_PRUEBAS.'/controllers/_doctos.php';
        $this->base=sys_get_temp_dir().'/qa-entrega-complemento-'.bin2hex(random_bytes(6)).'/';mkdir($this->base,0700);
        generales::$base=$this->base;generales::$formato='aguaban';generales::$proyecto='';generales::$titulo=false;
        \gamboamartin\facturacion\models\DiscoEntregaPdf::$fallarPublicacion=false;\gamboamartin\facturacion\models\DiscoEntregaPdf::$fallosRetiroTemporal=0;
        \gamboamartin\facturacion\models\_datos_pdf_complemento::$porId=[];
        errores::$error=false;_complemento_pago::$llamadas=0;_factura_and_nota_credito::$llamadas=0;
        \gamboamartin\facturacion\models\_doc::$registros=[];\gamboamartin\documento\models\doc_documento::$archivos=[];
        \gamboamartin\facturacion\models\_doc::$fallarRegistro=false;\gamboamartin\documento\models\doc_documento::$fallarCarga=false;\gamboamartin\documento\models\doc_documento::$mismaRuta=false;\gamboamartin\documento\models\doc_documento::$ultimaRuta='';
        $this->pdo=new class extends PDO{public function __construct(){}};
    }
    protected function tearDown():void
    {
        foreach($this->archivos() as $f){unlink($this->base.'archivos/'.$f);}if(is_dir($this->base.'archivos'))rmdir($this->base.'archivos');rmdir($this->base);
    }
    private function argumentos(string $tabla='fc_complemento_pago'):array
    {
        $n='gamboamartin\\facturacion\\models\\';
        return ['descarga'=>false,'guarda'=>true,'link'=>$this->pdo,'modelo_documento'=>new ($n.'_doc')($this->pdo),
            'modelo_entidad'=>new ($n.'_transacciones_fc')($this->pdo,$tabla),'modelo_partida'=>new ($n.'_partida')(),
            'modelo_predial'=>new ($n.'_cuenta_predial')(),'modelo_relacion'=>new ($n.'_relacion')(),
            'modelo_relacionada'=>new ($n.'_relacionada')(),'modelo_retencion'=>new ($n.'_data_impuestos')(),
            'modelo_sellado'=>new ($n.'_sellado')(),'modelo_traslado'=>new ($n.'_data_impuestos')(),'modelo_uuid_ext'=>new ($n.'_uuid_ext')(),'registro_id'=>1];
    }
    public function testDescargaEntregaBytesExactosYNombre():void
    {
        $a=$this->argumentos();$a['descarga']=true;$a['guarda']=false;
        ob_start();try{$nombre=(new _pdf())->pdf(...$a);$bytes=ob_get_contents();}finally{ob_end_clean();}
        self::assertSame(_complemento_pago::$contenido,$bytes);self::assertSame('CP000042',$nombre);
        self::assertSame(0,$a['modelo_entidad']->consultas);self::assertFalse(errores::$error);
    }
    public function testGuardarParaAdjuntoConservaExactamenteLosMismosBytes():void
    {
        $ruta=(new _pdf())->pdf(...$this->argumentos());
        self::assertStringStartsWith($this->base.'archivos/',$ruta);
        self::assertMatchesRegularExpression('~^complemento-1-[a-f0-9]{32}\.pdf$~D',basename($ruta));
        self::assertSame(_complemento_pago::$contenido,file_get_contents($ruta));
        self::assertCount(1,glob($this->base.'archivos/*'));self::assertFalse(errores::$error);
    }
    public function testDescargaMantienePrioridadSobreGuardar():void
    {
        $a=$this->argumentos();$a['descarga']=true;
        ob_start();try{(new _pdf())->pdf(...$a);$bytes=ob_get_contents();}finally{ob_end_clean();}
        self::assertSame(_complemento_pago::$contenido,$bytes);self::assertDirectoryDoesNotExist($this->base.'archivos');
    }
    public function testSeleccionEstandarConProyectoAguabanEntraAlFlujoGeneral():void
    {
        generales::$formato='estandar';generales::$proyecto='aguaban';$a=$this->argumentos();$r=(new _pdf())->pdf(...$a);
        self::assertSame(1,$a['modelo_entidad']->consultas);self::assertSame(0,_complemento_pago::$llamadas);
        self::assertSame('Error al obtener factura',$r['mensaje']);
    }
    public function testSelectorDeComplementoNoAfectaFacturaNiNotaCredito():void
    {
        foreach(['fc_factura','fc_nota_credito'] as $tabla){errores::$error=false;$a=$this->argumentos($tabla);(new _pdf())->pdf(...$a);self::assertSame(1,$a['modelo_entidad']->consultas);}
        self::assertSame(0,_complemento_pago::$llamadas);
    }
    public function testDoctosRegistraComplementoGuardadoConProyectoAguaban():void
    {
        generales::$proyecto='aguaban';$a=$this->argumentos();unset($a['link']);$a['modelo_sello']=$a['modelo_sellado'];unset($a['modelo_sellado']);$a['row_entidad_id']=$a['registro_id'];unset($a['registro_id']);
        (new \gamboamartin\facturacion\controllers\_doctos())->pdf(...$a);
        self::assertSame(0,_factura_and_nota_credito::$llamadas);self::assertSame(1,_complemento_pago::$llamadas);
        self::assertSame([['fc_complemento_pago_id'=>1,'doc_documento_id'=>88]],\gamboamartin\facturacion\models\_doc::$registros);
        $archivo=\gamboamartin\documento\models\doc_documento::$archivos[0]['file'];
        self::assertSame('000042.pdf',$archivo['name']);self::assertFileDoesNotExist($archivo['tmp_name']);
        self::assertSame(_complemento_pago::$contenido,file_get_contents(\gamboamartin\documento\models\doc_documento::$ultimaRuta));
    }
    public function testDoctosConservaRendererLegacyDeFacturaYNotaCredito():void
    {
        generales::$proyecto='aguaban';
        foreach(['fc_factura','fc_nota_credito'] as $tabla){
            $a=$this->argumentos($tabla);unset($a['link']);$a['modelo_sello']=$a['modelo_sellado'];unset($a['modelo_sellado']);$a['row_entidad_id']=$a['registro_id'];unset($a['registro_id']);
            (new \gamboamartin\facturacion\controllers\_doctos())->pdf(...$a);
        }
        self::assertSame(2,_factura_and_nota_credito::$llamadas);self::assertSame(0,_complemento_pago::$llamadas);
    }
    public function testRenderInvalidoNoGuardaArchivoNiEnviaSalida():void
    {
        _complemento_pago::$contenido='NO ES PDF';ob_start();try{$r=(new _pdf())->pdf(...$this->argumentos());$salida=ob_get_contents();}finally{ob_end_clean();}
        self::assertSame('',$salida);self::assertTrue(errores::$error);self::assertIsArray($r);self::assertDirectoryDoesNotExist($this->base.'archivos');
    }
    private function archivos():array
    {
        return is_dir($this->base.'archivos')?array_values(array_diff(scandir($this->base.'archivos'),['.','..'])):[];
    }
    public function testComplementosConMismoFolioNoCompartenArchivo():void
    {
        \gamboamartin\facturacion\models\_datos_pdf_complemento::$porId=[
            1=>['contenido_prueba'=>"%PDF-1.4\nPRIMER COMPLEMENTO\n%%EOF"],
            2=>['contenido_prueba'=>"%PDF-1.4\nSEGUNDO COMPLEMENTO\n%%EOF"]];
        $a=$this->argumentos();$uno=(new _pdf())->pdf(...$a);$a['registro_id']=2;$dos=(new _pdf())->pdf(...$a);
        self::assertNotSame($uno,$dos);
        self::assertMatchesRegularExpression('~^complemento-1-[a-f0-9]{32}\.pdf$~D',basename($uno));
        self::assertMatchesRegularExpression('~^complemento-2-[a-f0-9]{32}\.pdf$~D',basename($dos));
        self::assertSame("%PDF-1.4\nPRIMER COMPLEMENTO\n%%EOF",file_get_contents($uno));
        self::assertSame("%PDF-1.4\nSEGUNDO COMPLEMENTO\n%%EOF",file_get_contents($dos));
        self::assertCount(2,$this->archivos());
    }
    public function testDosGuardadosDelMismoComplementoNoSePisan():void
    {
        _complemento_pago::$contenido="%PDF-1.4\nPRIMERA SOLICITUD\n%%EOF";$uno=(new _pdf())->pdf(...$this->argumentos());
        _complemento_pago::$contenido="%PDF-1.4\nSEGUNDA SOLICITUD\n%%EOF";$dos=(new _pdf())->pdf(...$this->argumentos());
        self::assertNotSame($uno,$dos);
        self::assertSame("%PDF-1.4\nPRIMERA SOLICITUD\n%%EOF",file_get_contents($uno));
        self::assertSame("%PDF-1.4\nSEGUNDA SOLICITUD\n%%EOF",file_get_contents($dos));
        self::assertCount(2,$this->archivos());
    }
    public function testFalloDePublicacionRetiraTemporalSinDejarPdf():void
    {
        \gamboamartin\facturacion\models\DiscoEntregaPdf::$fallarPublicacion=true;
        $r=(new _pdf())->pdf(...$this->argumentos());
        self::assertTrue(errores::$error);self::assertIsArray($r);self::assertSame([],$this->archivos());
    }
    public function testFalloPosteriorAlPublicarRetiraAmbasRutas():void
    {
        \gamboamartin\facturacion\models\DiscoEntregaPdf::$fallosRetiroTemporal=1;
        $r=(new _pdf())->pdf(...$this->argumentos());
        self::assertTrue(errores::$error);self::assertIsArray($r);self::assertSame([],$this->archivos());
    }

    private function guardarDocumento():mixed
    {
        generales::$proyecto='aguaban';$a=$this->argumentos();unset($a['link']);$a['modelo_sello']=$a['modelo_sellado'];unset($a['modelo_sellado']);$a['row_entidad_id']=$a['registro_id'];unset($a['registro_id']);
        return (new \gamboamartin\facturacion\controllers\_doctos())->pdf(...$a);
    }
    public function testErrorDeCargaNoDejaIntermedioHuerfano():void
    {
        \gamboamartin\documento\models\doc_documento::$fallarCarga=true;$this->guardarDocumento();
        self::assertTrue(errores::$error);self::assertSame([],$this->archivos());
    }
    public function testErrorDeRegistroRetiraIntermedioSinBorrarCopiaDelCargador():void
    {
        \gamboamartin\facturacion\models\_doc::$fallarRegistro=true;$this->guardarDocumento();
        self::assertTrue(errores::$error);$archivo=\gamboamartin\documento\models\doc_documento::$archivos[0]['file'];
        self::assertFileDoesNotExist($archivo['tmp_name']);
        self::assertSame(_complemento_pago::$contenido,file_get_contents(\gamboamartin\documento\models\doc_documento::$ultimaRuta));
        self::assertCount(1,$this->archivos());
    }
    public function testRutaRegistradaIgualAlIntermedioNuncaSeElimina():void
    {
        \gamboamartin\documento\models\doc_documento::$mismaRuta=true;$r=$this->guardarDocumento();
        self::assertFalse(errores::$error);$ruta=$r->registro_obj->doc_documento_ruta_absoluta;
        self::assertFileExists($ruta);self::assertSame(_complemento_pago::$contenido,file_get_contents($ruta));
    }

}
