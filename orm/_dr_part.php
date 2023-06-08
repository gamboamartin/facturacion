<?php
namespace gamboamartin\facturacion\models;


use base\orm\_modelo_parent;
use gamboamartin\cat_sat\models\cat_sat_factor;

use gamboamartin\errores\errores;
use stdClass;

class _dr_part extends _modelo_parent{

    protected string $entidad_dr = '';
    protected string $tipo_impuesto = '';

    public function alta_bd(array $keys_integra_ds = array('descripcion')): array|stdClass
    {

        $cat_sat_factor = (new cat_sat_factor(link: $this->link))->registro(
            registro_id: $this->registro['cat_sat_factor_id'], retorno_obj: true);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener cat_sat_factor',data:  $cat_sat_factor);
        }

        $registro = $this->init_registro_alta(entidad_dr: $this->entidad_dr, registro: $this->registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener codigo',data:  $registro);
        }

        $this->registro = $registro;

        $r_alta_bd = parent::alta_bd(keys_integra_ds: $keys_integra_ds); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar',data:  $r_alta_bd);
        }


        $cat_sat_tipo_impuesto_codigo = $r_alta_bd->registro['cat_sat_tipo_impuesto_codigo'];

        $upd = $this->upd_fc_pago_total(cat_sat_factor: $cat_sat_factor,
            cat_sat_tipo_impuesto_codigo: $cat_sat_tipo_impuesto_codigo,
            fc_pago_id: $r_alta_bd->registro['fc_pago_id'], tipo_impuesto: $this->tipo_impuesto);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al actualizar pago_total',data:  $upd);
        }

        return $r_alta_bd;
    }

    private function campos_dr_impuesto_part(): array
    {
        $campos['base_dr'] = $this->tabla.'.base_dr';
        $campos['importe_dr'] = $this->tabla.'.importe_dr';
        return $campos;
    }

    /**
     * Integra el codigo si no existe de manera automatica
     * @param string $entidad_dr Entidad de tipo traslado_dr o retencion_dr
     * @param array $registro Registro en proceso de alta
     * @return array
     * @version 10.34.0
     */
    private function codigo(string $entidad_dr, array $registro): array
    {
        $entidad_dr = trim($entidad_dr);
        if($entidad_dr === ''){
            return $this->error->error(mensaje: 'Error entidad_dr esta vacia',data:  $entidad_dr);
        }

        if(!isset($registro['codigo'])){
            $key_id = $entidad_dr.'_id';
            $keys = array($key_id);
            $valida = $this->validacion->valida_ids(keys: $keys,registro:  $registro);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al validar registro',data:  $valida);
            }

            $codigo = $registro[$entidad_dr.'_id'];
            $codigo .= time();
            $codigo .= mt_rand(1000,9999);
            $registro['codigo'] = $codigo;
        }
        return $registro;
    }

    private function data_importes_total(array $fc_impuesto_dr_part): stdClass
    {
        $base_dr = round($fc_impuesto_dr_part['base_dr'],2);
        $importe_dr = round($fc_impuesto_dr_part['importe_dr'],2);

        $data = new stdClass();
        $data->base_dr = $base_dr;
        $data->importe_dr = $importe_dr;
        return $data;
    }

    /**
     * @param array $registro
     * @return array
     */
    private function descripcion(array $registro): array
    {
        if(!isset($registro['descripcion'])){
            $descripcion = $registro['codigo'];
            $registro['descripcion'] = $descripcion;
        }
        return $registro;

    }

    private function fc_pago_total(int $fc_pago_id){
        $filtro['fc_pago.id'] = $fc_pago_id;
        $r_pago_total = (new fc_pago_total(link: $this->link))->filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener pago_total',data:  $r_pago_total);
        }
        return $r_pago_total->registros[0];
    }

    private function fc_pago_total_upd(stdClass $cat_sat_factor, string $cat_sat_tipo_impuesto_codigo,
                                       int $fc_pago_id, string $tipo_impuesto): array
    {

        $impuestos = $this->importes_impuestos_dr_part(cat_sat_factor_id: $cat_sat_factor->cat_sat_factor_id,
            fc_pago_id:  $fc_pago_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener sumatorias de fc_retencion_dr_part',
                data:  $impuestos);
        }
        $key_factor = $this->key_factor(cat_sat_factor: $cat_sat_factor);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener key_factor', data:  $key_factor);
        }

        $fc_pago_total_upd = $this->fc_pago_total_upd_factor(
            cat_sat_tipo_impuesto_codigo: $cat_sat_tipo_impuesto_codigo, impuestos: $impuestos,
            key_factor: $key_factor, tipo_impuesto: $tipo_impuesto);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al maquetar fc_pago_total_upd', data:  $fc_pago_total_upd);
        }


        return $fc_pago_total_upd;
    }

    private function fc_pago_total_upd_factor(string $cat_sat_tipo_impuesto_codigo, stdClass $impuestos,
                                              string $key_factor, string $tipo_impuesto): array
    {

        $fc_pago_total_upd = array();
        if($tipo_impuesto === 'retenciones'){
            if($cat_sat_tipo_impuesto_codigo === '001'){
                $fc_pago_total_upd['total_retenciones_isr'] = round($impuestos->importe_dr,2);
            }
            if($cat_sat_tipo_impuesto_codigo === '002'){
                $fc_pago_total_upd['total_retenciones_iva'] = round($impuestos->importe_dr,2);
            }
            if($cat_sat_tipo_impuesto_codigo === '003'){
                $fc_pago_total_upd['total_retenciones_ieps'] = round($impuestos->importe_dr,2);
            }
        }

        if($tipo_impuesto === 'traslados'){
            $key_base_dr = "total_".$tipo_impuesto."_base_iva_$key_factor";
            $key_importe_dr = "total_".$tipo_impuesto."_impuesto_iva_$key_factor";

            $fc_pago_total_upd[$key_base_dr] = round($impuestos->base_dr,2);
            $fc_pago_total_upd[$key_importe_dr] = round($impuestos->importe_dr,2);
        }



        return $fc_pago_total_upd;
    }



    /**
     * Maqueta el filtro para obtener las partidas de traslados de un documento
     * @param int $cat_sat_factor_id Factor a verificar
     * @param int $fc_pago_id Pago a verificar
     * @return array
     */
    private function filtro_impuestos_dr_part(int $cat_sat_factor_id, int $fc_pago_id): array
    {
        if($cat_sat_factor_id <= 0){
            return $this->error->error(mensaje: 'Error cat_sat_factor_id debe sr mayor a 0', data:  $cat_sat_factor_id);
        }
        if($fc_pago_id <= 0){
            return $this->error->error(mensaje: 'Error fc_pago_id debe sr mayor a 0', data:  $fc_pago_id);
        }
        $filtro['cat_sat_factor.id'] = $cat_sat_factor_id;
        $filtro['fc_pago.id'] = $fc_pago_id;
        return $filtro;
    }

    private function importes_impuestos_dr_part(int $cat_sat_factor_id, int $fc_pago_id){

        $fc_impuesto_dr_part = $this->sum_impuestos_dr_parts(cat_sat_factor_id: $cat_sat_factor_id,fc_pago_id:  $fc_pago_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener sumatorias de fc_impuesto_dr_part', data:  $fc_impuesto_dr_part);
        }

        $data = $this->data_importes_total(fc_impuesto_dr_part: $fc_impuesto_dr_part);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al maquetar importes', data:  $data);
        }

        return $data;

    }

    private function init_registro_alta(string $entidad_dr, array $registro){
        $registro = $this->codigo(entidad_dr: $entidad_dr, registro: $registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener codigo',data:  $registro);
        }
        $registro = $this->descripcion(registro: $registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener codigo',data:  $registro);
        }
        return $registro;
    }

    private function key_factor(stdClass $cat_sat_factor): string
    {
        $key_factor = '';
        if((float)$cat_sat_factor->cat_sat_factor_factor === 0.00){
            $key_factor = '00';
        }
        if((float)$cat_sat_factor->cat_sat_factor_factor === 0.08){
            $key_factor = '08';
        }
        if((float)$cat_sat_factor->cat_sat_factor_factor === 0.16){
            $key_factor = '16';
        }
        return $key_factor;
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

    private function params_importes_total(int $cat_sat_factor_id, int $fc_pago_id){
        $filtro = $this->filtro_impuestos_dr_part(cat_sat_factor_id: $cat_sat_factor_id,fc_pago_id:  $fc_pago_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al maquetar filtro', data:  $filtro);
        }

        $campos = $this->campos_dr_impuesto_part();
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al maquetar filtro', data:  $filtro);
        }

        $data = new stdClass();
        $data->filtro = $filtro;
        $data->campos = $campos;
        return $data;
    }

    private function sum_impuestos_dr_parts(int $cat_sat_factor_id, int $fc_pago_id){
        $params = $this->params_importes_total(cat_sat_factor_id: $cat_sat_factor_id, fc_pago_id: $fc_pago_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al maquetar params', data:  $params);
        }

        $fc_impuestos_dr_part = $this->suma(campos: $params->campos, filtro: $params->filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener sumatorias de fc_impuestos_dr_part',
                data:  $fc_impuestos_dr_part);
        }
        return $fc_impuestos_dr_part;
    }

    final public function upd_fc_pago_total(stdClass $cat_sat_factor, string $cat_sat_tipo_impuesto_codigo,
                                            int $fc_pago_id, string $tipo_impuesto){
        $fc_pago_total_upd = $this->fc_pago_total_upd(cat_sat_factor: $cat_sat_factor,
            cat_sat_tipo_impuesto_codigo: $cat_sat_tipo_impuesto_codigo, fc_pago_id: $fc_pago_id,
            tipo_impuesto: $tipo_impuesto);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener fc_pago_total_upd',data:  $fc_pago_total_upd);
        }



        $fc_pago_total = $this->fc_pago_total(fc_pago_id: $fc_pago_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener fc_pago_total',data:  $fc_pago_total);
        }

        $upd = (new fc_pago_total(link: $this->link))->modifica_bd(registro: $fc_pago_total_upd ,
            id: $fc_pago_total['fc_pago_total_id']);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al actualizar pago_total',data:  $upd);
        }
        return $upd;
    }

}

