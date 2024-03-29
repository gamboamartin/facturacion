<?php

namespace gamboamartin\facturacion\models;

use gamboamartin\errores\errores;
use PDO;
use stdClass;



class fc_complemento_pago_etapa extends _etapa
{
    public function __construct(PDO $link)
    {
        $tabla = 'fc_complemento_pago_etapa';
        $columnas = array($tabla => false, 'fc_complemento_pago' => $tabla, 'pr_etapa_proceso' => $tabla,
            'pr_etapa' => 'pr_etapa_proceso', 'pr_proceso' => 'pr_etapa_proceso', 'pr_tipo_proceso' => 'pr_proceso');
        $campos_obligatorios = array('fc_complemento_pago_id', 'pr_etapa_proceso_id');

        $columnas_extra = array();



        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas,  columnas_extra: $columnas_extra);

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'Complemento Pago Etapa';
    }

    public function alta_bd(array $keys_integra_ds = array('descripcion', 'pr_etapa_proceso_id', 'fc_complemento_pago_id', 'fecha')): array|stdClass
    {
        $this->modelo_entidad = new fc_complemento_pago(link: $this->link);

        $r_alta_bd = parent::alta_bd($keys_integra_ds); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar',data:  $r_alta_bd);
        }
        return $r_alta_bd;

    }




}