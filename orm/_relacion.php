<?php
namespace gamboamartin\facturacion\models;

use base\orm\_modelo_parent_sin_codigo;
use gamboamartin\cat_sat\models\cat_sat_tipo_relacion;
use gamboamartin\errores\errores;
use stdClass;


class _relacion extends _modelo_parent_sin_codigo{

    protected _transacciones_fc $modelo_entidad;
    protected _relacionada $modelo_relacionada;
    protected _uuid_ext $modelo_uuid_ext;

    protected _etapa $modelo_etapa;

    protected array $codigos_rel_permitidos = array();

    /**
     * Ajusta las relaciones obtenidas de folios externos
     * @param string $cat_sat_tipo_relacion_codigo Tipo de relacion
     * @param _uuid_ext $modelo_uuid_ext Modelo externo
     * @param array $relacionados relaciones previamente cargadas
     * @param array $row_relacion Registro de relacion nuevo
     * @return array
     * @version 10.143.5
     */
    private function ajusta_relacionados(string $cat_sat_tipo_relacion_codigo, _uuid_ext $modelo_uuid_ext,
                                         array $relacionados, array $row_relacion): array
    {
        $valida = $this->valida_data_rel(row_relacion: $row_relacion);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error valida row relacion', data: $valida);
        }

        $relaciones = $this->relaciones_externas(modelo_uuid_ext: $modelo_uuid_ext,row_relacion:  $row_relacion);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener relacion', data: $relaciones);
        }

        foreach ($relaciones as $relacion){
            $relacionados[$cat_sat_tipo_relacion_codigo][] = $relacion['fc_uuid_uuid'];
        }
        return $relacionados;
    }

    public function alta_bd(array $keys_integra_ds = array('descripcion')): array|stdClass
    {

        $keys = array($this->modelo_entidad->key_id, 'cat_sat_tipo_relacion_id');
        $valida = $this->validacion->valida_ids(keys: $keys,registro:  $this->registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar registro', data: $valida);
        }

        $row_entidad = $this->modelo_entidad->registro(registro_id: $this->registro[$this->modelo_entidad->key_id]);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al al obtener row_entidad', data: $row_entidad);
        }


        $permite_transaccion = $this->modelo_entidad->verifica_permite_transaccion(modelo_etapa: $this->modelo_etapa,
            registro_id: $this->registro[$this->modelo_entidad->key_id]);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error verificar transaccion', data: $permite_transaccion);
        }

        $cat_sat_tipo_relacion = (new cat_sat_tipo_relacion(link: $this->link))->registro(
            registro_id: $this->registro['cat_sat_tipo_relacion_id']);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al al obtener cat_sat_tipo_relacion', data: $cat_sat_tipo_relacion);
        }

        $cat_sat_tipo_relacion_codigo = trim($cat_sat_tipo_relacion['cat_sat_tipo_relacion_codigo']);

        if(!in_array($cat_sat_tipo_relacion_codigo, $this->codigos_rel_permitidos)){
            return $this->error->error(mensaje: 'Error tipo de relacion invalida', data: $cat_sat_tipo_relacion_codigo);
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

        $permite_transaccion = $this->permite_transaccion(registro_id: $id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error verificar transaccion', data: $permite_transaccion);
        }

        $filtro[$this->key_filtro_id] = $id;
        $del = $this->modelo_relacionada->elimina_con_filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al eliminar',data:  $del);
        }

        $filtro[$this->key_filtro_id] = $id;
        $del = $this->modelo_uuid_ext->elimina_con_filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al eliminar',data:  $del);
        }

        $r_elimina_bd = parent::elimina_bd(id: $id); // TODO: Change the autogenerated stub
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


    /**
     * Genera los elementos relacionados de una entidad de tipo factura
     * @param _relacionada $modelo_relacionada Modelo de relacion directa
     * @param _uuid_ext $modelo_uuid_ext Modelo uuid directo
     * @param string $name_entidad Nombre de la entidad
     * @param array $relacionados Elementos previamente relacionados
     * @param array $row_relacion Registro nuevo de relacion a integrar
     * @return array
     */
    private function genera_relacionado(_relacionada $modelo_relacionada, _uuid_ext $modelo_uuid_ext,
                                        string $name_entidad, array $relacionados, array $row_relacion): array
    {
        $cat_sat_tipo_relacion_codigo = $row_relacion['cat_sat_tipo_relacion_codigo'];
        $relacionados[$cat_sat_tipo_relacion_codigo] = array();

        $fc_rows_relacionadas = $this->facturas_relacionadas(modelo_relacionada: $modelo_relacionada,
            row_relacion: $row_relacion);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener fc_rows_relacionadas', data: $fc_rows_relacionadas);
        }

        $relacionados = $this->integra_relacionados(cat_sat_tipo_relacion_codigo: $cat_sat_tipo_relacion_codigo,
            fc_rows_relacionadas: $fc_rows_relacionadas, name_entidad: $name_entidad, relacionados: $relacionados);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integrar relacionado', data: $relacionados);
        }

        $filtro[$this->key_filtro_id] = $row_relacion[$this->key_id];



        $relacionados = $this->ajusta_relacionados(cat_sat_tipo_relacion_codigo: $cat_sat_tipo_relacion_codigo,
            modelo_uuid_ext: $modelo_uuid_ext,relacionados:  $relacionados,row_relacion:  $row_relacion);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al ajustar relacionados', data: $relacionados);
        }


        if($name_entidad === 'fc_nota_credito'){
            $relacionados = $this->integra_relacion_nc(cat_sat_tipo_relacion_codigo: $cat_sat_tipo_relacion_codigo,
                filtro:  $filtro,relacionados:  $relacionados);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al obtener relacion', data: $relacionados);
            }

        }

        return $relacionados;
    }

    /**
     * @param _transacciones_fc $modelo_entidad
     * @param _relacionada $modelo_relacionada
     * @param _uuid_ext $modelo_uuid_ext
     * @param int $registro_entidad_id
     * @return array
     */
    final public function get_relaciones(_transacciones_fc $modelo_entidad, _relacionada $modelo_relacionada,
                                         _uuid_ext $modelo_uuid_ext, int $registro_entidad_id): array
    {

        $row_relaciones = $this->relaciones(modelo_entidad: $modelo_entidad, registro_entidad_id: $registro_entidad_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener row_relaciones', data: $row_relaciones);
        }

        $relacionados = $this->relacionados(modelo_relacionada: $modelo_relacionada, modelo_uuid_ext: $modelo_uuid_ext,
            name_entidad: $modelo_entidad->tabla, row_relaciones: $row_relaciones);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integrar relacionado', data: $relacionados);
        }



        return $relacionados;

    }

    /**
     * Integra las relaciones de notas de Credito
     * @param string $cat_sat_tipo_relacion_codigo codigo de tipo de relacion del SAT
     * @param array $filtro filtro para obtener relaciones aplicadas
     * @param array $relacionados Elementos relacionados
     * @return array
     * @version 10.28.0
     */
    private function integra_relacion_nc(string $cat_sat_tipo_relacion_codigo, array $filtro, array $relacionados): array
    {
        $cat_sat_tipo_relacion_codigo = trim($cat_sat_tipo_relacion_codigo);
        if($cat_sat_tipo_relacion_codigo === ''){
            return $this->error->error(mensaje: 'Error cat_sat_tipo_relacion_codigo esta vacio',
                data: $cat_sat_tipo_relacion_codigo);
        }
        $r_fc_nc = (new fc_nc_rel(link: $this->link))->filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener relacion', data: $r_fc_nc);
        }

        $relaciones = $r_fc_nc->registros;

        foreach ($relaciones as $relacion){
            $relacionados[$cat_sat_tipo_relacion_codigo][] = $relacion['fc_factura_uuid'];
        }
        return $relacionados;
    }

    /**
     * Integra una relacion de tipo factura
     * @param string $cat_sat_tipo_relacion_codigo Codigo sat de relacion
     * @param string $name_entidad Nombre de la entidad base factura, complemento pago, nota credito
     * @param array $relacionados Conjunto de elementos relacionados
     * @param array $row_relacionada Registro base de relacion
     * @return array
     * @version 8.88.4
     */
    private function integra_relacionado(string $cat_sat_tipo_relacion_codigo, string $name_entidad,
                                         array $relacionados, array $row_relacionada): array
    {
        $cat_sat_tipo_relacion_codigo = trim($cat_sat_tipo_relacion_codigo);
        if($cat_sat_tipo_relacion_codigo === ''){
            return $this->error->error(mensaje: 'Error al validar cat_sat_tipo_relacion_codigo esta vacio',
                data: $cat_sat_tipo_relacion_codigo);
        }

        $name_entidad = trim($name_entidad);
        if($name_entidad === ''){
            return $this->error->error(mensaje: 'Error name_entidad esta vacio', data: $name_entidad);
        }

        $keys = array($name_entidad.'_uuid');
        $valida = $this->validacion->valida_existencia_keys(keys: $keys,registro: $row_relacionada);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar row_relacionada', data: $valida);
        }
        $relacionados[$cat_sat_tipo_relacion_codigo][] = $row_relacionada[$name_entidad.'_uuid'];
        return $relacionados;
    }

    /**
     * Integra los cfdis relacionados al conjunto general de relaciones
     * @param string $cat_sat_tipo_relacion_codigo Tipo de relacion codigo
     * @param array $fc_rows_relacionadas Registros de facturas previamenete cargadas
     * @param string $name_entidad Nombre de proceso en ejecucion factura, complemento de pago nota de credito
     * @param array $relacionados Elementos previamenete relacionados
     * @return array
     * @version 9.24.3
     */
    private function integra_relacionados(string $cat_sat_tipo_relacion_codigo, array $fc_rows_relacionadas,
                                          string $name_entidad, array $relacionados): array
    {
        $cat_sat_tipo_relacion_codigo = trim($cat_sat_tipo_relacion_codigo);
        if($cat_sat_tipo_relacion_codigo === ''){
            return $this->error->error(mensaje: 'Error al validar cat_sat_tipo_relacion_codigo esta vacio',
                data: $cat_sat_tipo_relacion_codigo);
        }
        $name_entidad = trim($name_entidad);
        if($name_entidad === ''){
            return $this->error->error(mensaje: 'Error name_entidad esta vacio', data: $name_entidad);
        }

        foreach ($fc_rows_relacionadas as $row_relacionada){

            /**
             * REFACTORIZAR
             */
            if(!is_array($row_relacionada)){
                return $this->error->error(mensaje: 'Error row_relacionada no es un array', data: $row_relacionada);
            }

            $keys = array($name_entidad.'_uuid');
            $valida = $this->validacion->valida_existencia_keys(keys: $keys,registro: $row_relacionada);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al validar row_relacionada', data: $valida);
            }

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
        $permite_transaccion = $this->permite_transaccion(registro_id: $id);
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
     * Valida si se permite una transaccion de actualizacion dependiendo la etapa
     * @param int $registro_id Registro en proceso
     * @return array|true
     */
    private function permite_transaccion(int $registro_id): bool|array
    {
        $fc_relacion = $this->registro(registro_id: $registro_id, retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener relacion', data: $fc_relacion);
        }
        $key_id_entidad = $this->modelo_entidad->key_id;

        $permite_transaccion = $this->modelo_entidad->verifica_permite_transaccion(modelo_etapa: $this->modelo_etapa,
            registro_id: $fc_relacion->$key_id_entidad);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error verificar transaccion', data: $permite_transaccion);
        }
        return true;
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

    /**
     * Integra las relaciones externas de documentos
     * @param _uuid_ext $modelo_uuid_ext Modelo de tipo uuid externo
     * @param array $row_relacion Registro de relacion
     * @return array
     * @version 10.109.3
     */
    private function relaciones_externas(_uuid_ext $modelo_uuid_ext, array $row_relacion): array
    {

        $valida = $this->valida_data_rel(row_relacion: $row_relacion);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error valida row relacion', data: $valida);
        }

        $filtro[$this->key_filtro_id] = $row_relacion[$this->key_id];

        $r_fc_uuid_fc = $modelo_uuid_ext->filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener relacion', data: $r_fc_uuid_fc);
        }

        return $r_fc_uuid_fc->registros;
    }

    /**
     * @param _relacionada $modelo_relacionada
     * @param _uuid_ext $modelo_uuid_ext
     * @param string $name_entidad
     * @param array $row_relaciones
     * @return array
     */
    private function relacionados(_relacionada $modelo_relacionada, _uuid_ext $modelo_uuid_ext,
                                  string $name_entidad, array $row_relaciones): array
    {
        $relacionados = array();
        foreach ($row_relaciones as $row_relacion){

            $relacionados = $this->genera_relacionado(modelo_relacionada: $modelo_relacionada,
                modelo_uuid_ext: $modelo_uuid_ext, name_entidad: $name_entidad, relacionados: $relacionados,
                row_relacion: $row_relacion);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al integrar relacionado', data: $relacionados);
            }
        }


        return $relacionados;
    }

    private function valida_data_rel(array $row_relacion){
        $this->key_filtro_id = trim($this->key_filtro_id);
        if($this->key_filtro_id === ''){
            return $this->error->error(mensaje: 'Error this->key_filtro_id esta vacio', data: $this->key_filtro_id);
        }

        $this->key_id = trim($this->key_id);
        if($this->key_id === ''){
            return $this->error->error(mensaje: 'Error this->key_id esta vacio', data: $this->key_id);
        }

        $keys = array($this->key_id);
        $valida = $this->validacion->valida_ids(keys: $keys,registro:  $row_relacion);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error valida row relacion', data: $valida);
        }
        return true;
    }

}
