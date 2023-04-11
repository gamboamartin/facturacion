<?php
namespace gamboamartin\facturacion\models;
use gamboamartin\cat_sat\models\cat_sat_regimen_fiscal;
use gamboamartin\direccion_postal\models\dp_calle_pertenece;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\controllers\pdf;
use PDO;

class _pdf{
    private errores $error;

    public function __construct(){
        $this->error = new errores();
    }
    final public function pdf( bool $descarga, int $fc_factura_id, bool $guarda, PDO $link){
        $factura = (new fc_factura($link))->get_factura(fc_factura_id: $fc_factura_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener factura',data:  $factura);
        }

        $ruta_qr = (new fc_factura_documento(link: $link))->get_factura_documento(fc_factura_id: $fc_factura_id,
            tipo_documento: "qr_cfdi");
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener QR',data:  $ruta_qr);
        }


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

        $folio_fiscal = "-----";
        $sello_cfdi = "";
        $sello_sat = "";
        $complento = "---";
        $rfc_proveedor = "-----";
        $fecha_timbrado = "xxxx-xx-xx 00:00:00";
        $no_certificado = "-----";

        if ($cfdi_sellado->n_registros > 0) {
            $folio_fiscal = $cfdi_sellado->registros[0]['fc_cfdi_sellado_uuid'];
            $sello_cfdi = $cfdi_sellado->registros[0]['fc_cfdi_sellado_complemento_tfd_sello_cfd'];
            $sello_sat = $cfdi_sellado->registros[0]['fc_cfdi_sellado_complemento_tfd_sello_sat'];
            $complento = $cfdi_sellado->registros[0]['fc_cfdi_sellado_cadena_complemento_sat'];
            $rfc_proveedor = $cfdi_sellado->registros[0]['fc_cfdi_sellado_complemento_tfd_rfc_prov_certif'];
            $fecha_timbrado = $cfdi_sellado->registros[0]['fc_cfdi_sellado_complemento_tfd_fecha_timbrado'];
            $no_certificado = $cfdi_sellado->registros[0]['fc_cfdi_sellado_comprobante_no_certificado'];
        }

        if(!isset($factura['fc_factura_observaciones'])){
            $factura['fc_factura_observaciones'] = '';
        }

        $pdf = new pdf();
        $pdf->header(rfc_emisor: $factura['org_empresa_rfc'],folio_fiscal: $folio_fiscal,
            nombre_emisor: $factura['org_empresa_razon_social'],csd: $factura['fc_csd_serie'],
            rfc_receptor: $factura['com_cliente_rfc'],cod_postal: $factura['dp_cp_descripcion'], fecha: $factura['fc_factura_fecha'],
            nombre_receptor: $factura['com_cliente_razon_social'],efecto: $factura['cat_sat_tipo_de_comprobante_descripcion'],
            cod_postal_receptor: $cp_receptor['dp_cp_descripcion'], regimen_fiscal: $rf_emisor['cat_sat_regimen_fiscal_descripcion'],
            regimen_fiscal_receptor: $rf_receptor['cat_sat_regimen_fiscal_descripcion'],
            exportacion: $factura['fc_factura_exportacion'],cfdi: $factura['cat_sat_uso_cfdi_descripcion'],
            observaciones: $factura['fc_factura_observaciones']);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al maquetar header',data:  $pdf);
        }

        $relacionadas = (new fc_factura(link: $link))->get_data_relaciones(fc_factura_id: $fc_factura_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener relacionadas',data:  $relacionadas);
        }

        $rs = $pdf->data_relacionados(relacionadas: $relacionadas);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al maquetar relacionadas',data:  $rs);
        }

        $rs = $pdf->conceptos(conceptos: $factura['partidas']);
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



        $pdf->complementos(ruta_documento: $ruta_qr, complento: $complento,rfc_proveedor: $rfc_proveedor,
            fecha: $fecha_timbrado, no_certificado: $no_certificado);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al maquetar complementos',data:  $pdf);
        }

        $pdf->sellos(sello_cfdi: $sello_cfdi,sello_sat: $sello_sat);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al maquetar sellos',data:  $pdf);
        }

        $pdf->footer(descripcion: "--- IVITEC ---");
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
