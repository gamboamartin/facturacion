<?php
declare(strict_types=1);

use gamboamartin\facturacion\models\_complemento_desde_factura;
use gamboamartin\facturacion\models\fc_docto_relacionado;
require_once '/qa/bootstrap.php';
$root='/var/www/html/facturacion';
require_once __DIR__.'/fixture.php';
require_once $root.'/tools/migrations/_accion_complemento_desde_factura.php';
require_once $root.'/controllers/_complemento_desde_factura.php';
final class ControladorACLComplementoFixture {
    use \gamboamartin\facturacion\controllers\_complemento_desde_factura {permiso_complemento_desde_factura as public permiso;}
    public function __construct(public PDO $link){}
}
if((new config\database())->db_name!=='qa_csd_integral')throw new RuntimeException('Base efímera requerida');
$pdo=conexion_cp();$pdo->exec("SET sql_mode='STRICT_ALL_TABLES'");
$casos=[];$aserciones=0;$llamadas_cp=0;$permitido_cp=true;$tablas=[];$fallo=null;$limpieza=false;
function exigir_cp(bool $bien,string $codigo):void {global $aserciones;$aserciones++;if(!$bien)throw new RuntimeException($codigo);}
function rechaza_cp(callable $accion,string $codigo):void {try{$accion();}catch(InvalidArgumentException|RuntimeException $e){exigir_cp(true,$codigo);return;}exigir_cp(false,$codigo);}
function caso_cp(string $nombre,callable $accion):void {global $casos;\gamboamartin\errores\errores::$error=false;$accion();$casos[]=$nombre;}
function servicio_cp(int $usuario=7,?callable $callback=null,?PDO $conexion=null):_complemento_desde_factura {
    global $pdo,$permitido_cp;return new _complemento_desde_factura($conexion??$pdo,$usuario,static function()use(&$permitido_cp):bool{return $permitido_cp;},$callback??'estructura_cp');
}
function entrada_cp(array $cambios=[]):array {return array_replace(['clave_solicitud'=>bin2hex(random_bytes(16)),'monto'=>'116.00','fecha_pago'=>date('Y-m-d').'T12:00','cat_sat_forma_pago_id'=>'1','com_tipo_cambio_id'=>'1','fc_csd_id'=>'1'],$cambios);}
function conteos_cp():array {$r=[];foreach(['fc_complemento_pago','fc_pago','fc_pago_pago','fc_docto_relacionado','qa_callback'] as $t)$r[$t]=(int)q_cp('SELECT COUNT(*) FROM '.$t)->fetchColumn();return $r;}
$fuentes=[];
foreach([_complemento_desde_factura::class,AccionComplementoDesdeFactura::class,fc_docto_relacionado::class,\gamboamartin\facturacion\controllers\_complemento_desde_factura::class] as $clase){$f=(new ReflectionClass($clase))->getFileName();$fuentes[$clase]=['archivo'=>$f,'sha256'=>hash_file('sha256',$f)];}
ob_start();
try{
    $tablas=esquema_cp();
    caso_cp('columnas_fixture_contrastadas_con_metadatos_reales',function(){
        $ref=json_decode(file_get_contents(__DIR__.'/esquema_referencia.json'),true,flags:JSON_THROW_ON_ERROR);
        foreach($ref['tablas'] as $tabla=>$datos){
            $cols=q_cp('SHOW COLUMNS FROM '.$tabla)->fetchAll(PDO::FETCH_COLUMN);
            exigir_cp(array_diff($cols,$datos['columnas'])===[],'sin_columnas_inventadas_'.$tabla);
        }
        $rel=$ref['tablas']['fc_nc_rel']['relaciones'];
        exigir_cp(count(array_filter($rel,static fn(array $r):bool=>$r['COLUMN_NAME']==='fc_relacion_nc_id'&&$r['REFERENCED_TABLE_NAME']==='fc_relacion_nc'))===1,'ruta_real_nota_credito');
    });
    caso_cp('catalogo_plan_solo_lectura',function(){
        global $pdo;$antes=(int)q_cp('SELECT COUNT(*) FROM adm_accion')->fetchColumn();$p=AccionComplementoDesdeFactura::preparar($pdo);
        exigir_cp($p['estado']==='PLAN_SOLO_LECTURA'&&$p['grupos_elegibles']===[1],'plan_grupos_minimos');
        exigir_cp((int)q_cp('SELECT COUNT(*) FROM adm_accion')->fetchColumn()===$antes,'plan_sin_accion_creada');
    });
    caso_cp('catalogo_alta_limitada_y_revocacion_preservada',function(){
        global $pdo;$r=AccionComplementoDesdeFactura::preparar($pdo,true);$id=$r['accion_id'];
        exigir_cp($r['grupos_inicializados']===1&&$id>4,'solo_grupo_elegible');
        $r2=AccionComplementoDesdeFactura::preparar($pdo,true);exigir_cp($r2['accion_id']===$id&&$r2['grupos_inicializados']===0,'instalacion_idempotente');
        q_cp('DELETE FROM adm_accion_grupo WHERE adm_accion_id=?',[$id]);q_cp("UPDATE adm_accion SET status='inactivo' WHERE id=?",[$id]);
        $r3=AccionComplementoDesdeFactura::preparar($pdo,true);
        exigir_cp($r3['accion_id']===$id&&(int)q_cp('SELECT COUNT(*) FROM adm_accion_grupo WHERE adm_accion_id=?',[$id])->fetchColumn()===0,'no_repone_permiso_revocado');
        exigir_cp(q_cp('SELECT status FROM adm_accion WHERE id=?',[$id])->fetchColumn()==='inactivo','no_reactiva_accion');
    });
    caso_cp('acl_sql_real_revocacion_ve_estado_actual_en_tx',function(){
        global $pdo;$id=(int)q_cp("SELECT id FROM adm_accion WHERE descripcion='complemento_pago'")->fetchColumn();
        q_cp("UPDATE adm_accion SET status='activo' WHERE id=?",[$id]);q_cp("INSERT INTO adm_accion_grupo(adm_accion_id,adm_grupo_id,status,codigo,codigo_bis) VALUES(?,1,'activo','QA.RESTAURADO','QA.RESTAURADO')",[$id]);
        $_SESSION=['usuario_id'=>1];$c=new ControladorACLComplementoFixture($pdo);exigir_cp($c->permiso(),'acl_sql_cuatro_permisos');
        $pdo->beginTransaction();
        try{
            exigir_cp((int)q_cp('SELECT COUNT(*) FROM adm_accion_grupo')->fetchColumn()>0,'snapshot_permiso_previo');
            $otro=conexion_cp();$q=$otro->prepare("UPDATE adm_accion_grupo SET status='inactivo' WHERE adm_accion_id=? AND adm_grupo_id=1");$q->execute([$id]);
            exigir_cp(!$c->permiso(),'acl_locking_read_ve_revocacion_posterior_snapshot');
        }finally{if($pdo->inTransaction())$pdo->rollBack();}
        exigir_cp(!$c->permiso(),'acl_nueva_peticion_sigue_revocada');$_SESSION=[];
    });
    caso_cp('permiso_revocado_no_lee_ni_crea',function(){
        global $permitido_cp,$llamadas_cp;reiniciar_cp();factura_cp();$s=servicio_cp();$s->preparar(1);$permitido_cp=false;
        rechaza_cp(fn()=>$s->preparar(1),'preparar_revocado');rechaza_cp(fn()=>$s->crear(1,entrada_cp()),'crear_revocado');
        exigir_cp($llamadas_cp===0&&array_sum(conteos_cp())===0,'sin_estructura_revocado');$permitido_cp=true;
    });
    caso_cp('preparar_sin_escrituras_y_codigo_factura_intacto',function(){
        global $pdo;reiniciar_cp();factura_cp();$antes=q_cp('SELECT * FROM fc_factura WHERE id=1')->fetch(PDO::FETCH_ASSOC);
        $pdo->exec('SET TRANSACTION READ ONLY');$pdo->beginTransaction();
        try{$contexto=servicio_cp()->preparar(1);exigir_cp(is_array($contexto)&&$contexto!==[],'contexto_disponible');}finally{$pdo->rollBack();}
        exigir_cp(q_cp('SELECT * FROM fc_factura WHERE id=1')->fetch(PDO::FETCH_ASSOC)===$antes&&array_sum(conteos_cp())===0,'get_servicio_sin_mutaciones');
    });
    caso_cp('crear_estructura_unica_y_datos_releidos',function(){
        global $pdo,$llamadas_cp;reiniciar_cp();factura_cp();$r=servicio_cp()->crear(1,entrada_cp());
        exigir_cp($r['fc_complemento_pago_id']>0&&$r['reutilizado']===false&&$llamadas_cp===1,'creacion_correcta');
        exigir_cp(conteos_cp()===array_fill_keys(['fc_complemento_pago','fc_pago','fc_pago_pago','fc_docto_relacionado','qa_callback'],1),'estructura_atomica_completa');
        $captura=q_cp('SELECT cabecera,pago,docto FROM qa_callback')->fetch(PDO::FETCH_ASSOC);$c=json_decode($captura['cabecera'],true);$p=json_decode($captura['pago'],true);$d=json_decode($captura['docto'],true);
        exigir_cp((int)$c['fc_csd_id']===1&&(int)$c['com_sucursal_id']===1&&(int)$d['fc_factura_id']===1,'identidades_servidor');
        foreach(['total_descuento','sub_total_base','sub_total','total_traslados','total_retenciones','total','monto_pago_nc','monto_pago_cp','saldo','monto_saldo_aplicado','cantidad','valor_unitario','descuento'] as $campo)exigir_cp(array_key_exists($campo,$c)&&(float)$c[$campo]===0.0,'cabecera_cp_cero_'.$campo);
        exigir_cp((float)$p['monto']===116.0&&(float)$d['imp_pagado']===116.0,'monto_real');exigir_cp(!$pdo->inTransaction(),'commit_finalizado');
    });
    caso_cp('monto_parcial_editable_y_saldo_actualizado',function(){
        reiniciar_cp();factura_cp();servicio_cp()->crear(1,entrada_cp(['monto'=>'40.25']));
        exigir_cp((float)q_cp('SELECT imp_pagado FROM fc_docto_relacionado')->fetchColumn()===40.25,'pago_parcial_guardado');
        rechaza_cp(fn()=>servicio_cp()->crear(1,entrada_cp(['monto'=>'76.00'])),'relee_saldo_antes_crear');
        exigir_cp((int)q_cp('SELECT COUNT(*) FROM fc_complemento_pago')->fetchColumn()===1,'rechazo_sin_cp_huerfano');
    });
    caso_cp('idempotencia_misma_solicitud_y_payload_distinto',function(){
        global $llamadas_cp;reiniciar_cp();factura_cp();$datos=entrada_cp(['monto'=>'40.00']);$s=servicio_cp();$a=$s->crear(1,$datos);$b=$s->crear(1,$datos);
        exigir_cp($a['fc_complemento_pago_id']===$b['fc_complemento_pago_id']&&$b['reutilizado']===true&&$llamadas_cp===1,'repeticion_sin_duplicado');
        rechaza_cp(fn()=>$s->crear(1,array_replace($datos,['monto'=>'41.00'])),'misma_clave_payload_distinto');exigir_cp($llamadas_cp===1,'payload_distinto_no_crea');
    });
    caso_cp('fallo_callback_revierte_toda_estructura',function(){
        global $pdo;reiniciar_cp();factura_cp();$s=servicio_cp(callback:static function(array $c,array $p,array $d):int {estructura_cp($c,$p,$d);throw new RuntimeException('ERROR SINTETICO NO PUBLICAR');});
        rechaza_cp(fn()=>$s->crear(1,entrada_cp()),'callback_falla');exigir_cp(array_sum(conteos_cp())===0&&!$pdo->inTransaction(),'rollback_completo');
    });
    caso_cp('facturas_no_elegibles_no_generan_cp',function(){
        foreach([['etapa'=>'ALTA'],['etapa'=>'CANCELADO'],['status'=>'inactivo'],['cat_sat_metodo_pago_id'=>2],['folio_fiscal'=>'SIN UUID'],['total'=>'0.00']] as $c){
            reiniciar_cp();factura_cp(1,$c);rechaza_cp(fn()=>servicio_cp()->preparar(1),'factura_no_elegible_get');rechaza_cp(fn()=>servicio_cp()->crear(1,entrada_cp()),'factura_no_elegible_post');exigir_cp(array_sum(conteos_cp())===0,'no_elegible_sin_estructura');
        }
        reiniciar_cp();factura_cp();q_cp("INSERT INTO pr_etapa VALUES(1,'CANCELADO')");q_cp('INSERT INTO pr_etapa_proceso VALUES(1,1)');q_cp('INSERT INTO fc_factura_etapa(fc_factura_id,pr_etapa_proceso_id) VALUES(1,1)');
        rechaza_cp(fn()=>servicio_cp()->preparar(1),'historial_cancelado_no_ofrecido');rechaza_cp(fn()=>servicio_cp()->crear(1,entrada_cp()),'historial_cancelado_no_crea');
    });
    caso_cp('uuid_local_inconsistente_y_sello_duplicado',function(){
        foreach(['ausente','distinto','duplicado'] as $tipo){reiniciar_cp();factura_cp();
            if($tipo==='ausente')q_cp('DELETE FROM fc_cfdi_sellado');
            if($tipo==='distinto')q_cp("UPDATE fc_cfdi_sellado SET uuid='00000000-0000-4000-8000-000000000099'");
            if($tipo==='duplicado')q_cp("INSERT INTO fc_cfdi_sellado(fc_factura_id,uuid) VALUES(1,'00000000-0000-4000-8000-000000000001')");
            rechaza_cp(fn()=>servicio_cp()->crear(1,entrada_cp()),'uuid_rechazado');exigir_cp(array_sum(conteos_cp())===0,'uuid_inconsistente_no_crea');
        }
    });
    caso_cp('csd_otro_emisor_y_forma_inactiva_rechazados',function(){
        foreach([['fc_csd_id'=>'2'],['cat_sat_forma_pago_id'=>'2'],['cat_sat_forma_pago_id'=>'3'],['com_tipo_cambio_id'=>'3']] as $datos){reiniciar_cp();factura_cp();rechaza_cp(fn()=>servicio_cp()->crear(1,entrada_cp($datos)),'catalogo_invalido');exigir_cp(array_sum(conteos_cp())===0,'catalogo_invalido_sin_estructura');}
    });
    caso_cp('rotacion_csd_mismo_emisor_admitida',function(){
        reiniciar_cp();factura_cp();servicio_cp()->crear(1,entrada_cp(['fc_csd_id'=>'3']));exigir_cp((int)q_cp('SELECT fc_csd_id FROM fc_complemento_pago')->fetchColumn()===3,'csd_renovado_del_mismo_emisor');
    });
    caso_cp('transaccion_ajena_no_revierte_datos',function(){
        global $pdo;reiniciar_cp();factura_cp();$pdo->beginTransaction();
        try{q_cp("UPDATE fc_factura SET serie='CAMBIO DEL LLAMADOR' WHERE id=1");rechaza_cp(fn()=>servicio_cp()->crear(1,entrada_cp()),'transaccion_ajena');exigir_cp($pdo->inTransaction()&&q_cp('SELECT serie FROM fc_factura WHERE id=1')->fetchColumn()==='CAMBIO DEL LLAMADOR','tx_ajena_intacta');}
        finally{if($pdo->inTransaction())$pdo->rollBack();}
        exigir_cp(array_sum(conteos_cp())===0,'tx_ajena_sin_estructura');
    });
    caso_cp('autocommit_cero_no_inicia_operacion',function(){
        global $pdo;reiniciar_cp();factura_cp();$pdo->exec('SET autocommit=0');
        try{rechaza_cp(fn()=>servicio_cp()->crear(1,entrada_cp()),'autocommit0_rechazado');exigir_cp((int)q_cp('SELECT @@SESSION.autocommit')->fetchColumn()===0&&array_sum(conteos_cp())===0,'autocommit0_sin_mutacion');}
        finally{if($pdo->inTransaction())$pdo->rollBack();$pdo->exec('SET autocommit=1');}
    });
    caso_cp('saldo_ignora_cache_y_considera_notas_activas',function(){
        reiniciar_cp();factura_cp();servicio_cp()->crear(1,entrada_cp(['monto'=>'40.00']));
        q_cp("INSERT INTO fc_nota_credito VALUES(1,'activo'),(2,'inactivo')");q_cp('INSERT INTO fc_relacion_nc VALUES(1,1),(2,2)');q_cp('INSERT INTO fc_nc_rel(fc_factura_id,fc_relacion_nc_id,monto_aplicado_factura) VALUES(1,1,20.25),(1,2,999)');q_cp('UPDATE fc_factura SET saldo=999 WHERE id=1');
        $r=servicio_cp()->preparar(1);exigir_cp($r['factura']['saldo']==='55.75'&&$r['factura']['parcialidad']===2,'saldo_real_y_parcialidad');
        rechaza_cp(fn()=>servicio_cp()->crear(1,entrada_cp(['monto'=>'55.76'])),'exceso_saldo_real');
        servicio_cp()->crear(1,entrada_cp(['monto'=>'55.75']));exigir_cp((int)q_cp('SELECT COUNT(*) FROM fc_complemento_pago')->fetchColumn()===2,'saldo_exacto_admitido');
    });
    caso_cp('campos_de_autoridad_del_browser_se_ignoran',function(){
        reiniciar_cp();factura_cp();servicio_cp()->crear(1,entrada_cp(['fc_factura_id'=>999,'com_sucursal_id'=>2,'cat_sat_moneda_id'=>2,'uuid'=>'UUID FALSO','imp_saldo_ant'=>'999999','num_parcialidad'=>99]));
        $r=q_cp('SELECT cabecera,pago,docto FROM qa_callback')->fetch(PDO::FETCH_ASSOC);$c=json_decode($r['cabecera'],true);$p=json_decode($r['pago'],true);$d=json_decode($r['docto'],true);
        exigir_cp((int)$c['com_sucursal_id']===1&&(int)$p['cat_sat_moneda_id']===1&&(int)$d['fc_factura_id']===1,'ids_del_contexto_releido');
        exigir_cp(!isset($d['uuid'],$d['imp_saldo_ant'],$d['num_parcialidad']),'sin_copia_de_campos_criticos');
    });
    caso_cp('dos_conexiones_no_crean_dos_complementos_simultaneos',function(){
        global $llamadas_cp;reiniciar_cp();factura_cp();$otra=conexion_cp();
        $s=servicio_cp(callback:static function(array $c,array $p,array $d)use($otra):int{
            rechaza_cp(fn()=>servicio_cp(conexion:$otra)->crear(1,entrada_cp()),'segunda_conexion_bloqueada');return estructura_cp($c,$p,$d);
        });
        $s->crear(1,entrada_cp());exigir_cp($llamadas_cp===1&&(int)q_cp('SELECT COUNT(*) FROM fc_complemento_pago')->fetchColumn()===1,'una_estructura_concurrente');
    });
    caso_cp('revocacion_durante_preparacion_no_escribe',function(){
        global $pdo,$llamadas_cp;reiniciar_cp();factura_cp();$consultas=0;$s=new _complemento_desde_factura($pdo,7,static function()use(&$consultas):bool{return ++$consultas===1;},'estructura_cp');
        $antes=q_cp('SELECT * FROM fc_factura WHERE id=1')->fetch(PDO::FETCH_ASSOC);rechaza_cp(fn()=>$s->crear(1,entrada_cp()),'revocado_antes_primera_escritura');
        exigir_cp($consultas>=2&&$llamadas_cp===0&&array_sum(conteos_cp())===0,'sin_escrituras_tras_revocar');exigir_cp(q_cp('SELECT * FROM fc_factura WHERE id=1')->fetch(PDO::FETCH_ASSOC)===$antes,'factura_intacta');
    });
    caso_cp('callback_no_persistido_no_devuelve_exito',function(){
        reiniciar_cp();factura_cp();rechaza_cp(fn()=>servicio_cp(callback:static fn(array $c,array $p,array $d):int=>123)->crear(1,entrada_cp()),'resultado_no_persistido');exigir_cp(array_sum(conteos_cp())===0,'sin_cp_inesperado');
    });
    caso_cp('guardia_compartida_ve_pago_posterior_a_snapshot_ajeno',function(){
        reiniciar_cp();factura_cp();$lectora=conexion_cp();$lectora->beginTransaction();
        try{
            exigir_cp((int)$lectora->query('SELECT COUNT(*) FROM fc_docto_relacionado')->fetchColumn()===0,'snapshot_antes_pago');
            servicio_cp()->crear(1,entrada_cp(['monto'=>'40.00']));
            exigir_cp((int)$lectora->query('SELECT COUNT(*) FROM fc_docto_relacionado')->fetchColumn()===0,'snapshot_sigue_antiguo');
            $r=new ReflectionClass(fc_docto_relacionado::class);$m=$r->newInstanceWithoutConstructor();$r->getProperty('link')->setValue($m,$lectora);
            (new ReflectionMethod($m,'sincroniza_saldo_bloqueado'))->invoke($m,1);
            $saldo=$lectora->query('SELECT saldo,monto_pago_cp FROM fc_factura WHERE id=1')->fetch(PDO::FETCH_ASSOC);
            exigir_cp((float)$saldo['saldo']===76.0&&(float)$saldo['monto_pago_cp']===40.0,'locking_read_con_dato_actual');
            $registro=(new ReflectionMethod($m,'num_parcialidad_alta'))->invoke($m,['fc_factura_id'=>1]);
            exigir_cp($registro['num_parcialidad']===2,'parcialidad_sin_contar_snapshot_viejo');
        }finally{if($lectora->inTransaction())$lectora->rollBack();}
    });
    caso_cp('guardia_compartida_bloquea_factura_para_otra_conexion',function(){
        reiniciar_cp();factura_cp();$a=conexion_cp();$b=conexion_cp();$a->beginTransaction();$b->exec('SET SESSION innodb_lock_wait_timeout=1');
        try{
            $r=new ReflectionClass(fc_docto_relacionado::class);$m=$r->newInstanceWithoutConstructor();$r->getProperty('link')->setValue($m,$a);
            (new ReflectionMethod($m,'sincroniza_saldo_bloqueado'))->invoke($m,1);$b->beginTransaction();$timeout=false;
            try{$b->exec('UPDATE fc_factura SET saldo=0 WHERE id=1');}catch(PDOException $e){$timeout=(int)($e->errorInfo[1]??0)===1205;}
            exigir_cp($timeout,'fila_factura_excluida_por_for_update');
        }finally{if($b->inTransaction())$b->rollBack();if($a->inTransaction())$a->rollBack();}
        exigir_cp((float)q_cp('SELECT saldo FROM fc_factura WHERE id=1')->fetchColumn()===116.0,'rollback_aislado_sin_alterar_saldo');
    });
    caso_cp('alta_docto_real_revierte_error_con_tx_propia_y_savepoint',function(){
        global $pdo;
        exigir_cp(!class_exists('gamboamartin\\facturacion\\models\\fc_pago_pago',false),'contraparte_real_no_instanciada');require_once __DIR__.'/doble_pago_fallido.php';
        foreach([false,true] as $externa){
            reiniciar_cp();factura_cp();q_cp('UPDATE fc_factura SET saldo=999 WHERE id=1');\gamboamartin\errores\errores::$error=false;
            if($externa){$pdo->beginTransaction();q_cp("UPDATE fc_factura SET serie='CAMBIO_CALLER' WHERE id=1");}
            try{
                $r=new ReflectionClass(fc_docto_relacionado::class);$m=$r->newInstanceWithoutConstructor();
                $m->link=$pdo;$m->registro=['fc_factura_id'=>1,'fc_pago_pago_id'=>99,'imp_pagado'=>'10.00'];
                $m->validacion=new \gamboamartin\base_modelos\base_modelos();$m->error=new \gamboamartin\errores\errores();
                $resultado=$m->alta_bd();
                exigir_cp(is_array($resultado)&&!empty($resultado['error']),'alta_error_no_exito');
                exigir_cp($pdo->inTransaction()===$externa,'tx_externa_preservada_o_propia_cerrada');
                exigir_cp((float)q_cp('SELECT saldo FROM fc_factura WHERE id=1')->fetchColumn()===999.0,'sincronizacion_previa_revertida');
                if($externa)exigir_cp(q_cp('SELECT serie FROM fc_factura WHERE id=1')->fetchColumn()==='CAMBIO_CALLER','savepoint_preserva_cambio_previo');
                exigir_cp(array_sum(conteos_cp())===0,'ninguna_estructura_fiscal_parcial');
            }finally{if($pdo->inTransaction())$pdo->rollBack();}
        }
        \gamboamartin\errores\errores::$error=false;
    });
    $registro=json_decode(file_get_contents(__DIR__.'/registro.json'),true,flags:JSON_THROW_ON_ERROR);exigir_cp($casos===$registro['casos'],'identidades_registradas');
}catch(Throwable $e){$fallo=$e->getMessage();}
finally{if($pdo->inTransaction())$pdo->rollBack();try{foreach(array_reverse($tablas) as $t)q_cp('DROP TABLE '.$t);$limpieza=count($pdo->query('SHOW TABLES')->fetchAll())===0;}catch(Throwable){$limpieza=false;}$salida=ob_get_clean();}
echo json_encode(['estado'=>$fallo===null&&$limpieza&&$salida===''?'PASS_COMPLEMENTO_DESDE_FACTURA_BD':'FAIL_COMPLEMENTO_DESDE_FACTURA_BD',
 'casos'=>$casos,'casos_ejecutados'=>count($casos),'aserciones'=>$aserciones,'fuentes'=>$fuentes,'limpieza_confirmada'=>$limpieza,'fallo'=>$fallo,'salida_interna_presente'=>$salida!=='',
 'alcance'=>'Servicio real y catálogo con SQL sintético en MariaDB efímera sin red. Callback de estructura falso y helpers reales de bloqueo/saldos por Reflection sin constructor. No acredita cadena ORM fiscal completa, HTTP/navegador completos ni PAC real. La consulta ACL real se acredita con sesión sintética y cuatro permisos en tablas mínimas.'],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
exit($fallo===null&&$limpieza&&$salida===''?0:1);
