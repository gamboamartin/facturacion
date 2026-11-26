<?php
declare(strict_types=1);
namespace gamboamartin\errores {
    class errores { public static bool $error = false; }
}
namespace gamboamartin\template { class html {} }
namespace gamboamartin\template_1 { class html extends \gamboamartin\template\html {} }
namespace gamboamartin\comercial\controllers {
    class controlador_com_agente {
        public \PDO $link;
        public object $conf_generales;
        public object $modelo;
        public array $childrens_data = [];
        public function __construct(\PDO $link, \gamboamartin\template\html $html, \stdClass $paths_conf) {
            $this->link = $link;
            $this->conf_generales = new \config\generales();
        }
        public function lista(bool $header, bool $ws = false): array { return ['delegado' => true]; }
    }
}
namespace gamboamartin\comercial\models {
    class com_agente {
        public static array $registros = [];
        public function __construct(\PDO $link) {}
        public function filtro_and(array $filtro = [], array $columnas = []): object {
            return (object)['registros' => self::$registros];
        }
    }
    class com_tipo_agente {
        public function __construct(\PDO $link) {}
        public function filtro_and(array $columnas = []): object { return (object)['registros' => []]; }
    }
}
