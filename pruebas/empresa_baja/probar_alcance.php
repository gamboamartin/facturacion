<?php
declare(strict_types=1);

use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\bloqueo_uso_csd;
use gamboamartin\facturacion\models\referencias_baja_csd;
use gamboamartin\facturacion\models\referencias_baja_empresa;
use gamboamartin\facturacion\models\org_empresa;
use gamboamartin\organigrama\models\org_empresa as PadreEmpresa;
use gamboamartin\facturacion\controllers\controlador_org_empresa;

set_error_handler(static function (int $nivel, string $mensaje, string $archivo, int $linea): never {
    throw new ErrorException($mensaje, 0, $nivel, $archivo, $linea);
});
spl_autoload_register(static function (string $clase): never {
    throw new RuntimeException('La prueba no permite autoload externo: '.$clase);
});
require __DIR__.'/dobles.php';
foreach (['bloqueo_uso_csd', 'referencias_baja_csd', 'referencias_baja_empresa', 'org_empresa'] as $fuente) {
    require __DIR__.'/../../orm/'.$fuente.'.php';
}
require __DIR__.'/../../controllers/controlador_org_empresa.php';

if ($argc !== 2 || !preg_match('~^/tmp/qa-baja-empresa-[A-Za-z0-9]+/mysql\.sock$~D', $argv[1])
    || is_link(dirname($argv[1])) || is_link($argv[1].'.fixture')
    || !is_file($argv[1].'.fixture') || file_get_contents($argv[1].'.fixture') !== "empresa_baja\n") {
    throw new RuntimeException('Solo se permite el motor efímero creado por esta batería.');
}
$conectar = static fn (): PDO => new PDO('mysql:unix_socket='.$argv[1].';dbname=qa_baja_empresa', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);
$link = $conectar();
$segundo = $conectar();
foreach ([$link, $segundo] as $conexion) {
    if ($conexion->query('SELECT DATABASE()')->fetchColumn() !== 'qa_baja_empresa'
        || (int)$conexion->query('SELECT @@skip_networking')->fetchColumn() !== 1
        || $conexion->query('SELECT @@socket')->fetchColumn() !== $argv[1]) {
        throw new RuntimeException('Aislamiento de prueba no acreditado.');
    }
    $conexion->exec('SET SESSION innodb_lock_wait_timeout = 1');
    $conexion->exec('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
}
$ddls = [
    'doc_documento' => 'id INT PRIMARY KEY, nombre VARCHAR(80) NOT NULL',
    'cat_sat_actividad_economica' => 'id INT PRIMARY KEY',
    'org_representante_legal' => 'id INT PRIMARY KEY',
    'org_actividad' => 'id INT PRIMARY KEY',
    'bn_banco' => 'id INT PRIMARY KEY',
    'com_producto' => 'id INT PRIMARY KEY',
    'com_tipo_cliente' => 'id INT PRIMARY KEY',
    'com_sucursal' => 'id INT PRIMARY KEY',
    'org_empresa' => 'id INT PRIMARY KEY, rfc VARCHAR(30) NOT NULL, nota VARCHAR(80) NOT NULL',
    'org_sucursal' => 'id INT PRIMARY KEY, org_empresa_id INT NOT NULL, matriz INT NOT NULL, FOREIGN KEY (org_empresa_id) REFERENCES org_empresa(id)',
    'org_logo' => 'id INT PRIMARY KEY, org_empresa_id INT NOT NULL, doc_documento_id INT NOT NULL, FOREIGN KEY (org_empresa_id) REFERENCES org_empresa(id), FOREIGN KEY (doc_documento_id) REFERENCES doc_documento(id)',
    'org_porcentaje_act_economica' => 'id INT PRIMARY KEY, org_empresa_id INT NOT NULL, cat_sat_actividad_economica_id INT NOT NULL, FOREIGN KEY (org_empresa_id) REFERENCES org_empresa(id), FOREIGN KEY (cat_sat_actividad_economica_id) REFERENCES cat_sat_actividad_economica(id)',
    'org_representante_asignado' => 'id INT PRIMARY KEY, org_empresa_id INT NOT NULL, org_representante_legal_id INT NOT NULL, FOREIGN KEY (org_empresa_id) REFERENCES org_empresa(id), FOREIGN KEY (org_representante_legal_id) REFERENCES org_representante_legal(id)',
    'org_departamento' => 'id INT PRIMARY KEY, org_empresa_id INT NOT NULL, FOREIGN KEY (org_empresa_id) REFERENCES org_empresa(id)',
    'org_puesto' => 'id INT PRIMARY KEY, org_departamento_id INT NOT NULL, FOREIGN KEY (org_departamento_id) REFERENCES org_departamento(id)',
    'org_dependencia' => 'id INT PRIMARY KEY, org_puesto_jefe_id INT NOT NULL, org_puesto_empleado_id INT NOT NULL, FOREIGN KEY (org_puesto_jefe_id) REFERENCES org_puesto(id), FOREIGN KEY (org_puesto_empleado_id) REFERENCES org_puesto(id)',
    'org_ejecuta' => 'id INT PRIMARY KEY, org_puesto_id INT NOT NULL, org_actividad_id INT NOT NULL, FOREIGN KEY (org_puesto_id) REFERENCES org_puesto(id), FOREIGN KEY (org_actividad_id) REFERENCES org_actividad(id)',
    'bn_sucursal_cuenta' => 'id INT PRIMARY KEY, org_sucursal_id INT NOT NULL, bn_banco_id INT NOT NULL, FOREIGN KEY (org_sucursal_id) REFERENCES org_sucursal(id), FOREIGN KEY (bn_banco_id) REFERENCES bn_banco(id)',
    'fc_csd' => 'id INT PRIMARY KEY, org_sucursal_id INT NOT NULL, no_certificado VARCHAR(100) NOT NULL, FOREIGN KEY (org_sucursal_id) REFERENCES org_sucursal(id)',
    'fc_conf_automatico' => 'id INT PRIMARY KEY, fc_csd_id INT NOT NULL, com_tipo_cliente_id INT NOT NULL, FOREIGN KEY (fc_csd_id) REFERENCES fc_csd(id), FOREIGN KEY (com_tipo_cliente_id) REFERENCES com_tipo_cliente(id)',
    'fc_conf_aut_producto' => 'id INT PRIMARY KEY, fc_conf_automatico_id INT NOT NULL, fc_csd_id INT NOT NULL, com_producto_id INT NOT NULL, FOREIGN KEY (fc_conf_automatico_id) REFERENCES fc_conf_automatico(id), FOREIGN KEY (fc_csd_id) REFERENCES fc_csd(id), FOREIGN KEY (com_producto_id) REFERENCES com_producto(id)',
    'fc_cer_csd' => 'id INT PRIMARY KEY, fc_csd_id INT NOT NULL, doc_documento_id INT NOT NULL, FOREIGN KEY (fc_csd_id) REFERENCES fc_csd(id), FOREIGN KEY (doc_documento_id) REFERENCES doc_documento(id)',
    'fc_key_csd' => 'id INT PRIMARY KEY, fc_csd_id INT NOT NULL, doc_documento_id INT NOT NULL, FOREIGN KEY (fc_csd_id) REFERENCES fc_csd(id), FOREIGN KEY (doc_documento_id) REFERENCES doc_documento(id)',
    'fc_csd_etapa' => 'id INT PRIMARY KEY, fc_csd_id INT NOT NULL, FOREIGN KEY (fc_csd_id) REFERENCES fc_csd(id)',
    'fc_cer_pem' => 'id INT PRIMARY KEY, fc_cer_csd_id INT NOT NULL, doc_documento_id INT NOT NULL, FOREIGN KEY (fc_cer_csd_id) REFERENCES fc_cer_csd(id), FOREIGN KEY (doc_documento_id) REFERENCES doc_documento(id)',
    'fc_key_pem' => 'id INT PRIMARY KEY, fc_key_csd_id INT NOT NULL, doc_documento_id INT NOT NULL, FOREIGN KEY (fc_key_csd_id) REFERENCES fc_key_csd(id), FOREIGN KEY (doc_documento_id) REFERENCES doc_documento(id)',
    'fc_factura' => 'id INT PRIMARY KEY, fc_csd_id INT NOT NULL, status VARCHAR(20) NOT NULL, FOREIGN KEY (fc_csd_id) REFERENCES fc_csd(id)',
    'fc_nota_credito' => 'id INT PRIMARY KEY, fc_csd_id INT NOT NULL, status VARCHAR(20) NOT NULL, FOREIGN KEY (fc_csd_id) REFERENCES fc_csd(id)',
    'fc_complemento_pago' => 'id INT PRIMARY KEY, fc_csd_id INT NOT NULL, status VARCHAR(20) NOT NULL, FOREIGN KEY (fc_csd_id) REFERENCES fc_csd(id) ON DELETE CASCADE',
    'fc_uuid' => 'id INT PRIMARY KEY, fc_csd_id INT NOT NULL, FOREIGN KEY (fc_csd_id) REFERENCES fc_csd(id) ON DELETE CASCADE',
    // Modelo real: com_sucursal es cliente; no identifica a la empresa emisora.
    'fc_layout_nom' => 'id INT PRIMARY KEY, com_sucursal_id INT NOT NULL, FOREIGN KEY (com_sucursal_id) REFERENCES com_sucursal(id)',
    'fc_row_layout' => 'id INT PRIMARY KEY, fc_layout_nom_id INT NOT NULL, FOREIGN KEY (fc_layout_nom_id) REFERENCES fc_layout_nom(id)',
    'fc_row_nomina' => 'id INT PRIMARY KEY, fc_row_layout_id INT NOT NULL, doc_documento_id INT NOT NULL, FOREIGN KEY (fc_row_layout_id) REFERENCES fc_row_layout(id), FOREIGN KEY (doc_documento_id) REFERENCES doc_documento(id)',
    // Serial histórico emisor, sin FK al CSD ni al layout: cubre sellos huérfanos reales.
    'fc_cfdi_sellado_nomina' => 'id INT PRIMARY KEY, fc_row_layout_id INT NOT NULL, comprobante_no_certificado VARCHAR(100) NOT NULL, complemento_tfd_no_certificado_sat VARCHAR(100) NOT NULL, status VARCHAR(20) NOT NULL, INDEX (comprobante_no_certificado)',
    'uso_externo_empresa' => 'id INT PRIMARY KEY, referencia INT NULL, FOREIGN KEY (referencia) REFERENCES org_empresa(id) ON DELETE SET NULL',
    'uso_cascada_empresa' => 'id INT PRIMARY KEY, referencia INT NOT NULL, FOREIGN KEY (referencia) REFERENCES org_empresa(id) ON DELETE CASCADE',
    'uso_convencional' => 'id INT PRIMARY KEY, org_empresa_id INT NOT NULL',
    'uso_cer' => 'id INT PRIMARY KEY, referencia INT NULL, FOREIGN KEY (referencia) REFERENCES fc_cer_csd(id) ON DELETE SET NULL',
    'fc_ejecucion_automatica' => 'id INT PRIMARY KEY, fc_conf_automatico_id INT NOT NULL, FOREIGN KEY (fc_conf_automatico_id) REFERENCES fc_conf_automatico(id)',
    'fc_factura_automatica' => 'id INT PRIMARY KEY, fc_ejecucion_automatica_id INT NOT NULL, fc_factura_id INT NOT NULL, FOREIGN KEY (fc_ejecucion_automatica_id) REFERENCES fc_ejecucion_automatica(id), FOREIGN KEY (fc_factura_id) REFERENCES fc_factura(id)',
];
foreach ($ddls as $tabla => $definicion) {
    $link->exec('CREATE TABLE '.$tabla.' ('.$definicion.') ENGINE=InnoDB');
}
$tablas = array_keys($ddls);
$propias = ['org_empresa', 'org_sucursal', 'org_logo', 'org_porcentaje_act_economica', 'org_representante_asignado',
    'org_departamento', 'org_puesto', 'org_dependencia', 'org_ejecuta', 'bn_sucursal_cuenta', 'fc_csd',
    'fc_conf_automatico', 'fc_conf_aut_producto', 'fc_cer_csd', 'fc_key_csd', 'fc_csd_etapa', 'fc_cer_pem', 'fc_key_pem', 'fc_ejecucion_automatica'];
$assertions = 0;
$casos = [];

function exigir(bool $ok, string $mensaje): void {
    global $assertions;
    $assertions++;
    if (!$ok) { throw new RuntimeException('No pasó: '.$mensaje); }
}
function limpiar_y_sembrar(): void {
    global $link, $segundo, $tablas, $propias;
    foreach ([$link, $segundo] as $pdo) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        $pdo->exec('SET SESSION foreign_key_checks = 1');
        $pdo->exec('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
    }
    errores::$error = false;
    PadreEmpresa::$llamadas = 0;
    PadreEmpresa::$flag_durante_baja = null;
    PadreEmpresa::$fallo = '';
    foreach (array_reverse($tablas) as $tabla) { $link->exec('DELETE FROM '.$tabla); }
    foreach (['cat_sat_actividad_economica', 'org_representante_legal', 'org_actividad', 'bn_banco', 'com_producto', 'com_tipo_cliente', 'com_sucursal'] as $tabla) {
        $link->exec('INSERT INTO '.$tabla.' VALUES (10)');
    }
    $link->exec("INSERT INTO doc_documento VALUES (10, 'ARCHIVO_COMPARTIDO_FICTICIO')");
    for ($id = 1; $id <= 2; $id++) {
        foreach ($propias as $tabla) {
            $valores = match ($tabla) {
                'org_empresa' => [$id, 'FICTICIO_EMPRESA_'.$id, 'CONSERVAR_'.$id],
                'org_sucursal' => [$id, $id, 1],
                'org_logo', 'org_porcentaje_act_economica', 'org_representante_asignado', 'org_ejecuta', 'bn_sucursal_cuenta',
                'fc_conf_automatico', 'fc_cer_csd', 'fc_key_csd', 'fc_cer_pem', 'fc_key_pem' => [$id, $id, 10],
                'org_dependencia' => [$id, $id, $id],
                'fc_csd' => [$id, $id, 'SERIAL_FICTICIO_'.$id],
                'fc_conf_aut_producto' => [$id, $id, $id, 10],
                default => [$id, $id],
            };
            $q = $link->prepare('INSERT INTO '.$tabla.' VALUES ('.implode(',', array_fill(0, count($valores), '?')).')');
            $q->execute($valores);
        }
    }
}
function caso(string $id, callable $prueba): void {
    global $casos, $assertions;
    limpiar_y_sembrar();
    $antes = $assertions;
    $prueba();
    $casos[] = $id;
    echo 'PASS: '.$id.' ('.($assertions - $antes)." assertions)\n";
}
function snapshot(): array {
    global $link, $tablas;
    $salida = [];
    foreach ($tablas as $tabla) { $salida[$tabla] = $link->query('SELECT * FROM '.$tabla.' ORDER BY id')->fetchAll(PDO::FETCH_ASSOC); }
    return $salida;
}
function esperado_sin_empresa(array $antes, int $id): array {
    global $propias;
    foreach ($propias as $tabla) {
        $antes[$tabla] = array_values(array_filter($antes[$tabla], static fn (array $fila): bool => (int)$fila['id'] !== $id));
    }
    return $antes;
}
function padre(int $id): stdClass {
    global $link;
    $q = $link->prepare('DELETE FROM org_empresa WHERE id = ?');
    $q->execute([$id]);
    exigir($q->rowCount() === 1, 'el padre solicitado se elimina exactamente una vez');
    return (object)['registro_id' => $id];
}
function csd(int $id): stdClass {
    global $link;
    return (new referencias_baja_csd($link))->eliminar($id, static function () use ($id): stdClass {
        global $link;
        $q = $link->prepare('DELETE FROM fc_csd WHERE id = ?');
        $q->execute([$id]);
        exigir($q->rowCount() === 1, 'la cascada CSD elimina el padre solicitado');
        return (object)['registro_id' => $id];
    });
}
function baja(int $id = 1, ?callable $callback = null, ?callable $callbackCsd = null): stdClass {
    global $link;
    return (new referencias_baja_empresa($link))->eliminar($id, $callback ?? static fn (): stdClass => padre($id), $callbackCsd ?? 'csd');
}
function rechazo(callable $operacion, string $fragmento = ''): void {
    try { $operacion(); } catch (RuntimeException $error) {
        exigir(!$error instanceof PDOException, 'rechazo explícito y no error SQL accidental');
        exigir($error->getMessage() !== '', 'causa visible');
        if ($fragmento !== '') { exigir(str_contains($error->getMessage(), $fragmento), 'causa específica: '.$fragmento); }
        return;
    }
    throw new RuntimeException('No se rechazó la operación.');
}
function bloquear(int $id = 1, string $fragmento = ''): void {
    global $link;
    $antes = snapshot();
    $padre = false;
    $csd = false;
    rechazo(static function () use ($id, &$padre, &$csd): void {
        baja($id, static function () use (&$padre): stdClass { $padre = true; return new stdClass(); },
            static function () use (&$csd): stdClass { $csd = true; return new stdClass(); });
    }, $fragmento);
    exigir(!$padre && !$csd, 'ningún callback de baja se ejecuta');
    exigir(!$link->inTransaction(), 'cierra solo su propia transacción');
    exigir(snapshot() === $antes, 'todas las filas y columnas permanecen idénticas');
}
function permitir(int $id = 1): void {
    global $link;
    $antes = snapshot();
    $resultado = baja($id);
    exigir($resultado->registro_id === $id, 'conserva el retorno del ORM');
    exigir(!$link->inTransaction(), 'éxito confirma la transacción propia');
    exigir(snapshot() === esperado_sin_empresa($antes, $id), 'retira solo dependencias propias y conserva todo lo compartido');
}
function insertar_nomina(string $serial, string $status, int $layout = 999, string $sat = 'SERIAL_SAT_AJENO'): void {
    global $link;
    $q = $link->prepare('INSERT INTO fc_cfdi_sellado_nomina VALUES (1, ?, ?, ?, ?)');
    $q->execute([$layout, $serial, $sat, $status]);
}
function espera_bloqueo(callable $consulta): void {
    try { $consulta(); } catch (PDOException $error) {
        exigir((int)($error->errorInfo[1] ?? 0) === 1205, 'la segunda conexión encuentra bloqueo InnoDB, no otro error');
        return;
    }
    throw new RuntimeException('La escritura concurrente no quedó bloqueada.');
}

caso('identificador_cero_bloquea', static fn () => bloquear(0, 'positivo'));
caso('identificador_negativo_bloquea', static fn () => bloquear(-1, 'positivo'));
caso('empresa_inexistente_bloquea', static fn () => bloquear(999, 'no existe'));
caso('empresa_sin_dependencias_permite', static function () use ($link): void {
    $link->exec("INSERT INTO org_empresa VALUES (3, 'FICTICIO_EMPRESA_3', 'VACIA')");
    permitir(3);
});
caso('sin_fiscales_cascada_matriz_y_csd_conserva_compartidos', static fn () => permitir());
caso('enlace_puesto_cruzado_conserva_puesto_externo', static function () use ($link): void {
    $link->exec('UPDATE org_dependencia SET org_puesto_empleado_id = 2 WHERE id = 1');
    permitir();
});
caso('config_producto_csd_cruzado_bloquea', static function () use ($link): void {
    $link->exec('UPDATE fc_conf_aut_producto SET fc_csd_id = 2 WHERE id = 1');
    bloquear(1, 'compartida');
});
caso('config_producto_config_cruzada_bloquea', static function () use ($link): void {
    $link->exec('UPDATE fc_conf_aut_producto SET fc_conf_automatico_id = 2 WHERE id = 1');
    bloquear(1, 'compartida');
});
foreach (['fc_factura', 'fc_nota_credito', 'fc_complemento_pago'] as $tabla) {
    foreach (['activo', 'inactivo'] as $status) {
        caso($tabla.'_'.$status.'_bloquea', static function () use ($link, $tabla, $status): void {
            $q = $link->prepare('INSERT INTO '.$tabla.' VALUES (1, 1, ?)');
            $q->execute([$status]);
            bloquear(1, $tabla);
        });
    }
}
foreach (['activo', 'inactivo'] as $status) {
    caso('nomina_historica_'.$status.'_bloquea', static function () use ($status, $link): void {
        $link->exec('INSERT INTO fc_layout_nom VALUES (1,10)');
        $link->exec('INSERT INTO fc_row_layout VALUES (1,1)');
        insertar_nomina('SERIAL_FICTICIO_1', $status, 1);
        bloquear(1, 'nóminas');
    });
}
caso('nomina_huerfana_serial_emisor_bloquea', static function (): void {
    insertar_nomina('SERIAL_FICTICIO_1', 'inactivo');
    bloquear(1, 'nóminas');
});
caso('nomina_otro_csd_mismo_serial_bloquea', static function () use ($link): void {
    $link->exec("UPDATE fc_csd SET no_certificado = 'SERIAL_FICTICIO_1' WHERE id = 2");
    insertar_nomina('SERIAL_FICTICIO_1', 'activo');
    bloquear(2, 'nóminas');
});
caso('certificado_sat_no_es_emisor_permite', static function (): void {
    insertar_nomina('SERIAL_EMISOR_AJENO', 'activo', 999, 'SERIAL_FICTICIO_1');
    permitir();
});
caso('fiscales_y_nominas_otra_empresa_se_conservan', static function () use ($link): void {
    foreach (['fc_factura', 'fc_nota_credito', 'fc_complemento_pago'] as $tabla) {
        $link->exec("INSERT INTO ".$tabla." VALUES (1,2,'inactivo')");
    }
    insertar_nomina('SERIAL_FICTICIO_2', 'inactivo');
    $link->exec('INSERT INTO fc_layout_nom VALUES (1,10)');
    $link->exec('INSERT INTO fc_row_layout VALUES (1,1)');
    $link->exec('INSERT INTO fc_row_nomina VALUES (1,1,10)');
    permitir();
});
caso('fc_uuid_cascada_bloquea', static function () use ($link): void {
    $link->exec('INSERT INTO fc_uuid VALUES (1,1)');
    bloquear(1, 'fc_uuid');
});
foreach (['uso_externo_empresa', 'uso_cascada_empresa', 'uso_convencional', 'uso_cer'] as $tabla) {
    caso($tabla.'_bloquea', static function () use ($link, $tabla): void {
        $link->exec('INSERT INTO '.$tabla.' VALUES (1,1)');
        bloquear(1, $tabla);
    });
}
caso('ejecucion_automatica_sin_factura_permite', static fn () => permitir());
caso('factura_automatica_enlazada_a_ejecucion_bloquea', static function () use ($link): void {
    $link->exec("INSERT INTO fc_factura VALUES (1,2,'inactivo')");
    $link->exec('INSERT INTO fc_factura_automatica VALUES (1,1,1)');
    bloquear(1, 'fc_factura_automatica');
});
caso('fallo_csd_parcial_revierte_todo', static function (): void {
    $antes = snapshot();
    rechazo(static fn () => baja(1, null, static function (int $id): stdClass {
        csd($id);
        throw new RuntimeException('Fallo sintético posterior a cascada CSD');
    }), 'Fallo sintético');
    exigir(snapshot() === $antes, 'restaura incluso configuraciones retiradas antes de la cascada');
});
caso('fallo_padre_parcial_revierte_todo', static function (): void {
    $antes = snapshot();
    rechazo(static fn () => baja(1, static function (): stdClass {
        padre(1);
        throw new RuntimeException('Fallo sintético posterior a baja padre');
    }), 'Fallo sintético');
    exigir(snapshot() === $antes, 'restaura empresa y toda la cascada');
});
caso('callback_sin_baja_revierte_todo', static function (): void {
    $antes = snapshot();
    rechazo(static fn () => baja(1, static fn (): stdClass => new stdClass()), 'No se completó');
    exigir(snapshot() === $antes, 'callback incompleto no deja bajas parciales');
});
caso('callback_estado_error_revierte_todo', static function (): void {
    $antes = snapshot();
    rechazo(static fn () => baja(1, static function (): stdClass {
        $r = padre(1); errores::$error = true; return $r;
    }), 'No se completó');
    exigir(snapshot() === $antes, 'error global después del DELETE revierte todo');
});
caso('fallo_savepoint_preserva_trabajo_previo', static function () use ($link): void {
    $original = snapshot();
    $link->beginTransaction();
    $link->exec("UPDATE org_empresa SET nota = 'TRABAJO_PREVIO' WHERE id = 2");
    $previo = snapshot();
    rechazo(static fn () => baja(1, static function (): stdClass {
        padre(1); throw new RuntimeException('Fallo sintético savepoint');
    }), 'savepoint');
    exigir($link->inTransaction(), 'transacción del llamador permanece abierta');
    exigir(snapshot() === $previo, 'conserva trabajo previo y restaura la cascada');
    $link->rollBack();
    exigir(snapshot() === $original, 'rollback externo restaura el estado inicial');
});
caso('exito_savepoint_no_confirma_transaccion_ajena', static function () use ($link): void {
    $antes = snapshot();
    $link->beginTransaction();
    baja();
    exigir($link->inTransaction(), 'el helper no confirma la transacción externa');
    exigir(snapshot() === esperado_sin_empresa($antes, 1), 'baja visible solo dentro de transacción');
    $link->rollBack();
    exigir(snapshot() === $antes, 'caller puede revertir toda la baja');
});
caso('reserva_csd_otra_conexion_impide_baja_y_se_libera', static function () use ($segundo): void {
    (new bloqueo_uso_csd($segundo))->ejecutar(1, static function (): stdClass {
        bloquear(1, 'en uso');
        return new stdClass();
    });
    permitir();
});
caso('fk_concurrente_sucursal_no_entra_durante_baja', static function () use ($segundo): void {
    $antes = snapshot();
    baja(1, static function () use ($segundo): stdClass {
        espera_bloqueo(static fn () => $segundo->exec('INSERT INTO org_sucursal VALUES (50,1,0)'));
        return padre(1);
    });
    exigir(snapshot() === esperado_sin_empresa($antes, 1), 'ninguna sucursal huérfana entra durante baja');
});
caso('fk_concurrente_factura_no_entra_durante_baja', static function () use ($segundo): void {
    $antes = snapshot();
    baja(1, null, static function (int $id) use ($segundo): stdClass {
        espera_bloqueo(static fn () => $segundo->exec("INSERT INTO fc_factura VALUES (50,1,'activo')"));
        return csd($id);
    });
    exigir(snapshot() === esperado_sin_empresa($antes, 1), 'la factura concurrente no evade el preflight');
});
caso('referencia_sin_fk_concurrente_no_entra_durante_baja', static function () use ($segundo): void {
    $antes = snapshot();
    baja(1, static function () use ($segundo): stdClass {
        espera_bloqueo(static fn () => $segundo->exec('INSERT INTO uso_convencional VALUES (50,1)'));
        return padre(1);
    });
    exigir(snapshot() === esperado_sin_empresa($antes, 1), 'bloqueo de rango protege referencia sin FK');
});
caso('trigger_delete_bloquea_sin_ejecutarlo', static function () use ($link): void {
    $link->exec("CREATE TRIGGER qa_trigger_empresa BEFORE DELETE ON org_empresa FOR EACH ROW SET @qa_trigger_ejecutado = 1");
    try {
        bloquear(1, 'disparador');
        exigir($link->query('SELECT @qa_trigger_ejecutado')->fetchColumn() === null, 'el trigger jamás se ejecutó');
    } finally { $link->exec('DROP TRIGGER qa_trigger_empresa'); }
});
caso('hija_propia_vacia_myisam_bloquea', static function () use ($link): void {
    $ddl = $link->query('SHOW CREATE TABLE org_logo')->fetch(PDO::FETCH_NUM)[1];
    $link->exec('DROP TABLE org_logo');
    $link->exec('CREATE TABLE org_logo (id INT PRIMARY KEY, org_empresa_id INT NOT NULL) ENGINE=MyISAM');
    try {
        bloquear(1, 'reversión');
        exigir((int)$link->query('SELECT COUNT(*) FROM org_logo')->fetchColumn() === 0, 'la hija vacía no permite omitir la validación del motor');
    } finally {
        $link->exec('DROP TABLE org_logo');
        $link->exec($ddl);
    }
});
caso('foreign_key_checks_inactivo_bloquea', static function () use ($link): void {
    $link->exec('SET SESSION foreign_key_checks = 0');
    try { bloquear(1, 'relaciones activas'); }
    finally { $link->exec('SET SESSION foreign_key_checks = 1'); }
});
caso('read_committed_bloquea', static function () use ($link): void {
    $link->exec('SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED');
    try { bloquear(1, 'aislamiento'); }
    finally { $link->exec('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ'); }
});
caso('nomina_rfc_emisor_variante_sin_csd_bloquea', static function () use ($link): void {
    $link->exec('ALTER TABLE fc_cfdi_sellado_nomina ADD emisor_rfc VARCHAR(30) NULL');
    try {
        $link->exec("INSERT INTO org_empresa VALUES (3,'FICTICIO_EMPRESA_3','SIN_CSD')");
        $link->exec("INSERT INTO fc_cfdi_sellado_nomina VALUES (1,999,'CERT_ANTIGUO','SAT_AJENO','inactivo',' ficticio_empresa_3 ')");
        bloquear(3, 'nóminas');
    } finally { $link->exec('ALTER TABLE fc_cfdi_sellado_nomina DROP COLUMN emisor_rfc'); }
});
caso('nomina_layout_emisor_variante_bloquea', static function () use ($link): void {
    $link->exec('ALTER TABLE fc_layout_nom ADD org_empresa_id INT NULL');
    try {
        $link->exec('INSERT INTO fc_layout_nom VALUES (1,10,1)');
        $link->exec('INSERT INTO fc_row_layout VALUES (1,1)');
        $link->exec('INSERT INTO fc_row_nomina VALUES (1,1,10)');
        bloquear(1, 'nóminas');
    } finally { $link->exec('ALTER TABLE fc_layout_nom DROP COLUMN org_empresa_id'); }
});
caso('modelo_wrapper_exito_restauracion_flag', static function () use ($link): void {
    $antes = snapshot();
    $modelo = new org_empresa($link);
    $r = $modelo->elimina_bd(1);
    exigir($r instanceof stdClass && $r->registro_id === 1, 'wrapper real devuelve el resultado del padre');
    exigir(PadreEmpresa::$llamadas === 1 && PadreEmpresa::$flag_durante_baja === false, 'padre recibe desactivación dependientes apagada');
    exigir($modelo->desactiva_dependientes === true, 'restaura el flag original');
    exigir(snapshot() === esperado_sin_empresa($antes, 1), 'wrapper real aplica toda la cascada');
});
caso('modelo_wrapper_bloqueo_no_llama_padre', static function () use ($link): void {
    $link->exec("INSERT INTO fc_factura VALUES (1,1,'inactivo')");
    $antes = snapshot();
    $modelo = new org_empresa($link);
    $modelo->desactiva_dependientes = false;
    $r = $modelo->elimina_bd(1);
    exigir(is_array($r) && errores::$error && str_contains($r['mensaje'], 'facturas'), 'convierte rechazo fiscal en error de interfaz');
    exigir(PadreEmpresa::$llamadas === 0 && $modelo->desactiva_dependientes === false, 'no llama padre y conserva flag false');
    exigir(snapshot() === $antes, 'wrapper conserva todos los datos ante bloqueo');
});
caso('modelo_wrapper_fallo_padre_revierte', static function () use ($link): void {
    $antes = snapshot();
    $modelo = new org_empresa($link);
    PadreEmpresa::$fallo = 'error';
    $r = $modelo->elimina_bd(1);
    exigir(is_array($r) && errores::$error, 'propaga error del padre');
    exigir($modelo->desactiva_dependientes === true && PadreEmpresa::$llamadas === 1, 'finally restaura el flag');
    exigir(snapshot() === $antes, 'fallo del padre revierte datos completos');
});
caso('controlador_real_enruta_baja_al_modelo_local', static function () use ($link): void {
    $html = new \gamboamartin\template_1\html();
    $paths = (object)['fixture' => true];
    $c = new controlador_org_empresa($link, $html, $paths);
    exigir($c->modelo instanceof org_empresa, 'constructor real sustituye el modelo por el local protegido');
    exigir($c->html_recibido === $html && $c->paths_recibidos === $paths, 'preserva argumentos del constructor padre');
    $link->exec("INSERT INTO fc_nota_credito VALUES (1,1,'activo')");
    $antes = snapshot();
    $r = $c->elimina_bd(1);
    exigir(is_array($r) && str_contains($r['mensaje'], 'notas de crédito'), 'ruta heredada ejecuta bloqueo fiscal del modelo local');
    exigir(PadreEmpresa::$llamadas === 0 && snapshot() === $antes, 'ruta de controlador conserva empresa y asociaciones');
});

$registro = json_decode(file_get_contents(__DIR__.'/registro.json'), true, 512, JSON_THROW_ON_ERROR);
exigir($casos === $registro['casos'], 'registro coincide con nombres y orden de casos realmente ejecutados');
exigir(count($casos) === $registro['casos_previstos'], 'no faltan casos registrados');
exigir(!$link->inTransaction() && !$segundo->inTransaction(), 'ninguna transacción de prueba permanece abierta');
echo 'PASS empresa_baja: '.count($casos).' casos, '.$assertions." assertions, ".count($tablas)." tablas sintéticas.\n";
