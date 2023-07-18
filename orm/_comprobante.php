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
     * Limpia un monto para dejarlo double
     * @param string|int|float $monto Monto a limpiar
     * @return array|string
     * @version 4.7.0
     */
    private function limpia_monto(string|int|float $monto): array|string
    {
        $monto = trim($monto);
        $monto = str_replace(' ', '', $monto);
        $monto = str_replace(',', '', $monto);
        return str_replace('$', '', $monto);
    }

    /**
     * Aplica formato a un monto ingresado
     * @param string|int|float $monto Monto a dar formato
     * @return string|array
     * @version 4.9.0
     */
    final public function monto_dos_dec(string|int|float $monto): string|array
    {
        $monto = $this->limpia_monto(monto: $monto);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al limpiar monto', data: $monto);
        }

        if(!is_numeric($monto)){
            return $this->error->error(mensaje: 'Error al monto no es un numero', data: $monto);
        }

        $monto = round($monto,2);
        return number_format($monto,2,'.','');
    }

}
