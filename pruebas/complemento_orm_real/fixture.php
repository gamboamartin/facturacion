<?php
declare(strict_types=1);
/** Solo SQL ficticio; nunca ejecuta altas de certificados ni modelos para construir maestros. */
function fixture_cp_orm(PDO $link): Closure {
 if($link->query('SELECT DATABASE()')->fetchColumn()!=='qa_csd_integral'
  || (int)$link->query('SELECT @@skip_networking')->fetchColumn()!==1 || !is_file('/qa-instance-id')
  || readlink('/proc/self/ns/net')===getenv('QA_HOST_NET_NS'))throw new RuntimeException('Motor efímero requerido');
 $schema=json_decode(file_get_contents(__DIR__.'/esquema.json'),true,512,JSON_THROW_ON_ERROR)['tables'];
 $pending=$schema;$order=[];
 while($pending){$progress=false;foreach($pending as $t=>$s){
  foreach($s['foreign_keys'] as $fk)if($fk['REFERENCED_TABLE_NAME']!==$t&&!in_array($fk['REFERENCED_TABLE_NAME'],$order,true))continue 2;
  if(hash('sha256',$s['ddl'])!==$s['sha256'])throw new RuntimeException('DDL alterado');
  $link->exec($s['ddl']);$order[]=$t;unset($pending[$t]);$progress=true;
 }if(!$progress)throw new RuntimeException('Ciclo DDL no previsto');}
 $insert=static function(string $t,array $extra=[])use($link,$schema):void{
  $row=[];$fk=array_column($schema[$t]['foreign_keys'],'REFERENCED_TABLE_NAME','COLUMN_NAME');
  foreach($schema[$t]['columns'] as $c){$n=$c['Field'];$type=$c['Type'];
   if(isset($fk[$n])||$n==='id'||str_ends_with($n,'_id'))$v=1;
   elseif(str_starts_with($type,'timestamp')||str_starts_with($type,'datetime'))$v='2026-11-24 12:00:00';
   elseif(str_starts_with($type,'date'))$v='2026-11-24';
   elseif(preg_match('/^(?:bigint|int|smallint|tinyint|double|decimal|float)/',$type))$v=0;
   elseif(str_starts_with($type,'enum(')){preg_match("/enum\('([^']*)'/",$type,$m);$v=$m[1];}
   elseif($n==='status')$v='activo';
   elseif(in_array($n,['predeterminado','es_plantilla','root','solo_mi_info'],true))$v='inactivo';
   elseif($c['Default']!==null && !str_contains(strtolower((string)$c['Default']),'current_timestamp'))$v=$c['Default'];
   elseif($c['Null']==='YES')$v=null;
   else $v='QA-FICTICIO';
   $row[$n]=$v;
  }
  $row=array_replace($row,$extra);
  $keys=array_keys($row);$q=$link->prepare('INSERT INTO `'.$t.'` (`'.implode('`,`',$keys).'`) VALUES ('.implode(',',array_fill(0,count($keys),'?')).')');
  try{$q->execute(array_values($row));}catch(Throwable $e){throw new RuntimeException('Fixture '.$t.': '.$e->getMessage(),0,$e);}
 };
 $overrides=[
 'adm_usuario'=>['user'=>'qa_cp_sin_login','password'=>'NO-LOGIN-FICTICIO','email'=>'qa@example.invalid','nombre'=>'QA FICTICIO'],
 'adm_namespace'=>['descripcion'=>'gamboamartin\\facturacion\\models'],
 'adm_seccion'=>['descripcion'=>'fc_complemento_pago'],'adm_accion'=>['descripcion'=>'alta_bd'],
 'pr_etapa'=>['descripcion'=>'ALTA'],'pr_proceso'=>['descripcion'=>'fc_complemento_pago'],
 'cat_sat_forma_pago'=>['codigo'=>'99'],'cat_sat_metodo_pago'=>['codigo'=>'PPD'],
 'cat_sat_moneda'=>['codigo'=>'MXN'],'cat_sat_uso_cfdi'=>['codigo'=>'CP01'],
 'cat_sat_tipo_de_comprobante'=>['codigo'=>'P'],'cat_sat_regimen_fiscal'=>['codigo'=>'601'],
 'cat_sat_unidad'=>['codigo'=>'ACT'],'cat_sat_obj_imp'=>['codigo'=>'01'],
 'cat_sat_producto'=>['codigo'=>'84111506'],'cat_sat_factor'=>['factor'=>'0.00'],
 'com_tipo_cambio'=>['monto'=>'1.00','fecha'=>'2026-11-24'],
 'org_empresa'=>['rfc'=>'AAA010101AAA','razon_social'=>'EMISOR FICTICIO SIN VALIDEZ','logo'=>''],
 'com_cliente'=>['rfc'=>'XAXX010101000','razon_social'=>'RECEPTOR FICTICIO SIN VALIDEZ'],
 'fc_csd'=>['serie'=>'QA','password'=>'SIN-CERTIFICADO','no_certificado'=>'00000000000000000000'],
 'com_producto'=>['codigo'=>'99999999','codigo_sat'=>'84111506','precio'=>'0.00'],
 'fc_factura'=>['codigo'=>'QA-FACTURA','codigo_bis'=>'QA-FACTURA','folio'=>'QA-000001','serie'=>'QA','folio_fiscal'=>'00000000-0000-4000-8000-000000000001','etapa'=>'TIMBRADO','version'=>'4.0','exportacion'=>'01','fecha'=>'2026-11-24','total'=>'100.00','sub_total'=>'100.00','sub_total_base'=>'100.00','saldo'=>'100.00','aplica_saldo'=>'activo','es_plantilla'=>'inactivo'],
 'fc_partida'=>['codigo'=>'QA-PARTIDA','cantidad'=>'1.00','valor_unitario'=>'100.00','sub_total'=>'100.00','sub_total_base'=>'100.00','total'=>'100.00'],
 'fc_cfdi_sellado'=>['uuid'=>'00000000-0000-4000-8000-000000000001']];
 foreach($order as $t){if(str_starts_with($t,'fc_')&&!in_array($t,['fc_csd','fc_factura','fc_partida','fc_cfdi_sellado'],true))continue;if(in_array($t,['com_email_cte','cat_sat_traslado_conf','cat_sat_retencion_conf','com_precio_cliente'],true))continue;$insert($t,$overrides[$t]??[]);}
 $insert('cat_sat_moneda',['id'=>2,'codigo'=>'XXX','codigo_bis'=>'XXX','alias'=>'XXX','descripcion_select'=>'XXX']);
 $insert('com_tipo_cambio',['id'=>2,'codigo'=>'QA-TC-XXX','codigo_bis'=>'QA-TC-XXX','alias'=>'QA-TC-XXX','descripcion_select'=>'QA-TC-XXX','cat_sat_moneda_id'=>2,'monto'=>'1.00','fecha'=>'2026-11-24']);
 $insert('cat_sat_forma_pago',['id'=>2,'codigo'=>'03','codigo_bis'=>'03','alias'=>'03','descripcion_select'=>'03']);
 $_SESSION=['usuario_id'=>1];$_POST=[];$_FILES=[];
 return $insert;
}
