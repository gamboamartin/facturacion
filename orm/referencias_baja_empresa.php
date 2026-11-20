<?php
declare(strict_types=1);

namespace gamboamartin\facturacion\models;

use gamboamartin\errores\errores;
use PDO;
use RuntimeException;
use stdClass;

/** Baja acotada a asociaciones propias; nunca borra documentos, archivos ni catálogos. */
final class referencias_baja_empresa
{
    // Orden de padres a hijos. Las configuraciones con dos propietarios deben pertenecer
    // por ambos vínculos a esta empresa. org_dependencia es solo un enlace entre puestos:
    // retirar su vínculo no elimina el puesto externo ni sus demás relaciones.
    private const PROPIAS = [
        'org_empresa' => [],
        'org_sucursal' => ['org_empresa_id' => 'org_empresa'],
        'org_logo' => ['org_empresa_id' => 'org_empresa'],
        'org_porcentaje_act_economica' => ['org_empresa_id' => 'org_empresa'],
        'org_representante_asignado' => ['org_empresa_id' => 'org_empresa'],
        'org_departamento' => ['org_empresa_id' => 'org_empresa'],
        'org_puesto' => ['org_departamento_id' => 'org_departamento'],
        'org_dependencia' => ['org_puesto_jefe_id' => 'org_puesto', 'org_puesto_empleado_id' => 'org_puesto'],
        'org_ejecuta' => ['org_puesto_id' => 'org_puesto'],
        'bn_sucursal_cuenta' => ['org_sucursal_id' => 'org_sucursal'],
        'fc_csd' => ['org_sucursal_id' => 'org_sucursal'],
        'fc_conf_automatico' => ['fc_csd_id' => 'fc_csd'],
        'fc_conf_aut_producto' => ['fc_conf_automatico_id' => 'fc_conf_automatico', 'fc_csd_id' => 'fc_csd'],
        'fc_ejecucion_automatica' => ['fc_conf_automatico_id' => 'fc_conf_automatico'],
        'fc_cer_csd' => ['fc_csd_id' => 'fc_csd'],
        'fc_key_csd' => ['fc_csd_id' => 'fc_csd'],
        'fc_csd_etapa' => ['fc_csd_id' => 'fc_csd'],
        'fc_cer_pem' => ['fc_cer_csd_id' => 'fc_cer_csd'],
        'fc_key_pem' => ['fc_key_csd_id' => 'fc_key_csd'],
    ];
    private const ARCHIVOS_CSD = ['fc_cer_csd', 'fc_key_csd', 'fc_csd_etapa', 'fc_cer_pem', 'fc_key_pem'];
    private const FISCALES = [
        'fc_factura' => 'facturas',
        'fc_complemento_pago' => 'complementos de pago',
        'fc_nota_credito' => 'notas de crédito',
        'fc_cfdi_sellado_nomina' => 'nóminas',
        'fc_row_nomina' => 'documentos de nómina',
        'fc_uuid' => 'documentos fiscales externos',
        'fc_factura_automatica' => 'facturas automáticas',
    ];

    private array $tablas = [];
    private array $columnas = [];

    public function __construct(private readonly PDO $link)
    {
    }

    /**
     * El callback padre conserva la bitácora del ORM. El callback CSD opcional recibe
     * su id; por defecto utiliza el modelo real y su cascada validada de certificados.
     * @param callable(): stdClass $elimina_empresa
     * @param null|callable(int): stdClass $elimina_csd
     */
    public function eliminar(int $id, callable $elimina_empresa, ?callable $elimina_csd = null): stdClass
    {
        if ($id <= 0) {
            throw new RuntimeException('El identificador de empresa debe ser positivo.');
        }
        $propia = !$this->link->inTransaction();
        $punto = 'org_empresa_baja_'.bin2hex(random_bytes(8));
        if ($propia) {
            if (!$this->link->beginTransaction()) {
                throw new RuntimeException('No se pudo comenzar la baja de la empresa.');
            }
        } else {
            $this->consulta('SAVEPOINT '.$punto);
        }
        try {
            $this->cargar_metadata();
            foreach (['org_empresa', 'org_sucursal', 'fc_csd'] as $tabla) {
                $this->validar_tabla($tabla);
            }
            if ($this->consulta('SELECT id FROM org_empresa WHERE id = ? FOR UPDATE', [$id])->fetchColumn() === false) {
                throw new RuntimeException('La empresa no existe.');
            }
            $sucursales = $this->ids('org_sucursal', 'org_empresa_id', [$id]);
            // Antes de bloquear filas CSD se toma el mismo cerrojo usado por timbrado.
            $csds = $this->ids('fc_csd', 'org_sucursal_id', $sucursales, false);
            $resultado = $this->con_bloqueos_csd($csds, 0, function () use ($id, $csds, $elimina_empresa, $elimina_csd): stdClass {
                $destinos = $this->inventario($id);
                if ($destinos['fc_csd'] !== $csds) {
                    throw new RuntimeException('Cambió el conjunto de CSD de la empresa; intente nuevamente.');
                }
                $this->validar_fiscales($id, $destinos);
                $this->validar_dependencias($destinos);
                // Toda la prevalidación termina antes del primer DELETE. Las configuraciones
                // propias salen antes del CSD para que su cascada no las trate como externas.
                foreach (array_reverse(array_keys(self::PROPIAS)) as $tabla) {
                    if ($tabla === 'org_empresa' || in_array($tabla, self::ARCHIVOS_CSD, true)) {
                        continue;
                    }
                    foreach ($destinos[$tabla] as $destino) {
                        if ($tabla === 'fc_csd') {
                            $baja = $elimina_csd === null
                                ? (new fc_csd($this->link))->elimina_bd($destino)
                                : $elimina_csd($destino);
                            $this->validar_resultado($tabla, $destino, $baja);
                        } else {
                            $this->borrar($tabla, $destino);
                        }
                    }
                }
                $resultado = $elimina_empresa();
                $this->validar_resultado('org_empresa', $id, $resultado);
                foreach ($destinos as $tabla => $ids) {
                    foreach ($ids as $destino) {
                        if ($this->consulta('SELECT id FROM '.$this->q($tabla).' WHERE id = ?', [$destino])->fetchColumn() !== false) {
                            throw new RuntimeException('Quedó una dependencia propia sin retirar en '.$tabla.'.');
                        }
                    }
                }
                return $resultado;
            });
            if ($propia) {
                if (!$this->link->commit()) {
                    throw new RuntimeException('No se pudo confirmar la baja de la empresa.');
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

    private function con_bloqueos_csd(array $ids, int $posicion, callable $operacion): stdClass
    {
        if (!isset($ids[$posicion])) {
            return $operacion();
        }
        return (new bloqueo_uso_csd($this->link))->ejecutar(
            $ids[$posicion],
            fn (): stdClass => $this->con_bloqueos_csd($ids, $posicion + 1, $operacion)
        );
    }

    private function cargar_metadata(): void
    {
        $this->tablas = [];
        $this->columnas = [];
        foreach ($this->consulta(
            'SELECT TABLE_NAME, ENGINE, TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()'
        )->fetchAll(PDO::FETCH_ASSOC) as $tabla) {
            $this->tablas[$tabla['TABLE_NAME']] = $tabla;
        }
        foreach ($this->consulta(
            'SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()'
        )->fetchAll(PDO::FETCH_ASSOC) as $columna) {
            $this->columnas[$columna['TABLE_NAME']][$columna['COLUMN_NAME']] = true;
        }
        $aislamiento = $this->consulta("SHOW SESSION VARIABLES LIKE '%isolation'")->fetchAll(PDO::FETCH_KEY_PAIR);
        $nivel = strtoupper((string)($aislamiento['transaction_isolation'] ?? $aislamiento['tx_isolation'] ?? ''));
        if ((int)$this->consulta('SELECT @@SESSION.foreign_key_checks')->fetchColumn() !== 1
            || !in_array($nivel, ['REPEATABLE-READ', 'SERIALIZABLE'], true)) {
            throw new RuntimeException('La baja requiere relaciones activas y aislamiento REPEATABLE READ o SERIALIZABLE.');
        }
    }

    private function inventario(int $id): array
    {
        $destinos = ['org_empresa' => [$id]];
        foreach (self::PROPIAS as $tabla => $padres) {
            if ($tabla === 'org_empresa') {
                continue;
            }
            $destinos[$tabla] = [];
            foreach ($padres as $columna => $padre) {
                if ($this->tiene($tabla, $columna)) {
                    if ($destinos[$padre] !== []) {
                        $this->validar_tabla($tabla);
                    }
                    array_push($destinos[$tabla], ...$this->ids($tabla, $columna, $destinos[$padre]));
                }
            }
            $destinos[$tabla] = array_values(array_unique($destinos[$tabla]));
            sort($destinos[$tabla], SORT_NUMERIC);
            if ($destinos[$tabla] !== []) {
                $this->validar_tabla($tabla);
                foreach ($padres as $columna => $padre) {
                    if (!$this->tiene($tabla, $columna)) {
                        continue;
                    }
                    foreach ($destinos[$tabla] as $destino) {
                        $propietario = $this->consulta('SELECT '.$this->q($columna).' FROM '.$this->q($tabla)
                            .' WHERE id = ? FOR UPDATE', [$destino])->fetchColumn();
                        if ($tabla !== 'org_dependencia' && (int)$propietario > 0
                            && !in_array((int)$propietario, $destinos[$padre], true)) {
                            throw new RuntimeException('La dependencia '.$tabla.' está compartida con otra empresa.');
                        }
                    }
                }
            }
        }
        if ($destinos['fc_csd'] !== []) {
            foreach (self::ARCHIVOS_CSD as $tabla) {
                $this->validar_tabla($tabla);
            }
        }
        foreach ($destinos as $tabla => $ids) {
            $afectada = $ids !== [] || ($destinos['fc_csd'] !== [] && in_array($tabla, self::ARCHIVOS_CSD, true));
            if ($afectada && $this->consulta(
                "SELECT 1 FROM information_schema.TRIGGERS WHERE EVENT_OBJECT_SCHEMA = DATABASE() "
                ."AND EVENT_MANIPULATION = 'DELETE' AND EVENT_OBJECT_TABLE = ? LIMIT 1", [$tabla]
            )->fetchColumn() !== false) {
                throw new RuntimeException('No se puede garantizar la baja por un disparador en '.$tabla.'.');
            }
        }
        return $destinos;
    }

    private function validar_fiscales(int $id, array $destinos): void
    {
        if ($destinos['fc_csd'] !== [] && isset($this->tablas['fc_cfdi_sellado_nomina'])
            && (!$this->tiene('fc_cfdi_sellado_nomina', 'comprobante_no_certificado')
                || !$this->tiene('fc_csd', 'no_certificado'))) {
            throw new RuntimeException('No se puede verificar el certificado emisor de las nóminas.');
        }
        $rfc = $this->tiene('org_empresa', 'rfc')
            ? trim((string)$this->consulta('SELECT rfc FROM org_empresa WHERE id = ? FOR UPDATE', [$id])->fetchColumn()) : '';
        foreach (self::FISCALES as $tabla => $tipo) {
            if (!isset($this->tablas[$tabla])) {
                continue;
            }
            $this->validar_tabla($tabla);
            foreach (['org_empresa', 'org_sucursal', 'fc_csd'] as $propietario) {
                if ($this->tiene($tabla, $propietario.'_id') && $this->ids($tabla, $propietario.'_id', $destinos[$propietario]) !== []) {
                    $this->fiscal($tabla, $tipo);
                }
            }
            // Identidad del EMISOR histórico, nunca RFC de cliente/receptor ni CSD configurado vigente.
            if ($rfc !== '' && $this->tiene($tabla, 'emisor_rfc') && $this->consulta(
                'SELECT 1 FROM '.$this->q($tabla).' WHERE UPPER(TRIM(emisor_rfc)) = UPPER(?) LIMIT 1 FOR UPDATE', [$rfc]
            )->fetchColumn() !== false) {
                $this->fiscal($tabla, $tipo);
            }
        }
        if ($this->tiene('fc_cfdi_sellado_nomina', 'comprobante_no_certificado')
            && $this->tiene('fc_csd', 'no_certificado')) {
            foreach ($destinos['fc_csd'] as $csd) {
                if ($this->consulta('SELECT 1 FROM fc_cfdi_sellado_nomina n JOIN fc_csd c '
                    .'ON n.comprobante_no_certificado = c.no_certificado WHERE c.id = ? LIMIT 1 FOR UPDATE', [$csd]
                )->fetchColumn() !== false) {
                    $this->fiscal('fc_cfdi_sellado_nomina', 'nóminas');
                }
            }
        }
        // Algunas instalaciones guardan el emisor en el layout o en su renglón.
        // com_sucursal identifica al cliente y no constituye propiedad del emisor.
        $layouts = [];
        $renglones = [];
        foreach (['fc_layout_nom', 'fc_row_layout'] as $tabla) {
            $encontrados = [];
            foreach (['org_empresa', 'org_sucursal', 'fc_csd'] as $propietario) {
                if ($this->tiene($tabla, $propietario.'_id')) {
                    array_push($encontrados, ...$this->ids($tabla, $propietario.'_id', $destinos[$propietario]));
                }
            }
            if ($rfc !== '' && $this->tiene($tabla, 'emisor_rfc')) {
                array_push($encontrados, ...array_map('intval', $this->consulta('SELECT id FROM '.$this->q($tabla)
                    .' WHERE UPPER(TRIM(emisor_rfc)) = UPPER(?) FOR UPDATE', [$rfc])->fetchAll(PDO::FETCH_COLUMN)));
            }
            if ($tabla === 'fc_layout_nom') {
                $layouts = $encontrados;
            } else {
                $renglones = $encontrados;
            }
        }
        if ($this->tiene('fc_row_layout', 'fc_layout_nom_id')) {
            array_push($renglones, ...$this->ids('fc_row_layout', 'fc_layout_nom_id', $layouts));
        }
        foreach (['fc_cfdi_sellado_nomina', 'fc_row_nomina'] as $tabla) {
            if (($this->tiene($tabla, 'fc_row_layout_id') && $this->ids($tabla, 'fc_row_layout_id', $renglones) !== [])
                || ($this->tiene($tabla, 'fc_layout_nom_id') && $this->ids($tabla, 'fc_layout_nom_id', $layouts) !== [])) {
                $this->fiscal($tabla, 'nóminas');
            }
        }
    }

    private function validar_dependencias(array $destinos): void
    {
        foreach ($destinos as $padre => $ids) {
            if ($ids === []) {
                continue;
            }
            $grupos = [];
            foreach ($this->consulta(
                'SELECT TABLE_SCHEMA, TABLE_NAME, CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_COLUMN_NAME, '
                .'TABLE_SCHEMA = DATABASE() AS esquema_actual FROM information_schema.KEY_COLUMN_USAGE '
                .'WHERE REFERENCED_TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME = ? '
                .'ORDER BY TABLE_SCHEMA, TABLE_NAME, CONSTRAINT_NAME, ORDINAL_POSITION', [$padre]
            )->fetchAll(PDO::FETCH_ASSOC) as $fk) {
                $grupos[json_encode([$fk['TABLE_SCHEMA'], $fk['TABLE_NAME'], $fk['CONSTRAINT_NAME']], JSON_THROW_ON_ERROR)][] = $fk;
            }
            foreach ($grupos as $grupo) {
                $fk = $grupo[0];
                if ((int)$fk['esquema_actual'] === 1 && count($grupo) === 1 && $fk['REFERENCED_COLUMN_NAME'] === 'id'
                    && (self::PROPIAS[$fk['TABLE_NAME']][$fk['COLUMN_NAME']] ?? null) === $padre) {
                    continue;
                }
                $union = implode(' AND ', array_map(fn (array $c): string => 'h.'.$this->q($c['COLUMN_NAME'])
                    .' = p.'.$this->q($c['REFERENCED_COLUMN_NAME']), $grupo));
                foreach ($ids as $id) {
                    if ($this->consulta('SELECT 1 FROM '.$this->q($padre).' p JOIN '.$this->q($fk['TABLE_SCHEMA']).'.'
                        .$this->q($fk['TABLE_NAME']).' h ON '.$union.' WHERE p.id = ? LIMIT 1 FOR UPDATE', [$id]
                    )->fetchColumn() !== false) {
                        $this->dependencia($fk['TABLE_NAME']);
                    }
                }
            }
            foreach ($this->columnas as $tabla => $columnas) {
                $columna = $padre.'_id';
                if (!isset($columnas[$columna]) || ($this->tablas[$tabla]['TABLE_TYPE'] ?? '') !== 'BASE TABLE'
                    || (self::PROPIAS[$tabla][$columna] ?? null) === $padre) {
                    continue;
                }
                $this->validar_tabla($tabla);
                if ($this->ids($tabla, $columna, $ids) !== []) {
                    $this->dependencia($tabla);
                }
            }
        }
    }

    private function validar_tabla(string $tabla): void
    {
        $datos = $this->tablas[$tabla] ?? [];
        if (($datos['TABLE_TYPE'] ?? '') !== 'BASE TABLE' || strcasecmp((string)($datos['ENGINE'] ?? ''), 'InnoDB') !== 0
            || !$this->tiene($tabla, 'id')) {
            throw new RuntimeException('No se puede garantizar la reversión de la baja en '.$tabla.'.');
        }
    }

    private function validar_resultado(string $tabla, int $id, mixed $resultado): void
    {
        if (!$resultado instanceof stdClass || errores::$error
            || $this->consulta('SELECT id FROM '.$this->q($tabla).' WHERE id = ?', [$id])->fetchColumn() !== false) {
            throw new RuntimeException('No se completó la baja en '.$tabla.'.');
        }
    }

    private function borrar(string $tabla, int $id): void
    {
        if ($this->consulta('DELETE FROM '.$this->q($tabla).' WHERE id = ?', [$id])->rowCount() !== 1) {
            throw new RuntimeException('No se pudo retirar una dependencia propia en '.$tabla.'.');
        }
    }

    private function ids(string $tabla, string $columna, array $padres, bool $bloquear = true): array
    {
        if ($padres === []) {
            return [];
        }
        $ids = [];
        foreach (array_unique($padres) as $id) {
            array_push($ids, ...array_map('intval', $this->consulta('SELECT id FROM '.$this->q($tabla)
                .' WHERE '.$this->q($columna).' = ? ORDER BY id'.($bloquear ? ' FOR UPDATE' : ''), [$id]
            )->fetchAll(PDO::FETCH_COLUMN)));
        }
        $ids = array_values(array_unique($ids));
        sort($ids, SORT_NUMERIC);
        return $ids;
    }

    private function tiene(string $tabla, string $columna): bool
    {
        return isset($this->columnas[$tabla][$columna]);
    }

    private function fiscal(string $tabla, string $tipo): never
    {
        throw new RuntimeException('La empresa tiene '.$tipo.' asociadas ('.$tabla.') y no se puede eliminar.');
    }

    private function dependencia(string $tabla): never
    {
        if (isset(self::FISCALES[$tabla])) {
            $this->fiscal($tabla, self::FISCALES[$tabla]);
        }
        throw new RuntimeException('La empresa tiene una dependencia no clasificada o compartida en '.$tabla.'.');
    }

    private function q(string $nombre): string
    {
        return '`'.str_replace('`', '``', $nombre).'`';
    }

    private function consulta(string $sql, array $valores = []): \PDOStatement
    {
        $consulta = $this->link->prepare($sql);
        if ($consulta === false || !$consulta->execute($valores)) {
            throw new RuntimeException('No se pudieron verificar las referencias de la empresa.');
        }
        return $consulta;
    }
}
