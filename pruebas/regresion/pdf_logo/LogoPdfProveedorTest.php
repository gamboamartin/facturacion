<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use gamboamartin\facturacion\models\_logo_pdf;

/** Proveedor real con PDO simulado e imágenes locales ficticias; sin configuración ni BD. */
final class LogoPdfProveedorTest extends TestCase
{
    private string $raiz;
    private string $directorio;
    private string $errorLog;
    private _logo_pdf $lector;

    protected function setUp(): void
    {
        require_once RAIZ_REPOSITORIO_PRUEBAS.'/orm/_logo_pdf.php';
        $this->raiz=sys_get_temp_dir().'/qa-logo-pdf-'.bin2hex(random_bytes(8));
        $this->directorio=$this->raiz.'/archivos/doc_documento';
        mkdir($this->directorio,0700,true);
        $imagen=imagecreatetruecolor(120,60);
        imagefill($imagen,0,0,imagecolorallocate($imagen,40,120,160));
        imagejpeg($imagen,$this->directorio.'/9.logo.jpg');
        imagepng($imagen,$this->directorio.'/9.logo.png');
        imagedestroy($imagen);
        $this->errorLog=(string)ini_get('error_log');
        ini_set('error_log',$this->raiz.'/diagnostico.log');
        $this->lector=new _logo_pdf($this->raiz);
    }
    protected function tearDown(): void
    {
        ini_set('error_log',$this->errorLog);
        foreach(glob($this->directorio.'/*') as $p)unlink($p);
        rmdir($this->directorio);rmdir(dirname($this->directorio));
        foreach(glob($this->raiz.'/*') as $p)unlink($p);
        rmdir($this->raiz);
    }
    private function documento(string|false $ruta='archivos/doc_documento/9.logo.jpg', int $empresa=16): PDO
    {
        $stmt=$this->createMock(PDOStatement::class);
        $stmt->expects(self::once())->method('execute')->with(['empresa'=>$empresa])->willReturn(true);
        $stmt->expects(self::once())->method('fetch')->with(PDO::FETCH_ASSOC)
            ->willReturn($ruta===false?false:['ruta_relativa'=>$ruta]);
        $pdo=$this->createMock(PDO::class);
        $pdo->expects(self::once())->method('prepare')->with(self::callback(static function(string $sql):bool {
            return str_contains($sql,'l.org_empresa_id = :empresa')
                && str_contains($sql,"l.logo_pdf = 'activo'")
                && str_contains($sql,"l.status = 'activo'")
                && str_contains($sql,'d.doc_tipo_documento_id = 9')
                && str_contains($sql,'ORDER BY l.id DESC LIMIT 1')
                && !str_contains($sql,'es_principal');
        }))->willReturn($stmt);
        return $pdo;
    }
    public function testJpegUsaRaizDeLaInstalacionYMimeReal(): void
    {
        $logo=$this->lector->para_empresa($this->documento(),16);
        self::assertSame($this->directorio.'/9.logo.jpg',$logo['ruta']);
        self::assertSame('JPG',$logo['tipo']);self::assertSame(120,$logo['ancho']);self::assertSame(60,$logo['alto']);
        self::assertStringStartsWith('data:image/jpeg;base64,',$logo['uri']);
    }
    public function testPngConservaTipoYContenido(): void
    {
        $logo=$this->lector->para_empresa($this->documento('archivos/doc_documento/9.logo.png'),16);
        self::assertSame('PNG',$logo['tipo']);
        self::assertSame(file_get_contents($this->directorio.'/9.logo.png'),base64_decode(explode(',',$logo['uri'],2)[1],true));
    }
    public function testAusenciaDeLogoNoSeleccionaPrincipalNiOtraEmpresa(): void
    { self::assertNull($this->lector->para_empresa($this->documento(false),16)); }
    public function testConsultaRespetaEmpresaSolicitada(): void
    { self::assertNotNull($this->lector->para_empresa($this->documento(empresa:37),37)); }
    public function testEmpresaInvalidaNoConsulta(): void
    {
        $pdo=$this->createMock(PDO::class);$pdo->expects(self::never())->method('prepare');
        self::assertNull($this->lector->para_empresa($pdo,0));
    }
    public function testArchivoAusenteDejaBlanco(): void
    { self::assertNull($this->lector->para_empresa($this->documento('archivos/doc_documento/ausente.jpg'),16)); }
    public function testNoPermiteArchivoFueraDelAlmacen(): void
    { self::assertNull($this->lector->para_empresa($this->documento('archivos/doc_documento/../9.logo.jpg'),16)); }
    public function testNoPermiteUrlRemota(): void
    { self::assertNull($this->lector->para_empresa($this->documento('https://example.invalid/logo.jpg'),16)); }
    public function testNoPermiteEnlaceSimbolico(): void
    {
        symlink($this->directorio.'/9.logo.jpg',$this->directorio.'/enlace.jpg');
        self::assertNull($this->lector->para_empresa($this->documento('archivos/doc_documento/enlace.jpg'),16));
    }
    public function testImagenInvalidaDejaBlanco(): void
    {
        file_put_contents($this->directorio.'/9.invalida.jpg','esto no es una imagen');
        self::assertNull($this->lector->para_empresa($this->documento('archivos/doc_documento/9.invalida.jpg'),16));
    }
    public function testExtensionNoDeterminaElFormato(): void
    {
        copy($this->directorio.'/9.logo.png',$this->directorio.'/9.png-nombrado.jpg');
        $logo=$this->lector->para_empresa($this->documento('archivos/doc_documento/9.png-nombrado.jpg'),16);
        self::assertSame('PNG',$logo['tipo']);
    }
    public function testFalloSqlEsOpcionalSinRevelarExcepcion(): void
    {
        $pdo=$this->createMock(PDO::class);$pdo->method('prepare')->willThrowException(new PDOException('SECRETO-SQL'));
        self::assertNull($this->lector->para_empresa($pdo,16));
        $log=file_get_contents($this->raiz.'/diagnostico.log');
        self::assertStringNotContainsString('SECRETO-SQL',$log);self::assertStringContainsString('imagen opcional',$log);
    }
    public function testPreparacionFallidaDejaBlanco(): void
    {
        $pdo=$this->createMock(PDO::class);$pdo->method('prepare')->willReturn(false);
        self::assertNull($this->lector->para_empresa($pdo,16));
    }
    public function testRfcVacioNoConsulta(): void
    {
        $pdo=$this->createMock(PDO::class);$pdo->expects(self::never())->method('prepare');
        self::assertNull($this->lector->para_rfc($pdo,' '));
    }
    public function testRfcConEmpresaAmbiguaNoTomaLogoArbitrario(): void
    {
        $stmt=$this->createMock(PDOStatement::class);$stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn([['id'=>16],['id'=>37]]);
        $pdo=$this->createMock(PDO::class);$pdo->expects(self::once())->method('prepare')->willReturn($stmt);
        self::assertNull($this->lector->para_rfc($pdo,'AAA010101AAA'));
    }
    public function testRfcAusenteNoTomaLogoGlobal(): void
    {
        $stmt=$this->createMock(PDOStatement::class);$stmt->method('execute')->willReturn(true);$stmt->method('fetchAll')->willReturn([]);
        $pdo=$this->createMock(PDO::class);$pdo->expects(self::once())->method('prepare')->willReturn($stmt);
        self::assertNull($this->lector->para_rfc($pdo,'AAA010101AAA'));
    }
    public function testRfcUnicoSeResuelveALaEmpresaDelXml(): void
    {
        $empresa=$this->createMock(PDOStatement::class);
        $empresa->expects(self::once())->method('execute')->with(['rfc'=>'AAA010101AAA'])->willReturn(true);
        $empresa->method('fetchAll')->willReturn([['id'=>37]]);
        $logo=$this->createMock(PDOStatement::class);
        $logo->expects(self::once())->method('execute')->with(['empresa'=>37])->willReturn(true);
        $logo->method('fetch')->willReturn(['ruta_relativa'=>'archivos/doc_documento/9.logo.jpg']);
        $pdo=$this->createMock(PDO::class);$pdo->expects(self::exactly(2))->method('prepare')->willReturnOnConsecutiveCalls($empresa,$logo);
        self::assertSame('JPG',$this->lector->para_rfc($pdo,' aaa010101aaa ')['tipo']);
    }
    public function testCajaHorizontalRespetaProporcion(): void
    {
        $d=_logo_pdf::caja(['ancho'=>200,'alto'=>100],35,32);
        self::assertSame(35.0,$d['ancho']);self::assertSame(17.5,$d['alto']);
    }
    public function testCajaVerticalNoInvadeCabecera(): void
    {
        $d=_logo_pdf::caja(['ancho'=>100,'alto'=>200],35,32);
        self::assertSame(16.0,$d['ancho']);self::assertSame(32.0,$d['alto']);
    }
    public function testCajaDeComplementoRespetaSusMedidas(): void
    {
        $d=_logo_pdf::caja(['ancho'=>1358,'alto'=>1158],140,110);
        self::assertLessThanOrEqual(140.0,$d['ancho']);self::assertSame(110.0,$d['alto']);
        self::assertEqualsWithDelta(1358/1158,$d['ancho']/$d['alto'],0.00001);
    }
    public function testDimensionesInvalidasNoProducenImagen(): void
    { self::assertSame(['ancho'=>0.0,'alto'=>0.0],_logo_pdf::caja(['ancho'=>0,'alto'=>30],35,32)); }
}
