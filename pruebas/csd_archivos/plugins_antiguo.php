<?php
declare(strict_types=1);
namespace base\orm { class _modelo_parent { public object $error; } }
namespace gamboamartin\plugins { class ssl {} }
namespace {
    require getenv('FUENTE_ERRORES');
    require dirname(__DIR__, 2) . '/orm/fc_csd.php';
    $modelo = (new ReflectionClass(\gamboamartin\facturacion\models\fc_csd::class))->newInstanceWithoutConstructor();
    $modelo->error = new \gamboamartin\errores\errores();
    $resultado = $modelo->genera_pems(1);
    if (!\gamboamartin\errores\errores::$error || !is_array($resultado)
        || ($resultado['data'] ?? null) !== []
        || !str_contains(json_encode($resultado), 'Dependencia SSL incompatible')) {
        throw new RuntimeException('Plugins antiguo debe rechazarse antes de leer DB o archivos');
    }
    echo "PASS plugins antiguo rechazado antes de acceso a datos\n";
}
