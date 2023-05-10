<?php
namespace gamboamartin\facturacion\models;

use base\orm\_modelo_parent_sin_codigo;
use gamboamartin\errores\errores;

use gamboamartin\notificaciones\models\not_mensaje;
use stdClass;

class _notificacion extends _modelo_parent_sin_codigo{

    protected _transacciones_fc $modelo_entidad;

    public function alta_bd(array $keys_integra_ds = array('descripcion')): array|stdClass
    {
        $key_entidad_id = $this->modelo_entidad->key_id;
        $keys = array($key_entidad_id,'not_mensaje_id');
        $valida = $this->validacion->valida_ids(keys: $keys,registro:  $this->registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar registro',data:  $valida);
        }

        $row_entidad = $this->modelo_entidad->registro(registro_id: $this->registro[$key_entidad_id]);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener row_entidad',data:  $row_entidad);
        }
        $not_mensaje = (new not_mensaje(link: $this->link))->registro(registro_id: $this->registro['not_mensaje_id']);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener not_mensaje_id',data:  $not_mensaje);
        }


        $key_folio = $this->modelo_entidad->tabla.'_folio';
        if(!isset($this->registro['descripcion'])){
            $descripcion = $row_entidad[$key_folio].' '.$not_mensaje['not_mensaje_id'];
            $this->registro['descripcion'] = $descripcion;
        }
        $r_alta_bd = parent::alta_bd(keys_integra_ds: $keys_integra_ds); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar',data:  $r_alta_bd);
        }
        return $r_alta_bd;

    }
}