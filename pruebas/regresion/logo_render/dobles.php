<?php
declare(strict_types=1);
namespace PruebasLogoRender {
    final class Estado {
        public static ?string $logo = null;
        public static array $consultas = [];
    }
}
namespace config {
    final class generales { public static bool $menu_superior_v2 = false; }
}
namespace gamboamartin\system {
    final class links_menu {
        public object $links;
        public function __construct($link, int $registro_id) {
            $this->links = (object)['adm_session' => (object)['logout' => 'index.php?accion=logout']];
        }
    }
}
namespace {
    // Dobles del contrato público de branding: solo aíslan datos, no el render PHP.
    function logo_empresa_url_framework($link): ?string {
        \PruebasLogoRender\Estado::$consultas[] = $link;
        return \PruebasLogoRender\Estado::$logo;
    }
    function nombre_empresa_framework($link): ?string {
        return 'Empresa de prueba';
    }
}
