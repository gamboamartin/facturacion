<?php
namespace gamboamartin\errores {
    final class errores { public static bool $error = false; }
}
namespace PruebasLogo {
    /** Dobles de consultas, sin autoload, SQL ni credenciales. */
    class Modelo {
        public static array $filas = [];
        public static array $consultas = [];
        public static ?string $fallar = null;
        public function __construct($link) {}
        public function filtro_and(...$argumentos): object {
            $tabla = (new \ReflectionClass($this))->getShortName();
            self::$consultas[$tabla][] = $argumentos;
            if (self::$fallar === $tabla) {
                \gamboamartin\errores\errores::$error = true;
            }
            return (object)['registros' => self::$filas[$tabla] ?? []];
        }
    }
}
namespace gamboamartin\organigrama\models {
    class org_empresa extends \PruebasLogo\Modelo {}
    class org_logo extends \PruebasLogo\Modelo {}
}
namespace gamboamartin\documento\models {
    class doc_documento extends \PruebasLogo\Modelo {}
}
