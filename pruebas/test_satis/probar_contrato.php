<?php
declare(strict_types=1);

use gamboamartin\errores\errores;
use gamboamartin\test\liberator;
use gamboamartin\test\test;

set_error_handler(static function (int $nivel, string $mensaje, string $archivo, int $linea): never {
    throw new ErrorException($mensaje, 0, $nivel, $archivo, $linea);
});
if ($argc !== 4) {
    throw new RuntimeException('Se requieren paquete, fuente errores y autoload PHPUnit aislado.');
}
[$paquete, $fuenteErrores, $autoload] = array_slice($argv, 1);
// Cualquier intento accidental de resolver una configuración falla antes de cargarla.
spl_autoload_register(static function (string $clase): void {
    if (str_starts_with(strtolower($clase), 'config\\')) {
        throw new RuntimeException('La prueba prohíbe cargar configuración activa.');
    }
});
require $autoload;
require $fuenteErrores;
require $paquete.'/src/liberator.php';
require $paquete.'/src/test.php';

class PadreSintetico {
    protected string $protegida = 'PROTEGIDA';
    protected function saludar(string $nombre): string { return 'Hola '.$nombre; }
}
final class ObjetoSintetico extends PadreSintetico {
    private int $privada = 7;
    private string $sinInicializar;
    private function operar(int $base, int $factor = 2): int { return ($base + $this->privada) * $factor; }
    private function falla(): never { throw new DomainException('EXCEPCION_SINTETICA'); }
    public function estado(): array { return [$this->privada, $this->protegida]; }
}

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
function error_esperado(mixed $respuesta, string $mensaje, string $tipo): void {
    exigir(is_array($respuesta) && $respuesta['error'] === 1, 'retorna el contrato real de errores');
    exigir($respuesta['mensaje_limpio'] === $mensaje, 'conserva mensaje de la operación');
    exigir($respuesta['data'] instanceof $tipo, 'conserva la excepción original');
    exigir(errores::$error, 'activa el estado global de errores');
    exigir(count($_SESSION['error_resultado']) === 1 && count(errores::$out) === 1, 'registra una sola respuesta en memoria');
}
function base_sin_constructor(): test {
    $reflexion = new ReflectionClass(test::class);
    $base = $reflexion->newInstanceWithoutConstructor();
    exigir($base instanceof \PHPUnit\Framework\TestCase, 'usa la clase base PHPUnit real');
    exigir(!$reflexion->getProperty('link')->isInitialized($base), 'PDO/mysqli no se inicializó');
    return $base;
}

caso('liberator_lee_privada_y_protegida_heredada', static function (): void {
    $objeto = new ObjetoSintetico();
    $libre = new liberator($objeto);
    exigir($libre->privada === 7, 'lee propiedad privada');
    exigir($libre->protegida === 'PROTEGIDA', 'lee protegida heredada');
    exigir(!errores::$error, 'éxito no activa error');
});
caso('liberator_modifica_objeto_original', static function (): void {
    $objeto = new ObjetoSintetico();
    $libre = new liberator($objeto);
    $libre->privada = 11;
    $libre->protegida = 'NUEVA';
    exigir($objeto->estado() === [11, 'NUEVA'], 'escritura afecta la instancia original');
    exigir($libre->privada === 11 && $libre->protegida === 'NUEVA', 'lectura devuelve los cambios');
    exigir(!errores::$error, 'no genera errores');
});
caso('liberator_invoca_privado_con_argumentos', static function (): void {
    $libre = new liberator(new ObjetoSintetico());
    exigir($libre->operar(3) === 20, 'respeta el argumento predeterminado');
    exigir($libre->operar(3, 4) === 40, 'transmite argumentos posicionales');
    exigir($libre->operar(factor: 3, base: 4) === 33, 'transmite argumentos nombrados');
});
caso('liberator_invoca_protegido_heredado', static function (): void {
    $libre = new liberator(new ObjetoSintetico());
    exigir($libre->saludar('PRUEBA') === 'Hola PRUEBA', 'resuelve método heredado');
    exigir(!errores::$error, 'invocación válida sin error');
});
caso('liberator_aisla_instancias', static function (): void {
    $primero = new liberator(new ObjetoSintetico());
    $segundo = new liberator(new ObjetoSintetico());
    $primero->privada = 99;
    exigir($primero->privada === 99 && $segundo->privada === 7, 'no comparte valores entre objetos');
});
caso('liberator_lectura_inexistente_retorna_error', static function (): void {
    $libre = new liberator(new ObjetoSintetico());
    error_esperado($libre->inexistente, 'Error al obtener datos', ReflectionException::class);
});
caso('liberator_lectura_no_inicializada_retorna_error', static function (): void {
    $libre = new liberator(new ObjetoSintetico());
    error_esperado($libre->sinInicializar, 'Error al obtener datos', Error::class);
});
caso('liberator_escritura_inexistente_no_crea_propiedad', static function (): void {
    $objeto = new ObjetoSintetico();
    $libre = new liberator($objeto);
    error_esperado($libre->__set('inexistente', 9), 'Error al setear datos', ReflectionException::class);
    exigir(!property_exists($objeto, 'inexistente'), 'no añade una propiedad dinámica');
});
caso('liberator_tipo_invalido_conserva_valor', static function (): void {
    $objeto = new ObjetoSintetico();
    $libre = new liberator($objeto);
    error_esperado($libre->__set('privada', []), 'Error al setear datos', TypeError::class);
    exigir($objeto->estado() === [7, 'PROTEGIDA'], 'fallo de tipo conserva el objeto');
});
caso('liberator_metodo_inexistente_retorna_error', static function (): void {
    $libre = new liberator(new ObjetoSintetico());
    error_esperado($libre->inexistente(), 'Error al llamar datos', ReflectionException::class);
});
caso('liberator_excepcion_original_se_conserva', static function (): void {
    $libre = new liberator(new ObjetoSintetico());
    $respuesta = $libre->falla();
    error_esperado($respuesta, 'Error al llamar datos', DomainException::class);
    exigir($respuesta['data']->getMessage() === 'EXCEPCION_SINTETICA', 'no reemplaza la causa');
});
caso('base_registro_predeterminado_activo', static function (): void {
    $base = base_sin_constructor();
    exigir($base->registro('CODIGO', 'DESCRIPCION', 12, true) === [
        'predeterminado' => 'activo', 'id' => 12, 'descripcion' => 'DESCRIPCION', 'codigo' => 'CODIGO',
    ], 'registro activo exacto');
    exigir(!errores::$error, 'generación válida sin error');
});
caso('base_registro_sin_predeterminado', static function (): void {
    $base = base_sin_constructor();
    exigir($base->registro('CODIGO', 'DESCRIPCION', 12, false) === [
        'id' => 12, 'descripcion' => 'DESCRIPCION', 'codigo' => 'CODIGO',
    ], 'omite predeterminado cuando no se solicita');
});
caso('base_privado_row_base_conserva_campos_extra', static function (): void {
    $libre = new liberator(base_sin_constructor());
    exigir($libre->row_base(codigo: 'NUEVO', descripcion: 'NUEVA', id: 8,
        registro: ['extra' => 'CONSERVADO', 'id' => 2]) === [
            'extra' => 'CONSERVADO', 'id' => 8, 'descripcion' => 'NUEVA', 'codigo' => 'NUEVO',
        ], 'integración conserva campos extra y sustituye campos base');
});
caso('base_estado_error_previo_se_propaga', static function (): void {
    $base = base_sin_constructor();
    errores::$error = true;
    $respuesta = $base->registro('CODIGO', 'DESCRIPCION', 12, true);
    exigir($respuesta['error'] === 1 && $respuesta['mensaje_limpio'] === 'Error a integrar row', 'no oculta error previo');
    exigir($respuesta['data'] === ['predeterminado' => 'activo'], 'conserva el resultado intermedio');
});
caso('aislamiento_sin_config_sesion_ni_constructor_bd', static function (): void {
    exigir(!class_exists('config\\database', false) && !class_exists('config\\generales', false), 'ninguna configuración cargada');
    exigir(!defined('UT'), 'no se ejecutó el constructor de test');
    exigir(session_status() === PHP_SESSION_NONE, 'no se abrió una sesión PHP');
    foreach (get_included_files() as $incluido) {
        exigir(!preg_match('~/config/(database|generales)\.php$~', $incluido), 'sin archivos privados en includes');
    }
});

$registro = json_decode(file_get_contents(__DIR__.'/registro.json'), true, 512, JSON_THROW_ON_ERROR);
exigir($ejecutados === $registro['casos'], 'identidad y orden exacto de casos');
exigir(count($ejecutados) === $registro['casos_previstos'], 'conteo registrado completo');
echo 'PASS test_satis: '.count($ejecutados).' casos, '.$aserciones." assertions.\n";
echo json_encode([
    'php' => PHP_VERSION,
    'phpunit' => \PHPUnit\Runner\Version::id(),
    'sha256_liberator' => hash_file('sha256', $paquete.'/src/liberator.php'),
    'sha256_test' => hash_file('sha256', $paquete.'/src/test.php'),
    'sha256_errores' => hash_file('sha256', $fuenteErrores),
], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
