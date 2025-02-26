<?php

namespace gamboamartin\facturacion\models;
use gamboamartin\errores\errores;
use stdClass;

class _conceptos{

    private errores $error;
    public function __construct()
    {
        $this->error = new errores();

    }

    private function acumula_totales(stdClass $data_partida, _partida $modelo_partida, float $total_impuestos_retenidos,
                                     float $total_impuestos_trasladados): stdClass
    {
        $key_importe_total_traslado = $modelo_partida->tabla.'_total_traslados';
        $key_importe_total_retenido = $modelo_partida->tabla.'_total_retenciones';

        $total_impuestos_trasladados += ($data_partida->partida[$key_importe_total_traslado]);
        $total_impuestos_retenidos += ($data_partida->partida[$key_importe_total_retenido]);

        $totales = new stdClass();
        $totales->total_impuestos_retenidos = $total_impuestos_retenidos;
        $totales->total_impuestos_trasladados = $total_impuestos_trasladados;

        return $totales;

    }

    private function carga_totales(stdClass $data_partida, _partida $modelo_partida,
                                   _data_impuestos $modelo_retencion, _data_impuestos $modelo_traslado, array $ret_global,
                                   float $total_impuestos_retenidos, float $total_impuestos_trasladados, array $trs_global)
    {
        $totales = $this->acumula_totales(data_partida: $data_partida,modelo_partida: $modelo_partida,
            total_impuestos_retenidos: $total_impuestos_retenidos,total_impuestos_trasladados: $total_impuestos_trasladados);

        if(errores::$error){
            return $this->error->error(mensaje: 'Error al cargar $totales', data: $totales);
        }

        $total_impuestos_trasladados = $totales->total_impuestos_trasladados;
        $total_impuestos_retenidos = $totales->total_impuestos_retenidos;

        $globales_imps = $this->impuestos_globales(data_partida: $data_partida,modelo_partida:  $modelo_partida,
            modelo_retencion:  $modelo_retencion,
            modelo_traslado: $modelo_traslado, ret_global: $ret_global,trs_global:  $trs_global);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al cargar globales_imps', data: $globales_imps);
        }

        $ret_global = $globales_imps->ret_global;
        $trs_global = $globales_imps->trs_global;

        $totales_fin = new stdClass();
        $totales_fin->total_impuestos_trasladados = $total_impuestos_trasladados;
        $totales_fin->total_impuestos_retenidos = $total_impuestos_retenidos;
        $totales_fin->ret_global = $ret_global;
        $totales_fin->trs_global = $trs_global;

        return $totales_fin;

    }

    private function concepto(stdClass $data_partida, stdClass $keys_part): stdClass
    {
        $concepto = new stdClass();
        $concepto->clave_prod_serv = $data_partida->partida['cat_sat_producto_codigo'];
        $concepto->cantidad = $data_partida->partida[$keys_part->cantidad];
        $concepto->clave_unidad = $data_partida->partida['cat_sat_unidad_codigo'];
        $concepto->descripcion = $data_partida->partida[$keys_part->descripcion];
        $concepto->valor_unitario = number_format($data_partida->partida[$keys_part->valor_unitario], 2);
        $concepto->importe = number_format($data_partida->partida[$keys_part->importe], 2);
        $concepto->objeto_imp = $data_partida->partida['cat_sat_obj_imp_codigo'];
        $concepto->no_identificacion = $data_partida->partida['com_producto_codigo'];
        $concepto->unidad = $data_partida->partida['cat_sat_unidad_descripcion'];

        return $concepto;

    }

    private function cuenta_predial(
        stdClass $concepto, string $key_filtro_id, _cuenta_predial $modelo_predial, array $partida, int $registro_id): array|stdClass
    {

        // Validar que el ID del registro sea mayor a 0
        if ($registro_id <= 0) {
            return $this->error->error(
                mensaje: "Error: el registro_id debe ser mayor a 0",
                data: $registro_id,
                es_final: true
            );
        }

        $key_filtro_id = trim($key_filtro_id);
        if($key_filtro_id === ''){
            return $this->error->error(
                mensaje: "Error: el $key_filtro_id esta vacio", data: $registro_id, es_final: true
            );
        }

        // Verificar si el producto en la partida requiere cuenta predial
        if(isset($partida['com_producto_aplica_predial']) && $partida['com_producto_aplica_predial'] === 'activo'){

            // Obtener el número de cuenta predial del registro
            $fc_cuenta_predial_numero = $this->fc_cuenta_predial_numero(key_filtro_id: $key_filtro_id,
                modelo_predial: $modelo_predial,
                registro_id:  $registro_id
            );

            // Manejo de errores si la cuenta predial no pudo obtenerse
            if (errores::$error) {
                return $this->error->error(
                    mensaje: 'Error al obtener cuenta predial',
                    data: $fc_cuenta_predial_numero
                );
            }

            // Asignar la cuenta predial al concepto
            $concepto->cuenta_predial = $fc_cuenta_predial_numero;
        }

        return $concepto;
    }

    /**
     * REG
     * Obtiene los datos completos de una partida, incluyendo impuestos y detalles del producto.
     *
     * La función primero verifica si la clave identificadora de la partida existe en el array `$partida`.
     * Luego, obtiene los impuestos asociados a la partida y, si no hay errores, integra los datos
     * temporales del producto en la partida. Finalmente, agrega la información completa de la partida
     * al objeto de impuestos y la devuelve.
     *
     * @param _partida $modelo_partida Instancia del modelo de partida que contiene la estructura y claves de identificación.
     * @param _data_impuestos $modelo_retencion Modelo encargado de manejar los impuestos retenidos.
     * @param _data_impuestos $modelo_traslado Modelo encargado de manejar los impuestos trasladados.
     * @param array $partida Array que contiene los datos de la partida, incluyendo la clave de identificación.
     *
     * @return array|stdClass Retorna un objeto con los datos completos de la partida, incluyendo impuestos.
     * En caso de error, retorna un array con la información del error.
     *
     * @throws errores Si no se encuentra la clave identificadora de la partida o si falla la consulta de impuestos.
     * El array de error generado tiene la siguiente estructura:
     * ```php
     * Array
     * (
     *     [error] => 1
     *     [mensaje] => "<b><span style='color:red'>Mensaje de error</span></b>"
     *     [mensaje_limpio] => "Mensaje de error"
     *     [file] => "<b>ruta/del/archivo.php</b>"
     *     [line] => "<b>123</b>"
     *     [class] => "<b>NombreDeLaClase</b>"
     *     [function] => "<b>NombreDeLaFuncion</b>"
     *     [data] => "Datos asociados al error"
     *     [params] => "Parámetros utilizados en la función"
     *     [fix] => "Sugerencia de corrección"
     * )
     * ```
     *
     * @example
     * Ejemplo de entrada:
     * ```php
     * $modelo_partida = new _partida();
     * $modelo_partida->key_id = 'partida_id';
     * $modelo_partida->tabla = 'fc_partida';
     *
     * $modelo_retencion = new _data_impuestos();
     * $modelo_traslado = new _data_impuestos();
     *
     * $partida = [
     *     'partida_id' => 123,
     *     'fc_partida_cantidad' => 5,
     *     'fc_partida_valor_unitario' => 100.00,
     *     'com_producto_codigo_sat' => '01010101'
     * ];
     *
     * $resultado = $this->data_partida($modelo_partida, $modelo_retencion, $modelo_traslado, $partida);
     * print_r($resultado);
     * ```
     *
     * @example
     * Ejemplo de salida exitosa:
     * ```php
     * stdClass Object
     * (
     *     [traslados] => Array
     *         (
     *             [0] => Array
     *                 (
     *                     [tipo_impuesto] => IVA
     *                     [tasa] => 16
     *                     [importe] => 80.00
     *                 )
     *         )
     *
     *     [retenidos] => Array
     *         (
     *             [0] => Array
     *                 (
     *                     [tipo_impuesto] => ISR
     *                     [tasa] => 10
     *                     [importe] => 50.00
     *                 )
     *         )
     *
     *     [partida] => Array
     *         (
     *             [partida_id] => 123
     *             [fc_partida_cantidad] => 5
     *             [fc_partida_valor_unitario] => 100.00
     *             [cat_sat_producto_codigo] => '01010101'
     *         )
     * )
     * ```
     *
     * @example
     * Ejemplo de salida con error si la clave de identificación no existe:
     * ```php
     * Array
     * (
     *     [error] => 1
     *     [mensaje] => "Error partida[partida_id] no existe partida_id"
     *     [data] => Array()
     *     [es_final] => true
     * )
     * ```
     */
    private function data_partida(
        _partida $modelo_partida,
        _data_impuestos $modelo_retencion,
        _data_impuestos $modelo_traslado,
        array $partida
    ): array|stdClass {
        // Verifica que la clave identificadora de la partida exista en el array $partida
        if (!isset($partida[$modelo_partida->key_id])) {
            return $this->error->error(
                mensaje: 'Error $partida[' . $modelo_partida->key_id . '] no existe ' . $modelo_partida->key_id,
                data: $partida,
                es_final: true
            );
        }

        // Obtiene los impuestos trasladados y retenidos de la partida
        $imp_partida = $this->get_impuestos_partida(
            modelo_partida: $modelo_partida,
            modelo_retencion:  $modelo_retencion,
            modelo_traslado:  $modelo_traslado,
            partida:  $partida
        );

        // Manejo de error en la obtención de impuestos
        if (errores::$error) {
            return $this->error->error(
                mensaje: 'Error al obtener los impuestos de la partida',
                data: $imp_partida
            );
        }

        // Integra los datos temporales del producto a la partida
        $partida = $this->integra_producto_tmp(partida: $partida);

        // Manejo de error en la integración del producto temporal
        if (errores::$error) {
            return $this->error->error(
                mensaje: 'Error al integrar producto temporal',
                data: $partida
            );
        }

        // Agrega la información de la partida al objeto de impuestos
        $imp_partida->partida = $partida;

        return $imp_partida;
    }


    private function descuento(stdClass $data_partida, string $key_descuento): float|array
    {
        // Elimina espacios en blanco en la clave del descuento
        $key_descuento = trim($key_descuento);

        // Validación: La clave del descuento no debe estar vacía
        if ($key_descuento === '') {
            return $this->error->error(
                mensaje: 'Error $key_descuento está vacío',
                data: $key_descuento,
                es_final: true
            );
        }

        // Valor por defecto del descuento
        $descuento = 0.0;

        // Verifica si la clave del descuento está definida en la partida
        if (isset($data_partida->partida[$key_descuento])) {
            $descuento = $data_partida->partida[$key_descuento];
        }

        // Formatear el monto del descuento con dos decimales
        $descuento = (new _comprobante())->monto_dos_dec(monto: $descuento);

        // Validación de errores después del formateo
        if (errores::$error) {
            return $this->error->error(
                mensaje: 'Error al maquetar descuento',
                data: $descuento
            );
        }

        return $descuento;
    }

    private function fc_cuenta_predial_numero(string $key_filtro_id, _cuenta_predial $modelo_predial, int $registro_id): array|string
    {

        // Validar que el ID del registro sea mayor a 0
        if ($registro_id <= 0) {
            return $this->error->error(
                mensaje: "Error: el registro_id debe ser mayor a 0",
                data: $registro_id,
                es_final: true
            );
        }
        $key_filtro_id = trim($key_filtro_id);
        if($key_filtro_id === ''){
            return $this->error->error(
                mensaje: 'Error: el $key_filtro_id esta vacio', data: $registro_id, es_final: true
            );
        }

        // Obtener la cuenta predial asociada al registro
        $r_fc_cuenta_predial = $this->r_fc_cuenta_predial(key_filtro_id: $key_filtro_id,
            modelo_predial: $modelo_predial,
            registro_id: $registro_id
        );

        // Verificar si hubo un error en la consulta
        if (errores::$error) {
            return $this->error->error(
                mensaje: 'Error al obtener cuenta predial',
                data: $r_fc_cuenta_predial
            );
        }

        // Obtener la clave de la cuenta predial en el resultado
        $key_cuenta_predial_descripcion = $modelo_predial->tabla . '_descripcion';

        // Retornar el número de cuenta predial
        return $r_fc_cuenta_predial->registros[0][$key_cuenta_predial_descripcion];
    }

    private function genera_concepto(stdClass $data_partida, string $key_filtro_id, _partida $modelo_partida, _cuenta_predial $modelo_predial,
                                     _data_impuestos $modelo_retencion, _data_impuestos $modelo_traslado, int $registro_id)
    {

        $key_filtro_id = trim($key_filtro_id);
        if($key_filtro_id === ''){
            return $this->error->error(
                mensaje: "Error: el $key_filtro_id esta vacio", data: $registro_id, es_final: true
            );
        }

        $concepto = $this->integra_descuento(data_partida: $data_partida,modelo_partida: $modelo_partida);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al maquetar impuestos', data: $concepto);
        }

        $concepto = $this->inicializa_impuestos_de_concepto(concepto: $concepto);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al maquetar impuestos', data: $concepto);
        }

        $concepto = $this->integra_traslados(concepto: $concepto, data_partida: $data_partida,
            modelo_partida: $modelo_partida, modelo_traslado: $modelo_traslado);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al maquetar traslados', data: $concepto);
        }

        $concepto = $this->integra_retenciones(concepto: $concepto, data_partida: $data_partida,
            modelo_partida:  $modelo_partida,modelo_retencion:  $modelo_retencion);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al maquetar traslados', data: $concepto);
        }

        $concepto = $this->cuenta_predial(concepto: $concepto, key_filtro_id: $key_filtro_id,
            modelo_predial: $modelo_predial, partida: $data_partida->partida, registro_id: $registro_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al integrar cuenta predial', data: $concepto);
        }

        return $concepto;

    }
    /**
     * Obtiene los impuestos asociados a una partida, incluyendo impuestos trasladados y retenidos.
     *
     * Esta función verifica la existencia de la clave de identificación de la partida en el array `$partida`,
     * luego obtiene los impuestos trasladados y retenidos mediante los modelos `_data_impuestos`.
     * Si hay errores en la obtención de los impuestos, retorna un array con el error.
     *
     * @param _partida $modelo_partida Instancia del modelo de partida que contiene la estructura y claves de identificación.
     * @param _data_impuestos $modelo_retencion Modelo encargado de manejar los impuestos retenidos.
     * @param _data_impuestos $modelo_traslado Modelo encargado de manejar los impuestos trasladados.
     * @param array $partida Array que contiene los datos de la partida, incluyendo la clave de identificación.
     *
     * @return array|stdClass Retorna un objeto con los impuestos trasladados y retenidos si la operación es exitosa.
     * En caso de error, retorna un array con la información del error.
     *
     * @throws errores Si no se encuentra la clave identificadora de la partida o si falla la consulta de impuestos.
     *
     * @example
     * Ejemplo de entrada:
     * ```php
     * $modelo_partida = new _partida();
     * $modelo_partida->key_id = 'partida_id';
     * $modelo_partida->tabla = 'fc_partida';
     *
     * $modelo_retencion = new _data_impuestos();
     * $modelo_traslado = new _data_impuestos();
     *
     * $partida = [
     *     'partida_id' => 123,
     *     'fc_partida_cantidad' => 5,
     *     'fc_partida_valor_unitario' => 100.00
     * ];
     *
     * $resultado = $this->get_impuestos_partida($modelo_partida, $modelo_retencion, $modelo_traslado, $partida);
     * print_r($resultado);
     * ```
     *
     * @example
     * Ejemplo de salida exitosa:
     * ```php
     * stdClass Object
     * (
     *     [traslados] => Array
     *         (
     *             [0] => Array
     *                 (
     *                     [tipo_impuesto] => IVA
     *                     [tasa] => 16
     *                     [importe] => 80.00
     *                 )
     *         )
     *
     *     [retenidos] => Array
     *         (
     *             [0] => Array
     *                 (
     *                     [tipo_impuesto] => ISR
     *                     [tasa] => 10
     *                     [importe] => 50.00
     *                 )
     *         )
     * )
     * ```
     *
     * @example
     * Ejemplo de salida con error si la clave de identificación no existe:
     * ```php
     * Array
     * (
     *     [error] => true
     *     [mensaje] => "Error partida[partida_id] no existe partida_id"
     *     [data] => Array()
     *     [es_final] => true
     * )
     * ```
     */
    private function get_impuestos_partida(
        _partida $modelo_partida,
        _data_impuestos $modelo_retencion,
        _data_impuestos $modelo_traslado,
        array $partida
    ): array|stdClass {
        // Verifica que la clave identificadora de la partida exista en el array $partida
        if (!isset($partida[$modelo_partida->key_id])) {
            return $this->error->error(
                mensaje: 'Error $partida[' . $modelo_partida->key_id . '] no existe ' . $modelo_partida->key_id,
                data: $partida,
                es_final: true
            );
        }

        // Obtiene los impuestos trasladados asociados a la partida
        $traslados = $modelo_traslado->get_data_rows(
            name_modelo_partida: $modelo_partida->tabla,
            registro_partida_id: $partida[$modelo_partida->key_id]
        );

        // Manejo de error en la obtención de impuestos trasladados
        if (errores::$error) {
            return $this->error->error(
                mensaje: 'Error al obtener el traslados de la partida',
                data: $traslados
            );
        }

        // Obtiene los impuestos retenidos asociados a la partida
        $retenidos = $modelo_retencion->get_data_rows(
            name_modelo_partida: $modelo_partida->tabla,
            registro_partida_id: $partida[$modelo_partida->key_id]
        );

        // Manejo de error en la obtención de impuestos retenidos
        if (errores::$error) {
            return $this->error->error(
                mensaje: 'Error al obtener el retenidos de la partida',
                data: $retenidos
            );
        }

        // Retorna los impuestos de la partida en un objeto estándar
        $data = new stdClass();
        $data->traslados = $traslados;
        $data->retenidos = $retenidos;

        return $data;
    }

    private function impuestos_globales(
        stdClass $data_partida, _partida $modelo_partida, _data_impuestos $modelo_retencion,
        _data_impuestos $modelo_traslado, array $ret_global, array $trs_global)
    {
        $key_traslado_importe = $modelo_traslado->tabla.'_importe';
        $trs_global = (new _impuestos())->impuestos_globales(
            impuestos: $data_partida->traslados, global_imp: $trs_global, key_importe: $key_traslado_importe,
            name_tabla_partida: $modelo_partida->tabla);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al inicializar acumulado', data: $trs_global);
        }

        $key_retenido_importe = $modelo_retencion->tabla.'_importe';
        $ret_global = (new _impuestos())->impuestos_globales(
            impuestos: $data_partida->retenidos, global_imp: $ret_global, key_importe: $key_retenido_importe,
            name_tabla_partida: $modelo_partida->tabla);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al inicializar acumulado', data: $ret_global);
        }

        $impuestos_glb = new stdClass();
        $impuestos_glb->ret_global = $ret_global;
        $impuestos_glb->trs_global = $trs_global;

        return $impuestos_glb;

    }

    private function inicializa_impuestos_de_concepto(stdClass $concepto): stdClass
    {
        // Inicializa la propiedad impuestos como un array vacío en el concepto
        $concepto->impuestos = array();

        // Agrega una estructura de impuestos en el primer índice del array
        $concepto->impuestos[0] = new stdClass();

        // Inicializa las propiedades traslados y retenciones como arrays vacíos
        $concepto->impuestos[0]->traslados = array();
        $concepto->impuestos[0]->retenciones = array();

        // Retorna el objeto concepto con la estructura de impuestos inicializada
        return $concepto;
    }

    private function integra_descuento(stdClass $data_partida, _partida $modelo_partida)
    {

        $keys_part = $this->keys_partida(modelo_partida: $modelo_partida);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener $keys_part', data: $keys_part);
        }

        $concepto = $this->concepto(data_partida: $data_partida,keys_part: $keys_part);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al maquetar $concepto', data: $concepto);
        }

        $descuento = $this->descuento(data_partida: $data_partida,key_descuento:  $keys_part->descuento);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al maquetar descuento', data: $descuento);
        }

        $concepto->descuento = $descuento;

        return $concepto;

    }

    final public function integra_partidas(array $conceptos, string $key, string $key_filtro_id, _partida $modelo_partida, _cuenta_predial $modelo_predial,
                                      _data_impuestos $modelo_retencion, _data_impuestos $modelo_traslado,
                                      array $partida, array $registro, int $registro_id, array $ret_global,
                                      float $total_impuestos_retenidos, float $total_impuestos_trasladados, array $trs_global)
    {

        $key_filtro_id = trim($key_filtro_id);
        if($key_filtro_id === ''){
            return $this->error->error(
                mensaje: "Error: el $key_filtro_id esta vacio", data: $registro_id, es_final: true
            );
        }

        $data_partida = $this->data_partida(modelo_partida: $modelo_partida,modelo_retencion:  $modelo_retencion,
            modelo_traslado:  $modelo_traslado,partida:  $partida);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integrar producto temporal', data: $partida);
        }

        $registro['partidas'][$key]['traslados'] = $data_partida->traslados->registros;
        $registro['partidas'][$key]['retenidos'] = $data_partida->retenidos->registros;


        $concepto = $this->genera_concepto(data_partida: $data_partida, key_filtro_id: $key_filtro_id,
            modelo_partida: $modelo_partida, modelo_predial: $modelo_predial,
            modelo_retencion: $modelo_retencion, modelo_traslado: $modelo_traslado, registro_id: $registro_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al integrar concepto', data: $concepto);
        }


        $conceptos[] = $concepto;



        $totales = $this->carga_totales(data_partida: $data_partida, modelo_partida: $modelo_partida,
            modelo_retencion: $modelo_retencion, modelo_traslado: $modelo_traslado, ret_global: $ret_global,
            total_impuestos_retenidos: $total_impuestos_retenidos,
            total_impuestos_trasladados: $total_impuestos_trasladados, trs_global: $trs_global);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al cargar $totales', data: $totales);
        }

        $datos = new stdClass();


        $datos->ret_global = $totales->ret_global;
        $datos->trs_global = $totales->trs_global;
        $datos->total_impuestos_trasladados = $totales->total_impuestos_trasladados;
        $datos->total_impuestos_retenidos = $totales->total_impuestos_retenidos;
        $datos->conceptos = $conceptos;
        $datos->registro = $registro;

        return $datos;

    }

    /**
     * POR ELIMINAR FUNCION Y OBTENER DE COM PRODUCTO
     * @param array $partida
     * @return array
     */
    private function integra_producto_tmp(array $partida): array
    {
        $partida['cat_sat_producto_codigo'] = $partida['com_producto_codigo_sat'];
        return $partida;

    }

    private function integra_retenciones(stdClass $concepto, stdClass $data_partida, _partida $modelo_partida,
                                         _data_impuestos $modelo_retencion)
    {
        $key_retenido_importe = $modelo_retencion->tabla.'_importe';
        $impuestos = (new _impuestos())->maqueta_impuesto(impuestos: $data_partida->retenidos,
            key_importe_impuesto: $key_retenido_importe, name_tabla_partida: $modelo_partida->tabla);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al maquetar retenciones', data: $impuestos);
        }

        $concepto->impuestos[0]->retenciones = $impuestos;

        return $concepto;

    }

    private function integra_traslados(stdClass $concepto, stdClass $data_partida, _partida $modelo_partida,
                                       _data_impuestos $modelo_traslado){
        $key_traslado_importe = $modelo_traslado->tabla.'_importe';
        $impuestos = (new _impuestos())->maqueta_impuesto(impuestos: $data_partida->traslados,
            key_importe_impuesto: $key_traslado_importe,name_tabla_partida: $modelo_partida->tabla);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al maquetar traslados', data: $impuestos);
        }

        $concepto->impuestos[0]->traslados = $impuestos;

        return $concepto;
    }

    /**
     * REG
     * Genera un conjunto de claves (keys) dinámicas para identificar los atributos de una partida en la base de datos.
     *
     * Esta función toma el nombre de la tabla de la partida y genera las claves necesarias para acceder a los atributos
     * principales de la partida, como cantidad, descripción, valor unitario, importe y descuento.
     *
     * @param _partida $modelo_partida Instancia del modelo de partida que contiene la estructura y el nombre de la tabla.
     *
     * @return stdClass|array Retorna un objeto con las claves generadas si la operación es exitosa.
     * En caso de error (si `$modelo_partida->tabla` está vacío), retorna un array con la información del error.
     *
     * @throws errores Si el nombre de la tabla (`$modelo_partida->tabla`) está vacío, genera un error con la estructura:
     * ```php
     * Array
     * (
     *     [error] => 1
     *     [mensaje] => "<b><span style='color:red'>$modelo_partida->tabla esta vacio</span></b>"
     *     [mensaje_limpio] => "$modelo_partida->tabla esta vacio"
     *     [file] => "<b>ruta/del/archivo.php</b>"
     *     [line] => "<b>123</b>"
     *     [class] => "<b>NombreDeLaClase</b>"
     *     [function] => "<b>NombreDeLaFuncion</b>"
     *     [data] => "Datos asociados al error"
     *     [params] => "Parámetros utilizados en la función"
     *     [fix] => "Sugerencia de corrección"
     * )
     * ```
     *
     * @example
     * Ejemplo de entrada:
     * ```php
     * $modelo_partida = new _partida();
     * $modelo_partida->tabla = 'fc_partida';
     *
     * $resultado = $this->keys_partida($modelo_partida);
     * print_r($resultado);
     * ```
     *
     * @example
     * Ejemplo de salida exitosa:
     * ```php
     * stdClass Object
     * (
     *     [cantidad] => "fc_partida_cantidad"
     *     [descripcion] => "fc_partida_descripcion"
     *     [valor_unitario] => "fc_partida_valor_unitario"
     *     [importe] => "fc_partida_sub_total_base"
     *     [descuento] => "fc_partida_descuento"
     * )
     * ```
     *
     * @example
     * Ejemplo de salida con error si `$modelo_partida->tabla` está vacío:
     * ```php
     * Array
     * (
     *     [error] => 1
     *     [mensaje] => "Error: $modelo_partida->tabla esta vacio"
     *     [data] => ""
     *     [es_final] => true
     * )
     * ```
     */
    private function keys_partida(_partida $modelo_partida): stdClass|array
    {
        // Limpia espacios en blanco del nombre de la tabla
        $modelo_partida->tabla = trim($modelo_partida->tabla);

        // Verifica si el nombre de la tabla está vacío
        if ($modelo_partida->tabla === '') {
            return $this->error->error(
                mensaje: '$modelo_partida->tabla esta vacio',
                data: $modelo_partida->tabla
            );
        }

        // Construcción de claves basadas en el nombre de la tabla
        $key_cantidad = $modelo_partida->tabla . '_cantidad';
        $key_descripcion = $modelo_partida->tabla . '_descripcion';
        $key_valor_unitario = $modelo_partida->tabla . '_valor_unitario';
        $key_importe = $modelo_partida->tabla . '_sub_total_base';
        $key_descuento = $modelo_partida->tabla . '_descuento';

        // Creación del objeto que contiene las claves generadas
        $keys = new stdClass();
        $keys->cantidad = $key_cantidad;
        $keys->descripcion = $key_descripcion;
        $keys->valor_unitario = $key_valor_unitario;
        $keys->importe = $key_importe;
        $keys->descuento = $key_descuento;

        return $keys;
    }


    private function r_fc_cuenta_predial(string $key_filtro_id, _cuenta_predial $modelo_predial, int $registro_id): array|stdClass
    {
        // Validar que el ID del registro sea mayor a 0
        if ($registro_id <= 0) {
            return $this->error->error(
                mensaje: "Error: el registro_id debe ser mayor a 0",
                data: $registro_id,
                es_final: true
            );
        }

        $key_filtro_id = trim($key_filtro_id);
        if($key_filtro_id === ''){
            return $this->error->error(
                mensaje: "Error: el $key_filtro_id esta vacio", data: $registro_id, es_final: true
            );
        }

        // Consultar la cuenta predial asociada al registro
        $r_fc_cuenta_predial = $modelo_predial->filtro_and(filtro: array($key_filtro_id => $registro_id));

        // Verificar si hubo un error en la consulta
        if (errores::$error) {
            return $this->error->error(
                mensaje: "Error al obtener cuenta predial",
                data: $r_fc_cuenta_predial
            );
        }

        // Validar que haya exactamente un registro de cuenta predial
        if ($r_fc_cuenta_predial->n_registros === 0) {
            return $this->error->error(
                mensaje: "Error: no existe cuenta predial asignada",
                data: $r_fc_cuenta_predial,
                es_final: true
            );
        }

        if ($r_fc_cuenta_predial->n_registros > 1) {
            return $this->error->error(
                mensaje: "Error de integridad: más de una cuenta predial asignada",
                data: $r_fc_cuenta_predial,
                es_final: true
            );
        }

        // Retornar la cuenta predial encontrada
        return $r_fc_cuenta_predial;
    }


}
