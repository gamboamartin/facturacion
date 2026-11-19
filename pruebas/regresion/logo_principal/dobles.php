<?php
namespace gamboamartin\errores {
    final class errores { public static bool $error = false; public static array $out = []; }
}
namespace PruebasLogo {
    /** Dobles de consultas, sin autoload, SQL ni credenciales. */
    class Modelo {
        public static array $filas = [];
        public static array $consultas = [];
        public static ?string $fallar = null;
        public static ?string $excepcion = null;
        public function __construct($link) {}
        public function filtro_and(...$argumentos): object {
            $tabla = (new \ReflectionClass($this))->getShortName();
            self::$consultas[$tabla][] = $argumentos;
            if (self::$excepcion === $tabla) {
                throw new \RuntimeException('Detalle privado que no debe llegar al log');
            }
            if (self::$fallar === $tabla) {
                \gamboamartin\errores\errores::$error = true;
                \gamboamartin\errores\errores::$out[] = 'Detalle privado';
                $_SESSION['error_resultado'][] = ['detalle' => 'Detalle privado'];
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
