<?php

namespace gamboamartin\facturacion\models;

use gamboamartin\errores\errores;
use stdClass;

class _calculo_imps{

    private errores $error;

    public function __construct(){
        $this->error = new errores();
    }

    private function acumula_factor(array $factores){
        $factor_impuesto = 0.0;
        foreach ($factores as $factor){
            $factor_impuesto+=$factor;
        }
        return $factor_impuesto;
    }

    /**
     * Verifica si existe un factor en un array de factores
     * @param float $factor_impuesto_row Factor a comparar
     * @param array $factores Factores previo guardados
     * @return bool
     */
    private function existe_factor(float $factor_impuesto_row, array $factores): bool
    {
        $existe_factor = false;
        foreach ($factores as $factor){
            if((float)$factor === $factor_impuesto_row){
                $existe_factor = true;
            }
        }
        return $existe_factor;
    }

    private function factor_base(array $impuestos){
        $factores = $this->factores(impuestos: $impuestos);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al integrar factor', data: $factores);
        }

        $factor = $this->acumula_factor(factores: $factores);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al integrar factor', data: $factor);
        }
        return $factor;
    }

    private function factores(array $impuestos){
        $factores = array();
        foreach ($impuestos as $row_impuesto){
            $factores = $this->integra_factor(factores: $factores,row_impuesto:  $row_impuesto);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al integrar factor', data: $factores);
            }
        }
        return $factores;
    }

    private function integra_factor(array $factores, array $row_impuesto){
        $factor_impuesto_row = (float)$row_impuesto['cat_sat_factor_factor'];
        $existe_factor = $this->existe_factor(factor_impuesto_row: $factor_impuesto_row,factores:  $factores);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar si existe factor', data: $existe_factor);
        }
        if(!$existe_factor){
            $factores[] = $factor_impuesto_row;
        }
        return $factores;
    }


    final public function retenciones(_data_impuestos $modelo_retencion, string $key_filtro_id, int $registro_id){
        $filtro[$key_filtro_id] = $registro_id;
        $r_retenciones = $modelo_retencion->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar si tiene registros', data: $r_retenciones);
        }
        return $r_retenciones->registros;
    }

    final public function tasas_de_impuestos(string $key_filtro_id, _data_impuestos $modelo_traslado,
                                             _data_impuestos $modelo_retencion, int $registro_id){

        if($registro_id <= 0){
            return $this->error->error(mensaje: 'Error al registro_id debe ser mayor a 0', data: $registro_id);
        }

        $traslados = $this->traslados(modelo_traslado: $modelo_traslado,
            key_filtro_id: $key_filtro_id, registro_id: $registro_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener traslados', data: $traslados);
        }

        $tiene_traslado = $this->tiene_traslados(modelo_traslado: $modelo_traslado,
            key_filtro_id: $key_filtro_id, registro_id: $registro_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener traslados', data: $tiene_traslado);
        }

        $retenciones = $this->retenciones(modelo_retencion: $modelo_retencion,
            key_filtro_id: $key_filtro_id, registro_id: $registro_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener retenciones', data: $retenciones);
        }
        $tiene_retencion = $this->tiene_retenciones(modelo_retencion: $modelo_retencion,
            key_filtro_id: $key_filtro_id, registro_id: $registro_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener tiene_retencion', data: $tiene_retencion);
        }


        $factor_traslado = $this->factor_base(impuestos: $traslados);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al integrar factor', data: $factor_traslado);
        }
        $factor_retenido = $this->factor_base(impuestos: $retenciones);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al integrar factor', data: $factor_traslado);
        }


        $data = new stdClass();
        $data->traslado = new stdClass();

        $data->traslado->aplica = $tiene_traslado;
        $data->traslado->registros = $traslados;
        $data->traslado->factor = $factor_traslado;

        $data->retencion = new stdClass();

        $data->retencion->aplica = $tiene_retencion;
        $data->retencion->registros = $retenciones;
        $data->retencion->factor = $factor_retenido;

        $data->factor_calculo = $factor_traslado - $factor_retenido;

        return $data;

    }

    final public function tiene_impuestos(string $key_filtro_id, _data_impuestos $modelo_traslado,
                                          _data_impuestos $modelo_retencion, int $registro_id){

        $tiene_retenciones = $this->tiene_retenciones(modelo_retencion: $modelo_retencion,
            key_filtro_id: $key_filtro_id, registro_id: $registro_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar si tiene registros', data: $tiene_retenciones);
        }
        $tiene_traslados = $this->tiene_traslados(modelo_traslado: $modelo_traslado,
            key_filtro_id: $key_filtro_id, registro_id: $registro_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar si tiene registros', data: $tiene_traslados);
        }

        $tiene_impuestos = false;
        if($tiene_retenciones || $tiene_traslados){
            $tiene_impuestos = true;
        }
        return $tiene_impuestos;

    }

    final public function tiene_retenciones(_data_impuestos $modelo_retencion, string $key_filtro_id, int $registro_id){
        $filtro[$key_filtro_id] = $registro_id;
        $tiene_rows = $modelo_retencion->existe(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar si tiene registros', data: $tiene_rows);
        }
        return $tiene_rows;
    }

    final public function tiene_traslados(_data_impuestos $modelo_traslado, string $key_filtro_id, int $registro_id){
        $filtro[$key_filtro_id] = $registro_id;
        $tiene_rows = $modelo_traslado->existe(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar si tiene registros', data: $tiene_rows);
        }
        return $tiene_rows;
    }

    /**
     * Obtiene los traslados de una partida
     * @param _data_impuestos $modelo_traslado Modelo para obtencion de traslados
     * @param string $key_filtro_id
     * @param int $registro_id Registro base de fc_factura o Nota de credito
     * @return array
     * @version 10.81.3
     */
    final public function traslados(_data_impuestos $modelo_traslado, string $key_filtro_id, int $registro_id): array
    {
        if($registro_id <= 0){
            return $this->error->error(mensaje: 'Error al registro_id debe ser mayor a 0', data: $registro_id);
        }
        $filtro[$key_filtro_id] = $registro_id;
        $r_traslados = $modelo_traslado->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar si tiene registros', data: $r_traslados);
        }
        return $r_traslados->registros;
    }
}
