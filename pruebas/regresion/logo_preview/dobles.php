<?php
namespace gamboamartin\errores {
    final class errores { public static bool $error = false; }
}
namespace PruebasPreview {
    final class Estado {
        public static array $logos = [];
        public static array $consultas = [];
        public static array $permisos = [];
        public static bool|array $permitido = true;
        public static bool $errorPermiso = false;
    }
}
namespace gamboamartin\organigrama\controllers {
    class controlador_org_logo {
        public object $link;
        public int $registro_id = 0;
        public string $seccion_titulo = '';
        public string $titulo_lista = '';
        public function __construct() { $this->link = new \stdClass(); }
        public function retorno_error($mensaje, $datos, $header, $ws): array {
            return ['error' => $mensaje];
        }
    }
}
namespace gamboamartin\organigrama\models {
    class org_empresa {
        public function __construct($link) {}
        public function filtro_and(...$argumentos): object {
            \PruebasPreview\Estado::$consultas[] = ['empresa' => $argumentos];
            return (object)['registros' => [['org_empresa_id' => 71]]];
        }
    }
    class org_logo {
        public function __construct($link) {}
        public function filtro_and(...$argumentos): object {
            \PruebasPreview\Estado::$consultas[] = ['logos' => $argumentos];
            $filas = \PruebasPreview\Estado::$logos;
            if (isset($argumentos['filtro']['org_logo.es_principal'])) {
                $filas = array_values(array_filter($filas,
                    fn(array $fila): bool => ($fila['org_logo_es_principal'] ?? '') === 'activo'));
            }
            return (object)['registros' => $filas];
        }
    }
}
namespace gamboamartin\administrador\models {
    final class adm_accion {
        public function __construct($link) {}
        public function permiso(string $accion, string $seccion): bool|array {
            \PruebasPreview\Estado::$permisos[] = [$accion, $seccion];
            \gamboamartin\errores\errores::$error = \PruebasPreview\Estado::$errorPermiso;
            return \PruebasPreview\Estado::$permitido;
        }
    }
}
