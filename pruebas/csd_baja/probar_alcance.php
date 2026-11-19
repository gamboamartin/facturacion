<?php
declare(strict_types=1);

use gamboamartin\facturacion\models\bloqueo_uso_csd;
use gamboamartin\facturacion\models\referencias_baja_csd;

set_error_handler(static function (int $nivel, string $mensaje, string $archivo, int $linea): never {
    throw new ErrorException($mensaje, 0, $nivel, $archivo, $linea);
});

require __DIR__.'/../../orm/bloqueo_uso_csd.php';
require __DIR__.'/../../orm/referencias_baja_csd.php';
require __DIR__.'/pac_ficticio.php';

if ($argc !== 2 || !preg_match('~^/tmp/qa-baja-csd-[A-Za-z0-9]+/mysql\.sock$~D', $argv[1]) || !is_file($argv[1].'.fixture')) {
    throw new RuntimeException('Solo se permite el socket local efímero de esta prueba.');
}
$conectar = static fn (): PDO => new PDO('mysql:unix_socket='.$argv[1].';dbname=qa_baja_csd', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);
$link = $conectar();
$segundo = $conectar();
foreach ([$link, $segundo] as $conexion) {
    if ($conexion->query('SELECT DATABASE()')->fetchColumn() !== 'qa_baja_csd'
        || (int)$conexion->query('SELECT @@skip_networking')->fetchColumn() !== 1) {
        throw new RuntimeException('Aislamiento de prueba no acreditado.');
    }
    $conexion->exec('SET SESSION innodb_lock_wait_timeout = 2');
}

foreach ([
    'CREATE TABLE fc_csd (id INT PRIMARY KEY, no_certificado VARCHAR(100) NOT NULL) ENGINE=InnoDB',
    'CREATE TABLE doc_documento (id INT PRIMARY KEY) ENGINE=InnoDB',
    'CREATE TABLE fc_cer_csd (id INT PRIMARY KEY, fc_csd_id INT NOT NULL, doc_documento_id INT NOT NULL, FOREIGN KEY (fc_csd_id) REFERENCES fc_csd(id), FOREIGN KEY (doc_documento_id) REFERENCES doc_documento(id)) ENGINE=InnoDB',
    'CREATE TABLE fc_key_csd (id INT PRIMARY KEY, fc_csd_id INT NOT NULL, doc_documento_id INT NOT NULL, FOREIGN KEY (fc_csd_id) REFERENCES fc_csd(id), FOREIGN KEY (doc_documento_id) REFERENCES doc_documento(id)) ENGINE=InnoDB',
    'CREATE TABLE fc_cer_pem (id INT PRIMARY KEY, fc_cer_csd_id INT NOT NULL, doc_documento_id INT NOT NULL, FOREIGN KEY (fc_cer_csd_id) REFERENCES fc_cer_csd(id), FOREIGN KEY (doc_documento_id) REFERENCES doc_documento(id)) ENGINE=InnoDB',
    'CREATE TABLE fc_key_pem (id INT PRIMARY KEY, fc_key_csd_id INT NOT NULL, doc_documento_id INT NOT NULL, FOREIGN KEY (fc_key_csd_id) REFERENCES fc_key_csd(id), FOREIGN KEY (doc_documento_id) REFERENCES doc_documento(id)) ENGINE=InnoDB',
    'CREATE TABLE fc_csd_etapa (id INT PRIMARY KEY, fc_csd_id INT NOT NULL, FOREIGN KEY (fc_csd_id) REFERENCES fc_csd(id)) ENGINE=InnoDB',
    'CREATE TABLE fc_uuid (id INT PRIMARY KEY, fc_csd_id INT NOT NULL, FOREIGN KEY (fc_csd_id) REFERENCES fc_csd(id) ON DELETE CASCADE) ENGINE=InnoDB',
    'CREATE TABLE fc_conf_automatico (id INT PRIMARY KEY, fc_csd_id INT NOT NULL) ENGINE=InnoDB',
    'CREATE TABLE fc_uso_cer (id INT PRIMARY KEY, referencia_cer INT, FOREIGN KEY (referencia_cer) REFERENCES fc_cer_csd(id) ON DELETE SET NULL) ENGINE=InnoDB',
    'CREATE TABLE fc_factura (id INT PRIMARY KEY, fc_csd_id INT NOT NULL, status VARCHAR(20) NOT NULL, FOREIGN KEY (fc_csd_id) REFERENCES fc_csd(id)) ENGINE=InnoDB',
    'CREATE TABLE fc_nota_credito (id INT PRIMARY KEY, fc_csd_id INT NOT NULL, status VARCHAR(20) NOT NULL) ENGINE=InnoDB',
    'CREATE TABLE fc_complemento_pago (id INT PRIMARY KEY, fc_csd_id INT NOT NULL, status VARCHAR(20) NOT NULL, FOREIGN KEY (fc_csd_id) REFERENCES fc_csd(id) ON DELETE CASCADE) ENGINE=InnoDB',
    'CREATE TABLE fc_row_layout (id INT PRIMARY KEY) ENGINE=InnoDB',
    // Sin FK al CSD ni join obligatorio a layout: el serial emisor es la asociación histórica.
    'CREATE TABLE fc_cfdi_sellado_nomina (id INT PRIMARY KEY, fc_row_layout_id INT NOT NULL, comprobante_no_certificado VARCHAR(100) NOT NULL, complemento_tfd_no_certificado_sat VARCHAR(100) NOT NULL, status VARCHAR(20) NOT NULL, INDEX (comprobante_no_certificado)) ENGINE=InnoDB',
    'INSERT INTO doc_documento VALUES (10),(11),(12),(13)',
] as $sql) {
    $link->exec($sql);
}

function serial_csd(int $id): string
{
    return str_pad((string)$id, 20, '0', STR_PAD_LEFT);
}
for ($id = 1; $id <= 20; $id++) {
    $consulta = $link->prepare('INSERT INTO fc_csd VALUES (?, ?)');
    $consulta->execute([$id, serial_csd($id === 11 ? 7 : $id)]);
    $base = $id * 100;
    $link->exec('INSERT INTO fc_cer_csd VALUES ('.($base + 1).','.$id.',10)');
    $link->exec('INSERT INTO fc_key_csd VALUES ('.($base + 2).','.$id.',11)');
    $link->exec('INSERT INTO fc_cer_pem VALUES ('.($base + 3).','.($base + 1).',12)');
    $link->exec('INSERT INTO fc_key_pem VALUES ('.($base + 4).','.($base + 2).',13)');
}
foreach ([
    'INSERT INTO fc_csd_etapa VALUES (1,2),(2,6),(3,19)',
    'INSERT INTO fc_uuid VALUES (1,3)',
    'INSERT INTO fc_conf_automatico VALUES (1,4)',
    'INSERT INTO fc_uso_cer VALUES (1,501)',
    "INSERT INTO fc_factura VALUES (1,13,'inactivo')",
    "INSERT INTO fc_nota_credito VALUES (1,14,'inactivo')",
    "INSERT INTO fc_complemento_pago VALUES (1,15,'inactivo')",
    'INSERT INTO fc_row_layout VALUES (1),(2)',
] as $sql) {
    $link->exec($sql);
}
$sello = $link->prepare('INSERT INTO fc_cfdi_sellado_nomina VALUES (?, ?, ?, ?, ?)');
foreach ([
    [1, 1, serial_csd(7), 'SAT-FICTICIO-A', 'activo'],
    [2, 2, serial_csd(8), 'SAT-FICTICIO-B', 'inactivo'],
    [3, 999, serial_csd(9), 'SAT-FICTICIO-C', 'inactivo'],
    [4, 1, 'EMISOR-AJENO-FICTICIO', serial_csd(12), 'activo'],
] as $fila) {
    $sello->execute($fila);
}

$assertions = 0;
$casos = [];
function exigir(bool $ok, string $descripcion): void
{
    global $assertions;
    $assertions++;
    if (!$ok) {
        throw new RuntimeException('No pasó: '.$descripcion);
    }
}
function caso(string $id, callable $prueba): void
{
    global $casos, $assertions;
    $antes = $assertions;
    $prueba();
    $casos[] = $id;
    echo 'PASS: '.$id.' ('.($assertions - $antes)." assertions)\n";
}
function snapshot(PDO $conexion): array
{
    $salida = [];
    foreach (['fc_csd', 'fc_cer_csd', 'fc_key_csd', 'fc_cer_pem', 'fc_key_pem', 'fc_csd_etapa',
        'doc_documento', 'fc_uuid', 'fc_conf_automatico', 'fc_uso_cer', 'fc_factura', 'fc_nota_credito',
        'fc_complemento_pago', 'fc_row_layout', 'fc_cfdi_sellado_nomina'] as $tabla) {
        $salida[$tabla] = $conexion->query('SELECT * FROM '.$tabla.' ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    }
    return $salida;
}
function esperado_sin_csd(array $antes, int $id): array
{
    foreach (['fc_csd' => $id, 'fc_cer_csd' => $id * 100 + 1, 'fc_key_csd' => $id * 100 + 2,
        'fc_cer_pem' => $id * 100 + 3, 'fc_key_pem' => $id * 100 + 4] as $tabla => $filaId) {
        $antes[$tabla] = array_values(array_filter($antes[$tabla], static fn (array $fila): bool => (int)$fila['id'] !== $filaId));
    }
    $antes['fc_csd_etapa'] = array_values(array_filter($antes['fc_csd_etapa'], static fn (array $fila): bool => (int)$fila['fc_csd_id'] !== $id));
    return $antes;
}
function baja_padre(PDO $conexion, int $id): stdClass
{
    $consulta = $conexion->prepare('DELETE FROM fc_csd WHERE id = ?');
    $consulta->execute([$id]);
    exigir($consulta->rowCount() === 1, 'el callback elimina exactamente el padre solicitado');
    return (object)['registro_id' => $id];
}
function rechazo(callable $operacion, string $descripcion): RuntimeException
{
    try {
        $operacion();
    } catch (RuntimeException $error) {
        exigir(!$error instanceof PDOException, $descripcion.': rechazo de negocio, no error SQL accidental');
        exigir($error->getMessage() !== '', $descripcion.': causa explícita');
        return $error;
    }
    throw new RuntimeException('No se rechazó: '.$descripcion);
}
function rechazar_baja(PDO $conexion, int $id): void
{
    $antes = snapshot($conexion);
    $ejecutado = false;
    rechazo(static function () use ($conexion, $id, &$ejecutado): void {
        (new referencias_baja_csd($conexion))->eliminar($id, static function () use ($conexion, $id, &$ejecutado): stdClass {
            $ejecutado = true;
            return baja_padre($conexion, $id);
        });
    }, 'baja bloqueada '.$id);
    exigir(!$ejecutado, 'bloqueo antes del callback '.$id);
    exigir(!$conexion->inTransaction(), 'bloqueo cierra transacción propia '.$id);
    exigir(snapshot($conexion) === $antes, 'todas las filas y columnas permanecen intactas '.$id);
}
function permitir_baja(PDO $conexion, int $id): void
{
    $antes = snapshot($conexion);
    $llamadas = 0;
    $resultado = (new referencias_baja_csd($conexion))->eliminar($id, static function () use ($conexion, $id, &$llamadas): stdClass {
        $llamadas++;
        return baja_padre($conexion, $id);
    });
    exigir($llamadas === 1 && $resultado->registro_id === $id, 'callback único y retorno de baja '.$id);
    exigir(snapshot($conexion) === esperado_sin_csd($antes, $id), 'baja exacta preserva documentos compartidos y negocio '.$id);
    exigir(!$conexion->inTransaction(), 'baja confirma solo su transacción '.$id);
}

caso('inventario_exclusivo_y_etapa', static function () use ($link): void {
    exigir((int)$link->query('SELECT @@SESSION.foreign_key_checks')->fetchColumn() === 1, 'FK activas');
    exigir((int)$link->query("SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE()")->fetchColumn() === 13, 'trece FK físicas acreditadas también en MySQL');
    $antes = snapshot($link);
    $link->beginTransaction();
    try {
        $guard = new referencias_baja_csd($link);
        $inventario = $guard->inventario(1);
        $esperado = ['fc_csd' => [1], 'fc_cer_csd' => [101], 'fc_key_csd' => [102],
            'fc_cer_pem' => [103], 'fc_key_pem' => [104], 'fc_csd_etapa' => []];
        ksort($inventario);
        ksort($esperado);
        exigir($inventario === $esperado, 'inventario exacto del CSD con documentos compartidos');
        exigir($guard->inventario(2)['fc_csd_etapa'] === [1], 'etapa propia incluida');
        exigir(snapshot($link) === $antes, 'inventario no escribe ninguna fila');
    } finally {
        $link->rollBack();
    }
});
foreach ([3 => 'fk_cascada_bloquea', 4 => 'convencion_sin_fk_bloquea', 5 => 'hijo_set_null_bloquea',
    999 => 'csd_inexistente_bloquea'] as $id => $nombre) {
    caso($nombre, static fn () => rechazar_baja($link, $id));
}
caso('configurado_sin_recibos_permite_y_conserva_compartidos', static function () use ($link): void {
    \config\pac::$fc_csd_nomina_id = 2;
    permitir_baja($link, 2);
    exigir(\config\pac::$fc_csd_nomina_id === 2, 'baja no modifica configuración de nómina');
    exigir((int)$link->query('SELECT doc_documento_id FROM fc_cer_csd WHERE id = 101')->fetchColumn() === 10, 'documento compartido sigue vinculado al otro CSD');
});
foreach ([7 => 'nomina_serial_historico_activo_bloquea', 8 => 'nomina_serial_historico_inactivo_bloquea',
    9 => 'nomina_sello_huerfano_bloquea', 11 => 'otro_id_mismo_certificado_bloquea',
    13 => 'factura_bloquea', 14 => 'nota_credito_bloquea', 15 => 'complemento_pago_bloquea'] as $id => $nombre) {
    caso($nombre, static function () use ($link, $id): void {
        exigir(\config\pac::$fc_csd_nomina_id !== $id, 'la prueba no depende del ID configurado');
        rechazar_baja($link, $id);
    });
}
caso('nominas_otro_certificado_no_bloquean', static fn () => permitir_baja($link, 10));
caso('certificado_sat_no_es_certificado_emisor', static fn () => permitir_baja($link, 12));
caso('factura_nc_cp_de_otro_csd_no_bloquean', static fn () => permitir_baja($link, 20));

foreach ([false => 'fallo_parcial_revierte_transaccion_propia', true => 'fallo_parcial_revierte_savepoint'] as $ajena => $nombre) {
    caso($nombre, static function () use ($link, $ajena): void {
        \config\pac::$fc_csd_nomina_id = 6;
        if ($ajena) {
            $link->beginTransaction();
            $link->exec('INSERT INTO doc_documento VALUES (20)');
        }
        $antes = snapshot($link);
        try {
            $error = rechazo(static function () use ($link): void {
                (new referencias_baja_csd($link))->eliminar(6, static function () use ($link): stdClass {
                    baja_padre($link, 6);
                    throw new RuntimeException('Fallo simulado después de borrar el padre.');
                });
            }, 'fallo parcial');
            exigir($error->getMessage() === 'Fallo simulado después de borrar el padre.', 'conserva causa original');
            exigir(snapshot($link) === $antes, 'reversión exacta incluye etapa, documentos y trabajo previo');
            exigir($link->inTransaction() === (bool)$ajena, 'conserva propiedad de transacción');
            if ($ajena) {
                exigir((int)$link->query('SELECT COUNT(*) FROM doc_documento WHERE id = 20')->fetchColumn() === 1, 'marcador del llamador intacto');
            }
        } finally {
            if ($link->inTransaction()) {
                $link->rollBack();
            }
        }
    });
}
caso('exito_savepoint_no_confirma_transaccion_ajena', static function () use ($link): void {
    \config\pac::$fc_csd_nomina_id = 19;
    $original = snapshot($link);
    $link->beginTransaction();
    $link->exec('INSERT INTO doc_documento VALUES (20)');
    $antes = snapshot($link);
    try {
        $resultado = (new referencias_baja_csd($link))->eliminar(19, static fn (): stdClass => baja_padre($link, 19));
        exigir($resultado->registro_id === 19, 'retorno de baja anidada');
        exigir($link->inTransaction(), 'éxito no confirma transacción ajena');
        exigir(snapshot($link) === esperado_sin_csd($antes, 19), 'alcance exacto dentro de transacción');
    } finally {
        if ($link->inTransaction()) {
            $link->rollBack();
        }
    }
    exigir(snapshot($link) === $original, 'rollback del llamador restaura también la baja exitosa');
});
caso('callback_sin_baja_revierte_enlaces', static function () use ($link): void {
    $antes = snapshot($link);
    rechazo(static fn () => (new referencias_baja_csd($link))->eliminar(16, static fn (): stdClass => (object)['registro_id' => 16]), 'callback no elimina');
    exigir(snapshot($link) === $antes && !$link->inTransaction(), 'no permite baja parcial de enlaces');
});

caso('reserva_otra_conexion_impide_baja', static function () use ($link, $segundo): void {
    exigir($link->query('SELECT CONNECTION_ID()')->fetchColumn() !== $segundo->query('SELECT CONNECTION_ID()')->fetchColumn(), 'son dos conexiones independientes');
    $antes = snapshot($link);
    $resultado = (new bloqueo_uso_csd($link))->ejecutar(17, static function () use ($segundo): string {
        rechazar_baja($segundo, 17);
        return 'uso_sintetico_sin_pac';
    });
    exigir($resultado === 'uso_sintetico_sin_pac', 'reserva conserva retorno del callback');
    exigir(snapshot($link) === $antes, 'contención no modifica filas');
});
caso('liberar_reserva_permite_otro_escritor', static function () use ($segundo): void {
    $ejecutado = 0;
    $resultado = (new bloqueo_uso_csd($segundo))->ejecutar(17, static function () use (&$ejecutado): string {
        $ejecutado++;
        return 'reserva_liberada';
    });
    exigir($ejecutado === 1 && $resultado === 'reserva_liberada', 'otra conexión obtiene reserva liberada');
});
caso('excepcion_del_escritor_libera_reserva', static function () use ($link, $segundo): void {
    $antes = snapshot($link);
    $error = rechazo(static fn () => (new bloqueo_uso_csd($link))->ejecutar(17, static function (): never {
        throw new RuntimeException('Fallo sintético del escritor sin PAC.');
    }), 'excepción del escritor');
    exigir($error->getMessage() === 'Fallo sintético del escritor sin PAC.', 'preserva excepción del escritor');
    exigir((new bloqueo_uso_csd($segundo))->ejecutar(17, static fn (): string => 'ok') === 'ok', 'finally libera reserva a otra conexión');
    exigir(snapshot($link) === $antes, 'excepción sin cambios de datos');
});
caso('baja_en_curso_impide_escritor', static function () use ($link, $segundo): void {
    $antes = snapshot($link);
    $resultado = (new referencias_baja_csd($link))->eliminar(18, static function () use ($link, $segundo): stdClass {
        $ejecutado = false;
        rechazo(static function () use ($segundo, &$ejecutado): void {
            (new bloqueo_uso_csd($segundo))->ejecutar(18, static function () use (&$ejecutado): void {
                $ejecutado = true;
            });
        }, 'escritor durante baja');
        exigir(!$ejecutado, 'escritor no entra al callback con baja reservada');
        return baja_padre($link, 18);
    });
    exigir($resultado->registro_id === 18, 'baja reservada finaliza');
    exigir(snapshot($link) === esperado_sin_csd($antes, 18), 'alcance íntegro después de contención');
});
caso('padre_eliminado_impide_escritor', static function () use ($segundo): void {
    $antes = snapshot($segundo);
    $ejecutado = false;
    rechazo(static function () use ($segundo, &$ejecutado): void {
        (new bloqueo_uso_csd($segundo))->ejecutar(18, static function () use (&$ejecutado): void {
            $ejecutado = true;
        });
    }, 'uso de CSD ya eliminado');
    exigir(!$ejecutado, 'CSD inexistente se rechaza antes de datos o PAC');
    exigir(snapshot($segundo) === $antes, 'intento posterior no cambia datos');
});

require __DIR__.'/errores_ficticios.php';
require __DIR__.'/../../orm/_timbra_nomina.php';
$cargasProhibidas = [];
$rechazarAutoload = static function (string $clase) use (&$cargasProhibidas): never {
    $cargasProhibidas[] = $clase;
    throw new LogicException('Colaborador inesperado: la prueba no permite datos, documentos ni PAC.');
};
spl_autoload_register($rechazarAutoload);
try {
    foreach (['timbra_recibo', 'retimbra_recibo'] as $metodo) {
        foreach (['reserva_ocupada', 'csd_eliminado'] as $situacion) {
            caso($metodo.'_'.$situacion.'_antes_de_datos_y_pac', static function () use ($link, $segundo, $metodo, $situacion, &$cargasProhibidas): void {
                \config\pac::$fc_csd_nomina_id = $situacion === 'reserva_ocupada' ? 17 : 18;
                \gamboamartin\errores\errores::$error = false;
                $antes = snapshot($segundo);
                $llamar = static fn () => (new \gamboamartin\facturacion\models\_timbra_nomina())->$metodo($segundo, 999);
                $resultado = $situacion === 'reserva_ocupada'
                    ? (new bloqueo_uso_csd($link))->ejecutar(17, $llamar)
                    : $llamar();
                exigir(\gamboamartin\errores\errores::$error, 'wrapper público establece señal de error');
                exigir(is_array($resultado) && ($resultado['mensaje'] ?? '') === 'No se pudo completar la operación de nómina con el CSD seleccionado.', 'wrapper conserva retorno público de error');
                exigir(($resultado['data']['tipo'] ?? '') === RuntimeException::class, 'rechazo de reserva/existencia, no fallo por colaborador ausente');
                exigir($cargasProhibidas === [], 'no carga datos, emisor ni PAC');
                exigir(snapshot($segundo) === $antes, 'wrapper rechazado no cambia datos');
                \gamboamartin\errores\errores::$error = false;
            });
        }
    }
} finally {
    spl_autoload_unregister($rechazarAutoload);
}

$registro = json_decode(file_get_contents(__DIR__.'/registro.json'), true, 512, JSON_THROW_ON_ERROR);
exigir($casos === $registro['casos'], 'identidad y orden de todos los casos registrados');
exigir(count($casos) === $registro['casos_previstos'], 'conteo registrado sin omisiones');
exigir(!$link->inTransaction() && !$segundo->inTransaction(), 'conexiones finales sin transacciones abiertas');
echo json_encode(['resultado' => 'PASS', 'casos' => count($casos), 'assertions' => $assertions,
    'alcance' => 'baja, serial histórico de nómina, documentos compartidos, reversión y reserva cooperativa con dos PDO; sin PAC'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n";
