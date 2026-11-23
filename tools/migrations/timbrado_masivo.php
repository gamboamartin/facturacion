<?php
declare(strict_types=1);

// Instalador explícito de desarrollo. Nunca se ejecuta desde una vista ni al arrancar.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$opciones = getopt('', ['database:', 'apply', 'development']);
$root = dirname(__DIR__, 2);
require $root.'/config/database.php';
require __DIR__.'/_esquema_timbrado_masivo.php';
try {
    $db = new \config\database();
    if (!isset($opciones['database']) || $opciones['database'] !== $db->db_name) {
        throw new RuntimeException('Indica --database con el nombre exacto de la base configurada.');
    }
    $aplicar = isset($opciones['apply']);
    if ($aplicar && (!isset($opciones['development']) || !in_array($db->db_host, ['localhost', '127.0.0.1', '::1'], true))) {
        throw new RuntimeException('Este instalador solo aplica con --development y una base local de desarrollo.');
    }
    $pdo = new PDO('mysql:host='.$db->db_host.';dbname='.$db->db_name.';charset=utf8mb4', $db->db_user, $db->db_password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    if ($aplicar) {
        $lock=$pdo->prepare('SELECT GET_LOCK(?,0)');
        $lock->execute(['fc_masivo_setup_'.substr(hash('sha256',$db->db_name),0,40)]);
        if ((int)$lock->fetchColumn()!==1) {
            throw new RuntimeException('Otra instalación está preparando esta misma base.');
        }
    }
    $tablas = ['fc_timbrado_lote', 'fc_timbrado_lote_item', 'fc_timbrado_intento'];
    $q = $pdo->prepare('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME IN (?,?,?)');
    $q->execute([$db->db_name, ...$tablas]);
    $existentes = $q->fetchAll(PDO::FETCH_COLUMN);
    if ($existentes && count($existentes) !== count($tablas)) {
        throw new RuntimeException('La instalación de tablas está incompleta. Revisa el esquema antes de continuar.');
    }
    $q = $pdo->query("SELECT a.* FROM adm_accion a INNER JOIN adm_seccion s ON s.id=a.adm_seccion_id
        WHERE s.descripcion='fc_factura' AND a.descripcion='timbra_xml' AND a.status='activo'");
    $origen = $q->fetch(PDO::FETCH_ASSOC);
    if (!$origen || $q->fetch(PDO::FETCH_ASSOC)) {
        throw new RuntimeException('Debe existir una única acción activa de timbrado individual de factura.');
    }
    $q = $pdo->prepare("SELECT * FROM adm_accion WHERE adm_seccion_id=? AND descripcion='timbrado_masivo'");
    $q->execute([$origen['adm_seccion_id']]);
    $accion = $q->fetch(PDO::FETCH_ASSOC);
    if ($q->fetch(PDO::FETCH_ASSOC)) {
        throw new RuntimeException('Hay acciones de timbrado masivo duplicadas. Revisa el catálogo antes de continuar.');
    }
    if ($accion && ($accion['status'] !== 'activo' || $accion['visible'] !== 'activo' || $accion['es_view'] !== 'activo')) {
        throw new RuntimeException('La acción ya existe con otra configuración. No se modificará automáticamente.');
    }
    $sql = file_get_contents(__DIR__.'/sql/fc_timbrado_masivo.sql');
    if ($sql === false) {
        throw new RuntimeException('No se encontró la definición del esquema.');
    }
    $manifest=json_decode(file_get_contents(__DIR__.'/manifest.json'),true,flags:JSON_THROW_ON_ERROR);
    $registradas=array_values(array_filter($manifest['migrations'],static fn(array $m):bool=>$m['id']==='fc_timbrado_masivo'));
    if (count($registradas)!==1 || !hash_equals($registradas[0]['sha256'],hash('sha256',$sql))) {
        throw new RuntimeException('El SQL no coincide con la migración registrada.');
    }
    if ($existentes) {
        EsquemaTimbradoMasivo::validar($pdo);
    }
    if (!$aplicar) {
        echo json_encode(['estado' => 'PLAN_SOLO_LECTURA', 'base' => $db->db_name, 'tablas_existentes' => $existentes,
            'crear_tablas' => !$existentes, 'crear_accion' => !$accion, 'permisos' => 'Solo grupos que ya tengan lista y timbra_xml activos.',
            'sql_sha256' => hash('sha256', $sql)], JSON_PRETTY_PRINT), PHP_EOL;
        exit;
    }
    if (!$existentes) {
        foreach (explode(';', $sql) as $sentencia) {
            if (trim($sentencia) !== '') {
                $pdo->exec($sentencia);
            }
        }
    }
    EsquemaTimbradoMasivo::validar($pdo);
    $permisos = 0;
    if (!$accion) {
        $pdo->beginTransaction();
        try {
            $nuevo = $origen;
            unset($nuevo['id'], $nuevo['fecha_alta'], $nuevo['fecha_update']);
            $nuevo = array_replace($nuevo, ['descripcion' => 'timbrado_masivo', 'etiqueta_label' => 'Timbrado masivo',
                'titulo' => 'Timbrado masivo', 'visible' => 'activo', 'status' => 'activo', 'seguridad' => 'activo',
                'es_view' => 'activo', 'es_modal' => 'inactivo', 'es_status' => 'inactivo', 'es_lista' => 'activo',
                'inicio' => 'inactivo', 'lista' => 'activo', 'icono' => 'bi bi-receipt',
                'codigo' => 'timbrado_masivo.'.$origen['adm_seccion_id'], 'codigo_bis' => 'timbrado_masivo.'.$origen['adm_seccion_id'],
                'descripcion_select' => 'Timbrado masivo de facturas', 'alias' => 'timbrado_masivo', 'target' => '']);
            $insertar = $pdo->prepare('INSERT INTO adm_accion (`'.implode('`,`', array_keys($nuevo)).'`) VALUES ('.implode(',', array_fill(0, count($nuevo), '?')).')');
            $insertar->execute(array_values($nuevo));
            $id = (int)$pdo->lastInsertId();
            $grupos = $pdo->prepare("SELECT DISTINCT ag.adm_grupo_id FROM adm_accion_grupo ag
                INNER JOIN adm_grupo g ON g.id=ag.adm_grupo_id AND g.status='activo'
                WHERE ag.adm_accion_id=? AND ag.status='activo' AND EXISTS (
                    SELECT 1 FROM adm_accion_grupo al INNER JOIN adm_accion a ON a.id=al.adm_accion_id
                    WHERE al.adm_grupo_id=ag.adm_grupo_id AND al.status='activo' AND a.status='activo'
                    AND a.adm_seccion_id=? AND a.descripcion='lista')");
            $grupos->execute([$origen['id'], $origen['adm_seccion_id']]);
            $insertar = $pdo->prepare("INSERT INTO adm_accion_grupo (adm_accion_id,adm_grupo_id,status,usuario_alta_id,usuario_update_id,codigo,codigo_bis,descripcion,descripcion_select,predeterminado)
                VALUES (?,?,'activo',?,?,?,?,?,?,'inactivo')");
            foreach ($grupos->fetchAll(PDO::FETCH_COLUMN) as $grupo) {
                $codigo = 'timbrado_masivo.'.$grupo.'.'.$origen['adm_seccion_id'];
                $insertar->execute([$id, $grupo, $origen['usuario_alta_id'], $origen['usuario_update_id'], $codigo, $codigo, 'Timbrado masivo', 'Timbrado masivo']);
                $permisos++;
            }
            $pdo->commit();
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $error;
        }
    }
    echo json_encode(['estado' => 'DESARROLLO_PREPARADO', 'tablas' => $tablas, 'accion_creada' => !$accion,
        'grupos_inicializados' => $permisos, 'permisos_existentes_sin_cambios' => true,
        'facturas_modificadas' => 0, 'llamadas_pac' => 0], JSON_PRETTY_PRINT), PHP_EOL;
} catch (Throwable $error) {
    fwrite(STDERR, 'No se pudo preparar timbrado masivo: '.($error instanceof PDOException ? 'revisar el esquema o permisos de BD.' : $error->getMessage()).PHP_EOL);
    exit(1);
}
