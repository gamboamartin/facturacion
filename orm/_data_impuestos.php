<?php

namespace gamboamartin\facturacion\models;

use base\orm\modelo;
use gamboamartin\errores\errores;

use PDO;
use stdClass;

class _data_impuestos extends _base{

    protected _partida $modelo_partida;
    protected _transacciones_fc $modelo_entidad;
    protected _etapa $modelo_etapa;


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

        $r_alta_bd =  parent::alta_bd();
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error registrar traslados', data: $r_alta_bd);
        }

        return $r_alta_bd;
    }

    public function get_data_row(int $registro_id): array|stdClass|int
    {
        $registro = $this->registro(registro_id: $registro_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener row',data:  $registro);
        }

        return $registro;
    }

    /**
     * Obtiene los impuestos trasladados de una partida
     * @param string $name_modelo_partida
     * @param int $registro_partida_id partida
     * @return array|stdClass|int
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

    public function elimina_bd(int $id): array|stdClass
    {
        $traslado = $this->get_data_row(registro_id: $id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener retenido',data: $traslado);
        }
        $key_id = $this->modelo_entidad->tabla.'_id';
        $permite_transaccion = $this->modelo_entidad->verifica_permite_transaccion(modelo_etapa: $this->modelo_etapa,
            registro_id: $traslado[$key_id]);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error verificar transaccion', data: $permite_transaccion);
        }
        $r_elimina_bd = parent::elimina_bd($id); // TODO: Change the autogenerated stub
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al eliminar', data: $r_elimina_bd);
        }
        return $r_elimina_bd;

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
