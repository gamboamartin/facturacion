<?php
declare(strict_types=1);

use gamboamartin\facturacion\models\{_timbrado_lote, _timbrado_pendientes, _timbrado_guardia, bloqueo_uso_csd};
use gamboamartin\errores\errores;

require_once '/qa/bootstrap.php';
$root='/var/www/html/facturacion';
require_once $root.'/tools/migrations/_esquema_timbrado_masivo.php';
if ((new config\database())->db_name!=='qa_csd_integral') { throw new RuntimeException('Base efímera requerida'); }
function conexion(): PDO {
    return new PDO('mysql:unix_socket=/run/mysqld/mysqld.sock;dbname=qa_csd_integral;charset=utf8mb4','qa_csd_integral','QA-EFIMERA-SIN-VALIDEZ',
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false]);
}
$link=conexion();
$link->exec("SET sql_mode='STRICT_ALL_TABLES'");
$casos=[];$aserciones=0;$llamadas=0;$permitido=true;$limpieza=false;$fallo=null;
function exigir(bool $valor,string $nombre):void {global $aserciones;$aserciones++;if(!$valor)throw new RuntimeException($nombre);}
function rechaza(callable $accion,string $nombre):void {
    try {$accion();}catch(InvalidArgumentException|RuntimeException $e){exigir(true,$nombre);return;}
    exigir(false,$nombre);
}
function caso(string $nombre,callable $prueba):void {global $casos;errores::$error=false;$prueba();$casos[]=$nombre;}
function sql(string $sql,array $params=[]):PDOStatement {global $link;$q=$link->prepare($sql);$q->execute($params);return $q;}
function factura(int $id,array $cambios=[]):void {
    $fila=array_replace(['id'=>$id,'folio'=>sprintf('A-%06d',$id),'serie'=>'A','total'=>'116.00',
        'fecha'=>'2026-11-23 12:00:00','status'=>'activo','etapa'=>'ALTA','es_plantilla'=>'inactivo',
        'folio_fiscal'=>'SIN UUID','com_sucursal_id'=>1,'fc_csd_id'=>1,'cat_sat_moneda_id'=>1],$cambios);
    sql('INSERT INTO fc_factura ('.implode(',',array_keys($fila)).') VALUES ('.implode(',',array_fill(0,count($fila),'?')).')',array_values($fila));
}
function reiniciar():void {
    global $llamadas,$permitido;$llamadas=0;$permitido=true;
    foreach(['fc_timbrado_lote_item','fc_timbrado_lote','fc_timbrado_intento','fc_cfdi_sellado','fc_factura_etapa','fc_factura'] as $tabla)sql('DELETE FROM '.$tabla);
}
function emitir(int $id):array {
    global $llamadas;$llamadas++;
    $uuid=sprintf('00000000-0000-4000-8000-%012d',$id);
    sql('INSERT INTO fc_cfdi_sellado (fc_factura_id,uuid) VALUES (?,?)',[$id,$uuid]);
    sql('UPDATE fc_factura SET folio_fiscal=? WHERE id=?',[$uuid,$id]);
    return ['estado'=>'timbrada','uuid'=>$uuid,'mensaje'=>'Timbrado simulado sin validez'];
}
function servicio(int $usuario=7,?callable $callback=null,?PDO $pdo=null):_timbrado_lote {
    global $link,$permitido;
    return new _timbrado_lote($pdo??$link,$usuario,static function()use(&$permitido){return $permitido;},$callback??'emitir');
}
function crear(array $ids,array $filtros=[],?string $clave=null):array {return servicio()->crear($clave??bin2hex(random_bytes(16)),'ids',$ids,$filtros);}
function intento(int $id,string $estado):void {
    sql('INSERT INTO fc_timbrado_intento (factura_id,intento,estado) VALUES (?,?,?)',[$id,str_repeat('a',32),$estado]);
}
function abandonado(string $token,int $id):void {sql("UPDATE fc_timbrado_lote_item SET estado='procesando' WHERE lote_id=? AND factura_id=?",[$token,$id]);}
function respuesta_xml(int $id):stdClass {
    $uuid=sprintf('00000000-0000-4000-8000-%012d',$id);
    return (object)['xml_sellado'=>'<?xml version="1.0"?><cfdi:Comprobante xmlns:cfdi="http://www.sat.gob.mx/cfd/4"><cfdi:Complemento><tfd:TimbreFiscalDigital xmlns:tfd="http://www.sat.gob.mx/TimbreFiscalDigital" UUID="'.$uuid.'"/></cfdi:Complemento><cfdi:Addenda>SINTETICO-PRIVADO-SIN-VALIDEZ</cfdi:Addenda></cfdi:Comprobante>'];
}
function estado_item(array $lote,int $id):string {
    foreach($lote['resultados'] as $item)if($item['factura_id']===$id)return $item['estado'];
    throw new RuntimeException('Item faltante');
}
$fuentes=[];
foreach([_timbrado_lote::class,_timbrado_pendientes::class,_timbrado_guardia::class,bloqueo_uso_csd::class,EsquemaTimbradoMasivo::class] as $clase){$archivo=(new ReflectionClass($clase))->getFileName();$fuentes[$clase]=['archivo'=>$archivo,'sha256'=>hash_file('sha256',$archivo)];}
$tablas=['fc_timbrado_lote_item','fc_timbrado_lote','fc_timbrado_intento','fc_cfdi_sellado','fc_factura_etapa','fc_factura','pr_etapa_proceso','pr_etapa','com_sucursal','com_cliente','fc_csd','org_sucursal','org_empresa','cat_sat_moneda'];
ob_start();
try {
    $ddl=[
        'org_empresa'=>'id BIGINT PRIMARY KEY,razon_social VARCHAR(255),status VARCHAR(24)',
        'org_sucursal'=>'id BIGINT PRIMARY KEY,org_empresa_id BIGINT',
        'fc_csd'=>'id BIGINT PRIMARY KEY,org_sucursal_id BIGINT',
        'com_cliente'=>'id BIGINT PRIMARY KEY,razon_social VARCHAR(255),rfc VARCHAR(40)',
        'com_sucursal'=>'id BIGINT PRIMARY KEY,com_cliente_id BIGINT',
        'cat_sat_moneda'=>'id BIGINT PRIMARY KEY,codigo VARCHAR(10)',
        'fc_factura'=>'id BIGINT PRIMARY KEY,folio VARCHAR(255),serie VARCHAR(100),total DECIMAL(18,2),fecha VARCHAR(40),status VARCHAR(24),etapa VARCHAR(40),es_plantilla VARCHAR(24),folio_fiscal VARCHAR(64) NOT NULL DEFAULT \'SIN UUID\',com_sucursal_id BIGINT,fc_csd_id BIGINT,cat_sat_moneda_id BIGINT',
        'fc_cfdi_sellado'=>'id BIGINT AUTO_INCREMENT PRIMARY KEY,fc_factura_id BIGINT,uuid VARCHAR(64)',
        'pr_etapa'=>'id BIGINT PRIMARY KEY,descripcion VARCHAR(40)',
        'pr_etapa_proceso'=>'id BIGINT PRIMARY KEY,pr_etapa_id BIGINT',
        'fc_factura_etapa'=>'id BIGINT AUTO_INCREMENT PRIMARY KEY,fc_factura_id BIGINT,pr_etapa_proceso_id BIGINT',
    ];
    foreach($ddl as $tabla=>$campos)sql('CREATE TABLE '.$tabla.' ('.$campos.') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
    $link->exec(file_get_contents($root.'/tools/migrations/sql/fc_timbrado_masivo.sql'));
    sql("INSERT INTO org_empresa VALUES (1,'EMISOR UNO','activo'),(2,'EMISOR DOS','activo')");
    sql('INSERT INTO org_sucursal VALUES (1,1),(2,2)');sql('INSERT INTO fc_csd VALUES (1,1),(2,2)');
    sql("INSERT INTO com_cliente VALUES (1,'CLIENTE UNO','AAA010101AAA'),(2,'CLIENTE %_! LITERAL','BBB010101BBB')");
    sql('INSERT INTO com_sucursal VALUES (1,1),(2,2)');sql("INSERT INTO cat_sat_moneda VALUES (1,'MXN')");
    sql("INSERT INTO pr_etapa VALUES (1,'TIMBRADO'),(2,' cancelado '),(3,'ALTA')");sql('INSERT INTO pr_etapa_proceso VALUES (1,1),(2,2),(3,3)');
    caso('ddl_operativo_innodb_restricciones',function(){
        global $link;EsquemaTimbradoMasivo::validar($link);exigir(true,'esquema_real_valido');
        foreach(['fc_timbrado_lote','fc_timbrado_lote_item','fc_timbrado_intento'] as $tabla)exigir(sql('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?',[$tabla])->fetchColumn()==='InnoDB','engine');
    });
    caso('esquema_alterado_rechazado_y_restaurado',function(){
        global $link;
        $variantes=[
            ['ALTER TABLE fc_timbrado_lote DROP INDEX lote_solicitud','ALTER TABLE fc_timbrado_lote ADD UNIQUE KEY lote_solicitud (usuario_id,clave_solicitud)'],
            ["ALTER TABLE fc_timbrado_lote_item MODIFY estado VARCHAR(25) NOT NULL DEFAULT 'pendiente'","ALTER TABLE fc_timbrado_lote_item MODIFY estado VARCHAR(24) NOT NULL DEFAULT 'pendiente'"],
            ['ALTER TABLE fc_timbrado_intento ENGINE=MyISAM','ALTER TABLE fc_timbrado_intento ENGINE=InnoDB'],
            ['ALTER TABLE fc_timbrado_lote_item DROP FOREIGN KEY timbrado_item_lote','ALTER TABLE fc_timbrado_lote_item ADD CONSTRAINT timbrado_item_lote FOREIGN KEY (lote_id) REFERENCES fc_timbrado_lote(id) ON DELETE CASCADE'],
        ];
        foreach($variantes as [$romper,$restaurar]){
            sql($romper);
            try{rechaza(fn()=>EsquemaTimbradoMasivo::validar($link),'esquema_incorrecto_rechazado');}
            finally{sql($restaurar);}
            EsquemaTimbradoMasivo::validar($link);exigir(true,'esquema_restaurado_valido');
        }
    });
    caso('filtros_fecha_folio_rfc_cliente_emisor_importe',function(){
        reiniciar();factura(1);factura(2,['total'=>'500.00','fecha'=>'2026-11-24','fc_csd_id'=>2,'com_sucursal_id'=>2]);
        $r=servicio()->listar(['fecha_inicio'=>'2026-11-23','fecha_fin'=>'2026-11-23','folio'=>'000001','rfc'=>'AAA','cliente'=>'UNO','emisor_id'=>'1','total_min'=>'116','total_max'=>'116','serie'=>'A']);
        exigir($r['total']===1&&$r['filas'][0]['id']===1,'filtros_combinados');
        exigir(servicio()->listar(['fecha_inicio'=>'2026-11-24','emisor_id'=>'2','total_min'=>'499'])['filas'][0]['id']===2,'filtros_alternos');
    });
    caso('filtros_like_literales_y_sql_preparado',function(){
        reiniciar();factura(1,['folio'=>"X%_!'-1",'com_sucursal_id'=>2]);factura(2);
        exigir(servicio()->listar(['folio'=>"%_!'"])['total']===1,'metacaracteres_folio');
        exigir(servicio()->listar(['cliente'=>'%_!'])['total']===1,'metacaracteres_cliente');
        exigir(servicio()->listar(['folio'=>"' OR 1=1 --"])['total']===0,'sql_no_inyectado');
        $nombre=str_repeat('á',255);sql('UPDATE com_cliente SET razon_social=? WHERE id=2',[$nombre]);
        try{exigir(servicio()->listar(['cliente'=>$nombre])['total']===1,'cliente_255_caracteres_utf8');}
        finally{sql('UPDATE com_cliente SET razon_social=? WHERE id=2',['CLIENTE %_! LITERAL']);}
    });
    caso('filtros_invalidos_rechazados',function(){
        foreach([['desconocido'=>'x'],['fecha_inicio'=>'2026-02-30'],['fecha_inicio'=>'2026-12-01','fecha_fin'=>'2026-01-01'],['total_min'=>'2','total_max'=>'1'],['total_min'=>'-1'],['emisor_id'=>'0'],['folio'=>[]],['cliente'=>str_repeat('á',256)],['folio'=>"A\0B"]] as $f)rechaza(fn()=>servicio()->listar($f),'filtro_invalido');
        exigir(sql('SELECT COUNT(*) FROM fc_timbrado_lote')->fetchColumn()==0,'sin_creaciones');
    });
    caso('excluir_timbradas_canceladas_historicas_plantillas',function(){
        reiniciar();factura(1);factura(2,['etapa'=>'TIMBRADO']);factura(3,['etapa'=>' cancelado ']);factura(4,['es_plantilla'=>'activo']);factura(5,['status'=>'inactivo']);factura(6,['folio_fiscal'=>'UUID']);factura(7);factura(8);factura(9);factura(10);factura(11,['folio_fiscal'=>'SIN UUID']);factura(18,['folio_fiscal'=>'SIN UUID']);
        foreach(['enviado','aceptado','completo','revision_requerida','preparando','rechazado'] as $n=>$fase){factura(12+$n);intento(12+$n,$fase);}
        sql("INSERT INTO fc_cfdi_sellado(fc_factura_id,uuid) VALUES (7,'SELLO'),(18,'00000000-0000-4000-8000-000000000018')");sql('INSERT INTO fc_factura_etapa(fc_factura_id,pr_etapa_proceso_id) VALUES (8,1),(9,2),(10,3)');
        exigir(array_column(servicio()->listar([])['filas'],'id')===[1,10,11,16,17],'elegibilidad_historica');
    });
    caso('paginacion_conserva_filtros',function(){
        reiniciar();for($i=1;$i<=30;$i++)factura($i);$r=servicio()->listar([],2);
        exigir($r['total']===30&&$r['pagina']===2&&count($r['filas'])===5,'segunda_pagina');
        exigir(array_column($r['filas'],'id')===[26,27,28,29,30],'orden_estable');
    });
    caso('seleccion_congelada_sin_nuevas_facturas',function(){
        reiniciar();factura(1);$l=servicio()->crear(str_repeat('1',32),'todas',servicio()->listar([])['seleccion_ids'],[]);factura(2);
        exigir(array_column(servicio()->estado($l['lote_id'])['resultados'],'factura_id')===[1],'ids_congelados');
    });
    caso('todas_snapshot_rechaza_altas_y_sustituciones',function(){
        global $llamadas;
        foreach(['nuevo_id','mismo_total_otros_ids'] as $variante){
            reiniciar();factura(1);factura(2);$vista=servicio()->listar([]);
            exigir($vista['seleccion_ids']===[1,2],'snapshot_revisado');
            if($variante==='mismo_total_otros_ids')sql('DELETE FROM fc_factura WHERE id=2');
            factura(3);
            rechaza(fn()=>servicio()->crear(bin2hex(random_bytes(16)),'todas',$vista['seleccion_ids'],[]),'snapshot_cambiado_rechazado');
            exigir((int)sql('SELECT COUNT(*) FROM fc_timbrado_lote')->fetchColumn()===0&&$llamadas===0,'snapshot_no_crea_ni_emite');
        }
    });
    caso('todas_limite_cien',function(){
        reiniciar();for($i=1;$i<=100;$i++)factura($i);$l=servicio()->crear(str_repeat('2',32),'todas',servicio()->listar([])['seleccion_ids'],[]);
        exigir($l['resumen']['total']===100,'cien_completas');
    });
    caso('todas_ciento_uno_rechaza_sin_truncar',function(){
        reiniciar();for($i=1;$i<=101;$i++)factura($i);rechaza(fn()=>servicio()->crear(str_repeat('3',32),'todas',servicio()->listar([])['seleccion_ids'],[]),'limite101');
        exigir((int)sql('SELECT COUNT(*) FROM fc_timbrado_lote')->fetchColumn()===0,'sin_lote_parcial');
    });
    caso('crear_idempotente_misma_clave',function(){
        reiniciar();factura(1);$a=crear([1],[],str_repeat('4',32));$b=crear([1],[],str_repeat('4',32));
        exigir($a['lote_id']===$b['lote_id']&&(int)sql('SELECT COUNT(*) FROM fc_timbrado_lote_item')->fetchColumn()===1,'misma_solicitud');
    });
    caso('crear_clave_reusada_seleccion_distinta_rechazada',function(){
        reiniciar();factura(1);factura(2);crear([1],[],str_repeat('5',32));rechaza(fn()=>crear([2],[],str_repeat('5',32)),'clave_distinta');
    });
    caso('ownership_estado_y_procesar',function(){
        reiniciar();factura(1);$l=crear([1]);rechaza(fn()=>servicio(8)->estado($l['lote_id']),'owner_estado');rechaza(fn()=>servicio(8)->procesar($l['lote_id'],1),'owner_proceso');global $llamadas;exigir($llamadas===0,'owner_sin_pac');
    });
    caso('acl_revocado_en_todas_operaciones',function(){
        global $permitido,$llamadas;reiniciar();factura(1);$l=crear([1]);$permitido=false;
        rechaza(fn()=>servicio()->listar([]),'acl_listar');rechaza(fn()=>crear([1]),'acl_crear');rechaza(fn()=>servicio()->estado($l['lote_id']),'acl_estado');rechaza(fn()=>servicio()->procesar($l['lote_id'],1),'acl_procesar');exigir($llamadas===0,'acl_sin_emision');$permitido=true;
    });
    caso('procesar_repetido_no_emite_dos_veces',function(){
        global $llamadas;reiniciar();factura(1);$l=crear([1]);$s=servicio();$s->procesar($l['lote_id'],1);$r=$s->procesar($l['lote_id'],1);
        exigir($llamadas===1&&estado_item($r,1)==='timbrada'&&$r['estado']==='terminado','exactamente_una_emision');
    });
    caso('bloqueo_get_lock_dos_conexiones',function(){
        global $llamadas;reiniciar();factura(1);$l=crear([1]);$otro=conexion();$nombre='fc_lote_'.substr(hash('sha256','qa_csd_integral:'.$l['lote_id']),0,54);
        $q=$otro->prepare('SELECT GET_LOCK(?,0)');$q->execute([$nombre]);exigir((int)$q->fetchColumn()===1,'lock_otra_conexion');
        try{
            $r=servicio()->procesar($l['lote_id'],1);exigir(estado_item($r,1)==='pendiente'&&$llamadas===0,'no_procesa_con_lock');
            abandonado($l['lote_id'],1);$r=servicio()->estado($l['lote_id']);
            exigir(estado_item($r,1)==='procesando','no_recupera_mientras_lock_activo');
            sql("UPDATE fc_timbrado_lote_item SET estado='pendiente' WHERE lote_id=?",[$l['lote_id']]);
        }
        finally{$q=$otro->prepare('SELECT RELEASE_LOCK(?)');$q->execute([$nombre]);}
        exigir(estado_item(servicio()->procesar($l['lote_id'],1),1)==='timbrada'&&$llamadas===1,'procesa_despues_liberar');
    });
    foreach(['preparando'=>'rechazada','enviado'=>'revision_requerida','aceptado'=>'revision_requerida'] as $fase=>$esperado){
        caso('interrumpido_'.$fase,function()use($fase,$esperado){
            global $llamadas;reiniciar();factura(1);$l=crear([1]);intento(1,$fase);abandonado($l['lote_id'],1);$r=servicio()->estado($l['lote_id']);
            exigir(estado_item($r,1)===$esperado,'clasificacion_interrumpido');servicio()->procesar($l['lote_id'],1);exigir($llamadas===0,'interrumpido_sin_reenvio');
            if($fase!=='preparando'){
                reiniciar();factura(1);$l=crear([1]);intento(1,$fase);$r=servicio()->procesar($l['lote_id'],1);
                exigir(estado_item($r,1)==='revision_requerida'&&$llamadas===0,'intento_externo_tras_congelar_no_omitido');
            }
        });
    }
    caso('interrumpido_sin_intento_y_con_sello',function(){
        reiniciar();factura(1);$l=crear([1]);abandonado($l['lote_id'],1);exigir(estado_item(servicio()->estado($l['lote_id']),1)==='rechazada','no_intento');
        reiniciar();factura(1);$l=crear([1]);emitir(1);abandonado($l['lote_id'],1);exigir(estado_item(servicio()->estado($l['lote_id']),1)==='timbrada','sello_persistido');
    });
    caso('resultado_mixto_continua_y_reanuda',function(){
        global $llamadas;reiniciar();for($i=1;$i<=3;$i++)factura($i);$l=crear([1,2,3]);
        $s=servicio(callback:static fn(int $id)=>$id===2?['estado'=>'rechazada','mensaje'=>'Prevalidación ficticia']:emitir($id));
        $s->procesar($l['lote_id'],1);$s->procesar($l['lote_id'],2);sql("UPDATE fc_factura SET etapa='CANCELADO' WHERE id=3");$r=$s->procesar($l['lote_id'],3);
        exigir($r['resumen']['timbradas']===1&&$r['resumen']['rechazadas']===1&&$r['resumen']['omitidas']===1&&$llamadas===1,'resultados_individuales');
        exigir(servicio()->estado($l['lote_id'])===$r,'recarga_estado_durable');
    });
    caso('factura_fuera_lote_rechazada',function(){
        global $llamadas;reiniciar();factura(1);factura(2);$l=crear([1]);rechaza(fn()=>servicio()->procesar($l['lote_id'],2),'fuera_lote');exigir($llamadas===0,'fuera_lote_no_pac');
    });
    caso('cambio_emisor_no_envia',function(){
        global $llamadas;reiniciar();factura(1);$l=crear([1]);sql('UPDATE fc_factura SET fc_csd_id=2 WHERE id=1');$r=servicio()->procesar($l['lote_id'],1);exigir(estado_item($r,1)==='omitida'&&$llamadas===0,'emisor_cambiado');
    });
    caso('cambio_filtro_no_envia',function(){
        global $llamadas;reiniciar();factura(1);$l=crear([1],['total_max'=>'120']);sql('UPDATE fc_factura SET total=200 WHERE id=1');$r=servicio()->procesar($l['lote_id'],1);exigir(estado_item($r,1)==='omitida'&&$llamadas===0,'filtro_cambiado');
    });
    caso('callback_falla_despues_envio_no_reintenta',function(){
        global $llamadas;reiniciar();factura(1);$l=crear([1]);$s=servicio(callback:static function(int $id){global $llamadas;$llamadas++;intento($id,'enviado');throw new RuntimeException('ERROR FICTICIO SECRETO');});
        $r=$s->procesar($l['lote_id'],1);$s->procesar($l['lote_id'],1);exigir(estado_item($r,1)==='revision_requerida'&&$llamadas===1,'fallo_postenvio');exigir(!str_contains(json_encode($r),'SECRETO'),'sin_fuga_error');
    });
    caso('callback_falla_con_sello_conserva_timbrada',function(){
        global $llamadas;reiniciar();factura(1);$l=crear([1]);$s=servicio(callback:static function(int $id){emitir($id);throw new RuntimeException('falló operación posterior ficticia');});$r=$s->procesar($l['lote_id'],1);$s->procesar($l['lote_id'],1);exigir(estado_item($r,1)==='timbrada'&&$llamadas===1,'aceptada_sin_reenvio');
    });
    caso('transaccion_del_llamador_no_se_revierte',function(){
        global $link,$llamadas;reiniciar();factura(1);$l=crear([1]);$link->beginTransaction();
        try{
            sql("UPDATE fc_factura SET serie='CAMBIO_CALLER' WHERE id=1");
            rechaza(fn()=>servicio()->procesar($l['lote_id'],1),'procesar_rechaza_tx_ajena');
            exigir($link->inTransaction(),'tx_caller_permanece_abierta');
            exigir(sql('SELECT serie FROM fc_factura WHERE id=1')->fetchColumn()==='CAMBIO_CALLER','trabajo_caller_intacto');
            rechaza(fn()=>servicio()->estado($l['lote_id']),'estado_rechaza_tx_ajena');
            rechaza(fn()=>crear([1]),'crear_rechaza_tx_ajena');
            exigir($link->inTransaction()&&$llamadas===0,'sin_rollback_ni_pac');
        }finally{if($link->inTransaction())$link->rollBack();}
        exigir(estado_item(servicio()->estado($l['lote_id']),1)==='pendiente','item_sin_mutar');
    });
    caso('autocommit_cero_rechaza_sin_efectos',function(){
        global $link,$llamadas;reiniciar();factura(1);$l=crear([1]);$link->exec('SET autocommit=0');
        try{
            rechaza(fn()=>servicio()->procesar($l['lote_id'],1),'procesar_rechaza_autocommit0');
            rechaza(fn()=>servicio()->estado($l['lote_id']),'estado_rechaza_autocommit0');
            rechaza(fn()=>crear([1]),'crear_rechaza_autocommit0');
            exigir((int)sql('SELECT @@SESSION.autocommit')->fetchColumn()===0&&$llamadas===0,'autocommit_caller_intacto');
        }finally{if($link->inTransaction())$link->rollBack();$link->exec('SET autocommit=1');}
        exigir(estado_item(servicio()->estado($l['lote_id']),1)==='pendiente','autocommit0_sin_mutacion');
    });
    caso('recuperacion_identidad_invalida_requiere_revision',function(){
        foreach(['malformado','cabecera_vacia','uuid_distinto','sellos_duplicados','cabecera_sola'] as $tipo){
            reiniciar();factura(1);$l=crear([1]);$uuid='00000000-0000-4000-8000-000000000001';
            if($tipo!=='cabecera_sola')sql('INSERT INTO fc_cfdi_sellado(fc_factura_id,uuid) VALUES(?,?)',[1,$tipo==='malformado'?'NO-UUID':$uuid]);
            if($tipo==='sellos_duplicados')sql('INSERT INTO fc_cfdi_sellado(fc_factura_id,uuid) VALUES(?,?)',[1,$uuid]);
            if($tipo!=='cabecera_vacia')sql('UPDATE fc_factura SET folio_fiscal=? WHERE id=1',[$tipo==='uuid_distinto'?'00000000-0000-4000-8000-000000000002':$uuid]);
            abandonado($l['lote_id'],1);$r=servicio()->estado($l['lote_id']);
            exigir(estado_item($r,1)==='revision_requerida','identidad_no_debe_asumirse_timbrada_'.$tipo);
        }
    });
    caso('guardia_real_excluye_doble_envio_y_csd_ocupado',function(){
        global $link,$llamadas;reiniciar();factura(1);factura(2);$guardia=new _timbrado_guardia($link);$otro=new _timbrado_guardia(conexion());
        $resultado=$guardia->ejecutar(1,function()use($guardia,$otro){
            rechaza(fn()=>$otro->ejecutar(1,fn()=>emitir(1)),'misma_factura_bloqueada');
            rechaza(fn()=>$otro->ejecutar(2,fn()=>emitir(2)),'mismo_csd_bloqueado');
            $guardia->antes_pac(1);$guardia->respuesta_pac(1,respuesta_xml(1),false);return emitir(1);
        });
        exigir($resultado['estado']==='timbrada'&&$llamadas===1,'una_emision_dos_conexiones');
        $repetida=(new _timbrado_guardia(conexion()))->ejecutar(1,fn()=>emitir(1));
        exigir($repetida['ya_timbrada']===true&&$llamadas===1,'repeticion_real_sin_envio');
    });
    caso('guardia_marcador_sin_uuid_no_es_evidencia_fiscal',function(){
        global $link,$llamadas;
        foreach(['SIN UUID',' sin uuid '] as $marcador){
            reiniciar();factura(1,['folio_fiscal'=>$marcador]);$g=new _timbrado_guardia($link);
            $g->ejecutar(1,function()use($g){$g->antes_pac(1);$g->respuesta_pac(1,respuesta_xml(1),false);return emitir(1);});
            exigir($llamadas===1&&$g->estado(1)['estado']==='completo','marcador_sin_sello_permite_emision');
            exigir(sql('SELECT folio_fiscal FROM fc_factura WHERE id=1')->fetchColumn()==='00000000-0000-4000-8000-000000000001','cabecera_uuid_coherente');
        }
        reiniciar();factura(1);sql('INSERT INTO fc_cfdi_sellado(fc_factura_id,uuid) VALUES(1,?)',['00000000-0000-4000-8000-000000000001']);
        $g=new _timbrado_guardia($link);$r=$g->ejecutar(1,fn()=>emitir(1));
        exigir($llamadas===0&&$r['ya_timbrada']===true&&$r['requiere_revision']===true,'marcador_con_sello_no_reenvia');
        exigir(sql('SELECT folio_fiscal FROM fc_factura WHERE id=1')->fetchColumn()==='SIN UUID','marcador_con_sello_sin_normalizacion_dml');
    });
    caso('guardia_respuesta_aceptada_persistida_antes_legacy',function(){
        global $link;reiniciar();factura(1);$g=new _timbrado_guardia($link);$xml=respuesta_xml(1)->xml_sellado;
        $g->ejecutar(1,function()use($g,$xml){
            $g->antes_pac(1);$g->respuesta_pac(1,(object)['xml_sellado'=>$xml],false);
            $q=conexion()->query('SELECT estado,xml_respuesta FROM fc_timbrado_intento WHERE factura_id=1')->fetch(PDO::FETCH_ASSOC);
            exigir($q['estado']==='aceptado'&&$q['xml_respuesta']===$xml,'respuesta_durable_otra_conexion');
            exigir((int)sql('SELECT COUNT(*) FROM fc_cfdi_sellado')->fetchColumn()===0,'previo_guardado_legacy');
            exigir(!array_key_exists('xml_respuesta',$g->estado(1))&&!str_contains(json_encode($g->estado(1)),'SINTETICO-PRIVADO'),'consulta_publica_sin_xml');
            return emitir(1);
        });
        exigir($g->estado(1)['estado']==='completo','guardia_completa');
    });
    caso('guardia_timeout_real_bloquea_reenvio',function(){
        global $link,$llamadas;reiniciar();factura(1);$g=new _timbrado_guardia($link);
        rechaza(fn()=>$g->ejecutar(1,function()use($g){global $llamadas;$g->antes_pac(1);$llamadas++;throw new RuntimeException('TIMEOUT PRIVADO');}),'timeout_real');
        exigir($g->estado(1)['estado']==='revision_requerida','timeout_revision');
        rechaza(fn()=>(new _timbrado_guardia(conexion()))->ejecutar(1,fn()=>emitir(1)),'timeout_no_reenvia');
        exigir($llamadas===1&&!str_contains(json_encode($g->estado(1)),'PRIVADO'),'timeout_una_vez_sin_fuga');
    });
    caso('guardia_rechazo_40130_permite_nuevo_intento_explicito',function(){
        global $link,$llamadas;reiniciar();factura(1);$g=new _timbrado_guardia($link);
        $g->ejecutar(1,function()use($g){global $llamadas;$g->antes_pac(1);$llamadas++;$error=['error'=>1,'data'=>[0=>'Error',6=>'CFDI40130']];errores::$error=true;$g->respuesta_pac(1,$error,true);return $error;});
        exigir($g->estado(1)['estado']==='rechazado'&&$llamadas===1,'rechazo_explicito');errores::$error=false;
        $g->ejecutar(1,function()use($g){$g->antes_pac(1);$g->respuesta_pac(1,respuesta_xml(1),false);return emitir(1);});
        exigir($g->estado(1)['estado']==='completo'&&$llamadas===2,'nuevo_intento_autorizado');
    });
    caso('guardia_codigo_desconocido_requiere_revision',function(){
        global $link,$llamadas;reiniciar();factura(1);$g=new _timbrado_guardia($link);
        rechaza(fn()=>$g->ejecutar(1,function()use($g){global $llamadas;$g->antes_pac(1);$llamadas++;$g->respuesta_pac(1,['error'=>1,'data'=>[0=>'Error',6=>'CFDI99999']],true);return [];}),'codigo_desconocido');
        exigir($g->estado(1)['estado']==='revision_requerida','codigo_desconocido_revision');
        rechaza(fn()=>(new _timbrado_guardia(conexion()))->ejecutar(1,fn()=>emitir(1)),'codigo_desconocido_sin_reenvio');
        exigir($llamadas===1,'codigo_desconocido_una_vez');
    });
    $registro=json_decode(file_get_contents(__DIR__.'/registro.json'),true,flags:JSON_THROW_ON_ERROR);
    exigir($casos===$registro['casos'],'identidades_registradas');
} catch(Throwable $error){$fallo=$error->getMessage();}
finally{
    if($link->inTransaction())$link->rollBack();
    try{foreach($tablas as $tabla)sql('DROP TABLE IF EXISTS '.$tabla);$limpieza=count($link->query('SHOW TABLES')->fetchAll())===0;}catch(Throwable){$limpieza=false;}
    $interno=ob_get_clean();
}
echo json_encode(['estado'=>$fallo===null&&$limpieza&&$interno===''?'PASS_TIMBRADO_MASIVO_BD':'FAIL_TIMBRADO_MASIVO_BD',
    'casos'=>$casos,'casos_ejecutados'=>count($casos),'aserciones'=>$aserciones,'fuentes'=>$fuentes,'limpieza_confirmada'=>$limpieza,
    'fallo'=>$fallo,'salida_interna_presente'=>$interno!=='','alcance'=>'MariaDB efímera sin red. Servicio real de lote/filtros con callback fiscal falso; no acredita HTTP, ACL SQL del controlador, ORM legacy completo ni PAC real.'],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
exit($fallo===null&&$limpieza&&$interno===''?0:1);
