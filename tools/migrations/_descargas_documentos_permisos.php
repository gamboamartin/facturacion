<?php
declare(strict_types=1);

/** Catálogo de descarga de documentos; se ejecuta sólo mediante autorización CLI explícita. */
final class DescargasDocumentosPermisos
{
    public const VERSION = 'descargas_documentos_permisos_v1';
    public const SECCIONES = ['fc_factura', 'fc_nota_credito', 'fc_complemento_pago'];
    public const ACCIONES = ['lista', 'get_data', 'descargar_por_separado', 'descarga_xml', 'genera_pdf'];

    public static function preparar(PDO $pdo, string $codigoPrincipal, bool $aplicar = false,
                                    ?callable $guardar = null, ?array $identidadesEsperadas = null, ?array $catalogoEsperado = null): array
    {
        if ($pdo->inTransaction() || ($aplicar && ($guardar === null || $identidadesEsperadas === null || $catalogoEsperado === null))) {
            throw new RuntimeException('Se necesita conexión libre, identidades confirmadas y diario recuperable.');
        }
        $pdo->beginTransaction();
        try {
            $principal = self::unica($pdo, "SELECT * FROM adm_grupo WHERE id=3 AND codigo=? AND status='activo' FOR UPDATE", [$codigoPrincipal]);
            $actor = self::unica($pdo, "SELECT * FROM adm_usuario WHERE user='admin' AND status='activo' FOR UPDATE");
            $admin = self::unica($pdo, "SELECT * FROM adm_grupo WHERE id=? AND status='activo' AND root='activo' FOR UPDATE", [$actor['adm_grupo_id']]);
            $identidades = ['principal_sha256' => self::hashFila($principal), 'admin_sha256' => self::hashFila($admin),
                'usuario_admin_sha256' => self::hashFila($actor)];
            if ($identidadesEsperadas !== null && $identidadesEsperadas !== $identidades) {
                throw new RuntimeException('Cambió la identidad del grupo principal o del administrador.');
            }
            $grupos = array_values(array_unique([(int)$principal['id'], (int)$admin['id']]));
            $secciones = [];
            foreach (self::SECCIONES as $nombre) {
                $secciones[$nombre] = self::unica($pdo, "SELECT * FROM adm_seccion WHERE descripcion=? AND status='activo' FOR UPDATE", [$nombre]);
            }
            $antes = self::instantanea($pdo);
            if ($catalogoEsperado !== null && $catalogoEsperado !== $antes) {
                throw new RuntimeException('El catálogo o sus permisos cambiaron después del plan.');
            }
            $diario = ['version' => self::VERSION, 'base' => $pdo->query('SELECT DATABASE()')->fetchColumn(),
                'estado' => 'PLAN', 'grupos' => $grupos, 'actor_id' => (int)$actor['id'], 'identidades' => $identidades,
                'acciones_creadas' => [], 'permisos_creados' => [], 'pendientes' => [], 'antes_sha256' => $antes];
            foreach (self::ACCIONES as $nombre) {
                $origen = self::unica($pdo, "SELECT * FROM adm_accion WHERE adm_seccion_id=? AND descripcion=? AND status='activo' AND seguridad='activo' FOR UPDATE", [$secciones['fc_factura']['id'], $nombre]);
                $vista = in_array($nombre, ['lista', 'descargar_por_separado'], true) ? 'activo' : 'inactivo';
                if ($origen['es_view'] !== $vista) {
                    throw new RuntimeException('La acción de referencia tiene un tipo incompatible: '.$nombre);
                }
                foreach ($secciones as $seccion => $referencia) {
                    $accion = self::opcional($pdo, 'SELECT * FROM adm_accion WHERE adm_seccion_id=? AND descripcion=? FOR UPDATE', [$referencia['id'], $nombre]);
                    if ($accion === null) {
                        $diario['pendientes'][] = 'accion:'.$seccion.':'.$nombre;
                        if ($aplicar) {
                            $nuevo = $origen;
                            unset($nuevo['id'], $nuevo['fecha_alta'], $nuevo['fecha_update']);
                            $codigo = $nombre.$seccion;
                            $nuevo = array_replace($nuevo, ['adm_seccion_id' => $referencia['id'], 'codigo' => $codigo,
                                'codigo_bis' => strtoupper($codigo), 'usuario_alta_id' => $actor['id'], 'usuario_update_id' => $actor['id']]);
                            if (array_key_exists('alias', $nuevo)) $nuevo['alias'] = $codigo;
                            $id = self::insertar($pdo, 'adm_accion', $nuevo);
                            $accion = self::unica($pdo, 'SELECT * FROM adm_accion WHERE id=?', [$id]);
                            $diario['acciones_creadas'][] = ['id' => $id, 'sha256' => self::hashFila($accion)];
                        }
                    }
                    // get_data de NC/CP conserva su configuración histórica; esta migración no cambia seguridad ajena.
                    $legacyGetData = $nombre === 'get_data' && $seccion !== 'fc_factura';
                    if ($accion !== null && ($accion['status'] !== 'activo'
                        || (!$legacyGetData && $accion['seguridad'] !== 'activo') || $accion['es_view'] !== $vista)) {
                        throw new RuntimeException('Acción existente incompatible; no se modifica: '.$seccion.':'.$nombre);
                    }
                    foreach ($grupos as $grupo) {
                        $permiso = $accion === null ? null : self::opcional($pdo, 'SELECT * FROM adm_accion_grupo WHERE adm_accion_id=? AND adm_grupo_id=? FOR UPDATE', [$accion['id'], $grupo]);
                        if ($permiso !== null && $permiso['status'] !== 'activo') {
                            throw new RuntimeException('Permiso revocado; se requiere revisión explícita: '.$seccion.':'.$nombre.':'.$grupo);
                        }
                        if ($permiso !== null) continue;
                        $diario['pendientes'][] = 'permiso:'.$seccion.':'.$nombre.':'.$grupo;
                        if ($aplicar) {
                            $codigo = 'DESCARGAS_'.$accion['id'].'_'.$grupo;
                            $id = self::insertar($pdo, 'adm_accion_grupo', ['adm_accion_id' => $accion['id'], 'adm_grupo_id' => $grupo,
                                'status' => 'activo', 'usuario_alta_id' => $actor['id'], 'usuario_update_id' => $actor['id'],
                                'codigo' => $codigo, 'descripcion' => $seccion.' '.$nombre, 'predeterminado' => 'inactivo']);
                            $diario['permisos_creados'][] = ['id' => $id,
                                'sha256' => self::hashFila(self::unica($pdo, 'SELECT * FROM adm_accion_grupo WHERE id=?', [$id]))];
                        }
                    }
                }
            }
            if (!$aplicar) {
                $pdo->rollBack();
                return $diario;
            }
            self::verificarOriginales($pdo, $diario);
            $diario['estado'] = 'PENDIENTE_CONFIRMACION';
            $guardar($diario);
            $pdo->commit();
            $diario['estado'] = 'APLICADO';
            $guardar($diario);
            return $diario;
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $error;
        }
    }

    /** Retira solamente filas propias intactas; los permisos preexistentes nunca se eliminan. */
    public static function revertir(PDO $pdo, array $diario): array
    {
        if ($pdo->inTransaction() || ($diario['version'] ?? '') !== self::VERSION
            || ($diario['base'] ?? '') !== $pdo->query('SELECT DATABASE()')->fetchColumn()
            || !in_array($diario['estado'] ?? '', ['APLICADO', 'PENDIENTE_CONFIRMACION'], true)) {
            throw new RuntimeException('El diario no pertenece a esta base o no es reversible.');
        }
        $pdo->beginTransaction();
        try {
            foreach (['permisos_creados' => 'adm_accion_grupo', 'acciones_creadas' => 'adm_accion'] as $clave => $tabla) {
                foreach (array_reverse($diario[$clave]) as $propia) {
                    $fila = self::opcional($pdo, 'SELECT * FROM '.$tabla.' WHERE id=? FOR UPDATE', [$propia['id']]);
                    if ($fila === null) continue;
                    if (self::hashFila($fila) !== $propia['sha256']) {
                        throw new RuntimeException('Una fila creada cambió; no se eliminará automáticamente.');
                    }
                    if ($tabla === 'adm_accion') {
                        $q = $pdo->prepare('SELECT COUNT(*) FROM adm_accion_grupo WHERE adm_accion_id=?');
                        $q->execute([$propia['id']]);
                        if ((int)$q->fetchColumn() !== 0) throw new RuntimeException('Otro permiso utiliza una acción creada.');
                    }
                    $q = $pdo->prepare('DELETE FROM '.$tabla.' WHERE id=?');
                    $q->execute([$propia['id']]);
                }
            }
            $pdo->commit();
            return ['estado' => 'REVERTIDO', 'version' => self::VERSION, 'base' => $diario['base']];
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $error;
        }
    }

    private static function verificarOriginales(PDO $pdo, array $diario): void
    {
        $actual = self::instantanea($pdo, [
            'adm_accion' => array_column($diario['acciones_creadas'], 'id'),
            'adm_accion_grupo' => array_column($diario['permisos_creados'], 'id')]);
        if ($actual !== $diario['antes_sha256']) throw new RuntimeException('Se modificó un usuario, grupo, acción o permiso anterior.');
    }

    private static function instantanea(PDO $pdo, array $excluir = []): array
    {
        $salida = [];
        foreach (['adm_grupo', 'adm_usuario', 'adm_seccion', 'adm_accion', 'adm_accion_grupo'] as $tabla) {
            $filas = $pdo->query('SELECT * FROM '.$tabla.' ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
            $filas = array_filter($filas, static fn(array $f): bool => !in_array((int)$f['id'], $excluir[$tabla] ?? [], true));
            $salida[$tabla] = hash('sha256', implode("\n", array_map([self::class, 'hashFila'], $filas)));
        }
        return $salida;
    }

    private static function opcional(PDO $pdo, string $sql, array $params = []): ?array
    {
        $q = $pdo->prepare($sql); $q->execute($params); $filas = $q->fetchAll(PDO::FETCH_ASSOC);
        if (count($filas) > 1) throw new RuntimeException('Referencia de catálogo duplicada.');
        return $filas[0] ?? null;
    }
    private static function unica(PDO $pdo, string $sql, array $params = []): array
    {
        return self::opcional($pdo, $sql, $params) ?? throw new RuntimeException('Falta referencia de catálogo.');
    }
    private static function insertar(PDO $pdo, string $tabla, array $datos): int
    {
        $campos = implode(',', array_map(static fn(string $c): string => '`'.str_replace('`', '``', $c).'`', array_keys($datos)));
        $q = $pdo->prepare('INSERT INTO '.$tabla.' ('.$campos.') VALUES ('.implode(',', array_fill(0, count($datos), '?')).')');
        $q->execute(array_values($datos)); return (int)$pdo->lastInsertId();
    }
    private static function hashFila(array $fila): string
    {
        ksort($fila);
        return hash('sha256', json_encode(array_map(static fn($v) => $v === null ? null : (string)$v, $fila), JSON_THROW_ON_ERROR));
    }
}
