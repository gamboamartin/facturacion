<?php
declare(strict_types=1);

/** Instalación explícita del catálogo de rutas y su autorización administrativa. */
final class AguabanRutaMigracion
{
    public const ACCIONES = ['lista', 'alta', 'modifica', 'consulta', 'elimina',
        'alta_bd', 'modifica_bd', 'elimina_bd', 'status'];

    public const SQL = <<<'SQL'
CREATE TABLE IF NOT EXISTS aguaban_ruta (
  id BIGINT NOT NULL AUTO_INCREMENT,
  codigo VARCHAR(32) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ej: RUTA-CENTRO-01',
  descripcion VARCHAR(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  sector VARCHAR(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  hash_version VARCHAR(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Control de sincronización 304',
  adm_usuario_id BIGINT DEFAULT NULL COMMENT 'Operador/checador asignado',
  status VARCHAR(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  usuario_alta_id BIGINT DEFAULT NULL,
  usuario_update_id BIGINT DEFAULT NULL,
  fecha_alta TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_update TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_aguaban_ruta_codigo (codigo),
  KEY idx_aguaban_ruta_adm_usuario (adm_usuario_id),
  KEY idx_aguaban_ruta_status (status),
  CONSTRAINT fk_aguaban_ruta_adm_usuario FOREIGN KEY (adm_usuario_id) REFERENCES adm_usuario (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Rutas de medición y asignación de cuadrillas'
SQL;

    public static function preparar(PDO $pdo, bool $aplicar = false): array
    {
        $nombreBloqueo = 'aguaban_ruta_'.substr(hash('sha256', (string)$pdo->query('SELECT DATABASE()')->fetchColumn()), 0, 40);
        $bloqueado = false;
        try {
            if ($aplicar) {
                if ($pdo->inTransaction()) {
                    throw new RuntimeException('La instalación requiere una conexión sin transacción abierta.');
                }
                $q = $pdo->prepare('SELECT GET_LOCK(?,0)');
                $q->execute([$nombreBloqueo]);
                $bloqueado = (int)$q->fetchColumn() === 1;
                if (!$bloqueado) {
                    throw new RuntimeException('Otra instalación está preparando el catálogo de rutas.');
                }
            }
            self::validarUsuarioReferenciado($pdo);
            self::validarCatalogosTransaccionales($pdo);
            $grupo = self::unico($pdo, "SELECT * FROM adm_grupo WHERE codigo='PA' AND descripcion='Administrador Sistema' AND status='activo'",
                [], 'Debe existir un único grupo activo PA / Administrador Sistema.');
            $namespace = self::unico($pdo, "SELECT * FROM adm_namespace WHERE descripcion='gamboa.martin/facturacion' AND status='activo'",
                [], 'Debe existir un único namespace activo de Facturación.');
            $origen = self::unico($pdo, "SELECT * FROM adm_seccion WHERE descripcion='fc_factura' AND status='activo'",
                [], 'Debe existir una única sección activa de facturas como plantilla de catálogo.');
            if ((int)$origen['adm_namespace_id'] !== (int)$namespace['id']) {
                throw new RuntimeException('La sección de facturas no pertenece al namespace esperado.');
            }
            $sistema = self::unico($pdo, "SELECT * FROM adm_sistema WHERE codigo='facturacion' AND descripcion='facturacion' AND status='activo'",
                [], 'Debe existir un único sistema activo facturacion.');
            $pertenenciaOrigen = self::unico($pdo, "SELECT * FROM adm_seccion_pertenece WHERE adm_seccion_id=? AND adm_sistema_id=? AND status='activo'",
                [$origen['id'], $sistema['id']], 'La sección de facturas debe pertenecer una sola vez al sistema activo Facturación.');
            $menuOrigen = self::unico($pdo, 'SELECT * FROM adm_menu WHERE id=?', [$origen['adm_menu_id']], 'No existe el menú de referencia.');
            $accionOrigen = self::unico($pdo, "SELECT * FROM adm_accion WHERE adm_seccion_id=? AND descripcion='lista' AND status='activo' AND seguridad='activo'",
                [$origen['id']], 'Debe existir una única acción segura de lista de facturas como plantilla.');
            $menu = self::opcional($pdo, "SELECT * FROM adm_menu WHERE codigo='aguaban'", [], 'Existen menús Aguaban duplicados.');
            $seccion = self::opcional($pdo, "SELECT * FROM adm_seccion WHERE descripcion='aguaban_ruta'", [], 'Existen secciones de rutas duplicadas.');
            $acciones = [];
            $pertenencia = null;
            if ($seccion) {
                if (!$menu || (int)$seccion['adm_menu_id'] !== (int)$menu['id']
                    || (int)$seccion['adm_namespace_id'] !== (int)$namespace['id']) {
                    throw new RuntimeException('La sección de rutas existente tiene otro menú o namespace; no se modificará automáticamente.');
                }
                $pertenencia = self::unico($pdo, 'SELECT * FROM adm_seccion_pertenece WHERE adm_seccion_id=?',
                    [$seccion['id']], 'La sección de rutas existente requiere una única pertenencia al sistema; no se reconstruirá automáticamente.');
                if ((int)$pertenencia['adm_sistema_id'] !== (int)$sistema['id']) {
                    throw new RuntimeException('La sección de rutas pertenece a otro sistema; no se modificará automáticamente.');
                }
                $q = $pdo->prepare('SELECT descripcion,COUNT(*) AS total FROM adm_accion WHERE adm_seccion_id=? GROUP BY descripcion');
                $q->execute([$seccion['id']]);
                foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $accion) {
                    if ((int)$accion['total'] !== 1) {
                        throw new RuntimeException('Hay acciones de rutas duplicadas; revisa el catálogo.');
                    }
                    $acciones[] = $accion['descripcion'];
                }
                self::validarSeguridadExistente($pdo, (int)$seccion['id'], (int)$grupo['id']);
            }
            $q = $pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='aguaban_ruta'");
            $existeTabla = (int)$q->fetchColumn() === 1;
            if ($existeTabla) {
                self::validarEsquema($pdo);
            }
            $resultado = ['estado' => $aplicar ? 'DESARROLLO_PREPARADO' : 'PLAN_SOLO_LECTURA',
                'crear_tabla' => !$existeTabla, 'crear_menu' => !$menu, 'crear_seccion' => !$seccion,
                'crear_pertenencia' => !$seccion, 'sistema_id' => (int)$sistema['id'],
                'pertenencia_id' => $pertenencia ? (int)$pertenencia['id'] : null, 'pertenencias_creadas' => 0,
                'grupo_administrador_id' => (int)$grupo['id'], 'seccion_id' => $seccion ? (int)$seccion['id'] : null,
                'acciones_previstas' => self::ACCIONES, 'acciones_faltantes' => array_values(array_diff(self::ACCIONES, $acciones)),
                'acciones_creadas' => 0, 'permisos_creados' => 0, 'permisos_existentes_sin_cambios' => true,
                'sql_sha256' => hash('sha256', self::SQL), 'datos_rutas_modificados' => 0, 'llamadas_pac' => 0];
            if (!$aplicar) {
                return $resultado;
            }
            // MySQL confirma DDL implícitamente: por eso todo el catálogo se valida antes.
            if (!$existeTabla) {
                $pdo->exec(self::SQL);
            }
            self::validarEsquema($pdo);
            if ($seccion) {
                // Tampoco recrear acciones borradas: su ausencia puede ser una revocación deliberada.
                return $resultado;
            }
            $pdo->beginTransaction();
            try {
                if (!$menu) {
                    $menu = self::baseRegistro($menuOrigen);
                    $menu = array_replace($menu, ['codigo' => 'aguaban', 'codigo_bis' => 'aguaban',
                        'descripcion' => 'Aguaban', 'etiqueta_label' => 'Aguaban', 'descripcion_select' => 'Aguaban',
                        'alias' => 'aguaban', 'titulo' => 'Aguaban', 'icono' => 'bi bi-droplet', 'status' => 'activo']);
                    $menu['id'] = self::insertar($pdo, 'adm_menu', $menu);
                }
                $nuevo = array_replace(self::baseRegistro($origen), ['codigo' => 'aguaban_ruta', 'codigo_bis' => 'aguaban_ruta',
                    'descripcion' => 'aguaban_ruta', 'etiqueta_label' => 'Rutas', 'descripcion_select' => 'Rutas de medición',
                    'alias' => 'aguaban_ruta', 'icono' => 'bi bi-signpost-2', 'status' => 'activo',
                    'adm_menu_id' => $menu['id'], 'adm_namespace_id' => $namespace['id']]);
                $seccionId = self::insertar($pdo, 'adm_seccion', $nuevo);
                $nuevaPertenencia = array_replace(self::baseRegistro($pertenenciaOrigen), [
                    'adm_seccion_id' => $seccionId, 'adm_sistema_id' => $sistema['id'], 'status' => 'activo',
                    'codigo' => 'aguaban_ruta.facturacion', 'codigo_bis' => 'aguaban_ruta.facturacion',
                    'descripcion' => 'Rutas de medición de Facturación', 'descripcion_select' => 'Rutas de medición de Facturación',
                    'alias' => 'aguaban_ruta']);
                $pertenenciaId = self::insertar($pdo, 'adm_seccion_pertenece', $nuevaPertenencia);
                $etiquetas = ['lista' => 'Lista', 'alta' => 'Alta', 'modifica' => 'Modificar', 'consulta' => 'Consultar',
                    'elimina' => 'Eliminar', 'alta_bd' => 'Guardar ruta', 'modifica_bd' => 'Guardar cambios',
                    'elimina_bd' => 'Eliminar ruta', 'status' => 'Cambiar estado'];
                foreach (self::ACCIONES as $nombre) {
                    $codigo = 'aguaban_ruta.'.$nombre;
                    $nuevo = array_replace(self::baseRegistro($accionOrigen), ['descripcion' => $nombre,
                        'etiqueta_label' => $etiquetas[$nombre], 'titulo' => $etiquetas[$nombre].' de rutas',
                        'descripcion_select' => $etiquetas[$nombre].' de rutas', 'adm_seccion_id' => $seccionId,
                        'codigo' => $codigo, 'codigo_bis' => $codigo, 'alias' => $nombre, 'status' => 'activo',
                        'seguridad' => 'activo', 'es_view' => 'activo', 'es_modal' => 'inactivo',
                        'es_status' => 'inactivo', 'es_lista' => 'inactivo', 'inicio' => 'inactivo', 'lista' => 'inactivo',
                        'visible' => in_array($nombre, ['alta', 'lista'], true) ? 'activo' : 'inactivo',
                        'icono' => 'bi bi-signpost-2', 'css' => 'info', 'target' => '']);
                    $accionId = self::insertar($pdo, 'adm_accion', $nuevo);
                    self::insertar($pdo, 'adm_accion_grupo', ['adm_accion_id' => $accionId, 'adm_grupo_id' => $grupo['id'],
                        'codigo' => $codigo.'.'.$grupo['id'], 'codigo_bis' => $codigo.'.'.$grupo['id'],
                        'descripcion' => $etiquetas[$nombre].' de rutas', 'descripcion_select' => $etiquetas[$nombre].' de rutas',
                        'status' => 'activo', 'predeterminado' => 'inactivo', 'usuario_alta_id' => $origen['usuario_alta_id'],
                        'usuario_update_id' => $origen['usuario_update_id']]);
                }
                $pdo->commit();
                $resultado['seccion_id'] = $seccionId;
                $resultado['pertenencia_id'] = $pertenenciaId;
                $resultado['pertenencias_creadas'] = 1;
                $resultado['acciones_creadas'] = count(self::ACCIONES);
                $resultado['permisos_creados'] = count(self::ACCIONES);
                $resultado['acciones_faltantes'] = [];
                return $resultado;
            } catch (Throwable $error) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $error;
            }
        } finally {
            if ($bloqueado) {
                $q = $pdo->prepare('SELECT RELEASE_LOCK(?)');
                $q->execute([$nombreBloqueo]);
            }
        }
    }

    public static function validarEsquema(PDO $pdo): void
    {
        $tabla = self::unico($pdo, "SELECT ENGINE,TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='aguaban_ruta'",
            [], 'No existe la tabla aguaban_ruta.');
        if (strtolower((string)$tabla['ENGINE']) !== 'innodb' || $tabla['TABLE_COLLATION'] !== 'utf8mb4_unicode_ci') {
            throw new RuntimeException('aguaban_ruta requiere InnoDB y utf8mb4_unicode_ci; no se alterará automáticamente.');
        }
        $q = $pdo->query("SELECT COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,COLUMN_DEFAULT,EXTRA,COLLATION_NAME
            FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='aguaban_ruta' ORDER BY ORDINAL_POSITION");
        $esperados = ['id' => ['bigint', false], 'codigo' => ['varchar(32)', false], 'descripcion' => ['varchar(150)', false],
            'sector' => ['varchar(100)', true], 'hash_version' => ['varchar(64)', true], 'adm_usuario_id' => ['bigint', true],
            'status' => ['varchar(20)', false], 'usuario_alta_id' => ['bigint', true], 'usuario_update_id' => ['bigint', true],
            'fecha_alta' => ['timestamp', false], 'fecha_update' => ['timestamp', false]];
        $columnas = $q->fetchAll(PDO::FETCH_ASSOC);
        if (array_column($columnas, 'COLUMN_NAME') !== array_keys($esperados)) {
            throw new RuntimeException('Las columnas de aguaban_ruta no coinciden con la definición requerida.');
        }
        foreach ($columnas as $columna) {
            [$tipo, $nullable] = $esperados[$columna['COLUMN_NAME']];
            $real = self::normalizarTipo($columna['COLUMN_TYPE']);
            if ($real !== $tipo || ($columna['IS_NULLABLE'] === 'YES') !== $nullable
                || (str_starts_with($tipo, 'varchar') && $columna['COLLATION_NAME'] !== 'utf8mb4_unicode_ci')) {
                throw new RuntimeException('Tipo, nulabilidad o collation incompatible en aguaban_ruta.'.$columna['COLUMN_NAME'].'.');
            }
            $default = strtolower((string)$columna['COLUMN_DEFAULT']);
            if ($columna['COLUMN_NAME'] === 'status' && trim($default, "'") !== 'activo') {
                throw new RuntimeException('El estado predeterminado de aguaban_ruta debe ser activo.');
            }
            if (str_starts_with($columna['COLUMN_NAME'], 'fecha_') && !in_array($default, ['current_timestamp', 'current_timestamp()'], true)) {
                throw new RuntimeException('Las fechas de aguaban_ruta deben usar CURRENT_TIMESTAMP.');
            }
            if (!in_array($columna['COLUMN_NAME'], ['status', 'fecha_alta', 'fecha_update'], true)
                && $columna['COLUMN_DEFAULT'] !== null && strtoupper((string)$columna['COLUMN_DEFAULT']) !== 'NULL') {
                throw new RuntimeException('El valor predeterminado de aguaban_ruta.'.$columna['COLUMN_NAME'].' debe ser nulo.');
            }
            $extra = strtolower($columna['EXTRA']);
            if ($columna['COLUMN_NAME'] !== 'fecha_update' && str_contains($extra, 'on update')) {
                throw new RuntimeException('Solo fecha_update puede actualizarse automáticamente en aguaban_ruta.');
            }
            if (($columna['COLUMN_NAME'] === 'id' && !str_contains($extra, 'auto_increment'))
                || ($columna['COLUMN_NAME'] === 'fecha_update' && !str_contains($extra, 'on update current_timestamp'))) {
                throw new RuntimeException('Falta el autoincremento o actualización automática de aguaban_ruta.');
            }
        }
        $indices = [];
        foreach ($pdo->query('SHOW INDEX FROM aguaban_ruta')->fetchAll(PDO::FETCH_ASSOC) as $indice) {
            $indices[$indice['Key_name']]['columnas'][(int)$indice['Seq_in_index']] = $indice['Column_name'];
            $indices[$indice['Key_name']]['unico'] = (int)$indice['Non_unique'] === 0;
            if ($indice['Sub_part'] !== null) {
                throw new RuntimeException('Los índices de rutas no deben truncar sus columnas.');
            }
        }
        foreach (['PRIMARY' => ['id', true], 'uq_aguaban_ruta_codigo' => ['codigo', true],
            'idx_aguaban_ruta_adm_usuario' => ['adm_usuario_id', false], 'idx_aguaban_ruta_status' => ['status', false]] as $nombre => [$columna, $unico]) {
            if (!isset($indices[$nombre]) || array_values($indices[$nombre]['columnas']) !== [$columna] || $indices[$nombre]['unico'] !== $unico) {
                throw new RuntimeException('Falta o es incompatible el índice '.$nombre.' de aguaban_ruta.');
            }
        }
        $fk = self::unico($pdo, "SELECT k.COLUMN_NAME,k.REFERENCED_TABLE_NAME,k.REFERENCED_COLUMN_NAME,r.UPDATE_RULE,r.DELETE_RULE
            FROM information_schema.KEY_COLUMN_USAGE k JOIN information_schema.REFERENTIAL_CONSTRAINTS r
              ON r.CONSTRAINT_SCHEMA=k.CONSTRAINT_SCHEMA AND r.CONSTRAINT_NAME=k.CONSTRAINT_NAME AND r.TABLE_NAME=k.TABLE_NAME
            WHERE k.TABLE_SCHEMA=DATABASE() AND k.TABLE_NAME='aguaban_ruta' AND k.CONSTRAINT_NAME='fk_aguaban_ruta_adm_usuario' AND k.REFERENCED_TABLE_SCHEMA=DATABASE()",
            [], 'Falta o está duplicada la referencia de operador de aguaban_ruta.');
        if ($fk['COLUMN_NAME'] !== 'adm_usuario_id' || $fk['REFERENCED_TABLE_NAME'] !== 'adm_usuario' || $fk['REFERENCED_COLUMN_NAME'] !== 'id'
            || !in_array($fk['UPDATE_RULE'], ['RESTRICT', 'NO ACTION'], true) || !in_array($fk['DELETE_RULE'], ['RESTRICT', 'NO ACTION'], true)) {
            throw new RuntimeException('La referencia de operador de aguaban_ruta es incompatible.');
        }
    }

    private static function validarCatalogosTransaccionales(PDO $pdo): void
    {
        $tablas = ['adm_menu', 'adm_seccion', 'adm_accion', 'adm_accion_grupo', 'adm_seccion_pertenece'];
        $q = $pdo->query("SELECT TABLE_NAME,ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE()
            AND TABLE_NAME IN ('adm_menu','adm_seccion','adm_accion','adm_accion_grupo','adm_seccion_pertenece')");
        $existentes = [];
        foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $tabla) {
            if (strtolower((string)$tabla['ENGINE']) !== 'innodb') {
                throw new RuntimeException('Los catálogos de seguridad deben usar InnoDB para una instalación atómica.');
            }
            $existentes[] = $tabla['TABLE_NAME'];
        }
        if (array_diff($tablas, $existentes)) {
            throw new RuntimeException('Faltan catálogos de seguridad para instalar rutas.');
        }
    }

    private static function validarUsuarioReferenciado(PDO $pdo): void
    {
        $tabla = self::unico($pdo, "SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='adm_usuario'",
            [], 'No existe adm_usuario para relacionar operadores.');
        $columna = self::unico($pdo, "SELECT COLUMN_TYPE,IS_NULLABLE,COLUMN_KEY FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='adm_usuario' AND COLUMN_NAME='id'",
            [], 'No existe el identificador de adm_usuario.');
        if (strtolower((string)$tabla['ENGINE']) !== 'innodb' || self::normalizarTipo($columna['COLUMN_TYPE']) !== 'bigint'
            || $columna['IS_NULLABLE'] !== 'NO' || $columna['COLUMN_KEY'] !== 'PRI') {
            throw new RuntimeException('adm_usuario.id debe ser BIGINT con signo, clave primaria InnoDB, compatible con la relación solicitada.');
        }
    }

    private static function validarSeguridadExistente(PDO $pdo, int $seccionId, int $grupoId): void
    {
        $q = $pdo->prepare("SELECT COUNT(*) FROM adm_accion WHERE adm_seccion_id=? AND seguridad<>'activo'");
        $q->execute([$seccionId]);
        if ((int)$q->fetchColumn() !== 0) {
            throw new RuntimeException('Hay acciones de rutas sin seguridad activa; corrige el catálogo antes de continuar.');
        }
        $q = $pdo->prepare("SELECT COUNT(*) FROM adm_accion_grupo ag JOIN adm_accion a ON a.id=ag.adm_accion_id
            WHERE a.adm_seccion_id=? AND ag.status='activo' AND ag.adm_grupo_id<>?");
        $q->execute([$seccionId, $grupoId]);
        if ((int)$q->fetchColumn() !== 0) {
            throw new RuntimeException('Hay permisos activos de rutas para otro grupo; no se cambiarán automáticamente.');
        }
    }

    private static function normalizarTipo(string $tipo): string
    {
        return preg_replace('/^bigint\(\d+\)$/', 'bigint', strtolower($tipo));
    }

    private static function unico(PDO $pdo, string $sql, array $valores, string $error): array
    {
        $fila = self::opcional($pdo, $sql, $valores, $error);
        if ($fila === null) {
            throw new RuntimeException($error);
        }
        return $fila;
    }

    private static function opcional(PDO $pdo, string $sql, array $valores, string $error): ?array
    {
        $q = $pdo->prepare($sql);
        $q->execute($valores);
        $fila = $q->fetch(PDO::FETCH_ASSOC);
        if ($q->fetch(PDO::FETCH_ASSOC)) {
            throw new RuntimeException($error);
        }
        return $fila ?: null;
    }

    private static function baseRegistro(array $registro): array
    {
        unset($registro['id'], $registro['fecha_alta'], $registro['fecha_update']);
        return $registro;
    }

    private static function insertar(PDO $pdo, string $tabla, array $registro): int
    {
        $campos = implode(',', array_map(static fn(string $campo): string => '`'.str_replace('`', '``', $campo).'`', array_keys($registro)));
        $q = $pdo->prepare('INSERT INTO '.$tabla.' ('.$campos.') VALUES ('.implode(',', array_fill(0, count($registro), '?')).')');
        $q->execute(array_values($registro));
        return (int)$pdo->lastInsertId();
    }
}
