<?php
namespace base_movil\v1;

use base\conexion;
use base_movil\v1\src\app_screens;
use base_movil\v1\src\app_sesion;

/**
 * Enrutador por ?method=X, igual que index.php de em3/cobranza:
 *   - solo se atienden los métodos declarados en $rutas (lista blanca)
 *   - cada método apunta a la clase que lo resuelve
 *   - las clases devuelven arrays; aquí se decide el JSON de salida
 *
 * Para la respuesta JSON se usa 'mensaje_limpio' (texto plano) del
 * error de gamboamartin\errores\errores, no 'mensaje' (trae HTML).
 */

header('Content-Type: application/json; charset=utf-8');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Raíz del proyecto de facturación: ahí viven init.php y vendor/.
chdir(__DIR__ . '/../..');
require 'init.php';
require 'vendor/autoload.php';
require_once __DIR__ . '/src/app_screens.php';
require_once __DIR__ . '/src/app_sesion.php';

if (!isset($_GET['method']) || !is_string($_GET['method']) || trim($_GET['method']) === '') {
    echo json_encode(array('error' => 1, 'mensaje' => 'Error $_GET[method] debe existir.'), JSON_UNESCAPED_UNICODE);
    exit;
}

$method = trim($_GET['method']);

// Métodos habilitados: método => clase que lo atiende.
$rutas = array(
    'obten_pantalla' => app_screens::class,
    'guarda_registro' => app_screens::class,
    'login' => app_sesion::class,
    'logout' => app_sesion::class,
);

if (!isset($rutas[$method])) {
    echo json_encode(array('error' => 1, 'mensaje' => 'Error metodo no habilitado en el router.'), JSON_UNESCAPED_UNICODE);
    exit;
}

$con = new conexion();
$link = conexion::$link;

$clase = $rutas[$method];
$app = new $clase($link);

if (!method_exists($app, $method)) {
    echo json_encode(array('error' => 1, 'mensaje' => 'Error el metodo no existe en la clase.'), JSON_UNESCAPED_UNICODE);
    exit;
}

$resultado = $app->$method();

if (isset($resultado['error'])) {
    $mensaje = $resultado['mensaje_limpio'] ?? 'Error al procesar la solicitud';
    echo json_encode(array('error' => 1, 'mensaje' => $mensaje), JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode($resultado, JSON_UNESCAPED_UNICODE);