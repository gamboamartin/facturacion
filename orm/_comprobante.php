<?php
namespace gamboamartin\facturacion\models;
use gamboamartin\errores\errores;
use gamboamartin\validacion\validacion;

class _comprobante{
    private errores $error;
    private validacion  $validacion;

    public function __construct()
    {
        $this->error = new errores();
        $this->validacion = new validacion();
    }

    /**
     * Maqueta datos para un comprobante de factura
     * @param string $name_entidad
     * @param array $row_entidad Factura a obtener datos
     * @return array
     * @version 4.10.0
     */
    final public function comprobante(string $name_entidad, array $row_entidad): array
    {
        if(count($row_entidad) === 0){
            return $this->error->error(mensaje: 'Error la factura pasada no tiene registros', data: $row_entidad);
        }

        $column_sub_total = $name_entidad.'_sub_total_base';
        $column_total = $name_entidad.'_total';
        $column_descuento = $name_entidad.'_total_descuento';
        $column_exportacion = $name_entidad.'_exportacion';
        $column_folio = $name_entidad.'_folio';
        $column_fecha = $name_entidad.'_fecha';

        $keys = array($column_sub_total,$column_total, $column_descuento,'dp_cp_descripcion',
            'cat_sat_tipo_de_comprobante_codigo','cat_sat_moneda_codigo',$column_exportacion, $column_folio,
            'cat_sat_forma_pago_codigo','cat_sat_metodo_pago_codigo',$column_fecha);

        $valida = $this->validacion->valida_existencia_keys(keys: $keys,registro:  $row_entidad);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al valida row_entidad', data: $valida);
        }

        $fc_sub_total = $this->monto_dos_dec(monto: $row_entidad[$column_sub_total]);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al limpiar monto', data: $fc_sub_total);
        }
        $fc_total = $this->monto_dos_dec(monto: $row_entidad[$column_total]);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al limpiar monto', data: $fc_total);
        }
        $fc_descuento = $this->monto_dos_dec(monto: $row_entidad[$column_descuento]);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al limpiar monto', data: $fc_descuento);
        }

        $comprobante = array();
        $comprobante['lugar_expedicion'] = $row_entidad['dp_cp_descripcion'];
        $comprobante['tipo_de_comprobante'] = $row_entidad['cat_sat_tipo_de_comprobante_codigo'];
        $comprobante['moneda'] = $row_entidad['cat_sat_moneda_codigo'];
        $comprobante['sub_total'] = $fc_sub_total;
        $comprobante['total'] = $fc_total;
        $comprobante['exportacion'] = $row_entidad[$column_exportacion];
        $comprobante['folio'] = $row_entidad[$column_folio];
        $comprobante['forma_pago'] = $row_entidad['cat_sat_forma_pago_codigo'];
        $comprobante['descuento'] = $fc_descuento;
        $comprobante['metodo_pago'] = $row_entidad['cat_sat_metodo_pago_codigo'];
        $comprobante['fecha'] = $row_entidad[$column_fecha];

        if(isset($row_entidad['fc_csd_no_certificado'])){
            if(trim($row_entidad['fc_csd_no_certificado']) !== ''){
                $comprobante['no_certificado'] = $row_entidad['fc_csd_no_certificado'];
            }

        }

        if(isset($row_entidad['com_tipo_cambio_monto'])){
            if((float)($row_entidad['com_tipo_cambio_monto']) !== 1.0){
                $comprobante['tipo_cambio'] = $row_entidad['com_tipo_cambio_monto'];
            }

        }
        return $comprobante;
    }

    /**
     * REG
     * Limpia un monto dado eliminando espacios, comas y signos de dólar.
     *
     * Esta función recibe un valor numérico en formato `string`, `int` o `float`
     * y lo limpia de caracteres innecesarios como espacios, comas y el signo `$`,
     * dejándolo listo para ser procesado como un número en operaciones matemáticas.
     *
     * @param string|int|float $monto Monto a limpiar. Puede ser un número en formato string con caracteres especiales o un valor numérico sin formato.
     * @return array|string Devuelve el monto en formato string sin espacios, comas ni símbolos de dólar.
     *
     * @example
     * ```php
     * $comprobante = new _comprobante();
     * $monto_limpio = $comprobante->limpia_monto(" $1,234.56 ");
     * // Resultado esperado: "1234.56"
     *
     * $monto_limpio = $comprobante->limpia_monto(9876.54);
     * // Resultado esperado: "9876.54" (Sin cambios porque ya es un número válido)
     *
     * $monto_limpio = $comprobante->limpia_monto("5 000,75");
     * // Resultado esperado: "5000.75"
     * ```
     *
     * @version 4.7.0
     */
    private function limpia_monto(string|int|float $monto): array|string
    {
        // Elimina espacios en blanco al inicio y al final
        $monto = trim($monto);

        // Elimina espacios intermedios
        $monto = str_replace(' ', '', $monto);

        // Elimina comas de miles
        $monto = str_replace(',', '', $monto);

        // Elimina el signo de dólar
        return str_replace('$', '', $monto);
    }


    /**
     * REG
     * Aplica formato de dos decimales a un monto numérico.
     *
     * Esta función limpia un monto eliminando caracteres no numéricos y lo convierte
     * en un número con dos decimales redondeados. Si el monto no es válido, devuelve un error.
     *
     * @param string|int|float $monto Monto a formatear. Puede ser un número en diferentes formatos (string, int o float).
     * @return string|array Devuelve el monto en formato string con dos decimales o un array con error en caso de fallo.
     *
     * @example
     * ```php
     * $comprobante = new _comprobante();
     * $monto_formateado = $comprobante->monto_dos_dec(" 1,234.567 ");
     * // Resultado esperado: "1234.57"
     *
     * $monto_formateado = $comprobante->monto_dos_dec(100);
     * // Resultado esperado: "100.00"
     *
     * $monto_formateado = $comprobante->monto_dos_dec("$9,876.5");
     * // Resultado esperado: "9876.50"
     *
     * $monto_formateado = $comprobante->monto_dos_dec("texto");
     * // Resultado esperado: ['error' => true, 'mensaje' => 'Error el monto no es un número', 'data' => 'texto']
     * ```
     *
     * @version 4.9.0
     */
    final public function monto_dos_dec(string|int|float $monto): string|array
    {
        // Limpia el monto eliminando espacios, comas y el signo de dólar
        $monto = $this->limpia_monto(monto: $monto);

        // Verifica si hubo un error en la limpieza
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al limpiar monto', data: $monto);
        }

        // Verifica si el monto es numérico
        if(!is_numeric($monto)){
            return $this->error->error(mensaje: 'Error el monto no es un número', data: $monto);
        }

        // Redondea el monto a dos decimales
        $monto = round($monto, 2);

        // Formatea el número con dos decimales y punto decimal
        return number_format($monto, 2, '.', '');
    }


}
