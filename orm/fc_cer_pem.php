<?php
namespace gamboamartin\facturacion\models;
use base\orm\modelo;
use gamboamartin\documento\models\doc_documento;
use gamboamartin\errores\errores;
use PDO;
use stdClass;


class fc_cer_pem extends modelo{
    public function __construct(PDO $link){
        $tabla = 'fc_cer_pem';
        $columnas = array($tabla=>false,'fc_cer_csd'=>$tabla,'doc_documento'=>$tabla,'fc_csd'=>'fc_cer_csd');
        $campos_obligatorios = array('fc_cer_csd_id');

        $campos_view['fc_cer_csd_id'] = array('type' => 'selects', 'model' => new fc_cer_csd($link));
        $campos_view['doc_documento_id'] = array('type' => 'selects', 'model' => new doc_documento($link));
        $campos_view['documento'] = array('type' => 'files');
        $campos_view['codigo'] = array('type' => 'inputs');

        $no_duplicados = array('codigo','descripcion_select','alias','codigo_bis');

        parent::__construct(link: $link,tabla:  $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas, campos_view: $campos_view, no_duplicados: $no_duplicados,tipo_campos: array());

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'Cert PEM CSD';
    }

    public function alta_bd(): array|stdClass
    {

        $registro = (new _cert())->init_alta_pem(key_val_id: 'fc_cer_csd_id', modelo: $this);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al inicializar campos ',data: $registro);
        }

        $r_alta_bd = parent::alta_bd();
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al dar de alta key csd',data: $r_alta_bd);
        }
        $inserta_etapa = (new _cert())->inserta_etapas($r_alta_bd->registro['fc_csd_id'],link: $this->link,
            pr_etapa_descripcion:  'CER PEM INTEGRADO');
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar etapa',data: $inserta_etapa);
        }

        return $r_alta_bd;
    }


    public function get_key_csd(int $fc_cer_csd_id): array|stdClass|int
    {
        $registro = $this->registro(registro_id: $fc_cer_csd_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener Cer CSD',data:  $registro);
        }

        return $registro;
    }



    public function modifica_bd(array $registro, int $id, bool $reactiva = false): array|stdClass
    {
        $validacion = (new _cert())->validaciones(data: $registro,key_id: 'fc_cer_csd_id');
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar datos',data: $validacion);
        }

        $registro = (new _cert())->init_campos_base(data: $registro,link: $this->link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al inicializar campos base',data: $registro);
        }

        $r_modifica_bd = parent::modifica_bd($registro, $id, $reactiva);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al modificar csd',data: $r_modifica_bd);
        }

        return $r_modifica_bd;
    }

}