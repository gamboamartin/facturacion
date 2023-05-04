<?php
namespace gamboamartin\facturacion\models;

use base\orm\_modelo_parent_sin_codigo;
use gamboamartin\cat_sat\models\cat_sat_tipo_relacion;
use gamboamartin\errores\errores;
use stdClass;


class _relacion extends _modelo_parent_sin_codigo{

    protected _transacciones_fc $modelo_entidad;
    protected _relacionada $modelo_relacionada;

    public function alta_bd(array $keys_integra_ds = array('descripcion')): array|stdClass
    {
        $row_entidad = $this->modelo_entidad->registro(registro_id: $this->registro[$this->modelo_entidad->key_id]);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al al obtener row_entidad', data: $row_entidad);
        }

        $permite_transaccion = $this->modelo_entidad->verifica_permite_transaccion(registro_id: $this->registro[$this->modelo_entidad->key_id]);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error verificar transaccion', data: $permite_transaccion);
        }

        $cat_sat_tipo_relacion = (new cat_sat_tipo_relacion(link: $this->link))->registro(registro_id: $this->registro['cat_sat_tipo_relacion_id']);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al al obtener cat_sat_tipo_relacion', data: $cat_sat_tipo_relacion);
        }

        if(!isset($this->registro['descripcion'])){
            $descripcion = $row_entidad[$this->modelo_entidad->tabla.'_folio'].' '.$cat_sat_tipo_relacion['cat_sat_tipo_relacion_descripcion'];
            $this->registro['descripcion'] = $descripcion;
        }
        $r_alta_bd = parent::alta_bd(keys_integra_ds: $keys_integra_ds); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al dar de alta registro', data: $r_alta_bd);
        }
        return $r_alta_bd;
    }

    public function elimina_bd(int $id): array|stdClass
    {

        $fc_relacion = $this->registro(registro_id: $id, retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener relacion', data: $fc_relacion);
        }
        $key_id_entidad = $this->modelo_entidad->key_id;

        $permite_transaccion = $this->modelo_entidad->verifica_permite_transaccion(registro_id: $fc_relacion->$key_id_entidad);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error verificar transaccion', data: $permite_transaccion);
        }

        $filtro[$this->key_filtro_id] = $id;
        $del = $this->modelo_relacionada->elimina_con_filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al eliminar',data:  $del);
        }
        $r_elimina_bd = parent::elimina_bd($id); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al eliminar',data:  $r_elimina_bd);
        }
        return $r_elimina_bd;

    }

    /**
     * Obtiene las facturas relacionadas
     * @param array $row_relacion Relacion Base
     * @param _relacionada $modelo_relacionada
     * @return array
     * @version 7.19.3
     */
    final public function facturas_relacionadas(_relacionada $modelo_relacionada, array $row_relacion): array
    {
        $keys = array($this->key_id);
        $valida = $this->validacion->valida_ids(keys: $keys,registro:  $row_relacion);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar $fc_relacion', data: $valida);
        }
        $filtro = array();
        $filtro[$this->key_filtro_id] = $row_relacion[$this->key_id];
        $r_fc_factura_relacionada = $modelo_relacionada->filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener r_fc_factura_relacionada', data: $r_fc_factura_relacionada);
        }

        return $r_fc_factura_relacionada->registros;
    }


    private function genera_relacionado(_relacionada $modelo_relacionada, string $name_entidad, array $relacionados, array $row_relacion){
        $cat_sat_tipo_relacion_codigo = $row_relacion['cat_sat_tipo_relacion_codigo'];
        $relacionados[$cat_sat_tipo_relacion_codigo] = array();

        $fc_rows_relacionadas = $this->facturas_relacionadas(modelo_relacionada: $modelo_relacionada, row_relacion: $row_relacion);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener fc_rows_relacionadas', data: $fc_rows_relacionadas);
        }

        $relacionados = $this->integra_relacionados(cat_sat_tipo_relacion_codigo: $cat_sat_tipo_relacion_codigo,
            fc_rows_relacionadas: $fc_rows_relacionadas, name_entidad: $name_entidad, relacionados: $relacionados);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integrar relacionado', data: $relacionados);
        }
        return $relacionados;
    }

    final public function get_relaciones(int $registro_entidad_id, _transacciones_fc $modelo_entidad,
                                         _relacionada $modelo_relacionada){


        $row_relaciones = $this->relaciones(modelo_entidad: $modelo_entidad, registro_entidad_id: $registro_entidad_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener row_relaciones', data: $row_relaciones);
        }
        $relacionados = $this->relacionados(row_relaciones: $row_relaciones,modelo_relacionada: $modelo_relacionada,
            name_entidad: $modelo_entidad->tabla);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integrar relacionado', data: $relacionados);
        }

        return $relacionados;

    }

    /**
     *
     * @param string $cat_sat_tipo_relacion_codigo
     * @param string $name_entidad
     * @param array $relacionados
     * @param array $row_relacionada
     * @return array
     */
    private function integra_relacionado(string $cat_sat_tipo_relacion_codigo, string $name_entidad, array $relacionados,
                                         array $row_relacionada): array
    {
        $cat_sat_tipo_relacion_codigo = trim($cat_sat_tipo_relacion_codigo);
        $keys = array($name_entidad.'_uuid');
        $valida = $this->validacion->valida_existencia_keys(keys: $keys,registro: $row_relacionada);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar fc_factura_relacionada', data: $valida);
        }
        $relacionados[$cat_sat_tipo_relacion_codigo][] = $row_relacionada[$name_entidad.'_uuid'];
        return $relacionados;
    }

    private function integra_relacionados(string $cat_sat_tipo_relacion_codigo, array $fc_rows_relacionadas,
                                          string $name_entidad, array $relacionados){
        foreach ($fc_rows_relacionadas as $row_relacionada){
            $relacionados = $this->integra_relacionado(cat_sat_tipo_relacion_codigo: $cat_sat_tipo_relacion_codigo,
                name_entidad: $name_entidad, relacionados: $relacionados, row_relacionada: $row_relacionada);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al integrar relacionado', data: $relacionados);
            }
        }
        return $relacionados;
    }

    public function modifica_bd(array $registro, int $id, bool $reactiva = false, array $keys_integra_ds = array('descripcion')): array|stdClass
    {
        $fc_relacion = $this->registro(registro_id: $id, retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener relacion', data: $fc_relacion);
        }
        $key_entidad_id = $this->modelo_entidad->key_id;
        $permite_transaccion = $this->modelo_entidad->verifica_permite_transaccion(registro_id: $fc_relacion->$key_entidad_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error verificar transaccion', data: $permite_transaccion);
        }
        $r_modifica_bd =  parent::modifica_bd($registro, $id, $reactiva, $keys_integra_ds); // TODO: Change the autogenerated stub
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al modificar', data: $r_modifica_bd);
        }
        return $r_modifica_bd;
    }

    /**
     * Obtiene las relaciones de una factura
     * @param _transacciones_fc $modelo_entidad Modelo de tipo base factura complemento
     * @param int $registro_entidad_id Registro identificador
     * @return array
     * @version 6.33.0
     */
    final public function relaciones(_transacciones_fc $modelo_entidad, int $registro_entidad_id): array
    {
        $filtro = array();
        $filtro[$modelo_entidad->key_filtro_id] = $registro_entidad_id;

        $r_fc_relacion = $this->filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener relacion', data: $r_fc_relacion);
        }
        return $r_fc_relacion->registros;
    }

    private function relacionados(array $row_relaciones, _relacionada $modelo_relacionada, string $name_entidad){
        $relacionados = array();
        foreach ($row_relaciones as $row_relacion){
            $relacionados = $this->genera_relacionado(modelo_relacionada: $modelo_relacionada,
                name_entidad: $name_entidad, relacionados: $relacionados, row_relacion: $row_relacion);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al integrar relacionado', data: $relacionados);
            }
        }
        return $relacionados;
    }

}
