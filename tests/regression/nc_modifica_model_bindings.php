<?php
/** Pure method contract: original method body plus dependency doubles; no app/DB. */
namespace gamboamartin\errores { class errores { public static bool $error=false; } }
namespace gamboamartin\facturacion\models {
    class BindingDouble { public object $link; public function __construct(object $link){$this->link=$link;} }
    class fc_nota_credito extends BindingDouble {}
    class fc_traslado_nc extends BindingDouble {}
    class fc_retenido_nc extends BindingDouble {}
    class fc_nota_credito_etapa extends BindingDouble {}
    class ErrorDouble { public function error(string $mensaje,mixed $data):array{return ['mensaje'=>$mensaje,'data'=>$data];} }
    class ParentDouble {
        public object $link;
        public ErrorDouble $error;
        public fc_nota_credito $modelo_entidad;
        public fc_traslado_nc $modelo_traslado;
        public fc_retenido_nc $modelo_retencion;
        public fc_nota_credito_etapa $modelo_etapa;
        public array $received=[];
        public bool $returnError=false;
        public function __construct(){ $this->link=new \stdClass();$this->error=new ErrorDouble(); }
        public function modifica_bd(array $registro,int $id,bool $reactiva=false,array $keys_integra_ds=['codigo','descripcion']):array|\stdClass {
            foreach (['modelo_entidad','modelo_traslado','modelo_retencion','modelo_etapa'] as $property) {
                if($this->$property->link!==$this->link)throw new \RuntimeException('Connection binding changed');
            }
            $this->received=[$registro,$id,$reactiva,$keys_integra_ds];
            \gamboamartin\errores\errores::$error=$this->returnError;
            return (object)['parent_result'=>true];
        }
    }
}
namespace {
    function check(bool $ok,string $message):void {if(!$ok)throw new \RuntimeException($message);}
    $source=file_get_contents(__DIR__.'/../../orm/fc_partida_nc.php');
    check(preg_match('/    public function modifica_bd\(.*?\n    }/s',$source,$match)===1,'Method extraction');
    $method=$match[0];
    $prefix='namespace gamboamartin\\facturacion\\models; use gamboamartin\\errores\\errores; use stdClass;';
    eval($prefix.'class CandidateMethod extends ParentDouble {'.$method.'}');
    $original=$method;
    foreach(['modelo_traslado'=>'fc_traslado_nc','modelo_retencion'=>'fc_retenido_nc','modelo_etapa'=>'fc_nota_credito_etapa'] as $property=>$class){
        $line='        $this->'.$property.' = new '.$class.'(link: $this->link);'."\n";
        check(substr_count($method,$line)===1,'One explicit dependency: '.$property);
        $original=str_replace($line,'',$original);
    }
    eval($prefix.'class OriginalMethod extends ParentDouble {'.$original.'}');
    $failed=false;
    try{(new \gamboamartin\facturacion\models\OriginalMethod())->modifica_bd(['cantidad'=>2],7);}
    catch(\Error $error){$failed=str_contains($error->getMessage(),'modelo_traslado')&&str_contains($error->getMessage(),'must not be accessed before initialization');}
    check($failed,'Original must reproduce uninitialized typed property');
    $candidate=new \gamboamartin\facturacion\models\CandidateMethod();
    $result=$candidate->modifica_bd(['cantidad'=>2],7,true,['descripcion']);
    check($result instanceof \stdClass&&$result->parent_result===true,'Parent return preserved');
    check($candidate->received===[['cantidad'=>2],7,true,['descripcion']],'Four arguments preserved');
    $old=$candidate->modelo_traslado;$candidate->modifica_bd(['cantidad'=>3],8);
    check($candidate->modelo_traslado!==$old&&$candidate->received===[['cantidad'=>3],8,false,['codigo','descripcion']],'Repeat call and defaults');
    $candidate->returnError=true;$result=$candidate->modifica_bd(['cantidad'=>4],9);
    check(is_array($result)&&$result['mensaje']==='Error al modificar'&&$result['data']->parent_result===true,'Existing error propagation');
    echo "PASS: original typed-property failure; four NC bindings/link identity; arguments/defaults; repeated call; error propagation. Pure doubles, no DB.\n";
}
