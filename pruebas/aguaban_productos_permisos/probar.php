<?php
declare(strict_types=1);

// Sólo una base VACÍA, independiente del laboratorio UI y de las aplicaciones.
require dirname(__DIR__, 2).'/tools/migrations/_aguaban_productos_permisos.php';
$pdo = null;
$tablas = ['adm_accion_grupo', 'adm_accion', 'adm_seccion_pertenece', 'adm_seccion', 'adm_sistema', 'adm_usuario', 'adm_grupo'];
$creadas = false;
$casos = [];
$aserciones = 0;
$codigo = 0;
$resultado = null;
/** Cambia sólo el nombre declarado para probar el guard, nunca el destino SQL real. */
final class ConexionDeclaradaProduccion extends PDO {
    public function query(string $query, ?int $fetchMode = null, mixed ...$args): PDOStatement|false {
        $sql = $query === 'SELECT DATABASE()' ? "SELECT 'aguaban'" : $query;
        return $fetchMode === null ? parent::query($sql) : parent::query($sql, $fetchMode, ...$args);
    }
}
function verificar(bool $ok, string $mensaje): void {
    global $aserciones;
    $aserciones++;
    if (!$ok) throw new RuntimeException($mensaje);
}
function filas(): array {
    global $pdo, $tablas;
    $r = [];
    foreach ($tablas as $tabla) $r[$tabla] = $pdo->query('SELECT * FROM '.$tabla.' ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    return $r;
}
function preparar(): void {
    global $pdo, $tablas;
    foreach ($tablas as $t) $pdo->exec('DELETE FROM '.$t);
    $pdo->exec("INSERT INTO adm_grupo VALUES (2,'PA','Administrador Sistema','activo','activo'),
        (6,'administracion_clientes','administracion_clientes','activo','inactivo'),(9,'otro','Otro perfil','activo','inactivo')");
    $pdo->exec("INSERT INTO adm_usuario VALUES (1,'admin',2,'activo','SIN_LOGIN'),(2,'administracion_clientes',6,'activo','SIN_LOGIN')");
    $pdo->exec("INSERT INTO adm_sistema VALUES (1,'facturacion','facturacion','activo')");
    $pdo->exec("INSERT INTO adm_seccion VALUES (10,'com_producto','activo'),(11,'com_tipo_producto','activo'),
        (12,'cat_sat_cve_prod','activo'),(13,'com_cliente','activo')");
    $pdo->exec("INSERT INTO adm_seccion_pertenece VALUES (1,10,1,'activo'),(2,11,1,'activo')");
    $q = $pdo->prepare('INSERT INTO adm_accion (id,descripcion,adm_seccion_id,seguridad,usuario_alta_id,usuario_update_id) VALUES (?,?,?,?,1,1)');
    $id = 100;
    foreach ([10,11] as $seccion) {
        foreach (AguabanProductosPermisos::CRUD as $accion) {
            if ($seccion === 10 && $accion === 'status') continue;
            $q->execute([$id++, $accion, $seccion, $seccion === 11 && $accion === 'get_data' ? 'inactivo' : 'activo']);
        }
    }
    $q->execute([$id++, 'get_data_descripcion', 12, 'activo']);
    for ($i=1;$i<=27;$i++) {
        $q->execute([1000+$i, 'base_'.$i, 13, 'activo']);
        $pdo->exec("INSERT INTO adm_accion_grupo (adm_accion_id,adm_grupo_id,status,usuario_alta_id,usuario_update_id) VALUES (".(1000+$i).",6,'activo',1,1)");
    }
    $pdo->exec("INSERT INTO adm_accion_grupo (adm_accion_id,adm_grupo_id,status,usuario_alta_id,usuario_update_id)
        SELECT id,2,'activo',1,1 FROM adm_accion WHERE adm_seccion_id=11 AND descripcion='get_data'");
    $pdo->exec("INSERT INTO adm_accion_grupo (adm_accion_id,adm_grupo_id,status,usuario_alta_id,usuario_update_id) VALUES (1001,9,'activo',1,1)");
}
function aplicar(?callable $extra = null): array {
    global $pdo;
    return AguabanProductosPermisos::preparar($pdo, true, true, $extra ?? static function(array $d): void {});
}
function rechazar(callable $operacion, string $mensaje): void {
    $error = false;
    try { $operacion(); } catch (RuntimeException $e) { $error = true; }
    verificar($error, $mensaje);
}
try {
    $dsn = getenv('QA_PRODUCTOS_DSN');
    if (!$dsn || !getenv('QA_PRODUCTOS_DB_USER')) throw new RuntimeException('Configura el laboratorio efímero QA_PRODUCTOS_DSN/DB_USER/DB_PASSWORD.');
    $pdo = new PDO($dsn, getenv('QA_PRODUCTOS_DB_USER'), getenv('QA_PRODUCTOS_DB_PASSWORD') ?: '',
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES=>false]);
    $base = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
    if (!preg_match('/\Aqa_productos_permisos_[a-zA-Z0-9_]+\z/', $base)) throw new RuntimeException('La base no es un laboratorio independiente de permisos.');
    $q=$pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=?');$q->execute([$base]);
    if ((int)$q->fetchColumn() !== 0) throw new RuntimeException('La base debe estar completamente vacía; no se limpiarán tablas existentes.');
    $creadas = true;
    $pdo->exec("CREATE TABLE adm_grupo (id BIGINT PRIMARY KEY,codigo VARCHAR(100),descripcion VARCHAR(200),status VARCHAR(20),root VARCHAR(20)) ENGINE=InnoDB");
    $pdo->exec("CREATE TABLE adm_usuario (id BIGINT PRIMARY KEY,user VARCHAR(100),adm_grupo_id BIGINT,status VARCHAR(20),password VARCHAR(100)) ENGINE=InnoDB");
    $pdo->exec("CREATE TABLE adm_sistema (id BIGINT PRIMARY KEY,codigo VARCHAR(100),descripcion VARCHAR(100),status VARCHAR(20)) ENGINE=InnoDB");
    $pdo->exec("CREATE TABLE adm_seccion (id BIGINT PRIMARY KEY,descripcion VARCHAR(100),status VARCHAR(20)) ENGINE=InnoDB");
    $pdo->exec("CREATE TABLE adm_seccion_pertenece (id BIGINT PRIMARY KEY,adm_seccion_id BIGINT,adm_sistema_id BIGINT,status VARCHAR(20)) ENGINE=InnoDB");
    $pdo->exec("CREATE TABLE adm_accion (id BIGINT AUTO_INCREMENT PRIMARY KEY,descripcion VARCHAR(200),adm_seccion_id BIGINT,
        status VARCHAR(20) DEFAULT 'activo',seguridad VARCHAR(20) DEFAULT 'activo',usuario_alta_id BIGINT,usuario_update_id BIGINT,
        fecha_alta TIMESTAMP DEFAULT '2020-01-01 00:00:00',fecha_update TIMESTAMP DEFAULT '2020-01-01 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
        codigo VARCHAR(255),codigo_bis VARCHAR(255),alias VARCHAR(255),etiqueta_label VARCHAR(255),descripcion_select VARCHAR(255),titulo VARCHAR(255)) ENGINE=InnoDB");
    $pdo->exec("CREATE TABLE adm_accion_grupo (id BIGINT AUTO_INCREMENT PRIMARY KEY,adm_accion_id BIGINT,adm_grupo_id BIGINT,status VARCHAR(20),
        usuario_alta_id BIGINT,usuario_update_id BIGINT,fecha_alta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        fecha_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,codigo VARCHAR(255),codigo_bis VARCHAR(255),
        descripcion VARCHAR(255),descripcion_select VARCHAR(255),predeterminado VARCHAR(255),
        UNIQUE KEY permiso_unico(adm_accion_id,adm_grupo_id),FOREIGN KEY(adm_accion_id) REFERENCES adm_accion(id),
        FOREIGN KEY(adm_grupo_id) REFERENCES adm_grupo(id)) ENGINE=InnoDB");

    preparar();$antes=filas();$plan=AguabanProductosPermisos::preparar($pdo,false,true);
    verificar(count($plan['pendientes'])===18 && $plan['crear_status_producto'] && $plan['proteger_get_data_tipo'],'Plan incompleto');
    verificar($antes===filas(),'Plan escribió datos');$casos[]='plan_sin_escrituras_18_pendientes';

    $fases=[];$diario=aplicar(static function(array $d)use(&$fases,$pdo){$fases[]=['estado'=>$d['estado'],'transaccion'=>$pdo->inTransaction()];});
    verificar(count($diario['permisos_creados'])===18 && $diario['accion_creada']!==null && $diario['seguridad_modificada']!==null,'Delta distinto del autorizado');
    verificar((int)$pdo->query("SELECT COUNT(*) FROM adm_accion_grupo WHERE adm_grupo_id=6 AND status='activo'")->fetchColumn()===44,'Perfil no terminó con 44 permisos');
    verificar($fases===[['estado'=>'PENDIENTE_CONFIRMACION','transaccion'=>true],['estado'=>'APLICADO','transaccion'=>false]],'El diario no antecede al commit');$casos[]='aplicar_44_cliente_admin_y_diario_antes_commit';

    $segundo=aplicar();verificar($segundo['permisos_creados']===[] && $segundo['accion_creada']===null && $segundo['seguridad_modificada']===null,'Reaplicación no idempotente');$casos[]='reaplicacion_idempotente';
    AguabanProductosPermisos::revertir($pdo,$diario,true);verificar($antes===filas(),'Reversión alteró filas originales');$casos[]='rollback_solo_filas_propias_y_timestamp_original';
    AguabanProductosPermisos::revertir($pdo,$diario,true);verificar($antes===filas(),'Reversión repetida alteró originales');$casos[]='rollback_idempotente';

    preparar();$antes=filas();rechazar(static fn()=>aplicar(static function(array $d){throw new RuntimeException('fallo de escritura del diario');}),'Confirmó sin diario');
    verificar($antes===filas(),'Fallo de diario no revirtió');$casos[]='fallo_diario_revierte_transaccion';

    preparar();$antes=filas();$pendiente=null;
    rechazar(static function()use(&$pendiente){aplicar(static function(array $d)use(&$pendiente){
        if($d['estado']==='PENDIENTE_CONFIRMACION')$pendiente=$d;
        else throw new RuntimeException('Fallo del segundo guardado, después del commit');
    });},'No propagó fallo de diario posterior al commit');
    verificar(!$pdo->inTransaction() && count($pendiente['permisos_creados']??[])===18,'El diario previo no permite recuperar el commit');
    verificar((int)$pdo->query("SELECT COUNT(*) FROM adm_accion_grupo WHERE adm_grupo_id=6 AND status='activo'")->fetchColumn()===44,'La confirmación posterior se confundió con rollback');
    AguabanProductosPermisos::revertir($pdo,$pendiente,true);verificar($antes===filas(),'El diario pendiente no revirtió el commit realizado');$casos[]='diario_pendiente_recupera_fallo_despues_commit';

    preparar();$antes=filas();rechazar(static fn()=>AguabanProductosPermisos::preparar($pdo,true,true),'Permitió aplicar sin diario');
    verificar($antes===filas(),'Aplicar sin diario escribió');$casos[]='aplicar_exige_diario';
    rechazar(static fn()=>AguabanProductosPermisos::preparar($pdo,false,false),'Permitió laboratorio sin opt-in');$casos[]='laboratorio_exige_optin';
    $produccionDeclarada = new ConexionDeclaradaProduccion($dsn, getenv('QA_PRODUCTOS_DB_USER'), getenv('QA_PRODUCTOS_DB_PASSWORD') ?: '',
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES=>false]);
    rechazar(static fn()=>AguabanProductosPermisos::preparar($produccionDeclarada,false,true),'Aceptó Aguaban con bandera de desarrollo');
    verificar(!$produccionDeclarada->inTransaction() && $antes===filas(),'El guard de producción permitió una transacción o modificó datos');$casos[]='aguaban_rechaza_bandera_desarrollo_antes_transaccion';

    preparar();$pdo->exec("UPDATE adm_grupo SET root='activo' WHERE id=6");$antes=filas();rechazar(static fn()=>aplicar(),'Elevó perfil root');verificar($antes===filas(),'Cambió perfil root');$casos[]='perfil_root_rechazado';
    preparar();$pdo->exec("DELETE FROM adm_accion WHERE adm_seccion_id=11 AND descripcion='modifica'");$antes=filas();rechazar(static fn()=>aplicar(),'Inventó acción faltante');verificar($antes===filas(),'Acción faltante produjo cambios');$casos[]='accion_faltante_sin_cambios';
    preparar();$pdo->exec("INSERT INTO adm_accion (descripcion,adm_seccion_id,seguridad,usuario_alta_id,usuario_update_id) VALUES ('lista',10,'activo',1,1)");$antes=filas();rechazar(static fn()=>aplicar(),'Aceptó catálogo duplicado');verificar($antes===filas(),'Duplicado produjo cambios');$casos[]='accion_duplicada_rechazada';

    preparar();$pdo->exec("INSERT INTO adm_accion_grupo(adm_accion_id,adm_grupo_id,status,usuario_alta_id,usuario_update_id)
        SELECT id,6,'inactivo',1,1 FROM adm_accion WHERE adm_seccion_id=10 AND descripcion='lista'");$antes=filas();rechazar(static fn()=>aplicar(),'Reactivó permiso revocado');verificar($antes===filas(),'Cambió revocación');$casos[]='revocacion_preexistente_no_se_reactiva';

    preparar();$d=aplicar();$id=$d['permisos_creados'][0]['id'];$pdo->exec("UPDATE adm_accion_grupo SET descripcion='Cambio ajeno' WHERE id=".$id);$antes=filas();rechazar(static fn()=>AguabanProductosPermisos::revertir($pdo,$d,true),'Borró permiso modificado');verificar($antes===filas(),'Reversión parcial en conflicto');$casos[]='rollback_protege_permiso_editado';
    preparar();$d=aplicar();$id=$d['seguridad_modificada']['antes']['id'];$pdo->exec("UPDATE adm_accion SET titulo='Cambio ajeno' WHERE id=".$id);$antes=filas();rechazar(static fn()=>AguabanProductosPermisos::revertir($pdo,$d,true),'Pisó metadatos editados');verificar($antes===filas(),'Rollback pisó acción ajena');$casos[]='rollback_protege_accion_editada';
    preparar();$d=aplicar();$id=$d['accion_creada']['id'];$pdo->exec("INSERT INTO adm_accion_grupo(adm_accion_id,adm_grupo_id,status,usuario_alta_id,usuario_update_id) VALUES (".$id.",9,'activo',1,1)");$antes=filas();rechazar(static fn()=>AguabanProductosPermisos::revertir($pdo,$d,true),'Eliminó acción usada por otro grupo');verificar($antes===filas(),'Conflicto de acción hizo rollback parcial');$casos[]='rollback_protege_nuevos_permisos_ajenos';
    $resultado=['estado'=>'PASS_AGUABAN_PRODUCTOS_PERMISOS','casos'=>$casos,'casos_ejecutados'=>count($casos),'aserciones'=>$aserciones,'motor_real'=>true,'datos_ficticios'=>true];
} catch(Throwable $error) {
    fwrite(STDERR,'Prueba de permisos bloqueada: '.get_class($error).': '.$error->getMessage().PHP_EOL);$codigo=1;
} finally {
    if($pdo instanceof PDO && $creadas){
        if($pdo->inTransaction())$pdo->rollBack();
        foreach($tablas as $t)$pdo->exec('DROP TABLE IF EXISTS '.$t);
    }
}

if($codigo===0){$resultado["limpieza_confirmada"]=true;echo json_encode($resultado,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR),PHP_EOL;}
exit($codigo);
