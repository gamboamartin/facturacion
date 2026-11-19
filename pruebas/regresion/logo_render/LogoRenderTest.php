<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PruebasLogoRender\Estado;

/** Vistas PHP reales y contratos de branding dobles; sin ORM, SQL, red ni credenciales. */
final class LogoRenderTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/dobles.php';
        Estado::$logo = null;
        \config\generales::$menu_superior_v2 = false;
        Estado::$consultas = [];
        $_GET = [];
        $_SESSION = ['activa' => 1];
    }

    private function render(string $vista): string
    {
        $conexion = new stdClass();
        $controlador = (object)[
            'link' => $conexion,
            'logo_empresa_url' => 'https://imagen-no-autorizada.invalid/rota.png',
            'menu_header' => '<li>Menú sintético</li>',
            'existe_msj' => false,
            'mensaje_html' => '',
            'menu_permitido' => [],
            'session_id' => 'sesion-sintetica',
            'registro_id' => -1,
            'adm_menu_id' => -1
        ];
        $links_menu = (object)['adm_session' => (object)['inicio' => 'index.php?accion=inicio']];
        set_error_handler(static function (int $nivel, string $mensaje, string $archivo, int $linea): never {
            throw new ErrorException($mensaje, 0, $nivel, $archivo, $linea);
        });
        ob_start();
        try {
            include RAIZ_REPOSITORIO_PRUEBAS . '/' . $vista;
            $html = ob_get_contents();
        } finally {
            ob_end_clean();
            restore_error_handler();
        }
        self::assertStringNotContainsString('imagen-no-autorizada.invalid', $html);
        self::assertSame([$conexion], Estado::$consultas, 'La vista debe consultar el logo del sistema una vez.');
        return $html;
    }

    private function comprobar(string $vista, string $clase, bool $presente): void
    {
        $imagen = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAADElEQVQImWNgYGAAAAAEAAGjChXjAAAAAElFTkSuQmCC';
        Estado::$logo = $presente ? $imagen : null;
        $html = $this->render($vista);
        $dom = new DOMDocument();
        // HTML5 genera avisos de parser en DOMDocument; la captura es solo para esa lectura.
        $antes = libxml_use_internal_errors(true);
        try {
            self::assertTrue($dom->loadHTML('<?xml encoding="UTF-8">' . $html));
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($antes);
        }
        $xpath = new DOMXPath($dom);
        $slot = $xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " ' . $clase . ' ")]');
        self::assertCount(1, $slot);
        $imagenes = $xpath->query('.//img', $slot->item(0));
        self::assertCount($presente ? 1 : 0, $imagenes);
        if ($presente) {
            self::assertSame($imagen, $imagenes->item(0)->getAttribute('src'));
        } else {
            self::assertSame('', trim($slot->item(0)->textContent));
            self::assertCount(0, $xpath->query('.//svg', $slot->item(0)));
        }
        self::assertCount(0, $xpath->query('//img[not(@src) or @src=""]'));
        self::assertStringNotContainsString('iv-login__client-placeholder', $html);
        self::assertSame(0, preg_match('/<img[^>]+src=[\"\']https?:/i', $html));
        if ($vista === 'templates/nav/_menu_superior.php') {
            self::assertStringContainsString('aria-label="Inicio"', $html);
            self::assertStringContainsString('aria-label="Inicio y sesión"', $html);
            self::assertStringContainsString('aria-label="Menú"', $html);
            self::assertStringContainsString('index.php?accion=logout', $html);
        }
        if ($vista === 'views/adm_session/login_ivitec.php') {
            $proveedor = $xpath->query('//img[@class="iv-login__brand-logo"]');
            self::assertCount(1, $proveedor);
            self::assertSame('img/ivitec-login/ivitec-logo.png', $proveedor->item(0)->getAttribute('src'));
        }
    }

    public function testLoginSinLogoDejaEspacioEnBlanco(): void
    {
        $this->comprobar('views/adm_session/login_ivitec.php', 'iv-login__client-logo', false);
    }

    public function testLoginConLogoUsaImagenDelSistema(): void
    {
        $this->comprobar('views/adm_session/login_ivitec.php', 'iv-login__client-logo', true);
    }

    public function testMenuSinLogoDejaEspacioEnBlanco(): void
    {
        $this->comprobar('templates/nav/_menu_superior.php', 'iv-nav__brand', false);
    }

    public function testMenuConLogoUsaImagenDelSistema(): void
    {
        $this->comprobar('templates/nav/_menu_superior.php', 'iv-nav__brand', true);
    }

    public function testFooterSinLogoDejaEspacioEnBlanco(): void
    {
        // El contenedor de marca también contiene descripción; se inspecciona solo la región de imagen.
        $html = $this->render('templates/footer/_footer_ivitec.php');
        self::assertStringNotContainsString('iv-footer__logo', $html);
        self::assertStringNotContainsString('<img', $html);
        self::assertStringContainsString('Sistema de facturación', $html);
        self::assertStringContainsString('aria-label="Pie de página del sistema"', $html);
    }

    public function testFooterConLogoUsaImagenDelSistema(): void
    {
        Estado::$logo = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAADElEQVQImWNgYGAAAAAEAAGjChXjAAAAAElFTkSuQmCC';
        $html = $this->render('templates/footer/_footer_ivitec.php');
        self::assertStringContainsString('class="iv-footer__logo" src="' . Estado::$logo . '"', $html);
        self::assertSame(0, preg_match('/<img[^>]+src=[\"\']https?:/i', $html));
        self::assertStringNotContainsString('src=""', $html);
        self::assertSame(1, substr_count($html, '<img'));
    }

    public function testRedesSinLogoConservaEncabezadoEnBlanco(): void
    {
        $html = $this->render('templates/nav/_redes_sociales.php');
        self::assertStringNotContainsString('<img', $html);
        self::assertStringContainsString('Menú sintético', $html);
        self::assertStringContainsString('index.php?accion=logout', $html);
        self::assertStringContainsString('Inicio', $html);
    }

    public function testRedesConLogoUsaImagenDelSistema(): void
    {
        Estado::$logo = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAADElEQVQImWNgYGAAAAAEAAGjChXjAAAAAElFTkSuQmCC';
        $html = $this->render('templates/nav/_redes_sociales.php');
        self::assertStringContainsString('src="' . Estado::$logo . '"', $html);
        self::assertSame(1, substr_count($html, '<img'));
        self::assertStringNotContainsString('src=""', $html);
        self::assertSame(0, preg_match('/<img[^>]+src=[\"\']https?:/i', $html));
        self::assertStringContainsString('Menú sintético', $html);
        self::assertStringContainsString('index.php?accion=logout', $html);
    }
}
