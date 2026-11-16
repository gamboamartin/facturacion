<?php
declare(strict_types=1);
/* Pruebas unitarias: implementación NC real; colaboradores/herencia simulados.
 * Sin autoload, conexiones, SQL, red, timbrado ni datos de producción. */
namespace gamboamartin\errores { class errores { public static bool $error=false; } }
namespace gamboamartin\facturacion\models {
 class _transacciones_fc {
  public static array $campos_fixture=[];
  public array $campos_tabla=[], $columnas=['com_tipo_producto'=>['id']], $registro=[], $captura=[];
  public \PDO $link; public string $NAMESPACE='', $etiqueta='', $key_fc_id='';
  public mixed $modelo_email=null,$modelo_etapa=null;
  public \stdClass $respuesta;
  public function __construct(\PDO $link,string $tabla,array $columnas_extra,bool $valida_atributos_criticos) {
   $this->link=$link;$this->campos_tabla=self::$campos_fixture;
   $this->captura=compact('tabla','columnas_extra','valida_atributos_criticos');
   $this->respuesta=(object)['id'=>501,'mensaje'=>'Éxito ficticio'];
  }
  public function alta_bd():array|\stdClass {$this->captura=['registro'=>$this->registro];return $this->respuesta;}
  public function modifica_bd(array $registro,int $id,bool $reactiva=false,bool $verifica_permite_transaccion=true):array|\stdClass {
   $this->captura=compact('registro','id','reactiva','verifica_permite_transaccion');return $this->respuesta;
  }
 }
 class fc_email_nc {public function __construct(public \PDO $link){}}
 class fc_nota_credito_etapa {public function __construct(public \PDO $link){}}
}
namespace gamboamartin\facturacion\controllers {
 class _base_system_fc {
  public mixed $modelo=null,$modelo_entidad=null;public array $captura=[];
  public array|string $respuesta=['formulario'=>'nota_credito_ficticia'];
  public function alta(bool $header,bool $ws=false):array|string {$this->captura=compact('header','ws');return $this->respuesta;}
 }
}
