<?php
declare(strict_types=1);
namespace base\orm { class modelo {} }
namespace {
    $fuente = getenv('FUENTE_DOCUMENTO_ANTIGUO');
    if (!$fuente || hash_file('sha256', $fuente) !== '884567e60be82f98ba8a8430cf6d1268b1861dc4987560fdccc3f876d0ee8b86') {
        throw new RuntimeException('Fuente anterior no acreditada');
    }
    require $fuente;
    require dirname(__DIR__, 2) . '/vendor/autoload.php';
    $rechazo = false;
    try {
        // Ruta inexistente: la guardia de dependencia debe ocurrir antes de cualquier I/O.
        gamboamartin\facturacion\models\contexto_archivos_csd::iniciar('/no-creada-qa-csd');
    } catch (RuntimeException $e) {
        $rechazo = $e->getMessage() === 'Dependencia documento sin soporte de escritura CSD privada';
    }
    if (!$rechazo || file_exists('/no-creada-qa-csd')
        || gamboamartin\facturacion\models\contexto_archivos_csd::actual() !== null) {
        throw new RuntimeException('La dependencia antigua no fue rechazada antes de mutación');
    }
    echo "PASS dependencia_antigua_rechazada_sin_mutacion\n";
}
