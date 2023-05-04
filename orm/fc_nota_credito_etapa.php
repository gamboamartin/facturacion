<?php

namespace gamboamartin\facturacion\models;

use base\orm\_modelo_parent_sin_codigo;
use gamboamartin\errores\errores;
use gamboamartin\proceso\models\pr_etapa_proceso;
use PDO;
use stdClass;


class fc_nota_credito_etapa extends _modelo_parent_sin_codigo
{
    public function __construct(PDO $link)
    {
        $tabla = 'fc_nota_credito_etapa';
        $columnas = array($tabla => false, 'fc_nota_credito' => $tabla, 'pr_etapa_proceso' => $tabla,
            'pr_etapa' => 'pr_etapa_proceso', 'pr_proceso' => 'pr_etapa_proceso', 'pr_tipo_proceso' => 'pr_proceso');
        $campos_obligatorios = array('fc_nota_credito_id', 'pr_etapa_proceso_id');

        $columnas_extra = array();



        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas,  columnas_extra: $columnas_extra);

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'Nota Credito Etapa';
    }

    public function alta_bd(array $keys_integra_ds = array('descripcion','pr_etapa_proceso_id','fc_nota_credito_id', 'fecha')): array|stdClass
    {
        $pr_etapa_proceso = (new pr_etapa_proceso(link: $this->link))->registro(registro_id: $this->registro['pr_etapa_proceso_id'], retorno_obj: true);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener pr_etapa_proceso',data:  $pr_etapa_proceso);
        }
        $fc_nota_credito = (new fc_nota_credito(link: $this->link))->registro(registro_id: $this->registro['fc_nota_credito_id'], retorno_obj: true);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener fc_nota_credito',data:  $fc_nota_credito);
        }

        if(!isset($this->registro['descripcion'])){
            $descripcion = $pr_etapa_proceso->pr_proceso_descripcion.' '.$pr_etapa_proceso->pr_etapa_descripcion.' '.
                $fc_nota_credito->fc_nota_credito_folio.' '.$this->registro['fecha'];

            $this->registro['descripcion'] = $descripcion;
        }

        $r_alta_bd = parent::alta_bd(keys_integra_ds: $keys_integra_ds); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar',data:  $r_alta_bd);
        }
        return $r_alta_bd;
    }


}