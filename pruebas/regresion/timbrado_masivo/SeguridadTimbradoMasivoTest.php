<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use gamboamartin\facturacion\models\_timbrado_pendientes;

/** No carga configuración ni servicios: protege la frontera HTTP y sus filtros. */
final class SeguridadTimbradoMasivoTest extends TestCase
{
    protected function setUp(): void
    {
        require_once RAIZ_REPOSITORIO_PRUEBAS.'/orm/_timbrado_pendientes.php';
        require_once RAIZ_REPOSITORIO_PRUEBAS.'/controllers/_timbrado_masivo.php';
        require_once __DIR__.'/dobles_seguridad.php';
        $_SESSION = [];
    }

    public function testNormalizaFiltrosVaciosSinInventarFechas(): void
    {
        self::assertSame(array_fill_keys(['fecha_inicio','fecha_fin','serie','folio','rfc','cliente','emisor_id','total_min','total_max'], ''), _timbrado_pendientes::filtros([]));
    }

    public function testRechazaFiltroSqlArbitrario(): void
    {
        $this->expectException(InvalidArgumentException::class);
        _timbrado_pendientes::filtros(['campo' => '1=1']);
    }

    public function testRechazaFechaInexistente(): void
    {
        $this->expectException(InvalidArgumentException::class);
        _timbrado_pendientes::filtros(['fecha_inicio' => '2026-02-30']);
    }

    public function testRechazaRangoInvertido(): void
    {
        $this->expectException(InvalidArgumentException::class);
        _timbrado_pendientes::filtros(['fecha_inicio' => '2026-11-24', 'fecha_fin' => '2026-11-23']);
    }

    public function testRechazaImportesNegativos(): void
    {
        $this->expectException(InvalidArgumentException::class);
        _timbrado_pendientes::filtros(['total_min' => '-1']);
    }

    public function testRechazaImportesInvertidos(): void
    {
        $this->expectException(InvalidArgumentException::class);
        _timbrado_pendientes::filtros(['total_min' => '20.00', 'total_max' => '10.00']);
    }

    public function testRechazaNulYValoresCompuestos(): void
    {
        foreach ([['fecha_inicio' => "2026-11-23\0"], ['rfc' => ['x']]] as $entrada) {
            try {
                _timbrado_pendientes::filtros($entrada);
                self::fail('Filtro inválido aceptado');
            } catch (InvalidArgumentException $error) {
                self::assertNotSame('', $error->getMessage());
            }
        }
    }

    public function testSinUsuarioNoConsultaPermisos(): void
    {
        $pdo = new SeguridadMasivaPDO();
        self::assertFalse((new SeguridadMasivaControlador($pdo))->permiso());
        self::assertSame([], $pdo->consultas);
    }

    public function testExigeLosTresPermisosAunqueSesionLosTengaEnCache(): void
    {
        $_SESSION = ['usuario_id' => 2, 'permite' => ['cualquiera' => true]];
        $pdo = new SeguridadMasivaPDO();
        $pdo->permisos = 2;
        self::assertFalse((new SeguridadMasivaControlador($pdo))->permiso());
        self::assertSame([2], $pdo->parametros);
        self::assertStringContainsString("'lista','timbra_xml','timbrado_masivo'", $pdo->consultas[0]);
        self::assertStringContainsString("u.status='activo'", $pdo->consultas[0]);
    }

    public function testReconsultaRevocacionDePermiso(): void
    {
        $_SESSION = ['usuario_id' => 2];
        $pdo = new SeguridadMasivaPDO();
        $pdo->permisos = 3;
        $c = new SeguridadMasivaControlador($pdo);
        self::assertTrue($c->permiso());
        $pdo->permisos = 0;
        self::assertFalse($c->permiso());
        self::assertCount(2, $pdo->consultas);
    }

    public function testPostSinCsrfNoInicializaLoteNiPac(): void
    {
        $this->csrf([]);
    }

    public function testPostConCsrfAjenoNoInicializaLoteNiPac(): void
    {
        $this->csrf(['csrf' => 'otro']);
    }

    public function testGetConParametrosDeProcesarSoloPreparaVista(): void
    {
        $_SERVER['REQUEST_METHOD']='GET';
        $_SESSION=['usuario_id'=>2];
        $_GET=['operacion'=>'procesar','lote_id'=>str_repeat('a',32)];
        $pdo=new SeguridadMasivaPDO();$pdo->permisos=3;
        $controlador=new SeguridadMasivaControlador($pdo);
        self::assertInstanceOf(stdClass::class,$controlador->timbrado_masivo());
        self::assertSame(100,$controlador->timbrado_masivo_config['maximo']);
        self::assertSame(64,strlen($_SESSION['fc_timbrado_csrf']));
        self::assertCount(2,$pdo->consultas);
        self::assertStringNotContainsString('fc_timbrado_lote',implode(' ',$pdo->consultas));
    }

    public function testSalidaLegacyNoFiltraXmlNiEnsuciaSesion(): void
    {
        $archivo=tempnam(sys_get_temp_dir(),'qa-legacy-');
        try {
            $p=proc_open(['php','-d','display_errors=stderr',__DIR__.'/salida_legacy.php',
                RAIZ_REPOSITORIO_PRUEBAS.'/controllers/_timbrado_masivo.php',$archivo],
                [1=>['pipe','w'],2=>['pipe','w']],$pipes);
            self::assertIsResource($p);
            $out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);
            fclose($pipes[1]);fclose($pipes[2]);
            self::assertSame(0,proc_close($p));self::assertSame('',$out);self::assertSame('',$err);
            $estado=json_decode(file_get_contents($archivo),true,flags:JSON_THROW_ON_ERROR);
            self::assertSame(['previo'],$estado['sesion']);
            self::assertSame(['previo'],$estado['out']);
            self::assertFalse($estado['error']);
        } finally {unlink($archivo);}
    }

    private function csrf(array $post): void
    {
        $php = 'require $argv[1];$_SESSION=["usuario_id"=>2,"fc_timbrado_csrf"=>"secreto-de-prueba"];'
            .'$_SERVER["REQUEST_METHOD"]="POST";$_POST=json_decode($argv[2],true);'
            .'class PruebaCsrf {use \\gamboamartin\\facturacion\\controllers\\_timbrado_masivo;}'
            .'(new PruebaCsrf())->timbrado_masivo();';
        $p = proc_open(['php','-d','display_errors=stderr','-r',$php,
            RAIZ_REPOSITORIO_PRUEBAS.'/controllers/_timbrado_masivo.php',json_encode($post)],
            [1 => ['pipe','w'], 2 => ['pipe','w']], $pipes);
        self::assertIsResource($p);
        $out = stream_get_contents($pipes[1]);
        $err = stream_get_contents($pipes[2]);
        fclose($pipes[1]);fclose($pipes[2]);
        self::assertSame(0, proc_close($p));
        self::assertSame('', $err);
        self::assertSame(['ok' => false, 'mensaje' => 'La sesión del formulario venció. Actualiza la pantalla.'], json_decode($out, true, flags: JSON_THROW_ON_ERROR));
    }
}
