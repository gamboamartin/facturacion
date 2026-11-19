<?php
declare(strict_types=1);

use gamboamartin\facturacion\models\referencias_baja_csd;

require __DIR__.'/../../orm/referencias_baja_csd.php';

if ($argc !== 2 || !preg_match('~^/tmp/qa-baja-csd-[A-Za-z0-9]+/mysql\.sock$~D', $argv[1]) || !is_file($argv[1].'.fixture')) {
    throw new RuntimeException('Solo se permite el socket local efímero de esta prueba.');
}
$link = new PDO('mysql:unix_socket='.$argv[1].';dbname=qa_baja_csd', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);
if ($link->query('SELECT DATABASE()')->fetchColumn() !== 'qa_baja_csd'
    || (int)$link->query('SELECT @@skip_networking')->fetchColumn() !== 1) {
    throw new RuntimeException('Aislamiento de prueba no acreditado.');
}

foreach ([
    'CREATE TABLE fc_csd (id INT PRIMARY KEY) ENGINE=InnoDB',
    'CREATE TABLE doc_documento (id INT PRIMARY KEY) ENGINE=InnoDB',
    'CREATE TABLE fc_cer_csd (id INT PRIMARY KEY, fc_csd_id INT NOT NULL REFERENCES fc_csd(id), doc_documento_id INT NOT NULL REFERENCES doc_documento(id)) ENGINE=InnoDB',
    'CREATE TABLE fc_key_csd (id INT PRIMARY KEY, fc_csd_id INT NOT NULL REFERENCES fc_csd(id), doc_documento_id INT NOT NULL REFERENCES doc_documento(id)) ENGINE=InnoDB',
    'CREATE TABLE fc_cer_pem (id INT PRIMARY KEY, fc_cer_csd_id INT NOT NULL REFERENCES fc_cer_csd(id), doc_documento_id INT NOT NULL REFERENCES doc_documento(id)) ENGINE=InnoDB',
    'CREATE TABLE fc_key_pem (id INT PRIMARY KEY, fc_key_csd_id INT NOT NULL REFERENCES fc_key_csd(id), doc_documento_id INT NOT NULL REFERENCES doc_documento(id)) ENGINE=InnoDB',
    'CREATE TABLE fc_csd_etapa (id INT PRIMARY KEY, fc_csd_id INT NOT NULL REFERENCES fc_csd(id)) ENGINE=InnoDB',
    'CREATE TABLE fc_uuid (id INT PRIMARY KEY, fc_csd_id INT NOT NULL, FOREIGN KEY (fc_csd_id) REFERENCES fc_csd(id) ON DELETE CASCADE) ENGINE=InnoDB',
    'CREATE TABLE fc_conf_automatico (id INT PRIMARY KEY, fc_csd_id INT NOT NULL) ENGINE=InnoDB',
    'CREATE TABLE fc_uso_cer (id INT PRIMARY KEY, referencia_cer INT, FOREIGN KEY (referencia_cer) REFERENCES fc_cer_csd(id) ON DELETE SET NULL) ENGINE=InnoDB',
    'INSERT INTO fc_csd VALUES (1),(2),(3),(4),(5),(6)',
    'INSERT INTO doc_documento VALUES (10),(11),(12),(13)',
    'INSERT INTO fc_cer_csd VALUES (101,1,10),(201,2,10),(301,3,10),(401,4,10),(501,5,10),(601,6,10)',
    'INSERT INTO fc_key_csd VALUES (102,1,11),(202,2,11),(302,3,11),(402,4,11),(502,5,11),(602,6,11)',
    'INSERT INTO fc_cer_pem VALUES (103,101,12),(203,201,12),(303,301,12),(403,401,12),(503,501,12),(603,601,12)',
    'INSERT INTO fc_key_pem VALUES (104,102,13),(204,202,13),(304,302,13),(404,402,13),(504,502,13),(604,602,13)',
    'INSERT INTO fc_csd_etapa VALUES (1,2)',
    'INSERT INTO fc_uuid VALUES (1,3)',
    'INSERT INTO fc_conf_automatico VALUES (1,4)',
    'INSERT INTO fc_uso_cer VALUES (1,501)',
] as $sql) {
    $link->exec($sql);
}

function exigir(bool $ok, string $caso): void
{
    if (!$ok) {
        throw new RuntimeException('No pasó: '.$caso);
    }
}

$guard = new referencias_baja_csd($link);
$link->beginTransaction();
try {
    $inventario = $guard->inventario(1);
    $esperado = [
        'fc_csd' => [1], 'fc_cer_csd' => [101], 'fc_key_csd' => [102],
        'fc_cer_pem' => [103], 'fc_key_pem' => [104], 'fc_csd_etapa' => [],
    ];
    ksort($inventario);
    ksort($esperado);
    exigir($inventario === $esperado, 'inventario de exactamente cuatro vínculos del CSD con documentos compartidos');
    exigir($guard->inventario(2)['fc_csd_etapa'] === [1], 'etapa propia en inventario');
    foreach ([3 => 'negocio con cascada', 4 => 'negocio sin FK',
        5 => 'SET NULL de un hijo con nombre no convencional', 999 => 'CSD inexistente'] as $id => $caso) {
        $bloqueado = false;
        try {
            $guard->inventario($id);
        } catch (RuntimeException $error) {
            $bloqueado = in_array($error->getMessage(), [
                'El CSD tiene una referencia que no corresponde a sus archivos.', 'El CSD no existe.'
            ], true);
        }
        exigir($bloqueado, $caso);
    }
    foreach (['fc_csd' => 6, 'fc_cer_csd' => 6, 'fc_key_csd' => 6, 'fc_cer_pem' => 6, 'fc_key_pem' => 6,
        'doc_documento' => 4, 'fc_csd_etapa' => 1, 'fc_uuid' => 1, 'fc_conf_automatico' => 1, 'fc_uso_cer' => 1] as $tabla => $cantidad) {
        exigir((int)$link->query('SELECT COUNT(*) FROM '.$tabla)->fetchColumn() === $cantidad, 'sin cambios en '.$tabla);
    }
} finally {
    $link->rollBack();
}

$baja = static function (int $id) use ($link): stdClass {
    $consulta = $link->prepare('DELETE FROM fc_csd WHERE id = ?');
    $consulta->execute([$id]);
    return (object)['registro_id' => $id];
};
$antesNomina = $link->query('SELECT (SELECT COUNT(*) FROM fc_csd) + (SELECT COUNT(*) FROM fc_cer_csd)
    + (SELECT COUNT(*) FROM fc_key_csd) + (SELECT COUNT(*) FROM fc_cer_pem) + (SELECT COUNT(*) FROM fc_key_pem)
    + (SELECT COUNT(*) FROM fc_csd_etapa)')->fetchColumn();
$padreEjecutado = false;
require __DIR__.'/pac_ficticio.php';
\config\pac::$fc_csd_nomina_id = 2;
try {
    $guard->eliminar(2, static function () use ($baja, &$padreEjecutado): stdClass {
        $padreEjecutado = true;
        return $baja(2);
    });
    throw new RuntimeException('La baja de CSD designado para nómina no fue bloqueada.');
} catch (RuntimeException $error) {
    exigir($error->getMessage() === 'El CSD está asignado a nómina y no se puede eliminar.', 'bloqueo por nómina configurada');
}
exigir(!$padreEjecutado && !$link->inTransaction(), 'nómina rechazada antes de tocar transacción o modelo padre');
exigir($antesNomina === $link->query('SELECT (SELECT COUNT(*) FROM fc_csd) + (SELECT COUNT(*) FROM fc_cer_csd)
    + (SELECT COUNT(*) FROM fc_key_csd) + (SELECT COUNT(*) FROM fc_cer_pem) + (SELECT COUNT(*) FROM fc_key_pem)
    + (SELECT COUNT(*) FROM fc_csd_etapa)')->fetchColumn(), 'nómina preserva referencias y etapas');
\config\pac::$fc_csd_nomina_id = 0;
exigir($guard->eliminar(2, fn (): stdClass => $baja(2))->registro_id === 2, 'baja física con etapa propia');
foreach (['fc_csd' => 5, 'fc_cer_csd' => 5, 'fc_key_csd' => 5, 'fc_cer_pem' => 5, 'fc_key_pem' => 5,
    'doc_documento' => 4, 'fc_csd_etapa' => 0, 'fc_uuid' => 1, 'fc_conf_automatico' => 1, 'fc_uso_cer' => 1] as $tabla => $cantidad) {
    exigir((int)$link->query('SELECT COUNT(*) FROM '.$tabla)->fetchColumn() === $cantidad, 'alcance tras baja '.$tabla);
}
exigir((int)$link->query('SELECT doc_documento_id FROM fc_cer_csd WHERE id = 101')->fetchColumn() === 10, 'documento compartido sigue vinculado');

foreach ([false, true] as $ajena) {
    if ($ajena) {
        $link->beginTransaction();
        $link->exec('INSERT INTO doc_documento VALUES (20)');
    }
    try {
        $rechazo = false;
        try {
            $guard->eliminar(6, static function () use ($baja): stdClass {
                $baja(6);
                throw new RuntimeException('Fallo simulado después de borrar el padre.');
            });
        } catch (RuntimeException $error) {
            $rechazo = $error->getMessage() === 'Fallo simulado después de borrar el padre.';
        }
        exigir($rechazo, 'fallo inducido identificado');
        foreach (['fc_csd' => 6, 'fc_cer_csd' => 601, 'fc_key_csd' => 602, 'fc_cer_pem' => 603, 'fc_key_pem' => 604] as $tabla => $id) {
            exigir((int)$link->query('SELECT COUNT(*) FROM '.$tabla.' WHERE id = '.$id)->fetchColumn() === 1, 'reversión '.$tabla);
        }
        exigir($link->inTransaction() === $ajena, 'propiedad de la transacción');
        if ($ajena) {
            exigir((int)$link->query('SELECT COUNT(*) FROM doc_documento WHERE id = 20')->fetchColumn() === 1, 'trabajo del llamador intacto');
        }
    } finally {
        if ($ajena) {
            $link->rollBack();
        }
    }
}
echo "PASS: baja física exacta, dependencias de negocio y nómina, documentos compartidos y rollback propio/anidado\n";
