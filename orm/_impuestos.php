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

    /**
     * REG
     * Acumula los valores de base e importe en la estructura global de impuestos.
     *
     * Esta función toma la estructura `$global_imp` y el array `$impuesto` y suma los valores
     * de base e importe al acumulado existente en `$global_imp[$key_gl]`. Se utiliza para
     * mantener un registro acumulativo de los valores de impuestos de todas las partidas.
     *
     * ### Ejemplo de entrada:
     * ```php
     * $global_imp = [
     *     'IVA' => (object) ['base' => 100.00, 'importe' => 16.00]
     * ];
     * $impuesto = [
     *     'fc_partida_importe_con_descuento' => 50.00,
     *     'fc_partida_importe_impuesto' => 8.00
     * ];
     * $key_gl = 'IVA';
     * $key_importe = 'fc_partida_importe_impuesto';
     * ```
     *
     * ### Ejemplo de uso:
     * ```php
     * $resultado = $this->acumulado_global_imp($global_imp, $impuesto, $key_gl, $key_importe);
     * print_r($resultado);
     * ```
     *
     * ### Salida esperada:
     * ```php
     * stdClass Object
     * (
     *     [base_ac] => 150.00
     *     [importe_ac] => 24.00
     * )
     * ```
     *
     * @param array $global_imp Arreglo que almacena los impuestos acumulados. Cada clave representa un tipo de impuesto.
     *
     * @param array $impuesto Datos del impuesto actual, incluyendo:
     *                        - `'fc_partida_importe_con_descuento'`: Base imponible (ej. `50.00`).
     *                        - `'$key_importe'`: Importe del impuesto correspondiente a la partida.
     *
     * @param string $key_gl Clave dentro de `$global_imp` donde se acumularán los valores. Ejemplo: `'IVA'`.
     *
     * @param string $key_importe Clave dentro de `$impuesto` que contiene el importe del impuesto.
     *                             Ejemplo: `'fc_partida_importe_impuesto'`.
     *
     * @return array|stdClass Devuelve un objeto `stdClass` con las propiedades:
     *                        - `base_ac`: Nueva base acumulada.
     *                        - `importe_ac`: Nuevo importe acumulado.
     *                        Si ocurre un error, retorna un objeto de error.
     *
     * @throws stdClass Devuelve un objeto de error si alguna clave está vacía o si ocurre un problema al sumar los valores.
     */
    private function acumulado_global_imp(
        array $global_imp, array $impuesto, string $key_gl, string $key_importe
    ): array|stdClass {
        // Asegura que el valor de base exista en el array de impuesto
        if (!isset($impuesto['fc_partida_importe_con_descuento'])) {
            $impuesto['fc_partida_importe_con_descuento'] = 0.0;
        }

        $global_imp = $this->init_base(global_imp: $global_imp,key_gl:  $key_gl,key_importe: $key_importe);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al inicializar $global_imp', data: $global_imp);
        }

        // Asegura que el importe del impuesto esté presente
        if (!isset($impuesto[$key_importe])) {
            $impuesto[$key_importe] = 0.0;
        }

        // Cálculo de base acumulada
        $base = round($impuesto['fc_partida_importe_con_descuento'], 2);
        $base_ac = round(($global_imp[$key_gl]->base ?? 0) + $base, 2);

        // Cálculo de importe acumulado
        $importe = round($impuesto[$key_importe], 2);
        $importe_ac = round(($global_imp[$key_gl]->importe ?? 0) + $importe, 2);

        // Formateo a dos decimales
        $base_ac = number_format($base_ac, 2, '.', '');
        $importe_ac = number_format($importe_ac, 2, '.', '');

        // Retorna los valores acumulados en un objeto
        $data = new stdClass();
        $data->base_ac = $base_ac;
        $data->importe_ac = $importe_ac;

        return $data;
    }


    /**
     * REG
     * Acumula valores de base e importe de impuestos en una estructura global.
     *
     * Esta función inicializa la estructura de acumulación de impuestos (`$global_imp`)
     * si no está definida, y luego suma los valores actuales de la base e importe
     * del impuesto correspondiente dentro de `$global_imp`.
     *
     * ### Ejemplo de entrada:
     * ```php
     * $global_imp = [
     *     'IVA' => (object) ['base' => '100.00', 'importe' => '16.00']
     * ];
     * $impuesto = [
     *     'fc_partida_importe_con_descuento' => 50.00,
     *     'importe_iva' => 8.00
     * ];
     * $key_gl = 'IVA';
     * $key_importe = 'importe_iva';
     * ```
     *
     * ### Ejemplo de uso:
     * ```php
     * $resultado = $this->acumulado_global_impuesto($global_imp, $impuesto, 'IVA', 'importe_iva');
     * print_r($resultado);
     * ```
     *
     * ### Salida esperada:
     * ```php
     * Array
     * (
     *     [IVA] => stdClass Object
     *         (
     *             [base] => "150.00"
     *             [importe] => "24.00"
     *         )
     * )
     * ```
     *
     * @param array $global_imp Arreglo que almacena los impuestos acumulados por tipo.
     *                          Cada clave representa un tipo de impuesto, y su valor es un objeto con `base` e `importe`.
     *
     * @param array $impuesto Arreglo de datos del impuesto a acumular.
     *                        Debe contener las claves `fc_partida_importe_con_descuento` y `$key_importe` con valores numéricos.
     *
     * @param string $key_gl Clave dentro de `$global_imp` donde se acumularán los valores.
     *                       Ejemplo: `'IVA'`, `'ISR'`, `'IEPS'`.
     *
     * @param string $key_importe Clave dentro de `$impuesto` que representa el importe del impuesto.
     *                             Ejemplo: `'importe_iva'`, `'importe_isr'`.
     *
     * @return array Devuelve el array `$global_imp` con los valores acumulados en la clave `$key_gl`.
     *
     * @throws array Retorna un array de error si `$key_gl` o `$key_importe` están vacíos, o si falla la inicialización de `$global_imp`.
     */
    private function acumulado_global_impuesto(
        array $global_imp, array $impuesto, string $key_gl, string $key_importe): array
    {
        // Inicializa la base del impuesto dentro del acumulador global
        $global_imp = $this->init_base(global_imp: $global_imp, key_gl: $key_gl, key_importe: $key_importe);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al inicializar $global_imp', data: $global_imp);
        }

        // Calcula los valores acumulados de base e importe
        $acumulado = $this->acumulado_global_imp(
            global_imp: $global_imp, impuesto: $impuesto, key_gl: $key_gl, key_importe: $key_importe);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al inicializar acumulado', data: $acumulado);
        }

        // Asigna los valores acumulados en la clave correspondiente dentro del array global
        $global_imp[$key_gl]->base = $acumulado->base_ac;
        $global_imp[$key_gl]->importe = $acumulado->importe_ac;

        return $global_imp;
    }


    /**
     * REG
     * Carga los valores de impuestos en una estructura global.
     *
     * Esta función toma los datos de un impuesto individual (`$data_imp`) y los almacena dentro del array `$imp_global`,
     * utilizando `$key_gl` como clave. Si algún valor no está definido en los arreglos `$data_imp` o `$impuesto`,
     * la función asigna valores predeterminados para evitar errores.
     *
     * ### Ejemplo de entrada:
     * ```php
     * $data_imp = new stdClass();
     * $data_imp->cat_sat_factor_factor = 0.160000;
     * $data_imp->importe = 16.08;
     * $data_imp->base = 100.50;
     *
     * $impuesto = [
     *     'cat_sat_tipo_factor_descripcion' => 'Tasa',
     *     'cat_sat_tipo_impuesto_codigo' => '002'
     * ];
     *
     * $imp_global = [];
     * $key_gl = 'IVA';
     * ```
     *
     * ### Ejemplo de uso:
     * ```php
     * $resultado = $this->carga_global($data_imp, $impuesto, $imp_global, $key_gl);
     * print_r($resultado);
     * ```
     *
     * ### Salida esperada:
     * ```php
     * Array
     * (
     *     [IVA] => stdClass Object
     *         (
     *             [base] => 100.50
     *             [tipo_factor] => Tasa
     *             [tasa_o_cuota] => 0.160000
     *             [impuesto] => 002
     *             [importe] => 16.08
     *         )
     * )
     * ```
     *
     * @param stdClass $data_imp Objeto con los valores del impuesto a registrar. Puede contener:
     *                           - `cat_sat_factor_factor`: Factor del impuesto (ej. `0.160000`).
     *                           - `importe`: Monto del impuesto (ej. `16.08`).
     *                           - `base`: Base sobre la cual se aplica el impuesto (ej. `100.50`).
     *
     * @param array $impuesto Datos adicionales del impuesto. Debe incluir:
     *                        - `'cat_sat_tipo_factor_descripcion'`: Descripción del tipo de factor (ej. `'Tasa'`).
     *                        - `'cat_sat_tipo_impuesto_codigo'`: Código del impuesto (ej. `'002'` para IVA).
     *
     * @param array $imp_global Arreglo que almacena los impuestos en estructura global.
     *
     * @param string $key_gl Clave utilizada dentro de `$imp_global` para almacenar el impuesto.
     *                       Ejemplo: `'IVA'`.
     *
     * @return array Devuelve `$imp_global` con los datos del impuesto añadidos en la clave `$key_gl`.
     *
     * @throws stdClass Devuelve un objeto de error si `$key_gl` está vacío.
     */
    private function carga_global(stdClass $data_imp, array $impuesto, array $imp_global, string $key_gl): array
    {
        // Validación de la clave global
        $key_gl = trim($key_gl);
        if ($key_gl === '') {
            return $this->error->error(mensaje: 'Error $key_gl esta vacio', data: $key_gl);
        }

        // Asignación de valores predeterminados si no existen
        $data_imp->cat_sat_factor_factor = $data_imp->cat_sat_factor_factor ?? 0.0;
        $data_imp->importe = $data_imp->importe ?? 0.0;
        $data_imp->base = $data_imp->base ?? 0.0;

        // Inicializa el nodo en $imp_global si no existe
        if (!isset($imp_global[$key_gl])) {
            $imp_global[$key_gl] = new stdClass();
        }

        // Asegura la existencia de valores en el array $impuesto
        $impuesto['cat_sat_tipo_factor_descripcion'] = $impuesto['cat_sat_tipo_factor_descripcion'] ?? '';
        $impuesto['cat_sat_tipo_impuesto_codigo'] = $impuesto['cat_sat_tipo_impuesto_codigo'] ?? '';

        // Carga los valores en el nodo global
        $imp_global[$key_gl]->base = $data_imp->base;
        $imp_global[$key_gl]->tipo_factor = $impuesto['cat_sat_tipo_factor_descripcion'];
        $imp_global[$key_gl]->tasa_o_cuota = $data_imp->cat_sat_factor_factor;
        $imp_global[$key_gl]->impuesto = $impuesto['cat_sat_tipo_impuesto_codigo'];
        $imp_global[$key_gl]->importe = $data_imp->importe;

        return $imp_global;
    }

    /**
     * REG
     * Limpia y valida las claves obligatorias del array de impuestos.
     *
     * Esta función realiza dos acciones principales:
     * 1. **Validación previa**: Verifica que las claves esenciales (`cat_sat_tipo_factor_id`,
     *    `cat_sat_factor_id`, `cat_sat_tipo_impuesto_id`) existan en `$impuesto`. Si alguna falta,
     *    devuelve un error.
     * 2. **Limpieza de datos**: Aplica `trim()` a los valores de las claves para eliminar
     *    espacios en blanco al inicio y al final.
     *
     * ### Ejemplo de entrada válida:
     * ```php
     * $impuesto = [
     *     'cat_sat_tipo_factor_id' => ' 1 ',
     *     'cat_sat_factor_id' => '2 ',
     *     'cat_sat_tipo_impuesto_id' => ' 3'
     * ];
     * $resultado = $this->impuesto_limpia($impuesto);
     * print_r($resultado);
     * ```
     *
     * ### Salida esperada:
     * ```php
     * Array
     * (
     *     [cat_sat_tipo_factor_id] => "1"
     *     [cat_sat_factor_id] => "2"
     *     [cat_sat_tipo_impuesto_id] => "3"
     * )
     * ```
     *
     * ### Ejemplo de entrada inválida (falta `cat_sat_tipo_factor_id`):
     * ```php
     * $impuesto = [
     *     'cat_sat_factor_id' => '2 ',
     *     'cat_sat_tipo_impuesto_id' => ' 3'
     * ];
     * $resultado = $this->impuesto_limpia($impuesto);
     * print_r($resultado);
     * ```
     *
     * ### Salida esperada (error):
     * ```php
     * Array
     * (
     *     [mensaje] => "Error al validar impuesto"
     *     [data] => Array
     *         (
     *             [mensaje] => "Error cat_sat_tipo_factor_id no existe"
     *             [data] => Array
     *                 (
     *                     [cat_sat_factor_id] => "2"
     *                     [cat_sat_tipo_impuesto_id] => "3"
     *                 )
     *             [es_final] => true
     *         )
     *     [es_final] => true
     * )
     * ```
     *
     * @param array $impuesto Arreglo con los datos del impuesto.
     *                        Debe contener las claves:
     *                        - `cat_sat_tipo_factor_id`
     *                        - `cat_sat_factor_id`
     *                        - `cat_sat_tipo_impuesto_id`
     *
     * @return array Retorna el array `$impuesto` con los valores de las claves limpiados con `trim()`.
     *               Si falta alguna clave, devuelve un array con un mensaje de error.
     *
     * @throws array Retorna un array de error si alguna clave obligatoria no existe en `$impuesto`.
     */
    private function impuesto_limpia(array $impuesto): array
    {
        // Validar que las claves requeridas existan en el array
        $valida = $this->valida_keys_existentes(impuesto: $impuesto);
        if (errores::$error) {
            return (new errores())->error(
                mensaje: 'Error al validar impuesto',
                data: $valida,
                es_final: true
            );
        }

        // Aplicar trim() para limpiar espacios en blanco en los valores
        $impuesto['cat_sat_tipo_factor_id'] = trim($impuesto['cat_sat_tipo_factor_id']);
        $impuesto['cat_sat_factor_id'] = trim($impuesto['cat_sat_factor_id']);
        $impuesto['cat_sat_tipo_impuesto_id'] = trim($impuesto['cat_sat_tipo_impuesto_id']);

        return $impuesto;
    }

    /**
     * REG
     * Valida y limpia los valores del array de impuestos.
     *
     * Esta función ejecuta un proceso completo de validación y limpieza en el array `$impuesto`,
     * asegurando que los datos sean correctos y listos para ser utilizados. Realiza las siguientes validaciones:
     *
     * 1. **Verificación de existencia de claves** (`valida_keys_existentes`)
     *    - Asegura que las claves requeridas están presentes en `$impuesto`.
     *
     * 2. **Limpieza de datos** (`impuesto_limpia`)
     *    - Aplica `trim()` para eliminar espacios en blanco en las claves relevantes.
     *
     * 3. **Validación de valores foráneos** (`valida_foraneas`)
     *    - Comprueba que las claves no estén vacías.
     *    - Verifica que los valores sean numéricos.
     *    - Asegura que los valores sean mayores a 0.
     *
     * Si alguna validación falla, la función devuelve un array con un mensaje de error.
     *
     * ### Ejemplo de entrada válida:
     * ```php
     * $impuesto = [
     *     'cat_sat_tipo_factor_id' => ' 1 ',
     *     'cat_sat_factor_id' => ' 2 ',
     *     'cat_sat_tipo_impuesto_id' => '3'
     * ];
     * $resultado = $this->impuesto_validado($impuesto);
     * print_r($resultado);
     * ```
     *
     * ### Salida esperada:
     * ```php
     * Array
     * (
     *     [cat_sat_tipo_factor_id] => "1"
     *     [cat_sat_factor_id] => "2"
     *     [cat_sat_tipo_impuesto_id] => "3"
     * )
     * ```
     *
     * ### Ejemplo de entrada inválida (clave faltante):
     * ```php
     * $impuesto = [
     *     'cat_sat_factor_id' => '2',
     *     'cat_sat_tipo_impuesto_id' => '3'
     * ];
     * $resultado = $this->impuesto_validado($impuesto);
     * print_r($resultado);
     * ```
     *
     * ### Salida esperada (error):
     * ```php
     * Array
     * (
     *     [mensaje] => "Error al validar impuesto"
     *     [data] => Array
     *         (
     *             [mensaje] => "Error cat_sat_tipo_factor_id no existe"
     *             [data] => Array
     *                 (
     *                     [cat_sat_factor_id] => "2"
     *                     [cat_sat_tipo_impuesto_id] => "3"
     *                 )
     *             [es_final] => true
     *         )
     *     [es_final] => true
     * )
     * ```
     *
     * ### Ejemplo de entrada inválida (valor no numérico):
     * ```php
     * $impuesto = [
     *     'cat_sat_tipo_factor_id' => 'ABC',
     *     'cat_sat_factor_id' => '2',
     *     'cat_sat_tipo_impuesto_id' => '3'
     * ];
     * $resultado = $this->impuesto_validado($impuesto);
     * print_r($resultado);
     * ```
     *
     * ### Salida esperada (error):
     * ```php
     * Array
     * (
     *     [mensaje] => "Error al validar impuesto"
     *     [data] => Array
     *         (
     *             [mensaje] => "Error cat_sat_tipo_factor_id debe ser un número"
     *             [data] => Array
     *                 (
     *                     [cat_sat_tipo_factor_id] => "ABC"
     *                     [cat_sat_factor_id] => "2"
     *                     [cat_sat_tipo_impuesto_id] => "3"
     *                 )
     *             [es_final] => true
     *         )
     *     [es_final] => true
     * )
     * ```
     *
     * @param array $impuesto Arreglo con los datos del impuesto.
     *                        Debe contener las claves:
     *                        - `cat_sat_tipo_factor_id`
     *                        - `cat_sat_factor_id`
     *                        - `cat_sat_tipo_impuesto_id`
     *
     * @return array Retorna el array `$impuesto` con los valores validados y limpiados.
     *               En caso de error, devuelve un array con un mensaje de error.
     *
     * @throws array Retorna un array de error si alguna validación falla.
     */
    private function impuesto_validado(array $impuesto): array
    {
        // Validar que las claves requeridas existan en el array
        $valida = $this->valida_keys_existentes(impuesto: $impuesto);
        if (errores::$error) {
            return (new errores())->error(
                mensaje: 'Error al validar impuesto',
                data: $valida,
                es_final: true
            );
        }

        // Limpiar los valores del array
        $impuesto = $this->impuesto_limpia(impuesto: $impuesto);
        if (errores::$error) {
            return (new errores())->error(
                mensaje: 'Error al limpiar impuesto',
                data: $impuesto
            );
        }

        // Validar que los valores cumplan con las reglas establecidas
        $valida = $this->valida_foraneas(impuesto: $impuesto);
        if (errores::$error) {
            return (new errores())->error(
                mensaje: 'Error al validar impuesto',
                data: $valida,
                es_final: true
            );
        }

        return $impuesto;
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

    /**
     * REG
     * Procesa y acumula impuestos en una estructura global.
     *
     * Esta función toma una colección de impuestos y los valida, formatea y acumula dentro de un array `$global_imp`.
     * Cada impuesto es procesado en los siguientes pasos:
     *
     * 1. **Validación del tipo de datos**:
     *    - Se verifica que cada impuesto dentro de `$impuestos->registros` sea un array.
     *
     * 2. **Validación y limpieza de impuestos** (`impuesto_validado`)
     *    - Se validan claves requeridas, se eliminan espacios en blanco y se verifica que los valores sean correctos.
     *
     * 3. **Generación de clave única** (`key_gl`)
     *    - Se construye una clave en el formato `"X.Y.Z"` donde `X`, `Y` y `Z` corresponden a los identificadores de impuesto.
     *
     * 4. **Validación de la estructura base** (`valida_base`)
     *    - Se verifica que la clave global generada, el importe y la tabla de partida sean correctos.
     *
     * 5. **Acumulación de impuestos** (`integra_ac_impuesto`)
     *    - Se agregan los impuestos al array `$global_imp` manteniendo un registro de los valores acumulados.
     *
     * Si alguna validación falla, la función devuelve un array con un mensaje de error.
     *
     * ### Ejemplo de entrada válida:
     * ```php
     * $impuestos = new stdClass();
     * $impuestos->registros = [
     *     [
     *         'cat_sat_tipo_factor_id' => '1',
     *         'cat_sat_factor_id' => '2',
     *         'cat_sat_tipo_impuesto_id' => '3',
     *         'fc_partida_importe_impuesto' => '10.00'
     *     ]
     * ];
     *
     * $global_imp = [];
     * $key_importe = 'fc_partida_importe_impuesto';
     * $name_tabla_partida = 'fc_partida';
     *
     * $resultado = $this->impuestos_globales($impuestos, $global_imp, $key_importe, $name_tabla_partida);
     * print_r($resultado);
     * ```
     *
     * ### Salida esperada:
     * ```php
     * Array
     * (
     *     [1.2.3] => stdClass Object
     *         (
     *             [base] => "100.00"
     *             [importe] => "10.00"
     *         )
     * )
     * ```
     *
     * ### Ejemplo de entrada inválida (`impuesto` no es un array):
     * ```php
     * $impuestos = new stdClass();
     * $impuestos->registros = [
     *     "Este no es un array"
     * ];
     *
     * $global_imp = [];
     * $key_importe = 'fc_partida_importe_impuesto';
     * $name_tabla_partida = 'fc_partida';
     *
     * $resultado = $this->impuestos_globales($impuestos, $global_imp, $key_importe, $name_tabla_partida);
     * print_r($resultado);
     * ```
     *
     * ### Salida esperada (error):
     * ```php
     * Array
     * (
     *     [mensaje] => "Error $impuesto debe ser un array"
     *     [data] => "Este no es un array"
     * )
     * ```
     *
     * @param stdClass $impuestos Objeto que contiene un array de impuestos dentro de la propiedad `registros`.
     *                            Cada registro debe contener:
     *                            - `cat_sat_tipo_factor_id`
     *                            - `cat_sat_factor_id`
     *                            - `cat_sat_tipo_impuesto_id`
     *                            - `key_importe` (Ejemplo: `fc_partida_importe_impuesto`)
     *
     * @param array $global_imp Arreglo que almacena los impuestos acumulados. Cada clave representa un tipo de impuesto.
     *
     * @param string $key_importe Clave dentro de `$impuesto` que contiene el importe del impuesto.
     *                             Ejemplo: `'fc_partida_importe_impuesto'`.
     *
     * @param string $name_tabla_partida Nombre de la tabla de partidas en la base de datos.
     *                                   Ejemplo: `'fc_partida'`.
     *
     * @return array Devuelve `$global_imp` con los impuestos acumulados por clave única generada.
     *               En caso de error, devuelve un array con un mensaje de error.
     *
     * @throws array Retorna un array de error si alguna validación falla.
     */
    final public function impuestos_globales(stdClass $impuestos, array $global_imp, string $key_importe,
                                             string $name_tabla_partida): array
    {
        foreach ($impuestos->registros as $impuesto) {

            // Validar que el impuesto sea un array
            if (!is_array($impuesto)) {
                return $this->error->error(
                    mensaje: 'Error $impuesto debe ser un array',
                    data: $impuesto
                );
            }

            // Validar y limpiar el impuesto
            $impuesto = $this->impuesto_validado(impuesto: $impuesto);
            if (errores::$error) {
                return (new errores())->error(
                    mensaje: 'Error al limpiar impuesto',
                    data: $impuesto
                );
            }

            // Generar clave única del impuesto
            $key_gl = $this->key_gl(impuesto: $impuesto);
            if (errores::$error) {
                return $this->error->error(
                    mensaje: 'Error al inicializar $key_gl',
                    data: $key_gl
                );
            }

            // Validar estructura base antes de procesar
            $valida = $this->valida_base(key_gl: $key_gl, key_importe: $key_importe,
                name_tabla_partida: $name_tabla_partida);
            if (errores::$error) {
                return $this->error->error(
                    mensaje: 'Error al validar datos',
                    data: $valida
                );
            }

            // Acumular impuestos en la estructura global
            $global_imp = $this->integra_ac_impuesto(global_imp: $global_imp, impuesto: $impuesto, key_gl: $key_gl,
                key_importe: $key_importe, name_tabla_partida: $name_tabla_partida);
            if (errores::$error) {
                return $this->error->error(
                    mensaje: 'Error al inicializar acumulado',
                    data: $global_imp
                );
            }
        }

        return $global_imp;
    }



    /**
     * REG
     * Inicializa una clave dentro del array de impuestos globales si no existe.
     *
     * Esta función verifica si `$key_gl` existe en `$global_imp`. Si no existe, la inicializa
     * con un objeto `stdClass`. Su propósito es garantizar que la clave de impuestos
     * tenga una estructura adecuada antes de acumular datos en ella.
     *
     * ### Ejemplo de entrada:
     * ```php
     * $global_imp = [
     *     'IVA' => (object) ['base' => 100.00, 'importe' => 16.00]
     * ];
     * $key_gl = 'ISR';
     * $key_importe = 'importe_isr';
     * ```
     *
     * ### Ejemplo de uso:
     * ```php
     * $resultado = $this->init_base($global_imp, $key_gl, $key_importe);
     * print_r($resultado);
     * ```
     *
     * ### Salida esperada:
     * Si `$key_gl` existe en `$global_imp`, devuelve el array sin cambios:
     * ```php
     * Array
     * (
     *     [IVA] => stdClass Object
     *         (
     *             [base] => 100
     *             [importe] => 16
     *         )
     * )
     * ```
     *
     * Si `$key_gl` no existe, se agrega con un `stdClass` vacío:
     * ```php
     * Array
     * (
     *     [IVA] => stdClass Object
     *         (
     *             [base] => 100
     *             [importe] => 16
     *         )
     *     [ISR] => stdClass Object
     *         (
     *         )
     * )
     * ```
     *
     * @param array $global_imp Arreglo de impuestos globales donde se almacenan los datos acumulados.
     *                          Cada clave representa un tipo de impuesto.
     *
     * @param string $key_gl Clave dentro de `$global_imp` donde se verificará o inicializará un objeto `stdClass`.
     *                       Ejemplo: `'IVA'`, `'ISR'`, `'IEPS'`.
     *
     * @param string $key_importe Clave que representa el importe del impuesto dentro de la estructura.
     *                             Ejemplo: `'importe_iva'`, `'importe_isr'`.
     *
     * @return array Devuelve el array `$global_imp` con la clave `$key_gl` inicializada si no existía.
     *
     * @throws array Retorna un array de error si `$key_gl` o `$key_importe` están vacíos.
     */
    private function init_base(array $global_imp, string $key_gl, string $key_importe): array
    {
        // Validación de la clave global
        $key_gl = trim($key_gl);
        if ($key_gl === '') {
            return $this->error->error(mensaje: 'Error $key_gl esta vacio', data: $key_gl, es_final: true);
        }

        // Validación de la clave del importe
        $key_importe = trim($key_importe);
        if ($key_importe === '') {
            return $this->error->error(mensaje: 'Error $key_importe esta vacio', data: $key_importe, es_final: true);
        }

        // Si la clave no existe en $global_imp, inicializarla con un objeto vacío
        if (!isset($global_imp[$key_gl])) {
            $global_imp[$key_gl] = new stdClass();
        }

        return $global_imp;
    }


    /**
     * REG
     * Inicializa un nodo global con información de impuestos y cálculos formateados.
     *
     * Esta función toma los datos de impuestos y genera un nodo global en el array `$global_nodo`, asegurando
     * que las claves necesarias existan en el array `$impuesto`, aplicando redondeo y formato adecuado a los valores.
     *
     * ### Ejemplo de entrada:
     * ```php
     * $global_nodo = [];
     * $impuesto = [
     *     'fc_partida_importe_con_descuento' => 100.50,
     *     'fc_partida_iva' => 16.08,
     *     'cat_sat_factor_factor' => 0.160000
     * ];
     * $key = 'iva';
     * $key_importe = 'fc_partida_iva';
     * $name_tabla_partida = 'fc_partida';
     * ```
     *
     * ### Ejemplo de uso:
     * ```php
     * $resultado = $this->init_globales($global_nodo, $impuesto, $key, $key_importe, $name_tabla_partida);
     * print_r($resultado);
     * ```
     *
     * ### Salida esperada:
     * ```php
     * stdClass Object
     * (
     *     [global_nodo] => Array
     *         (
     *             [iva] => stdClass Object
     *                 (
     *                 )
     *         )
     *     [base] => 100.50
     *     [importe] => 16.08
     *     [cat_sat_factor_factor] => 0.160000
     * )
     * ```
     *
     * @param array $global_nodo Referencia al array donde se almacenará el nodo global de impuestos.
     *                           Ejemplo: `['iva' => stdClass()]`.
     *
     * @param array $impuesto Datos del impuesto que se están procesando. Debe contener claves como:
     *                        - `<name_tabla_partida>_importe_con_descuento` (ej. `'fc_partida_importe_con_descuento'`).
     *                        - `$key_importe` (ej. `'fc_partida_iva'`).
     *                        - `'cat_sat_factor_factor'` (ej. `0.160000`).
     *
     * @param string $key Clave que identifica el nodo en `$global_nodo`.
     *                    Ejemplo: `'iva'`.
     *
     * @param string $key_importe Clave dentro del array `$impuesto` que representa el importe del impuesto.
     *                            Ejemplo: `'fc_partida_iva'`.
     *
     * @param string $name_tabla_partida Nombre de la tabla o prefijo utilizado para generar claves de importe.
     *                                   Ejemplo: `'fc_partida'` generará `'fc_partida_importe_con_descuento'`.
     *
     * @return array|stdClass Devuelve un objeto `stdClass` con los siguientes valores:
     *                        - `global_nodo`: Contiene el nodo actualizado.
     *                        - `base`: Base del impuesto formateada a 2 decimales.
     *                        - `importe`: Importe del impuesto formateado a 2 decimales.
     *                        - `cat_sat_factor_factor`: Factor del impuesto formateado a 6 decimales.
     *
     * @throws stdClass Devuelve un objeto de error si alguna clave requerida está vacía.
     */
    private function init_globales(array $global_nodo, array $impuesto, string $key, string $key_importe,
                                   string $name_tabla_partida): array|stdClass
    {
        // Validación de parámetros obligatorios
        $key = trim($key);
        if ($key === '') {
            return $this->error->error(mensaje: 'Error $key esta vacio', data: $key);
        }

        $name_tabla_partida = trim($name_tabla_partida);
        if ($name_tabla_partida === '') {
            return $this->error->error(mensaje: 'Error $name_tabla_partida esta vacio', data: $name_tabla_partida);
        }

        $key_importe = trim($key_importe);
        if ($key_importe === '') {
            return $this->error->error(mensaje: 'Error $key_importe esta vacio', data: $key_importe);
        }

        // Inicializa valores si no existen en el array $impuesto
        $impuesto[$name_tabla_partida.'_importe_con_descuento']
            = $impuesto[$name_tabla_partida.'_importe_con_descuento'] ?? 0.0;
        $impuesto[$key_importe] = $impuesto[$key_importe] ?? 0.0;
        $impuesto['cat_sat_factor_factor'] = $impuesto['cat_sat_factor_factor'] ?? 0.0;

        // Inicializa el nodo en el array global
        $global_nodo[$key] = new stdClass();

        // Aplica redondeo y formato a los valores numéricos
        $base = round($impuesto[$name_tabla_partida.'_importe_con_descuento'], 2);
        $importe = round($impuesto[$key_importe], 2);
        $cat_sat_factor_factor = round($impuesto['cat_sat_factor_factor'], 6);

        // Formatea los valores a número con decimales adecuados
        $base = number_format($base, 2, '.', '');
        $importe = number_format($importe, 2, '.', '');
        $cat_sat_factor_factor = number_format($cat_sat_factor_factor,
            6, '.', '');

        // Construye y retorna el objeto resultado
        $data = new stdClass();
        $data->global_nodo = $global_nodo;
        $data->base = $base;
        $data->importe = $importe;
        $data->cat_sat_factor_factor = $cat_sat_factor_factor;

        return $data;
    }


    /**
     * REG
     * Inicializa y carga los impuestos en la estructura global.
     *
     * Esta función combina las operaciones de inicialización (`init_globales`) y carga (`carga_global`)
     * para gestionar correctamente los impuestos de una partida en el CFDI. Primero, valida y limpia
     * las claves de entrada, luego inicializa los valores en `global_nodo` y finalmente carga los valores
     * correctos en la estructura global.
     *
     * ### Ejemplo de entrada:
     * ```php
     * $global_nodo = [];
     * $impuesto = [
     *     'cat_sat_tipo_factor_descripcion' => 'Tasa',
     *     'cat_sat_tipo_impuesto_codigo' => '002',
     *     'cat_sat_factor_factor' => 0.160000,
     *     'fc_partida_importe_con_descuento' => 100.50,
     *     'fc_partida_importe_impuesto' => 16.08
     * ];
     * $key_gl = 'IVA';
     * $key_importe = 'fc_partida_importe_impuesto';
     * $name_tabla_partida = 'fc_partida';
     * ```
     *
     * ### Ejemplo de uso:
     * ```php
     * $resultado = $this->init_imp_global($global_nodo, $impuesto, $key_gl, $key_importe, $name_tabla_partida);
     * print_r($resultado);
     * ```
     *
     * ### Salida esperada:
     * ```php
     * Array
     * (
     *     [IVA] => stdClass Object
     *         (
     *             [base] => 100.50
     *             [tipo_factor] => Tasa
     *             [tasa_o_cuota] => 0.160000
     *             [impuesto] => 002
     *             [importe] => 16.08
     *         )
     * )
     * ```
     *
     * @param array $global_nodo Arreglo que almacena los impuestos en estructura global.
     *
     * @param array $impuesto Datos del impuesto que se van a procesar. Contiene:
     *                        - `'cat_sat_tipo_factor_descripcion'`: Tipo de factor (ej. `'Tasa'`).
     *                        - `'cat_sat_tipo_impuesto_codigo'`: Código del impuesto (ej. `'002'` para IVA).
     *                        - `'cat_sat_factor_factor'`: Factor del impuesto (ej. `0.160000`).
     *                        - `'<name_tabla_partida>_importe_con_descuento'`: Base imponible (ej. `100.50`).
     *                        - `'<name_tabla_partida>_importe_impuesto'`: Importe del impuesto (ej. `16.08`).
     *
     * @param string $key_gl Clave en `$global_nodo` donde se almacenará el impuesto. Ejemplo: `'IVA'`.
     *
     * @param string $key_importe Clave dentro de `$impuesto` que contiene el importe del impuesto.
     *                             Ejemplo: `'fc_partida_importe_impuesto'`.
     *
     * @param string $name_tabla_partida Nombre de la tabla de partidas en la base de datos.
     *                                   Ejemplo: `'fc_partida'`.
     *
     * @return array Devuelve `$global_nodo` con los datos del impuesto añadidos bajo la clave `$key_gl`.
     *
     * @throws stdClass Devuelve un objeto de error si algún parámetro está vacío o si hay un problema al cargar los datos.
     */
    private function init_imp_global(array $global_nodo, array $impuesto, string $key_gl, string $key_importe,
                                     string $name_tabla_partida): array
    {
        $valida = $this->valida_base(key_gl: $key_gl,key_importe: $key_importe,name_tabla_partida: $name_tabla_partida);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar datos', data: $valida);
        }

        // Inicialización de valores en global_nodo
        $data_imp = $this->init_globales(global_nodo: $global_nodo, impuesto: $impuesto, key: $key_gl,
            key_importe: $key_importe, name_tabla_partida: $name_tabla_partida);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al inicializar global impuesto', data: $data_imp);
        }

        $global_nodo = $data_imp->global_nodo;

        // Carga de datos en global_nodo
        $global_nodo = $this->carga_global(data_imp: $data_imp, impuesto: $impuesto, imp_global: $global_nodo,
            key_gl: $key_gl);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al inicializar global impuesto', data: $global_nodo);
        }

        return $global_nodo;
    }

    /**
     * REG
     * Integra y acumula valores de impuestos en una estructura global.
     *
     * Esta función valida las claves de impuesto y los datos necesarios,
     * luego inicializa la estructura de impuestos si no existe o
     * acumula valores en la estructura existente.
     *
     * ### Ejemplo de entrada válida:
     * ```php
     * $global_imp = [];
     * $impuesto = [
     *     'fc_partida_importe_con_descuento' => 100.00,
     *     'importe_iva' => 16.00,
     *     'cat_sat_factor_factor' => 0.16
     * ];
     * $key_gl = 'IVA';
     * $key_importe = 'importe_iva';
     * $name_tabla_partida = 'fc_partida';
     * ```
     *
     * ### Ejemplo de uso:
     * ```php
     * $resultado = $this->integra_ac_impuesto($global_imp, $impuesto, 'IVA', 'importe_iva', 'fc_partida');
     * print_r($resultado);
     * ```
     *
     * ### Salida esperada si el impuesto es nuevo:
     * ```php
     * Array
     * (
     *     [IVA] => stdClass Object
     *         (
     *             [base] => "100.00"
     *             [importe] => "16.00"
     *             [tasa_o_cuota] => "0.160000"
     *         )
     * )
     * ```
     *
     * ### Salida esperada si el impuesto ya existía (acumulado):
     * ```php
     * Array
     * (
     *     [IVA] => stdClass Object
     *         (
     *             [base] => "200.00"
     *             [importe] => "32.00"
     *             [tasa_o_cuota] => "0.160000"
     *         )
     * )
     * ```
     *
     * ### Ejemplo de entrada inválida:
     * ```php
     * $key_gl = '';
     * $key_importe = 'importe_iva';
     * $name_tabla_partida = 'fc_partida';
     * ```
     *
     * ### Salida esperada en caso de error:
     * ```php
     * Array
     * (
     *     [mensaje] => "Error $key_gl esta vacio"
     *     [data] => ""
     *     [es_final] => true
     * )
     * ```
     *
     * @param array $global_imp Estructura global de acumulación de impuestos.
     * @param array $impuesto Datos del impuesto a integrar. Debe contener claves como
     *                        `'fc_partida_importe_con_descuento'`, `'cat_sat_factor_factor'`, etc.
     * @param string $key_gl Clave del impuesto global, por ejemplo `'IVA'`, `'ISR'`, `'IEPS'`.
     * @param string $key_importe Clave del importe del impuesto, por ejemplo `'importe_iva'`, `'importe_isr'`.
     * @param string $name_tabla_partida Nombre de la tabla de partida, por ejemplo `'fc_partida'`.
     *
     * @return array Retorna la estructura `$global_imp` con los valores de impuesto integrados o acumulados.
     *
     * @throws array Retorna un array con mensaje de error si alguna validación falla.
     */
    private function integra_ac_impuesto(array $global_imp, array $impuesto, string $key_gl, string $key_importe,
                                         string $name_tabla_partida): array
    {
        // Validación de los parámetros base
        $valida = $this->valida_base(key_gl: $key_gl, key_importe: $key_importe, name_tabla_partida: $name_tabla_partida);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar datos', data: $valida);
        }

        // Si el impuesto no está registrado, inicializarlo
        if (!isset($global_imp[$key_gl])) {
            $global_imp = $this->init_imp_global(
                global_nodo: $global_imp,
                impuesto: $impuesto,
                key_gl: $key_gl,
                key_importe: $key_importe,
                name_tabla_partida: $name_tabla_partida
            );

            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al inicializar global impuesto', data: $global_imp);
            }
        }
        // Si el impuesto ya está registrado, acumular valores
        else {
            $global_imp = $this->acumulado_global_impuesto(
                global_imp: $global_imp,
                impuesto: $impuesto,
                key_gl: $key_gl,
                key_importe: $key_importe
            );

            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al inicializar acumulado', data: $global_imp);
            }
        }

        return $global_imp;
    }


    /**
     * REG
     * Genera una clave global única concatenando los valores de impuestos validados.
     *
     * Esta función realiza los siguientes pasos:
     * 1. **Validación y limpieza del array `$impuesto`**:
     *    - Llama a `impuesto_validado()` para verificar la existencia de claves, eliminar espacios en blanco y validar los valores.
     *    - Si alguna validación falla, devuelve un error con los detalles correspondientes.
     *
     * 2. **Construcción de la clave global (`key_gl`)**:
     *    - Concatena los valores de las claves `cat_sat_tipo_factor_id`, `cat_sat_factor_id` y `cat_sat_tipo_impuesto_id` con un separador `.`.
     *    - Aplica `trim()` al resultado final para asegurar que no haya espacios en blanco al inicio o al final.
     *
     * ### Ejemplo de entrada válida:
     * ```php
     * $impuesto = [
     *     'cat_sat_tipo_factor_id' => '1',
     *     'cat_sat_factor_id' => '2',
     *     'cat_sat_tipo_impuesto_id' => '3'
     * ];
     * $resultado = $this->key_gl($impuesto);
     * print_r($resultado);
     * ```
     *
     * ### Salida esperada:
     * ```php
     * "1.2.3"
     * ```
     *
     * ### Ejemplo de entrada inválida (clave faltante):
     * ```php
     * $impuesto = [
     *     'cat_sat_factor_id' => '2',
     *     'cat_sat_tipo_impuesto_id' => '3'
     * ];
     * $resultado = $this->key_gl($impuesto);
     * print_r($resultado);
     * ```
     *
     * ### Salida esperada (error):
     * ```php
     * Array
     * (
     *     [mensaje] => "Error al limpiar impuesto"
     *     [data] => Array
     *         (
     *             [mensaje] => "Error cat_sat_tipo_factor_id no existe"
     *             [data] => Array
     *                 (
     *                     [cat_sat_factor_id] => "2"
     *                     [cat_sat_tipo_impuesto_id] => "3"
     *                 )
     *             [es_final] => true
     *         )
     * )
     * ```
     *
     * @param array $impuesto Arreglo con los datos del impuesto.
     *                        Debe contener las claves:
     *                        - `cat_sat_tipo_factor_id`
     *                        - `cat_sat_factor_id`
     *                        - `cat_sat_tipo_impuesto_id`
     *
     * @return string|array Retorna un string con la clave global generada en formato "X.Y.Z".
     *                      En caso de error, devuelve un array con un mensaje de error.
     *
     * @throws array Retorna un array de error si la validación del impuesto falla.
     */
    private function key_gl(array $impuesto): array|string
    {
        // Validar y limpiar los valores del impuesto
        $impuesto = $this->impuesto_validado(impuesto: $impuesto);
        if (errores::$error) {
            return (new errores())->error(
                mensaje: 'Error al limpiar impuesto',
                data: $impuesto
            );
        }

        // Construir clave global concatenada
        $key_gl = $impuesto['cat_sat_tipo_factor_id'];
        $key_gl .= '.' . $impuesto['cat_sat_factor_id'] . '.' . $impuesto['cat_sat_tipo_impuesto_id'];

        return trim($key_gl);
    }




    /**
     * REG
     * Formatea y estructura los datos de impuestos a partir de los registros de impuestos proporcionados.
     *
     * Esta función toma un objeto `stdClass` que contiene registros de impuestos y los procesa para
     * generar una estructura de salida en un array de objetos `stdClass`, asegurando que cada impuesto
     * tenga los valores correctos y bien formateados.
     *
     * ### Ejemplo de entrada:
     * ```php
     * $impuestos = new stdClass();
     * $impuestos->registros = [
     *     [
     *         'fc_partida_importe_con_descuento' => 100.00,
     *         'cat_sat_factor_factor' => 0.160000,
     *         'cat_sat_tipo_impuesto_codigo' => '002',
     *         'cat_sat_tipo_factor_descripcion' => 'Tasa',
     *         'fc_partida_iva' => 16.00,
     *         'cat_sat_tipo_factor_codigo' => 'Tasa'
     *     ]
     * ];
     * ```
     *
     * ### Ejemplo de uso:
     * ```php
     * $resultado = $this->maqueta_impuesto($impuestos, 'fc_partida_iva', 'fc_partida');
     * print_r($resultado);
     * ```
     *
     * ### Salida esperada:
     * ```php
     * Array
     * (
     *     [0] => stdClass Object
     *         (
     *             [base] => 100.00
     *             [impuesto] => 002
     *             [tipo_factor] => Tasa
     *             [tasa_o_cuota] => 0.160000
     *             [importe] => 16.00
     *         )
     * )
     * ```
     *
     * ### Manejo de impuestos exentos:
     * Si el campo `'cat_sat_tipo_factor_codigo'` es `'Exento'`, los valores `tasa_o_cuota` e `importe` se eliminan del objeto de salida.
     *
     * @param stdClass $impuestos Objeto que contiene registros de impuestos en un array dentro de la propiedad `registros`.
     *                            Cada registro debe ser un array asociativo con claves como `cat_sat_factor_factor`,
     *                            `cat_sat_tipo_impuesto_codigo`, `cat_sat_tipo_factor_descripcion`, y el importe asociado.
     *
     * @param string $key_importe_impuesto Clave del importe del impuesto dentro de cada registro.
     *                                     Ejemplo: `'fc_partida_iva'`.
     *
     * @param string $name_tabla_partida Nombre de la tabla o prefijo usado para construir las claves de los importes.
     *                                   Ejemplo: `'fc_partida'` generará claves como `'fc_partida_importe_con_descuento'`.
     *
     * @return array Devuelve un array de objetos `stdClass`, donde cada objeto representa un impuesto con los campos:
     *               - `base`: Monto base del impuesto formateado a 2 decimales.
     *               - `impuesto`: Código del impuesto según el SAT.
     *               - `tipo_factor`: Descripción del tipo de factor (Ej. "Tasa").
     *               - `tasa_o_cuota`: Valor de la tasa o cuota formateado a 6 decimales (excepto para exentos).
     *               - `importe`: Importe del impuesto formateado a 2 decimales (excepto para exentos).
     *
     * ### Ejemplo de retorno cuando el impuesto es exento:
     * ```php
     * Array
     * (
     *     [0] => stdClass Object
     *         (
     *             [base] => 100.00
     *             [impuesto] => 002
     *             [tipo_factor] => Exento
     *         )
     * )
     * ```
     */
    final public function maqueta_impuesto(
        stdClass $impuestos, string $key_importe_impuesto, string $name_tabla_partida): array
    {
        // Limpia los parámetros de espacios en blanco
        $name_tabla_partida = trim($name_tabla_partida);
        if ($name_tabla_partida === '') {
            return $this->error->error(
                mensaje: 'Error $name_tabla_partida esta vacia',
                data: $name_tabla_partida,
                es_final: true
            );
        }

        $key_importe_impuesto = trim($key_importe_impuesto);
        if ($key_importe_impuesto === '') {
            return $this->error->error(
                mensaje: 'Error $key_importe_impuesto esta vacia',
                data: $key_importe_impuesto,
                es_final: true
            );
        }

        $imp = array();

        // Itera sobre los registros de impuestos
        foreach ($impuestos->registros as $impuesto) {
            if (!is_array($impuesto)) {
                return $this->error->error(
                    mensaje: 'Error $impuesto debe ser un array',
                    data: $impuesto,
                    es_final: true
                );
            }

            // Inicializa valores predeterminados si no existen
            $impuesto[$name_tabla_partida.'_importe_con_descuento']
                = $impuesto[$name_tabla_partida.'_importe_con_descuento'] ?? 0.0;
            $impuesto['cat_sat_factor_factor'] = $impuesto['cat_sat_factor_factor'] ?? 0.0;
            $impuesto['cat_sat_tipo_impuesto_codigo'] = $impuesto['cat_sat_tipo_impuesto_codigo'] ?? '';
            $impuesto['cat_sat_tipo_factor_descripcion'] = $impuesto['cat_sat_tipo_factor_descripcion'] ?? '';
            $impuesto['cat_sat_tipo_factor_codigo'] = $impuesto['cat_sat_tipo_factor_codigo'] ?? '';
            $impuesto[$key_importe_impuesto] = $impuesto[$key_importe_impuesto] ?? 0.0;

            // Crea un objeto para almacenar la información del impuesto
            $impuesto_obj = new stdClass();
            $impuesto_obj->base = number_format($impuesto[$name_tabla_partida.'_importe_con_descuento'],
                2, '.', '');
            $impuesto_obj->impuesto = $impuesto['cat_sat_tipo_impuesto_codigo'];
            $impuesto_obj->tipo_factor = $impuesto['cat_sat_tipo_factor_descripcion'];
            $impuesto_obj->tasa_o_cuota = number_format($impuesto['cat_sat_factor_factor'],
                6, '.', '');
            $impuesto_obj->importe = number_format($impuesto[$key_importe_impuesto], 2,
                '.', '');

            // Si el impuesto es exento, elimina tasa y cuota
            if ($impuesto['cat_sat_tipo_factor_codigo'] === 'Exento') {
                unset($impuesto_obj->tasa_o_cuota);
                unset($impuesto_obj->importe);
            }

            // Agrega el objeto de impuesto a la lista de resultados
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

    /**
     * REG
     * Valida que las claves proporcionadas no estén vacías.
     *
     * Esta función verifica que las claves `$key_gl`, `$key_importe` y `$name_tabla_partida`
     * contengan valores no vacíos después de ser limpiadas con `trim()`. Si alguna está vacía,
     * genera un error y retorna un array con los detalles del error.
     *
     * ### Ejemplo de entrada válida:
     * ```php
     * $key_gl = 'IVA';
     * $key_importe = 'importe_iva';
     * $name_tabla_partida = 'fc_partida';
     * ```
     *
     * ### Ejemplo de uso:
     * ```php
     * $resultado = $this->valida_base('IVA', 'importe_iva', 'fc_partida');
     * var_dump($resultado);
     * ```
     *
     * ### Salida esperada:
     * ```php
     * bool(true)
     * ```
     *
     * ### Ejemplo de entrada inválida:
     * ```php
     * $key_gl = '';
     * $key_importe = 'importe_iva';
     * $name_tabla_partida = 'fc_partida';
     * ```
     *
     * ### Salida esperada en caso de error:
     * ```php
     * Array
     * (
     *     [mensaje] => "Error \$key_gl esta vacio"
     *     [data] => ""
     *     [es_final] => true
     * )
     * ```
     *
     * @param string $key_gl Clave global del impuesto. Ejemplo: `'IVA'`, `'ISR'`, `'IEPS'`.
     * @param string $key_importe Clave del importe del impuesto. Ejemplo: `'importe_iva'`, `'importe_isr'`.
     * @param string $name_tabla_partida Nombre de la tabla de partida a la que pertenece el impuesto.
     *                                   Ejemplo: `'fc_partida'`.
     *
     * @return true|array Devuelve `true` si las claves son válidas. En caso de error,
     *                    retorna un array con el mensaje de error y la clave vacía.
     *
     * @throws array Retorna un array de error si alguna clave está vacía.
     */
    private function valida_base(string $key_gl, string $key_importe, string $name_tabla_partida): true|array
    {
        // Validación de la clave global del impuesto
        $key_gl = trim($key_gl);
        if ($key_gl === '') {
            return $this->error->error(mensaje: 'Error $key_gl esta vacio', data: $key_gl, es_final: true);
        }

        // Validación del nombre de la tabla de partida
        $name_tabla_partida = trim($name_tabla_partida);
        if ($name_tabla_partida === '') {
            return $this->error->error(mensaje: 'Error $name_tabla_partida esta vacio',
                data: $name_tabla_partida, es_final: true);
        }

        // Validación de la clave del importe del impuesto
        $key_importe = trim($key_importe);
        if ($key_importe === '') {
            return $this->error->error(mensaje: 'Error $key_importe esta vacio', data: $key_importe, es_final: true);
        }

        return true;
    }

    /**
     * REG
     * Valida que los valores del array de impuestos cumplan con las siguientes reglas:
     * - Existan las claves obligatorias (`cat_sat_tipo_factor_id`, `cat_sat_factor_id`, `cat_sat_tipo_impuesto_id`).
     * - No estén vacías.
     * - Sean valores numéricos.
     * - Sean valores mayores a 0.
     *
     * ### Proceso de validación:
     * 1. **`valida_keys_existentes()`**: Verifica que las claves necesarias estén presentes en `$impuesto`.
     * 2. **`valida_vacio()`**: Asegura que las claves no tengan valores vacíos.
     * 3. **`valida_numeric()`**: Confirma que las claves contienen valores numéricos.
     * 4. **`valida_negativo()`**: Asegura que los valores sean mayores a 0.
     *
     * Si alguna validación falla, devuelve un array con un mensaje de error.
     *
     * ### Ejemplo de entrada válida:
     * ```php
     * $impuesto = [
     *     'cat_sat_tipo_factor_id' => 1,
     *     'cat_sat_factor_id' => 2,
     *     'cat_sat_tipo_impuesto_id' => 3
     * ];
     * $resultado = $this->valida_foraneas($impuesto);
     * var_dump($resultado);
     * ```
     *
     * ### Salida esperada:
     * ```php
     * bool(true)
     * ```
     *
     * ### Ejemplo de entrada inválida (`cat_sat_tipo_factor_id` está vacío):
     * ```php
     * $impuesto = [
     *     'cat_sat_tipo_factor_id' => '',
     *     'cat_sat_factor_id' => 2,
     *     'cat_sat_tipo_impuesto_id' => 3
     * ];
     * $resultado = $this->valida_foraneas($impuesto);
     * print_r($resultado);
     * ```
     *
     * ### Salida esperada (error):
     * ```php
     * Array
     * (
     *     [mensaje] => "Error al validar impuesto"
     *     [data] => Array
     *         (
     *             [mensaje] => "Error cat_sat_tipo_factor_id esta vacio"
     *             [data] => Array
     *                 (
     *                     [cat_sat_tipo_factor_id] => ""
     *                     [cat_sat_factor_id] => 2
     *                     [cat_sat_tipo_impuesto_id] => 3
     *                 )
     *             [es_final] => true
     *         )
     * )
     * ```
     *
     * ### Ejemplo de entrada inválida (`cat_sat_factor_id` no es numérico):
     * ```php
     * $impuesto = [
     *     'cat_sat_tipo_factor_id' => 1,
     *     'cat_sat_factor_id' => 'ABC',
     *     'cat_sat_tipo_impuesto_id' => 3
     * ];
     * $resultado = $this->valida_foraneas($impuesto);
     * print_r($resultado);
     * ```
     *
     * ### Salida esperada (error):
     * ```php
     * Array
     * (
     *     [mensaje] => "Error al validar impuesto"
     *     [data] => Array
     *         (
     *             [mensaje] => "Error cat_sat_factor_id debe ser un número"
     *             [data] => Array
     *                 (
     *                     [cat_sat_tipo_factor_id] => 1
     *                     [cat_sat_factor_id] => "ABC"
     *                     [cat_sat_tipo_impuesto_id] => 3
     *                 )
     *             [es_final] => true
     *         )
     * )
     * ```
     *
     * @param array $impuesto Arreglo con los datos del impuesto.
     *                        Debe contener las claves:
     *                        - `cat_sat_tipo_factor_id`
     *                        - `cat_sat_factor_id`
     *                        - `cat_sat_tipo_impuesto_id`
     *
     * @return true|array Retorna `true` si todas las validaciones se cumplen correctamente.
     *                    En caso contrario, devuelve un array con un mensaje de error.
     *
     * @throws array Retorna un array de error si alguna validación falla.
     */
    private function valida_foraneas(array $impuesto): true|array
    {
        // Validar existencia de claves
        $valida = $this->valida_keys_existentes(impuesto: $impuesto);
        if (errores::$error) {
            return (new errores())->error(
                mensaje: 'Error al validar impuesto',
                data: $valida,
                es_final: true
            );
        }

        // Validar valores no vacíos
        $valida = $this->valida_vacio(impuesto: $impuesto);
        if (errores::$error) {
            return (new errores())->error(
                mensaje: 'Error al validar impuesto',
                data: $valida
            );
        }

        // Validar que los valores sean numéricos
        $valida = $this->valida_numeric(impuesto: $impuesto);
        if (errores::$error) {
            return (new errores())->error(
                mensaje: 'Error al validar impuesto',
                data: $valida
            );
        }

        // Validar que los valores sean mayores a 0
        $valida = $this->valida_negativo(impuesto: $impuesto);
        if (errores::$error) {
            return (new errores())->error(
                mensaje: 'Error al validar impuesto',
                data: $valida
            );
        }

        return true;
    }


    /**
     * REG
     * Valida que los valores de las claves obligatorias en `$impuesto` sean mayores a 0.
     *
     * Esta función realiza dos validaciones:
     * 1. **Verificación de existencia de claves**: Llama a `valida_keys_existentes()` para asegurarse
     *    de que las claves obligatorias (`cat_sat_tipo_factor_id`, `cat_sat_factor_id`, `cat_sat_tipo_impuesto_id`)
     *    existen en `$impuesto`. Si falta alguna, devuelve un error.
     * 2. **Verificación de valores positivos**: Si alguna de las claves contiene un valor menor o igual a 0,
     *    devuelve un error con un mensaje descriptivo.
     *
     * ### Ejemplo de entrada válida:
     * ```php
     * $impuesto = [
     *     'cat_sat_tipo_factor_id' => 1,
     *     'cat_sat_factor_id' => 2,
     *     'cat_sat_tipo_impuesto_id' => 3
     * ];
     * $resultado = $this->valida_negativo($impuesto);
     * var_dump($resultado);
     * ```
     *
     * ### Salida esperada:
     * ```php
     * bool(true)
     * ```
     *
     * ### Ejemplo de entrada inválida (`cat_sat_tipo_factor_id` es 0):
     * ```php
     * $impuesto = [
     *     'cat_sat_tipo_factor_id' => 0,
     *     'cat_sat_factor_id' => 2,
     *     'cat_sat_tipo_impuesto_id' => 3
     * ];
     * $resultado = $this->valida_negativo($impuesto);
     * print_r($resultado);
     * ```
     *
     * ### Salida esperada (error):
     * ```php
     * Array
     * (
     *     [mensaje] => "Error cat_sat_tipo_factor_id debe ser mayor a 0"
     *     [data] => Array
     *         (
     *             [cat_sat_tipo_factor_id] => 0
     *             [cat_sat_factor_id] => 2
     *             [cat_sat_tipo_impuesto_id] => 3
     *         )
     *     [es_final] => true
     * )
     * ```
     *
     * ### Ejemplo de entrada inválida (`cat_sat_factor_id` es negativo):
     * ```php
     * $impuesto = [
     *     'cat_sat_tipo_factor_id' => 1,
     *     'cat_sat_factor_id' => -5,
     *     'cat_sat_tipo_impuesto_id' => 3
     * ];
     * $resultado = $this->valida_negativo($impuesto);
     * print_r($resultado);
     * ```
     *
     * ### Salida esperada (error):
     * ```php
     * Array
     * (
     *     [mensaje] => "Error cat_sat_factor_id debe ser mayor a 0"
     *     [data] => Array
     *         (
     *             [cat_sat_tipo_factor_id] => 1
     *             [cat_sat_factor_id] => -5
     *             [cat_sat_tipo_impuesto_id] => 3
     *         )
     *     [es_final] => true
     * )
     * ```
     *
     * @param array $impuesto Arreglo con los datos del impuesto.
     *                        Debe contener las claves:
     *                        - `cat_sat_tipo_factor_id`
     *                        - `cat_sat_factor_id`
     *                        - `cat_sat_tipo_impuesto_id`
     *
     * @return true|array Retorna `true` si todas las claves contienen valores mayores a 0.
     *                    En caso contrario, devuelve un array con un mensaje de error.
     *
     * @throws array Retorna un array de error si alguna clave es menor o igual a 0.
     */
    private function valida_negativo(array $impuesto): true|array
    {
        // Validar que las claves requeridas existan en el array
        $valida = $this->valida_keys_existentes(impuesto: $impuesto);
        if (errores::$error) {
            return (new errores())->error(
                mensaje: 'Error al validar impuesto',
                data: $valida,
                es_final: true
            );
        }

        // Verificar si los valores son mayores a 0
        if ((int)$impuesto['cat_sat_tipo_factor_id'] <= 0) {
            return (new errores())->error(
                mensaje: 'Error cat_sat_tipo_factor_id debe ser mayor a 0',
                data: $impuesto,
                es_final: true
            );
        }
        if ((int)$impuesto['cat_sat_factor_id'] <= 0) {
            return (new errores())->error(
                mensaje: 'Error cat_sat_factor_id debe ser mayor a 0',
                data: $impuesto,
                es_final: true
            );
        }
        if ((int)$impuesto['cat_sat_tipo_impuesto_id'] <= 0) {
            return (new errores())->error(
                mensaje: 'Error cat_sat_tipo_impuesto_id debe ser mayor a 0',
                data: $impuesto,
                es_final: true
            );
        }

        return true;
    }


    /**
     * REG
     * Verifica que los valores de las claves obligatorias en `$impuesto` sean numéricos.
     *
     * Esta función realiza dos validaciones:
     * 1. **Verificación de existencia de claves**: Llama a `valida_keys_existentes()` para asegurarse
     *    de que las claves obligatorias (`cat_sat_tipo_factor_id`, `cat_sat_factor_id`, `cat_sat_tipo_impuesto_id`)
     *    existen en `$impuesto`. Si falta alguna, devuelve un error.
     * 2. **Verificación de tipo numérico**: Si alguna de las claves contiene un valor que no es numérico,
     *    devuelve un error con un mensaje descriptivo.
     *
     * ### Ejemplo de entrada válida:
     * ```php
     * $impuesto = [
     *     'cat_sat_tipo_factor_id' => 1,
     *     'cat_sat_factor_id' => '2',
     *     'cat_sat_tipo_impuesto_id' => 3.5
     * ];
     * $resultado = $this->valida_numeric($impuesto);
     * var_dump($resultado);
     * ```
     *
     * ### Salida esperada:
     * ```php
     * bool(true)
     * ```
     *
     * ### Ejemplo de entrada inválida (`cat_sat_tipo_factor_id` no es numérico):
     * ```php
     * $impuesto = [
     *     'cat_sat_tipo_factor_id' => 'ABC',
     *     'cat_sat_factor_id' => 2,
     *     'cat_sat_tipo_impuesto_id' => 3
     * ];
     * $resultado = $this->valida_numeric($impuesto);
     * print_r($resultado);
     * ```
     *
     * ### Salida esperada (error):
     * ```php
     * Array
     * (
     *     [mensaje] => "Error cat_sat_tipo_factor_id debe ser un numero"
     *     [data] => Array
     *         (
     *             [cat_sat_tipo_factor_id] => "ABC"
     *             [cat_sat_factor_id] => 2
     *             [cat_sat_tipo_impuesto_id] => 3
     *         )
     *     [es_final] => true
     * )
     * ```
     *
     * @param array $impuesto Arreglo con los datos del impuesto.
     *                        Debe contener las claves:
     *                        - `cat_sat_tipo_factor_id`
     *                        - `cat_sat_factor_id`
     *                        - `cat_sat_tipo_impuesto_id`
     *
     * @return true|array Retorna `true` si todas las claves contienen valores numéricos.
     *                    En caso contrario, devuelve un array con un mensaje de error.
     *
     * @throws array Retorna un array de error si alguna clave obligatoria no es numérica.
     */
    private function valida_numeric(array $impuesto): true|array
    {
        // Validar que las claves requeridas existan en el array
        $valida = $this->valida_keys_existentes(impuesto: $impuesto);
        if (errores::$error) {
            return (new errores())->error(
                mensaje: 'Error al validar impuesto',
                data: $valida,
                es_final: true
            );
        }

        // Verificar si los valores son numéricos
        if (!is_numeric($impuesto['cat_sat_tipo_factor_id'])) {
            return (new errores())->error(
                mensaje: 'Error cat_sat_tipo_factor_id debe ser un número',
                data: $impuesto,
                es_final: true
            );
        }
        if (!is_numeric($impuesto['cat_sat_factor_id'])) {
            return (new errores())->error(
                mensaje: 'Error cat_sat_factor_id debe ser un número',
                data: $impuesto,
                es_final: true
            );
        }
        if (!is_numeric($impuesto['cat_sat_tipo_impuesto_id'])) {
            return (new errores())->error(
                mensaje: 'Error cat_sat_tipo_impuesto_id debe ser un número',
                data: $impuesto,
                es_final: true
            );
        }

        return true;
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

    /**
     * REG
     * Valida que las claves esenciales del array de impuestos no estén vacías.
     *
     * Esta función realiza dos validaciones:
     * 1. **Verificación de existencia de claves**: Llama a `valida_keys_existentes()` para asegurarse
     *    de que las claves obligatorias (`cat_sat_tipo_factor_id`, `cat_sat_factor_id`, `cat_sat_tipo_impuesto_id`)
     *    existen en `$impuesto`. Si falta alguna, devuelve un error.
     * 2. **Verificación de valores vacíos**: Si alguna de las claves contiene un valor vacío (`''`),
     *    devuelve un error con un mensaje descriptivo.
     *
     * ### Ejemplo de entrada válida:
     * ```php
     * $impuesto = [
     *     'cat_sat_tipo_factor_id' => '1',
     *     'cat_sat_factor_id' => '2',
     *     'cat_sat_tipo_impuesto_id' => '3'
     * ];
     * $resultado = $this->valida_vacio($impuesto);
     * var_dump($resultado);
     * ```
     *
     * ### Salida esperada:
     * ```php
     * bool(true)
     * ```
     *
     * ### Ejemplo de entrada inválida (valor vacío en `cat_sat_tipo_factor_id`):
     * ```php
     * $impuesto = [
     *     'cat_sat_tipo_factor_id' => '',
     *     'cat_sat_factor_id' => '2',
     *     'cat_sat_tipo_impuesto_id' => '3'
     * ];
     * $resultado = $this->valida_vacio($impuesto);
     * print_r($resultado);
     * ```
     *
     * ### Salida esperada (error):
     * ```php
     * Array
     * (
     *     [mensaje] => "Error cat_sat_tipo_factor_id esta vacio"
     *     [data] => Array
     *         (
     *             [cat_sat_tipo_factor_id] => ""
     *             [cat_sat_factor_id] => "2"
     *             [cat_sat_tipo_impuesto_id] => "3"
     *         )
     *     [es_final] => true
     * )
     * ```
     *
     * @param array $impuesto Arreglo con los datos del impuesto.
     *                        Debe contener las claves:
     *                        - `cat_sat_tipo_factor_id`
     *                        - `cat_sat_factor_id`
     *                        - `cat_sat_tipo_impuesto_id`
     *
     * @return true|array Retorna `true` si todas las claves tienen valores no vacíos.
     *                    En caso contrario, devuelve un array con un mensaje de error.
     *
     * @throws array Retorna un array de error si alguna clave obligatoria está vacía.
     */
    private function valida_vacio(array $impuesto): true|array
    {
        // Validar que las claves requeridas existan en el array
        $valida = $this->valida_keys_existentes(impuesto: $impuesto);
        if (errores::$error) {
            return (new errores())->error(
                mensaje: 'Error al validar impuesto',
                data: $valida,
                es_final: true
            );
        }

        // Verificar si alguna de las claves tiene un valor vacío
        if ($impuesto['cat_sat_tipo_factor_id'] === '') {
            return (new errores())->error(
                mensaje: 'Error cat_sat_tipo_factor_id esta vacio',
                data: $impuesto,
                es_final: true
            );
        }
        if ($impuesto['cat_sat_factor_id'] === '') {
            return (new errores())->error(
                mensaje: 'Error cat_sat_factor_id esta vacio',
                data: $impuesto,
                es_final: true
            );
        }
        if ($impuesto['cat_sat_tipo_impuesto_id'] === '') {
            return (new errores())->error(
                mensaje: 'Error cat_sat_tipo_impuesto_id esta vacio',
                data: $impuesto,
                es_final: true
            );
        }

        return true;
    }


    /**
     * REG
     * Verifica que el array de impuestos contenga las claves obligatorias.
     *
     * Esta función valida la existencia de las claves:
     * - `cat_sat_tipo_factor_id`
     * - `cat_sat_factor_id`
     * - `cat_sat_tipo_impuesto_id`
     *
     * Si alguna clave no existe, devuelve un array con un mensaje de error.
     * Si todas las claves existen, devuelve `true`.
     *
     * ### Ejemplo de entrada válida:
     * ```php
     * $impuesto = [
     *     'cat_sat_tipo_factor_id' => 1,
     *     'cat_sat_factor_id' => 2,
     *     'cat_sat_tipo_impuesto_id' => 3
     * ];
     * $resultado = $this->valida_keys_existentes($impuesto);
     * var_dump($resultado);
     * ```
     *
     * ### Salida esperada:
     * ```php
     * bool(true)
     * ```
     *
     * ### Ejemplo de entrada inválida (falta `cat_sat_tipo_factor_id`):
     * ```php
     * $impuesto = [
     *     'cat_sat_factor_id' => 2,
     *     'cat_sat_tipo_impuesto_id' => 3
     * ];
     * $resultado = $this->valida_keys_existentes($impuesto);
     * var_dump($resultado);
     * ```
     *
     * ### Salida esperada:
     * ```php
     * array(3) {
     *   ["mensaje"] => string(37) "Error cat_sat_tipo_factor_id no existe"
     *   ["data"] => array(2) {
     *     ["cat_sat_factor_id"] => int(2)
     *     ["cat_sat_tipo_impuesto_id"] => int(3)
     *   }
     *   ["es_final"] => bool(true)
     * }
     * ```
     *
     * @param array $impuesto Arreglo con los datos del impuesto.
     *                        Debe contener las claves:
     *                        - `cat_sat_tipo_factor_id`
     *                        - `cat_sat_factor_id`
     *                        - `cat_sat_tipo_impuesto_id`
     *
     * @return true|array Retorna `true` si todas las claves existen.
     *                    En caso contrario, retorna un array con un mensaje de error y los datos del array recibido.
     *
     * @throws array Devuelve un array de error si falta alguna clave obligatoria.
     */
    private function valida_keys_existentes(array $impuesto): true|array
    {
        if (!isset($impuesto['cat_sat_tipo_factor_id'])) {
            return (new errores())->error(
                mensaje: 'Error cat_sat_tipo_factor_id no existe',
                data: $impuesto,
                es_final: true
            );
        }

        if (!isset($impuesto['cat_sat_factor_id'])) {
            return (new errores())->error(
                mensaje: 'Error cat_sat_factor_id no existe',
                data: $impuesto,
                es_final: true
            );
        }

        if (!isset($impuesto['cat_sat_tipo_impuesto_id'])) {
            return (new errores())->error(
                mensaje: 'Error cat_sat_tipo_impuesto_id no existe',
                data: $impuesto,
                es_final: true
            );
        }

        return true;
    }

}
