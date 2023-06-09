<?php

namespace gamboamartin\facturacion\models;

use base\orm\modelo;
use gamboamartin\cat_sat\models\cat_sat_factor;
use gamboamartin\cat_sat\models\cat_sat_tipo_factor;
use gamboamartin\errores\errores;

use PDO;
use stdClass;

class _data_impuestos extends _base{

    protected _partida $modelo_partida;
    protected _transacciones_fc $modelo_entidad;
    protected _etapa $modelo_etapa;

    public bool $valida_restriccion = true;


    public function alta_bd(array $keys_integra_ds = array('codigo', 'descripcion')): array|stdClass
    {
        $registro = $this->init_alta_bd();
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al inicializar campos base', data: $registro);
        }

        $this->registro = $this->validaciones(data: $this->registro, name_modelo_partida: $this->modelo_partida->tabla);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar foraneas',data: $this->registro);
        }

        $key_entidad_partida_id = $this->modelo_partida->tabla.'_id';
        $fc_entidad_partida = $this->modelo_partida->registro(registro_id: $this->registro[$key_entidad_partida_id], retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener fc_entidad_partida', data: $fc_entidad_partida);
        }


        $key_entidad_id_base = $this->modelo_entidad->tabla.'_id';
        $permite_transaccion = $this->modelo_entidad->verifica_permite_transaccion(modelo_etapa: $this->modelo_etapa,
            registro_id: $fc_entidad_partida->$key_entidad_id_base);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error verificar transaccion', data: $permite_transaccion);
        }


        $cat_sat_factor = (new cat_sat_factor(link: $this->link))->registro(registro_id: $this->registro['cat_sat_factor_id'], retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error obtener cat_sat_factor', data: $cat_sat_factor);
        }

        $cat_sat_tipo_factor = (new cat_sat_tipo_factor(link: $this->link))->registro(registro_id: $this->registro['cat_sat_tipo_factor_id'], retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error obtener cat_sat_tipo_factor', data: $cat_sat_tipo_factor);
        }

        $row_partida = $this->modelo_partida->registro(registro_id: $this->registro[$this->modelo_partida->key_id], columnas_en_bruto: true, retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error obtener row_partida', data: $row_partida);
        }


        $total = $this->calcula_total(cat_sat_factor: $cat_sat_factor,row_partida:  $row_partida);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error calcular total', data: $total);
        }

        $this->registro['total'] = $total;

        $r_alta_bd =  parent::alta_bd();
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error registrar traslados', data: $r_alta_bd);
        }

        $upd_partida = $this->upd_partida(key_filtro_id: $this->modelo_partida->key_filtro_id, row_partida_id: $row_partida->id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error ejecutar upd_partida', data: $upd_partida);
        }

        return $r_alta_bd;
    }

    private function calcula_total(stdClass $cat_sat_factor, stdClass $row_partida): float
    {
        return round($row_partida->sub_total * $cat_sat_factor->cat_sat_factor_factor,2);
    }

    public function elimina_bd(int $id): array|stdClass
    {

        $init = $this->init_del(id: $id,modelo_entidad:  $this->modelo_entidad,modelo_etapa:  $this->modelo_etapa,
            modelo_partida:  $this->modelo_partida);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error obtener init', data: $init);
        }

        $r_elimina_bd = parent::elimina_bd($id); // TODO: Change the autogenerated stub
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al eliminar', data: $r_elimina_bd);
        }

        $upd_partida = $this->upd_partida(key_filtro_id: $this->modelo_partida->key_filtro_id,
            row_partida_id: $init->row_partida->id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error ejecutar upd_partida', data: $upd_partida);
        }

        return $r_elimina_bd;

    }

    /**
     * Obtiene un registro de tipo impuesto
     * @param int $registro_id Registro en proceso
     * @return array|int
     * @version 10.63.3
     */
    private function get_data_row(int $registro_id): array|int
    {
        $registro = $this->registro(registro_id: $registro_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener row',data:  $registro);
        }

        return $registro;
    }

    /**
     * Obtiene los impuestos trasladados de una partida
     * @param string $name_modelo_partida Nombre del modelo de tipo impuestopuede ser retenido traslado
     * @param int $registro_partida_id partida
     * @return array|stdClass|int
     *
     */
    final public function get_data_rows(string $name_modelo_partida, int $registro_partida_id): array|stdClass|int
    {

        $name_modelo_partida = trim($name_modelo_partida);
        if($name_modelo_partida === ''){
            return $this->error->error(mensaje: 'Error name_modelo_partida esta inicializado',data:  $name_modelo_partida);
        }
        $key_id = $name_modelo_partida.'.id';
        $filtro[$key_id]  = $registro_partida_id;
        $registro = $this->filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener traslados',data:  $registro);
        }

        return $registro;
    }

    private function init_del(int $id, _transacciones_fc $modelo_entidad, _etapa $modelo_etapa, _partida $modelo_partida){
        $traslado = $this->get_data_row(registro_id: $id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener retenido',data: $traslado);
        }

        $permite_transaccion = $this->valida_restriccion(modelo_entidad:  $modelo_entidad,
            modelo_etapa:  $modelo_etapa, traslado: $traslado);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error verificar transaccion', data: $permite_transaccion);
        }

        $row_partida = $this->modelo_partida->registro(registro_id: $traslado[$modelo_partida->key_id],
            columnas_en_bruto: true, retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error obtener row_partida', data: $row_partida);
        }

        $data = new stdClass();
        $data->traslado = $traslado;
        $data->permite_transaccion = $permite_transaccion;
        $data->row_partida = $row_partida;
        return $data;

    }

    private function key_total(): string
    {
        $key_total = '';
        if($this->tabla === 'fc_traslado' || $this->tabla === 'fc_traslado_nc' || $this->tabla === 'fc_traslado_cp'){
            $key_total = 'total_traslados';
        }
        if($this->tabla === 'fc_retenido' || $this->tabla === 'fc_retenido_nc' || $this->tabla === 'fc_retenido_cp'){
            $key_total = 'total_retenciones';
        }
        return $key_total;
    }

    public function modifica_bd(array $registro, int $id, bool $reactiva = false,
                                array $keys_integra_ds = array('codigo', 'descripcion')): array|stdClass
    {
        $traslado = $this->get_data_row(registro_id: $id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener traslado',data: $traslado);
        }
        $key_entidad_base_id = $this->modelo_entidad->tabla.'_id';
        $permite_transaccion = $this->modelo_entidad->verifica_permite_transaccion(modelo_etapa: $this->modelo_etapa,
            registro_id: $traslado->$key_entidad_base_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error verificar transaccion', data: $permite_transaccion);
        }

        $key_codigo = $this->tabla.'_codigo';
        if(!isset($registro['codigo'])){
            $registro['codigo'] =  $traslado[$key_codigo];
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al obtener el codigo del registro',data: $registro);
            }
        }

        $registro = $this->campos_base(data: $registro,modelo: $this,id: $id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al inicializar campos base',data: $registro);
        }

        $registro = $this->validaciones(data: $registro, name_modelo_partida: $this->modelo_partida->tabla);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar foraneas',data: $registro);
        }

        $r_modifica_bd = parent::modifica_bd($registro, $id, $reactiva);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al modificar traslados',data:  $r_modifica_bd);
        }

        return $r_modifica_bd;
    }

    private function row_partida_upd(string $key_filtro_id, int $row_partida_id){
        $total = $this->total(key_filtro_id: $key_filtro_id, row_partida_id: $row_partida_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error obtener total', data: $total);
        }

        $key_total = $this->key_total();
        if(errores::$error){
            return $this->error->error(mensaje: 'Error obtener key_total', data: $key_total);
        }

        $row_partida_upd[$key_total] = $total;
        return $row_partida_upd;
    }

    /**
     * Obtiene el total de una partida para impuestos
     * @param string $key_filtro_id key de filtro de entidad
     * @param int $row_partida_id Registro id en proceso
     * @return array|float
     */
    private function total(string $key_filtro_id, int $row_partida_id): float|array
    {
        $filtro[$key_filtro_id] = $row_partida_id;
        $r_data_impuestos = $this->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error obtener r_data_impuestos', data: $r_data_impuestos);
        }

        $rows_impuestos = $r_data_impuestos->registros;

        $total = 0.0;
        foreach ($rows_impuestos as $row_impuesto){
            $total += round($row_impuesto[$this->tabla.'_total'],2);

        }
        return $total;
    }

    private function upd_partida(string $key_filtro_id, int $row_partida_id){
        $row_partida_upd = $this->row_partida_upd(key_filtro_id: $key_filtro_id, row_partida_id: $row_partida_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error obtener row_partida_upd', data: $row_partida_upd);
        }

        $upd_partida = $this->modelo_partida->modifica_bd(registro: $row_partida_upd,id:  $row_partida_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error modificar', data: $upd_partida);
        }
        return $upd_partida;
    }


    private function valida_restriccion( _transacciones_fc $modelo_entidad, _etapa $modelo_etapa, array $traslado){

        $key_id = $modelo_entidad->tabla.'_id';
        if($this->valida_restriccion) {
            $permite_transaccion = $modelo_entidad->verifica_permite_transaccion(modelo_etapa: $modelo_etapa,
                registro_id: $traslado[$key_id]);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error verificar transaccion', data: $permite_transaccion);
            }
        }
        return true;
    }

    private function validaciones(array $data, string $name_modelo_partida): array
    {
        $keys = array('descripcion','codigo');
        $valida = $this->validacion->valida_existencia_keys(keys:$keys,registro:  $data);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar campos', data: $valida);
        }


        $keys = array($name_modelo_partida.'_id', 'cat_sat_tipo_factor_id', 'cat_sat_factor_id', 'cat_sat_tipo_impuesto_id');
        $valida = $this->validacion->valida_ids(keys: $keys, registro: $data);
        if(errores::$error){
            return $this->error->error(mensaje: "Error al validar foraneas",data:  $valida);
        }

        return $data;
    }

}
