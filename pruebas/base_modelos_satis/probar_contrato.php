<?php
declare(strict_types=1);

use gamboamartin\base_modelos\base_modelos;
use gamboamartin\errores\errores;

set_error_handler(static function (int $nivel, string $mensaje, string $archivo, int $linea): never {
    throw new ErrorException($mensaje, 0, $nivel, $archivo, $linea);
});
if ($argc !== 2 || !is_dir($argv[1])) { throw new RuntimeException('Se requiere vendor del candidato QA.'); }
$vendor = realpath($argv[1]);
$fuentes = [
    'gamboamartin\\validacion\\_codigos' => 'gamboa.martin/validacion/src/_codigos.php',
    'gamboamartin\\base_modelos\\base_modelos' => 'gamboa.martin/base_modelos/src/base_modelos.php',
    'gamboamartin\\errores\\errores' => 'gamboa.martin/errores/src/errores.php',
    'gamboamartin\\validacion\\validacion' => 'gamboa.martin/validacion/src/validacion.php',
    'base\\controller\\valida_controller' => 'gamboa.martin/administrador/base/controller/valida_controller.php',
    'base\\orm\\modelo_base' => 'gamboa.martin/administrador/base/orm/modelo_base.php',
    'base\\orm\\modelo' => 'gamboa.martin/administrador/base/orm/modelo.php',
    'base\\orm\\upd' => 'gamboa.martin/administrador/base/orm/upd.php',
    'gamboamartin\\system\\datatables\\validacion_dt' => 'gamboa.martin/system/src/datatables/validacion_dt.php',
];
require $vendor.'/composer/ClassLoader.php';
$mapa = require $vendor.'/composer/autoload_psr4.php';
$cargador = new \Composer\Autoload\ClassLoader();
foreach (['gamboamartin\\base_modelos\\', 'gamboamartin\\validacion\\', 'gamboamartin\\errores\\',
    'base\\', 'gamboamartin\\system\\'] as $prefijo) {
    if (!isset($mapa[$prefijo])) { throw new RuntimeException('Falta namespace Composer: '.$prefijo); }
    $cargador->addPsr4($prefijo, $mapa[$prefijo]);
}
spl_autoload_register(static function (string $clase) use ($fuentes, $cargador, $vendor): void {
    if (!isset($fuentes[$clase])) { throw new RuntimeException('Clase/configuración prohibida: '.$clase); }
    $archivo = $cargador->findFile($clase);
    if (!$archivo || realpath($archivo) !== realpath($vendor.'/'.$fuentes[$clase])) {
        throw new RuntimeException('Fuente Composer incorrecta: '.$clase);
    }
    require $archivo;
});
$aserciones = 0;
$ejecutados = [];
function exigir(bool $condicion, string $mensaje): void {
    global $aserciones;
    $aserciones++;
    if (!$condicion) { throw new RuntimeException('No pasó: '.$mensaje); }
}
function caso(string $nombre, callable $prueba): void {
    global $aserciones, $ejecutados;
    errores::$error = false;
    errores::$out = [];
    $_SESSION = [];
    $antes = $aserciones;
    $prueba();
    $ejecutados[] = $nombre;
    echo 'PASS: '.$nombre.' ('.($aserciones - $antes)." assertions)\n";
}
function exito(mixed $respuesta): void {
    exigir($respuesta === true, 'retorno true exacto');
    exigir(!errores::$error, 'sin error global');
}
function error_esperado(mixed $respuesta, string $mensaje, mixed $datos): void {
    exigir(is_array($respuesta) && ($respuesta['error'] ?? null) === 1, 'contrato real de errores');
    exigir($respuesta['mensaje_limpio'] === $mensaje, 'mensaje específico');
    exigir($respuesta['data'] === $datos, 'conserva datos de la causa');
    exigir(errores::$error && count(errores::$out) > 0, 'estado y salida real de errores');
    exigir(count($_SESSION['error_resultado'] ?? []) > 0, 'registro de error en memoria');
}

caso('autoload_herencia_firmas_publicas_finales', static function (): void {
    $objeto = new base_modelos();
    exigir($objeto instanceof \gamboamartin\validacion\validacion, 'herencia real singular');
    exigir(count(array_filter((new ReflectionClass($objeto))->getMethods(), static fn ($m) =>
        $m->getDeclaringClass()->getName() === base_modelos::class)) === 2, 'dos métodos propios');
    foreach (['valida_datos_lista_entrada' => ['accion', 'seccion'],
        'valida_transaccion_activa' => ['aplica_transaccion_inactivo', 'registro', 'registro_id', 'tabla']] as $nombre => $parametros) {
        $metodo = new ReflectionMethod($objeto, $nombre);
        exigir($metodo->isPublic() && $metodo->isFinal(), 'API pública final');
        exigir(array_map(static fn ($p) => $p->getName(), $metodo->getParameters()) === $parametros,
            'argumentos nombrados compatibles');
    }
});
caso('lista_valida_con_argumentos_nombrados', static function (): void {
    exito((new base_modelos())->valida_datos_lista_entrada(seccion: 'fc_factura', accion: 'lista'));
});
caso('lista_prefijo_models_valido_sin_mutar_entrada', static function (): void {
    $seccion = 'models\\fc_factura';
    exito((new base_modelos())->valida_datos_lista_entrada('lista', $seccion));
    exigir($seccion === 'models\\fc_factura', 'entrada sin mutación');
});
foreach (['lista_seccion_vacia_precede_accion' => ['', ''],
    'lista_solo_prefijo_models_rechaza' => ['lista', 'models\\'],
    'lista_prefijo_repetido_sin_seccion_rechaza' => ['lista', 'models\\models\\']] as $nombre => [$accion, $seccion]) {
    caso($nombre, static function () use ($accion, $seccion): void {
        error_esperado((new base_modelos())->valida_datos_lista_entrada($accion, $seccion),
            'Error seccion no puede venir vacio', '');
    });
}
caso('lista_accion_vacia_rechaza', static function (): void {
    error_esperado((new base_modelos())->valida_datos_lista_entrada('', 'fc_factura'), 'Error no existe la accion', '');
});
caso('lista_no_certifica_existencia_de_accion_o_seccion', static function (): void {
    exito((new base_modelos())->valida_datos_lista_entrada('accion_ficticia_qa', 'seccion_ficticia_qa'));
    exito((new base_modelos())->valida_datos_lista_entrada(' ', ' '));
});
caso('transaccion_activa_conserva_registro', static function (): void {
    $registro = ['fc_factura_status' => 'activo', 'dato_qa' => 'ficticio'];
    $antes = $registro;
    exito((new base_modelos())->valida_transaccion_activa(false, $registro, 7, 'fc_factura'));
    exigir($registro === $antes, 'no modifica datos del llamador');
});
caso('transaccion_tabla_con_espacios_normaliza_clave', static function (): void {
    exito((new base_modelos())->valida_transaccion_activa(false, ['fc_factura_status' => 'activo'], 7, ' fc_factura '));
});
foreach ([false, true] as $permite) {
    caso('transaccion_tabla_vacia_rechaza_'.($permite ? 'con_permiso' : 'sin_permiso'), static function () use ($permite): void {
        error_esperado((new base_modelos())->valida_transaccion_activa($permite, [], 0, '  '),
            'Error la tabla esta vacia', '');
    });
}
foreach (['cero' => 0, 'negativo' => -7] as $nombre => $id) {
    caso('transaccion_id_'.$nombre.'_rechaza', static function () use ($id): void {
        error_esperado((new base_modelos())->valida_transaccion_activa(false, [], $id, 'fc_factura'),
            'Error el id debe ser mayor a 0', $id);
    });
}
foreach (['ausente' => [], 'null' => ['fc_factura_status' => null], 'otra_tabla' => ['fc_nota_credito_status' => 'activo']]
    as $nombre => $registro) {
    caso('transaccion_status_'.$nombre.'_rechaza', static function () use ($registro): void {
        error_esperado((new base_modelos())->valida_transaccion_activa(false, $registro, 7, 'fc_factura'),
            'Error no existe el registro con el campo fc_factura_status', $registro);
    });
}
caso('transaccion_inactiva_bloquea', static function (): void {
    $registro = ['fc_factura_status' => 'inactivo'];
    error_esperado((new base_modelos())->valida_transaccion_activa(false, $registro, 7, 'fc_factura'),
        'Error el registro no puede ser manipulado', $registro);
});
caso('permiso_explicito_permite_inactivo', static function (): void {
    exito((new base_modelos())->valida_transaccion_activa(true, ['fc_factura_status' => 'inactivo'], 7, 'fc_factura'));
});
caso('permiso_explicito_omite_id_y_status_por_contrato', static function (): void {
    exito((new base_modelos())->valida_transaccion_activa(true, [], 0, 'fc_factura'));
});
caso('status_distinto_de_inactivo_se_admite_por_contrato', static function (): void {
    exito((new base_modelos())->valida_transaccion_activa(false, ['fc_factura_status' => ''], 7, 'fc_factura'));
});
caso('herencia_valida_claves_con_cero', static function (): void {
    exito((new base_modelos())->valida_existencia_keys(['total'], ['total' => 0]));
});
caso('herencia_error_clave_ausente', static function (): void {
    error_esperado((new base_modelos())->valida_existencia_keys(['total'], []), 'Error total no existe en el registro', []);
});
caso('exito_no_borra_error_previo', static function (): void {
    $validador = new base_modelos();
    errores::$error = true;
    exigir($validador->valida_datos_lista_entrada('lista', 'fc_factura') === true, 'validación válida');
    exigir(errores::$error, 'preserva estado previo del llamador');
});
caso('administrador_descendiente_real_hereda_y_bloquea', static function (): void {
    $validador = new \base\controller\valida_controller();
    exigir($validador instanceof base_modelos, 'descendiente real');
    exigir((new ReflectionMethod($validador, 'valida_transaccion_activa'))->getDeclaringClass()->getName()
        === base_modelos::class, 'método del paquete migrado');
    exito($validador->valida_datos_lista_entrada('lista', 'fc_factura'));
    $registro = ['fc_factura_status' => 'inactivo'];
    error_esperado($validador->valida_transaccion_activa(false, $registro, 7, 'fc_factura'),
        'Error el registro no puede ser manipulado', $registro);
});
// Se ejecuta la lógica real de upd con un modelo real sin constructor: no hay PDO ni configuración.
function modelo_qa(bool $permite): \base\orm\modelo {
    $modelo = (new ReflectionClass(\base\orm\modelo::class))->newInstanceWithoutConstructor();
    $modelo->validacion = new base_modelos();
    $modelo->aplica_transaccion_inactivo = $permite;
    $modelo->registro_id = 7;
    $modelo->tabla = 'fc_factura';
    return $modelo;
}
caso('orm_upd_real_activo_y_permiso_inactivo', static function (): void {
    $metodo = new ReflectionMethod(\base\orm\upd::class, 'reactiva');
    $upd = new \base\orm\upd();
    exito($metodo->invoke($upd, modelo_qa(false), false, ['fc_factura_status' => 'activo']));
    exito($metodo->invoke($upd, modelo_qa(true), false, ['fc_factura_status' => 'inactivo']));
});
caso('orm_upd_real_conserva_causa_inactivo', static function (): void {
    $registro = ['fc_factura_status' => 'inactivo'];
    $respuesta = (new ReflectionMethod(\base\orm\upd::class, 'reactiva'))
        ->invoke(new \base\orm\upd(), modelo_qa(false), false, $registro);
    exigir(is_array($respuesta) && $respuesta['mensaje_limpio'] === 'Error al validar transaccion activa', 'envoltura real ORM');
    error_esperado($respuesta['data'], 'Error el registro no puede ser manipulado', $registro);
    exigir(str_contains($_SESSION['error_resultado'][0]['class'], base_modelos::class), 'causa procede del paquete migrado');
});
caso('system_real_compatibilidad_validacion_compartida', static function (): void {
    $validador = new \gamboamartin\system\datatables\validacion_dt();
    exigir(!$validador instanceof base_modelos, 'system tiene su propio descendiente de validacion singular');
    exito($validador->valida_base('fc_factura_folio', 'folio'));
    error_esperado($validador->valida_base('', 'folio'), 'Error column no puede venir vacia', '');
});
caso('fuentes_reales_sin_config_sesion_ni_bd', static function () use ($vendor, $fuentes): void {
    foreach ($fuentes as $clase => $archivo) {
        exigir(class_exists($clase), 'clase real disponible');
        exigir(realpath((new ReflectionClass($clase))->getFileName()) === realpath($vendor.'/'.$archivo), 'fuente exacta');
    }
    exigir(!class_exists('config\\database', false) && !class_exists('config\\generales', false), 'configuración privada ausente');
    exigir(!class_exists('gamboamartin\\test\\test', false), 'no ejecuta test que abre conexiones');
    exigir(session_status() === PHP_SESSION_NONE, 'sin sesión PHP real');
    $permitidos = [realpath(__FILE__), realpath($vendor.'/composer/ClassLoader.php'), realpath($vendor.'/composer/autoload_psr4.php')];
    foreach ($fuentes as $archivo) { $permitidos[] = realpath($vendor.'/'.$archivo); }
    exigir(array_diff(array_map('realpath', get_included_files()), $permitidos) === [], 'solo archivos declarados');
});
$registro = json_decode(file_get_contents(__DIR__.'/registro.json'), true, 512, JSON_THROW_ON_ERROR);
exigir($ejecutados === $registro['casos'], 'identidad y orden completos');
exigir(count($ejecutados) === $registro['casos_previstos'], 'conteo completo');
$hashes = [];
foreach ($fuentes as $archivo) { $hashes[$archivo] = hash_file('sha256', $vendor.'/'.$archivo); }
echo 'PASS base_modelos_satis: '.count($ejecutados).' casos, '.$aserciones." assertions.\n";
echo json_encode(['php' => PHP_VERSION, 'sha256' => $hashes], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
