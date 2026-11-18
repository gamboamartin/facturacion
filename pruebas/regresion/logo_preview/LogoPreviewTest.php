<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PruebasPreview\Estado;
use gamboamartin\facturacion\controllers\controlador_org_logo;

/** Controlador y vista reales, modelos/ACL dobles documentados, ningún SQL ni petición HTTP. */
final class LogoPreviewTest extends TestCase
{
    private string $archivo;
    private string $ruta;
    private string $png;
    private array $directoriosCreados = [];

    protected function setUp(): void
    {
        require_once __DIR__ . '/dobles.php';
        require_once RAIZ_REPOSITORIO_PRUEBAS . '/controllers/controlador_org_logo.php';
        require_once RAIZ_REPOSITORIO_PRUEBAS . '/templates/logo_service.php';
        foreach (['archivos', 'archivos/doc_documento'] as $relativo) {
            $directorio = RAIZ_REPOSITORIO_PRUEBAS . '/' . $relativo;
            if (!is_dir($directorio)) {
                mkdir($directorio, 0700);
                $this->directoriosCreados[] = $directorio;
            }
        }
        $this->ruta = 'archivos/doc_documento/qa-preview-' . bin2hex(random_bytes(8)) . '.png';
        $this->archivo = RAIZ_REPOSITORIO_PRUEBAS . '/' . $this->ruta;
        $this->png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a9WQAAAAASUVORK5CYII=');
        file_put_contents($this->archivo, $this->png);
        Estado::$logos = [$this->fila(5, 'activo'), $this->fila(4, 'inactivo')];
        Estado::$consultas = Estado::$permisos = [];
        Estado::$permitido = true;
        Estado::$errorPermiso = false;
        \gamboamartin\errores\errores::$error = false;
        $_SESSION = ['activa' => 1, 'usuario_id' => 11, 'grupo_id' => 12];
        $_GET = ['session_id' => 'sesion-ficticia-no-valida'];
        $_SERVER = ['HTTPS' => 'on', 'HTTP_HOST' => 'prueba.invalid', 'SCRIPT_NAME' => '/index.php'];
    }

    protected function tearDown(): void
    {
        if (file_exists($this->archivo) || is_link($this->archivo)) {
            unlink($this->archivo);
        }
        foreach (array_reverse($this->directoriosCreados) as $directorio) {
            rmdir($directorio);
        }
    }

    private function fila(int $id, string $principal): array
    {
        return [
            'org_logo_id' => $id,
            'org_logo_status' => 'activo',
            'org_logo_es_principal' => $principal,
            'org_logo_org_empresa_id' => 71,
            'org_logo_doc_documento_id' => 91,
            'doc_documento_id' => 91,
            'doc_documento_doc_tipo_documento_id' => 9,
            'doc_documento_ruta_relativa' => $this->ruta
        ];
    }

    private function ejecutar(bool $ws = false): controlador_org_logo
    {
        $controlador = new controlador_org_logo();
        $controlador->subir_logo(header: false, ws: $ws);
        return $controlador;
    }

    private function imagen(): string
    {
        return 'data:image/png;base64,' . base64_encode($this->png);
    }

    private function sinBytes(controlador_org_logo $controlador): void
    {
        self::assertNull($controlador->logo_activo['imagen_privada_url'] ?? null);
        foreach ($controlador->logos as $fila) {
            self::assertNull($fila['imagen_privada_url'] ?? null);
        }
    }

    public function testPrincipalEHistoricoVisibleConAclExistente(): void
    {
        $controlador = $this->ejecutar();
        self::assertSame($this->imagen(), $controlador->logo_activo['imagen_privada_url']);
        self::assertSame($this->imagen(), $controlador->logos[0]['imagen_privada_url']);
        self::assertSame($this->imagen(), $controlador->logos[1]['imagen_privada_url']);
        self::assertSame([['subir_logo', 'org_logo']], Estado::$permisos);
    }

    public function testSinPrincipalConservaArrayVacioYVistaSinLogo(): void
    {
        Estado::$logos = [];
        $controlador = $this->ejecutar();
        self::assertSame([], $controlador->logo_activo);
        self::assertSame([], $controlador->logos);
        self::assertStringContainsString('No hay logo activo todavía.', $this->render($controlador));
    }

    public function testAnonimoNoLeeBytes(): void
    {
        $_SESSION = [];
        $this->sinBytes($this->ejecutar());
        self::assertSame([], Estado::$permisos);
    }

    public function testSesionInactivaNoLeeBytes(): void
    {
        $_SESSION['activa'] = 0;
        $this->sinBytes($this->ejecutar());
    }

    public function testUsuarioAusenteNoLeeBytes(): void
    {
        unset($_SESSION['usuario_id']);
        $this->sinBytes($this->ejecutar());
    }

    public function testGrupoAusenteNoLeeBytes(): void
    {
        unset($_SESSION['grupo_id']);
        $this->sinBytes($this->ejecutar());
    }

    public function testAclDenegadaNoLeeBytes(): void
    {
        Estado::$permitido = false;
        $this->sinBytes($this->ejecutar());
    }

    public function testErrorAclNoSeInterpretaComoPermiso(): void
    {
        Estado::$permitido = ['error' => 'ficticio'];
        Estado::$errorPermiso = true;
        $this->sinBytes($this->ejecutar());
    }

    public function testWsNoSerializaBytesNiConsultaAclAdicional(): void
    {
        $controlador = new controlador_org_logo();
        $salida = $controlador->subir_logo(header: false, ws: true);
        $this->sinBytes($controlador);
        self::assertStringNotContainsString('data:image', json_encode($salida));
        self::assertSame([], Estado::$permisos);
    }

    public function testEmpresaDiferenteNoLeeBytes(): void
    {
        foreach (Estado::$logos as &$fila) { $fila['org_logo_org_empresa_id'] = 72; }
        unset($fila);
        $this->sinBytes($this->ejecutar());
    }

    public function testDocumentoNoAsociadoNoLeeBytes(): void
    {
        foreach (Estado::$logos as &$fila) { $fila['doc_documento_id'] = 92; }
        unset($fila);
        $this->sinBytes($this->ejecutar());
    }

    public function testTipoNoLogoNoLeeBytes(): void
    {
        foreach (Estado::$logos as &$fila) { $fila['doc_documento_doc_tipo_documento_id'] = 8; }
        unset($fila);
        $this->sinBytes($this->ejecutar());
    }

    public function testFilaInactivaNoLeeBytes(): void
    {
        foreach (Estado::$logos as &$fila) { $fila['org_logo_status'] = 'inactivo'; }
        unset($fila);
        $this->sinBytes($this->ejecutar());
    }

    public function testFilaSinIdNoLeeBytes(): void
    {
        foreach (Estado::$logos as &$fila) { $fila['org_logo_id'] = 0; }
        unset($fila);
        $this->sinBytes($this->ejecutar());
    }

    public function testGetNoProporcionaRutaNiDocumento(): void
    {
        $_GET['ruta'] = '/etc/passwd';
        $_GET['doc_documento_id'] = 999;
        self::assertSame($this->imagen(), $this->ejecutar()->logos[0]['imagen_privada_url']);
    }

    public function testTraversalEnFilaEsRechazado(): void
    {
        foreach (Estado::$logos as &$fila) { $fila['doc_documento_ruta_relativa'] = 'archivos/doc_documento/../../secreto'; }
        unset($fila);
        $this->sinBytes($this->ejecutar());
    }

    public function testMimeFalsoEnArchivoEsRechazado(): void
    {
        file_put_contents($this->archivo, '<svg></svg>');
        $this->sinBytes($this->ejecutar());
    }

    public function testConsultasConservanFiltrosYLimiteDoce(): void
    {
        $this->ejecutar();
        $consultas = array_values(array_filter(Estado::$consultas, fn($c) => isset($c['logos'])));
        self::assertSame(12, $consultas[1]['logos']['limit']);
        self::assertSame(['org_logo.status' => 'activo', 'org_logo.org_empresa_id' => 71], $consultas[1]['logos']['filtro']);
        foreach (['org_logo_org_empresa_id', 'org_logo_doc_documento_id', 'doc_documento_id', 'doc_documento_doc_tipo_documento_id'] as $campo) {
            self::assertContains($campo, $consultas[0]['logos']['columnas']);
            self::assertContains($campo, $consultas[1]['logos']['columnas']);
        }
    }

    private function render(controlador_org_logo $controlador): string
    {
        ob_start();
        try {
            include RAIZ_REPOSITORIO_PRUEBAS . '/views/org_logo/subir_logo.php';
            return ob_get_contents();
        } finally {
            ob_end_clean();
        }
    }

    public function testRenderRealMuestraPrincipalYListaAutorizada(): void
    {
        $html = $this->render($this->ejecutar());
        self::assertSame(3, substr_count($html, $this->imagen()));
        self::assertStringContainsString('name="org_logo_id"', $html);
        self::assertStringContainsString('value="5"', $html);
        self::assertStringContainsString('value="4"', $html);
    }

    public function testRenderDenegadoMantieneFallbackSinBytes(): void
    {
        Estado::$permitido = false;
        $html = $this->render($this->ejecutar());
        self::assertStringNotContainsString('data:image', $html);
        self::assertStringContainsString('https://prueba.invalid/' . $this->ruta, $html);
    }
}
