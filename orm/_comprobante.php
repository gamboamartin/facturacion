<?php
namespace gamboamartin\facturacion\models;
use gamboamartin\errores\errores;

class _comprobante{
    private errores $error;

    public function __construct()
    {
        $this->error = new errores();
    }

    /**
     * Maqueta datos para un comprobante de factura
     * @param array $factura Factura a obtener datos
     * @return array
     * @version 4.10.0
     */
    final public function comprobante(array $factura): array
    {
        if(count($factura) === 0){
            return $this->error->error(mensaje: 'Error la factura pasada no tiene registros', data: $factura);
        }

        $fc_factura_sub_total = $this->monto_dos_dec(monto: $factura['fc_factura_sub_total']);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al limpiar monto', data: $fc_factura_sub_total);
        }
        $fc_factura_total = $this->monto_dos_dec(monto: $factura['fc_factura_total']);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al limpiar monto', data: $fc_factura_total);
        }
        $fc_factura_descuento = $this->monto_dos_dec(monto: $factura['fc_factura_descuento']);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al limpiar monto', data: $fc_factura_descuento);
        }



        $comprobante = array();
        $comprobante['lugar_expedicion'] = $factura['dp_cp_descripcion'];
        $comprobante['tipo_de_comprobante'] = $factura['cat_sat_tipo_de_comprobante_codigo'];
        $comprobante['moneda'] = $factura['cat_sat_moneda_codigo'];
        $comprobante['sub_total'] = $fc_factura_sub_total;
        $comprobante['total'] = $fc_factura_total;
        $comprobante['exportacion'] = $factura['fc_factura_exportacion'];
        $comprobante['folio'] = $factura['fc_factura_folio'];
        $comprobante['forma_pago'] = $factura['cat_sat_forma_pago_codigo'];
        $comprobante['descuento'] = $fc_factura_descuento;
        $comprobante['metodo_pago'] = $factura['cat_sat_metodo_pago_codigo'];
        $comprobante['fecha'] = $factura['fc_factura_fecha'];

        if(isset($factura['fc_csd_no_certificado'])){
            if(trim($factura['fc_csd_no_certificado']) !== ''){
                $comprobante['no_certificado'] = $factura['fc_csd_no_certificado'];
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
     * @return string
     * @version 4.9.0
     */
    final public function monto_dos_dec(string|int|float $monto): string
    {
        $monto = $this->limpia_monto(monto: $monto);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al limpiar monto', data: $monto);
        }
        $monto = round($monto,2);
        return number_format($monto,2,'.','');
    }

}
