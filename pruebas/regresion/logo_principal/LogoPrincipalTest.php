<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PruebasLogo\Modelo;

/** Acopla el helper real a modelos dobles y archivos ficticios; no usa DB ni HTTP. */
final class LogoPrincipalTest extends TestCase
{
    private string $directorio;
    private string $png;
    private string $logAnterior;

    protected function setUp(): void
    {
        require_once __DIR__ . '/dobles.php';
        require_once RAIZ_REPOSITORIO_PRUEBAS . '/templates/logo_service.php';
        $this->directorio = sys_get_temp_dir() . '/logo-principal-' . bin2hex(random_bytes(6));
        mkdir($this->directorio, 0700);
        $imagen = imagecreatetruecolor(1, 1);
        ob_start();
        imagepng($imagen);
        $this->png = (string)ob_get_clean();
        imagedestroy($imagen);
        file_put_contents($this->directorio . '/logo.png', $this->png);
        Modelo::$filas = [
            'org_empresa' => [['org_empresa_id' => 71, 'org_empresa_nombre_comercial' => 'Empresa del sistema']],
            'org_logo' => [[
                'org_logo_doc_documento_id' => 91,
                'org_logo_org_empresa_id' => 71
            ]],
            'doc_documento' => [[
                'doc_documento_ruta_relativa' => 'archivos/doc_documento/logo.png',
                'doc_documento_doc_tipo_documento_id' => 9
            ]]
        ];
        Modelo::$consultas = [];
        Modelo::$fallar = null;
        Modelo::$excepcion = null;
        \gamboamartin\errores\errores::$error = false;
        \gamboamartin\errores\errores::$out = [];
        $this->logAnterior = (string)ini_get('error_log');
        ini_set('error_log', $this->directorio . '/errores.log');
        $_GET = [];
        $_SESSION = [];
        $_SERVER = ['HTTPS' => 'on', 'HTTP_HOST' => 'prueba.invalid', 'SCRIPT_NAME' => '/index.php'];
    }

    protected function tearDown(): void
    {
        ini_set('error_log', $this->logAnterior);
        foreach (new DirectoryIterator($this->directorio) as $archivo) {
            if (!$archivo->isDot()) {
                unlink($archivo->getPathname());
            }
        }
        rmdir($this->directorio);
    }

    private function resolver(): ?string
    {
        return resolver_logo_principal_framework(new stdClass(), $this->directorio);
    }

    private function imagenEsperada(): string
    {
        return 'data:image/png;base64,' . base64_encode($this->png);
    }

    public function testLoginAnonimoConservaBrandingPrincipal(): void
    {
        $_GET = ['seccion' => 'adm_session', 'accion' => 'login'];
        self::assertSame($this->imagenEsperada(), $this->resolver());
    }

    public function testAutenticadoSinAdministracionLogosConservaBranding(): void
    {
        $_SESSION = ['activa' => 1, 'usuario_id' => 5, 'grupo_id' => 6, 'valida_permiso' => []];
        self::assertSame($this->imagenEsperada(), $this->resolver());
    }

    public function testAutenticadoAdministradorTieneMismoBranding(): void
    {
        $_SESSION = ['activa' => 1, 'usuario_id' => 5, 'grupo_id' => 6,
            'valida_permiso' => [6 => ['org_logo' => ['subir_logo' => true]]]];
        self::assertSame($this->imagenEsperada(), $this->resolver());
    }

    public function testGetNoSeleccionaEmpresaLogoDocumentoNiArchivo(): void
    {
        $_GET = ['org_empresa_id' => 999, 'org_logo_id' => 888, 'doc_documento_id' => 777,
            'ruta' => '/etc/passwd'];
        self::assertSame($this->imagenEsperada(), $this->resolver());
        self::assertSame(['org_empresa.status' => 'activo'], Modelo::$consultas['org_empresa'][0]['filtro']);
        self::assertSame(2, Modelo::$consultas['org_empresa'][0]['limit']);
        self::assertSame([
            'org_logo.status' => 'activo', 'org_logo.es_principal' => 'activo',
            'org_logo.org_empresa_id' => 71
        ], Modelo::$consultas['org_logo'][0]['filtro']);
        self::assertSame(['doc_documento.id' => 91], Modelo::$consultas['doc_documento'][0]['filtro']);
    }

    public function testEmpresaAmbiguaDejaLogoEnBlanco(): void
    {
        Modelo::$filas['org_empresa'][] = ['org_empresa_id' => 72];
        self::assertNull($this->resolver());
    }

    public function testPrincipalesAmbiguosDejanLogoEnBlanco(): void
    {
        Modelo::$filas['org_logo'][] = Modelo::$filas['org_logo'][0];
        self::assertNull($this->resolver());
    }

    public function testAsociacionOtraEmpresaNoPublicaBytes(): void
    {
        Modelo::$filas['org_logo'][0]['org_logo_org_empresa_id'] = 72;
        self::assertNull($this->resolver());
    }

    public function testTipoDocumentoDistintoNoPublicaBytes(): void
    {
        Modelo::$filas['doc_documento'][0]['doc_documento_doc_tipo_documento_id'] = 8;
        self::assertNull($this->resolver());
    }

    public function testSinEmpresaNoConsultaLogos(): void
    {
        Modelo::$filas['org_empresa'] = [];
        self::assertNull($this->resolver());
        self::assertArrayNotHasKey('org_logo', Modelo::$consultas);
    }

    public function testSinPrincipalNoConsultaDocumentos(): void
    {
        Modelo::$filas['org_logo'] = [];
        self::assertNull($this->resolver());
        self::assertArrayNotHasKey('doc_documento', Modelo::$consultas);
    }

    public function testSinDocumentoNoPublica(): void
    {
        Modelo::$filas['doc_documento'] = [];
        self::assertNull($this->resolver());
    }

    public function testErrorModeloDetieneCadena(): void
    {
        foreach (['org_empresa', 'org_logo', 'doc_documento'] as $tabla) {
            Modelo::$consultas = [];
            Modelo::$fallar = $tabla;
            self::assertNull($this->resolver());
            self::assertFalse(\gamboamartin\errores\errores::$error);
            if ($tabla === 'org_empresa') {
                self::assertArrayNotHasKey('org_logo', Modelo::$consultas);
            }
        }
        Modelo::$consultas = [];
        \gamboamartin\errores\errores::$error = true;
        self::assertNull($this->resolver());
        self::assertTrue(\gamboamartin\errores\errores::$error);
        self::assertSame([], Modelo::$consultas);
    }

    public function testConexionAusenteNoConsulta(): void
    {
        self::assertNull(resolver_logo_principal_framework(null, $this->directorio));
        self::assertSame([], Modelo::$consultas);
    }

    public function testArchivoAusenteDejaLogoEnBlanco(): void
    {
        unlink($this->directorio . '/logo.png');
        self::assertNull($this->resolver());
    }

    public function testLectorRechazaRutasFueraAlmacen(): void
    {
        foreach (['/etc/passwd', '../logo.png', 'https://ejemplo.invalid/logo.png',
            'php://filter', 'archivos/otro/logo.png', 'archivos/doc_documento/../logo.png',
            'archivos/doc_documento/a/logo.png', "archivos/doc_documento/n\0.png",
            'archivos/doc_documento/a\\logo.png'] as $ruta) {
            self::assertNull(LectorLogoPrincipal::leer($this->directorio, $ruta), $ruta);
        }
    }

    public function testLectorRechazaSymlinkArchivo(): void
    {
        symlink($this->directorio . '/logo.png', $this->directorio . '/enlace.png');
        self::assertNull(LectorLogoPrincipal::leer($this->directorio, 'archivos/doc_documento/enlace.png'));
    }

    public function testLectorRechazaAlmacenNoCanonico(): void
    {
        self::assertNull(LectorLogoPrincipal::leer($this->directorio . '/.', 'archivos/doc_documento/logo.png'));
    }

    public function testLectorRechazaAlmacenSymlink(): void
    {
        symlink($this->directorio, $this->directorio . '/enlace');
        self::assertNull(LectorLogoPrincipal::leer($this->directorio . '/enlace', 'archivos/doc_documento/logo.png'));
    }

    public function testLectorRechazaSvgRenombrado(): void
    {
        file_put_contents($this->directorio . '/logo.png', '<svg xmlns="http://www.w3.org/2000/svg"></svg>');
        self::assertNull($this->resolver());
    }

    public function testLectorRechazaImagenVacia(): void
    {
        file_put_contents($this->directorio . '/logo.png', '');
        self::assertNull($this->resolver());
    }

    public function testLectorRechazaMayorDosMiB(): void
    {
        file_put_contents($this->directorio . '/logo.png', $this->png . str_repeat('x', 2097153));
        self::assertNull($this->resolver());
    }

    public function testLectorAceptaLimiteDosMiB(): void
    {
        $contenido = str_pad($this->png, 2097152, "\0");
        file_put_contents($this->directorio . '/logo.png', $contenido);
        self::assertSame('data:image/png;base64,' . base64_encode($contenido), $this->resolver());
    }

    public function testLectorAceptaJpegReal(): void
    {
        $jpeg = base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAABAAEDASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6goOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/9oADAMBAAIRAxEAPwDzWiiivSOA/9k=');
        file_put_contents($this->directorio . '/logo.png', $jpeg);
        self::assertSame('data:image/jpeg;base64,' . base64_encode($jpeg), $this->resolver());
    }

    public function testLectorRechazaArchivoOculto(): void
    {
        file_put_contents($this->directorio . '/.oculto.png', $this->png);
        self::assertNull(LectorLogoPrincipal::leer($this->directorio, 'archivos/doc_documento/.oculto.png'));
    }

    public function testCambioSesionNoCambiaBrandingNiConsultaPermisos(): void
    {
        $anonimo = $this->resolver();
        $_SESSION = ['activa' => 1, 'grupo_id' => 99, 'usuario_id' => 77];
        self::assertSame($anonimo, $this->resolver());
        self::assertSame(['org_empresa', 'org_logo', 'doc_documento'], array_keys(Modelo::$consultas));
    }

    public function testWrapperCacheaBrandingSinDependerDeSesion(): void
    {
        // Almacén de aplicación ausente: cachea ausencia sin fabricar una URL rota.
        $primero = logo_empresa_url_framework(new stdClass());
        self::assertNull($primero);
        $consultas = Modelo::$consultas;
        $_SESSION = ['activa' => 1, 'grupo_id' => 99, 'usuario_id' => 77];
        $_GET = ['org_empresa_id' => 999];
        self::assertSame($primero, logo_empresa_url_framework(new stdClass()));
        self::assertSame($consultas, Modelo::$consultas);
    }

    public function testVistasExistentesUsanHelperSinNuevoAcl(): void
    {
        foreach (['views/adm_session/login_ivitec.php', 'templates/nav/_menu_superior.php',
            'templates/nav/_redes_sociales.php', 'templates/footer/_footer_ivitec.php'] as $vista) {
            $fuente = file_get_contents(RAIZ_REPOSITORIO_PRUEBAS . '/' . $vista);
            self::assertStringContainsString('logo_empresa_url_framework(', $fuente);
        }
    }

    public function testFalloConsultaRestauraContextoYDiagnosticaSinDatosPrivados(): void
    {
        \gamboamartin\errores\errores::$out = ['salida anterior'];
        $_SESSION['error_resultado'] = [['mensaje' => 'anterior']];
        $_SESSION['usuario_id'] = 5;
        Modelo::$fallar = 'org_logo';
        self::assertNull($this->resolver());
        self::assertFalse(\gamboamartin\errores\errores::$error);
        self::assertSame(['salida anterior'], \gamboamartin\errores\errores::$out);
        self::assertSame([['mensaje' => 'anterior']], $_SESSION['error_resultado']);
        self::assertSame(5, $_SESSION['usuario_id']);
        $log = file_get_contents($this->directorio . '/errores.log');
        self::assertStringContainsString('[facturacion.branding] Fallo de consulta', $log);
        self::assertStringContainsString('etapa=org_logo', $log);
        self::assertStringNotContainsString('Detalle privado', $log);
    }

    public function testFalloConsultaNoCreaEstadoSesionResidual(): void
    {
        Modelo::$fallar = 'org_empresa';
        self::assertNull($this->resolver());
        self::assertSame([], \gamboamartin\errores\errores::$out);
        self::assertArrayNotHasKey('error_resultado', $_SESSION);
    }

    public function testExcepcionDevuelveAusenciaYDiagnosticoSinDetallePrivado(): void
    {
        Modelo::$excepcion = 'org_logo';
        self::assertNull($this->resolver());
        self::assertFalse(\gamboamartin\errores\errores::$error);
        self::assertSame([], \gamboamartin\errores\errores::$out);
        $log = file_get_contents($this->directorio . '/errores.log');
        self::assertStringContainsString('[facturacion.branding] Excepcion', $log);
        self::assertStringContainsString('etapa=org_logo', $log);
        self::assertStringNotContainsString('Detalle privado', $log);
    }

    public function testAusenciasNormalesNoGeneranDiagnostico(): void
    {
        $filas = Modelo::$filas;
        foreach (['org_empresa', 'org_logo', 'doc_documento'] as $tabla) {
            Modelo::$filas = $filas;
            Modelo::$filas[$tabla] = [];
            self::assertNull($this->resolver());
            self::assertFalse(\gamboamartin\errores\errores::$error);
        }
        self::assertFileDoesNotExist($this->directorio . '/errores.log');
    }

    public function testErrorPrevioSeConservaSinConsultasNiDiagnosticoNuevo(): void
    {
        \gamboamartin\errores\errores::$error = true;
        \gamboamartin\errores\errores::$out = ['previo'];
        $_SESSION['error_resultado'] = [['mensaje' => 'previo']];
        self::assertNull($this->resolver());
        self::assertNull(nombre_empresa_framework(new stdClass()));
        self::assertTrue(\gamboamartin\errores\errores::$error);
        self::assertSame(['previo'], \gamboamartin\errores\errores::$out);
        self::assertSame([['mensaje' => 'previo']], $_SESSION['error_resultado']);
        self::assertSame([], Modelo::$consultas);
        self::assertFileDoesNotExist($this->directorio . '/errores.log');
    }

    public function testNombreEmpresaIgnoraSeleccionGetYCacheaSistema(): void
    {
        $_GET['org_empresa_id'] = 999;
        self::assertSame('Empresa del sistema', nombre_empresa_framework(new stdClass()));
        self::assertSame(['org_empresa.status' => 'activo'], Modelo::$consultas['org_empresa'][0]['filtro']);
        self::assertSame(['org_empresa.id' => 'ASC'], Modelo::$consultas['org_empresa'][0]['order']);
        self::assertSame(2, Modelo::$consultas['org_empresa'][0]['limit']);
        $_GET['org_empresa_id'] = 111;
        self::assertSame('Empresa del sistema', nombre_empresa_framework(new stdClass()));
        self::assertCount(1, Modelo::$consultas['org_empresa']);
    }

    public function testNombreEmpresaAusenteNoGeneraError(): void
    {
        Modelo::$filas['org_empresa'] = [];
        self::assertNull(nombre_empresa_framework(new stdClass()));
        self::assertFalse(\gamboamartin\errores\errores::$error);
        self::assertFileDoesNotExist($this->directorio . '/errores.log');
    }

    public function testNombreEmpresaFalloRestauraContexto(): void
    {
        Modelo::$fallar = 'org_empresa';
        self::assertNull(nombre_empresa_framework(new stdClass()));
        self::assertFalse(\gamboamartin\errores\errores::$error);
        self::assertSame([], \gamboamartin\errores\errores::$out);
        self::assertArrayNotHasKey('error_resultado', $_SESSION);
        self::assertFileExists($this->directorio . '/errores.log');
    }

    public function testNombreEmpresaAmbiguaDejaNombreEnBlanco(): void
    {
        Modelo::$filas['org_empresa'][] = ['org_empresa_id' => 72, 'org_empresa_nombre_comercial' => 'Otra'];
        self::assertNull(nombre_empresa_framework(new stdClass()));
        self::assertFalse(\gamboamartin\errores\errores::$error);
        self::assertFileDoesNotExist($this->directorio . '/errores.log');
    }

    public function testPngTruncadoConCabeceraValidaNoPublica(): void
    {
        // IHDR y MIME válidos, pero sin flujo IDAT completo.
        $truncado = substr($this->png, 0, strpos($this->png, 'IDAT') + 5);
        self::assertIsArray(getimagesizefromstring($truncado));
        file_put_contents($this->directorio . '/logo.png', $truncado);
        self::assertNull($this->resolver());
    }

    public function testJpegTruncadoConCabeceraValidaNoPublica(): void
    {
        $imagen = imagecreatetruecolor(10, 10);
        ob_start();
        imagejpeg($imagen);
        $jpeg = (string)ob_get_clean();
        imagedestroy($imagen);
        // Conserva SOF (dimensiones), retira SOS y todos los datos comprimidos.
        $truncado = substr($jpeg, 0, strpos($jpeg, "\xFF\xDA"));
        self::assertIsArray(getimagesizefromstring($truncado));
        file_put_contents($this->directorio . '/logo.png', $truncado);
        self::assertNull($this->resolver());
    }

    public function testDimensionesExcesivasNoLleganADecodificacion(): void
    {
        foreach ([[8193, 1], [1, 8193], [4097, 4097]] as [$ancho, $alto]) {
            // Cabecera PNG sintética: calcular CRC mantiene IHDR válido sin reservar píxeles.
            $ihdr = pack('NNCCCCC', $ancho, $alto, 8, 2, 0, 0, 0);
            $png = "\x89PNG\r\n\x1a\n" . pack('N', strlen($ihdr)) . 'IHDR' . $ihdr
                . pack('N', crc32('IHDR' . $ihdr))
                . pack('N', 0) . 'IEND' . pack('N', crc32('IEND'));
            self::assertIsArray(getimagesizefromstring($png));
            file_put_contents($this->directorio . '/logo.png', $png);
            self::assertNull($this->resolver());
        }
    }
}
