<?php
declare(strict_types=1);

use gamboamartin\errores\errores;
use gamboamartin\src\validaciones;
use gamboamartin\where\where;

set_error_handler(static function (int $nivel, string $mensaje, string $archivo, int $linea): never {
    throw new ErrorException($mensaje, 0, $nivel, $archivo, $linea);
});
if ($argc !== 2 || !is_dir($argv[1])) { throw new RuntimeException('Se requiere vendor de una copia QA.'); }
$vendor = realpath($argv[1]);
$fuentes = [
    'gamboamartin\\errores\\errores' => 'gamboa.martin/errores/src/errores.php',
    'gamboamartin\\validacion\\validacion' => 'gamboa.martin/validacion/src/validacion.php',
    'gamboamartin\\validacion\\_codigos' => 'gamboa.martin/validacion/src/_codigos.php',
    'gamboamartin\\src\\validaciones' => 'gamboa.martin/validaciones/src/validaciones.php',
    'gamboamartin\\src\\sql' => 'gamboa.martin/sql/src/sql.php',
    'gamboamartin\\where\\where' => 'gamboa.martin/where/src/where.php',
    'base\\orm\\where' => 'gamboa.martin/administrador/base/orm/where.php',
    'gamboamartin\\administrador\\modelado\\validaciones' => 'gamboa.martin/administrador/modelado/validaciones.php',
];
require $vendor.'/composer/ClassLoader.php';
$mapa = require $vendor.'/composer/autoload_psr4.php';
$cargador = new \Composer\Autoload\ClassLoader();
foreach (['gamboamartin\\src\\', 'gamboamartin\\where\\', 'gamboamartin\\validacion\\',
    'gamboamartin\\errores\\', 'base\\', 'gamboamartin\\administrador\\modelado\\'] as $prefijo) {
    if (!isset($mapa[$prefijo])) { throw new RuntimeException('Falta un namespace requerido: '.$prefijo); }
    $cargador->addPsr4($prefijo, $mapa[$prefijo]);
}
spl_autoload_register(static function (string $clase) use ($cargador, $fuentes, $vendor): void {
    if (!isset($fuentes[$clase])) {
        throw new RuntimeException('La prueba prohíbe cargar esta clase/configuración: '.$clase);
    }
    $archivo = $cargador->findFile($clase);
    if ($archivo === false || realpath($archivo) !== realpath($vendor.'/'.$fuentes[$clase])) {
        throw new RuntimeException('Composer resolvió una fuente distinta de la esperada: '.$clase);
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
    exigir($respuesta === true, 'retorna true para la entrada válida');
    exigir(!errores::$error, 'la validación exitosa no genera error');
}
function error_esperado(mixed $respuesta, string $mensaje): void {
    exigir(is_array($respuesta) && ($respuesta['error'] ?? null) === 1, 'contrato real de errores');
    exigir($respuesta['mensaje_limpio'] === $mensaje, 'mensaje exacto de la operación');
    exigir(errores::$error, 'estado global activo');
    exigir(count($_SESSION['error_resultado'] ?? []) > 0 && count(errores::$out) > 0, 'error registrado solo en memoria');
}
function causa(array $respuesta): array {
    while (isset($respuesta['data']) && is_array($respuesta['data']) && ($respuesta['data']['error'] ?? null) === 1) {
        $respuesta = $respuesta['data'];
    }
    return $respuesta;
}
function sql_esperado(mixed $respuesta, string $sql): void {
    exigir($respuesta === $sql, 'fragmento completo esperado');
    exigir(!errores::$error, 'el consumidor no generó error');
}

caso('autoload_validaciones_antes_sql_y_herencia_real', static function () use ($vendor): void {
    exigir(!class_exists(\gamboamartin\src\sql::class, false), 'SQL todavía no está cargado');
    $validador = new validaciones();
    exigir($validador instanceof \gamboamartin\validacion\validacion, 'hereda de validacion singular real');
    exigir((new ReflectionMethod($validador, 'valida_existencia_keys'))->getDeclaringClass()->getName()
        === \gamboamartin\validacion\validacion::class, 'método heredado proviene de la clase singular');
    $sql = new \gamboamartin\src\sql();
    sql_esperado($sql->in('t.id', '1'), 't.id IN (1)');
    exigir(realpath((new ReflectionClass(validaciones::class))->getFileName())
        === realpath($vendor.'/gamboa.martin/validaciones/src/validaciones.php'), 'paquete plural resuelve después junto a SQL');
});
caso('filtro_numerico_cero_valido_no_muta_entrada', static function (): void {
    $filtro = ['t.total' => ['operador' => '>=', 'valor' => 0]];
    $antes = $filtro;
    exito((new validaciones())->valida_data_filtro_especial('t.total', $filtro));
    exigir($filtro === $antes, 'conserva exactamente el argumento original');
});
caso('filtro_texto_unicode_valido', static function (): void {
    exito((new validaciones())->valida_data_filtro_especial('t.nombre',
        ['t.nombre' => ['operador' => '=', 'valor' => 'Árbol']]));
});
caso('campo_numerico_con_indicador_explicito_permite', static function (): void {
    exito((new validaciones())->valida_data_filtro_especial('7',
        [7 => ['valor_es_campo' => true, 'operador' => '=', 'valor' => 't.id']]));
});
caso('campo_vacio_rechaza', static function (): void {
    error_esperado((new validaciones())->valida_data_filtro_especial('', []), 'Error campo vacio');
});
caso('campo_numerico_sin_indicador_rechaza', static function (): void {
    error_esperado((new validaciones())->valida_data_filtro_especial('7', [7 => ['operador' => '=', 'valor' => 't.id']]),
        'Error el campo debe ser un string $filtro[campo]');
});
caso('campo_ausente_en_filtro_rechaza', static function (): void {
    error_esperado((new validaciones())->valida_data_filtro_especial('t.id',
        ['otro' => ['operador' => '=', 'valor' => 1]]), 'Error debe existir $filtro[campo][operador]');
});
caso('operador_ausente_rechaza', static function (): void {
    error_esperado((new validaciones())->valida_data_filtro_especial('t.id', ['t.id' => ['valor' => 1]]),
        'Error debe existir $filtro[campo][operador]');
});
caso('operador_null_rechaza', static function (): void {
    error_esperado((new validaciones())->valida_data_filtro_especial('t.id',
        ['t.id' => ['operador' => null, 'valor' => 1]]), 'Error debe existir $filtro[campo][operador]');
});
caso('valor_array_rechaza_y_conserva_causa', static function (): void {
    $filtro = ['t.id' => ['operador' => '=', 'valor' => [1, 2]]];
    $r = (new validaciones())->valida_data_filtro_especial('t.id', $filtro);
    error_esperado($r, "Error \$filtro['t.id']['valor'] debe ser un dato");
    exigir($r['data'] === $filtro, 'conserva el filtro rechazado');
});
caso('valor_array_vacio_tambien_rechaza', static function (): void {
    error_esperado((new validaciones())->valida_data_filtro_especial('t.id',
        ['t.id' => ['operador' => '=', 'valor' => []]]), "Error \$filtro['t.id']['valor'] debe ser un dato");
});
caso('valor_omitido_no_se_inserta_en_argumento', static function (): void {
    $filtro = ['t.id' => ['operador' => '=']];
    exito((new validaciones())->valida_data_filtro_especial('t.id', $filtro));
    exigir(!array_key_exists('valor', $filtro['t.id']), 'el parámetro se recibe por valor; no hay normalización externa');
});
caso('valor_null_no_muta_argumento', static function (): void {
    $filtro = ['t.id' => ['operador' => '=', 'valor' => null]];
    exito((new validaciones())->valida_data_filtro_especial('t.id', $filtro));
    exigir(array_key_exists('valor', $filtro['t.id']) && $filtro['t.id']['valor'] === null, 'conserva null externo');
});
caso('validador_no_borra_estado_error_previo', static function (): void {
    $validador = new validaciones();
    errores::$error = true;
    exigir($validador->valida_data_filtro_especial('t.id', ['t.id' => ['operador' => '=', 'valor' => 1]]) === true,
        'la estructura coherente devuelve true');
    exigir(errores::$error, 'el consumidor todavía puede detectar el error previo');
});
caso('metodo_heredado_valida_claves_con_cero', static function (): void {
    exito((new validaciones())->valida_existencia_keys(['valor'], ['valor' => 0]));
});
caso('metodo_heredado_propaga_clave_ausente', static function (): void {
    error_esperado((new validaciones())->valida_existencia_keys(['valor'], []),
        'Error valor no existe en el registro');
});
caso('where_real_filtro_especial_cero', static function (): void {
    sql_esperado((new where())->filtro_especial_sql([], [
        ['t.total' => ['operador' => '>=', 'valor' => 0]],
    ]), " t.total >= '0' ");
});
caso('where_real_alias_expresion', static function (): void {
    sql_esperado((new where())->filtro_especial_sql(['total' => 'SUM(t.total)'], [
        ['total' => ['operador' => '>', 'valor' => 10]],
    ]), " SUM(t.total) > '10' ");
});
caso('where_real_combina_filtros_and', static function (): void {
    sql_esperado((new where())->filtro_especial_sql([], [
        ['t.total' => ['operador' => '>=', 'valor' => 0]],
        ['t.status' => ['operador' => '=', 'valor' => 'activo', 'comparacion' => 'AND']],
    ]), " t.total >= '0'  AND  t.status = 'activo' ");
});
caso('where_real_propaga_error_del_paquete_plural', static function (): void {
    $r = (new where())->filtro_especial_sql([], [['t.id' => ['valor' => 1]]]);
    error_esperado($r, 'Error filtro');
    exigir(causa($r)['mensaje_limpio'] === 'Error debe existir $filtro[campo][operador]', 'mantiene causa original anidada');
    exigir(str_contains($_SESSION['error_resultado'][0]['class'], validaciones::class), 'el error proviene del paquete plural real');
});
caso('where_real_rechaza_array_sin_fragmento_parcial', static function (): void {
    $r = (new where())->filtro_especial_sql([], [
        ['t.status' => ['operador' => '=', 'valor' => 'activo']],
        ['t.id' => ['operador' => '=', 'valor' => [1, 2], 'comparacion' => 'AND']],
    ]);
    error_esperado($r, 'Error filtro');
    exigir(causa($r)['mensaje_limpio'] === "Error \$filtro['t.id']['valor'] debe ser un dato", 'conserva causa específica del segundo filtro');
});
caso('where_real_valor_omitido_se_rechaza_en_consumidor', static function (): void {
    $r = (new where())->filtro_especial_sql([], [['t.id' => ['operador' => '=']]]);
    error_esperado($r, 'Error filtro');
    exigir(causa($r)['mensaje_limpio'] === 'Error valor no existe en el registro', 'el consumidor exige valor explícito');
});
caso('administrador_real_filtro_especial_cero', static function (): void {
    sql_esperado((new \base\orm\where())->filtro_especial_sql([], [
        ['t.total' => ['operador' => '>=', 'valor' => 0]],
    ]), " t.total >= '0' ");
});
caso('administrador_real_propaga_su_validador_distinto', static function (): void {
    $r = (new \base\orm\where())->filtro_especial_sql([], [['t.id' => ['valor' => 1]]]);
    error_esperado($r, 'Error filtro');
    exigir(causa($r)['mensaje_limpio'] === 'Error debe existir $filtro[campo][operador]', 'mantiene la causa compatible');
    exigir(str_contains($_SESSION['error_resultado'][0]['class'],
        \gamboamartin\administrador\modelado\validaciones::class), 'administrador usa su clase propia, no se atribuye al paquete plural');
});
caso('administrador_real_valor_omitido_rechaza', static function (): void {
    $r = (new \base\orm\where())->filtro_especial_sql([], [['t.id' => ['operador' => '=']]]);
    error_esperado($r, 'Error filtro');
    exigir(causa($r)['mensaje_limpio'] === 'Error valor no existe en el registro', 'misma exigencia explícita de valor');
});
caso('fuentes_reales_y_aislamiento_sin_config', static function () use ($vendor, $fuentes): void {
    foreach ($fuentes as $clase => $archivo) {
        exigir(class_exists($clase), 'carga controlada de las fuentes reales');
        exigir(realpath((new ReflectionClass($clase))->getFileName()) === realpath($vendor.'/'.$archivo), 'fuente correcta para cada clase');
    }
    exigir(!class_exists('config\\database', false) && !class_exists('config\\generales', false), 'sin configuración privada');
    exigir(!class_exists('gamboamartin\\test\\test', false), 'sin base test que abre PDO/mysqli');
    exigir(session_status() === PHP_SESSION_NONE, 'sin sesión PHP');
    $permitidos = [realpath(__FILE__), realpath($vendor.'/composer/ClassLoader.php'), realpath($vendor.'/composer/autoload_psr4.php')];
    foreach ($fuentes as $archivo) { $permitidos[] = realpath($vendor.'/'.$archivo); }
    exigir(array_diff(array_map('realpath', get_included_files()), $permitidos) === [], 'solo archivos declarados');
});

$registro = json_decode(file_get_contents(__DIR__.'/registro.json'), true, 512, JSON_THROW_ON_ERROR);
exigir($ejecutados === $registro['casos'], 'identidad y orden exactos de casos');
exigir(count($ejecutados) === $registro['casos_previstos'], 'conteo registrado completo');
$hashes = [];
foreach ($fuentes as $archivo) { $hashes[$archivo] = hash_file('sha256', $vendor.'/'.$archivo); }
echo 'PASS validaciones_satis: '.count($ejecutados).' casos, '.$aserciones." assertions.\n";
echo json_encode(['php' => PHP_VERSION, 'sha256' => $hashes], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
