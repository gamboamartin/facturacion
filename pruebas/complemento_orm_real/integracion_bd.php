<?php
declare(strict_types=1);
use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\{_complemento_desde_factura,fc_complemento_pago,fc_pago_pago,fc_docto_relacionado};
require '/qa/bootstrap.php';
require __DIR__.'/fixture.php';
require __DIR__.'/iva.php';
if((new config\database())->db_name!=='qa_csd_integral')throw new RuntimeException('Base efímera requerida');
$link=new PDO('mysql:unix_socket=/run/mysqld/mysqld.sock;dbname=qa_csd_integral','qa_csd_integral','QA-EFIMERA-SIN-VALIDEZ',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$link->exec("SET sql_mode=''");
$casos=[];$aserciones=0;$fallo=null;$limpieza=false;$fuentes=[];$codigo=1;
function exigir_orm(bool $v,string $n):void{global $aserciones;$aserciones++;if(!$v)throw new RuntimeException($n);}
function caso_orm(string $n,callable $p):void{global $casos;errores::$error=false;$p();exigir_orm(!errores::$error,'estado_error_inesperado');$casos[]=$n;}
function snapshot_orm(PDO $p):array{$a=[];foreach(['fc_complemento_pago','fc_partida_cp','fc_pago','fc_pago_total','fc_pago_pago','fc_docto_relacionado','fc_complemento_pago_etapa']as$t)$a[$t]=$p->query('SELECT * FROM '.$t.' ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);$a['saldo']=$p->query('SELECT saldo,monto_pago_cp FROM fc_factura WHERE id=1')->fetch(PDO::FETCH_ASSOC);return $a;}
ob_start();
try{
 $insertar=fixture_cp_orm($link);
 foreach([_complemento_desde_factura::class,fc_complemento_pago::class,fc_pago_pago::class,fc_docto_relacionado::class,\gamboamartin\facturacion\models\_saldos_fc::class,\base\orm\modelo::class,\base\orm\inserts::class,\gamboamartin\proceso\models\pr_proceso::class,\gamboamartin\comercial\models\com_tipo_cambio::class]as$c){$f=(new ReflectionClass($c))->getFileName();$fuentes[$c]=['archivo'=>$f,'sha256'=>hash_file('sha256',$f)];}
 $svc=new _complemento_desde_factura($link,1,static fn():bool=>true);
 $datos=['clave_solicitud'=>str_repeat('a',32),'monto'=>'40.00','fecha_pago'=>'2026-11-24T12:00','fc_csd_id'=>1,'cat_sat_forma_pago_id'=>2,'com_tipo_cambio_id'=>1];
 caso_orm('alta_real_estructura_saldos_totales',function()use($svc,$datos,$link){
  $fk=(int)$link->query('SELECT @@foreign_key_checks')->fetchColumn();exigir_orm($fk===1,'claves_foraneas_activas');
  $r=$svc->crear(1,$datos);exigir_orm($r['reutilizado']===false,'alta_nueva');$id=$r['fc_complemento_pago_id'];
  $q=$link->query('SELECT d.imp_pagado,d.num_parcialidad,d.imp_saldo_ant,d.imp_saldo_insoluto,p.id pago_id,pp.id pago_pago_id FROM fc_docto_relacionado d JOIN fc_pago_pago pp ON pp.id=d.fc_pago_pago_id JOIN fc_pago p ON p.id=pp.fc_pago_id WHERE p.fc_complemento_pago_id='.(int)$id)->fetchAll(PDO::FETCH_ASSOC);
  exigir_orm(count($q)===1,'enlace_completo');$d=$q[0];exigir_orm((float)$d['imp_pagado']===40.0&&(int)$d['num_parcialidad']===1,'monto_parcialidad');exigir_orm((float)$d['imp_saldo_ant']===100.0&&(float)$d['imp_saldo_insoluto']===60.0,'saldos_docto');
  exigir_orm((float)$link->query('SELECT saldo FROM fc_factura WHERE id=1')->fetchColumn()===60.0,'saldo_factura');
  exigir_orm((int)$link->query('SELECT COUNT(*) FROM fc_partida_cp WHERE fc_complemento_pago_id='.(int)$id)->fetchColumn()===1,'partida_cp_real');
  exigir_orm((float)$link->query('SELECT monto_total_pagos FROM fc_pago_total WHERE fc_pago_id='.(int)$d['pago_id'])->fetchColumn()===40.0,'totales_pago_real');
 });
 caso_orm('reintento_real_no_duplica',function()use($svc,$datos,$link){$a=snapshot_orm($link);$r=$svc->crear(1,$datos);exigir_orm($r['reutilizado']===true,'reutilizado');exigir_orm(snapshot_orm($link)===$a,'sin_duplicar');});
 caso_orm('segunda_parcialidad_real',function()use($svc,$datos,$link){$d=$datos;$d['clave_solicitud']=str_repeat('b',32);$d['monto']='10.00';$svc->crear(1,$d);$r=$link->query('SELECT num_parcialidad,imp_saldo_ant,imp_saldo_insoluto FROM fc_docto_relacionado ORDER BY id DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC);exigir_orm((int)$r['num_parcialidad']===2&&(float)$r['imp_saldo_ant']===60.0&&(float)$r['imp_saldo_insoluto']===50.0,'segunda_saldos');});
 caso_orm('fallo_docto_revierte_cadena_real',function()use($svc,$datos,$link){
  $antes=snapshot_orm($link);$link->exec("CREATE TRIGGER qa_fallo_docto BEFORE INSERT ON fc_docto_relacionado FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='QA_FALLO_CONTROLADO'");
  $rechazado=false;try{$d=$datos;$d['clave_solicitud']=str_repeat('c',32);$d['monto']='5.00';$svc->crear(1,$d);}catch(Throwable $e){$rechazado=true;}finally{$link->exec('DROP TRIGGER qa_fallo_docto');}
  exigir_orm($rechazado,'fallo_propagado');exigir_orm(str_contains(json_encode($_SESSION['error_resultado']??[],JSON_THROW_ON_ERROR),'QA_FALLO_CONTROLADO'),'fallo_inyectado_alcanzado');exigir_orm(!$link->inTransaction(),'sin_transaccion_abierta');exigir_orm(snapshot_orm($link)===$antes,'rollback_cabecera_partida_pagos_totales_saldo');errores::$error=false;
 });
 caso_orm('iva_16_real_traslados_y_totales',function()use($link,$insertar){probar_iva_orm($link,$insertar);});
 $codigo=0;
}catch(Throwable $e){$fallo=['tipo'=>get_class($e),'mensaje'=>$e->getMessage()];$mensajes=[];$rec=function($x)use(&$rec,&$mensajes){if(is_array($x)){if(isset($x['mensaje_limpio']))$mensajes[]=$x['mensaje_limpio'];foreach($x as $v)if(is_array($v))$rec($v);}};$rec($_SESSION['error_resultado']??[]);$rec(errores::$out);$fallo['modelo_mensajes']=array_values(array_unique($mensajes));}
finally{
 if($link->inTransaction())$link->rollBack();
 $tables=$link->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);$link->exec('SET FOREIGN_KEY_CHECKS=0');foreach($tables as$t)$link->exec('DROP TABLE `'.$t.'`');$link->exec('SET FOREIGN_KEY_CHECKS=1');$limpieza=$link->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN)===[];
 $salida=ob_get_clean();
}
echo json_encode(['estado'=>$codigo===0?'PASS_COMPLEMENTO_ORM_REAL_BD':'BLOQUEADO','casos'=>$casos,'casos_ejecutados'=>count($casos),'aserciones'=>$aserciones,'fuentes'=>$fuentes,'fallo'=>$fallo,'limpieza_confirmada'=>$limpieza,'salida_interna_presente'=>$salida!=='','sql_mode'=>'','sin_sql_estricto'=>true,'alcance'=>'Cadena ORM real en motor efímero; datos SQL ficticios, sin certificados ni PAC.'],JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),"\n";
exit($codigo);
