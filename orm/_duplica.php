<?php
namespace gamboamartin\facturacion\models;
use gamboamartin\errores\errores;
use gamboamartin\validacion\validacion;
use stdClass;

class _duplica
{
    private errores $error;
    private validacion $validacion;

    public function __construct(){
        $this->error = new errores();
        $this->validacion = new validacion();
    }
    private function fc_partida_ins(string $key_id, string $name_entidad_partida, array $row, int $row_entidad_id): array
    {
        $fc_partida_ins['com_producto_id'] = $row[$name_entidad_partida . '_com_producto_id'];
        $fc_partida_ins['cantidad'] = $row[$name_entidad_partida . '_cantidad'];
        $fc_partida_ins['descripcion'] = $row[$name_entidad_partida . '_descripcion'];
        $fc_partida_ins['valor_unitario'] = $row[$name_entidad_partida . '_valor_unitario'];
        $fc_partida_ins['descuento'] = $row[$name_entidad_partida . '_descuento'];
        $fc_partida_ins['cat_sat_obj_imp_id'] = $row[$name_entidad_partida . '_cat_sat_obj_imp_id'];
        $fc_partida_ins[$key_id] = $row_entidad_id;

        return $fc_partida_ins;
    }

    final public function genera_partidas(_transacciones_fc $modelo_entidad, _partida $modelo_partida, int $registro_id, int $row_entidad_id): array
    {
        $rows_partidas = $modelo_entidad->partidas_base(modelo_partida:  $modelo_partida, registro_id: $registro_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener rows_partidas', data: $rows_partidas);
        }


        $r_alta_bd_part = $this->inserta_partidas(key_id: $modelo_entidad->key_id,
            modelo_partida: $modelo_partida, name_entidad_partida: $modelo_partida->tabla,
            row_entidad_id: $row_entidad_id, rows_partidas: $rows_partidas);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al insertar registro', data: $r_alta_bd_part);
        }
        return $r_alta_bd_part;
    }

    private function genera_row_entidad_ins(_transacciones_fc $modelo_entidad, int $registro_id): array
    {
        $row_entidad = $modelo_entidad->registro(registro_id: $registro_id, columnas_en_bruto: true,
            retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener row_entidad', data: $row_entidad);
        }

        $row_entidad_ins = $this->row_entidad_ins(row_entidad: $row_entidad);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al generar registro', data: $row_entidad_ins);
        }
        return $row_entidad_ins;
    }

    private function inserta_partida(string $key_id, _partida $modelo_partida, string $name_entidad_partida, array $row,
                                     int $row_entidad_id): array|stdClass
    {
        $fc_partida_ins = $this->fc_partida_ins(key_id: $key_id, name_entidad_partida: $name_entidad_partida,
            row: $row, row_entidad_id: $row_entidad_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al generar registro', data: $fc_partida_ins);
        }


        $traslados = $modelo_partida->get_traslados(partida_id: $row[$modelo_partida->key_id]);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener traslados', data: $traslados);
        }

        $retenciones = $modelo_partida->get_retenciones(partida_id: $row[$modelo_partida->key_id]);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener retenciones', data: $retenciones);
        }

        $r_alta_bd_part = $modelo_partida->alta_registro(registro: $fc_partida_ins);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al insertar registro', data: $r_alta_bd_part);
        }

        $filtro[$modelo_partida->key_filtro_id] = $r_alta_bd_part->registro_id;

        $del = $modelo_partida->modelo_retencion->elimina_con_filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al eliminar retenciones', data: $del);
        }

        $del = $modelo_partida->modelo_traslado->elimina_con_filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al eliminar retenciones', data: $del);
        }

        foreach ($traslados as $traslado){
            $traslado_ins[$modelo_partida->key_id] = $r_alta_bd_part->registro_id;
            $traslado_ins['cat_sat_tipo_factor_id'] = $traslado['cat_sat_tipo_factor_id'];
            $traslado_ins['cat_sat_factor_id'] = $traslado['cat_sat_factor_id'];
            $traslado_ins['cat_sat_tipo_impuesto_id'] = $traslado['cat_sat_tipo_impuesto_id'];
            $traslado_ins['total'] = $traslado['fc_traslado_total'];

            $r_alta_traslado = $modelo_partida->modelo_traslado->alta_registro(registro: $traslado_ins);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al insertar traslado', data: $r_alta_traslado);
            }
        }

        foreach ($retenciones as $retencion){
            $retencion_ins[$modelo_partida->key_id] = $r_alta_bd_part->registro_id;
            $retencion_ins['cat_sat_tipo_factor_id'] = $retencion['cat_sat_tipo_factor_id'];
            $retencion_ins['cat_sat_factor_id'] = $retencion['cat_sat_factor_id'];
            $retencion_ins['cat_sat_tipo_impuesto_id'] = $retencion['cat_sat_tipo_impuesto_id'];
            $retencion_ins['total'] = $retencion['fc_retencion_total'];

            $r_alta_retencion = $modelo_partida->modelo_retencion->alta_registro(registro: $retencion_ins);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al insertar retencion', data: $r_alta_retencion);
            }
        }

        return $r_alta_bd_part;
    }

    private function inserta_partidas(string $key_id, _partida $modelo_partida, string $name_entidad_partida, int $row_entidad_id,
                                      array $rows_partidas): array
    {

        $altas = array();
        foreach ($rows_partidas as $row){

            $r_alta_bd_part = $this->inserta_partida(key_id: $key_id, modelo_partida: $modelo_partida,
                name_entidad_partida: $name_entidad_partida, row: $row, row_entidad_id: $row_entidad_id);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al insertar registro', data: $r_alta_bd_part);
            }
            $altas[] = $r_alta_bd_part;
        }
        return $altas;
    }

    final public function inserta_row_entidad(_transacciones_fc $modelo_entidad, int $registro_id){
        $row_entidad_ins = $this->genera_row_entidad_ins(modelo_entidad: $modelo_entidad, registro_id: $registro_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al generar registro', data: $row_entidad_ins);
        }


        $r_alta_bd = $modelo_entidad->alta_registro(registro: $row_entidad_ins);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al insertar registro', data: $r_alta_bd);
        }
        return $r_alta_bd->registro_id;
    }

    /**
     * Maqueta el registro para insersion
     * @param stdClass $row_entidad Registro base de operacion
     * @return array
     * @version 10.144.5
     */
    private function row_entidad_ins(stdClass $row_entidad): array
    {
        $keys = array('fc_csd_id','cat_sat_forma_pago_id','cat_sat_metodo_pago_id','cat_sat_moneda_id',
            'com_tipo_cambio_id','cat_sat_uso_cfdi_id','cat_sat_tipo_de_comprobante_id','dp_calle_pertenece_id',
            'cat_sat_regimen_fiscal_id','cat_sat_regimen_fiscal_id','com_sucursal_id');

        $valida = $this->validacion->valida_ids(keys: $keys,registro:  $row_entidad);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar row entidad',data:  $valida);
        }

        $keys = array('exportacion');

        $valida = $this->validacion->valida_existencia_keys(keys: $keys,registro:  $row_entidad);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar row entidad',data:  $valida);
        }

        $row_entidad_ins['fc_csd_id'] = $row_entidad->fc_csd_id;
        $row_entidad_ins['cat_sat_forma_pago_id'] = $row_entidad->cat_sat_forma_pago_id;
        $row_entidad_ins['cat_sat_metodo_pago_id'] = $row_entidad->cat_sat_metodo_pago_id;
        $row_entidad_ins['cat_sat_moneda_id'] = $row_entidad->cat_sat_moneda_id;
        $row_entidad_ins['com_tipo_cambio_id'] = $row_entidad->com_tipo_cambio_id;
        $row_entidad_ins['cat_sat_uso_cfdi_id'] = $row_entidad->cat_sat_uso_cfdi_id;
        $row_entidad_ins['cat_sat_tipo_de_comprobante_id'] = $row_entidad->cat_sat_tipo_de_comprobante_id;
        $row_entidad_ins['dp_calle_pertenece_id'] = $row_entidad->dp_calle_pertenece_id;
        $row_entidad_ins['exportacion'] = $row_entidad->exportacion;
        $row_entidad_ins['cat_sat_regimen_fiscal_id'] = $row_entidad->cat_sat_regimen_fiscal_id;
        $row_entidad_ins['com_sucursal_id'] = $row_entidad->com_sucursal_id;

        if(isset($row_entidad->observaciones)) {
            $row_entidad->observaciones = trim($row_entidad->observaciones);
            $row_entidad_ins['observaciones'] = $row_entidad->observaciones;
        }
        return $row_entidad_ins;
    }

}
