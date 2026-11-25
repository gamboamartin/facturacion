<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PruebasLogo\Modelo;

/** Marca configurada con imágenes sintéticas; sin empresas reales, SQL ni red. */
final class LogoInterfazTest extends TestCase
{
    private string $directorio;
    private string $principal;
    private string $interfaz;

    protected function setUp(): void
    {
        require_once __DIR__ . '/../logo_principal/dobles.php';
        require_once __DIR__ . '/dobles.php';
        require_once RAIZ_REPOSITORIO_PRUEBAS . '/templates/logo_service.php';
        $this->directorio = sys_get_temp_dir() . '/logo-interfaz-' . bin2hex(random_bytes(6));
        mkdir($this->directorio, 0700);
        $this->principal = $this->png(1);
        $this->interfaz = $this->png(2);
        file_put_contents($this->directorio . '/principal.png', $this->principal);
        file_put_contents($this->directorio . '/interfaz.png', $this->interfaz);
        Modelo::$filas = [];
        Modelo::$consultas = [];
        Modelo::$fallar = Modelo::$excepcion = null;
        \gamboamartin\errores\errores::$error = false;
        \config\generales::$logo_interfaz = '';
        $_GET = $_SESSION = [];
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directorio . '/*') as $archivo) {
            unlink($archivo);
        }
        rmdir($this->directorio);
    }

    private function png(int $ancho): string
    {
        $imagen = imagecreatetruecolor($ancho, 1);
        ob_start();
        imagepng($imagen);
        $contenido = (string)ob_get_clean();
        imagedestroy($imagen);
        return $contenido;
    }

    private function resolver(): ?string
    {
        return resolver_logo_interfaz_framework(new stdClass(), $this->directorio);
    }

    public function testSinConfiguracionConservaLogoEnBlanco(): void
    {
        self::assertNull($this->resolver());
    }

    public function testMarcaExplicitaFuncionaSinEmpresaFiscal(): void
    {
        \config\generales::$logo_interfaz = 'archivos/doc_documento/interfaz.png';
        self::assertSame('data:image/png;base64,' . base64_encode($this->interfaz), $this->resolver());
        self::assertArrayNotHasKey('org_logo', Modelo::$consultas);
    }

    public function testLogoPrincipalConservaPrioridadSobreMarca(): void
    {
        \config\generales::$logo_interfaz = 'archivos/doc_documento/interfaz.png';
        Modelo::$filas = [
            'org_empresa' => [['org_empresa_id' => 71]],
            'org_logo' => [['org_logo_org_empresa_id' => 71, 'org_logo_doc_documento_id' => 91]],
            'doc_documento' => [[
                'doc_documento_ruta_relativa' => 'archivos/doc_documento/principal.png',
                'doc_documento_doc_tipo_documento_id' => 9,
            ]],
        ];
        self::assertSame('data:image/png;base64,' . base64_encode($this->principal), $this->resolver());
    }

    public function testRechazaUrlRemotaConfigurada(): void
    {
        \config\generales::$logo_interfaz = 'https://ejemplo.invalid/interfaz.png';
        self::assertNull($this->resolver());
    }

    public function testRechazaRecorridoFueraDelAlmacen(): void
    {
        \config\generales::$logo_interfaz = 'archivos/doc_documento/../interfaz.png';
        self::assertNull($this->resolver());
    }

    public function testArchivoAusenteConservaAusencia(): void
    {
        \config\generales::$logo_interfaz = 'archivos/doc_documento/ausente.png';
        self::assertNull($this->resolver());
    }

    public function testConfiguracionInvalidaNoPublicaArchivo(): void
    {
        \config\generales::$logo_interfaz = ['archivos/doc_documento/interfaz.png'];
        self::assertNull($this->resolver());
    }

    public function testPeticionNoSeleccionaLaMarca(): void
    {
        \config\generales::$logo_interfaz = 'archivos/doc_documento/interfaz.png';
        $_GET = ['logo_interfaz' => 'archivos/doc_documento/principal.png'];
        self::assertSame('data:image/png;base64,' . base64_encode($this->interfaz), $this->resolver());
    }

    public function testErrorPrevioNoSeOcultaNiPublicaMarca(): void
    {
        \config\generales::$logo_interfaz = 'archivos/doc_documento/interfaz.png';
        \gamboamartin\errores\errores::$error = true;
        self::assertNull($this->resolver());
        self::assertTrue(\gamboamartin\errores\errores::$error);
    }
}
