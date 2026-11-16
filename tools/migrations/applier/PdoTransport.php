<?php
declare(strict_types=1);
require_once __DIR__.'/Policy.php';
final class PdoTransport implements DatabaseTransport {
 private PDO $p;private string $schema;
 public function __construct(array $c){demand(array_keys($c)===['socket','database','user','password'],'connection_fields');demand(is_string($c['socket'])&&str_starts_with($c['socket'],'/')&&!str_contains($c['socket'],';')&&preg_match('/^[a-zA-Z0-9_]{1,64}$/D',$c['database'])===1,'connection_scope');$this->schema=$c['database'];$this->p=new PDO('mysql:unix_socket='.$c['socket'].';dbname='.$c['database'],$c['user'],$c['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false,PDO::MYSQL_ATTR_MULTI_STATEMENTS=>false]);$v=$this->p->query('SELECT @@autocommit,@@foreign_key_checks,@@sql_mode,VERSION()')->fetch(PDO::FETCH_NUM);demand((int)$v[0]===1&&(int)$v[1]===1&&str_starts_with($v[3],'10.6.'),'connection_defaults');$strict=self::sessionMode($v[2]);if($strict!==$v[2]){$this->p->exec('SET SESSION sql_mode = '.$this->p->quote($strict));}$actual=explode(',',$this->p->query('SELECT @@SESSION.sql_mode')->fetchColumn());$expected=explode(',',$strict);sort($actual);sort($expected);demand($actual===$expected,'session_mode_verification');$this->verifyVisibility();}

 // Only strengthens this dedicated migration connection; never changes GLOBAL defaults.
 public static function sessionMode(string $mode):string{
  $parts=explode(',',$mode);
  demand(in_array('STRICT_ALL_TABLES',$parts,true)||in_array('STRICT_TRANS_TABLES',$parts,true),'strict_session_required');
  if(in_array('STRICT_ALL_TABLES',$parts,true))return $mode;
  return $mode.',STRICT_ALL_TABLES';
 }
 private function verifyVisibility():void{
  $grants=$this->p->query('SHOW GRANTS')->fetchAll(PDO::FETCH_COLUMN);$covered=[];
  foreach($grants as $g){if(!preg_match('/^GRANT (.+) ON (`?[a-zA-Z0-9_]+`?|\*)\.(`?[a-zA-Z0-9_]+`?|\*) TO /D',$g,$m))continue;$priv=array_map('trim',explode(',',$m[1]));if(!in_array('ALL PRIVILEGES',$priv,true)&&!in_array('TRIGGER',$priv,true))continue;$db=trim($m[2],'`');$table=trim($m[3],'`');if($db!=='*'&&$db!==$this->schema)continue;foreach(['adm_usuario','cc_tipo_medidor','cc_medidor','base_movil_sesion','base_movil_push_token'] as $n)if($table==='*'||$table===$n)$covered[$n]=true;}
  demand(count($covered)===5,'trigger_visibility_privilege_required');
  $coll=$this->rows("SELECT COLLATION_NAME FROM information_schema.COLLATIONS WHERE COLLATION_NAME IN ('ascii_bin','utf8mb4_unicode_ci') ORDER BY COLLATION_NAME");demand(array_column($coll,'COLLATION_NAME')===['ascii_bin','utf8mb4_unicode_ci'],'required_collations');
 }
 private function rows(string $sql,array $args=[]):array{$s=$this->p->prepare($sql);$s->execute($args);$r=$s->fetchAll(PDO::FETCH_ASSOC);$s->closeCursor();return $r;}
 public function identity():array{$r=$this->p->query('SELECT DATABASE(),VERSION(),CAST(@@server_id AS CHAR),@@socket,@@datadir,CURRENT_USER(),@@hostname')->fetch(PDO::FETCH_NUM);return array_combine(['database','version','server_id','socket','datadir','account','hostname'],$r);}
 public static function type(string $v):string{return preg_replace('/^(bigint|int)\([0-9]+\)( unsigned)?$/D','$1$2',preg_replace_callback('/^[A-Za-z]+/',fn($m)=>strtolower($m[0]),$v));}
 public static function defaultValue(?string $v):?string{if($v===null||$v==='NULL')return null;if(strlen($v)>=2&&$v[0]==="'"&&$v[-1]==="'")return str_replace("''","'",substr($v,1,-1));return $v;}
 public function inspect(string $table):?array{
  demand(in_array($table,['adm_usuario','cc_tipo_medidor','cc_medidor','base_movil_sesion','base_movil_push_token'],true),'inspect_scope');$a=[$this->schema,$table];
  $t=$this->rows('SELECT TABLE_TYPE,ENGINE,TABLE_COLLATION,ROW_FORMAT,CREATE_OPTIONS,TABLE_COMMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=?',$a);if($t===[])return null;demand(count($t)===1&&$t[0]['TABLE_TYPE']==='BASE TABLE','table_kind');$t=$t[0];
  $out=['engine'=>$t['ENGINE'],'collation'=>$t['TABLE_COLLATION'],'row_format'=>$t['ROW_FORMAT'],'create_options'=>$t['CREATE_OPTIONS'],'comment'=>$t['TABLE_COMMENT'],'columns'=>[],'indexes'=>[],'foreign_keys'=>[],'checks'=>[],'triggers'=>[]];
  foreach($this->rows('SELECT COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,COLUMN_DEFAULT,EXTRA,CHARACTER_SET_NAME,COLLATION_NAME,GENERATION_EXPRESSION,COLUMN_COMMENT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? ORDER BY ORDINAL_POSITION',$a) as $c)$out['columns'][]=['name'=>$c['COLUMN_NAME'],'type'=>self::type($c['COLUMN_TYPE']),'nullable'=>$c['IS_NULLABLE']==='YES','default'=>self::defaultValue($c['COLUMN_DEFAULT']),'extra'=>$c['EXTRA'],'charset'=>$c['CHARACTER_SET_NAME'],'collation'=>$c['COLLATION_NAME'],'generation'=>$c['GENERATION_EXPRESSION']??'','comment'=>$c['COLUMN_COMMENT']];
  $ix=[];foreach($this->rows('SELECT INDEX_NAME,NON_UNIQUE,INDEX_TYPE,COLUMN_NAME,SUB_PART,COLLATION,IGNORED,INDEX_COMMENT FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? ORDER BY INDEX_NAME,SEQ_IN_INDEX',$a) as $i){$n=$i['INDEX_NAME'];if(!isset($ix[$n]))$ix[$n]=['name'=>$n,'unique'=>(int)$i['NON_UNIQUE']===0,'type'=>$i['INDEX_TYPE'],'ignored'=>$i['IGNORED']==='YES','comment'=>$i['INDEX_COMMENT'],'columns'=>[]];$ix[$n]['columns'][]=['name'=>$i['COLUMN_NAME'],'prefix'=>$i['SUB_PART']===null?null:(int)$i['SUB_PART'],'order'=>$i['COLLATION']];}uksort($ix,'strcmp');$out['indexes']=array_values($ix);
  foreach($this->rows('SELECT CONSTRAINT_NAME,REFERENCED_TABLE_NAME,UPDATE_RULE,DELETE_RULE,MATCH_OPTION,UNIQUE_CONSTRAINT_NAME FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=? AND TABLE_NAME=? ORDER BY CONSTRAINT_NAME',$a) as $fk){$cols=$this->rows('SELECT COLUMN_NAME,REFERENCED_TABLE_SCHEMA,REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND CONSTRAINT_NAME=? ORDER BY ORDINAL_POSITION',[$this->schema,$table,$fk['CONSTRAINT_NAME']]);foreach($cols as $c)demand($c['REFERENCED_TABLE_SCHEMA']===$this->schema,'foreign_schema');$out['foreign_keys'][]=['name'=>$fk['CONSTRAINT_NAME'],'columns'=>array_column($cols,'COLUMN_NAME'),'parent'=>$fk['REFERENCED_TABLE_NAME'],'parent_columns'=>array_column($cols,'REFERENCED_COLUMN_NAME'),'update'=>$fk['UPDATE_RULE'],'delete'=>$fk['DELETE_RULE'],'match'=>$fk['MATCH_OPTION'],'parent_index'=>$fk['UNIQUE_CONSTRAINT_NAME']];}
  $out['checks']=$this->rows("SELECT t.CONSTRAINT_NAME,c.CHECK_CLAUSE FROM information_schema.TABLE_CONSTRAINTS t JOIN information_schema.CHECK_CONSTRAINTS c ON c.CONSTRAINT_SCHEMA=t.CONSTRAINT_SCHEMA AND c.CONSTRAINT_NAME=t.CONSTRAINT_NAME AND c.TABLE_NAME=t.TABLE_NAME WHERE t.TABLE_SCHEMA=? AND t.TABLE_NAME=? AND t.CONSTRAINT_TYPE='CHECK' ORDER BY t.CONSTRAINT_NAME",$a);
  $out['triggers']=$this->rows('SELECT TRIGGER_NAME,ACTION_TIMING,EVENT_MANIPULATION,ACTION_STATEMENT FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=? AND EVENT_OBJECT_TABLE=? ORDER BY TRIGGER_NAME',$a);
  return $out;
 }
 public function preflight(array $tables,array $foreignKeys):void{
  $grants=$this->p->query('SHOW GRANTS')->fetchAll(PDO::FETCH_COLUMN);$covered=[];
  foreach($grants as $g){if(!preg_match('/^GRANT (.+) ON (`?[a-zA-Z0-9_]+`?|\*)\.(`?[a-zA-Z0-9_]+`?|\*) TO /D',$g,$m))continue;$priv=array_map('trim',explode(',',$m[1]));if(!in_array('ALL PRIVILEGES',$priv,true)&&!in_array('CREATE',$priv,true))continue;$db=trim($m[2],'`');$table=trim($m[3],'`');if($db!=='*'&&$db!==$this->schema)continue;foreach($tables as $n)if($table==='*'||$table===$n)$covered[$n]=true;}
  demand(count($covered)===count($tables),'create_privilege_required');
  $metadataVisible=false;foreach($grants as $g){if(preg_match('/^GRANT (.+) ON (`?[a-zA-Z0-9_]+`?|\*)\.\* TO /D',$g,$m)){ $priv=array_map('trim',explode(',',$m[1]));$db=trim($m[2],'`');if(($db==='*'||$db===$this->schema)&&(in_array('SELECT',$priv,true)||in_array('ALL PRIVILEGES',$priv,true)))$metadataVisible=true;}}demand($metadataVisible,'schema_metadata_visibility_required');

  if($foreignKeys!==[]){$q=implode(',',array_fill(0,count($foreignKeys),'?'));$rows=$this->rows('SELECT CONSTRAINT_NAME,TABLE_NAME FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=? AND CONSTRAINT_NAME IN ('.$q.')',array_merge([$this->schema],array_keys($foreignKeys)));foreach($rows as $r)demand(($foreignKeys[$r['CONSTRAINT_NAME']]??null)===$r['TABLE_NAME'],'foreign_key_name_collision');}
 }
 public function lock(string $name):bool{$r=$this->rows('SELECT GET_LOCK(?,0) AS locked',[$name]);return (int)$r[0]['locked']===1;}
 public function unlock(string $name):void{$this->rows('SELECT RELEASE_LOCK(?) AS released',[$name]);}
 public function create(string $sql):void{demand(!$this->p->inTransaction(),'unexpected_transaction');$this->p->exec($sql);demand($this->p->query('SHOW WARNINGS')->fetchAll()===[],'ddl_warnings');}
}
