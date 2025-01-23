<?php
namespace gamboamartin\facturacion\models;
use gamboamartin\errores\errores;
use gamboamartin\validacion\validacion;
use stdClass;

class _impuestos{

    private errores $error;
    private validacion  $validacion;

    public function __construct(){
        $this->error = new errores();
        $this->validacion = new validacion();
    }

    private function acumulado_global_imp(array $global_imp, array $impuesto, string $key_gl, string $key_importe): stdClass
    {
        $base = round($impuesto['fc_partida_importe_con_descuento'],2);
        $base_ac = round($global_imp[$key_gl]->base+ $base,2);

        $importe = round($impuesto[$key_importe],2);
        $importe_ac = round($global_imp[$key_gl]->importe+ $importe,2);

        $base_ac = number_format($base_ac,2,'.','');
        $importe_ac = number_format($importe_ac,2,'.','');

        $data = new stdClass();
        $data->base_ac = $base_ac;
        $data->importe_ac = $importe_ac;

        return $data;

    }

    private function acumulado_global_impuesto(array $global_imp, array $impuesto, string $key_gl, string $key_importe){
        $acumulado = $this->acumulado_global_imp(global_imp: $global_imp, impuesto: $impuesto, key_gl: $key_gl, key_importe: $key_importe);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al inicializar acumulado', data: $acumulado);
        }

        $global_imp[$key_gl]->base = $acumulado->base_ac;
        $global_imp[$key_gl]->importe = $acumulado->importe_ac;
        return $global_imp;
    }

    private function carga_global(stdClass $data_imp, array $impuesto, array $imp_global, string $key_gl): array
    {
        $imp_global[$key_gl]->base = $data_imp->base;
        $imp_global[$key_gl]->tipo_factor = $impuesto['cat_sat_tipo_factor_descripcion'];
        $imp_global[$key_gl]->tasa_o_cuota = $data_imp->cat_sat_factor_factor;
        $imp_global[$key_gl]->impuesto = $impuesto['cat_sat_tipo_impuesto_codigo'];
        $imp_global[$key_gl]->importe = $data_imp->importe;




        return $imp_global;
    }

    /**
     * @param array $row_entidad
     * @return stdClass|array
     */
    final public function impuestos(array $row_entidad): stdClass|array
    {
        $keys = array('total_impuestos_trasladados','total_impuestos_retenidos');
        $valida = $this->validacion->valida_existencia_keys(keys: $keys,registro:  $row_entidad);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar $row_entidad', data: $valida);
        }

        if(!isset($row_entidad['traslados'])){
            $row_entidad['traslados'] = array();
        }
        if(!isset($row_entidad['retenidos'])){
            $row_entidad['retenidos'] = array();
        }


        $tiene_tasa = $this->tiene_tasa(row_entidad: $row_entidad);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar si tiene tasa', data: $tiene_tasa);
        }


        $impuestos = new stdClass();
        if($tiene_tasa) {
            $impuestos->total_impuestos_trasladados = $row_entidad['total_impuestos_trasladados'];
        }
        $impuestos->total_impuestos_retenidos = $row_entidad['total_impuestos_retenidos'];
        $impuestos->traslados = $row_entidad['traslados'];
        $impuestos->retenciones = $row_entidad['retenidos'];

        return $impuestos;
    }

    final public function impuestos_globales(stdClass $impuestos, array $global_imp, string $key_importe,
                                             string $name_tabla_partida){
        foreach ($impuestos->registros as $impuesto){

            $key_gl = $impuesto['cat_sat_tipo_factor_id'].'.'.$impuesto['cat_sat_factor_id'].'.'.$impuesto['cat_sat_tipo_impuesto_id'];

            $global_imp = $this->integra_ac_impuesto(global_imp: $global_imp, impuesto: $impuesto, key_gl: $key_gl,
                key_importe: $key_importe, name_tabla_partida: $name_tabla_partida);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al inicializar acumulado', data: $global_imp);
            }

        }
        return $global_imp;
    }

    private function init_globales(array $global_nodo, array $impuesto, string $key, string $key_importe,
                                   string $name_tabla_partida): stdClass
    {
        $global_nodo[$key] = new stdClass();
        $base = round($impuesto[$name_tabla_partida.'_importe_con_descuento'],2);
        $importe = round($impuesto[$key_importe],2);
        $cat_sat_factor_factor = round($impuesto['cat_sat_factor_factor'],6);


        $base = number_format($base,2,'.','');
        $importe = number_format($importe,2,'.','');
        $cat_sat_factor_factor = number_format($cat_sat_factor_factor,6,'.','');


        $data  = new stdClass();
        $data->global_nodo = $global_nodo;
        $data->base = $base;
        $data->importe = $importe;
        $data->cat_sat_factor_factor = $cat_sat_factor_factor;
        return $data;

    }

    private function init_imp_global(array $global_nodo, array $impuesto, string $key_gl, string $key_importe,
                                     string $name_tabla_partida){

        $data_imp = $this->init_globales(global_nodo:$global_nodo, impuesto: $impuesto, key: $key_gl,
            key_importe:$key_importe, name_tabla_partida: $name_tabla_partida);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al inicializar global impuesto', data: $data_imp);
        }

        $global_nodo = $data_imp->global_nodo;

        $global_nodo = $this->carga_global(data_imp: $data_imp,impuesto:  $impuesto, imp_global: $global_nodo,
            key_gl: $key_gl);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al inicializar global impuesto', data: $global_nodo);
        }

        return $global_nodo;
    }

    private function integra_ac_impuesto(array $global_imp, array $impuesto, string $key_gl, string $key_importe,
                                         string $name_tabla_partida){
        if(!isset($global_imp[$key_gl])) {
            $global_imp = $this->init_imp_global(global_nodo: $global_imp, impuesto: $impuesto, key_gl: $key_gl,
                key_importe: $key_importe, name_tabla_partida: $name_tabla_partida);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al inicializar global impuesto', data: $global_imp);
            }

        }
        else{
            $global_imp = $this->acumulado_global_impuesto(global_imp: $global_imp, impuesto: $impuesto,
                key_gl: $key_gl, key_importe: $key_importe);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al inicializar acumulado', data: $global_imp);
            }
        }
        return $global_imp;
    }

    final public function maqueta_impuesto(stdClass $impuestos, string $key_importe_impuesto, string $name_tabla_partida): array
    {
        $imp = array();

        foreach ($impuestos->registros as $impuesto) {

            $impuesto_obj = new stdClass();
            $impuesto_obj->base = number_format($impuesto[$name_tabla_partida.'_importe_con_descuento'], 2,'.','');
            $impuesto_obj->impuesto = $impuesto['cat_sat_tipo_impuesto_codigo'];
            $impuesto_obj->tipo_factor = $impuesto['cat_sat_tipo_factor_descripcion'];
            $impuesto_obj->tasa_o_cuota = number_format($impuesto['cat_sat_factor_factor'], 6,'.','');
            $impuesto_obj->importe = number_format($impuesto[$key_importe_impuesto], 2,'.','');

            if($impuesto['cat_sat_tipo_factor_codigo'] === 'Exento'){
                unset($impuesto->tasa_o_cuota);
                unset($impuesto->importe);
            }

            $imp[] = $impuesto_obj;
        }

        return $imp;
    }

    /**
     * Verifica si el tipo de impuestos de traslado tienen o no una tasa de impuestos diferente a exento
     * @param array $row_entidad Registro en proceso
     * @return bool|array
     */
    private function tiene_tasa(array $row_entidad): bool|array
    {
        $tiene_tasa = false;
        if(isset($row_entidad['traslados'])){
            if(!is_array($row_entidad['traslados'])){
                return $this->error->error(mensaje: 'Error $row_entidad[traslados] debe ser un array' ,
                    data: $row_entidad);
            }
            foreach ($row_entidad['traslados'] as $imp_traslado){
                $valida = $this->valida_tasa_cuota(imp_traslado: $imp_traslado);
                if(errores::$error){
                    return $this->error->error(mensaje: 'Error al validar impuesto' ,
                        data: $valida);
                }
                if ($imp_traslado->tipo_factor !=='Exento'){
                    $tiene_tasa = true;
                    break;
                }
            }

        }
        return $tiene_tasa;
    }

    private function valida_tasa_cuota(mixed $imp_traslado){
        if(!is_object($imp_traslado)){
            return $this->error->error(mensaje: 'Error $row_entidad[traslados][] debe ser un objeto' ,
                data: $imp_traslado);
        }
        $keys = array('tipo_factor');
        $valida = $this->validacion->valida_existencia_keys(keys: $keys, registro: $imp_traslado);

        if(errores::$error){
            return $this->error->error(mensaje: 'Error $row_entidad[traslados][] al validar' ,
                data: $valida);
        }
        return true;
    }
}
