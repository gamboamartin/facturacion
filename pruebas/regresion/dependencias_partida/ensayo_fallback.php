<?php
namespace gamboamartin\errores {class errores {}}
namespace {
require __DIR__.'/init_original.php';require __DIR__.'/init_compatible.php';require __DIR__.'/init_corregido.php';
function observe($model,$dt,$get){$_GET=$get;$warnings=[];$thrown=null;$out=null;set_error_handler(function($severity,$message)use(&$warnings){$warnings[]=[$severity,$message];return true;});try{$out=$model->order($dt);}catch(\Throwable $e){$thrown=[get_class($e),$e->getMessage()];}finally{restore_error_handler();}return ['out'=>$out,'get'=>$_GET,'warnings'=>$warnings,'throw'=>$thrown];}
$base=['columns'=>[(object)['data'=>'id'],(object)['data'=>'total'],(object)['data'=>'accion']]];$cases=['class'=>'no-sort','css'=>'.no-sort','name'=>'total:name','string_index'=>'1','mixed'=>[0,'.no-sort'],'css_all'=>'th','null'=>[null],'object'=>(object)['selector'=>'th']];$rows=[];$pass=true;
foreach($cases as $name=>$target){$dt=$base;$dt['columnDefs']=[['targets'=>$target,'orderable'=>false]];foreach(['asc'=>['order'=>[['column'=>0,'dir'=>'asc']]],'absent'=>[],'negative'=>['order'=>[['column'=>-1,'dir'=>'asc']]]] as $request=>$get){$old=observe(new \Pruebas\OrdenOriginal\init,$dt,$get);$new=observe(new \Pruebas\OrdenCompatible\init,$dt,$get);$ok=$old===$new;$pass=$pass&&$ok;$rows[]=['case'=>$name.'/'.$request,'equal'=>$ok,'old'=>$old,'new'=>$new];}}
$numeric=$base;$numeric['columnDefs']=[['targets'=>-1,'type'=>'button'],['targets'=>[0,1],'orderable'=>true]];
foreach([0,1,2] as $i){$get=['order'=>[['column'=>$i,'dir'=>'asc']]];$old=observe(new \Pruebas\OrdenCorregido\init,$numeric,$get);$new=observe(new \Pruebas\OrdenCompatible\init,$numeric,$get);$ok=$old===$new;$pass=$pass&&$ok;$rows[]=['case'=>'numeric/'.$i,'equal'=>$ok,'old'=>$old,'new'=>$new];}
echo json_encode(['success'=>$pass,'cases'=>$rows]),"\n";exit($pass?0:1);
}
