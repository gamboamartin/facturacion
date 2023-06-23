<?php

namespace gamboamartin\facturacion\models;

use base\orm\_modelo_parent;
use gamboamartin\errores\errores;
use stdClass;


class _p extends _modelo_parent{

    protected _modelo_parent $modelo_dr_part;
    protected _p_part $modelo_p_part;


    public function alta_bd(array $keys_integra_ds = array('descripcion')): array|stdClass
    {

        $keys = array('fc_impuesto_p_id');
        $valida = $this->validacion->valida_ids(keys: $keys,registro:  $this->registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar registro',data:  $valida);
        }

        if(!isset($this->registro['codigo'])){
            $codigo = $this->registro['fc_impuesto_p_id'];
            $codigo .= time();
            $codigo .= mt_rand(1000,9999);
            $this->registro['codigo'] = $codigo;
        }

        if(!isset($this->registro['descripcion'])){
            $descripcion = $this->registro['codigo'];
            $this->registro['descripcion'] = $descripcion;
        }

        $filtro['fc_impuesto_p.id'] = $this->registro['fc_impuesto_p_id'];

        $r_alta_bd = new stdClass();
        $existe = $this->existe(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al verificar si existe',data:  $existe);
        }

        if($existe){
            $r_p = $this->filtro_and(filtro: $filtro);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al obtener registro',data:  $r_p);
            }
            $r_alta_bd->registro_id = $r_p->registros[0][$this->key_id];
            $r_alta_bd->registro = $r_p->registros[0];

        }
        else {

            $r_alta_bd = parent::alta_bd(keys_integra_ds: $keys_integra_ds); // TODO: Change the autogenerated stub
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al insertar', data: $r_alta_bd);
            }

        }

        $r_alta_fc_impuesto_p_part = $this->inserta_p_parts(fc_pago_pago_id: $r_alta_bd->registro['fc_pago_pago_id'],
            row_p_part_id:  $r_alta_bd->registro_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar r_alta_fc_impuesto_p_part',data:  $r_alta_fc_impuesto_p_part);
        }


        return $r_alta_bd;
    }

    private function datas_ins_p_part(int $fc_pago_pago_id, int $row_p_part_id){
        $fc_impuesto_dr_parts = $this->fc_impuesto_dr_p_part(fc_pago_pago_id: $fc_pago_pago_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener r_fc_impuesto_dr_part',data:  $fc_impuesto_dr_parts);
        }

        $datas_ins_p_part = array();
        foreach ($fc_impuesto_dr_parts as $fc_impuesto_dr_part) {
            $cat_sat_tipo_impuesto_id = $fc_impuesto_dr_part['cat_sat_tipo_impuesto_id'];
            $datas_ins_p_part = $this->integra_datas_init_p_part(cat_sat_tipo_impuesto_id: $cat_sat_tipo_impuesto_id,
                datas_ins_p_part:  $datas_ins_p_part,fc_impuesto_dr_part:  $fc_impuesto_dr_part,
                row_p_part_id: $row_p_part_id);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al obtener inicializar',data:  $datas_ins_p_part);
            }
        }
        return $datas_ins_p_part;
    }

    /**
     * Inicializa los importes de un registro de tipo p part
     * @param int $cat_sat_tipo_impuesto_id Tipo impuesto id
     * @param array $datas_ins_p_part Datos previos de un impuesto
     * @return array
     */
    private function datas_ins_p_part_init(int $cat_sat_tipo_impuesto_id, array $datas_ins_p_part): array
    {
        if(!isset($datas_ins_p_part[$cat_sat_tipo_impuesto_id]['base_p'])){
            $datas_ins_p_part[$cat_sat_tipo_impuesto_id]['base_p'] = 0.0;
        }
        if(!isset($datas_ins_p_part[$cat_sat_tipo_impuesto_id]['importe_p'])){
            $datas_ins_p_part[$cat_sat_tipo_impuesto_id]['importe_p'] = 0.0;
        }
        return $datas_ins_p_part;
    }


    /**
     * Elimina un registro de tipo p_part de complemento de pago previa a la eliminacion de tipo this
     * @param int $id Identificador de modelo impuesto p de complemento de pago
     * @return array|stdClass
     */
    public function elimina_bd(int $id): array|stdClass
    {
        $filtro[$this->key_filtro_id] = $id;
        $del = $this->modelo_p_part->elimina_con_filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al eliminar del',data:  $del);
        }
        $r_del = parent::elimina_bd(id: $id); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al eliminar del',data:  $r_del);
        }
        return $r_del;

    }

    /**
     * Obtiene los impuestos dr
     * @param int $fc_pago_pago_id Pago a buscar
     * @return array
     */
    private function fc_impuesto_dr_p_part(int $fc_pago_pago_id): array
    {
        $filtro['fc_pago_pago.id'] = $fc_pago_pago_id;
        $r_fc_impuesto_dr_part = $this->modelo_dr_part->filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener r_fc_impuesto_dr_part',data:  $r_fc_impuesto_dr_part);
        }
        return $r_fc_impuesto_dr_part->registros;
    }

    private function inserta_p_parts(int $fc_pago_pago_id, int $row_p_part_id){
        $r_altas_fc_impuesto_p_part = array();
        $datas_ins_p_part = $this->datas_ins_p_part(fc_pago_pago_id: $fc_pago_pago_id, row_p_part_id: $row_p_part_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener inicializar',data:  $datas_ins_p_part);
        }

        $fc_pago_pago = (new fc_pago_pago(link: $this->link))->registro(registro_id: $fc_pago_pago_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener fc_pago_pago',data:  $fc_pago_pago);
        }

        foreach ($datas_ins_p_part as $data_ins_p_part){
            $data_ins_p_part['base_p'] = round($data_ins_p_part['base_p'] / $fc_pago_pago['com_tipo_cambio_monto'],2);
            $data_ins_p_part['importe_p'] = round($data_ins_p_part['importe_p'] / $fc_pago_pago['com_tipo_cambio_monto'],2);

            $r_alta_fc_impuesto_p_part = $this->modelo_p_part->alta_registro(registro: $data_ins_p_part);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al insertar registro', data: $r_alta_fc_impuesto_p_part);
            }
            $r_altas_fc_impuesto_p_part[] = $r_alta_fc_impuesto_p_part;
        }
        return $r_altas_fc_impuesto_p_part;
    }

    private function integra_datas_init_p_part(int $cat_sat_tipo_impuesto_id, array $datas_ins_p_part,
                                               array $fc_impuesto_dr_part, int $row_p_part_id){


        $keys_dr = $this->keys_dr(tabla: $this->tabla);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener keys_dr',data:  $keys_dr);
        }


        $datas_ins_p_part = $this->datas_ins_p_part_init(cat_sat_tipo_impuesto_id: $cat_sat_tipo_impuesto_id,
            datas_ins_p_part:  $datas_ins_p_part);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener inicializar',data:  $datas_ins_p_part);
        }

        $datas_ins_p_part[$cat_sat_tipo_impuesto_id]['base_p'] += $fc_impuesto_dr_part[$keys_dr->key_dr_part_base_dr];
        $datas_ins_p_part[$cat_sat_tipo_impuesto_id]['importe_p'] += $fc_impuesto_dr_part[$keys_dr->key_dr_part_importe_dr];
        $datas_ins_p_part[$cat_sat_tipo_impuesto_id]['cat_sat_tipo_impuesto_id'] = $cat_sat_tipo_impuesto_id;
        $datas_ins_p_part[$cat_sat_tipo_impuesto_id]['cat_sat_tipo_factor_id'] = $fc_impuesto_dr_part['cat_sat_tipo_factor_id'];
        $datas_ins_p_part[$cat_sat_tipo_impuesto_id]['cat_sat_factor_id'] = $fc_impuesto_dr_part['cat_sat_factor_id'];
        $datas_ins_p_part[$cat_sat_tipo_impuesto_id][$this->key_id] = $row_p_part_id;

        return $datas_ins_p_part;
    }

    private function keys_dr(string $tabla): stdClass
    {
        $key_dr_part_base_dr = '';
        $key_dr_part_importe_dr = '';

        if($tabla === 'fc_traslado_p'){
            $key_dr_part_base_dr = 'fc_traslado_dr_part_base_dr';
            $key_dr_part_importe_dr = 'fc_traslado_dr_part_importe_dr';
        }
        if($tabla === 'fc_retencion_p'){
            $key_dr_part_base_dr = 'fc_retencion_dr_part_base_dr';
            $key_dr_part_importe_dr = 'fc_retencion_dr_part_importe_dr';
        }

        $data = new stdClass();
        $data->key_dr_part_base_dr = $key_dr_part_base_dr;
        $data->key_dr_part_importe_dr = $key_dr_part_importe_dr;
        return $data;
    }

    public function modifica_bd(array $registro, int $id, bool $reactiva = false,
                                array $keys_integra_ds = array('codigo', 'descripcion')): array|stdClass
    {

        $registro_previo = $this->registro(registro_id: $id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener registro_previo',data:  $registro_previo);
        }


        if(!isset($registro['descripcion'])){
            $key_codigo = $this->tabla.'_codigo';
            $codigo = $registro_previo[$key_codigo];
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