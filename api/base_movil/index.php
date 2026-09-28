<?php
namespace base_movil\v1;

use base_movil\v1\src\app_screens;

/**
 * Enrutador por ?method=X, igual que index.php de em3/cobranza:
 *   - valida method_exists antes de llamar
 *   - la clase devuelve arrays, aquí se decide el JSON de salida
 *
 * El array de error que devuelve gamboamartin\errores\errores trae
 * 'mensaje' con HTML (<b><span style="color:red">...) pensado para
 * pantallas web — para la respuesta JSON de la app se usa
 * 'mensaje_limpio' (texto plano), igual que em3 hace strip_tags()
 * sobre el mensaje antes de mandarlo a la app móvil.
 *
 */

header('Content-Type: application/json; charset=utf-8');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
require_once '../../vendor/autoload.php';
require_once __DIR__ . '/src/app_screens.php';

if (!isset($_GET['method']) || !is_string($_GET['method']) || trim($_GET['method']) === '') {
    echo json_encode(array('error' => 1, 'mensaje' => 'Error $_GET[method] debe existir.'), JSON_UNESCAPED_UNICODE);
    exit;
}

$method = trim($_GET['method']);
$app = new app_screens();

$metodos_habilitados = array('obten_pantalla', 'guarda_registro');


if (!in_array($method, $metodos_habilitados, true) || !method_exists($app, $method)) {
    echo json_encode(array('error' => 1, 'mensaje' => 'Error metodo no habilitado en el router.'), JSON_UNESCAPED_UNICODE);
    exit;
}

$resultado = $app->$method();

if (isset($resultado['error'])) {
    $mensaje = $resultado['mensaje_limpio'] ?? 'Error al procesar la solicitud';
    echo json_encode(array('error' => 1, 'mensaje' => $mensaje), JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
