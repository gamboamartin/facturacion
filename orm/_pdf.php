<?php
namespace gamboamartin\facturacion\models;
use gamboamartin\cat_sat\models\cat_sat_regimen_fiscal;
use gamboamartin\comercial\models\com_tmp_cte_dp;
use gamboamartin\direccion_postal\models\dp_calle_pertenece;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\controllers\pdf;
use gamboamartin\organigrama\models\org_logo;
use PDO;
use stdClass;

class _pdf{
    private errores $error;

    public function __construct(){
        $this->error = new errores();
    }

    /**
     * Obtiene el cp de un receptor
     * @param array $cp_receptor
     * @param array $factura
     * @param PDO $link
     * @return array|string
     */
    private function cod_postal_receptor(array $cp_receptor, array $factura, PDO $link): array|string
    {
        $cod_postal_receptor = $cp_receptor['dp_cp_descripcion'];

        $filtro = array();
        $filtro["com_tmp_cte_dp.com_cliente_id"] = $factura['com_cliente_id'];
        $existe_cp_tmp = (new com_tmp_cte_dp(link: $link))->existe(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar si existe', data:  $existe_cp_tmp);
        }
        if($existe_cp_tmp){
            $r_com_tmp_cte_dp = (new com_tmp_cte_dp(link: $link))->filtro_and(filtro: $filtro);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al validar si existe', data:  $r_com_tmp_cte_dp);
            }
            $cod_postal_receptor = $r_com_tmp_cte_dp->registros[0]['com_tmp_cte_dp_dp_cp'];
        }
        return $cod_postal_receptor;
    }

    private function data_factura(stdClass $cfdi_sellado){
        $data = $this->data_init_limpio();
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al asignar datos', data:  $data);
        }

        if ($cfdi_sellado->n_registros > 0) {

            $data = $this->data_init(cfdi_sellado: $cfdi_sellado);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al asignar datos', data:  $data);
            }

        }
        return $data;
    }

    private function data_init(stdClass $cfdi_sellado): stdClass
    {
        $folio_fiscal = $cfdi_sellado->registros[0]['fc_cfdi_sellado_uuid'];
        $sello_cfdi = $cfdi_sellado->registros[0]['fc_cfdi_sellado_complemento_tfd_sello_cfd'];
        $sello_sat = $cfdi_sellado->registros[0]['fc_cfdi_sellado_complemento_tfd_sello_sat'];
        $complento = $cfdi_sellado->registros[0]['fc_cfdi_sellado_cadena_complemento_sat'];
        $rfc_proveedor = $cfdi_sellado->registros[0]['fc_cfdi_sellado_complemento_tfd_rfc_prov_certif'];
        $fecha_timbrado = $cfdi_sellado->registros[0]['fc_cfdi_sellado_complemento_tfd_fecha_timbrado'];
        $no_certificado = $cfdi_sellado->registros[0]['fc_cfdi_sellado_comprobante_no_certificado'];

        $data = new stdClass();
        $data->folio_fiscal = $folio_fiscal;
        $data->sello_cfdi = $sello_cfdi;
        $data->sello_sat = $sello_sat;
        $data->complento = $complento;
        $data->rfc_proveedor = $rfc_proveedor;
        $data->fecha_timbrado = $fecha_timbrado;
        $data->no_certificado = $no_certificado;

        return $data;

    }

    private function data_init_limpio(): stdClass
    {
        $folio_fiscal = "-----";
        $sello_cfdi = "";
        $sello_sat = "";
        $complento = "---";
        $rfc_proveedor = "-----";
        $fecha_timbrado = "xxxx-xx-xx 00:00:00";
        $no_certificado = "-----";

        $data = new stdClass();
        $data->folio_fiscal = $folio_fiscal;
        $data->sello_cfdi = $sello_cfdi;
        $data->sello_sat = $sello_sat;
        $data->complento = $complento;
        $data->rfc_proveedor = $rfc_proveedor;
        $data->fecha_timbrado = $fecha_timbrado;
        $data->no_certificado = $no_certificado;

        return $data;

    }
    final public function pdf( bool $descarga, bool $guarda, PDO $link, _partida $modelo_partida,
                               _cuenta_predial $modelo_predial, _relacion $modelo_relacion,
                               _relacionada $modelo_relacionada, _data_impuestos $modelo_retencion,
                               _data_impuestos $modelo_traslado, int $registro_id):array|string|bool{

        $factura = (new fc_factura($link))->get_factura(modelo_partida: $modelo_partida,
            modelo_predial:  $modelo_predial,modelo_relacion:  $modelo_relacion,
            modelo_relacionada: $modelo_relacionada,modelo_retencion:  $modelo_retencion,
            modelo_traslado:  $modelo_traslado,registro_id:  $registro_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener factura',data:  $factura);
        }

        $ruta_qr = (new fc_factura_documento(link: $link))->get_factura_documento(key_entidad_filter_id: 'fc_factura.id',
            registro_id: $registro_id, tipo_documento: "qr_cfdi");
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener QR',data:  $ruta_qr);
        }

        $filtro = array();
        $filtro['org_empresa.id'] = $factura['org_empresa_id'];
        $r_org_logo = (new org_logo(link: $link))->filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener Logo',data:  $r_org_logo);
        }
        $ruta_logo = '';
        if($r_org_logo->n_registros > 0) {
            $ruta_logo = $r_org_logo->registros[0]['doc_documento_ruta_absoluta'];
        }

        $filtro = array();
        $filtro["fc_factura_id"] = $factura['fc_factura_id'];
        $cfdi_sellado = (new fc_cfdi_sellado($link))->filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener cfdi_sellado', data:  $cfdi_sellado);
        }

        $cp_receptor = (new dp_calle_pertenece($link))->registro(
            $factura["com_cliente_dp_calle_pertenece_id"]);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener regimen fiscal emisor', data:  $cp_receptor);
        }

        $cod_postal_receptor = $this->cod_postal_receptor(cp_receptor: $cp_receptor,factura:  $factura,link:  $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener cod rec', data:  $cod_postal_receptor);
        }


        $rf_emisor = (new cat_sat_regimen_fiscal($link))->registro(
            $factura["org_empresa_cat_sat_regimen_fiscal_id"]);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener regimen fiscal emisor', data:  $rf_emisor);
        }

        $rf_receptor = (new cat_sat_regimen_fiscal($link))->registro(
            $factura["com_cliente_cat_sat_regimen_fiscal_id"]);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener regimen fiscal receptor', data:  $rf_receptor);
        }


        $data = $this->data_factura(cfdi_sellado: $cfdi_sellado);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al asignar datos', data:  $data);
        }



        if(!isset($factura['fc_factura_observaciones'])){
            $factura['fc_factura_observaciones'] = '';
        }

        $pdf = new pdf();
        $pdf->header(cfdi: $factura['cat_sat_uso_cfdi_descripcion'], cod_postal: $factura['dp_cp_descripcion'],
            cod_postal_receptor: $cod_postal_receptor, csd: $factura['fc_csd_serie'],
            efecto: $factura['cat_sat_tipo_de_comprobante_descripcion'],
            exportacion: $factura['fc_factura_exportacion'], fecha: $factura['fc_factura_fecha'],
            folio: $factura['fc_factura_folio'], folio_fiscal: $data->folio_fiscal,
            nombre_emisor: $factura['org_empresa_razon_social'], nombre_receptor: $factura['com_cliente_razon_social'],
            observaciones: $factura['fc_factura_observaciones'],
            regimen_fiscal: $rf_emisor['cat_sat_regimen_fiscal_descripcion'],
            regimen_fiscal_receptor: $rf_receptor['cat_sat_regimen_fiscal_descripcion'],
            rfc_emisor: $factura['org_empresa_rfc'], rfc_receptor: $factura['com_cliente_rfc'], ruta_logo: $ruta_logo);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al maquetar header',data:  $pdf);
        }

        $relacionadas = (new fc_factura(link: $link))->get_data_relaciones(modelo_relacion: $modelo_relacion,
            modelo_relacionada:  $modelo_relacionada,registro_entidad_id:  $registro_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener relacionadas',data:  $relacionadas);
        }

        $rs = $pdf->data_relacionados(relacionadas: $relacionadas);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al maquetar relacionadas',data:  $rs);
        }

        $rs = $pdf->conceptos(conceptos: $factura['partidas'], link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al maquetar conceptos',data:  $rs);
        }

        $pdf->totales(moneda: $factura['cat_sat_moneda_descripcion'],subtotal: $factura['fc_factura_sub_total'],
            forma_pago: $factura['cat_sat_forma_pago_descripcion'],imp_trasladados: $factura['total_impuestos_trasladados'],
            imp_retenidos: $factura['total_impuestos_retenidos'],metodo_pago: $factura['cat_sat_metodo_pago_descripcion'],
            total: $factura['fc_factura_total']);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al maquetar totales',data:  $pdf);
        }



        $pdf->complementos(ruta_documento: $ruta_qr, complento: $data->complento,rfc_proveedor: $data->rfc_proveedor,
            fecha: $data->fecha_timbrado, no_certificado: $data->no_certificado);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al maquetar complementos',data:  $pdf);
        }

        $pdf->sellos(sello_cfdi: $data->sello_cfdi,sello_sat: $data->sello_sat);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al maquetar sellos',data:  $pdf);
        }

        $pdf->footer(descripcion: "efacturacion.com.mx");
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar pdf',data:  $pdf);
        }

        $nombre_documento = $factura['fc_factura_serie'].$factura['fc_factura_folio'];

        $nombre_documento = $pdf->guardar(nombre_documento: $nombre_documento, descarga: $descarga, guarda: $guarda);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar pdf',data:  $pdf);
        }
        return $nombre_documento;
    }
}
