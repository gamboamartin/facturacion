<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/tools/migrations/_descargas_complemento_admin.php';
$pdo = null;
$creadas = false;
$tablas = ['adm_accion_grupo','adm_accion','adm_seccion','adm_usuario','adm_grupo'];
function verifica(bool $ok, string $msg): void { if (!$ok) throw new RuntimeException($msg); }
function instantanea(PDO $pdo,array $tablas): string {
    return json_encode(array_map(static fn($t)=>$pdo->query('SELECT * FROM '.$t.' ORDER BY id')->fetchAll(PDO::FETCH_ASSOC),$tablas), JSON_THROW_ON_ERROR);
}
try {
    $dsn = getenv('QA_PRODUCTOS_DSN');
    verifica(is_string($dsn) && str_starts_with($dsn,'mysql:unix_socket='),'QA requiere socket local aislado');
    $pdo = new PDO($dsn,getenv('QA_PRODUCTOS_DB_USER'),getenv('QA_PRODUCTOS_DB_PASSWORD'),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    verifica(preg_match('/\Aqa_productos_permisos_[a-zA-Z0-9_]+\z/',(string)$pdo->query('SELECT DATABASE()')->fetchColumn())===1,'BD de QA no válida');
    verifica((int)$pdo->query('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE()')->fetchColumn()===0,'BD QA no vacía');
    verifica((int)$pdo->query('SELECT @@skip_networking')->fetchColumn()===1,'QA debe estar sin red');
    $creadas = true;
    $pdo->exec("CREATE TABLE adm_grupo(id INT PRIMARY KEY,codigo VARCHAR(20),descripcion VARCHAR(100),status VARCHAR(20),root VARCHAR(20)) ENGINE=InnoDB");
    $pdo->exec("CREATE TABLE adm_usuario(id INT PRIMARY KEY,user VARCHAR(100),adm_grupo_id INT,status VARCHAR(20)) ENGINE=InnoDB");
    $pdo->exec("CREATE TABLE adm_seccion(id INT PRIMARY KEY,descripcion VARCHAR(100),status VARCHAR(20)) ENGINE=InnoDB");
    $pdo->exec("CREATE TABLE adm_accion(id INT AUTO_INCREMENT PRIMARY KEY,adm_seccion_id INT,descripcion VARCHAR(100),status VARCHAR(20),seguridad VARCHAR(20),es_view VARCHAR(20),codigo VARCHAR(200) UNIQUE,codigo_bis VARCHAR(200),usuario_alta_id INT,usuario_update_id INT,fecha_alta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,fecha_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP,UNIQUE KEY seccion_accion(adm_seccion_id,descripcion)) ENGINE=InnoDB");
    $pdo->exec("CREATE TABLE adm_accion_grupo(id INT AUTO_INCREMENT PRIMARY KEY,adm_accion_id INT,adm_grupo_id INT,status VARCHAR(20),usuario_alta_id INT,usuario_update_id INT,codigo VARCHAR(200),descripcion VARCHAR(200),predeterminado VARCHAR(20),UNIQUE KEY accion_grupo(adm_accion_id,adm_grupo_id),FOREIGN KEY(adm_accion_id) REFERENCES adm_accion(id),FOREIGN KEY(adm_grupo_id) REFERENCES adm_grupo(id)) ENGINE=InnoDB");
    $pdo->exec("INSERT INTO adm_grupo VALUES(2,'PA','Administrador Sistema','activo','activo'),(3,'PS','Sistema','activo','inactivo')");
    $pdo->exec("INSERT INTO adm_usuario VALUES(1,'admin',2,'activo'),(2,'usuario_qa',3,'activo')");
    $pdo->exec("INSERT INTO adm_seccion VALUES(45,'fc_factura','activo'),(166,'fc_complemento_pago','activo')");
    $q=$pdo->prepare("INSERT INTO adm_accion VALUES(NULL,45,?,'activo','activo',?,?,?,1,1,DEFAULT,DEFAULT)");
    foreach(DescargasComplementoAdmin::ACCIONES as $a) {$q->execute([$a,$a==='descargar_por_separado'?'activo':'inactivo',$a.'fc_factura',strtoupper($a.'fc_factura')]);}
    $pdo->exec("INSERT INTO adm_accion_grupo(adm_accion_id,adm_grupo_id,status,usuario_alta_id,usuario_update_id,codigo,descripcion,predeterminado) SELECT id,2,'activo',1,1,CONCAT(id,'.2'),'QA','inactivo' FROM adm_accion");
    $antes=instantanea($pdo,$tablas);
    $plan=DescargasComplementoAdmin::preparar($pdo);
    verifica(count($plan['pendientes'])===6 && instantanea($pdo,$tablas)===$antes,'Plan escribió o fue incompleto');
    $casos=['plan_sin_escrituras'];
    try { DescargasComplementoAdmin::preparar($pdo,true,static function(){throw new RuntimeException('QA diario');}); throw new LogicException('No rechazó diario'); }
    catch(RuntimeException $e){verifica($e->getMessage()==='QA diario' && instantanea($pdo,$tablas)===$antes,'Rollback incompleto');}
    $casos[]='fallo_diario_revierte';
    $diario=null;
    $r=DescargasComplementoAdmin::preparar($pdo,true,static function($d) use (&$diario){$diario=$d;});
    verifica($r===$diario && count($r['acciones_creadas'])===3 && count($r['permisos_creados'])===3,'Diario incorrecto');
    verifica((int)$pdo->query("SELECT COUNT(*) FROM adm_accion a JOIN adm_accion_grupo g ON a.id=g.adm_accion_id WHERE a.adm_seccion_id=166 AND a.seguridad='activo' AND g.status='activo' AND g.adm_grupo_id=2")->fetchColumn()===3,'Acciones PA incompletas');
    verifica((int)$pdo->query("SELECT COUNT(*) FROM adm_accion_grupo WHERE adm_grupo_id<>2")->fetchColumn()===0,'Se concedió a otro grupo');
    $casos[]='solo_permisos_PA';
    $despues=instantanea($pdo,$tablas);
    $r=DescargasComplementoAdmin::preparar($pdo,true,static function($d){});
    verifica($r['pendientes']===[] && instantanea($pdo,$tablas)===$despues,'No idempotente');
    $casos[]='idempotente';
    $pdo->exec("UPDATE adm_accion_grupo SET status='inactivo' WHERE adm_accion_id=(SELECT id FROM adm_accion WHERE adm_seccion_id=166 AND descripcion='descarga_xml')");
    $antes=instantanea($pdo,$tablas);
    try { DescargasComplementoAdmin::preparar($pdo,true,static function($d){}); throw new LogicException('No rechazó permiso revocado'); }
    catch(RuntimeException $e){verifica(instantanea($pdo,$tablas)===$antes,'Reactivó revocación');}
    $casos[]='respeta_revocaciones';
    $controlador=(object)['registro'=>(object)['genera_pdf'=>'index.php?seccion=fc_complemento_pago&accion=genera_pdf&registro_id=7','descarga_xml'=>'index.php?seccion=fc_complemento_pago&accion=descarga_xml&registro_id=7']];
    ob_start();require dirname(__DIR__,2).'/views/fc_complemento_pago/descargar_por_separado.php';$html=ob_get_clean();
    verifica(substr_count($html,'<iframe')===2 && str_contains($html,'&amp;accion=genera_pdf&amp;') && str_contains($html,'&amp;accion=descarga_xml&amp;'),'Vista no genera ambas descargas');
    $casos[]='vista_dos_descargas';
    verifica(str_contains($html,'Descargar PDF') && str_contains($html,'Descargar XML') && substr_count($html,'target="_blank"')===2,'Faltan enlaces individuales para reintentar');
    $casos[]='enlaces_individuales_disponibles';
    $controlador->registro->genera_pdf='';$controlador->registro->descarga_xml='';
    ob_start();require dirname(__DIR__,2).'/views/fc_complemento_pago/descargar_por_separado.php';$sin=ob_get_clean();
    verifica(!str_contains($sin,'<iframe') && !str_contains($sin,'href=') && str_contains($sin,'No hay enlaces de descarga disponibles'),'Render de sesión sin permisos genera peticiones');
    $casos[]='sin_enlaces_no_hay_peticiones';
    $js=file_get_contents(dirname(__DIR__,2).'/js/fc_complemento_pago/descargar_por_separado.js');
    verifica(!preg_match('/\b(?:setTimeout|setInterval)\s*\(|\bwindow\s*\.\s*close\s*\(/',$js),'El temporizador vuelve a cerrar descargas');
    $casos[]='sin_cierre_automatico';

    echo json_encode(['estado'=>'PASS_DESCARGAS_COMPLEMENTO','casos'=>$casos],JSON_THROW_ON_ERROR).PHP_EOL;
} finally {
    if($pdo instanceof PDO && $creadas){if($pdo->inTransaction())$pdo->rollBack();foreach($tablas as $t)$pdo->exec('DROP TABLE IF EXISTS '.$t);}
}
