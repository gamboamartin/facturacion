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


    /**
     * REG
     * Acumula los totales de impuestos trasladados y retenidos en una partida.
     *
     * Esta función valida la estructura de la partida, verifica la existencia de los
     * campos que almacenan los impuestos trasladados y retenidos dentro de la partida,
     * y luego suma los valores correspondientes para devolver un objeto con los totales actualizados.
     *
     * @param stdClass $data_partida Objeto que contiene la información de la partida,
     *                               incluyendo los impuestos trasladados y retenidos.
     * @param _partida $modelo_partida Instancia del modelo de partida que contiene
     *                                  la referencia a la tabla donde se almacenan los impuestos.
     * @param float $total_impuestos_retenidos Monto acumulado de impuestos retenidos antes de la función.
     * @param float $total_impuestos_trasladados Monto acumulado de impuestos trasladados antes de la función.
     *
     * @return array|stdClass Retorna un objeto con los valores acumulados de los impuestos:
     *  - `total_impuestos_retenidos`: Suma de los impuestos retenidos.
     *  - `total_impuestos_trasladados`: Suma de los impuestos trasladados.
     *  En caso de error, retorna un array con el mensaje de error y los datos asociados.
     *
     * @example
     * // Ejemplo de uso exitoso
     * $data_partida = new stdClass();
     * $data_partida->partida = [
     *     'factura_total_traslados' => 50.75,
     *     'factura_total_retenciones' => 30.25
     * ];
     *
     * $modelo_partida = new _partida();
     * $modelo_partida->tabla = 'factura';
     *
     * $resultado = $this->acumula_totales($data_partida, $modelo_partida, 100.00, 80.00);
     * print_r($resultado);
     * // Salida esperada:
     * // stdClass Object (
     * //     [total_impuestos_retenidos] => 110.25
     * //     [total_impuestos_trasladados] => 150.75
     * // )
     *
     * @example
     * // Ejemplo de error: partida no válida
     * $data_partida = new stdClass(); // Sin datos de partida
     * $modelo_partida = new _partida();
     * $modelo_partida->tabla = 'factura';
     *
     * $resultado = $this->acumula_totales($data_partida, $modelo_partida, 0, 0);
     * print_r($resultado);
     * // Salida esperada:
     * // [
     * //     'error' => true,
     * //     'mensaje' => 'Error al validar partida',
     * //     'data' => [...detalle del error...]
     * // ]
     *
     * @example
     * // Ejemplo de valores por defecto cuando no existen en la partida
     * $data_partida = new stdClass();
     * $data_partida->partida = []; // Sin claves específicas
     *
     * $modelo_partida = new _partida();
     * $modelo_partida->tabla = 'factura';
     *
     * $resultado = $this->acumula_totales($data_partida, $modelo_partida, 0, 0);
     * print_r($resultado);
     * // Salida esperada:
     * // stdClass Object (
     * //     [total_impuestos_retenidos] => 0
     * //     [total_impuestos_trasladados] => 0
     * // )
     */
    private function acumula_totales(
        stdClass $data_partida,
        _partida $modelo_partida,
        float $total_impuestos_retenidos,
        float $total_impuestos_trasladados
    ): array|stdClass
    {
        // Validar que la partida contiene los datos necesarios
        $valida = $this->valida_data_partida(data_partida: $data_partida, modelo_partida: $modelo_partida);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar partida', data: $valida);
        }

        // Generar claves para obtener los valores de impuestos
        $key_importe_total_traslado = $modelo_partida->tabla . '_total_traslados';
        $key_importe_total_retenido = $modelo_partida->tabla . '_total_retenciones';

        // Verificar si existen los valores, si no, inicializarlos en 0
        if (!isset($data_partida->partida[$key_importe_total_traslado])) {
            $data_partida->partida[$key_importe_total_traslado] = 0;
        }
        if (!isset($data_partida->partida[$key_importe_total_retenido])) {
            $data_partida->partida[$key_importe_total_retenido] = 0;
        }

        // Acumular impuestos trasladados y retenidos
        $total_impuestos_trasladados += $data_partida->partida[$key_importe_total_traslado];
        $total_impuestos_retenidos += $data_partida->partida[$key_importe_total_retenido];

        // Crear objeto con los valores totales acumulados
        $totales = new stdClass();
        $totales->total_impuestos_retenidos = $total_impuestos_retenidos;
        $totales->total_impuestos_trasladados = $total_impuestos_trasladados;

        return $totales;
    }



    /**
     * REG
     * Carga y acumula los totales de impuestos retenidos y trasladados en una partida.
     *
     * Esta función realiza validaciones sobre los datos de la partida, acumula los impuestos
     * correspondientes y obtiene los impuestos globales aplicables. Retorna un objeto con los
     * totales finales de impuestos retenidos y trasladados, además de los impuestos globales.
     *
     * @param stdClass $data_partida Objeto con la información de la partida, incluyendo impuestos.
     * @param _partida $modelo_partida Modelo de la partida con la tabla de referencia.
     * @param _data_impuestos $modelo_retencion Modelo para la gestión de impuestos retenidos.
     * @param _data_impuestos $modelo_traslado Modelo para la gestión de impuestos trasladados.
     * @param array $ret_global Array con los impuestos retenidos a nivel global.
     * @param float $total_impuestos_retenidos Total acumulado de impuestos retenidos.
     * @param float $total_impuestos_trasladados Total acumulado de impuestos trasladados.
     * @param array $trs_global Array con los impuestos trasladados a nivel global.
     *
     * @return stdClass|array Retorna un objeto con los siguientes valores si es exitoso:
     *  - `total_impuestos_retenidos`: Suma de los impuestos retenidos.
     *  - `total_impuestos_trasladados`: Suma de los impuestos trasladados.
     *  - `ret_global`: Array de impuestos retenidos globales actualizados.
     *  - `trs_global`: Array de impuestos trasladados globales actualizados.
     *
     * En caso de error, retorna un array con un mensaje de error y los datos relacionados.
     *
     * @example
     * // Ejemplo de uso exitoso
     * $data_partida = new stdClass();
     * $data_partida->partida = [
     *     'factura_total_traslados' => 120.50,
     *     'factura_total_retenciones' => 80.25
     * ];
     *
     * $modelo_partida = new _partida();
     * $modelo_partida->tabla = 'factura';
     *
     * $modelo_retencion = new _data_impuestos();
     * $modelo_traslado = new _data_impuestos();
     *
     * $ret_global = [];
     * $trs_global = [];
     *
     * $resultado = $this->carga_totales($data_partida, $modelo_partida, $modelo_retencion,
     *     $modelo_traslado, $ret_global, 50.00, 100.00, $trs_global);
     * print_r($resultado);
     * // Salida esperada:
     * // stdClass Object (
     * //     [total_impuestos_trasladados] => 220.50
     * //     [total_impuestos_retenidos] => 130.25
     * //     [ret_global] => [... impuestos retenidos globales actualizados ...]
     * //     [trs_global] => [... impuestos trasladados globales actualizados ...]
     * // )
     *
     * @example
     * // Ejemplo de error: datos de partida inválidos
     * $data_partida = new stdClass(); // Sin datos de partida
     * $resultado = $this->carga_totales($data_partida, $modelo_partida, $modelo_retencion,
     *     $modelo_traslado, $ret_global, 0, 0, $trs_global);
     * print_r($resultado);
     * // Salida esperada:
     * // [
     * //     'error' => true,
     * //     'mensaje' => 'Error al validar partida',
     * //     'data' => [... detalle del error ...]
     * // ]
     */
    private function carga_totales(
        stdClass $data_partida,
        _partida $modelo_partida,
        _data_impuestos $modelo_retencion,
        _data_impuestos $modelo_traslado,
        array $ret_global,
        float $total_impuestos_retenidos,
        float $total_impuestos_trasladados,
        array $trs_global
    ): stdClass|array
    {
        // Validar los datos de la partida
        $valida = $this->valida_data_partida(data_partida: $data_partida, modelo_partida: $modelo_partida);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar partida', data: $valida);
        }

        // Validar impuestos en la partida
        $valida = $this->valida_impuestos_partida(data_partida: $data_partida);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar impuestos de la partida', data: $valida);
        }

        // Acumular los totales de impuestos
        $totales = $this->acumula_totales(
            data_partida: $data_partida,
            modelo_partida: $modelo_partida,
            total_impuestos_retenidos: $total_impuestos_retenidos,
            total_impuestos_trasladados: $total_impuestos_trasladados
        );

        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al cargar totales', data: $totales);
        }

        // Asignar los totales acumulados
        $total_impuestos_trasladados = $totales->total_impuestos_trasladados;
        $total_impuestos_retenidos = $totales->total_impuestos_retenidos;

        // Calcular impuestos globales
        $globales_imps = $this->impuestos_globales(
            data_partida: $data_partida,
            modelo_partida: $modelo_partida,
            modelo_retencion: $modelo_retencion,
            modelo_traslado: $modelo_traslado,
            ret_global: $ret_global,
            trs_global: $trs_global
        );

        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al cargar impuestos globales', data: $globales_imps);
        }

        // Asignar impuestos globales
        $ret_global = $globales_imps->ret_global;
        $trs_global = $globales_imps->trs_global;

        // Crear objeto con los totales finales
        $totales_fin = new stdClass();
        $totales_fin->total_impuestos_trasladados = $total_impuestos_trasladados;
        $totales_fin->total_impuestos_retenidos = $total_impuestos_retenidos;
        $totales_fin->ret_global = $ret_global;
        $totales_fin->trs_global = $trs_global;

        return $totales_fin;
    }


    /**
     * REG
     * Genera un objeto `concepto` a partir de los datos de la partida y las claves proporcionadas.
     *
     * Esta función verifica que los datos de la partida contengan la información necesaria antes de crear
     * el objeto `concepto`. Si faltan claves requeridas o si los valores no son válidos, genera un error.
     *
     * @param stdClass $data_partida Objeto que contiene la información de la partida.
     *        - `partida` (array): Datos de la partida, incluyendo valores numéricos y claves fiscales.
     * @param stdClass $keys_part Objeto que contiene las claves necesarias para extraer datos de la partida.
     *        - `cantidad` (string): Clave del campo cantidad.
     *        - `descripcion` (string): Clave del campo descripción.
     *        - `valor_unitario` (string): Clave del campo valor unitario.
     *        - `importe` (string): Clave del campo importe.
     *
     * @return stdClass|array Retorna un objeto con la estructura del concepto generado si la operación es exitosa.
     * En caso de error, retorna un array con la información del error.
     *
     * @throws errores Si no se encuentra la clave identificadora de la partida o si los valores no son válidos.
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
     * $data_partida = new stdClass();
     * $data_partida->partida = [
     *     'cat_sat_producto_codigo' => '01010101',
     *     'fc_partida_cantidad' => 5,
     *     'cat_sat_unidad_codigo' => 'H87',
     *     'fc_partida_descripcion' => 'Producto genérico',
     *     'fc_partida_valor_unitario' => 100.00,
     *     'fc_partida_importe' => 500.00,
     *     'cat_sat_obj_imp_codigo' => '02',
     *     'com_producto_codigo' => 'PROD123',
     *     'cat_sat_unidad_descripcion' => 'Pieza'
     * ];
     *
     * $keys_part = new stdClass();
     * $keys_part->cantidad = 'fc_partida_cantidad';
     * $keys_part->descripcion = 'fc_partida_descripcion';
     * $keys_part->valor_unitario = 'fc_partida_valor_unitario';
     * $keys_part->importe = 'fc_partida_importe';
     *
     * $resultado = $this->concepto($data_partida, $keys_part);
     * print_r($resultado);
     * ```
     *
     * @example
     * Ejemplo de salida exitosa:
     * ```php
     * stdClass Object
     * (
     *     [clave_prod_serv] => "01010101"
     *     [cantidad] => 5
     *     [clave_unidad] => "H87"
     *     [descripcion] => "Producto genérico"
     *     [valor_unitario] => "100.00"
     *     [importe] => "500.00"
     *     [objeto_imp] => "02"
     *     [no_identificacion] => "PROD123"
     *     [unidad] => "Pieza"
     * )
     * ```
     *
     * @example
     * Ejemplo de salida con error si falta una clave en `$keys_part`:
     * ```php
     * Array
     * (
     *     [error] => 1
     *     [mensaje] => "Error $keys_part->valor_unitario no existe"
     *     [data] => stdClass Object()
     *     [es_final] => true
     * )
     * ```
     *
     * @example
     * Ejemplo de salida con error si un valor no es numérico:
     * ```php
     * Array
     * (
     *     [error] => 1
     *     [mensaje] => "Error $data_partida->partida[fc_partida_valor_unitario] debe ser un número"
     *     [data] => stdClass Object()
     *     [es_final] => true
     * )
     * ```
     */
    private function concepto(stdClass $data_partida, stdClass $keys_part): stdClass|array
    {
        // Verifica que la partida exista en el objeto data_partida
        if (!isset($data_partida->partida)) {
            return $this->error->error(
                mensaje: 'Error $data_partida->partida no existe',
                data: $data_partida,
                es_final: true
            );
        }


        $valida  = $this->valida_partida(data_partida: $data_partida,keys_part: $keys_part);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al $validar data_partida', data: $valida);
        }

        // Creación del objeto concepto con los valores validados
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


    /**
     * REG
     * Asigna la cuenta predial a un concepto si el producto de la partida lo requiere.
     *
     * Esta función verifica si el producto en la partida está marcado para aplicar cuenta predial
     * (`com_producto_aplica_predial` == 'activo'). Si es así, obtiene la cuenta predial mediante
     * `fc_cuenta_predial_numero()` y la asigna al concepto. Si ocurre algún error en la validación o
     * en la consulta de la cuenta predial, se devuelve un array con la información del error.
     *
     * @param stdClass $concepto Objeto que representa un concepto de facturación.
     *        - Este objeto debe estar previamente estructurado con los datos del producto.
     * @param string $key_filtro_id Clave del campo que se utilizará como filtro en la consulta.
     * @param _cuenta_predial $modelo_predial Modelo que maneja los datos de cuentas prediales.
     * @param array $partida Datos de la partida que contienen información sobre el producto y si aplica cuenta predial.
     *        - `com_producto_aplica_predial` (string): Indica si el producto requiere cuenta predial ('activo' o no).
     * @param int $registro_id Identificador del registro del cual se quiere obtener la cuenta predial.
     *
     * @return stdClass|array Retorna el objeto `concepto` con la cuenta predial asignada si aplica.
     * En caso de error, retorna un array con la información del error.
     *
     * @throws errores Si `$registro_id` es menor o igual a 0, si `$key_filtro_id` está vacío o si la consulta falla.
     *
     * Estructura del error generado:
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
     * Ejemplo de entrada válida:
     * ```php
     * $concepto = new stdClass();
     * $concepto->clave_prod_serv = "01010101";
     *
     * $modelo_predial = new _cuenta_predial();
     * $key_filtro_id = 'fc_cuenta_predial_id';
     * $partida = [
     *     'com_producto_aplica_predial' => 'activo'
     * ];
     * $registro_id = 123;
     *
     * $resultado = $this->cuenta_predial($concepto, $key_filtro_id, $modelo_predial, $partida, $registro_id);
     * print_r($resultado);
     * ```
     *
     * @example
     * Ejemplo de salida esperada si el producto aplica cuenta predial:
     * ```php
     * stdClass Object
     * (
     *     [clave_prod_serv] => "01010101"
     *     [cuenta_predial] => "123456789"
     * )
     * ```
     *
     * @example
     * Ejemplo de salida esperada si el producto no aplica cuenta predial:
     * ```php
     * stdClass Object
     * (
     *     [clave_prod_serv] => "01010101"
     * )
     * ```
     *
     * @example
     * Ejemplo de salida con error si `$registro_id` es 0:
     * ```php
     * Array
     * (
     *     [error] => 1
     *     [mensaje] => "Error: el registro_id debe ser mayor a 0"
     *     [data] => 0
     *     [es_final] => true
     * )
     * ```
     *
     * @example
     * Ejemplo de salida con error si `$key_filtro_id` está vacío:
     * ```php
     * Array
     * (
     *     [error] => 1
     *     [mensaje] => "Error: el key_filtro_id está vacío"
     *     [data] => ""
     *     [es_final] => true
     * )
     * ```
     *
     * @example
     * Ejemplo de salida con error si no se encuentra cuenta predial:
     * ```php
     * Array
     * (
     *     [error] => 1
     *     [mensaje] => "Error al obtener cuenta predial"
     *     [data] => stdClass Object()
     * )
     * ```
     */
    private function cuenta_predial(
        stdClass $concepto,
        string $key_filtro_id,
        _cuenta_predial $modelo_predial,
        array $partida,
        int $registro_id
    ): array|stdClass
    {
        // Validar que el ID del registro sea mayor a 0
        if ($registro_id <= 0) {
            return $this->error->error(
                mensaje: "Error: el registro_id debe ser mayor a 0",
                data: $registro_id,
                es_final: true
            );
        }

        // Validar que la clave del filtro no esté vacía
        $key_filtro_id = trim($key_filtro_id);
        if ($key_filtro_id === '') {
            return $this->error->error(
                mensaje: "Error: el $key_filtro_id está vacío",
                data: $registro_id,
                es_final: true
            );
        }

        // Verificar si el producto en la partida requiere cuenta predial
        if (isset($partida['com_producto_aplica_predial']) && $partida['com_producto_aplica_predial'] === 'activo') {
            // Obtener el número de cuenta predial del registro
            $fc_cuenta_predial_numero = $this->fc_cuenta_predial_numero(
                key_filtro_id: $key_filtro_id,
                modelo_predial: $modelo_predial,
                registro_id: $registro_id
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


    /**
     * REG
     * Obtiene y formatea el valor del descuento aplicado a una partida.
     *
     * Esta función verifica que la clave del descuento proporcionada no esté vacía, luego busca el descuento
     * en los datos de la partida y lo formatea a dos decimales. Si la clave del descuento no existe en la partida,
     * se asigna un valor predeterminado de `0.0`. En caso de error en cualquier etapa, se retorna un array con la
     * información del error.
     *
     * @param stdClass $data_partida Objeto que contiene la información de la partida.
     *        - `partida` (array): Datos de la partida, incluyendo claves fiscales y valores numéricos.
     * @param string $key_descuento Clave dentro de la partida que representa el descuento.
     *
     * @return float|array Retorna el valor del descuento formateado a dos decimales si la operación es exitosa.
     * En caso de error, retorna un array con la información del error.
     *
     * @throws errores Si `$key_descuento` está vacío o si hay un error al formatear el descuento.
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
     * $data_partida = new stdClass();
     * $data_partida->partida = [
     *     'fc_partida_descuento' => 50.25
     * ];
     *
     * $key_descuento = 'fc_partida_descuento';
     *
     * $resultado = $this->descuento($data_partida, $key_descuento);
     * print_r($resultado);
     * ```
     *
     * @example
     * Ejemplo de salida exitosa:
     * ```php
     * 50.25
     * ```
     *
     * @example
     * Ejemplo de salida con error si `$key_descuento` está vacío:
     * ```php
     * Array
     * (
     *     [error] => 1
     *     [mensaje] => "Error $key_descuento está vacío"
     *     [data] => ""
     *     [es_final] => true
     * )
     * ```
     *
     * @example
     * Ejemplo de salida cuando no existe la clave en `$data_partida->partida`:
     * ```php
     * 0.0
     * ```
     */
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



    /**
     * REG
     * Obtiene el número de cuenta predial asociado a un registro en la base de datos.
     *
     * Esta función valida la existencia del `registro_id`, asegura que `key_filtro_id` no esté vacío,
     * y realiza una consulta mediante `r_fc_cuenta_predial()` para obtener los datos de la cuenta predial.
     * Luego, extrae y retorna la descripción (número de cuenta predial).
     *
     * @param string $key_filtro_id Clave del campo que se utilizará como filtro en la consulta.
     * @param _cuenta_predial $modelo_predial Modelo que maneja los datos de cuentas prediales.
     * @param int $registro_id Identificador del registro del cual se quiere obtener la cuenta predial.
     *
     * @return array|string Retorna el número de cuenta predial si la operación es exitosa.
     * En caso de error, retorna un array con la información del error.
     *
     * @throws errores Si `$registro_id` es menor o igual a 0, si `$key_filtro_id` está vacío o si la consulta falla.
     *
     * Estructura del error generado:
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
     * Ejemplo de entrada válida:
     * ```php
     * $modelo_predial = new _cuenta_predial();
     * $key_filtro_id = 'fc_cuenta_predial_id';
     * $registro_id = 123;
     *
     * $resultado = $this->fc_cuenta_predial_numero($key_filtro_id, $modelo_predial, $registro_id);
     * print_r($resultado);
     * ```
     *
     * @example
     * Ejemplo de salida esperada si existe una cuenta predial:
     * ```php
     * "123456789"
     * ```
     *
     * @example
     * Ejemplo de salida con error si `$registro_id` es 0:
     * ```php
     * Array
     * (
     *     [error] => 1
     *     [mensaje] => "Error: el registro_id debe ser mayor a 0"
     *     [data] => 0
     *     [es_final] => true
     * )
     * ```
     *
     * @example
     * Ejemplo de salida con error si `$key_filtro_id` está vacío:
     * ```php
     * Array
     * (
     *     [error] => 1
     *     [mensaje] => "Error: el key_filtro_id está vacío"
     *     [data] => ""
     *     [es_final] => true
     * )
     * ```
     *
     * @example
     * Ejemplo de salida con error si no se encuentra cuenta predial:
     * ```php
     * Array
     * (
     *     [error] => 1
     *     [mensaje] => "Error al obtener cuenta predial"
     *     [data] => stdClass Object()
     * )
     * ```
     */
    private function fc_cuenta_predial_numero(
        string $key_filtro_id,
        _cuenta_predial $modelo_predial,
        int $registro_id
    ): array|string
    {
        // Validar que el ID del registro sea mayor a 0
        if ($registro_id <= 0) {
            return $this->error->error(
                mensaje: "Error: el registro_id debe ser mayor a 0",
                data: $registro_id,
                es_final: true
            );
        }

        // Validar que la clave del filtro no esté vacía
        $key_filtro_id = trim($key_filtro_id);
        if ($key_filtro_id === '') {
            return $this->error->error(
                mensaje: 'Error: el $key_filtro_id está vacío',
                data: $registro_id,
                es_final: true
            );
        }

        // Obtener la cuenta predial asociada al registro
        $r_fc_cuenta_predial = $this->r_fc_cuenta_predial(
            key_filtro_id: $key_filtro_id,
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


    /**
     * REG
     * Genera un concepto de facturación a partir de la información de una partida.
     *
     * Esta función realiza validaciones en la estructura de la partida, asegura la existencia y el
     * formato correcto de los impuestos trasladados y retenidos, e integra diversos datos como
     * descuentos, impuestos y cuenta predial en el concepto generado.
     *
     * @param stdClass $data_partida Objeto que contiene la información de la partida.
     *        - `partida` (array): Datos de la partida a validar.
     *        - `traslados` (stdClass): Objeto que contiene los impuestos trasladados asociados a la partida.
     *        - `retenidos` (stdClass): Objeto que contiene los impuestos retenidos asociados a la partida.
     * @param string $key_filtro_id Clave del campo que se utilizará como filtro en la consulta.
     * @param _partida $modelo_partida Instancia del modelo de partida que contiene la estructura y claves de identificación.
     * @param _cuenta_predial $modelo_predial Modelo que maneja los datos de cuentas prediales.
     * @param _data_impuestos $modelo_retencion Modelo que maneja los datos de impuestos retenidos.
     * @param _data_impuestos $modelo_traslado Modelo que maneja los datos de impuestos trasladados.
     * @param int $registro_id Identificador del registro del cual se quiere generar el concepto.
     *
     * @return stdClass|array Retorna un objeto `concepto` con los datos estructurados si la operación es exitosa.
     * En caso de error, retorna un array con la información del error.
     *
     * @throws errores Si `$registro_id` es menor o igual a 0, si `$key_filtro_id` está vacío,
     * si `data_partida` no contiene los datos esperados o si la integración de impuestos falla.
     *
     * Estructura del error generado:
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
     * Ejemplo de entrada válida:
     * ```php
     * $data_partida = new stdClass();
     * $data_partida->partida = [
     *     'fc_partida_cantidad' => 5,
     *     'fc_partida_descripcion' => 'Producto de prueba',
     *     'fc_partida_valor_unitario' => 100.00,
     *     'fc_partida_sub_total_base' => 500.00
     * ];
     * $data_partida->traslados = (object) [
     *     (object) ['tipo_impuesto' => 'IVA', 'tasa' => 16, 'importe' => 80.00]
     * ];
     * $data_partida->retenidos = (object) [
     *     (object) ['tipo_impuesto' => 'ISR', 'tasa' => 10, 'importe' => 50.00]
     * ];
     *
     * $modelo_partida = new _partida();
     * $modelo_partida->tabla = 'fc_partida';
     *
     * $modelo_predial = new _cuenta_predial();
     * $modelo_retencion = new _data_impuestos();
     * $modelo_traslado = new _data_impuestos();
     *
     * $key_filtro_id = 'fc_cuenta_predial_id';
     * $registro_id = 123;
     *
     * $resultado = $this->genera_concepto($data_partida, $key_filtro_id, $modelo_partida,
     *     $modelo_predial, $modelo_retencion, $modelo_traslado, $registro_id);
     * print_r($resultado);
     * ```
     *
     * @example
     * Ejemplo de salida esperada:
     * ```php
     * stdClass Object
     * (
     *     [clave_prod_serv] => "01010101"
     *     [cantidad] => 5
     *     [clave_unidad] => "H87"
     *     [descripcion] => "Producto de prueba"
     *     [valor_unitario] => 100.00
     *     [importe] => 500.00
     *     [objeto_imp] => "02"
     *     [no_identificacion] => "12345"
     *     [unidad] => "Pieza"
     *     [descuento] => 50.00
     *     [impuestos] => Array
     *         (
     *             [0] => stdClass Object
     *                 (
     *                     [traslados] => Array
     *                         (
     *                             [0] => stdClass Object
     *                                 (
     *                                     [tipo_impuesto] => "IVA"
     *                                     [tasa] => 16
     *                                     [importe] => 80.00
     *                                 )
     *                         )
     *                     [retenciones] => Array
     *                         (
     *                             [0] => stdClass Object
     *                                 (
     *                                     [tipo_impuesto] => "ISR"
     *                                     [tasa] => 10
     *                                     [importe] => 50.00
     *                                 )
     *                         )
     *                 )
     *         )
     * )
     * ```
     *
     * @example
     * Ejemplo de salida con error si `$registro_id` es 0:
     * ```php
     * Array
     * (
     *     [error] => 1
     *     [mensaje] => "Error: el registro_id debe ser mayor a 0"
     *     [data] => 0
     *     [es_final] => true
     * )
     * ```
     *
     * @example
     * Ejemplo de salida con error si `$key_filtro_id` está vacío:
     * ```php
     * Array
     * (
     *     [error] => 1
     *     [mensaje] => "Error: el key_filtro_id está vacío"
     *     [data] => ""
     *     [es_final] => true
     * )
     * ```
     */
    private function genera_concepto(stdClass $data_partida, string $key_filtro_id, _partida $modelo_partida,
                                     _cuenta_predial $modelo_predial, _data_impuestos $modelo_retencion,
                                     _data_impuestos $modelo_traslado, int $registro_id): array|stdClass
    {

        $key_filtro_id = trim($key_filtro_id);
        if($key_filtro_id === ''){
            return $this->error->error(
                mensaje: 'Error: el $key_filtro_id esta vacio', data: $key_filtro_id, es_final: true
            );
        }


        $valida = $this->verifica_partida(data_partida: $data_partida,modelo_partida:  $modelo_partida);
        if (errores::$error) {
            return $this->error->error(
                mensaje: 'Error al validar data_partida',
                data: $valida
            );
        }

        if (!isset($data_partida->traslados)) {
            return $this->error->error(
                mensaje: 'Error $data_partida->traslados no existe',
                data: $data_partida
            );
        }

        if (!is_object($data_partida->traslados)) {
            return $this->error->error(
                mensaje: 'Error $data_partida->traslados debe ser un objeto',
                data: $data_partida
            );
        }

        if (!isset($data_partida->retenidos)) {
            return $this->error->error(
                mensaje: 'Error $data_partida->retenidos no existe',
                data: $data_partida
            );
        }

        // Validación: Verifica que los impuestos retenidos sean un objeto
        if (!is_object($data_partida->retenidos)) {
            return $this->error->error(
                mensaje: 'Error $data_partida->retenidos debe ser un objeto',
                data: $data_partida
            );
        }

        if ($registro_id <= 0) {
            return $this->error->error(
                mensaje: "Error: el registro_id debe ser mayor a 0",
                data: $registro_id,
                es_final: true
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
     * REG
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

    /**
     * REG
     * Calcula los impuestos globales trasladados y retenidos a partir de los datos de una partida.
     *
     * Este método procesa los impuestos trasladados y retenidos de una partida específica, acumulándolos en estructuras
     * de datos globales. Utiliza la clase `_impuestos` para realizar la acumulación de importes.
     *
     * @param stdClass $data_partida Objeto que contiene los impuestos de la partida. Debe tener las propiedades:
     *                               - `traslados`: array con impuestos trasladados.
     *                               - `retenidos`: array con impuestos retenidos.
     * @param _partida $modelo_partida Modelo que representa la partida sobre la que se calculan los impuestos.
     * @param _data_impuestos $modelo_retencion Modelo de los impuestos retenidos, usado para extraer el nombre de la tabla.
     * @param _data_impuestos $modelo_traslado Modelo de los impuestos trasladados, usado para extraer el nombre de la tabla.
     * @param array $ret_global Arreglo de acumulación de impuestos retenidos a nivel global.
     * @param array $trs_global Arreglo de acumulación de impuestos trasladados a nivel global.
     *
     * @return stdClass|array Retorna un objeto con los impuestos globales:
     *                        - `ret_global`: impuestos retenidos acumulados.
     *                        - `trs_global`: impuestos trasladados acumulados.
     *                        En caso de error, retorna un array con detalles del error.
     *
     * @throws errores Si hay errores en la validación de datos o en la ejecución de la acumulación de impuestos.
     *
     * @example
     * ```php
     * $data_partida = new stdClass();
     * $data_partida->traslados = [
     *     (object)["impuesto" => "IVA", "tasa" => 0.16, "importe" => 100],
     *     (object)["impuesto" => "ISR", "tasa" => 0.10, "importe" => 50]
     * ];
     * $data_partida->retenidos = [
     *     (object)["impuesto" => "IVA", "tasa" => 0.04, "importe" => 20],
     * ];
     *
     * $modelo_partida = new _partida();
     * $modelo_partida->tabla = "fc_partida";
     *
     * $modelo_retencion = new _data_impuestos();
     * $modelo_retencion->tabla = "fc_retenido";
     *
     * $modelo_traslado = new _data_impuestos();
     * $modelo_traslado->tabla = "fc_traslado";
     *
     * $ret_global = [];
     * $trs_global = [];
     *
     * $resultado = impuestos_globales($data_partida, $modelo_partida, $modelo_retencion, $modelo_traslado, $ret_global, $trs_global);
     *
     * print_r($resultado);
     * ```
     *
     * @example Salida esperada:
     * ```php
     * stdClass Object
     * (
     *     [ret_global] => Array
     *         (
     *             [fc_retenido_IVA] => 20
     *         )
     *
     *     [trs_global] => Array
     *         (
     *             [fc_traslado_IVA] => 100
     *             [fc_traslado_ISR] => 50
     *         )
     * )
     * ```
     */
    private function impuestos_globales(
        stdClass $data_partida, _partida $modelo_partida, _data_impuestos $modelo_retencion,
        _data_impuestos $modelo_traslado, array $ret_global, array $trs_global): array|stdClass
    {
        $valida = $this->valida_impuestos_partida(data_partida: $data_partida);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar partida', data: $valida);
        }

        // Obtener el nombre de la clave del importe en los impuestos trasladados
        $key_traslado_importe = $modelo_traslado->tabla . '_importe';
        $trs_global = (new _impuestos())->impuestos_globales(
            impuestos: $data_partida->traslados, global_imp: $trs_global, key_importe: $key_traslado_importe,
            name_tabla_partida: $modelo_partida->tabla
        );

        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al inicializar acumulado de trasladados', data: $trs_global);
        }

        // Obtener el nombre de la clave del importe en los impuestos retenidos
        $key_retenido_importe = $modelo_retencion->tabla . '_importe';
        $ret_global = (new _impuestos())->impuestos_globales(
            impuestos: $data_partida->retenidos, global_imp: $ret_global, key_importe: $key_retenido_importe,
            name_tabla_partida: $modelo_partida->tabla
        );

        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al inicializar acumulado de retenidos', data: $ret_global);
        }

        // Crear objeto de salida con los impuestos globales acumulados
        $impuestos_glb = new stdClass();
        $impuestos_glb->ret_global = $ret_global;
        $impuestos_glb->trs_global = $trs_global;

        return $impuestos_glb;
    }


    /**
     * REG
     * Inicializa la estructura de impuestos dentro del objeto `concepto`.
     *
     * Esta función agrega una propiedad `impuestos` al objeto `concepto`, que contendrá arrays vacíos
     * para `traslados` y `retenciones`. Esto permite estructurar correctamente los impuestos de un concepto
     * antes de ser llenados con datos específicos.
     *
     * @param stdClass $concepto Objeto que representa un concepto de facturación.
     *        - Este objeto debe estar previamente estructurado con datos del producto.
     *
     * @return stdClass Retorna el objeto `concepto` con la estructura de impuestos inicializada.
     *
     * @example
     * Ejemplo de entrada:
     * ```php
     * $concepto = new stdClass();
     * $concepto->clave_prod_serv = "01010101";
     * $concepto->cantidad = 5;
     * $concepto->clave_unidad = "H87";
     * $concepto->descripcion = "Producto genérico";
     * $concepto->valor_unitario = "100.00";
     * $concepto->importe = "500.00";
     *
     * $resultado = $this->inicializa_impuestos_de_concepto($concepto);
     * print_r($resultado);
     * ```
     *
     * @example
     * Ejemplo de salida esperada:
     * ```php
     * stdClass Object
     * (
     *     [clave_prod_serv] => "01010101"
     *     [cantidad] => 5
     *     [clave_unidad] => "H87"
     *     [descripcion] => "Producto genérico"
     *     [valor_unitario] => "100.00"
     *     [importe] => "500.00"
     *     [impuestos] => Array
     *         (
     *             [0] => stdClass Object
     *                 (
     *                     [traslados] => Array()
     *                     [retenciones] => Array()
     *                 )
     *         )
     * )
     * ```
     */
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


    /**
     * REG
     * Integra el descuento en la estructura de un concepto generado a partir de los datos de una partida.
     *
     * La función valida la existencia de los datos de la partida, obtiene las claves necesarias,
     * valida la partida, genera el concepto y calcula el descuento, integrándolo en la estructura final del concepto.
     * Si alguna validación falla, devuelve un array con la información del error.
     *
     * @param stdClass $data_partida Objeto que contiene la información de la partida.
     *        - `partida` (array): Datos de la partida, incluyendo claves fiscales y valores numéricos.
     * @param _partida $modelo_partida Instancia del modelo de partida que contiene la estructura y claves de identificación.
     *
     * @return stdClass|array Retorna un objeto `concepto` con el descuento integrado si la operación es exitosa.
     * En caso de error, retorna un array con la información del error.
     *
     * @throws errores Si `$data_partida->partida` no existe, si no se pueden obtener las claves necesarias,
     * si los datos de la partida no son válidos o si hay un error en el cálculo del descuento.
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
     * Ejemplo de entrada válida:
     * ```php
     * $data_partida = new stdClass();
     * $data_partida->partida = [
     *     'cat_sat_producto_codigo' => '01010101',
     *     'fc_partida_cantidad' => 5,
     *     'cat_sat_unidad_codigo' => 'H87',
     *     'fc_partida_descripcion' => 'Producto genérico',
     *     'fc_partida_valor_unitario' => 100.00,
     *     'fc_partida_importe' => 500.00,
     *     'cat_sat_obj_imp_codigo' => '02',
     *     'com_producto_codigo' => 'PROD123',
     *     'cat_sat_unidad_descripcion' => 'Pieza',
     *     'fc_partida_descuento' => 50.00
     * ];
     *
     * $modelo_partida = new _partida();
     * $modelo_partida->tabla = 'fc_partida';
     *
     * $resultado = $this->integra_descuento($data_partida, $modelo_partida);
     * print_r($resultado);
     * ```
     *
     * @example
     * Ejemplo de salida exitosa:
     * ```php
     * stdClass Object
     * (
     *     [clave_prod_serv] => "01010101"
     *     [cantidad] => 5
     *     [clave_unidad] => "H87"
     *     [descripcion] => "Producto genérico"
     *     [valor_unitario] => "100.00"
     *     [importe] => "500.00"
     *     [objeto_imp] => "02"
     *     [no_identificacion] => "PROD123"
     *     [unidad] => "Pieza"
     *     [descuento] => 50.00
     * )
     * ```
     *
     * @example
     * Ejemplo de salida con error si `$data_partida->partida` no existe:
     * ```php
     * Array
     * (
     *     [error] => 1
     *     [mensaje] => "Error $data_partida->partida no existe"
     *     [data] => stdClass Object()
     *     [es_final] => true
     * )
     * ```
     *
     * @example
     * Ejemplo de salida con error si hay problemas al obtener las claves de la partida:
     * ```php
     * Array
     * (
     *     [error] => 1
     *     [mensaje] => "Error al obtener $keys_part"
     *     [data] => stdClass Object()
     * )
     * ```
     */
    private function integra_descuento(stdClass $data_partida, _partida $modelo_partida): stdClass|array
    {
        $valida = $this->verifica_partida(data_partida: $data_partida,modelo_partida:  $modelo_partida);
        if (errores::$error) {
            return $this->error->error(
                mensaje: 'Error al validar data_partida',
                data: $valida
            );
        }

        $keys_part  = $this->keys_partida(modelo_partida: $modelo_partida);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener $keys_part', data: $valida);
        }
        // Genera el objeto concepto a partir de la partida
        $concepto = $this->concepto(data_partida: $data_partida, keys_part: $keys_part);
        if (errores::$error) {
            return $this->error->error(
                mensaje: 'Error al maquetar $concepto',
                data: $concepto
            );
        }

        // Obtiene el valor del descuento
        $descuento = $this->descuento(data_partida: $data_partida, key_descuento: $keys_part->descuento);
        if (errores::$error) {
            return $this->error->error(
                mensaje: 'Error al maquetar descuento',
                data: $descuento
            );
        }

        // Agrega el descuento al concepto
        $concepto->descuento = $descuento;

        return $concepto;
    }


    /**
     * REG
     * Integra partidas en una factura o comprobante fiscal, validando y acumulando los impuestos de cada partida.
     *
     * Esta función procesa los datos de una partida para generar conceptos fiscales,
     * asigna impuestos trasladados y retenidos, y acumula los totales de impuestos.
     * Retorna un objeto con los conceptos generados, el registro actualizado y los totales de impuestos.
     *
     * @param array $conceptos Arreglo de conceptos fiscales acumulados.
     * @param string $key Clave identificadora de la partida dentro del registro.
     * @param string $key_filtro_id Clave para filtrar los registros de la partida.
     * @param _partida $modelo_partida Modelo de la partida.
     * @param _cuenta_predial $modelo_predial Modelo para la gestión de cuentas prediales.
     * @param _data_impuestos $modelo_retencion Modelo para la gestión de impuestos retenidos.
     * @param _data_impuestos $modelo_traslado Modelo para la gestión de impuestos trasladados.
     * @param array $partida Datos específicos de la partida a procesar.
     * @param array $registro Registro en el que se integra la partida.
     * @param int $registro_id Identificador del registro donde se integra la partida.
     * @param array $ret_global Array con los impuestos retenidos globales.
     * @param float $total_impuestos_retenidos Total acumulado de impuestos retenidos.
     * @param float $total_impuestos_trasladados Total acumulado de impuestos trasladados.
     * @param array $trs_global Array con los impuestos trasladados globales.
     *
     * @return stdClass|array Retorna un objeto con los siguientes valores si es exitoso:
     *  - `ret_global`: Impuestos retenidos globales actualizados.
     *  - `trs_global`: Impuestos trasladados globales actualizados.
     *  - `total_impuestos_retenidos`: Total acumulado de impuestos retenidos.
     *  - `total_impuestos_trasladados`: Total acumulado de impuestos trasladados.
     *  - `conceptos`: Arreglo con los conceptos generados.
     *  - `registro`: Registro actualizado con la partida integrada.
     *
     * En caso de error, retorna un array con un mensaje de error y los datos relacionados.
     *
     * @example
     * // Ejemplo de uso exitoso
     * $conceptos = [];
     * $partida = ['factura_id' => 123, 'factura_total_traslados' => 50.00, 'factura_total_retenciones' => 20.00];
     * $registro = [];
     * $registro_id = 456;
     *
     * $modelo_partida = new _partida();
     * $modelo_partida->tabla = 'factura';
     *
     * $modelo_predial = new _cuenta_predial();
     * $modelo_retencion = new _data_impuestos();
     * $modelo_traslado = new _data_impuestos();
     *
     * $ret_global = [];
     * $trs_global = [];
     *
     * $resultado = $this->integra_partidas(
     *     $conceptos, 'clave_partida', 'factura_id', $modelo_partida,
     *     $modelo_predial, $modelo_retencion, $modelo_traslado,
     *     $partida, $registro, $registro_id, $ret_global, 0, 0, $trs_global
     * );
     * print_r($resultado);
     * // Salida esperada:
     * // stdClass Object (
     * //     [ret_global] => [... impuestos retenidos globales actualizados ...]
     * //     [trs_global] => [... impuestos trasladados globales actualizados ...]
     * //     [total_impuestos_retenidos] => 20.00
     * //     [total_impuestos_trasladados] => 50.00
     * //     [conceptos] => [... lista de conceptos generados ...]
     * //     [registro] => [... registro actualizado ...]
     * // )
     *
     * @example
     * // Ejemplo de error: key_filtro_id vacío
     * $resultado = $this->integra_partidas(
     *     $conceptos, 'clave_partida', '', $modelo_partida,
     *     $modelo_predial, $modelo_retencion, $modelo_traslado,
     *     $partida, $registro, $registro_id, $ret_global, 0, 0, $trs_global
     * );
     * print_r($resultado);
     * // Salida esperada:
     * // [
     * //     'error' => true,
     * //     'mensaje' => 'Error: el $key_filtro_id esta vacio',
     * //     'data' => ''
     * // ]
     */
    final public function integra_partidas(
        array $conceptos,
        string $key,
        string $key_filtro_id,
        _partida $modelo_partida,
        _cuenta_predial $modelo_predial,
        _data_impuestos $modelo_retencion,
        _data_impuestos $modelo_traslado,
        array $partida,
        array $registro,
        int $registro_id,
        array $ret_global,
        float $total_impuestos_retenidos,
        float $total_impuestos_trasladados,
        array $trs_global
    ): stdClass|array
    {
        // Validar que key_filtro_id no esté vacío
        $key_filtro_id = trim($key_filtro_id);
        if ($key_filtro_id === '') {
            return $this->error->error(
                mensaje: 'Error: el $key_filtro_id está vacío',
                data: $key_filtro_id,
                es_final: true
            );
        }

        // Validar que la partida contenga la clave de identificación de la tabla
        if (!isset($partida[$modelo_partida->key_id])) {
            return $this->error->error(
                mensaje: 'Error $partida[' . $modelo_partida->key_id . '] no existe ' . $modelo_partida->key_id,
                data: $partida,
                es_final: true
            );
        }

        // Validar que el registro_id sea mayor a 0
        if ($registro_id <= 0) {
            return $this->error->error(
                mensaje: "Error: el registro_id debe ser mayor a 0",
                data: $registro_id,
                es_final: true
            );
        }

        // Obtener datos de la partida
        $data_partida = $this->data_partida(
            modelo_partida: $modelo_partida,
            modelo_retencion: $modelo_retencion,
            modelo_traslado: $modelo_traslado,
            partida: $partida
        );
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al integrar producto temporal', data: $data_partida);
        }

        // Asignar impuestos trasladados y retenidos a la partida en el registro
        $registro['partidas'][$key]['traslados'] = $data_partida->traslados->registros;
        $registro['partidas'][$key]['retenidos'] = $data_partida->retenidos->registros;

        // Generar el concepto fiscal para la partida
        $concepto = $this->genera_concepto(
            data_partida: $data_partida,
            key_filtro_id: $key_filtro_id,
            modelo_partida: $modelo_partida,
            modelo_predial: $modelo_predial,
            modelo_retencion: $modelo_retencion,
            modelo_traslado: $modelo_traslado,
            registro_id: $registro_id
        );
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al integrar concepto', data: $concepto);
        }

        // Agregar el concepto generado a la lista de conceptos
        $conceptos[] = $concepto;

        // Acumular los totales de impuestos
        $totales = $this->carga_totales(
            data_partida: $data_partida,
            modelo_partida: $modelo_partida,
            modelo_retencion: $modelo_retencion,
            modelo_traslado: $modelo_traslado,
            ret_global: $ret_global,
            total_impuestos_retenidos: $total_impuestos_retenidos,
            total_impuestos_trasladados: $total_impuestos_trasladados,
            trs_global: $trs_global
        );
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al cargar $totales', data: $totales);
        }

        // Construcción del objeto de retorno
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

    /**
     * REG
     * Integra los impuestos retenidos en la estructura de un concepto.
     *
     * Esta función verifica que los impuestos retenidos existan en `$data_partida`,
     * sean un objeto válido y que `concepto` tenga una estructura de impuestos adecuada.
     * Posteriormente, maqueta los impuestos retenidos con `_impuestos()->maqueta_impuesto()`
     * y los asigna a la propiedad `retenciones` dentro del concepto.
     *
     * @param stdClass $concepto Objeto que representa un concepto de facturación.
     *        - Este objeto debe estar previamente estructurado con datos del producto y debe contener
     *          la estructura de `impuestos[0]` antes de ser completada con las retenciones.
     * @param stdClass $data_partida Objeto que contiene la información de la partida, incluyendo impuestos retenidos.
     *        - `retenidos` (stdClass): Objeto que contiene los impuestos retenidos asociados a la partida.
     * @param _partida $modelo_partida Instancia del modelo de partida que contiene la estructura y claves de identificación.
     * @param _data_impuestos $modelo_retencion Modelo que maneja los datos de impuestos retenidos.
     *        - `tabla` (string): Nombre de la tabla en la base de datos donde se almacenan las retenciones.
     *
     * @return stdClass|array Retorna el objeto `concepto` con los impuestos retenidos integrados si la operación es exitosa.
     * En caso de error, retorna un array con la información del error.
     *
     * @throws errores Si la propiedad `retenidos` no existe en `$data_partida`, si no es un objeto,
     * o si la maquetación de los impuestos retenidos falla.
     *
     * Estructura del error:
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
     * Ejemplo de entrada válida:
     * ```php
     * $concepto = new stdClass();
     * $concepto->impuestos = array();
     * $concepto->impuestos[0] = new stdClass();
     *
     * $data_partida = new stdClass();
     * $data_partida->retenidos = (object) [
     *     (object) ['tipo_impuesto' => 'ISR', 'tasa' => 10, 'importe' => 50.00]
     * ];
     *
     * $modelo_partida = new _partida();
     * $modelo_partida->tabla = 'fc_partida';
     *
     * $modelo_retencion = new _data_impuestos();
     * $modelo_retencion->tabla = 'fc_retencion';
     *
     * $resultado = $this->integra_retenciones($concepto, $data_partida, $modelo_partida, $modelo_retencion);
     * print_r($resultado);
     * ```
     *
     * @example
     * Ejemplo de salida esperada:
     * ```php
     * stdClass Object
     * (
     *     [impuestos] => Array
     *         (
     *             [0] => stdClass Object
     *                 (
     *                     [retenciones] => Array
     *                         (
     *                             [0] => stdClass Object
     *                                 (
     *                                     [tipo_impuesto] => "ISR"
     *                                     [tasa] => 10
     *                                     [importe] => 50.00
     *                                 )
     *                         )
     *                 )
     *         )
     * )
     * ```
     *
     * @example
     * Ejemplo de salida con error si `retenidos` no existe en `$data_partida`:
     * ```php
     * Array
     * (
     *     [error] => 1
     *     [mensaje] => "Error $data_partida->retenidos no existe"
     *     [data] => stdClass Object()
     * )
     * ```
     *
     * @example
     * Ejemplo de salida con error si `retenidos` no es un objeto:
     * ```php
     * Array
     * (
     *     [error] => 1
     *     [mensaje] => "Error $data_partida->retenidos debe ser un objeto"
     *     [data] => stdClass Object()
     * )
     * ```
     *
     * @example
     * Ejemplo de salida con error en la maquetación de impuestos retenidos:
     * ```php
     * Array
     * (
     *     [error] => 1
     *     [mensaje] => "Error al maquetar retenciones"
     *     [data] => stdClass Object()
     * )
     * ```
     */
    private function integra_retenciones(
        stdClass $concepto,
        stdClass $data_partida,
        _partida $modelo_partida,
        _data_impuestos $modelo_retencion
    ): stdClass|array
    {
        // Validación: Verifica que los impuestos retenidos existan en la partida
        if (!isset($data_partida->retenidos)) {
            return $this->error->error(
                mensaje: 'Error $data_partida->retenidos no existe',
                data: $data_partida
            );
        }

        // Validación: Verifica que los impuestos retenidos sean un objeto
        if (!is_object($data_partida->retenidos)) {
            return $this->error->error(
                mensaje: 'Error $data_partida->retenidos debe ser un objeto',
                data: $data_partida
            );
        }

        // Asegura que el objeto concepto tenga la estructura de impuestos inicializada
        if (!isset($concepto->impuestos)) {
            $concepto->impuestos = array();
        }
        if (!isset($concepto->impuestos[0])) {
            $concepto->impuestos[0] = new stdClass();
        }

        // Define la clave para obtener el importe del impuesto retenido desde la tabla de impuestos retenidos
        $key_retenido_importe = $modelo_retencion->tabla . '_importe';

        // Maqueta los impuestos retenidos con base en la información de la partida
        $impuestos = (new _impuestos())->maqueta_impuesto(
            impuestos: $data_partida->retenidos,
            key_importe_impuesto: $key_retenido_importe,
            name_tabla_partida: $modelo_partida->tabla
        );

        // Manejo de error en la maquetación de los impuestos retenidos
        if (errores::$error) {
            return $this->error->error(
                mensaje: 'Error al maquetar retenciones',
                data: $impuestos
            );
        }

        // Asigna los impuestos retenidos al concepto
        $concepto->impuestos[0]->retenciones = $impuestos;

        return $concepto;
    }


    /**
     * REG
     * Integra los impuestos trasladados en la estructura de un concepto.
     *
     * Esta función verifica que los impuestos trasladados existan en `$data_partida`,
     * sean un objeto válido y que `concepto` tenga una estructura de impuestos adecuada.
     * Posteriormente, maqueta los impuestos trasladados con `_impuestos()->maqueta_impuesto()`
     * y los asigna a la propiedad `traslados` dentro del concepto.
     *
     * @param stdClass $concepto Objeto que representa un concepto de facturación.
     *        - Este objeto debe estar previamente estructurado con datos del producto y debe contener
     *          la estructura de `impuestos[0]` antes de ser completada con los traslados.
     * @param stdClass $data_partida Objeto que contiene la información de la partida, incluyendo impuestos trasladados.
     *        - `traslados` (stdClass): Objeto que contiene los impuestos trasladados asociados a la partida.
     * @param _partida $modelo_partida Instancia del modelo de partida que contiene la estructura y claves de identificación.
     * @param _data_impuestos $modelo_traslado Modelo que maneja los datos de impuestos trasladados.
     *        - `tabla` (string): Nombre de la tabla en la base de datos donde se almacenan los traslados.
     *
     * @return stdClass|array Retorna el objeto `concepto` con los impuestos trasladados integrados si la operación es exitosa.
     * En caso de error, retorna un array con la información del error.
     *
     * @throws errores Si la propiedad `traslados` no existe en `$data_partida`, si no es un objeto,
     * o si la maquetación de los impuestos trasladados falla.
     *
     * Estructura del error:
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
     * Ejemplo de entrada válida:
     * ```php
     * $concepto = new stdClass();
     * $concepto->impuestos = array();
     * $concepto->impuestos[0] = new stdClass();
     *
     * $data_partida = new stdClass();
     * $data_partida->traslados = (object) [
     *     (object) ['tipo_impuesto' => 'IVA', 'tasa' => 16, 'importe' => 80.00]
     * ];
     *
     * $modelo_partida = new _partida();
     * $modelo_partida->tabla = 'fc_partida';
     *
     * $modelo_traslado = new _data_impuestos();
     * $modelo_traslado->tabla = 'fc_traslado';
     *
     * $resultado = $this->integra_traslados($concepto, $data_partida, $modelo_partida, $modelo_traslado);
     * print_r($resultado);
     * ```
     *
     * @example
     * Ejemplo de salida esperada:
     * ```php
     * stdClass Object
     * (
     *     [impuestos] => Array
     *         (
     *             [0] => stdClass Object
     *                 (
     *                     [traslados] => Array
     *                         (
     *                             [0] => stdClass Object
     *                                 (
     *                                     [tipo_impuesto] => "IVA"
     *                                     [tasa] => 16
     *                                     [importe] => 80.00
     *                                 )
     *                         )
     *                 )
     *         )
     * )
     * ```
     *
     * @example
     * Ejemplo de salida con error si `traslados` no existe en `$data_partida`:
     * ```php
     * Array
     * (
     *     [error] => 1
     *     [mensaje] => "Error $data_partida->traslados no existe"
     *     [data] => stdClass Object()
     * )
     * ```
     *
     * @example
     * Ejemplo de salida con error si `traslados` no es un objeto:
     * ```php
     * Array
     * (
     *     [error] => 1
     *     [mensaje] => "Error $data_partida->traslados debe ser un objeto"
     *     [data] => stdClass Object()
     * )
     * ```
     *
     * @example
     * Ejemplo de salida con error en la maquetación de impuestos trasladados:
     * ```php
     * Array
     * (
     *     [error] => 1
     *     [mensaje] => "Error al maquetar traslados"
     *     [data] => stdClass Object()
     * )
     * ```
     */
    private function integra_traslados(
        stdClass $concepto,
        stdClass $data_partida,
        _partida $modelo_partida,
        _data_impuestos $modelo_traslado
    ): stdClass|array
    {
        // Validación: Verifica que los traslados existan en la partida
        if (!isset($data_partida->traslados)) {
            return $this->error->error(
                mensaje: 'Error $data_partida->traslados no existe',
                data: $data_partida
            );
        }

        // Validación: Verifica que los traslados sean un objeto
        if (!is_object($data_partida->traslados)) {
            return $this->error->error(
                mensaje: 'Error $data_partida->traslados debe ser un objeto',
                data: $data_partida
            );
        }

        // Asegura que el objeto concepto tenga la estructura de impuestos inicializada
        if (!isset($concepto->impuestos)) {
            $concepto->impuestos = array();
        }
        if (!isset($concepto->impuestos[0])) {
            $concepto->impuestos[0] = new stdClass();
        }

        // Define la clave para obtener el importe del traslado desde la tabla de impuestos trasladados
        $key_traslado_importe = $modelo_traslado->tabla . '_importe';

        // Maqueta los impuestos trasladados con base en la información de la partida
        $impuestos = (new _impuestos())->maqueta_impuesto(
            impuestos: $data_partida->traslados,
            key_importe_impuesto: $key_traslado_importe,
            name_tabla_partida: $modelo_partida->tabla
        );

        // Manejo de error en la maquetación de los impuestos trasladados
        if (errores::$error) {
            return $this->error->error(
                mensaje: 'Error al maquetar traslados',
                data: $impuestos
            );
        }

        // Asigna los impuestos trasladados al concepto
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


    /**
     * REG
     * Obtiene la cuenta predial asociada a un registro en la base de datos.
     *
     * Esta función valida la existencia del `registro_id`, asegura que `key_filtro_id` no esté vacío
     * y realiza una consulta en el modelo `_cuenta_predial` para obtener la cuenta predial asociada.
     * Si la consulta falla o si no hay exactamente un registro asociado, se devuelve un error.
     *
     * @param string $key_filtro_id Clave del campo que se utilizará como filtro en la consulta.
     * @param _cuenta_predial $modelo_predial Modelo que maneja los datos de cuentas prediales.
     * @param int $registro_id Identificador del registro que se está consultando.
     *
     * @return array|stdClass Retorna un objeto con los datos de la cuenta predial si la operación es exitosa.
     * En caso de error, retorna un array con la información del error.
     *
     * @throws errores Si `$registro_id` es menor o igual a 0, si `$key_filtro_id` está vacío,
     * si la consulta falla o si hay más de una cuenta predial asignada.
     *
     * Estructura del error generado:
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
     * Ejemplo de entrada válida:
     * ```php
     * $modelo_predial = new _cuenta_predial();
     * $key_filtro_id = 'fc_cuenta_predial_id';
     * $registro_id = 123;
     *
     * $resultado = $this->r_fc_cuenta_predial($key_filtro_id, $modelo_predial, $registro_id);
     * print_r($resultado);
     * ```
     *
     * @example
     * Ejemplo de salida esperada si hay una cuenta predial asociada:
     * ```php
     * stdClass Object
     * (
     *     [n_registros] => 1
     *     [registros] => Array
     *         (
     *             [0] => Array
     *                 (
     *                     [fc_cuenta_predial_id] => 123
     *                     [descripcion] => "Cuenta predial asignada"
     *                 )
     *         )
     * )
     * ```
     *
     * @example
     * Ejemplo de salida con error si `$registro_id` es 0:
     * ```php
     * Array
     * (
     *     [error] => 1
     *     [mensaje] => "Error: el registro_id debe ser mayor a 0"
     *     [data] => 0
     *     [es_final] => true
     * )
     * ```
     *
     * @example
     * Ejemplo de salida con error si `$key_filtro_id` está vacío:
     * ```php
     * Array
     * (
     *     [error] => 1
     *     [mensaje] => "Error: el key_filtro_id está vacío"
     *     [data] => ""
     *     [es_final] => true
     * )
     * ```
     *
     * @example
     * Ejemplo de salida con error si no hay cuenta predial asociada:
     * ```php
     * Array
     * (
     *     [error] => 1
     *     [mensaje] => "Error: no existe cuenta predial asignada"
     *     [data] => stdClass Object()
     *     [es_final] => true
     * )
     * ```
     *
     * @example
     * Ejemplo de salida con error si hay más de una cuenta predial asignada:
     * ```php
     * Array
     * (
     *     [error] => 1
     *     [mensaje] => "Error de integridad: más de una cuenta predial asignada"
     *     [data] => stdClass Object()
     *     [es_final] => true
     * )
     * ```
     */
    private function r_fc_cuenta_predial(
        string $key_filtro_id,
        _cuenta_predial $modelo_predial,
        int $registro_id
    ): array|stdClass
    {
        // Validar que el ID del registro sea mayor a 0
        if ($registro_id <= 0) {
            return $this->error->error(
                mensaje: "Error: el registro_id debe ser mayor a 0",
                data: $registro_id,
                es_final: true
            );
        }

        // Validar que la clave del filtro no esté vacía
        $key_filtro_id = trim($key_filtro_id);
        if ($key_filtro_id === '') {
            return $this->error->error(
                mensaje: "Error: el $key_filtro_id está vacío",
                data: $registro_id,
                es_final: true
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


    /**
     * REG
     * Valida los datos de una partida asegurando que contenga la información requerida y que su estructura sea correcta.
     *
     * Esta función se encarga de validar que la tabla del modelo de partida no esté vacía y que la clave `partida`
     * dentro del objeto `$data_partida` exista y tenga el formato adecuado.
     *
     * @param stdClass $data_partida Objeto que contiene los datos de la partida. Debe incluir la clave `partida`,
     *                               la cual debe ser un array.
     * @param _partida $modelo_partida Instancia del modelo de partida, utilizada para obtener el nombre de la tabla
     *                                 y validar que no esté vacío.
     *
     * @return true|array Retorna `true` si los datos son válidos. En caso de error, retorna un array con el mensaje
     *                    de error y la información asociada.
     *
     * @example
     * // Ejemplo de uso exitoso:
     * $data_partida = new stdClass();
     * $data_partida->partida = ['clave' => '001', 'descripcion' => 'Servicio de Consultoría'];
     *
     * $modelo_partida = new _partida();
     * $modelo_partida->tabla = 'fc_partida';
     *
     * $resultado = $this->valida_data_partida($data_partida, $modelo_partida);
     * var_dump($resultado); // true
     *
     * @example
     * // Ejemplo de error: modelo_partida->tabla está vacío
     * $data_partida = new stdClass();
     * $data_partida->partida = ['clave' => '001', 'descripcion' => 'Servicio de Consultoría'];
     *
     * $modelo_partida = new _partida();
     * $modelo_partida->tabla = '  '; // Cadena vacía después de trim()
     *
     * $resultado = $this->valida_data_partida($data_partida, $modelo_partida);
     * print_r($resultado);
     * // Salida esperada:
     * // [
     * //     'error' => true,
     * //     'mensaje' => 'Error $modelo_partida->tabla está vacío',
     * //     'data' => ''
     * // ]
     *
     * @example
     * // Ejemplo de error: data_partida->partida no existe
     * $data_partida = new stdClass();
     * // Falta la clave 'partida'
     *
     * $modelo_partida = new _partida();
     * $modelo_partida->tabla = 'fc_partida';
     *
     * $resultado = $this->valida_data_partida($data_partida, $modelo_partida);
     * print_r($resultado);
     * // Salida esperada:
     * // [
     * //     'error' => true,
     * //     'mensaje' => 'Error $data_partida->partida no existe',
     * //     'data' => (object) []
     * // ]
     *
     * @example
     * // Ejemplo de error: data_partida->partida no es un array
     * $data_partida = new stdClass();
     * $data_partida->partida = 'Texto inválido'; // Debe ser un array
     *
     * $modelo_partida = new _partida();
     * $modelo_partida->tabla = 'fc_partida';
     *
     * $resultado = $this->valida_data_partida($data_partida, $modelo_partida);
     * print_r($resultado);
     * // Salida esperada:
     * // [
     * //     'error' => true,
     * //     'mensaje' => 'Error $data_partida->partida debe ser un array',
     * //     'data' => (object) ['partida' => 'Texto inválido']
     * // ]
     */
    private function valida_data_partida(stdClass $data_partida, _partida $modelo_partida): true|array
    {
        $modelo_partida->tabla = trim($modelo_partida->tabla);
        if ($modelo_partida->tabla === '') {
            return $this->error->error(
                mensaje: 'Error $modelo_partida->tabla está vacío',
                data: $modelo_partida->tabla,
                es_final: true
            );
        }

        // Validar que la partida exista dentro de los datos de la partida
        if (!isset($data_partida->partida)) {
            return $this->error->error(
                mensaje: 'Error $data_partida->partida no existe',
                data: $data_partida,
                es_final: true
            );
        }

        if (!is_array($data_partida->partida)) {
            return $this->error->error(
                mensaje: 'Error $data_partida->partida debe ser un array',
                data: $data_partida,
                es_final: true
            );
        }

        return true;
    }


    /**
     * REG
     * Valida la estructura de los impuestos en una partida asegurando que contenga traslados y retenidos.
     *
     * Esta función verifica que los impuestos traslados y retenidos estén presentes dentro del objeto `$data_partida`,
     * y que sean objetos válidos. Si alguna validación falla, se devuelve un mensaje de error estructurado.
     *
     * @param stdClass $data_partida Objeto que contiene los impuestos de la partida. Debe incluir las claves `traslados` y `retenidos`,
     *                               ambas deben ser objetos.
     *
     * @return true|array Retorna `true` si los datos son válidos. En caso de error, retorna un array con el mensaje
     *                    de error y la información asociada.
     *
     * @example
     * // Ejemplo de uso exitoso:
     * $data_partida = new stdClass();
     * $data_partida->traslados = new stdClass();
     * $data_partida->retenidos = new stdClass();
     *
     * $resultado = $this->valida_impuestos_partida($data_partida);
     * var_dump($resultado); // true
     *
     * @example
     * // Ejemplo de error: traslados no existe
     * $data_partida = new stdClass();
     * $data_partida->retenidos = new stdClass();
     *
     * $resultado = $this->valida_impuestos_partida($data_partida);
     * print_r($resultado);
     * // Salida esperada:
     * // [
     * //     'error' => true,
     * //     'mensaje' => 'Error: $data_partida->traslados no existe',
     * //     'data' => (object) ['retenidos' => (object) []]
     * // ]
     *
     * @example
     * // Ejemplo de error: traslados no es un objeto
     * $data_partida = new stdClass();
     * $data_partida->traslados = 'valor incorrecto'; // Debe ser un objeto
     * $data_partida->retenidos = new stdClass();
     *
     * $resultado = $this->valida_impuestos_partida($data_partida);
     * print_r($resultado);
     * // Salida esperada:
     * // [
     * //     'error' => true,
     * //     'mensaje' => 'Error: $data_partida->traslados debe ser un obj',
     * //     'data' => (object) ['traslados' => 'valor incorrecto', 'retenidos' => (object) []]
     * // ]
     *
     * @example
     * // Ejemplo de error: retenidos no existe
     * $data_partida = new stdClass();
     * $data_partida->traslados = new stdClass();
     *
     * $resultado = $this->valida_impuestos_partida($data_partida);
     * print_r($resultado);
     * // Salida esperada:
     * // [
     * //     'error' => true,
     * //     'mensaje' => 'Error: $data_partida->retenidos no existe',
     * //     'data' => (object) ['traslados' => (object) []]
     * // ]
     *
     * @example
     * // Ejemplo de error: retenidos no es un objeto
     * $data_partida = new stdClass();
     * $data_partida->traslados = new stdClass();
     * $data_partida->retenidos = 'dato incorrecto'; // Debe ser un objeto
     *
     * $resultado = $this->valida_impuestos_partida($data_partida);
     * print_r($resultado);
     * // Salida esperada:
     * // [
     * //     'error' => true,
     * //     'mensaje' => 'Error: $data_partida->retenidos debe ser un obj',
     * //     'data' => (object) ['traslados' => (object) [], 'retenidos' => 'dato incorrecto']
     * // ]
     */
    private function valida_impuestos_partida(stdClass $data_partida): true|array
    {
        // Validar la existencia de la clave 'traslados' en la partida
        if (!isset($data_partida->traslados)) {
            return $this->error->error(
                mensaje: 'Error: $data_partida->traslados no existe',
                data: $data_partida
            );
        }

        // Validar que 'traslados' sea un objeto
        if (!is_object($data_partida->traslados)) {
            return $this->error->error(
                mensaje: 'Error: $data_partida->traslados debe ser un obj',
                data: $data_partida
            );
        }

        // Validar la existencia de la clave 'retenidos' en la partida
        if (!isset($data_partida->retenidos)) {
            return $this->error->error(
                mensaje: 'Error: $data_partida->retenidos no existe',
                data: $data_partida
            );
        }

        // Validar que 'retenidos' sea un objeto
        if (!is_object($data_partida->retenidos)) {
            return $this->error->error(
                mensaje: 'Error: $data_partida->retenidos debe ser un obj',
                data: $data_partida
            );
        }

        return true;
    }


    /**
     * REG
     * Valida la estructura y los valores de una partida asegurando que contenga los datos esenciales.
     *
     * La función verifica que las claves necesarias existan en `$keys_part` y en `$data_partida->partida`,
     * y que sus valores no estén vacíos. También valida que los valores numéricos sean realmente números.
     * Si alguna validación falla, devuelve un array con la información del error.
     *
     * @param stdClass $data_partida Objeto que contiene la información de la partida.
     *        - `partida` (array): Datos de la partida, incluyendo claves fiscales y valores numéricos.
     * @param stdClass $keys_part Objeto que contiene las claves necesarias para extraer datos de la partida.
     *        - `cantidad` (string): Clave del campo cantidad.
     *        - `descripcion` (string): Clave del campo descripción.
     *        - `valor_unitario` (string): Clave del campo valor unitario.
     *        - `importe` (string): Clave del campo importe.
     *
     * @return true|array Retorna `true` si la validación es exitosa.
     * En caso de error, retorna un array con la información del error.
     *
     * @throws errores Si alguna clave requerida no existe en `$keys_part` o en `$data_partida->partida`,
     * o si los valores no son válidos.
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
     * Ejemplo de entrada válida:
     * ```php
     * $data_partida = new stdClass();
     * $data_partida->partida = [
     *     'cat_sat_producto_codigo' => '01010101',
     *     'fc_partida_cantidad' => 5,
     *     'cat_sat_unidad_codigo' => 'H87',
     *     'fc_partida_descripcion' => 'Producto genérico',
     *     'fc_partida_valor_unitario' => 100.00,
     *     'fc_partida_importe' => 500.00,
     *     'cat_sat_obj_imp_codigo' => '02',
     *     'com_producto_codigo' => 'PROD123',
     *     'cat_sat_unidad_descripcion' => 'Pieza'
     * ];
     *
     * $keys_part = new stdClass();
     * $keys_part->cantidad = 'fc_partida_cantidad';
     * $keys_part->descripcion = 'fc_partida_descripcion';
     * $keys_part->valor_unitario = 'fc_partida_valor_unitario';
     * $keys_part->importe = 'fc_partida_importe';
     *
     * $resultado = $this->valida_partida($data_partida, $keys_part);
     * var_dump($resultado); // true
     * ```
     *
     * @example
     * Ejemplo de salida exitosa:
     * ```php
     * true
     * ```
     *
     * @example
     * Ejemplo de salida con error si falta una clave en `$keys_part`:
     * ```php
     * Array
     * (
     *     [error] => 1
     *     [mensaje] => "Error $keys_part->valor_unitario no existe"
     *     [data] => stdClass Object()
     *     [es_final] => true
     * )
     * ```
     *
     * @example
     * Ejemplo de salida con error si un valor en la partida no es numérico:
     * ```php
     * Array
     * (
     *     [error] => 1
     *     [mensaje] => "Error $data_partida->partida[fc_partida_valor_unitario] debe ser un número"
     *     [data] => stdClass Object()
     *     [es_final] => true
     * )
     * ```
     */
    private function valida_partida(stdClass $data_partida, stdClass $keys_part): true|array
    {
        // Lista de claves obligatorias en keys_part
        $keys_val = array('cantidad', 'descripcion', 'valor_unitario', 'importe');

        // Validación de existencia y contenido de keys_part
        foreach ($keys_val as $key) {
            if (!isset($keys_part->$key)) {
                return $this->error->error(
                    mensaje: 'Error $keys_part->' . $key . ' no existe',
                    data: $keys_part,
                    es_final: true
                );
            }
            if (trim($keys_part->$key) === '') {
                return $this->error->error(
                    mensaje: 'Error $keys_part->' . $key . ' está vacío',
                    data: $keys_part,
                    es_final: true
                );
            }
        }

        // Lista de claves requeridas en la partida
        $keys_val = array(
            'cat_sat_producto_codigo', $keys_part->cantidad, 'cat_sat_unidad_codigo',
            $keys_part->descripcion, $keys_part->valor_unitario, $keys_part->importe,
            'cat_sat_obj_imp_codigo', 'com_producto_codigo', 'cat_sat_unidad_descripcion'
        );

        // Validación de existencia y contenido de los valores en la partida
        foreach ($keys_val as $key) {
            if (!isset($data_partida->partida[$key])) {
                return $this->error->error(
                    mensaje: 'Error $data_partida->partida[' . $key . '] no existe',
                    data: $data_partida,
                    es_final: true
                );
            }
            if (trim($data_partida->partida[$key]) === '') {
                return $this->error->error(
                    mensaje: 'Error $data_partida->partida[' . $key . '] está vacío',
                    data: $data_partida,
                    es_final: true
                );
            }
        }

        // Validación de valores numéricos en los campos clave
        $keys_val = array($keys_part->cantidad, $keys_part->valor_unitario, $keys_part->importe);

        foreach ($keys_val as $key) {
            if (!is_numeric($data_partida->partida[$key])) {
                return $this->error->error(
                    mensaje: 'Error $data_partida->partida[' . $key . '] debe ser un número',
                    data: $data_partida,
                    es_final: true
                );
            }
        }

        return true;
    }

    /**
     * REG
     * Verifica la validez de una partida antes de procesarla.
     *
     * Esta función realiza validaciones en el objeto `$data_partida`, asegurándose de que contenga
     * la clave `partida`, obtiene las claves requeridas usando `keys_partida()`, y luego valida
     * la estructura de la partida mediante `valida_partida()`.
     *
     * Si alguna validación falla, se devuelve un error detallado.
     *
     * @param stdClass $data_partida Objeto que contiene la información de la partida.
     *        - `partida` (array): Datos de la partida a validar.
     * @param _partida $modelo_partida Instancia del modelo de partida que contiene la estructura y claves de identificación.
     *
     * @return true|array Retorna `true` si la partida es válida.
     * En caso de error, retorna un array con la información del error.
     *
     * @throws errores Si `$data_partida->partida` no existe, si no se pueden obtener las claves de la partida
     * o si la validación de la partida falla.
     *
     * Estructura del error generado:
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
     * Ejemplo de entrada válida:
     * ```php
     * $data_partida = new stdClass();
     * $data_partida->partida = [
     *     'fc_partida_cantidad' => 5,
     *     'fc_partida_descripcion' => 'Producto de prueba',
     *     'fc_partida_valor_unitario' => 100.00,
     *     'fc_partida_sub_total_base' => 500.00
     * ];
     *
     * $modelo_partida = new _partida();
     * $modelo_partida->tabla = 'fc_partida';
     *
     * $resultado = $this->verifica_partida($data_partida, $modelo_partida);
     * print_r($resultado);
     * ```
     *
     * @example
     * Ejemplo de salida esperada si la partida es válida:
     * ```php
     * true
     * ```
     *
     * @example
     * Ejemplo de salida con error si `$data_partida->partida` no existe:
     * ```php
     * Array
     * (
     *     [error] => 1
     *     [mensaje] => "Error $data_partida->partida no existe"
     *     [data] => stdClass Object()
     *     [es_final] => true
     * )
     * ```
     *
     * @example
     * Ejemplo de salida con error si no se pueden obtener las claves de la partida:
     * ```php
     * Array
     * (
     *     [error] => 1
     *     [mensaje] => "Error al obtener $keys_part"
     *     [data] => stdClass Object()
     * )
     * ```
     *
     * @example
     * Ejemplo de salida con error si la validación de la partida falla:
     * ```php
     * Array
     * (
     *     [error] => 1
     *     [mensaje] => "Error al validar data_partida"
     *     [data] => stdClass Object()
     * )
     * ```
     */
    private function verifica_partida(
        stdClass $data_partida,
        _partida $modelo_partida
    ): true|array
    {
        // Validar que la partida exista dentro de los datos de la partida
        if (!isset($data_partida->partida)) {
            return $this->error->error(
                mensaje: 'Error $data_partida->partida no existe',
                data: $data_partida,
                es_final: true
            );
        }

        // Obtener las claves necesarias de la partida
        $keys_part = $this->keys_partida(modelo_partida: $modelo_partida);
        if (errores::$error) {
            return $this->error->error(
                mensaje: 'Error al obtener $keys_part',
                data: $keys_part
            );
        }

        // Validar la estructura de la partida
        $valida = $this->valida_partida(data_partida: $data_partida, keys_part: $keys_part);
        if (errores::$error) {
            return $this->error->error(
                mensaje: 'Error al validar data_partida',
                data: $valida
            );
        }

        return true;
    }



}
