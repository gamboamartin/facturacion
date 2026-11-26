<?php
declare(strict_types=1);

/** Registra explícitamente acciones de descarga; nunca se ejecuta desde una petición web. */
final class DescargasComplementoAdmin
{
    public const ACCIONES = ['descargar_por_separado', 'descarga_xml', 'genera_pdf'];

    public static function preparar(PDO $pdo, bool $aplicar = false, ?callable $guardar = null): array
    {
        if ($pdo->inTransaction() || ($aplicar && $guardar === null)) {
            throw new RuntimeException('Se requiere transacción propia y diario recuperable.');
        }
        $pdo->beginTransaction();
        try {
            $admin = self::unica($pdo, "SELECT id FROM adm_grupo WHERE codigo='PA' AND descripcion='Administrador Sistema' AND status='activo' AND root='activo'");
            $actor = self::unica($pdo, "SELECT id FROM adm_usuario WHERE user='admin' AND adm_grupo_id=? AND status='activo'", [$admin['id']]);
            $factura = self::unica($pdo, "SELECT id FROM adm_seccion WHERE descripcion='fc_factura' AND status='activo'");
            $cp = self::unica($pdo, "SELECT id FROM adm_seccion WHERE descripcion='fc_complemento_pago' AND status='activo' FOR UPDATE");
            $diario = ['base' => $pdo->query('SELECT DATABASE()')->fetchColumn(), 'grupo' => (int)$admin['id'],
                'acciones_creadas' => [], 'permisos_creados' => [], 'pendientes' => []];
            foreach (self::ACCIONES as $nombre) {
                $origen = self::unica($pdo, "SELECT * FROM adm_accion WHERE adm_seccion_id=? AND descripcion=? AND status='activo' AND seguridad='activo'", [$factura['id'], $nombre]);
                self::unica($pdo, "SELECT id FROM adm_accion_grupo WHERE adm_accion_id=? AND adm_grupo_id=? AND status='activo'", [$origen['id'], $admin['id']]);
                $accion = self::opcional($pdo, "SELECT * FROM adm_accion WHERE adm_seccion_id=? AND descripcion=? FOR UPDATE", [$cp['id'], $nombre]);
                if ($accion === null) {
                    $diario['pendientes'][] = 'accion:'.$nombre;
                    if (!$aplicar) {
                        $diario['pendientes'][] = 'permiso:'.$nombre;
                        continue;
                    }
                    $nuevo = $origen;
                    unset($nuevo['id'], $nuevo['fecha_alta'], $nuevo['fecha_update']);
                    $nuevo = array_replace($nuevo, ['adm_seccion_id' => $cp['id'],
                        'codigo' => $nombre.'fc_complemento_pago', 'codigo_bis' => strtoupper($nombre.'fc_complemento_pago'),
                        'usuario_alta_id' => $actor['id'], 'usuario_update_id' => $actor['id']]);
                    $id = self::insertar($pdo, 'adm_accion', $nuevo);
                    $diario['acciones_creadas'][] = $id;
                    $accion = self::unica($pdo, 'SELECT * FROM adm_accion WHERE id=?', [$id]);
                }
                if ($accion['status'] !== 'activo' || $accion['seguridad'] !== 'activo' || $accion['es_view'] !== $origen['es_view']) {
                    throw new RuntimeException('Acción existente incompatible; no se modifica: '.$nombre);
                }
                $permiso = self::opcional($pdo, "SELECT id,status FROM adm_accion_grupo WHERE adm_accion_id=? AND adm_grupo_id=? FOR UPDATE", [$accion['id'], $admin['id']]);
                if ($permiso !== null && $permiso['status'] !== 'activo') {
                    throw new RuntimeException('Permiso revocado; no se reactiva automáticamente: '.$nombre);
                }
                if ($permiso === null) {
                    $diario['pendientes'][] = 'permiso:'.$nombre;
                    if ($aplicar) {
                        $codigo = $accion['id'].'.'.$admin['id'];
                        $diario['permisos_creados'][] = self::insertar($pdo, 'adm_accion_grupo', [
                            'adm_accion_id' => $accion['id'], 'adm_grupo_id' => $admin['id'], 'status' => 'activo',
                            'usuario_alta_id' => $actor['id'], 'usuario_update_id' => $actor['id'],
                            'codigo' => $codigo, 'descripcion' => $nombre.' Administrador Sistema', 'predeterminado' => 'inactivo']);
                    }
                }
            }
            if (!$aplicar) {
                $pdo->rollBack();
                return $diario;
            }
            $guardar($diario);
            $pdo->commit();
            return $diario;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            throw $e;
        }
    }

    private static function opcional(PDO $pdo, string $sql, array $params = []): ?array
    {
        $q = $pdo->prepare($sql);
        $q->execute($params);
        $fila = $q->fetch(PDO::FETCH_ASSOC);
        if ($q->fetch(PDO::FETCH_ASSOC)) { throw new RuntimeException('Registro duplicado en catálogo.'); }
        return $fila ?: null;
    }
    private static function unica(PDO $pdo, string $sql, array $params = []): array
    {
        return self::opcional($pdo, $sql, $params) ?? throw new RuntimeException('Falta registro de referencia.');
    }
    private static function insertar(PDO $pdo, string $tabla, array $datos): int
    {
        $campos = implode(',', array_map(static fn(string $c): string => '`'.str_replace('`', '``', $c).'`', array_keys($datos)));
        $q = $pdo->prepare('INSERT INTO '.$tabla.' ('.$campos.') VALUES ('.implode(',', array_fill(0, count($datos), '?')).')');
        $q->execute(array_values($datos));
        return (int)$pdo->lastInsertId();
    }
}
