<?php
declare(strict_types=1);
namespace gamboamartin\errores {
 class errores {public static bool $error=false;public function error(string $mensaje,mixed $data):array {return ['mensaje'=>$mensaje,'data'=>$data];}}
}
namespace gamboamartin\facturacion\models {
 class _transacciones_fc {
  public array $campos_tabla=[], $columnas=['com_tipo_producto'=>['id']],$registro=[],$captura=[];
  public string $NAMESPACE='', $etiqueta='', $key_fc_id=''; public \PDO $link;
  public mixed $modelo_email=null,$modelo_etapa=null,$modelo_partida=null,$modelo_documento=null,$modelo_sello=null,$modelo_relacionada=null,$modelo_notificacion=null,$modelo_relacion=null,$modelo_uuid_ext=null;
  public \gamboamartin\errores\errores $error;
  public array|\stdClass $respuesta;public bool $fallar=false;
  public function __construct(\PDO $link,string $tabla,array $columnas_extra,bool $valida_atributos_criticos){$this->link=$link;$this->captura=compact('valida_atributos_criticos');$this->error=new \gamboamartin\errores\errores();$this->respuesta=(object)['id'=>501];}
  protected function resultado():array|\stdClass {\gamboamartin\errores\errores::$error=$this->fallar;return $this->respuesta;}
  public function alta_bd():array|\stdClass {$this->captura=['registro'=>$this->registro];return $this->resultado();}
  public function modifica_bd(array $registro,int $id,bool $reactiva=false,bool $verifica_permite_transaccion=true):array|\stdClass {$this->captura=compact('registro','id','reactiva','verifica_permite_transaccion');return $this->resultado();}
  public function elimina_bd(int $id):array|\stdClass {$this->captura=compact('id');return $this->resultado();}
 }
 class ColaboradorNc {public function __construct(public \PDO $link){}}
 class fc_email_nc extends ColaboradorNc{} class fc_nota_credito_etapa extends ColaboradorNc{}
 class fc_partida_nc extends ColaboradorNc{} class fc_nota_credito_documento extends ColaboradorNc{}
 class fc_cfdi_sellado_nc extends ColaboradorNc{} class fc_nota_credito_relacionada extends ColaboradorNc{}
 class fc_notificacion_nc extends ColaboradorNc{} class fc_relacion_nc extends ColaboradorNc{} class fc_uuid_nc extends ColaboradorNc{}
}
namespace gamboamartin\facturacion\controllers {
 class _base_system_fc {
  public mixed $modelo=null,$modelo_entidad=null;public array $captura=[];public bool $fallar=false;
  public array|string $respuesta=['fallo_ficticio'=>'vista'];
  public function alta(bool $header,bool $ws=false):array|string {\gamboamartin\errores\errores::$error=$this->fallar;return $this->respuesta;}
  public function retorno_error(string $mensaje,mixed $data,bool $header,bool $ws):array {$this->captura=compact('mensaje','data','header','ws');return $this->captura;}
 }
}
