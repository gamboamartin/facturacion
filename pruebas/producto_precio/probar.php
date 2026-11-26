<?php
declare(strict_types=1);

namespace gamboamartin\errores {
    class errores {
        public static bool $error = false;
        public function error(string $mensaje, mixed $data): array {
            self::$error = true;
            return ['error' => true, 'mensaje' => $mensaje, 'data' => $data];
        }
    }
}
namespace gamboamartin\comercial\models {
    class com_producto {
        public array $registro = [];
        public array $operaciones = [];
        public mixed $link = null;
        public \gamboamartin\errores\errores $error;
        public function __construct() { $this->error = new \gamboamartin\errores\errores(); }
        public function alta_bd(array $keys_integra_ds = ['codigo','descripcion']): array|\stdClass {
            $this->operaciones[] = ['alta', $this->registro, $keys_integra_ds];
            $this->registro['cat_sat_conf_imps_id'] = 1;
            return (object)['registro_id' => 12];
        }
        public function modifica_bd(array $registro, int $id, bool $reactiva = false,
            array $keys_integra_ds = ['codigo','descripcion']): array|\stdClass {
            $this->operaciones[] = ['modifica', $registro, $id, $reactiva, $keys_integra_ds];
            return (object)['registro_id' => $id];
        }
        public function registro(int $registro_id, bool $retorno_obj = false): \stdClass {
            $this->operaciones[] = ['leer', $registro_id];
            return (object)['cat_sat_conf_imps_id' => 1];
        }
    }
}
namespace gamboamartin\cat_sat\models {
    class cat_sat_conf_imps {
        public function __construct(mixed $link) {}
        public function get_impuestos(int $cat_sat_conf_imps_id): \stdClass {
            $GLOBALS['impuestos'][] = ['leer', $cat_sat_conf_imps_id];
            return (object)['retenciones' => [], 'traslados' => []];
        }
    }
}
namespace gamboamartin\facturacion\models {
    class fc_conf_traslado {
        public function __construct(mixed $link) {}
        public function elimina_con_filtro_and(array $filtro): \stdClass {
            $GLOBALS['impuestos'][] = ['limpiar', static::class, $filtro];
            return new \stdClass();
        }
    }
    class fc_conf_retenido extends fc_conf_traslado {}
}
namespace {
    require dirname(__DIR__, 2).'/orm/com_producto.php';
    $casos = [];
    $aserciones = 0;
    function verificar(bool $ok, string $mensaje): void {
        global $aserciones;
        $aserciones++;
        if (!$ok) throw new RuntimeException($mensaje);
    }
    // El doble marca cualquier llegada a persistencia o regeneración fiscal.
    // La prueba HTTP/MySQL separada certifica además la herencia real.
    $precios = [
        'cero_entero' => [true, 0], 'cero_texto' => [true, '0'],
        'negativo_entero' => [true, -1], 'negativo_decimal' => [true, '-1.20'],
        'decimal_tres_posiciones' => [true, '10.125'], 'decimal_float' => [true, 0.025],
        'entero' => [true, 100], 'exponente_finito' => [true, '1e2'],
        'texto' => [false, 'NO_ES_NUMERO'], 'vacio' => [false, ''],
        'espacios' => [false, ' '], 'nulo' => [false, null],
        'arreglo' => [false, []], 'booleano' => [false, true],
        'nan' => [false, NAN], 'infinito' => [false, INF],
        'exponente_infinito' => [false, '1e9999'], 'coma_decimal' => [false, '1,25'],
    ];
    try {
        foreach (['alta','modifica'] as $accion) {
            foreach ($precios + ['ausente' => [true, null]] as $nombre => [$valido, $precio]) {
                \gamboamartin\errores\errores::$error = false;
                $GLOBALS['impuestos'] = [];
                $modelo = new \gamboamartin\facturacion\models\com_producto();
                $registro = ['codigo' => 'PRUEBA', 'descripcion' => 'Producto ficticio'];
                if ($nombre !== 'ausente') $registro['precio'] = $precio;
                $modelo->registro = $registro;
                $resultado = $accion === 'alta' ? $modelo->alta_bd(keys_integra_ds: ['codigo'])
                    : $modelo->modifica_bd(registro: $registro, id: 12, reactiva: true, keys_integra_ds: ['codigo']);
                if (!$valido) {
                    verificar(is_array($resultado) && ($resultado['error'] ?? false) === true
                        && str_contains($resultado['mensaje'], 'precio') && \gamboamartin\errores\errores::$error,
                        $accion.'/'.$nombre.': falta error claro');
                    verificar($modelo->operaciones === [] && $GLOBALS['impuestos'] === [],
                        $accion.'/'.$nombre.': llegó a persistencia, lectura o impuestos');
                } else {
                    verificar($resultado instanceof \stdClass && !\gamboamartin\errores\errores::$error,
                        $accion.'/'.$nombre.': rechazo de número permitido');
                    verificar($modelo->operaciones[0][1] === $registro,
                        $accion.'/'.$nombre.': alteró el valor o actualización parcial');
                    verificar(count($GLOBALS['impuestos']) === 3,
                        $accion.'/'.$nombre.': perdió regeneración fiscal existente');
                }
                $casos[] = $accion.'/'.$nombre;
            }
        }
        echo json_encode(['estado'=>'PASS_PRODUCTO_PRECIO','casos_ejecutados'=>count($casos),
            'aserciones'=>$aserciones,'casos'=>$casos,'sin_bd_ni_pac'=>true], JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR), PHP_EOL;
    } catch (Throwable $error) { fwrite(STDERR, 'FAIL_PRODUCTO_PRECIO: '.$error->getMessage().PHP_EOL); exit(1); }
}
