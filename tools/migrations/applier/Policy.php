<?php
declare(strict_types=1);
final class MigrationFailure extends RuntimeException {}
function demand(bool $ok,string $code):void{if(!$ok)throw new MigrationFailure($code);}
function canonical(mixed $v):string{if(is_array($v)){if(!array_is_list($v))ksort($v);foreach($v as $k=>$x)$v[$k]=json_decode(canonical($x),true,512,JSON_THROW_ON_ERROR);}return json_encode($v,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES|JSON_PRESERVE_ZERO_FRACTION);}
function digest(mixed $v):string{return hash('sha256',canonical($v));}
function jsonFile(string $p,int $max=1048576):array{demand(is_file($p)&&!is_link($p)&&filesize($p)<=$max,'file_boundary');$v=json_decode(file_get_contents($p),true,64,JSON_THROW_ON_ERROR);demand(is_array($v),'json_shape');return $v;}
final class Package {
 public array $manifest;public string $hash;public array $sql=[],$expected=[];
 public function __construct(public string $path){
  demand(realpath($path)===$path,'package_path');demand(is_file($path.'/manifest.json')&&!is_link($path.'/manifest.json')&&filesize($path.'/manifest.json')<=1048576,'manifest_boundary');$manifestBytes=file_get_contents($path.'/manifest.json',false,null,0,1048577);demand(strlen($manifestBytes)<=1048576,'manifest_size');$this->manifest=json_decode($manifestBytes,true,64,JSON_THROW_ON_ERROR);$this->hash=hash('sha256',$manifestBytes);
  demand(($this->manifest['format']??null)===1,'package_format');$entries=$this->manifest['entries']??[];
  demand(array_column($entries,'table')===['cc_tipo_medidor','cc_medidor','base_movil_sesion','base_movil_push_token'],'table_scope');$seen=['adm_usuario'];
  foreach($entries as $e){$n=$e['table'];demand($e['sql']===$n.'.sql'&&$e['structure']===$n.'.json','package_paths');foreach([$e['sql']=>$e['sha256'],$e['structure']=>$e['structure_sha256']] as $f=>$h){demand(is_file($path.'/'.$f)&&!is_link($path.'/'.$f)&&filesize($path.'/'.$f)<=65536,'package_boundary');$bytes[$f]=file_get_contents($path.'/'.$f,false,null,0,65537);demand(is_string($bytes[$f])&&strlen($bytes[$f])<=65536&&preg_match('/^[a-f0-9]{64}$/D',$h)===1&&hash_equals($h,hash('sha256',$bytes[$f])),'package_pin');}
   $sql=$bytes[$e['sql']];demand(preg_match('/^CREATE TABLE `?'.preg_quote($n,'/').'`? \([\s\S]+\) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n$/D',$sql)===1&&substr_count($sql,';')===1&&!preg_match('/\b(IF NOT EXISTS|ALTER|DROP|INSERT|UPDATE\s+\w+\s+SET|DELETE\s+FROM|TRIGGER|PROCEDURE|DEFINER|DIRECTORY)\b|--|\/\*|#/i',$sql),'ddl_scope');
   foreach($e['parents'] as $parent)demand(in_array($parent,$seen,true),'dependency_order');$seen[]=$n;$this->sql[$n]=$sql;$this->expected[$n]=json_decode($bytes[$e['structure']],true,64,JSON_THROW_ON_ERROR);
  }
 }
}
interface DatabaseTransport {public function identity():array;public function inspect(string $table):?array;public function lock(string $name):bool;public function unlock(string $name):void;public function create(string $sql):void;public function preflight(array $tables,array $foreignKeys):void;}
interface Journal {public function acquire():void;public function release():void;public function read():?array;public function save(array $v):void;}
final class FileJournal implements Journal {
 private mixed $lock=null;
 public function __construct(private string $file){$dir=dirname($file);demand(realpath($dir)===$dir&&is_dir($dir)&&((fileperms($dir)&0077)===0),'journal_directory');demand(basename($file)==='ledger.json','journal_name');}
 private function safe(string $f):void{demand(!is_link($f)&&(!file_exists($f)||(is_file($f)&&(fileperms($f)&0077)===0)),'journal_permissions');}
 public function acquire():void{$f=$this->file.'.lock';$this->safe($f);$old=umask(0077);try{$this->lock=fopen($f,'c+b');}finally{umask($old);}demand(is_resource($this->lock)&&flock($this->lock,LOCK_EX|LOCK_NB),'local_lock_busy');}
 public function release():void{if(is_resource($this->lock)){flock($this->lock,LOCK_UN);fclose($this->lock);}$this->lock=null;}
 public function read():?array{$this->safe($this->file);return file_exists($this->file)?jsonFile($this->file):null;}
 public function save(array $v):void{
  demand(is_resource($this->lock),'journal_lock_required');$this->safe($this->file);$tmp=$this->file.'.'.bin2hex(random_bytes(8)).'.tmp';$b=canonical($v)."\n";$old=umask(0077);
  try{$f=fopen($tmp,'xb');demand(is_resource($f),'journal_open');try{demand(fwrite($f,$b)===strlen($b)&&fflush($f)&&fsync($f),'journal_sync');}finally{fclose($f);}demand(rename($tmp,$this->file),'journal_rename');$d=fopen(dirname($this->file),'r');demand(is_resource($d),'journal_dir_open');try{demand(fsync($d),'journal_dir_sync');}finally{fclose($d);}}finally{umask($old);}
 }
}
function verifyBackup(string $path,string $approvedHash,array $identity,array $scope):array{
 demand(preg_match('/^[a-f0-9]{64}$/D',$approvedHash)===1&&hash_equals($approvedHash,hash_file('sha256',$path)),'backup_manifest_pin');$m=jsonFile($path);demand(canonical($m['identity']??null)===canonical($identity)&&($m['scope']??null)===$scope,'backup_scope');demand(in_array($m['kind']??null,['synthetic_fixture','external_backup'],true),'backup_kind');demand(in_array($m['restore_status']??null,['fixture_reconstruction_verified','independently_verified'],true),'backup_verification_required');demand(($m['kind']==='synthetic_fixture')===($m['restore_status']==='fixture_reconstruction_verified'),'backup_claim');
 demand(is_array($m['artifacts']??null)&&count($m['artifacts'])>=1&&count($m['artifacts'])<=8,'backup_artifacts');foreach($m['artifacts'] as $a){demand(preg_match('/^[a-zA-Z0-9_.-]{1,80}$/D',$a['file']??'')===1&&$a['file']!=='.'&&$a['file']!=='..','backup_path');$f=dirname($path).'/'.$a['file'];demand(is_file($f)&&!is_link($f)&&is_int($a['bytes'])&&filesize($f)===$a['bytes']&&hash_equals($a['sha256'],hash_file('sha256',$f)),'backup_bytes');}
 return ['manifest_sha256'=>$approvedHash,'kind'=>$m['kind'],'restore_status'=>$m['restore_status'],'integrity_checked'=>true,'restore_not_performed_by_tool'=>true];
}
final class Migrator {
 public function __construct(private Package $package,private DatabaseTransport $db,private Journal $journal,private array $target){}
 private function bind():array{
  $id=$this->db->identity();demand(canonical($id)===canonical($this->target['identity']??null),'target_identity');$parent=$this->db->inspect('adm_usuario');demand(is_array($parent)&&digest($parent)===($this->target['parent_sha256']??null),'parent_fingerprint');
  demand($parent['engine']==='InnoDB','parent_engine');$cols=array_column($parent['columns'],null,'name');demand(isset($cols['id'])&&$cols['id']['type']==='bigint'&&!$cols['id']['nullable'],'parent_id_type');$primary=array_values(array_filter($parent['indexes'],fn($i)=>$i['name']==='PRIMARY'));demand(count($primary)===1&&$primary[0]['unique']&&$primary[0]['columns']===[['name'=>'id','prefix'=>null,'order'=>'A']],'parent_id_index');return $id;
 }
 public function check():array{
  $id=$this->bind();$states=[];foreach($this->package->expected as $n=>$expected){$s=$this->db->inspect($n);$states[$n]=$s===null?'absent':(canonical($s)===canonical($expected)?'exact':'drift');}
  $j=$this->journal->read();$mode='blocked';
  if(in_array('drift',$states,true))$mode='drift';elseif($j===null&&count(array_unique($states))===1&&current($states)==='absent')$mode='ready';elseif($j===null)$mode='reconcile_required';else{
   demand(($j['format']??null)===1&&($j['package_sha256']??null)===$this->package->hash&&canonical($j['identity']??null)===canonical($id),'journal_binding');
   if(($j['status']??null)==='applied'&&count(array_unique($states))===1&&current($states)==='exact'){
    $keys=array_keys($j['steps']??[]);sort($keys);$expectedKeys=array_keys($this->package->sql);sort($expectedKeys);demand($keys===$expectedKeys,'journal_steps');foreach($this->package->manifest['entries'] as $e){$s=$j['steps'][$e['table']];demand(($s['state']??null)==='applied'&&($s['sql_sha256']??null)===$e['sha256']&&($s['structure_sha256']??null)===digest($this->package->expected[$e['table']]),'journal_drift');}$mode='noop';
   }else $mode='dirty_stop';
  }
  return ['mode'=>$mode,'tables'=>$states,'identity'=>$id,'package_sha256'=>$this->package->hash,'database_modified'=>false];
 }
 public function apply(string $approvedPackage,string $backupPath,string $approvedBackup):array{
  demand(hash_equals($this->package->hash,$approvedPackage),'package_approval');$id=$this->bind();$backup=verifyBackup($backupPath,$approvedBackup,$id,array_keys($this->package->sql));$this->check();$this->journal->acquire();$locked=false;$j=null;$lock='migrations:'.substr(digest(array_intersect_key($id,array_flip(['database','server_id','socket','datadir']))),0,48);
  try{
   demand($this->db->lock($lock),'database_lock_busy');$locked=true;$check=$this->check();if($check['mode']==='noop')return $check+['backup'=>$backup];demand($check['mode']==='ready','preflight_not_ready');$fks=[];foreach($this->package->expected as $tn=>$structure)foreach($structure['foreign_keys'] as $fk)$fks[$fk['name']]=$tn;$this->db->preflight(array_keys($this->package->sql),$fks);
   $j=['format'=>1,'package_sha256'=>$this->package->hash,'identity'=>$id,'backup'=>$backup,'status'=>'running','steps'=>[]];foreach($this->package->manifest['entries'] as $e)$j['steps'][$e['table']]=['state'=>'pending','sql_sha256'=>$e['sha256'],'structure_sha256'=>null];$this->journal->save($j);
   foreach($this->package->sql as $n=>$sql){$j['steps'][$n]['state']='started';$this->journal->save($j);$this->db->create($sql);demand(canonical($this->db->inspect($n))===canonical($this->package->expected[$n]),'postcondition_drift');$j['steps'][$n]['state']='applied';$j['steps'][$n]['structure_sha256']=digest($this->package->expected[$n]);$this->journal->save($j);}
   $this->bind();foreach($this->package->expected as $tn=>$structure)demand(canonical($this->db->inspect($tn))===canonical($structure),'global_postcondition_drift');
   $j['status']='applied';$this->journal->save($j);return ['mode'=>'applied','database_modified'=>true,'ddl_autocommit'=>true,'backup'=>$backup,'rollback_attempted'=>false];
  }catch(Throwable $e){if($j!==null){$j['status']='uncertain_stop';$j['failure_class']=get_class($e);try{$this->journal->save($j);}catch(Throwable $ignored){}}throw $e;}
  finally{if($locked){try{$this->db->unlock($lock);}catch(Throwable $ignored){}}$this->journal->release();}
 }
}
