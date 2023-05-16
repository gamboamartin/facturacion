<?php
namespace gamboamartin\facturacion\models;


use base\orm\_modelo_parent;
use gamboamartin\errores\errores;
use stdClass;

class _uuid_ext extends _modelo_parent{

    protected _relacion $modelo_relacion;

    public function alta_bd(array $keys_integra_ds = array('codigo', 'descripcion')): array|stdClass
    {
        $fc_uuid = (new fc_uuid(link: $this->link))->registro(registro_id: $this->registro['fc_uuid_id'], retorno_obj: true);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener fc_uuid',data:  $fc_uuid);
        }
        $fc_relacion = $this->modelo_relacion->registro(registro_id: $this->registro[$this->modelo_relacion->key_id], retorno_obj: true);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener fc_uuid',data:  $fc_relacion);
        }

        $key_relacion_descripcion = $this->modelo_relacion->tabla.'_descripcion';
        if(!isset($this->registro['descripcion'])){

            $descripcion = $fc_uuid->fc_uuid_descripcion;
            $descripcion .= ' '.$fc_relacion->$key_relacion_descripcion;
            $this->registro['descripcion'] = $descripcion;
        }

        $r_alta_bd = parent::alta_bd(keys_integra_ds: $keys_integra_ds); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar',data:  $r_alta_bd);
        }
        return $r_alta_bd;
    }

}
