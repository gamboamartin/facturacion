<?php
declare(strict_types=1);
const APPLY_ENABLED=true;
require __DIR__.'/PdoTransport.php';
set_error_handler(function($n,$s,$f,$l){if(!(error_reporting()&$n))return false;throw new ErrorException('runtime_warning',0,$n);});
try{
 $mode=$argv[1]??'plan';demand(in_array($mode,['plan','check','apply'],true),'mode');$args=[];for($i=2;$i<count($argv);$i+=2){demand(isset($argv[$i+1])&&str_starts_with($argv[$i],'--')&&!isset($args[$argv[$i]]),'arguments');$args[$argv[$i]]=$argv[$i+1];}
 $allowed=['--package','--connection','--target','--journal','--approve-package','--backup','--approve-backup'];foreach(array_keys($args) as $k)demand(in_array($k,$allowed,true),'argument_scope');
 $package=new Package($args['--package']??__DIR__.'/package');
 if($mode==='plan'){demand(count(array_diff(array_keys($args),['--package']))===0,'plan_offline');echo canonical(['mode'=>'plan','package_sha256'=>$package->hash,'tables'=>array_keys($package->sql),'writes'=>false,'apply_enabled'=>APPLY_ENABLED,'ddl_autocommit'=>true]),"\n";exit(0);}
 if($mode==='apply')demand(APPLY_ENABLED,'candidate_apply_disabled');
 foreach(['--connection','--target','--journal'] as $k)demand(isset($args[$k]),'required_argument');
 $cp=$args['--connection'];demand(is_file($cp)&&!is_link($cp)&&(fileperms($cp)&0077)===0,'private_connection_file');$c=jsonFile($cp,16384);$ordered=[];foreach(['socket','database','user','password'] as $k){demand(isset($c[$k])&&is_string($c[$k]),'connection_field');$ordered[$k]=$c[$k];}demand(count($c)===4,'connection_fields');$db=new PdoTransport($ordered);unset($c,$ordered);
 $target=jsonFile($args['--target'],16384);$j=new FileJournal($args['--journal']);$app=new Migrator($package,$db,$j,$target);
 if($mode==='check')$result=$app->check();else{foreach(['--approve-package','--backup','--approve-backup'] as $k)demand(isset($args[$k]),'required_approval');$result=$app->apply($args['--approve-package'],$args['--backup'],$args['--approve-backup']);}
 echo canonical(['success'=>true,'result'=>$result]),"\n";
}catch(Throwable $e){$safe=$e instanceof MigrationFailure?$e->getMessage():'unexpected_failure';echo canonical(['success'=>false,'error_code'=>$safe,'error_type'=>get_class($e),'automatic_retry'=>false,'rollback_attempted'=>false]),"\n";exit(1);}
