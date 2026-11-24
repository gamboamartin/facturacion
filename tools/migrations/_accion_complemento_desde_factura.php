<?php
declare(strict_types=1);

/** Registra la acción de fila sin alterar autorizaciones ya administradas. */
final class AccionComplementoDesdeFactura
{
    public static function preparar(PDO $pdo, bool $aplicar = false): array
    {
        $bloqueo = 'fc_cp_factura_setup_'.substr(hash('sha256', (string)$pdo->query('SELECT DATABASE()')->fetchColumn()), 0, 40);
        $adquirido = false;
        try {
            if ($aplicar) {
                if ($pdo->inTransaction()) {
                    throw new RuntimeException('El registro de la acción requiere su propia transacción.');
                }
                $q = $pdo->prepare('SELECT GET_LOCK(?,0)');
                $q->execute([$bloqueo]);
                $adquirido = (int)$q->fetchColumn() === 1;
                if (!$adquirido) {
                    throw new RuntimeException('Otra instalación está registrando esta acción.');
                }
            }
            $q = $pdo->query("SELECT a.* FROM adm_accion a INNER JOIN adm_seccion s ON s.id=a.adm_seccion_id
                WHERE s.descripcion='fc_factura' AND s.status='activo' AND a.descripcion='modifica' AND a.status='activo'");
            $origen = $q->fetch(PDO::FETCH_ASSOC);
            if (!$origen || $q->fetch(PDO::FETCH_ASSOC)) {
                throw new RuntimeException('Debe existir una única acción de edición de facturas activa.');
            }
            $q = $pdo->prepare("SELECT id,status FROM adm_accion WHERE adm_seccion_id=? AND descripcion='complemento_pago'");
            $q->execute([$origen['adm_seccion_id']]);
            $existente = $q->fetch(PDO::FETCH_ASSOC);
            if ($q->fetch(PDO::FETCH_ASSOC)) {
                throw new RuntimeException('Existen acciones duplicadas de complemento desde factura.');
            }
            $grupos = $pdo->query("SELECT ag.adm_grupo_id FROM adm_accion_grupo ag
                INNER JOIN adm_grupo g ON g.id=ag.adm_grupo_id AND g.status='activo'
                INNER JOIN adm_accion a ON a.id=ag.adm_accion_id AND a.status='activo'
                INNER JOIN adm_seccion s ON s.id=a.adm_seccion_id AND s.status='activo'
                WHERE ag.status='activo' AND ((s.descripcion='fc_factura' AND a.descripcion='lista')
                    OR (s.descripcion='fc_complemento_pago' AND a.descripcion IN ('alta_bd','modifica')))
                GROUP BY ag.adm_grupo_id HAVING COUNT(DISTINCT CONCAT(s.descripcion,'.',a.descripcion))=3
                ORDER BY ag.adm_grupo_id")->fetchAll(PDO::FETCH_COLUMN);
            $resultado = ['estado' => $aplicar ? 'DESARROLLO_PREPARADO' : 'PLAN_SOLO_LECTURA',
                'crear_accion' => !$existente, 'accion_id' => $existente ? (int)$existente['id'] : null,
                'grupos_elegibles' => array_map('intval', $grupos), 'grupos_inicializados' => 0,
                'permisos_existentes_sin_cambios' => true, 'documentos_creados' => 0, 'llamadas_pac' => 0];
            // No se reactivan acciones ni se reponen permisos retirados por un administrador.
            if (!$aplicar || $existente) {
                return $resultado;
            }
            $pdo->beginTransaction();
            try {
                $nuevo = $origen;
                unset($nuevo['id'], $nuevo['fecha_alta'], $nuevo['fecha_update']);
                $codigo = 'complemento_pago.'.$origen['adm_seccion_id'];
                $nuevo = array_replace($nuevo, ['descripcion' => 'complemento_pago',
                    'etiqueta_label' => 'Complemento de pago', 'titulo' => 'Complemento desde factura',
                    'visible' => 'inactivo', 'status' => 'activo', 'seguridad' => 'activo',
                    'es_view' => 'activo', 'es_modal' => 'inactivo', 'es_status' => 'inactivo',
                    'es_lista' => 'activo', 'inicio' => 'inactivo', 'lista' => 'activo',
                    'icono' => 'bi bi-credit-card', 'codigo' => $codigo, 'codigo_bis' => $codigo,
                    'descripcion_select' => 'Crear complemento de pago desde factura',
                    'alias' => 'complemento_pago', 'target' => '']);
                $campos = implode(',', array_map(static fn(string $campo): string => '`'.str_replace('`', '``', $campo).'`', array_keys($nuevo)));
                $q = $pdo->prepare('INSERT INTO adm_accion ('.$campos.') VALUES ('.implode(',', array_fill(0, count($nuevo), '?')).')');
                $q->execute(array_values($nuevo));
                $id = (int)$pdo->lastInsertId();
                $q = $pdo->prepare("INSERT INTO adm_accion_grupo
                    (adm_accion_id,adm_grupo_id,status,usuario_alta_id,usuario_update_id,codigo,codigo_bis,descripcion,descripcion_select,predeterminado)
                    VALUES (?,?,'activo',?,?,?,?,?,?,'inactivo')");
                foreach ($grupos as $grupo) {
                    $codigoGrupo = 'complemento_pago.'.$grupo.'.'.$origen['adm_seccion_id'];
                    $q->execute([$id, $grupo, $origen['usuario_alta_id'], $origen['usuario_update_id'],
                        $codigoGrupo, $codigoGrupo, 'Complemento desde factura', 'Complemento desde factura']);
                }
                $pdo->commit();
                $resultado['accion_id'] = $id;
                $resultado['grupos_inicializados'] = count($grupos);
                return $resultado;
            } catch (Throwable $error) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $error;
            }
        } finally {
            if ($adquirido) {
                $q = $pdo->prepare('SELECT RELEASE_LOCK(?)');
                $q->execute([$bloqueo]);
            }
        }
    }
}
