<?php declare(strict_types=1);
namespace gamboamartin\errores {class errores {}}
namespace {
require __DIR__.'/init_original.php';require __DIR__.'/init_compatible.php';
$base=['columns'=>[(object)['data'=>'fc_partida_id'],(object)['data'=>'fc_partida_total']],'columnDefs'=>[(object)['targets'=>0,'type'=>'text'],(object)['targets'=>1,'type'=>'text']]];
$action=$base;$action['columns'][]=(object)['data'=>'modifica'];$action['columnDefs'][]=(object)['targets'=>2,'type'=>'button'];
$cases=[['asc',$base,['order'=>[['column'=>0,'dir'=>'asc']]],['fc_partida_id'=>'ASC']],['desc',$base,['order'=>[['column'=>0,'dir'=>'desc']]],['fc_partida_id'=>'DESC']],['ASC_upper',$base,['order'=>[['column'=>'0','dir'=>'ASC']]],['fc_partida_id'=>'ASC']],['last_data',$base,['order'=>[['column'=>1,'dir'=>'asc']]],['fc_partida_total'=>'ASC']],['action',$action,['order'=>[['column'=>2,'dir'=>'asc']]],[]],['negative',$base,['order'=>[['column'=>-1,'dir'=>'asc']]],[]],['too_large',$base,['order'=>[['column'=>999,'dir'=>'asc']]],[]],['direction_sql',$base,['order'=>[['column'=>0,'dir'=>'asc; DROP TABLE x']]],[]],['direction_array',$base,['order'=>[['column'=>0,'dir'=>[]]]],[]],['index_sql',$base,['order'=>[['column'=>'0 OR 1','dir'=>'asc']]],[]],['index_float',$base,['order'=>[['column'=>0.1,'dir'=>'asc']]],[]],['no_request',$base,[],['fc_partida_id'=>'DESC']]];
$r=['scope'=>'isolated dependency order only; no app/vendor changes','cases'=>[],'success'=>true];
foreach($cases as [$label,$dt,$get,$expected]){$_GET=$get;$original=null;$oldError=null;set_error_handler(function($n,$m){throw new \ErrorException($m);});try{$original=(new \Pruebas\OrdenOriginal\init())->order($dt);}catch(\Throwable $e){$oldError=get_class($e);}restore_error_handler();$_GET=$get;$before=$_GET;$new=(new \Pruebas\OrdenCompatible\init())->order($dt);$ok=$new===$expected&&$_GET===$before;$r['success']=$r['success']&&$ok;$r['cases'][]=['case'=>$label,'original'=>$original,'original_error'=>$oldError,'candidate'=>$new,'matches_expected'=>$ok];}
echo json_encode($r,JSON_PRETTY_PRINT),"\n";exit($r['success']?0:1);
}
