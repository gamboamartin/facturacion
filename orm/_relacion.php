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
     * REG
     * Ajusta y agrega relaciones externas a un conjunto de relaciones existentes.
     *
     * Esta función valida la estructura de `$row_relacion` y luego obtiene las relaciones externas
     * a través del modelo `_uuid_ext`. Posteriormente, integra las relaciones encontradas en el
     * array `$relacionados`, organizándolas bajo el código SAT de relación correspondiente.
     *
     * @param string $cat_sat_tipo_relacion_codigo Código SAT del tipo de relación. No debe estar vacío.
     * @param _uuid_ext $modelo_uuid_ext Instancia del modelo que maneja las relaciones externas
     *                                   de documentos por UUID.
     * @param array $relacionados Array de relaciones previas, que será actualizado con los nuevos datos integrados.
     * @param array $row_relacion Registro base que contiene la información necesaria para obtener relaciones externas.
     *
     * @return array Retorna el array de relaciones actualizado con los nuevos registros relacionados.
     *
     * @throws array Si `$row_relacion` no pasa la validación de `valida_data_rel()`, devuelve un array de error.
     * @throws array Si ocurre un error al obtener relaciones externas, devuelve un array de error con los detalles.
     *
     * @example
     * // Ejemplo 1: Ajustar relaciones externas para una factura
     * $modelo_uuid_ext = new _uuid_ext();
     * $relacionados = [];
     * $row_relacion = ['id' => 10, 'key_id' => 1001];
     *
     * $resultado = $obj->_relacion->ajusta_relacionados(
     *     cat_sat_tipo_relacion_codigo: '01',
     *     modelo_uuid_ext: $modelo_uuid_ext,
     *     relacionados: $relacionados,
     *     row_relacion: $row_relacion
     * );
     * print_r($resultado);
     *
     * @example
     * // Ejemplo 2: Manejo de error si los datos de relación son inválidos
     * $modelo_uuid_ext = new _uuid_ext();
     * $relacionados = [];
     * $row_relacion = []; // Falta la clave `key_id`
     *
     * $resultado = $obj->_relacion->ajusta_relacionados(
     *     cat_sat_tipo_relacion_codigo: '01',
     *     modelo_uuid_ext: $modelo_uuid_ext,
     *     relacionados: $relacionados,
     *     row_relacion: $row_relacion
     * );
     * if (isset($resultado['error'])) {
     *     echo "Error: " . $resultado['mensaje'];
     * }
     */
    private function ajusta_relacionados(
        string $cat_sat_tipo_relacion_codigo,
        _uuid_ext $modelo_uuid_ext,
        array $relacionados,
        array $row_relacion
    ): array {
        $valida = $this->valida_data_rel(row_relacion: $row_relacion);
        if (errores::$error) {
            return $this->error->error(
                mensaje: 'Error valida row relacion',
                data: $valida
            );
        }

        $relaciones = $this->relaciones_externas(modelo_uuid_ext: $modelo_uuid_ext, row_relacion: $row_relacion);
        if (errores::$error) {
            return $this->error->error(
                mensaje: 'Error al obtener relacion',
                data: $relaciones
            );
        }

        foreach ($relaciones as $relacion) {
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
     * REG
     * Obtiene las facturas relacionadas a un registro específico.
     *
     * Esta función busca las facturas relacionadas en la base de datos usando un modelo específico.
     * Primero valida que el array `$row_relacion` contenga la clave necesaria antes de realizar la consulta.
     *
     * @param _relacionada $modelo_relacionada Instancia del modelo que maneja las relaciones entre facturas y otros documentos.
     * @param array $row_relacion Datos del registro base que contiene la información de la relación. Debe incluir `$this->key_id`.
     *
     * @return array Retorna un array con los registros de las facturas relacionadas.
     *
     * @throws array Si `$row_relacion` no contiene las claves necesarias, devuelve un array de error en el formato `errores::$error`.
     * @throws array Si ocurre un error en la consulta de facturas relacionadas, devuelve un array de error con el mensaje y los detalles del fallo.
     *
     * @example
     * // Ejemplo 1: Obtener las facturas relacionadas a un registro de relación
     * $modelo_relacionada = new _relacionada();
     * $row_relacion = ['id' => 10, 'key_id' => 1001];
     *
     * $facturas = $obj->_relacion->facturas_relacionadas(
     *     modelo_relacionada: $modelo_relacionada,
     *     row_relacion: $row_relacion
     * );
     * print_r($facturas);
     *
     * @example
     * // Ejemplo 2: Manejo de error si los datos de relación no son válidos
     * $modelo_relacionada = new _relacionada();
     * $row_relacion = []; // Faltan datos clave
     *
     * $facturas = $obj->_relacion->facturas_relacionadas(
     *     modelo_relacionada: $modelo_relacionada,
     *     row_relacion: $row_relacion
     * );
     * if (isset($facturas['error'])) {
     *     echo "Error: " . $facturas['mensaje'];
     * }
     */
    final public function facturas_relacionadas(_relacionada $modelo_relacionada, array $row_relacion): array
    {
        $keys = array($this->key_id);
        $valida = $this->validacion->valida_ids(keys: $keys, registro: $row_relacion);
        if (errores::$error) {
            return $this->error->error(
                mensaje: 'Error al validar $row_relacion',
                data: $valida
            );
        }

        $filtro = array();
        $filtro[$this->key_filtro_id] = $row_relacion[$this->key_id];

        $r_fc_factura_relacionada = $modelo_relacionada->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(
                mensaje: 'Error al obtener r_fc_factura_relacionada',
                data: $r_fc_factura_relacionada
            );
        }

        return $r_fc_factura_relacionada->registros;
    }



    /**
     * REG
     * Genera las relaciones de una entidad con base en los datos proporcionados.
     *
     * Esta función obtiene y procesa las relaciones de una entidad (`$name_entidad`) asegurando que
     * los datos sean válidos. Se valida la estructura de `$row_relacion`, se extrae el código SAT
     * de la relación y se obtienen las facturas relacionadas. Posteriormente, se ajustan e integran
     * las relaciones a `$relacionados`.
     *
     * @param _relacionada $modelo_relacionada Instancia del modelo que maneja las relaciones entre documentos.
     * @param _uuid_ext $modelo_uuid_ext Instancia del modelo para manejar UUID externos de documentos.
     * @param string $name_entidad Nombre de la entidad base (factura, complemento de pago, nota de crédito, etc.). No debe estar vacío.
     * @param array $relacionados Conjunto de relaciones previas que serán actualizadas con las nuevas relaciones generadas.
     * @param array $row_relacion Registro base que contiene la información de la relación.
     *                            Debe incluir las claves `key_id` y `cat_sat_tipo_relacion_codigo`.
     *
     * @return array Retorna el array de relaciones actualizado con las nuevas asociaciones generadas.
     *
     * @throws array Si `$row_relacion` no pasa la validación de claves obligatorias (`key_id`, `cat_sat_tipo_relacion_codigo`),
     *               devuelve un array de error en el formato `errores::$error`.
     * @throws array Si `$name_entidad` está vacío, devuelve un array de error con los detalles.
     * @throws array Si ocurre un error al obtener facturas relacionadas, devuelve un array de error.
     * @throws array Si ocurre un error al ajustar relaciones externas, devuelve un array de error.
     * @throws array Si ocurre un error al integrar relaciones de notas de crédito, devuelve un array de error.
     *
     * @example
     * // Ejemplo 1: Generar relaciones de una factura
     * $modelo_relacionada = new _relacionada();
     * $modelo_uuid_ext = new _uuid_ext();
     * $relacionados = [];
     * $row_relacion = ['id' => 10, 'key_id' => 1001, 'cat_sat_tipo_relacion_codigo' => '01'];
     *
     * $resultado = $obj->_relacion->genera_relacionado(
     *     modelo_relacionada: $modelo_relacionada,
     *     modelo_uuid_ext: $modelo_uuid_ext,
     *     name_entidad: 'fc_factura',
     *     relacionados: $relacionados,
     *     row_relacion: $row_relacion
     * );
     * print_r($resultado);
     *
     * @example
     * // Ejemplo 2: Manejo de error si los datos de relación son inválidos
     * $modelo_relacionada = new _relacionada();
     * $modelo_uuid_ext = new _uuid_ext();
     * $relacionados = [];
     * $row_relacion = ['id' => 10]; // Falta `cat_sat_tipo_relacion_codigo`
     *
     * $resultado = $obj->_relacion->genera_relacionado(
     *     modelo_relacionada: $modelo_relacionada,
     *     modelo_uuid_ext: $modelo_uuid_ext,
     *     name_entidad: 'fc_factura',
     *     relacionados: $relacionados,
     *     row_relacion: $row_relacion
     * );
     * if (isset($resultado['error'])) {
     *     echo "Error: " . $resultado['mensaje'];
     * }
     */
    private function genera_relacionado(
        _relacionada $modelo_relacionada,
        _uuid_ext $modelo_uuid_ext,
        string $name_entidad,
        array $relacionados,
        array $row_relacion
    ): array {

        $valida = $this->valida_base(name_entidad: $name_entidad,row_relacion: $row_relacion);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar row_relacion', data: $valida);
        }

        $cat_sat_tipo_relacion_codigo = $row_relacion['cat_sat_tipo_relacion_codigo'];
        $relacionados[$cat_sat_tipo_relacion_codigo] = array();

        $fc_rows_relacionadas = $this->facturas_relacionadas(
            modelo_relacionada: $modelo_relacionada,
            row_relacion: $row_relacion
        );
        if (errores::$error) {
            return $this->error->error(
                mensaje: 'Error al obtener fc_rows_relacionadas',
                data: $fc_rows_relacionadas
            );
        }

        $relacionados = $this->integra_relacionados(
            cat_sat_tipo_relacion_codigo: $cat_sat_tipo_relacion_codigo,
            fc_rows_relacionadas: $fc_rows_relacionadas,
            name_entidad: $name_entidad,
            relacionados: $relacionados
        );
        if (errores::$error) {
            return $this->error->error(
                mensaje: 'Error al integrar relacionado',
                data: $relacionados
            );
        }

        $filtro[$this->key_filtro_id] = $row_relacion[$this->key_id];

        $relacionados = $this->ajusta_relacionados(
            cat_sat_tipo_relacion_codigo: $cat_sat_tipo_relacion_codigo,
            modelo_uuid_ext: $modelo_uuid_ext,
            relacionados: $relacionados,
            row_relacion: $row_relacion
        );
        if (errores::$error) {
            return $this->error->error(
                mensaje: 'Error al ajustar relacionados',
                data: $relacionados
            );
        }

        if ($name_entidad === 'fc_nota_credito') {
            $relacionados = $this->integra_relacion_nc(
                cat_sat_tipo_relacion_codigo: $cat_sat_tipo_relacion_codigo,
                filtro: $filtro,
                relacionados: $relacionados
            );
            if (errores::$error) {
                return $this->error->error(
                    mensaje: 'Error al obtener relacion',
                    data: $relacionados
                );
            }
        }

        return $relacionados;
    }


    /**
     * REG
     * Obtiene las relaciones de una entidad específica y genera un conjunto de datos relacionados.
     *
     * Esta función obtiene las relaciones de una entidad a partir de su identificador (`$registro_entidad_id`),
     * valida los datos y luego genera el conjunto de relaciones mediante `relacionados()`.
     * Se realizan validaciones para asegurar que `$registro_entidad_id` sea mayor a 0 antes de proceder.
     *
     * @param _transacciones_fc $modelo_entidad Instancia del modelo de entidad de transacciones (factura, complemento de pago, etc.).
     * @param _relacionada $modelo_relacionada Instancia del modelo que maneja las relaciones entre documentos.
     * @param _uuid_ext $modelo_uuid_ext Instancia del modelo que maneja UUIDs externos de documentos.
     * @param int $registro_entidad_id Identificador único del registro de la entidad. Debe ser mayor a 0.
     *
     * @return array Retorna un array con los registros de las relaciones obtenidas y generadas.
     *
     * @throws array Si `$registro_entidad_id` es menor o igual a 0, devuelve un array de error en el formato `errores::$error`.
     * @throws array Si ocurre un error al obtener las relaciones base, devuelve un array de error con los detalles.
     * @throws array Si ocurre un error al generar relaciones, devuelve un array de error con los detalles.
     *
     * @example
     * // Ejemplo 1: Obtener relaciones de una factura con ID 100
     * $modelo_entidad = new _transacciones_fc();
     * $modelo_relacionada = new _relacionada();
     * $modelo_uuid_ext = new _uuid_ext();
     *
     * $relaciones = $obj->_relacion->get_relaciones(
     *     modelo_entidad: $modelo_entidad,
     *     modelo_relacionada: $modelo_relacionada,
     *     modelo_uuid_ext: $modelo_uuid_ext,
     *     registro_entidad_id: 100
     * );
     * print_r($relaciones);
     *
     * @example
     * // Ejemplo 2: Manejo de error si el ID es inválido
     * $modelo_entidad = new _transacciones_fc();
     * $modelo_relacionada = new _relacionada();
     * $modelo_uuid_ext = new _uuid_ext();
     *
     * $relaciones = $obj->_relacion->get_relaciones(
     *     modelo_entidad: $modelo_entidad,
     *     modelo_relacionada: $modelo_relacionada,
     *     modelo_uuid_ext: $modelo_uuid_ext,
     *     registro_entidad_id: 0
     * );
     * if (isset($relaciones['error'])) {
     *     echo "Error: " . $relaciones['mensaje'];
     * }
     */
    final public function get_relaciones(
        _transacciones_fc $modelo_entidad,
        _relacionada $modelo_relacionada,
        _uuid_ext $modelo_uuid_ext,
        int $registro_entidad_id
    ): array {
        if ($registro_entidad_id <= 0) {
            return $this->error->error(
                mensaje: 'Error $registro_entidad_id debe ser mayor a 0',
                data: $registro_entidad_id,
                es_final: true
            );
        }

        $row_relaciones = $this->relaciones(
            modelo_entidad: $modelo_entidad,
            registro_entidad_id: $registro_entidad_id
        );
        if (errores::$error) {
            return $this->error->error(
                mensaje: 'Error al obtener row_relaciones',
                data: $row_relaciones
            );
        }

        $relacionados = $this->relacionados(
            modelo_relacionada: $modelo_relacionada,
            modelo_uuid_ext: $modelo_uuid_ext,
            name_entidad: $modelo_entidad->tabla,
            row_relaciones: $row_relaciones
        );
        if (errores::$error) {
            return $this->error->error(
                mensaje: 'Error al integrar relacionado',
                data: $relacionados
            );
        }

        return $relacionados;
    }


    /**
     * REG
     * Integra relaciones de notas de crédito en un conjunto de relaciones existentes.
     *
     * Esta función busca en la base de datos las notas de crédito relacionadas con una entidad específica
     * utilizando un filtro de búsqueda. Posteriormente, agrega las relaciones encontradas al array `$relacionados`,
     * organizándolas bajo el código SAT de relación correspondiente.
     *
     * @param string $cat_sat_tipo_relacion_codigo Código SAT del tipo de relación. No debe estar vacío.
     * @param array $filtro Filtros aplicados en la búsqueda de notas de crédito relacionadas.
     * @param array $relacionados Array de relaciones previas, que será actualizado con los nuevos datos integrados.
     *
     * @return array Retorna el array de relaciones actualizado con las notas de crédito relacionadas.
     *
     * @throws array Si `$cat_sat_tipo_relacion_codigo` está vacío, devuelve un array de error en el formato `errores::$error`.
     * @throws array Si ocurre un error al obtener relaciones de notas de crédito, devuelve un array de error con los detalles.
     *
     * @example
     * // Ejemplo 1: Integrar relaciones de notas de crédito en una factura
     * $filtro = ['fc_factura_id' => 123];
     * $relacionados = [];
     *
     * $resultado = $obj->_relacion->integra_relacion_nc(
     *     cat_sat_tipo_relacion_codigo: '04',
     *     filtro: $filtro,
     *     relacionados: $relacionados
     * );
     * print_r($resultado);
     *
     * @example
     * // Ejemplo 2: Manejo de error si el código de relación está vacío
     * $filtro = ['fc_factura_id' => 123];
     * $relacionados = [];
     *
     * $resultado = $obj->_relacion->integra_relacion_nc(
     *     cat_sat_tipo_relacion_codigo: '',
     *     filtro: $filtro,
     *     relacionados: $relacionados
     * );
     * if (isset($resultado['error'])) {
     *     echo "Error: " . $resultado['mensaje'];
     * }
     */
    private function integra_relacion_nc(
        string $cat_sat_tipo_relacion_codigo,
        array $filtro,
        array $relacionados
    ): array {
        $cat_sat_tipo_relacion_codigo = trim($cat_sat_tipo_relacion_codigo);
        if ($cat_sat_tipo_relacion_codigo === '') {
            return $this->error->error(
                mensaje: 'Error cat_sat_tipo_relacion_codigo esta vacio',
                data: $cat_sat_tipo_relacion_codigo
            );
        }

        $r_fc_nc = (new fc_nc_rel(link: $this->link))->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(
                mensaje: 'Error al obtener relacion',
                data: $r_fc_nc
            );
        }

        $relaciones = $r_fc_nc->registros;

        foreach ($relaciones as $relacion) {
            $relacionados[$cat_sat_tipo_relacion_codigo][] = $relacion['fc_factura_uuid'];
        }

        return $relacionados;
    }


    /**
     * REG
     * Integra una relación de tipo factura con una entidad específica.
     *
     * Esta función agrega una relación entre una factura y una entidad específica,
     * asegurándose de que los datos sean válidos antes de integrarlos en el array de relaciones.
     *
     * Se validan los parámetros `$cat_sat_tipo_relacion_codigo` y `$name_entidad` para evitar valores vacíos.
     * También se verifica que `$row_relacionada` contenga la clave necesaria antes de agregar la relación.
     *
     * @param string $cat_sat_tipo_relacion_codigo Código SAT de la relación. No debe estar vacío.
     * @param string $name_entidad Nombre de la entidad base (factura, complemento de pago, nota de crédito, etc.). No debe estar vacío.
     * @param array $relacionados Conjunto de elementos relacionados hasta el momento.
     * @param array $row_relacionada Registro base que contiene la información de la relación. Debe incluir la clave `{$name_entidad}_uuid`.
     *
     * @return array Retorna el array de relaciones actualizado con la nueva relación integrada.
     *
     * @throws array Si `$cat_sat_tipo_relacion_codigo` está vacío, devuelve un array de error en el formato `errores::$error`.
     * @throws array Si `$name_entidad` está vacío, devuelve un array de error en el formato `errores::$error`.
     * @throws array Si `$row_relacionada` no contiene la clave `{$name_entidad}_uuid`, devuelve un array de error con los detalles del fallo.
     *
     * @example
     * // Ejemplo 1: Integrar una relación de factura con una nota de crédito
     * $relacionados = [];
     * $row_relacionada = ['fc_nota_credito_uuid' => '123e4567-e89b-12d3-a456-426614174000'];
     *
     * $resultado = $obj->_relacion->integra_relacionado(
     *     cat_sat_tipo_relacion_codigo: '01',
     *     name_entidad: 'fc_nota_credito',
     *     relacionados: $relacionados,
     *     row_relacionada: $row_relacionada
     * );
     * print_r($resultado);
     *
     * @example
     * // Ejemplo 2: Manejo de error si el código de relación está vacío
     * $resultado = $obj->_relacion->integra_relacionado(
     *     cat_sat_tipo_relacion_codigo: '',
     *     name_entidad: 'fc_factura',
     *     relacionados: [],
     *     row_relacionada: ['fc_factura_uuid' => 'abcd1234']
     * );
     * if (isset($resultado['error'])) {
     *     echo "Error: " . $resultado['mensaje'];
     * }
     */
    private function integra_relacionado(string $cat_sat_tipo_relacion_codigo, string $name_entidad,
                                         array $relacionados, array $row_relacionada): array
    {
        $cat_sat_tipo_relacion_codigo = trim($cat_sat_tipo_relacion_codigo);
        if($cat_sat_tipo_relacion_codigo === ''){
            return $this->error->error(mensaje: 'Error al validar cat_sat_tipo_relacion_codigo esta vacio',
                data: $cat_sat_tipo_relacion_codigo, es_final: true);
        }

        $name_entidad = trim($name_entidad);
        if($name_entidad === ''){
            return $this->error->error(mensaje: 'Error name_entidad esta vacio', data: $name_entidad, es_final: true);
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
     * REG
     * Integra múltiples relaciones de una entidad con base en registros previos.
     *
     * Esta función se encarga de procesar un conjunto de registros relacionados (`$fc_rows_relacionadas`)
     * y agregarlos a la estructura `$relacionados` si cumplen con los criterios de validación.
     *
     * Se valida que el código SAT de la relación y el nombre de la entidad no estén vacíos.
     * También se verifica que cada registro en `$fc_rows_relacionadas` sea un array y contenga la clave `{name_entidad}_uuid`.
     *
     * @param string $cat_sat_tipo_relacion_codigo Código SAT del tipo de relación. No debe estar vacío.
     * @param array $fc_rows_relacionadas Conjunto de registros previos que se desean integrar a `$relacionados`.
     * @param string $name_entidad Nombre de la entidad (factura, complemento de pago, nota de crédito, etc.). No debe estar vacío.
     * @param array $relacionados Array de relaciones previas, que será actualizado con los nuevos datos integrados.
     *
     * @return array Retorna el array de relaciones actualizado con los nuevos registros relacionados.
     *
     * @throws array Si `$cat_sat_tipo_relacion_codigo` está vacío, devuelve un array de error en el formato `errores::$error`.
     * @throws array Si `$name_entidad` está vacío, devuelve un array de error en el formato `errores::$error`.
     * @throws array Si algún registro de `$fc_rows_relacionadas` no es un array, devuelve un array de error con los detalles del fallo.
     * @throws array Si algún registro de `$fc_rows_relacionadas` no contiene la clave `{name_entidad}_uuid`, devuelve un array de error.
     * @throws array Si ocurre un error al integrar una relación, devuelve un array de error con el mensaje y los detalles del fallo.
     *
     * @example
     * // Ejemplo 1: Integrar múltiples relaciones de facturas
     * $relacionados = [];
     * $fc_rows_relacionadas = [
     *     ['fc_factura_uuid' => '123e4567-e89b-12d3-a456-426614174000'],
     *     ['fc_factura_uuid' => '789e0123-e89b-12d3-a456-426614174111']
     * ];
     *
     * $resultado = $obj->_relacion->integra_relacionados(
     *     cat_sat_tipo_relacion_codigo: '01',
     *     fc_rows_relacionadas: $fc_rows_relacionadas,
     *     name_entidad: 'fc_factura',
     *     relacionados: $relacionados
     * );
     * print_r($resultado);
     *
     * @example
     * // Ejemplo 2: Manejo de error si el código SAT está vacío
     * $resultado = $obj->_relacion->integra_relacionados(
     *     cat_sat_tipo_relacion_codigo: '',
     *     fc_rows_relacionadas: $fc_rows_relacionadas,
     *     name_entidad: 'fc_factura',
     *     relacionados: []
     * );
     * if (isset($resultado['error'])) {
     *     echo "Error: " . $resultado['mensaje'];
     * }
     */
    private function integra_relacionados(string $cat_sat_tipo_relacion_codigo, array $fc_rows_relacionadas,
                                          string $name_entidad, array $relacionados): array
    {
        $cat_sat_tipo_relacion_codigo = trim($cat_sat_tipo_relacion_codigo);
        if($cat_sat_tipo_relacion_codigo === ''){
            return $this->error->error(mensaje: 'Error al validar cat_sat_tipo_relacion_codigo esta vacio',
                data: $cat_sat_tipo_relacion_codigo, es_final: true);
        }
        $name_entidad = trim($name_entidad);
        if($name_entidad === ''){
            return $this->error->error(mensaje: 'Error name_entidad esta vacio', data: $name_entidad,
                es_final: true);
        }

        foreach ($fc_rows_relacionadas as $row_relacionada){

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
     * REG
     * Obtiene las relaciones asociadas a una entidad específica dentro del sistema de facturación.
     *
     * Esta función consulta las relaciones existentes de una entidad basada en su identificador único.
     * Se realiza una validación del `registro_entidad_id` antes de ejecutar la consulta.
     *
     * @param _transacciones_fc $modelo_entidad Instancia del modelo de entidad de transacciones (factura, complemento de pago, nota de crédito, etc.).
     * @param int $registro_entidad_id Identificador único del registro de la entidad. Debe ser mayor a 0.
     *
     * @return array Retorna un array con los registros de las relaciones encontradas.
     *
     * @throws array Si `$registro_entidad_id` es menor o igual a 0, devuelve un array de error en el formato `errores::$error`.
     * @throws array Si ocurre un error al ejecutar la consulta de relaciones, devuelve un array de error con el mensaje y los detalles del fallo.
     *
     * @example
     * // Ejemplo 1: Obtener relaciones de una factura con ID 100
     * $modelo_factura = new _transacciones_fc();
     * $relaciones = $obj->_relacion->relaciones(modelo_entidad: $modelo_factura, registro_entidad_id: 100);
     * print_r($relaciones);
     *
     * @example
     * // Ejemplo 2: Manejo de error si el ID es inválido
     * $modelo_factura = new _transacciones_fc();
     * $relaciones = $obj->_relacion->relaciones(modelo_entidad: $modelo_factura, registro_entidad_id: 0);
     * if (isset($relaciones['error'])) {
     *     echo "Error: " . $relaciones['mensaje'];
     * }
     */
    final public function relaciones(_transacciones_fc $modelo_entidad, int $registro_entidad_id): array
    {
        if ($registro_entidad_id <= 0) {
            return $this->error->error(
                mensaje: 'Error $registro_entidad_id debe ser mayor a 0',
                data: $registro_entidad_id,
                es_final: true
            );
        }

        $filtro = array();
        $filtro[$modelo_entidad->key_filtro_id] = $registro_entidad_id;

        $r_fc_relacion = $this->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(
                mensaje: 'Error al obtener relacion',
                data: $r_fc_relacion
            );
        }

        return $r_fc_relacion->registros;
    }


    /**
     * REG
     * Obtiene las relaciones externas de un documento a partir de su UUID.
     *
     * Esta función busca en la base de datos las relaciones externas asociadas a un documento
     * utilizando el modelo `_uuid_ext`. Antes de realizar la consulta, se valida que los datos
     * de `$row_relacion` sean correctos mediante `valida_data_rel()`.
     *
     * @param _uuid_ext $modelo_uuid_ext Instancia del modelo que maneja las relaciones externas
     *                                   de documentos por UUID.
     * @param array $row_relacion Registro de relación que contiene la información base.
     *                            Debe incluir la clave `$this->key_id`.
     *
     * @return array Retorna un array con los registros de las relaciones externas encontradas.
     *
     * @throws array Si `$row_relacion` no pasa la validación de `valida_data_rel()`, devuelve un array de error.
     * @throws array Si ocurre un error en la consulta de relaciones externas, devuelve un array de error con los detalles.
     *
     * @example
     * // Ejemplo 1: Obtener relaciones externas de un documento con UUID
     * $modelo_uuid_ext = new _uuid_ext();
     * $row_relacion = ['id' => 10, 'key_id' => 1001];
     *
     * $relaciones = $obj->_relacion->relaciones_externas(
     *     modelo_uuid_ext: $modelo_uuid_ext,
     *     row_relacion: $row_relacion
     * );
     * print_r($relaciones);
     *
     * @example
     * // Ejemplo 2: Manejo de error si los datos de relación son inválidos
     * $modelo_uuid_ext = new _uuid_ext();
     * $row_relacion = []; // Falta la clave `key_id`
     *
     * $relaciones = $obj->_relacion->relaciones_externas(
     *     modelo_uuid_ext: $modelo_uuid_ext,
     *     row_relacion: $row_relacion
     * );
     * if (isset($relaciones['error'])) {
     *     echo "Error: " . $relaciones['mensaje'];
     * }
     */
    private function relaciones_externas(_uuid_ext $modelo_uuid_ext, array $row_relacion): array
    {
        $valida = $this->valida_data_rel(row_relacion: $row_relacion);
        if (errores::$error) {
            return $this->error->error(
                mensaje: 'Error valida row relacion',
                data: $valida
            );
        }

        $filtro[$this->key_filtro_id] = $row_relacion[$this->key_id];

        $r_fc_uuid_fc = $modelo_uuid_ext->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(
                mensaje: 'Error al obtener relacion',
                data: $r_fc_uuid_fc
            );
        }

        return $r_fc_uuid_fc->registros;
    }


    /**
     * REG
     * Genera un conjunto de relaciones basadas en los registros de entrada.
     *
     * Esta función recorre una lista de registros (`$row_relaciones`), validando cada uno de ellos
     * y generando sus relaciones correspondientes. Para cada registro, se verifica que sea un array,
     * que cumpla con los requisitos básicos (`valida_base`) y se generan las relaciones con
     * `genera_relacionado()`. Si hay algún error en el proceso, se devuelve un array de error.
     *
     * @param _relacionada $modelo_relacionada Instancia del modelo que maneja las relaciones entre documentos.
     * @param _uuid_ext $modelo_uuid_ext Instancia del modelo para manejar UUID externos de documentos.
     * @param string $name_entidad Nombre de la entidad base (factura, complemento de pago, nota de crédito, etc.).
     * @param array $row_relaciones Lista de registros que contienen los datos para generar relaciones.
     *
     * @return array Retorna el array de relaciones generadas y validadas.
     *
     * @throws array Si algún `$row_relacion` no es un array, devuelve un array de error en el formato `errores::$error`.
     * @throws array Si la validación de la base de datos para un registro falla, devuelve un array de error.
     * @throws array Si ocurre un error al generar una relación, devuelve un array de error con los detalles.
     *
     * @example
     * // Ejemplo 1: Generar múltiples relaciones a partir de una lista de registros
     * $modelo_relacionada = new _relacionada();
     * $modelo_uuid_ext = new _uuid_ext();
     * $row_relaciones = [
     *     ['id' => 10, 'key_id' => 1001, 'cat_sat_tipo_relacion_codigo' => '01'],
     *     ['id' => 11, 'key_id' => 1002, 'cat_sat_tipo_relacion_codigo' => '02']
     * ];
     *
     * $resultado = $obj->_relacion->relacionados(
     *     modelo_relacionada: $modelo_relacionada,
     *     modelo_uuid_ext: $modelo_uuid_ext,
     *     name_entidad: 'fc_factura',
     *     row_relaciones: $row_relaciones
     * );
     * print_r($resultado);
     *
     * @example
     * // Ejemplo 2: Manejo de error si uno de los registros no es un array
     * $modelo_relacionada = new _relacionada();
     * $modelo_uuid_ext = new _uuid_ext();
     * $row_relaciones = [
     *     ['id' => 10, 'key_id' => 1001, 'cat_sat_tipo_relacion_codigo' => '01'],
     *     'esto_no_es_un_array' // Error
     * ];
     *
     * $resultado = $obj->_relacion->relacionados(
     *     modelo_relacionada: $modelo_relacionada,
     *     modelo_uuid_ext: $modelo_uuid_ext,
     *     name_entidad: 'fc_factura',
     *     row_relaciones: $row_relaciones
     * );
     * if (isset($resultado['error'])) {
     *     echo "Error: " . $resultado['mensaje'];
     * }
     */
    private function relacionados(
        _relacionada $modelo_relacionada,
        _uuid_ext $modelo_uuid_ext,
        string $name_entidad,
        array $row_relaciones
    ): array {
        $relacionados = array();

        foreach ($row_relaciones as $row_relacion) {
            if (!is_array($row_relacion)) {
                return $this->error->error(
                    mensaje: 'Error $row_relacion debe ser un array',
                    data: $row_relacion,
                    es_final: true
                );
            }

            $valida = $this->valida_base(name_entidad: $name_entidad, row_relacion: $row_relacion);
            if (errores::$error) {
                return $this->error->error(
                    mensaje: 'Error al validar row_relacion',
                    data: $valida
                );
            }

            $relacionados = $this->genera_relacionado(
                modelo_relacionada: $modelo_relacionada,
                modelo_uuid_ext: $modelo_uuid_ext,
                name_entidad: $name_entidad,
                relacionados: $relacionados,
                row_relacion: $row_relacion
            );
            if (errores::$error) {
                return $this->error->error(
                    mensaje: 'Error al integrar relacionado',
                    data: $relacionados
                );
            }
        }

        return $relacionados;
    }


    /**
     * REG
     * Valida la estructura y datos esenciales de una relación antes de su procesamiento.
     *
     * Esta función verifica que `$row_relacion` contenga las claves requeridas, como el identificador clave (`key_id`)
     * y el código SAT de la relación (`cat_sat_tipo_relacion_codigo`). Además, valida que el nombre de la entidad
     * no esté vacío antes de continuar con el procesamiento.
     *
     * @param string $name_entidad Nombre de la entidad base (factura, complemento de pago, nota de crédito, etc.). No debe estar vacío.
     * @param array $row_relacion Array que representa la relación que se desea validar.
     *                            Debe incluir `key_id` y `cat_sat_tipo_relacion_codigo`.
     *
     * @return bool|array Retorna `true` si la validación es exitosa.
     *                    En caso de error, devuelve un array con el mensaje de error en el formato `errores::$error`.
     *
     * @throws array Si `$row_relacion` no contiene `key_id`, devuelve un array de error en el formato `errores::$error`.
     * @throws array Si `$row_relacion` no contiene `cat_sat_tipo_relacion_codigo`, devuelve un array de error.
     * @throws array Si `$name_entidad` está vacío, devuelve un array de error con los detalles.
     *
     * @example
     * // Ejemplo 1: Validar una relación correctamente estructurada
     * $row_relacion = ['id' => 10, 'key_id' => 1001, 'cat_sat_tipo_relacion_codigo' => '01'];
     * $resultado = $obj->_relacion->valida_base(name_entidad: 'fc_factura', row_relacion: $row_relacion);
     * if ($resultado === true) {
     *     echo "Validación exitosa.";
     * } else {
     *     echo "Error: " . $resultado['mensaje'];
     * }
     *
     * @example
     * // Ejemplo 2: Manejo de error si falta la clave `cat_sat_tipo_relacion_codigo`
     * $row_relacion = ['id' => 10, 'key_id' => 1001]; // Falta `cat_sat_tipo_relacion_codigo`
     * $resultado = $obj->_relacion->valida_base(name_entidad: 'fc_factura', row_relacion: $row_relacion);
     * if (isset($resultado['error'])) {
     *     echo "Error: " . $resultado['mensaje'];
     * }
     */
    private function valida_base(string $name_entidad, array $row_relacion): bool|array
    {
        $keys = array($this->key_id);
        $valida = $this->validacion->valida_ids(keys: $keys, registro: $row_relacion);
        if (errores::$error) {
            return $this->error->error(
                mensaje: 'Error al validar $row_relacion',
                data: $valida
            );
        }

        if (!isset($row_relacion['cat_sat_tipo_relacion_codigo'])) {
            return $this->error->error(
                mensaje: 'Error al validar $row_relacion debe existir cat_sat_tipo_relacion_codigo',
                data: $row_relacion,
                es_final: true
            );
        }

        $name_entidad = trim($name_entidad);
        if ($name_entidad === '') {
            return $this->error->error(
                mensaje: 'Error name_entidad esta vacio',
                data: $name_entidad,
                es_final: true
            );
        }

        return true;
    }


    /**
     * REG
     * Valida que los datos de una relación contengan las claves necesarias.
     *
     * Esta función revisa que la estructura de `$row_relacion` incluya las claves obligatorias
     * y que los identificadores de filtro (`key_filtro_id`) e identificador de la relación (`key_id`)
     * estén definidos correctamente antes de procesar la relación.
     *
     * @param array $row_relacion Array que representa la relación que se desea validar. Debe contener la clave `key_id`.
     *
     * @return bool|array Retorna `true` si la validación es exitosa.
     *                    En caso de error, devuelve un array con el mensaje de error en el formato `errores::$error`.
     *
     * @throws array Si `$this->key_filtro_id` está vacío, devuelve un array de error.
     * @throws array Si `$this->key_id` está vacío, devuelve un array de error.
     * @throws array Si `$row_relacion` no contiene `$this->key_id`, devuelve un array de error con los detalles del fallo.
     *
     * @example
     * // Ejemplo 1: Validar una relación correctamente estructurada
     * $row_relacion = ['id' => 10, 'key_id' => 1001];
     * $resultado = $obj->_relacion->valida_data_rel($row_relacion);
     * if ($resultado === true) {
     *     echo "Validación exitosa.";
     * } else {
     *     echo "Error: " . $resultado['mensaje'];
     * }
     *
     * @example
     * // Ejemplo 2: Manejo de error si falta la clave `key_id`
     * $row_relacion = ['id' => 10]; // Falta `key_id`
     * $resultado = $obj->_relacion->valida_data_rel($row_relacion);
     * if (isset($resultado['error'])) {
     *     echo "Error: " . $resultado['mensaje'];
     * }
     */
    private function valida_data_rel(array $row_relacion): bool|array
    {
        $this->key_filtro_id = trim($this->key_filtro_id);
        if ($this->key_filtro_id === '') {
            return $this->error->error(
                mensaje: 'Error this->key_filtro_id esta vacio',
                data: $this->key_filtro_id,es_final: true
            );
        }

        $this->key_id = trim($this->key_id);
        if ($this->key_id === '') {
            return $this->error->error(
                mensaje: 'Error this->key_id esta vacio',
                data: $this->key_id,es_final: true
            );
        }

        $keys = array($this->key_id);
        $valida = $this->validacion->valida_ids(keys: $keys, registro: $row_relacion);
        if (errores::$error) {
            return $this->error->error(
                mensaje: 'Error valida row relacion',
                data: $valida
            );
        }

        return true;
    }


}
