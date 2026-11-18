<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PruebasLogo\Modelo;

/** Acopla el helper real a modelos dobles y archivos ficticios; no usa DB ni HTTP. */
final class LogoPrincipalTest extends TestCase
{
    private string $directorio;
    private string $png;

    protected function setUp(): void
    {
        require_once __DIR__ . '/dobles.php';
        require_once RAIZ_REPOSITORIO_PRUEBAS . '/templates/logo_service.php';
        $this->directorio = sys_get_temp_dir() . '/logo-principal-' . bin2hex(random_bytes(6));
        mkdir($this->directorio, 0700);
        $this->png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a9WQAAAAASUVORK5CYII=');
        file_put_contents($this->directorio . '/logo.png', $this->png);
        Modelo::$filas = [
            'org_empresa' => [['org_empresa_id' => 71]],
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
        \gamboamartin\errores\errores::$error = false;
        $_GET = [];
        $_SESSION = [];
        $_SERVER = ['HTTPS' => 'on', 'HTTP_HOST' => 'prueba.invalid', 'SCRIPT_NAME' => '/index.php'];
    }

    protected function tearDown(): void
    {
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

    private function fallback(): string
    {
        return 'https://prueba.invalid/archivos/doc_documento/logo.png';
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

    public function testEmpresaAmbiguaMantieneEnlaceSinBytes(): void
    {
        Modelo::$filas['org_empresa'][] = ['org_empresa_id' => 72];
        self::assertSame($this->fallback(), $this->resolver());
    }

    public function testPrincipalesAmbiguosMantienenEnlaceSinBytes(): void
    {
        Modelo::$filas['org_logo'][] = Modelo::$filas['org_logo'][0];
        self::assertSame($this->fallback(), $this->resolver());
    }

    public function testAsociacionOtraEmpresaNoPublicaBytes(): void
    {
        Modelo::$filas['org_logo'][0]['org_logo_org_empresa_id'] = 72;
        self::assertSame($this->fallback(), $this->resolver());
    }

    public function testTipoDocumentoDistintoNoPublicaBytes(): void
    {
        Modelo::$filas['doc_documento'][0]['doc_documento_doc_tipo_documento_id'] = 8;
        self::assertSame($this->fallback(), $this->resolver());
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
        Modelo::$fallar = 'org_empresa';
        self::assertNull($this->resolver());
        self::assertArrayNotHasKey('org_logo', Modelo::$consultas);
    }

    public function testConexionAusenteNoConsulta(): void
    {
        self::assertNull(resolver_logo_principal_framework(null, $this->directorio));
        self::assertSame([], Modelo::$consultas);
    }

    public function testArchivoAusenteMantieneEnlace(): void
    {
        unlink($this->directorio . '/logo.png');
        self::assertSame($this->fallback(), $this->resolver());
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
        self::assertSame($this->fallback(), $this->resolver());
    }

    public function testLectorRechazaImagenVacia(): void
    {
        file_put_contents($this->directorio . '/logo.png', '');
        self::assertSame($this->fallback(), $this->resolver());
    }

    public function testLectorRechazaMayorDosMiB(): void
    {
        file_put_contents($this->directorio . '/logo.png', $this->png . str_repeat('x', 2097153));
        self::assertSame($this->fallback(), $this->resolver());
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
        // Almacén de aplicación ausente en esta copia: conserva el enlace sin bytes.
        $primero = logo_empresa_url_framework(new stdClass());
        self::assertSame($this->fallback(), $primero);
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
}
