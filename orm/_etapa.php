<?php
namespace gamboamartin\facturacion\models;

use base\orm\_modelo_parent_sin_codigo;
use gamboamartin\errores\errores;
use gamboamartin\proceso\models\pr_etapa_proceso;

use stdClass;

class _etapa extends _modelo_parent_sin_codigo{

    protected _transacciones_fc $modelo_entidad;

    public function alta_bd(array $keys_integra_ds = array('descripcion','pr_etapa_proceso_id', 'fecha')): array|stdClass
    {
        $pr_etapa_proceso = (new pr_etapa_proceso(link: $this->link))->registro(registro_id: $this->registro['pr_etapa_proceso_id'], retorno_obj: true);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener pr_etapa_proceso',data:  $pr_etapa_proceso);
        }
        $row_entidad = $this->modelo_entidad->registro(registro_id: $this->registro[$this->modelo_entidad->key_id], retorno_obj: true);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener row_entidad',data:  $row_entidad);
        }

        $key_folio = $this->modelo_entidad->tabla.'_folio';

        if(!isset($this->registro['descripcion'])){
            $descripcion = $pr_etapa_proceso->pr_proceso_descripcion.' '.$pr_etapa_proceso->pr_etapa_descripcion.' '.
                $row_entidad->$key_folio.' '.$this->registro['fecha'];

            $this->registro['descripcion'] = $descripcion;
        }

        $r_alta_bd = parent::alta_bd(keys_integra_ds: $keys_integra_ds); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar',data:  $r_alta_bd);
        }
        return $r_alta_bd;
    }

}