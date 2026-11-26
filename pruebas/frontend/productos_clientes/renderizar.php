<?php
declare(strict_types=1);
namespace config {
 final class views {public string $ruta_templates;public function __construct(){ $this->ruta_templates=$GLOBALS['qa_templates']; }}
}
namespace {
 $root=$argv[1];$accion=$argv[2];$seccion=$argv[3]??'com_producto';$GLOBALS['qa_templates']=$argv[4].'/';
 if(!in_array($accion,['alta','modifica'],true)||!in_array($seccion,['com_producto','com_tipo_producto'],true))exit(1);
 $esc=static fn(string $v):string=>htmlspecialchars($v,ENT_QUOTES,'UTF-8');
 $field=static function(string $name,bool $disabled=false,bool $select=false)use($esc):string{
  $id=$name;$attr=$disabled?' disabled':'';$value='QA '.$name;
  if($select){$control='<select name="'.$esc($name).'" id="'.$esc($id).'" required><option value="7" selected>Opción QA</option></select>';}
  else{$control='<input name="'.$esc($name).'" id="'.$esc($id).'" value="'.$esc($value).'" required'.$attr.'>';}
  return '<div class="control-group"><label for="'.$esc($id).'">'.$esc($name).'</label>'.$control.'</div>';
 };
 $controlador=(object)['accion'=>$accion,'seccion'=>$seccion,'inputs'=>new \stdClass(),'registro'=>['cat_sat_cve_prod_descripcion_select'=>'QA <img src=x onerror=alert(1)> & servicio'],'acciones_visibles_permitidas'=>[]];
 foreach(['alta','lista','get_data'] as $a){$controlador->acciones_visibles_permitidas[]=(object)['adm_accion_descripcion'=>$a,'boton'=>'<a href="index.php?seccion='.$seccion.'&amp;accion='.$a.'">'.$a.'</a>'];}
 foreach(['com_tipo_producto_id','cat_sat_unidad_id','cat_sat_obj_imp_id'] as $n)$controlador->inputs->$n=$field($n,false,true);
 foreach(['codigo','precio','descripcion'] as $n)$controlador->inputs->$n=$field($n,$seccion==='com_tipo_producto'&&$n==='codigo');
 $controlador->inputs->cat_sat_producto=$field($accion==='alta'?'cat_sat_producto':'codigo_sat',$accion!=='alta');
 $controlador->link_com_producto_alta_bd='index.php?seccion=com_producto&accion=alta_bd&session_id=QA-TOKEN';$controlador->link_com_producto_modifica_bd='index.php?seccion=com_producto&accion=modifica_bd&registro_id=77&session_id=QA-TOKEN';
 $controlador->link_alta_bd='index.php?seccion=com_tipo_producto&accion=alta_bd&session_id=QA-TOKEN';$controlador->link_modifica_bd='index.php?seccion=com_tipo_producto&accion=modifica_bd&registro_id=88&session_id=QA-TOKEN';
 include $root.'/views/'.$seccion.'/'.$accion.'.php';
}
