<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/tools/migrations/_descargas_documentos_permisos.php';
$pdo=null;$creadas=false;$casos=[];$aserciones=0;
$tablas=['adm_accion_grupo','adm_accion','adm_seccion','adm_usuario','adm_grupo'];
function exigir(bool $ok,string $mensaje):void {global $aserciones;$aserciones++;if(!$ok)throw new RuntimeException($mensaje);}
function datos():array {global $pdo,$tablas;$r=[];foreach($tablas as$t)$r[$t]=$pdo->query('SELECT * FROM '.$t.' ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);return$r;}
function fixture():void {
 global $pdo,$tablas;
 foreach($tablas as$t)$pdo->exec('DELETE FROM '.$t);
 $pdo->exec("INSERT INTO adm_grupo VALUES(2,'PA','Administrador Sistema','activo','activo'),(3,'Sistema','Sistema','activo','inactivo'),(9,'OTRO','Otro','activo','inactivo')");
 $pdo->exec("INSERT INTO adm_usuario VALUES(1,'admin',2,'activo'),(2,'principal',3,'activo')");
 $pdo->exec("INSERT INTO adm_seccion VALUES(45,'fc_factura','activo'),(66,'fc_nota_credito','activo'),(166,'fc_complemento_pago','activo')");
 $q=$pdo->prepare("INSERT INTO adm_accion(id,adm_seccion_id,descripcion,status,seguridad,es_view,codigo,codigo_bis,usuario_alta_id,usuario_update_id) VALUES(?,?,?,'activo','activo',?,?,?,1,1)");
 $id=100;
 foreach([45,66,166]as$s) foreach(DescargasDocumentosPermisos::ACCIONES as$a) {
  if($s!==45&&!in_array($a,['lista','get_data'],true))continue;
  $q->execute([$id++,$s,$a,in_array($a,['lista','descargar_por_separado'],true)?'activo':'inactivo',$a.$s,strtoupper($a.$s)]);
 }
 $pdo->exec("INSERT INTO adm_accion_grupo(adm_accion_id,adm_grupo_id,status,usuario_alta_id,usuario_update_id,codigo,descripcion,predeterminado) SELECT id,2,'activo',1,1,CONCAT(id,'.2'),'ADMIN','inactivo' FROM adm_accion WHERE adm_seccion_id=45");
 $pdo->exec("INSERT INTO adm_accion_grupo(adm_accion_id,adm_grupo_id,status,usuario_alta_id,usuario_update_id,codigo,descripcion,predeterminado) SELECT id,3,'activo',1,1,CONCAT(id,'.3'),'PRINCIPAL','inactivo' FROM adm_accion WHERE adm_seccion_id=45 AND descripcion IN('lista','get_data')");
 $pdo->exec("INSERT INTO adm_accion_grupo(adm_accion_id,adm_grupo_id,status,usuario_alta_id,usuario_update_id,codigo,descripcion,predeterminado) SELECT id,9,'activo',1,1,CONCAT(id,'.9'),'OTRO','inactivo' FROM adm_accion WHERE adm_seccion_id=45 AND descripcion='get_data'");
}
function plan():array {global$pdo;return DescargasDocumentosPermisos::preparar($pdo,'Sistema');}
function aplicar(?callable$guardar=null,?array$p=null):array {global$pdo;$p??=plan();return DescargasDocumentosPermisos::preparar($pdo,'Sistema',true,$guardar??static function(array$d):void{},$p['identidades'],$p['antes_sha256']);}
function rechazar(callable$accion,string$nombre):void {
 global$casos;$antes=datos();$rechazo=false;
 try{$accion();}catch(RuntimeException$e){$rechazo=true;}
 exigir($rechazo,'No rechazó '.$nombre);exigir(datos()===$antes,'Mutación al rechazar '.$nombre);$casos[]=$nombre;
}
try {
 $dsn=getenv('QA_DESCARGAS_DSN')?:getenv('QA_PRODUCTOS_DSN');$user=getenv('QA_DESCARGAS_DB_USER')?:getenv('QA_PRODUCTOS_DB_USER');$pass=getenv('QA_DESCARGAS_DB_PASSWORD')?:getenv('QA_PRODUCTOS_DB_PASSWORD');
 exigir(is_string($dsn)&&str_starts_with($dsn,'mysql:')&&(str_contains($dsn,'host=127.0.0.1')||str_contains($dsn,'host=localhost')||str_contains($dsn,'unix_socket=')),'Sólo MySQL local de QA');
 $pdo=new PDO($dsn,$user,$pass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false]);
 exigir(preg_match('/\Aqa_(descargas|productos_permisos)_[a-zA-Z0-9_]+\z/',(string)$pdo->query('SELECT DATABASE()')->fetchColumn())===1,'Base ajena al laboratorio');
 exigir((int)$pdo->query('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE()')->fetchColumn()===0,'La base QA debe estar vacía');
 $creadas=true;
 $pdo->exec("CREATE TABLE adm_grupo(id INT PRIMARY KEY,codigo VARCHAR(50),descripcion VARCHAR(100),status VARCHAR(20),root VARCHAR(20)) ENGINE=InnoDB");
 $pdo->exec("CREATE TABLE adm_usuario(id INT PRIMARY KEY,user VARCHAR(100),adm_grupo_id INT,status VARCHAR(20)) ENGINE=InnoDB");
 $pdo->exec("CREATE TABLE adm_seccion(id INT PRIMARY KEY,descripcion VARCHAR(100),status VARCHAR(20)) ENGINE=InnoDB");
 $pdo->exec("CREATE TABLE adm_accion(id INT AUTO_INCREMENT PRIMARY KEY,adm_seccion_id INT,descripcion VARCHAR(100),status VARCHAR(20),seguridad VARCHAR(20),es_view VARCHAR(20),codigo VARCHAR(200) UNIQUE,codigo_bis VARCHAR(200),usuario_alta_id INT,usuario_update_id INT,fecha_alta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,fecha_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP,UNIQUE KEY seccion_accion(adm_seccion_id,descripcion)) ENGINE=InnoDB");
 $pdo->exec("CREATE TABLE adm_accion_grupo(id INT AUTO_INCREMENT PRIMARY KEY,adm_accion_id INT,adm_grupo_id INT,status VARCHAR(20),usuario_alta_id INT,usuario_update_id INT,codigo VARCHAR(200),descripcion VARCHAR(200),predeterminado VARCHAR(20),UNIQUE KEY accion_grupo(adm_accion_id,adm_grupo_id),FOREIGN KEY(adm_accion_id) REFERENCES adm_accion(id),FOREIGN KEY(adm_grupo_id) REFERENCES adm_grupo(id)) ENGINE=InnoDB");
 fixture();$antes=datos();$p=plan();exigir(count($p['pendientes'])===29&&datos()===$antes,'Plan incompleto o con escrituras');$casos[]='plan_sin_escrituras';
 rechazar(static fn()=>aplicar(static function(array$d):void{throw new RuntimeException('QA diario');}),'fallo_diario_antes_commit');
 $p=plan();$p['identidades']['principal_sha256']='OTRO';rechazar(static fn()=>aplicar(p:$p),'rechaza_identidad_cambiada');
 $p=plan();$p['antes_sha256']['adm_accion']='OTRO';rechazar(static fn()=>aplicar(p:$p),'rechaza_catalogo_cambiado');
 $estados=[];$j=aplicar(static function(array$d)use(&$estados):void{$estados[]=$d['estado'];});
 exigir($estados===['PENDIENTE_CONFIRMACION','APLICADO']&&count($j['acciones_creadas'])===6&&count($j['permisos_creados'])===23,'Delta/diario incorrectos');
 exigir((int)$pdo->query("SELECT COUNT(*) FROM adm_accion_grupo ag JOIN adm_accion a ON a.id=ag.adm_accion_id WHERE ag.adm_grupo_id IN(2,3) AND ag.status='activo'")->fetchColumn()===30,'Permisos incompletos');
 exigir($pdo->query('SELECT COUNT(*) FROM adm_accion_grupo WHERE adm_grupo_id=9')->fetchColumn()==1,'Se alteró otro grupo');$casos[]='solo_admin_principal_tres_documentos';
 $despues=datos();$r=aplicar();exigir($r['acciones_creadas']===[]&&$r['permisos_creados']===[]&&datos()===$despues,'No idempotente');$casos[]='idempotente';
 $creado=$j['permisos_creados'][0]['id'];$pdo->exec("UPDATE adm_accion_grupo SET descripcion='EDITADO' WHERE id=".$creado);
 rechazar(static fn()=>DescargasDocumentosPermisos::revertir($pdo,$j),'rollback_rechaza_fila_editada');
 $q=$pdo->prepare('UPDATE adm_accion_grupo SET descripcion=? WHERE id=?');foreach($despues['adm_accion_grupo']as$f)if((int)$f['id']===$creado)$q->execute([$f['descripcion'],$creado]);
 $pdo->exec("INSERT INTO adm_accion_grupo(adm_accion_id,adm_grupo_id,status,usuario_alta_id,usuario_update_id,codigo,descripcion,predeterminado) VALUES(".$j['acciones_creadas'][0]['id'].",9,'activo',1,1,'AJENO','AJENO','inactivo')");$ajeno=(int)$pdo->lastInsertId();
 rechazar(static fn()=>DescargasDocumentosPermisos::revertir($pdo,$j),'rollback_rechaza_referencia_ajena');$pdo->exec('DELETE FROM adm_accion_grupo WHERE id='.$ajeno);
 DescargasDocumentosPermisos::revertir($pdo,$j);exigir(datos()===$antes,'Reversión incompleta');$casos[]='rollback_exacto';
 DescargasDocumentosPermisos::revertir($pdo,$j);exigir(datos()===$antes,'Reversión no idempotente');$casos[]='rollback_idempotente';
 $pendiente=null;$llamadas=0;
 try{aplicar(static function(array$d)use(&$pendiente,&$llamadas):void{if(++$llamadas===2)throw new RuntimeException('QA posterior');$pendiente=$d;});throw new LogicException('No falló callback posterior');}catch(RuntimeException$e){exigir($e->getMessage()==='QA posterior','Error diferente');}
 exigir($pendiente['estado']==='PENDIENTE_CONFIRMACION'&&datos()!==$antes,'No quedó diario previo con commit');DescargasDocumentosPermisos::revertir($pdo,$pendiente);exigir(datos()===$antes,'No recuperó journal pendiente');$casos[]='recuperacion_fallo_posterior_commit';
 $pdo->exec("UPDATE adm_accion_grupo SET status='inactivo' WHERE adm_grupo_id=3 AND adm_accion_id=(SELECT id FROM adm_accion WHERE adm_seccion_id=45 AND descripcion='lista')");rechazar(static fn()=>plan(),'respeta_permiso_revocado');fixture();
 rechazar(static fn()=>DescargasDocumentosPermisos::preparar($pdo,'INCORRECTO'),'rechaza_otro_codigo_principal');
 $pdo->exec("UPDATE adm_grupo SET root='inactivo' WHERE id=2");rechazar(static fn()=>plan(),'rechaza_admin_sin_root');fixture();
 $pdo->exec("UPDATE adm_accion SET seguridad='inactivo' WHERE adm_seccion_id=66 AND descripcion='lista'");rechazar(static fn()=>plan(),'rechaza_accion_insegura');fixture();
 $pdo->exec("UPDATE adm_accion SET es_view='activo' WHERE adm_seccion_id=45 AND descripcion='genera_pdf'");rechazar(static fn()=>plan(),'rechaza_tipo_accion_incompatible');fixture();
 $p=plan();$p['base']='OTRA';$p['estado']='APLICADO';rechazar(static fn()=>DescargasDocumentosPermisos::revertir($pdo,$p),'rechaza_diario_otra_base');
 $pdo->exec("UPDATE adm_accion SET seguridad='inactivo' WHERE adm_seccion_id IN(66,166) AND descripcion='get_data'");$legacy=datos();$j=aplicar();
 exigir((int)$pdo->query("SELECT COUNT(*) FROM adm_accion WHERE adm_seccion_id IN(66,166) AND descripcion='get_data' AND seguridad='inactivo'")->fetchColumn()===2,'Cambió flag legacy');DescargasDocumentosPermisos::revertir($pdo,$j);exigir(datos()===$legacy,'No preservó metadata legacy');$casos[]='conserva_get_data_legacy';fixture();
 $privado=sys_get_temp_dir().'/qa-descargas-cli-'.bin2hex(random_bytes(10));exigir(mkdir($privado,0700),'No reservó QA CLI');
 try {
  $app=$privado.'/app';mkdir($app,0700);mkdir($app.'/config',0700);mkdir($app.'/tools',0700);mkdir($app.'/tools/migrations',0700);
  foreach(['_descargas_documentos_permisos.php','descargas_documentos_permisos.php']as$n)copy(dirname(__DIR__,2).'/tools/migrations/'.$n,$app.'/tools/migrations/'.$n);
  $dsnPartes=[];foreach(explode(';',substr($dsn,6))as$parte){if(str_contains($parte,'=')){[$k,$v]=explode('=',$parte,2);$dsnPartes[$k]=$v;}}
  $host=$dsnPartes['host']??'localhost';if(isset($dsnPartes['port']))$host.=';port='.$dsnPartes['port'];if(isset($dsnPartes['unix_socket']))$host.=';unix_socket='.$dsnPartes['unix_socket'];
  $dbname=(string)$pdo->query('SELECT DATABASE()')->fetchColumn();
  $config='<?php namespace config; class database {';
  foreach(['db_host'=>$host,'db_name'=>$dbname,'db_user'=>$user,'db_password'=>$pass]as$k=>$v)$config.='public string $'.$k.'='.var_export($v,true).';';
  file_put_contents($app.'/config/database.php',$config.'}');chmod($app.'/config/database.php',0600);
  file_put_contents($app.'/config/views.php',"<?php namespace config; class views {public string \$url='http://127.0.0.1/';}");chmod($app.'/config/views.php',0600);
  $comando=[PHP_BINARY,'-d','zend.exception_ignore_args=1','-d','xdebug.mode=off',$app.'/tools/migrations/descargas_documentos_permisos.php','--database='.$dbname,'--interface=127.0.0.1','--grupo-principal-codigo=Sistema'];
  $ejecutar=static function(array$args)use($comando):array{
   $proc=proc_open([...$comando,...$args],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);if(!is_resource($proc))throw new RuntimeException('No inició CLI QA');fclose($pipes[0]);$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);return[proc_close($proc),$out,$err];
  };
  $antesCli=datos();[$rc,$stdout,$stderr]=$ejecutar([]);$planCli=json_decode($stdout,true,flags:JSON_THROW_ON_ERROR);exigir($rc===0&&$stderr===''&&$planCli['estado']==='PLAN'&&datos()===$antesCli,'CLI plan falla');$casos[]='cli_plan_sin_escrituras';
  file_put_contents($privado.'/plan.json',$stdout);chmod($privado.'/plan.json',0600);
  [$rc,$stdout,$stderr]=$ejecutar(['--apply','--plan='.$privado.'/plan.json','--journal='.$privado.'/journal.json']);$diarioCli=json_decode(file_get_contents($privado.'/journal.json'),true,flags:JSON_THROW_ON_ERROR);
  exigir($rc===0&&$stderr===''&&$diarioCli['estado']==='APLICADO'&&(fileperms($privado.'/journal.json')&0077)===0,'CLI diario incompleto');$casos[]='cli_apply_diario_durable_privado';
  [$rc]=$ejecutar(['--rollback='.$privado.'/journal.json']);exigir($rc===0&&datos()===$antesCli,'CLI rollback no restaura');$casos[]='cli_rollback_exacto';
  [$rc]=$ejecutar(['--apply','--plan='.$privado.'/plan.json','--journal='.$privado.'/journal.json']);exigir($rc!==0&&datos()===$antesCli,'Reutilizó diario existente');$casos[]='cli_rechaza_diario_existente';
  [$rc]=$ejecutar(['--interface=otro.invalid']);exigir($rc!==0&&datos()===$antesCli,'No protege interfaz');$casos[]='cli_rechaza_otra_interfaz';
 } finally {
  foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($privado,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST)as$f){if($f->isDir())rmdir($f->getPathname());else unlink($f->getPathname());}rmdir($privado);
 }
 $pdo->exec("INSERT INTO adm_grupo VALUES(7,'PA','Administrador Sistema','activo','activo')");$pdo->exec('UPDATE adm_usuario SET adm_grupo_id=7 WHERE id=1');$pdo->exec('UPDATE adm_accion_grupo SET adm_grupo_id=7 WHERE adm_grupo_id=2');$pdo->exec('DELETE FROM adm_grupo WHERE id=2');
 $p=plan();exigir($p['grupos']===[3,7],'Admin asumido por id fijo');$casos[]='admin_detectado_por_identidad';
 $resultado=['estado'=>'PASS_DESCARGAS_PERMISOS','casos'=>$casos,'aserciones'=>$aserciones,'motor_real'=>true];
}finally{if($pdo instanceof PDO&&$creadas){if($pdo->inTransaction())$pdo->rollBack();foreach($tablas as$t)$pdo->exec('DROP TABLE IF EXISTS '.$t);}}

exigir((int)$pdo->query('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE()')->fetchColumn()===0,'La limpieza QA quedó incompleta');
$resultado['limpieza_confirmada']=true;$resultado['aserciones']=$aserciones;echo json_encode($resultado,JSON_THROW_ON_ERROR),PHP_EOL;
