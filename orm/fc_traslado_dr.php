<?php

namespace gamboamartin\facturacion\models;

use base\orm\_modelo_parent;
use gamboamartin\errores\errores;
use PDO;
use stdClass;


class fc_traslado_dr extends _modelo_parent{
    public function __construct(PDO $link)
    {
        $tabla = 'fc_traslado_dr';
        $columnas = array($tabla=>false,'fc_impuesto_dr'=>$tabla,
            'fc_docto_relacionado'=>'fc_impuesto_dr','fc_factura'=>'fc_docto_relacionado');
        $campos_obligatorios = array();


        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas);

        $this->NAMESPACE = __NAMESPACE__;
        $this->etiqueta = 'Traslado Dr';
    }

    public function alta_bd(array $keys_integra_ds = array('descripcion')): array|stdClass
    {

        if(!isset($this->registro['codigo'])){
            $codigo = $this->registro['fc_impuesto_dr_id'];
            $codigo .= time();
            $codigo .= mt_rand(1000,9999);
            $this->registro['codigo'] = $codigo;
        }

        if(!isset($this->registro['descripcion'])){
            $descripcion = $this->registro['codigo'];
            $this->registro['descripcion'] = $descripcion;
        }

        $r_alta_bd = parent::alta_bd(keys_integra_ds: $keys_integra_ds); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar',data:  $r_alta_bd);
        }

        $fc_traslado_dr = $this->registro(registro_id: $r_alta_bd->registro_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener traslado',data:  $fc_traslado_dr);
        }


        $modelo_traslado = new fc_traslado(link: $this->link);
        $modelo_retencion = new fc_retenido(link: $this->link);

        $traslados = (new fc_factura(link: $this->link))->traslados(modelo_traslado: $modelo_traslado,
            registro_id:  $fc_traslado_dr['fc_factura_id']);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener traslados',data:  $traslados);
        }


        foreach ($traslados as $traslado){
            $fc_traslado_dr_part_ins['cat_sat_tipo_impuesto_id'] = $traslado['cat_sat_tipo_impuesto_id'];
            $fc_traslado_dr_part_ins['cat_sat_tipo_factor_id'] = $traslado['cat_sat_tipo_factor_id'];
            $fc_traslado_dr_part_ins['fc_traslado_dr_id'] = $r_alta_bd->registro_id;
            $fc_traslado_dr_part_ins['cat_sat_factor_id'] = $traslado['cat_sat_factor_id'];

            $importe_pagado = round($fc_traslado_dr['fc_docto_relacionado_imp_pagado'],2);

            $tasas = (new fc_factura(link: $this->link))->tasas_de_impuestos(modelo_traslado: $modelo_traslado,
                modelo_retencion:  $modelo_retencion,registro_id:  $fc_traslado_dr['fc_factura_id']);

            if(errores::$error){
                return $this->error->error(mensaje: 'Error al obtener tasas',data:  $tasas);
            }

            $factor = 1 + round(($tasas->traslado->factor - $tasas->retencion->factor),6);
            $base_dr = round($importe_pagado / $factor,2);

            $importe_dr = round($base_dr * $tasas->traslado->factor,2);

            $fc_traslado_dr_part_ins['base_dr'] = $base_dr;
            $fc_traslado_dr_part_ins['importe_dr'] = $importe_dr;

            $r_alta_fc_traslado_dr_part = (new fc_traslado_dr_part(link: $this->link))->alta_registro(registro: $fc_traslado_dr_part_ins);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al insertar',data:  $r_alta_fc_traslado_dr_part);
            }






        }


        /**
         * insertar fc_traslado_part
         */


        return $r_alta_bd;
    }

    public function elimina_bd(int $id): array|stdClass
    {

        $filtro['fc_traslado_dr.id'] = $id;

        $del_fc_traslado_dr_part = (new fc_traslado_dr_part(link: $this->link))->elimina_con_filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al eliminar del_fc_traslado_dr_part',data:  $del_fc_traslado_dr_part);
        }

        $r_elimina_bd =  parent::elimina_bd($id); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al eliminar',data:  $r_elimina_bd);
        }
        return $r_elimina_bd;

    }

    public function modifica_bd(array $registro, int $id, bool $reactiva = false, array $keys_integra_ds = array('codigo', 'descripcion')): array|stdClass
    {

        $registro_previo = $this->registro(registro_id: $id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener registro_previo',data:  $registro_previo);
        }


        if(!isset($registro['descripcion'])){

            $codigo = $registro_previo['fc_impuesto_p_codigo'];
            if(isset($registro['codigo'])){
                $codigo = $registro['codigo'];
            }

            $descripcion = $codigo;
            $registro['descripcion'] = $descripcion;
        }
        $r_modifica_bd  = parent::modifica_bd(registro: $registro,id:  $id,reactiva:  $reactiva,
            keys_integra_ds:  $keys_integra_ds); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al modificar',data:  $r_modifica_bd);
        }
        return $r_modifica_bd;
    }

}