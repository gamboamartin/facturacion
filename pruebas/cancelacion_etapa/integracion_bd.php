<?php
declare(strict_types=1);
require __DIR__.'/fixture.php';
use gamboamartin\errores\errores;
$p=conexion_qa();
if (($argv[1]??'')==='--worker') {
    [$d,$e,$c]=modelos_qa($p);
    $barrera=getenv('QA_CANCELACION_BARRIER');
    touch($barrera.'/ready-'.getmypid());
    $limite=microtime(true)+5;
    while(count(glob($barrera.'/ready-*'))<2) {
        if(microtime(true)>$limite)throw new RuntimeException('Barrera concurrente incompleta.');
        usleep(10000);
    }
    $r=$d->cancela_bd(2,$c,$e,100);
    echo json_encode(['error'=>errores::$error,'resultado'=>$r],JSON_THROW_ON_ERROR); exit;
}
$tablas=[]; $casos=[]; $limpio=false;
$log=tempnam(sys_get_temp_dir(),'qa_cancelacion_pac_'); chmod($log,0600);
$barrera=tempnam(sys_get_temp_dir(),'qa_cancelacion_barrier_'); unlink($barrera); mkdir($barrera,0700);
putenv('QA_CANCELACION_PAC_LOG='.$log); putenv('QA_CANCELACION_BARRIER='.$barrera);
$exigir=static function(bool $ok,string $nombre) use (&$casos): void {
    if (!$ok) throw new RuntimeException('Fallo: '.$nombre); $casos[]=$nombre;
};
$alta=static function($e,int $id,int $etapa=9,array $otros=[]): mixed {
    errores::$error=false;
    $key=str_replace('_etapa','',$e->tabla).'_id';
    $e->registro=[$key=>$id,'pr_etapa_proceso_id'=>$etapa,'fecha'=>'2026-11-26']+$otros;
    return $e->alta_bd();
};
$contar=static fn(string $tabla): int=>$tabla==='qa_pac_llamada'
    ? count(file($log,FILE_IGNORE_NEW_LINES)) : (int)$p->query('SELECT COUNT(*) FROM `'.$tabla.'`')->fetchColumn();
try {
    if ($p->query('SHOW TABLES')->fetchAll()) throw new RuntimeException('La base QA debe estar vacía y ser exclusiva.');
    foreach (['pr_etapa'=>'id BIGINT PRIMARY KEY,descripcion VARCHAR(100) NOT NULL',
        'pr_etapa_proceso'=>'id BIGINT PRIMARY KEY,pr_etapa_id BIGINT NOT NULL,FOREIGN KEY(pr_etapa_id) REFERENCES pr_etapa(id)'] as $t=>$ddl) {
        $p->exec('CREATE TABLE `'.$t.'` ('.$ddl.') ENGINE=InnoDB'); $tablas[]=$t;
    }
    $p->exec("INSERT INTO pr_etapa VALUES (7,'CANCELADO'),(8,'ALTA'),(9,'TIMBRADO')");
    $p->exec('INSERT INTO pr_etapa_proceso VALUES(9,7),(10,8),(11,9)');
    foreach (['fc_factura','fc_nota_credito','fc_complemento_pago'] as $t) {
        $p->exec('CREATE TABLE `'.$t.'`(id BIGINT PRIMARY KEY,folio VARCHAR(255),etapa VARCHAR(100) NOT NULL) ENGINE=InnoDB');$tablas[]=$t;
        $p->exec('CREATE TABLE `'.$t.'_etapa`(id BIGINT AUTO_INCREMENT PRIMARY KEY,`'.$t.'_id` BIGINT NOT NULL,
            pr_etapa_proceso_id BIGINT NOT NULL,fecha DATE NOT NULL,descripcion VARCHAR(200),codigo VARCHAR(200) UNIQUE,
            codigo_bis VARCHAR(255) UNIQUE,descripcion_select VARCHAR(255),FOREIGN KEY(`'.$t.'_id`) REFERENCES `'.$t.'`(id),
            FOREIGN KEY(pr_etapa_proceso_id) REFERENCES pr_etapa_proceso(id)) ENGINE=InnoDB');$tablas[]=$t.'_etapa';
        $p->exec('CREATE TABLE `'.$t.'_cancelacion`(id BIGINT AUTO_INCREMENT PRIMARY KEY,`'.$t.'_id` BIGINT NOT NULL,
            FOREIGN KEY(`'.$t.'_id`) REFERENCES `'.$t.'`(id)) ENGINE=InnoDB');$tablas[]=$t.'_cancelacion';
        $p->exec("INSERT INTO `$t` VALUES(1,'A-000001','TIMBRADO'),(2,'A-000001','TIMBRADO'),(3,'A-000003','TIMBRADO'),(100,'A-000100','TIMBRADO')");
        [$d,$e,$c]=modelos_qa($p,$t);
        $r1=$alta($e,1); $r2=$alta($e,2); $r3=$alta($e,1);
        $exigir(!errores::$error && $r1->registro['descripcion']===$r2->registro['descripcion'] &&
            $r1->registro['codigo']!==$r2->registro['codigo'] && $r1->registro['codigo']!==$r3->registro['codigo'] &&
            $contar($e->tabla)===3,$t.'_folio_repetido_y_eventos_mismo_dia_preservados');
        $exigir(str_starts_with($r1->registro['codigo'],$t.'-1-9-') &&
            $r1->registro['codigo_bis']===$r1->registro['codigo'] && strlen($r1->registro['codigo'])<=200,
            $t.'_identidad_evento_y_longitud_codigo');
        $alta($e,3,10,['codigo'=>'EXPLICITO','codigo_bis'=>'EXPLICITO-BIS','descripcion'=>'Descripción del operador']);
        $exigir(!errores::$error && $e->registro['codigo']==='EXPLICITO' && $e->registro['descripcion']==='Descripción del operador',
            $t.'_codigo_y_descripcion_explicitos_preservados');
        $p->beginTransaction(); $p->exec("INSERT INTO `$t` VALUES(4,'EXTERNO','TIMBRADO')");
        $fallo=$alta($e,3,11,['codigo'=>'EXPLICITO']);
        $exigir(errores::$error && $p->inTransaction() &&
            $p->query("SELECT etapa FROM `$t` WHERE id=3")->fetchColumn()==='ALTA' &&
            $p->query("SELECT id FROM `$t` WHERE id=4")->fetchColumn()===4,
            $t.'_duplicado_explicito_revierte_solo_savepoint');
        $p->rollBack();
        $fallo=$alta($e,3,11,['codigo'=>'EXPLICITO']);
        $exigir(errores::$error && !$p->inTransaction() &&
            $p->query("SELECT etapa FROM `$t` WHERE id=3")->fetchColumn()==='ALTA',
            $t.'_duplicado_explicito_revierte_tx_propia');
        $antes=$contar($e->tabla); $alta($e,999);
        $exigir(errores::$error && $contar($e->tabla)===$antes && !$p->inTransaction(),$t.'_entidad_inexistente_sin_historial_huerfano');
        $GLOBALS['qa_forzar_fk']=true; $alta($e,3,11); unset($GLOBALS['qa_forzar_fk']);
        $exigir(errores::$error && $p->query("SELECT etapa FROM `$t` WHERE id=3")->fetchColumn()==='ALTA',
            $t.'_fk_insert_real_revierte_actualizacion_entidad');
        $alta($e,3,999);
        $exigir(errores::$error && $p->query("SELECT etapa FROM `$t` WHERE id=3")->fetchColumn()==='ALTA',
            $t.'_proceso_inexistente_preserva_entidad');
        errores::$error=false; $d->cancela_bd(2,$c,$e,1);
        $exigir(errores::$error && $contar('qa_pac_llamada')===0,$t.'_cancelada_no_reenvia_pac');
        $p->exec("UPDATE `$t` SET etapa='TIMBRADO' WHERE id=1");
        errores::$error=false; $d->cancela_bd(2,$c,$e,1);
        $exigir(errores::$error && $contar('qa_pac_llamada')===0,$t.'_historial_cancelado_impide_reenvio_con_cache_desfasada');
        $p->exec("INSERT INTO `{$t}_cancelacion` (`{$t}_id`) VALUES(3)");
        errores::$error=false; $r=$d->cancela_bd(2,$c,$e,3);
        $exigir(errores::$error && str_contains($r['mensaje'],'solicitud') && $contar('qa_pac_llamada')===0,
            $t.'_solicitud_previa_no_se_infiere_cancelacion_sat');
    }
    [$d,$e,$c]=modelos_qa($p);
    // La transacción externa crea snapshot antes de un cambio concurrente ya confirmado.
    $p->exec("INSERT INTO fc_factura VALUES(101,'SNAPSHOT','TIMBRADO')");
    $p->beginTransaction(); $p->query('SELECT COUNT(*) FROM fc_factura_cancelacion')->fetchColumn();
    $otro=conexion_qa(); $otro->exec('INSERT INTO fc_factura_cancelacion(fc_factura_id) VALUES(101)');
    errores::$error=false; $r=$d->cancela_bd(2,$c,$e,101);
    $exigir(errores::$error && str_contains($r['mensaje'],'solicitud') && $p->inTransaction() && $contar('qa_pac_llamada')===0,
        'guard_lee_cancelacion_actual_aunque_snapshot_externo_sea_anterior');
    $p->rollBack(); $otro=null;
    $p->beginTransaction(); errores::$error=false; $c->fallar=true;
    $r=$d->cancela_bd(2,$c,$e,100);
    $exigir(errores::$error && $p->inTransaction() && $contar('qa_pac_llamada')===1 &&
        $p->query('SELECT etapa FROM fc_factura WHERE id=100')->fetchColumn()==='TIMBRADO',
        'rechazo_pac_ficticio_no_crea_cancelacion_etapa_ni_cierra_tx_externa');
    $p->rollBack(); $c->fallar=false;
    $p->exec("INSERT INTO fc_factura VALUES(102,'TX-EXTERNA','TIMBRADO'),(103,'FALLO-LOCAL','TIMBRADO')");
    $p->beginTransaction(); errores::$error=false; $e->verifica_permite_transaccion=false;
    $r=$d->cancela_bd(2,$c,$e,102);
    $exigir(!errores::$error && $p->inTransaction() && $e->verifica_permite_transaccion===false && $contar('qa_pac_llamada')===2 &&
        $p->query('SELECT etapa FROM fc_factura WHERE id=102')->fetchColumn()==='CANCELADO',
        'cancelacion_exitosa_respeta_tx_externa_y_bandera_previa');
    $p->rollBack();
    $exigir($p->query('SELECT etapa FROM fc_factura WHERE id=102')->fetchColumn()==='TIMBRADO' &&
        $contar('qa_pac_llamada')===2,'caller_puede_revertir_cambios_locales_sin_commit_oculto');
    $GLOBALS['qa_etapa_codigo']='EXPLICITO'; errores::$error=false; $e->verifica_permite_transaccion=true;
    $r=$d->cancela_bd(2,$c,$e,103); unset($GLOBALS['qa_etapa_codigo']);
    $exigir(errores::$error && !$p->inTransaction() && $e->verifica_permite_transaccion===true && $contar('qa_pac_llamada')===3 &&
        $p->query('SELECT etapa FROM fc_factura WHERE id=103')->fetchColumn()==='TIMBRADO' &&
        (int)$p->query('SELECT COUNT(*) FROM fc_factura_cancelacion WHERE fc_factura_id=103')->fetchColumn()===0,
        'error_persistencia_etapa_revierte_cancelacion_local_y_restaura_bandera');
    $antesPac=$contar('qa_pac_llamada');
    $workers=[];
    for($i=0;$i<2;$i++) {
        $env=getenv(); $env['QA_CANCELACION_WORKER']='1';
        $proc=proc_open([PHP_BINARY,__FILE__,'--worker'],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,null,$env);
        if(!is_resource($proc)) throw new RuntimeException('No inició worker de concurrencia.');
        fclose($pipes[0]);$workers[]=[$proc,$pipes];
    }
    $salidas=[];
    foreach($workers as [$proc,$pipes]) {
        $out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);
        if(proc_close($proc)!==0 || $err!=='') throw new RuntimeException('Worker inválido: '.$err);
        $salidas[]=json_decode($out,true,512,JSON_THROW_ON_ERROR);
    }
    $exigir(count(array_filter($salidas,static fn($v)=>$v['error']===false))===1 && $contar('qa_pac_llamada')===$antesPac+1 &&
        (int)$p->query('SELECT COUNT(*) FROM fc_factura_cancelacion WHERE fc_factura_id=100')->fetchColumn()===1 &&
        $p->query('SELECT etapa FROM fc_factura WHERE id=100')->fetchColumn()==='CANCELADO',
        'dos_cancelaciones_concurrentes_solo_un_pac_ficticio_y_un_evento');
    errores::$error=false; $r=$d->cancela_bd(2,$c,$e,0);
    $exigir(errores::$error && $contar('qa_pac_llamada')===$antesPac+1,'id_invalido_no_llama_pac');
} finally {
    if($p->inTransaction())$p->rollBack();
    foreach(array_reverse($tablas) as $t)$p->exec('DROP TABLE `'.$t.'`');
    $limpio=!$p->query('SHOW TABLES')->fetchAll();
    unlink($log); foreach(glob($barrera.'/ready-*') as $f)unlink($f); rmdir($barrera);
}
if(!$limpio)throw new RuntimeException('No se acreditó limpieza.');
echo json_encode(['estado'=>'PASS_CANCELACION_ETAPA','motor_real'=>true,'sin_pac_real'=>true,
    'limpieza_confirmada'=>$limpio,'casos'=>$casos,'casos_ejecutados'=>count($casos)],JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE).PHP_EOL;
