<?php
declare(strict_types=1);

/** Autorización explícita de productos para Aguaban; nunca se invoca desde una vista. */
final class AguabanProductosPermisos
{
    public const PERFIL = 'administracion_clientes';
    public const CRUD = ['lista', 'alta', 'alta_bd', 'modifica', 'modifica_bd', 'elimina_bd', 'status', 'get_data'];
    public const VERSION = 'aguaban_productos_permisos_v1';

    public static function preparar(PDO $pdo, bool $aplicar = false, bool $desarrollo = false, ?callable $guardar = null): array
    {
        $base = self::validarBase($pdo, $desarrollo);
        if ($pdo->inTransaction()) {
            throw new RuntimeException('La autorización necesita una conexión sin transacción abierta.');
        }
        if ($aplicar && $guardar === null) {
            throw new RuntimeException('Guarda el diario de cambios antes de confirmar la autorización.');
        }
        $pdo->beginTransaction();
        try {
            $grupo = self::uno($pdo, "SELECT * FROM adm_grupo WHERE codigo=? AND descripcion=? AND status='activo'", [self::PERFIL, self::PERFIL]);
            if ($grupo['root'] !== 'inactivo') {
                throw new RuntimeException('El perfil de clientes debe mantener root inactivo.');
            }
            self::uno($pdo, "SELECT id FROM adm_usuario WHERE user=? AND adm_grupo_id=? AND status='activo'", [self::PERFIL, $grupo['id']]);
            $admin = self::uno($pdo, "SELECT * FROM adm_grupo WHERE codigo='PA' AND descripcion='Administrador Sistema' AND status='activo'");
            $actor = self::uno($pdo, "SELECT id FROM adm_usuario WHERE user='admin' AND adm_grupo_id=? AND status='activo'", [$admin['id']]);
            $secciones = [];
            foreach (['com_producto', 'com_tipo_producto', 'cat_sat_cve_prod'] as $nombre) {
                $secciones[$nombre] = self::uno($pdo, "SELECT * FROM adm_seccion WHERE descripcion=? AND status='activo'", [$nombre]);
            }
            $sistema = self::uno($pdo, "SELECT id FROM adm_sistema WHERE codigo='facturacion' AND descripcion='facturacion' AND status='activo'");
            foreach (['com_producto', 'com_tipo_producto'] as $nombre) {
                self::uno($pdo, "SELECT id FROM adm_seccion_pertenece WHERE adm_seccion_id=? AND adm_sistema_id=? AND status='activo'", [$secciones[$nombre]['id'], $sistema['id']]);
            }
            $acciones = [];
            foreach (['com_producto', 'com_tipo_producto'] as $nombre) {
                foreach (self::CRUD as $accion) {
                    $acciones[$nombre.':'.$accion] = self::accion($pdo, (int)$secciones[$nombre]['id'], $accion, $nombre === 'com_producto' && $accion === 'status');
                }
            }
            $acciones['cat_sat_cve_prod:get_data_descripcion'] = self::accion($pdo, (int)$secciones['cat_sat_cve_prod']['id'], 'get_data_descripcion');
            foreach ($acciones as $clave => $accion) {
                if ($accion !== null && $accion['seguridad'] !== 'activo' && $clave !== 'com_tipo_producto:get_data') {
                    throw new RuntimeException('La acción necesita autorización segura: '.$clave);
                }
            }
            $antesAcl = self::filas($pdo, 'SELECT * FROM adm_accion_grupo ORDER BY id');
            $antesCatalogo = self::filas($pdo, 'SELECT * FROM adm_accion ORDER BY id');
            $antesUsuarios = self::hashFilas(self::filas($pdo, 'SELECT * FROM adm_usuario ORDER BY id'));
            $antesGrupos = self::hashFilas(self::filas($pdo, 'SELECT * FROM adm_grupo ORDER BY id'));
            $diario = ['version' => self::VERSION, 'base' => $base, 'grupo_clientes_id' => (int)$grupo['id'],
                'grupo_admin_id' => (int)$admin['id'], 'actor_id' => (int)$actor['id'], 'estado' => 'PLAN',
                'accion_creada' => null, 'seguridad_modificada' => null, 'permisos_creados' => [],
                'pendientes' => [], 'acl_antes_sha256' => self::hashFilas($antesAcl),
                'catalogo_antes_sha256' => self::hashFilas($antesCatalogo), 'usuarios_sha256' => $antesUsuarios, 'grupos_sha256' => $antesGrupos];
            foreach ($acciones as $clave => $accion) {
                $permiso = $accion === null ? null : self::permiso($pdo, (int)$grupo['id'], (int)$accion['id']);
                if ($permiso === null) {
                    $diario['pendientes'][] = self::PERFIL.':'.$clave;
                }
            }
            $status = $acciones['com_producto:status'];
            if ($status === null || self::permiso($pdo, (int)$admin['id'], (int)$status['id']) === null) {
                $diario['pendientes'][] = 'PA:com_producto:status';
            }
            // Si se protege el endpoint previamente público, el administrador conserva su acceso.
            $getData = $acciones['com_tipo_producto:get_data'];
            if (self::permiso($pdo, (int)$admin['id'], (int)$getData['id']) === null) {
                $diario['pendientes'][] = 'PA:com_tipo_producto:get_data';
            }
            $diario['crear_status_producto'] = $status === null;
            $diario['proteger_get_data_tipo'] = $getData['seguridad'] !== 'activo';
            if (!$aplicar) {
                $pdo->rollBack();
                return $diario;
            }
            if ($status === null) {
                $nuevo = $acciones['com_tipo_producto:status'];
                unset($nuevo['id'], $nuevo['fecha_alta'], $nuevo['fecha_update']);
                $nuevo = array_replace($nuevo, ['adm_seccion_id' => $secciones['com_producto']['id'],
                    'descripcion' => 'status', 'codigo' => 'com_producto.status', 'codigo_bis' => 'com_producto.status',
                    'alias' => 'com_producto.status', 'etiqueta_label' => 'Estatus', 'descripcion_select' => 'Estatus de producto',
                    'titulo' => 'Cambiar estado del producto', 'usuario_alta_id' => $actor['id'], 'usuario_update_id' => $actor['id']]);
                $id = self::insertar($pdo, 'adm_accion', $nuevo);
                $status = self::uno($pdo, 'SELECT * FROM adm_accion WHERE id=?', [$id]);
                $acciones['com_producto:status'] = $status;
                $diario['accion_creada'] = ['id' => $id, 'despues_sha256' => self::hashFila($status)];
            }
            if ($getData['seguridad'] !== 'activo') {
                $q = $pdo->prepare("UPDATE adm_accion SET seguridad='activo',usuario_update_id=? WHERE id=? AND seguridad=?");
                $q->execute([$actor['id'], $getData['id'], $getData['seguridad']]);
                if ($q->rowCount() !== 1) {
                    throw new RuntimeException('Cambió la seguridad de Tipos de producto durante la operación.');
                }
                $diario['seguridad_modificada'] = ['antes' => $getData,
                    'despues_sha256' => self::hashFila(self::uno($pdo, 'SELECT * FROM adm_accion WHERE id=?', [$getData['id']]))];
            }
            foreach ($acciones as $accion) {
                self::conceder($pdo, (int)$grupo['id'], (int)$accion['id'], (int)$actor['id'], $diario);
            }
            self::conceder($pdo, (int)$admin['id'], (int)$status['id'], (int)$actor['id'], $diario);
            self::conceder($pdo, (int)$admin['id'], (int)$getData['id'], (int)$actor['id'], $diario);
            $sinNuevos = self::filas($pdo, 'SELECT * FROM adm_accion_grupo ORDER BY id');
            $nuevosIds = array_column($diario['permisos_creados'], 'id');
            $sinNuevos = array_values(array_filter($sinNuevos, static fn(array $r): bool => !in_array((int)$r['id'], $nuevosIds, true)));
            if (self::hashFilas($sinNuevos) !== $diario['acl_antes_sha256']
                || self::hashFilas(self::filas($pdo, 'SELECT * FROM adm_usuario ORDER BY id')) !== $antesUsuarios
                || self::hashFilas(self::filas($pdo, 'SELECT * FROM adm_grupo ORDER BY id')) !== $antesGrupos) {
                throw new RuntimeException('La operación debe conservar los usuarios, perfiles y permisos anteriores.');
            }
            $diario['estado'] = 'PENDIENTE_CONFIRMACION';
            $guardar($diario); // Si falla el respaldo del diario, todavía es posible ROLLBACK.
            $pdo->commit();
            $diario['estado'] = 'APLICADO';
            $guardar($diario);
            return $diario;
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $error;
        }
    }

    /** Revierte únicamente filas propias e intactas; nunca borra permisos preexistentes. */
    public static function revertir(PDO $pdo, array $diario, bool $desarrollo = false): array
    {
        $base = self::validarBase($pdo, $desarrollo);
        if (($diario['version'] ?? null) !== self::VERSION || ($diario['base'] ?? null) !== $base || $pdo->inTransaction()) {
            throw new RuntimeException('El diario no corresponde a esta base o la conexión está ocupada.');
        }
        $pdo->beginTransaction();
        try {
            foreach ($diario['permisos_creados'] as $permiso) {
                $fila = self::opcional($pdo, 'SELECT * FROM adm_accion_grupo WHERE id=?', [$permiso['id']]);
                if ($fila === null) {
                    continue; // Permite repetir una reversión completada.
                }
                if (self::hashFila($fila) !== $permiso['despues_sha256']) {
                    throw new RuntimeException('Un permiso creado fue modificado; no se eliminará automáticamente.');
                }
                $q = $pdo->prepare('DELETE FROM adm_accion_grupo WHERE id=?');
                $q->execute([$permiso['id']]);
            }
            $cambio = $diario['seguridad_modificada'];
            if ($cambio !== null) {
                $antes = $cambio['antes'];
                $actual = self::uno($pdo, 'SELECT * FROM adm_accion WHERE id=?', [$antes['id']]);
                if (self::hashFila($actual) !== self::hashFila($antes)) {
                    if (self::hashFila($actual) !== $cambio['despues_sha256']) {
                        throw new RuntimeException('La acción protegida cambió; su seguridad no se restaurará automáticamente.');
                    }
                    $q = $pdo->prepare('UPDATE adm_accion SET seguridad=?,usuario_update_id=?,fecha_update=? WHERE id=?');
                    $q->execute([$antes['seguridad'], $antes['usuario_update_id'], $antes['fecha_update'], $antes['id']]);
                }
            }
            $creada = $diario['accion_creada'];
            if ($creada !== null) {
                $actual = self::opcional($pdo, 'SELECT * FROM adm_accion WHERE id=?', [$creada['id']]);
                if ($actual !== null) {
                    if (self::hashFila($actual) !== $creada['despues_sha256']) {
                        throw new RuntimeException('La acción nueva fue modificada; no se eliminará automáticamente.');
                    }
                    $q = $pdo->prepare('SELECT COUNT(*) FROM adm_accion_grupo WHERE adm_accion_id=?');
                    $q->execute([$creada['id']]);
                    if ((int)$q->fetchColumn() !== 0) {
                        throw new RuntimeException('Otro permiso usa la acción nueva; no se eliminará automáticamente.');
                    }
                    $q = $pdo->prepare('DELETE FROM adm_accion WHERE id=?');
                    $q->execute([$creada['id']]);
                }
            }
            $pdo->commit();
            return ['estado' => 'REVERTIDO', 'version' => self::VERSION, 'base' => $base];
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $error;
        }
    }

    private static function validarBase(PDO $pdo, bool $desarrollo): string
    {
        $nombre = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
        $permitida = $desarrollo ? (bool)preg_match('/\Aqa_productos_[a-zA-Z0-9_]+\z/', $nombre) : $nombre === 'aguaban';
        if (!$permitida) {
            throw new RuntimeException('Esta autorización es exclusiva de Aguaban o del laboratorio explícito de productos.');
        }
        return $nombre;
    }

    private static function accion(PDO $pdo, int $seccion, string $nombre, bool $opcional = false): ?array
    {
        $fila = self::opcional($pdo, 'SELECT * FROM adm_accion WHERE adm_seccion_id=? AND descripcion=?', [$seccion, $nombre]);
        if (($fila === null && !$opcional) || ($fila !== null && $fila['status'] !== 'activo')) {
            throw new RuntimeException('Falta una acción activa del catálogo: '.$nombre);
        }
        return $fila;
    }

    private static function permiso(PDO $pdo, int $grupo, int $accion): ?array
    {
        $fila = self::opcional($pdo, 'SELECT * FROM adm_accion_grupo WHERE adm_grupo_id=? AND adm_accion_id=?', [$grupo, $accion]);
        if ($fila !== null && $fila['status'] !== 'activo') {
            throw new RuntimeException('Existe un permiso revocado; no se reactivará automáticamente.');
        }
        return $fila;
    }

    private static function conceder(PDO $pdo, int $grupo, int $accion, int $actor, array &$diario): void
    {
        if (self::permiso($pdo, $grupo, $accion) !== null) {
            return;
        }
        $codigo = 'AGUABAN_PRODUCTOS_'.$grupo.'_'.$accion;
        $id = self::insertar($pdo, 'adm_accion_grupo', ['adm_accion_id' => $accion, 'adm_grupo_id' => $grupo,
            'status' => 'activo', 'usuario_alta_id' => $actor, 'usuario_update_id' => $actor, 'codigo' => $codigo,
            'codigo_bis' => $codigo, 'descripcion' => $codigo, 'descripcion_select' => $codigo, 'predeterminado' => 'inactivo']);
        $diario['permisos_creados'][] = ['id' => $id, 'despues_sha256' => self::hashFila(self::uno($pdo, 'SELECT * FROM adm_accion_grupo WHERE id=?', [$id]))];
    }

    private static function insertar(PDO $pdo, string $tabla, array $registro): int
    {
        foreach (array_keys($registro) as $columna) {
            if (!preg_match('/\A[a-zA-Z0-9_]+\z/', $columna)) {
                throw new RuntimeException('Columna de catálogo inválida.');
            }
        }
        $q = $pdo->prepare('INSERT INTO '.$tabla.' (`'.implode('`,`', array_keys($registro)).'`) VALUES ('.implode(',', array_fill(0, count($registro), '?')).')');
        $q->execute(array_values($registro));
        return (int)$pdo->lastInsertId();
    }

    private static function uno(PDO $pdo, string $sql, array $params = []): array
    {
        $fila = self::opcional($pdo, $sql, $params);
        if ($fila === null) {
            throw new RuntimeException('Falta una referencia única de autorización.');
        }
        return $fila;
    }

    private static function opcional(PDO $pdo, string $sql, array $params = []): ?array
    {
        $q = $pdo->prepare($sql);
        $q->execute($params);
        $filas = $q->fetchAll(PDO::FETCH_ASSOC);
        if (count($filas) > 1) {
            throw new RuntimeException('Hay referencias de autorización duplicadas.');
        }
        return $filas[0] ?? null;
    }

    private static function filas(PDO $pdo, string $sql): array
    {
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    private static function hashFila(array $fila): string
    {
        ksort($fila);
        return hash('sha256', json_encode(array_map(static fn($v) => $v === null ? null : (string)$v, $fila), JSON_THROW_ON_ERROR));
    }

    private static function hashFilas(array $filas): string
    {
        return hash('sha256', implode("\n", array_map([self::class, 'hashFila'], $filas)));
    }
}
