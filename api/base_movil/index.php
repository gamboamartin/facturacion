<?php
namespace base_movil\v1;

use base\conexion;
use base_movil\v1\src\app_config;
use base_movil\v1\src\app_screens;
use base_movil\v1\src\app_sesion;
use Throwable;

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

/**
 * Única salida de error del API: código HTTP real + cuerpo {error, mensaje}.
 * Prototipo #4: el cliente decide qué mostrar según el código.
 */
function responde_error(int $codigo, string $mensaje): void
{
    http_response_code($codigo);
    echo json_encode(array('error' => 1, 'mensaje' => $mensaje), JSON_UNESCAPED_UNICODE);
    exit;
}

chdir(__DIR__ . '/../..');
require 'init.php';

// init.php (compartido) enciende display_errors. En el API un warning
// impreso rompe el JSON y puede mostrar rutas del servidor: aquí los
// errores van solo al log.
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require 'vendor/autoload.php';
require_once __DIR__ . '/src/app_config.php';
require_once __DIR__ . '/src/app_screens.php';
require_once __DIR__ . '/src/app_sesion.php';

if (!isset($_GET['method']) || !is_string($_GET['method']) || trim($_GET['method']) === '') {
    responde_error(400, 'Error $_GET[method] debe existir.');
}

$method = trim($_GET['method']);

$rutas = array(
    'obten_pantalla' => app_screens::class,
    'guarda_registro' => app_screens::class,
    'login' => app_sesion::class,
    'logout' => app_sesion::class,
    'pantallas_offline' => app_screens::class,
    'configuracion_app' => app_config::class,
);

if (!isset($rutas[$method])) {
    responde_error(404, 'Error metodo no habilitado en el router.');
}

try {
    $con = new conexion();
    $link = conexion::$link;

    $clase = $rutas[$method];
    $app = new $clase($link);

    if (!method_exists($app, $method)) {
        responde_error(500, 'Error el metodo no existe en la clase.');
    }

    $resultado = $app->$method();
} catch (Throwable $e) {
    // El detalle de la excepción nunca sale al cliente.
    responde_error(500, 'Error interno del servidor.');
}

if (isset($resultado['error'])) {
    $codigo = (int)($resultado['http_code'] ?? 500);
    $mensaje = $resultado['mensaje_limpio'] ?? 'Error al procesar la solicitud';
    responde_error($codigo, $mensaje);
}

echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
