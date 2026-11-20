<?php
declare(strict_types=1);

use gamboamartin\errores\errores;
use gamboamartin\src\sql;
use gamboamartin\where\where;

set_error_handler(static function (int $nivel, string $mensaje, string $archivo, int $linea): never {
    throw new ErrorException($mensaje, 0, $nivel, $archivo, $linea);
});
if ($argc !== 2 || !is_dir($argv[1])) { throw new RuntimeException('Se requiere vendor de una copia QA.'); }
$vendor = realpath($argv[1]);
$fuentes = [
    'gamboamartin\\errores\\errores' => 'gamboa.martin/errores/src/errores.php',
    'gamboamartin\\src\\sql' => 'gamboa.martin/sql/src/sql.php',
    'gamboamartin\\src\\validaciones' => 'gamboa.martin/validaciones/src/validaciones.php',
    'gamboamartin\\validacion\\validacion' => 'gamboa.martin/validacion/src/validacion.php',
    'gamboamartin\\validacion\\_codigos' => 'gamboa.martin/validacion/src/_codigos.php',
    'gamboamartin\\where\\where' => 'gamboa.martin/where/src/where.php',
    'base\\orm\\sql' => 'gamboa.martin/administrador/base/orm/sql.php',
    'base\\orm\\where' => 'gamboa.martin/administrador/base/orm/where.php',
];
require $vendor.'/composer/ClassLoader.php';
// Se lee el mapa generado real, no vendor/autoload.php (que puede ejecutar archivos).
$mapa = require $vendor.'/composer/autoload_psr4.php';
$cargador = new \Composer\Autoload\ClassLoader();
foreach (['gamboamartin\\src\\', 'gamboamartin\\where\\', 'gamboamartin\\validacion\\',
    'gamboamartin\\errores\\', 'base\\'] as $prefijo) {
    if (!isset($mapa[$prefijo])) { throw new RuntimeException('Falta el namespace del consumidor: '.$prefijo); }
    $cargador->addPsr4($prefijo, $mapa[$prefijo]);
}
spl_autoload_register(static function (string $clase) use ($cargador, $fuentes, $vendor): void {
    if (!isset($fuentes[$clase])) {
        throw new RuntimeException('La prueba prohíbe cargar esta clase/configuración: '.$clase);
    }
    $encontrado = $cargador->findFile($clase);
    if ($encontrado === false || realpath($encontrado) !== realpath($vendor.'/'.$fuentes[$clase])) {
        throw new RuntimeException('Composer resolvió una fuente distinta de la esperada: '.$clase);
    }
    require $encontrado;
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
function error_esperado(mixed $respuesta, ?string $mensaje = null): void {
    exigir(is_array($respuesta) && ($respuesta['error'] ?? null) === 1, 'retorna el contrato real de errores');
    exigir(errores::$error, 'activa el indicador global');
    exigir(count($_SESSION['error_resultado'] ?? []) > 0 && count(errores::$out) > 0, 'registra la causa en memoria');
    if ($mensaje !== null) { exigir($respuesta['mensaje_limpio'] === $mensaje, 'mensaje exacto de la operación'); }
}
function exito(string|array $respuesta, string $esperado): void {
    exigir($respuesta === $esperado, 'fragmento SQL completo esperado');
    exigir(!errores::$error, 'la construcción válida no genera errores');
}
function limpiar(string $texto): string {
    return (new ReflectionMethod(sql::class, 'limpia_espacios_dobles'))->invoke(new sql(), $texto);
}

caso('sql_in_preserva_lista_formateada_y_alias', static function (): void {
    exito((new sql())->in('f.id', "'10','20','30'"), "f.id IN ('10','20','30')");
});
caso('sql_in_ambos_vacios_omite_filtro', static function (): void {
    exito((new sql())->in('', ''), '');
});
caso('sql_coherencia_ambos_presentes_y_ambos_vacios', static function (): void {
    $sql = new sql();
    exigir($sql->valida_in('f.id', '1,2') === true, 'acepta llave y valores presentes');
    exigir($sql->valida_in('', '') === true, 'acepta ambos vacíos');
    exigir($sql->valida_in(' ', "\t") === true, 'valida coherencia después de trim');
    exigir(!errores::$error, 'combinaciones coherentes no generan error');
});
caso('sql_coherencia_llave_sin_valores_bloquea', static function (): void {
    $r = (new sql())->valida_in(' f.id ', " \t");
    error_esperado($r, 'Error: si la llave tiene contenido, $values_sql no puede estar vacío');
    exigir($r['data'] === 'f.id', 'causa contiene la llave recortada');
});
caso('sql_coherencia_valores_sin_llave_bloquea', static function (): void {
    $r = (new sql())->valida_in(" \t", ' 1,2 ');
    error_esperado($r, 'Error: si $values_sql tiene contenido, la llave no puede estar vacía');
    exigir($r['data'] === '1,2', 'causa contiene los valores recortados');
});
caso('sql_in_propaga_error_de_coherencia', static function (): void {
    $r = (new sql())->in('f.id', '');
    error_esperado($r, 'Error al validar in');
    exigir($r['data']['error'] === 1 && $r['data']['data'] === 'f.id', 'preserva el error original anidado');
    exigir(count($_SESSION['error_resultado']) === 2, 'registra causa y envoltura');
});
caso('sql_in_estado_error_previo_no_se_oculta', static function (): void {
    errores::$error = true;
    error_esperado((new sql())->in('f.id', '1'), 'Error al validar in');
});
caso('sql_in_normaliza_espacios_estructurales', static function (): void {
    exito((new sql())->in('f.id    ', '1,   2'), 'f.id IN (1, 2)');
});
caso('sql_limpieza_default_reduce_espacios_e_idempotente', static function (): void {
    $salida = limpiar('SELECT     id       FROM    ejemplo');
    exigir($salida === 'SELECT id FROM ejemplo', 'limpia separadores repetidos');
    exigir(limpiar($salida) === $salida, 'limpieza idempotente para texto normalizado');
    exigir(limpiar('     ') === ' ', 'conserva un separador, sin trim implícito');
});
caso('sql_limpieza_preserva_tabs_saltos_y_unicode', static function (): void {
    $texto = "SELECT\t'Árbol'\nFROM tabla";
    exigir(limpiar($texto) === $texto, 'no modifica caracteres que no son espacios dobles');
});
caso('sql_in_preserva_literals_ya_formateados', static function (): void {
    $valores = "'Árbol','O\\'Neil','A&B'";
    exito((new sql())->in('f.nombre', $valores), "f.nombre IN ('Árbol','O\\'Neil','A&B')");
});
caso('where_real_genera_in_ids_y_cero', static function (): void {
    exito((new where())->genera_in(['llave' => ' f.id ', 'values' => [0, 10, 20]]),
        "f.id IN ('0' ,'10' ,'20')");
});
caso('where_real_transforma_cadenas_y_comillas', static function (): void {
    exito((new where())->genera_in(['llave' => 'f.nombre', 'values' => [" O'Neil ", 'Ruta\\Archivo']]),
        "f.nombre IN ('O\\'Neil' ,'Ruta\\\\Archivo')");
});
caso('where_real_rechaza_lista_vacia', static function (): void {
    error_esperado((new where())->genera_in(['llave' => 'f.id', 'values' => []]));
});
caso('where_real_rechaza_clave_ausente', static function (): void {
    error_esperado((new where())->genera_in(['llave' => 'f.id']));
});
caso('where_real_rechaza_values_no_array', static function (): void {
    error_esperado((new where())->genera_in(['llave' => 'f.id', 'values' => '10,20']));
});
caso('administrador_real_in_sql_pasa_por_where_y_sql', static function (): void {
    exito((new \base\orm\where())->in_sql(' f.id ', [0, 10, 20]), "f.id IN ('0' ,'10' ,'20')");
});
caso('administrador_real_in_sql_preserva_cadenas', static function (): void {
    exito((new \base\orm\where())->in_sql('f.nombre', ["O'Neil", 'Árbol']),
        "f.nombre IN ('O\\'Neil' ,'Árbol')");
});
caso('administrador_real_rechaza_valores_vacios', static function (): void {
    error_esperado((new \base\orm\where())->in_sql('f.id', []), 'Error al validar in');
});
caso('autoload_namespace_compartido_resuelve_fuentes_distintas', static function () use ($vendor, $fuentes, $cargador): void {
    $validaciones = new \gamboamartin\src\validaciones();
    exigir($validaciones instanceof \gamboamartin\validacion\validacion, 'clase del namespace compartido conserva herencia');
    foreach ($fuentes as $clase => $relativa) {
        exigir(class_exists($clase), 'carga todas las clases reales del flujo');
        exigir(realpath((new ReflectionClass($clase))->getFileName()) === realpath($vendor.'/'.$relativa),
            'namespace resuelve el paquete correcto');
        exigir(realpath($cargador->findFile($clase)) === realpath($vendor.'/'.$relativa), 'mapa PSR-4 real sin colisiones');
    }
    exigir(sql::class !== \base\orm\sql::class, 'SQL de paquete y SQL de administrador son clases diferentes');
});
caso('aislamiento_sin_config_ni_sesion_php', static function () use ($fuentes, $vendor): void {
    exigir(!class_exists('config\\database', false) && !class_exists('config\\generales', false), 'no se cargó configuración');
    exigir(!class_exists('gamboamartin\\test\\test', false), 'no se cargó la base que abre PDO/mysqli');
    exigir(session_status() === PHP_SESSION_NONE, 'no se abrió sesión PHP');
    $permitidos = [realpath(__FILE__), realpath($vendor.'/composer/ClassLoader.php'), realpath($vendor.'/composer/autoload_psr4.php')];
    foreach ($fuentes as $relativa) { $permitidos[] = realpath($vendor.'/'.$relativa); }
    exigir(array_diff(array_map('realpath', get_included_files()), $permitidos) === [], 'solo se incluyeron las fuentes declaradas');
});

$registro = json_decode(file_get_contents(__DIR__.'/registro.json'), true, 512, JSON_THROW_ON_ERROR);
exigir($ejecutados === $registro['casos'], 'nombres y orden exactos');
exigir(count($ejecutados) === $registro['casos_previstos'], 'conteo registrado completo');
$hashes = [];
foreach ($fuentes as $relativa) { $hashes[$relativa] = hash_file('sha256', $vendor.'/'.$relativa); }
echo 'PASS sql_satis: '.count($ejecutados).' casos, '.$aserciones." assertions.\n";
echo json_encode(['php' => PHP_VERSION, 'sha256' => $hashes], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
