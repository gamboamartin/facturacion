<?php
declare(strict_types=1);

namespace gamboamartin\facturacion\models;

use PDO;
use RuntimeException;

/**
 * Delimita qué filas puede retirar una baja de CSD y rechaza dependencias externas.
 * Todo se consulta y escribe en la misma transacción que bloquea las filas destino.
 */
final class referencias_baja_csd
{
    private const VINCULOS = [
        'fc_csd' => [
            'fc_cer_csd' => 'fc_csd_id',
            'fc_key_csd' => 'fc_csd_id',
            'fc_csd_etapa' => 'fc_csd_id',
        ],
        'fc_cer_csd' => ['fc_cer_pem' => 'fc_cer_csd_id'],
        'fc_key_csd' => ['fc_key_pem' => 'fc_key_csd_id'],
        'fc_cer_pem' => [],
        'fc_key_pem' => [],
        'fc_csd_etapa' => [],
    ];

    public function __construct(private readonly PDO $link)
    {
    }

    /**
     * Retira primero referencias PEM, luego CER/KEY y etapas propias. La baja padre
     * conserva el contrato y la bitácora del ORM; todos los pasos se revierten juntos.
     *
     * @param callable(): \stdClass $elimina_csd
     */
    public function eliminar(int $id, callable $elimina_csd): \stdClass
    {
        return (new bloqueo_uso_csd($this->link))->ejecutar(
            $id,
            fn (): \stdClass => $this->eliminar_reservado($id, $elimina_csd)
        );
    }

    private function eliminar_reservado(int $id, callable $elimina_csd): \stdClass
    {
        $propia = !$this->link->inTransaction();
        $punto = 'fc_csd_baja_'.bin2hex(random_bytes(8));
        if ($propia) {
            if (!$this->link->beginTransaction()) {
                throw new RuntimeException('No se pudo comenzar la baja de CSD.');
            }
        } else {
            $this->consulta('SAVEPOINT '.$punto);
        }

        try {
            $destinos = $this->inventario($id);
            foreach (array_reverse(array_keys(self::VINCULOS)) as $tabla) {
                if ($tabla === 'fc_csd') {
                    continue;
                }
                foreach ($destinos[$tabla] as $destino) {
                    if ($this->consulta('DELETE FROM '.$tabla.' WHERE id = ?', [$destino])->rowCount() !== 1) {
                        throw new RuntimeException('No se pudo retirar una referencia propia del CSD.');
                    }
                }
            }

            $resultado = $elimina_csd();
            if (!$resultado instanceof \stdClass
                || $this->consulta('SELECT id FROM fc_csd WHERE id = ?', [$id])->fetchColumn() !== false) {
                throw new RuntimeException('No se completó la baja del CSD.');
            }
            if ($propia) {
                if (!$this->link->commit()) {
                    throw new RuntimeException('No se pudo confirmar la baja de CSD.');
                }
            } else {
                $this->consulta('RELEASE SAVEPOINT '.$punto);
            }
            return $resultado;
        } catch (\Throwable $error) {
            if ($this->link->inTransaction()) {
                if ($propia) {
                    $this->link->rollBack();
                } else {
                    $this->consulta('ROLLBACK TO SAVEPOINT '.$punto);
                    $this->consulta('RELEASE SAVEPOINT '.$punto);
                }
            }
            throw $error;
        }
    }

    public function inventario(int $id): array
    {
        if (!$this->link->inTransaction() || $id <= 0) {
            throw new RuntimeException('La baja de CSD requiere una transacción y un identificador válido.');
        }

        $tablas = array_keys(self::VINCULOS);
        $motores = $this->consulta(
            'SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES '
            .'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN (?,?,?,?,?,?)',
            $tablas
        )->fetchAll(PDO::FETCH_KEY_PAIR);
        if ((int)$this->consulta('SELECT @@SESSION.foreign_key_checks')->fetchColumn() !== 1
            || count($motores) !== count($tablas)
            || array_filter($motores, static fn ($motor): bool => strcasecmp((string)$motor, 'InnoDB') !== 0)) {
            throw new RuntimeException('No se pueden garantizar las relaciones ni la reversión de la baja de CSD.');
        }

        if ($this->consulta(
            'SELECT 1 FROM information_schema.TRIGGERS WHERE EVENT_OBJECT_SCHEMA = DATABASE() '
            ."AND EVENT_MANIPULATION = 'DELETE' AND EVENT_OBJECT_TABLE IN (?,?,?,?,?,?) LIMIT 1",
            $tablas
        )->fetchColumn() !== false) {
            throw new RuntimeException('La baja de CSD está bloqueada por un disparador de base de datos.');
        }

        if ($this->consulta('SELECT id FROM fc_csd WHERE id = ? FOR UPDATE', [$id])->fetchColumn() === false) {
            throw new RuntimeException('El CSD no existe.');
        }

        $this->validar_nominas($id);

        $destinos = ['fc_csd' => [$id]];
        foreach (self::VINCULOS as $tabla => $vinculos) {
            $ids = $destinos[$tabla] ?? [];
            foreach ($vinculos as $hija => $columna) {
                $destinos[$hija] = [];
                foreach ($ids as $padre) {
                    $filas = $this->consulta(
                        'SELECT id FROM '.$hija.' WHERE '.$columna.' = ? ORDER BY id FOR UPDATE',
                        [$padre]
                    )->fetchAll(PDO::FETCH_COLUMN);
                    array_push($destinos[$hija], ...array_map('intval', $filas));
                }
            }
            if ($ids !== []) {
                $this->validar_dependencias($tabla, $vinculos, $ids);
            }
        }
        return $destinos;
    }

    /** La nómina conserva la identidad del certificado emisor en el CFDI sellado. */
    private function validar_nominas(int $id): void
    {
        $tabla = $this->consulta(
            'SELECT ENGINE, TABLE_TYPE FROM information_schema.TABLES '
            .'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            ['fc_cfdi_sellado_nomina']
        )->fetch(PDO::FETCH_ASSOC);
        // Sin este módulo instalado no hay CFDI de nómina almacenados en él.
        if ($tabla === false) {
            return;
        }
        if ($tabla['TABLE_TYPE'] !== 'BASE TABLE' || strcasecmp((string)$tabla['ENGINE'], 'InnoDB') !== 0) {
            throw new RuntimeException('No se pueden verificar las nóminas asociadas al CSD.');
        }
        // No usar el certificado del SAT ni filtrar cancelados/inactivos. El número
        // emisor identifica el uso histórico aunque cambie el CSD configurado.
        if ($this->consulta(
            'SELECT n.id FROM fc_cfdi_sellado_nomina AS n '
            .'JOIN fc_csd AS c ON n.comprobante_no_certificado = c.no_certificado '
            .'WHERE c.id = ? LIMIT 1 FOR UPDATE',
            [$id]
        )->fetchColumn() !== false) {
            throw new RuntimeException('El CSD tiene nóminas asociadas y no se puede eliminar.');
        }
    }

    private function validar_dependencias(string $padre, array $permitidas, array $ids): void
    {
        $foraneas = $this->consulta(
            'SELECT TABLE_SCHEMA, TABLE_NAME, CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_COLUMN_NAME, '
            .'TABLE_SCHEMA = DATABASE() AS esquema_actual '
            .'FROM information_schema.KEY_COLUMN_USAGE '
            .'WHERE REFERENCED_TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME = ? '
            .'ORDER BY TABLE_SCHEMA, TABLE_NAME, CONSTRAINT_NAME, ORDINAL_POSITION',
            [$padre]
        )->fetchAll(PDO::FETCH_ASSOC);
        $grupos = [];
        foreach ($foraneas as $fk) {
            $clave = json_encode([$fk['TABLE_SCHEMA'], $fk['TABLE_NAME'], $fk['CONSTRAINT_NAME']], JSON_THROW_ON_ERROR);
            $grupos[$clave][] = $fk;
        }
        foreach ($grupos as $grupo) {
            $fk = $grupo[0];
            if ((int)$fk['esquema_actual'] === 1 && count($grupo) === 1 && $fk['REFERENCED_COLUMN_NAME'] === 'id'
                && ($permitidas[$fk['TABLE_NAME']] ?? null) === $fk['COLUMN_NAME']) {
                continue;
            }

            $union = implode(' AND ', array_map(
                fn (array $campo): string => 'h.'.$this->identificador($campo['COLUMN_NAME'])
                    .' = p.'.$this->identificador($campo['REFERENCED_COLUMN_NAME']),
                $grupo
            ));
            $tabla = $this->identificador($fk['TABLE_SCHEMA']).'.'.$this->identificador($fk['TABLE_NAME']);
            foreach ($ids as $id) {
                $this->rechazar_si_existe(
                    'SELECT 1 FROM '.$this->identificador($padre).' AS p JOIN '.$tabla.' AS h ON '.$union
                    .' WHERE p.id = ? LIMIT 1 FOR UPDATE',
                    $id
                );
            }
        }

        // También protege los vínculos de aplicación por convención que no tengan FK física.
        $columna = $padre.'_id';
        $convencionales = $this->consulta(
            'SELECT c.TABLE_NAME FROM information_schema.COLUMNS c '
            .'JOIN information_schema.TABLES t ON t.TABLE_SCHEMA = c.TABLE_SCHEMA AND t.TABLE_NAME = c.TABLE_NAME '
            .'WHERE c.TABLE_SCHEMA = DATABASE() AND c.COLUMN_NAME = ? AND t.TABLE_TYPE = ? ORDER BY c.TABLE_NAME',
            [$columna, 'BASE TABLE']
        )->fetchAll(PDO::FETCH_COLUMN);
        foreach ($convencionales as $tabla) {
            if (($permitidas[$tabla] ?? null) === $columna) {
                continue;
            }
            foreach ($ids as $id) {
                $this->rechazar_si_existe(
                    'SELECT 1 FROM '.$this->identificador($tabla).' WHERE '.$this->identificador($columna)
                    .' = ? LIMIT 1 FOR UPDATE',
                    $id
                );
            }
        }
    }

    private function rechazar_si_existe(string $sql, int $id): void
    {
        if ($this->consulta($sql, [$id])->fetchColumn() !== false) {
            throw new RuntimeException('El CSD tiene una referencia que no corresponde a sus archivos.');
        }
    }

    private function identificador(string $nombre): string
    {
        return chr(96).str_replace(chr(96), chr(96).chr(96), $nombre).chr(96);
    }

    private function consulta(string $sql, array $valores = []): \PDOStatement
    {
        $consulta = $this->link->prepare($sql);
        if ($consulta === false || !$consulta->execute($valores)) {
            throw new RuntimeException('No se pudieron verificar las referencias del CSD.');
        }
        return $consulta;
    }
}
