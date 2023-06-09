<?php

namespace gamboamartin\facturacion\models;

use base\orm\_modelo_parent;
use gamboamartin\errores\errores;

use stdClass;


class _imp_dr extends _modelo_parent{

    protected _dr_part $modelo_dr_part;

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

        $fc_row_dr = $this->registro(registro_id: $r_alta_bd->registro_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener fc_row_dr',data:  $fc_row_dr);
        }

        $modelo_traslado = new fc_traslado(link: $this->link);
        $modelo_retencion = new fc_retenido(link: $this->link);

        $fc_rows = array();

        if($this->tabla === 'fc_traslado_dr') {
            $fc_rows = (new fc_factura(link: $this->link))->traslados(modelo_traslado: $modelo_traslado,
                registro_id: $fc_row_dr['fc_factura_id']);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al obtener fc_rows', data: $fc_rows);
            }
        }
        if($this->tabla === 'fc_retencion_dr') {
            $fc_rows = (new fc_factura(link: $this->link))->retenciones(modelo_retencion: $modelo_retencion,
                registro_id: $fc_row_dr['fc_factura_id']);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al obtener fc_rows', data: $fc_rows);
            }
        }

        foreach ($fc_rows as $fc_row){

            $r_alta_fc_row_dr_part = $this->inserta_fc_row_dr_part(fc_row: $fc_row,
                fc_row_dr: $fc_row_dr, modelo_dr_part: $this->modelo_dr_part,
                modelo_retencion:  $modelo_retencion, modelo_traslado: $modelo_traslado);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al insertar',data:  $r_alta_fc_row_dr_part);
            }

        }


        return $r_alta_bd;
    }

    public function elimina_bd(int $id): array|stdClass
    {

        $filtro[$this->key_filtro_id] = $id;

        $del_fc_traslado_dr_part = $this->modelo_dr_part->elimina_con_filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al eliminar del_fc_traslado_dr_part',data:  $del_fc_traslado_dr_part);
        }

        $r_elimina_bd =  parent::elimina_bd($id); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al eliminar',data:  $r_elimina_bd);
        }
        return $r_elimina_bd;

    }

    private function data_importes(float $factor_calculo, array $fc_row, array $fc_row_dr): stdClass
    {
        $importe_pagado = round($fc_row_dr['fc_docto_relacionado_imp_pagado'],2);
        $factor_calculo = 1 + round(($factor_calculo),6);
        $base_dr = round($importe_pagado / $factor_calculo,2);
        $importe_dr = round($base_dr * $fc_row['cat_sat_factor_factor'],2);

        $data = new stdClass();
        $data->importe_pagado = $importe_pagado;
        $data->factor_calculo = $factor_calculo;
        $data->base_dr = $base_dr;
        $data->importe_dr = $importe_dr;

        return $data;

    }

    private function fc_row_dr_part_alta(array $fc_row, array $fc_row_dr, _data_impuestos $modelo_retencion,
                                         _data_impuestos $modelo_traslado){


        $fc_traslado_dr_part_ins['cat_sat_tipo_impuesto_id'] = $fc_row['cat_sat_tipo_impuesto_id'];
        $fc_traslado_dr_part_ins['cat_sat_tipo_factor_id'] = $fc_row['cat_sat_tipo_factor_id'];
        $fc_traslado_dr_part_ins[$this->key_id] = $fc_row_dr[$this->key_id];
        $fc_traslado_dr_part_ins['cat_sat_factor_id'] = $fc_row['cat_sat_factor_id'];


        $data_importes = $this->genera_data_importes(fc_factura_id: $fc_row_dr['fc_factura_id'],
            modelo_traslado: $modelo_traslado, modelo_retencion: $modelo_retencion, fc_row: $fc_row, fc_row_dr: $fc_row_dr);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener data_importes',data:  $data_importes);
        }

        $fc_traslado_dr_part_ins['base_dr'] = $data_importes->base_dr;
        $fc_traslado_dr_part_ins['importe_dr'] = $data_importes->importe_dr;

        return $fc_traslado_dr_part_ins;
    }


    private function genera_data_importes(int $fc_factura_id, _data_impuestos $modelo_traslado,
                                          _data_impuestos $modelo_retencion, array $fc_row, array $fc_row_dr){

        $tasas = (new fc_factura(link: $this->link))->tasas_de_impuestos(modelo_traslado: $modelo_traslado,
            modelo_retencion:  $modelo_retencion,registro_id:  $fc_factura_id);

        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener tasas',data:  $tasas);
        }

        $data_importes = $this->data_importes(factor_calculo: $tasas->factor_calculo, fc_row: $fc_row, fc_row_dr: $fc_row_dr);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener data_importes',data:  $data_importes);
        }

        return $data_importes;
    }

    private function inserta_fc_row_dr_part(array $fc_row, array $fc_row_dr,_dr_part $modelo_dr_part, _data_impuestos $modelo_retencion,
                                            _data_impuestos $modelo_traslado){
        $fc_row_dr_part_ins = $this->fc_row_dr_part_alta(fc_row: $fc_row, fc_row_dr: $fc_row_dr,
            modelo_retencion:  $modelo_retencion, modelo_traslado: $modelo_traslado);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener fc_row_dr_part_ins',data:  $fc_row_dr_part_ins);
        }

        $r_alta_fc_row_dr_part = $modelo_dr_part->alta_registro(registro: $fc_row_dr_part_ins);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar',data:  $r_alta_fc_row_dr_part);
        }
        return $r_alta_fc_row_dr_part;
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