<?php
declare(strict_types=1);

use gamboamartin\facturacion\models\_datos_pdf_complemento;
use PHPUnit\Framework\TestCase;

final class CargaDatosPdfComplementoTest extends TestCase
{
    private string $directorio;
    protected function setUp(): void
    {
        require_once RAIZ_REPOSITORIO_PRUEBAS.'/orm/_datos_pdf_complemento.php';
        require_once __DIR__.'/fixture.php'; require_once __DIR__.'/dobles_datos.php';
        $this->directorio = sys_get_temp_dir().'/qa-lector-complemento-'.bin2hex(random_bytes(6));
        mkdir($this->directorio, 0700);
    }
    protected function tearDown(): void
    {
        foreach (glob($this->directorio.'/*') as $p) { unlink($p); }
        rmdir($this->directorio);
    }
    private function xml(PdoDatosPdfComplemento $p): void
    {
        file_put_contents($this->directorio.'/complemento.xml', xmlComplementoPrueba());
        $p->documentos = [['tipo'=>'xml_sin_timbrar','ruta'=>$this->directorio.'/complemento.xml']];
        $p->sellados = [['uuid'=>'11111111-1111-4111-8111-111111111111','cadena_complemento_sat'=>'CADENA PAC FICTICIA']];
    }
    public function testPendienteDesdeBdUsaSaldosParcialidadEImpuestosReales(): void
    {
        $p = new PdoDatosPdfComplemento();
        $d = (new _datos_pdf_complemento())->cargar($p, 1, $this->directorio);
        self::assertSame('pendiente', $d['estado']);
        self::assertSame('CP-000042', $d['folio']);
        self::assertSame('2', $d['pagos'][0]['doctos'][0]['parcialidad']);
        self::assertSame('580.00', $d['pagos'][0]['doctos'][0]['saldo_insoluto']);
        self::assertSame('500.00', $d['pagos'][0]['doctos'][0]['impuestos'][0]['base']);
        self::assertSame('', $d['fiscales']['uuid']);
        self::assertSame('', $d['qr_url']);
        self::assertCount(11, $p->sentencias);
    }
    public function testXmlHistoricoEsAutoridadYNoConsultaPagosActuales(): void
    {
        $p = new PdoDatosPdfComplemento(); $this->xml($p);
        $d = (new _datos_pdf_complemento())->cargar($p, 1, $this->directorio);
        self::assertCount(2, $d['pagos']);
        self::assertSame('730.00', $d['pagos'][0]['monto']);
        self::assertSame('CADENA PAC FICTICIA', $d['sellos']['cadena_original']);
        self::assertNotSame('', $d['qr_url']);
        self::assertCount(3, $p->sentencias);
    }
    public function testXmlAjenoEnUuidRfcOSerieSeRechaza(): void
    {
        foreach (['emisor_rfc'=>'CCC010101CCC','receptor_rfc'=>'CCC010101CCC','serie'=>'OTRA'] as $campo=>$valor) {
            $p = new PdoDatosPdfComplemento(); $this->xml($p); $p->cabecera[$campo] = $valor;
            $error = null;
            try { (new _datos_pdf_complemento())->cargar($p, 1, $this->directorio); } catch (RuntimeException $e) { $error=$e; }
            self::assertInstanceOf(RuntimeException::class, $error);
        }
        $p = new PdoDatosPdfComplemento(); $this->xml($p); $p->sellados[0]['uuid']='99999999-9999-4999-8999-999999999999';
        $this->expectException(RuntimeException::class);
        (new _datos_pdf_complemento())->cargar($p, 1, $this->directorio);
    }
    public function testFaltaXmlCertificadoNoImprimeDatosActualesComoTimbrados(): void
    {
        $p = new PdoDatosPdfComplemento(); $p->sellados = [['uuid'=>'11111111-1111-4111-8111-111111111111']];
        $this->expectException(RuntimeException::class);
        (new _datos_pdf_complemento())->cargar($p, 1, $this->directorio);
    }
    public function testRutaFueraDeInstalacionYArchivoDuplicadoSeRechazan(): void
    {
        $p = new PdoDatosPdfComplemento();
        $p->documentos = [['tipo'=>'xml_sin_timbrar','ruta'=>'/etc/passwd']];
        $error = null;
        try { (new _datos_pdf_complemento())->cargar($p, 1, $this->directorio); } catch (RuntimeException $e) { $error=$e; }
        self::assertInstanceOf(RuntimeException::class, $error);
        $this->xml($p); $p->documentos[] = $p->documentos[0];
        $this->expectException(RuntimeException::class);
        (new _datos_pdf_complemento())->cargar($p, 1, $this->directorio);
    }
}
