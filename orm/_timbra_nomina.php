<?php
namespace gamboamartin\facturacion\models;

use config\generales;
use config\pac;
use gamboamartin\documento\models\doc_documento;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\controllers\_make_json;
use gamboamartin\facturacion\models\_timbra_nomina\_certificado;
use gamboamartin\facturacion\models\_timbra_nomina\_datos;
use gamboamartin\facturacion\models\_timbra_nomina\_finalizacion;
use gamboamartin\facturacion\models\_timbra_nomina\_salida;
use gamboamartin\facturacion\pac\_cnx_pac;
use gamboamartin\modelo\modelo;
use PDO;
use stdClass;

class _timbra_nomina
{


    /**
     * out
     * @param PDO $link
     * @param int $fc_row_layout_id
     * @return array|stdClass
     */
    final public function timbra_recibo(PDO $link, int $fc_row_layout_id)
    {

        $datos_rec = (new _datos())->datos_recibo($link, $fc_row_layout_id);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al obtener datos de recibo', data: $datos_rec);
        }

        if((int)$datos_rec->fc_layout_nom->fc_layout_nom_id <= 178){
            //return (new errores())->error(mensaje: 'Error timbrado version anterior', data: $datos_rec);
        }

        if($datos_rec->fc_row_layout->fc_row_layout_esta_timbrado === 'activo'){
            return (new errores())->error(mensaje: 'Error ya esta timbrado', data: $datos_rec);
        }

        $datos_cfdi = (new _datos())->datos_response_timbre($link, $datos_rec->fc_row_layout);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al obtener datos de datos_cfdi', data: $datos_cfdi);
        }

        $response = (new _cnx_pac())->operacion_timbrarJSON2($datos_cfdi->jsonB64, $datos_cfdi->keyPEM,
            $datos_cfdi->cerPEM, $datos_cfdi->plantilla);
        $response = json_decode($response, false);
        $codigo = trim($response->codigo);

        $code_error = (new _salida()) ->code_error($codigo, $response, $datos_cfdi->nomina_json,$link,$fc_row_layout_id);
        if(errores::$error){
            return (new errores())->error("Error al timbrar", $code_error);
        }

        $rs_exito = (new _finalizacion())->cfdi_exito($response, $datos_rec->fc_row_layout,$link);
        if(errores::$error) {
            return (new errores())->error("Error al actualizar UUID", $rs_exito);
        }

        $rs_estado = (new _finalizacion())->upd_estado_timbrado_fc_layout_nom(fc_layout_nom_id: $datos_rec->fc_layout_nom->fc_layout_nom_id,link: $link);
        if(errores::$error) {
            return (new errores())->error("Error en upd_estado_timbrado_fc_layout_nom", $rs_estado);
        }

        $out = new stdClass();
        $out->datos_rec = $datos_rec;
        $out->datos_cfdi = $datos_cfdi;
        $out->response = $response;
        $out->rs_exito = $rs_exito;
        $out->rs_estado = $rs_estado;
        return $out;

    }

    final public function retimbra_recibo(PDO $link, int $fc_row_layout_id): array|stdClass
    {

        $datos_rec = (new _datos())->datos_recibo($link, $fc_row_layout_id);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al obtener datos de recibo', data: $datos_rec);
        }

        if($datos_rec->fc_row_layout->fc_row_layout_esta_cancelado !== 'activo'){
            return (new errores())->error(mensaje: 'Error no se puede retimbrar un recibo que no se a cancelado', data: $datos_rec);
        }

        $datos_cfdi = (new _datos())->datos_response_timbre($link, $datos_rec->fc_row_layout);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al obtener datos de datos_cfdi', data: $datos_cfdi);
        }

        $response = (new _cnx_pac())->operacion_timbrarJSON2($datos_cfdi->jsonB64, $datos_cfdi->keyPEM,
            $datos_cfdi->cerPEM, $datos_cfdi->plantilla);
        $response = json_decode($response, false);
        $codigo = trim($response->codigo);

        $code_error = (new _salida()) ->code_error($codigo, $response, $datos_cfdi->nomina_json,$link,$fc_row_layout_id);
        if(errores::$error){
            return (new errores())->error("Error al timbrar", $code_error);
        }

        $rs_exito = (new _finalizacion())->cfdi_exito($response, $datos_rec->fc_row_layout,$link);
        if(errores::$error) {
            return (new errores())->error("Error al actualizar UUID", $rs_exito);
        }

        $rs_esta_cancelado = (new _finalizacion())->upd_esta_cancelado_fc_row_layout(link: $link,fc_row_layout_id:  $fc_row_layout_id);
        if(errores::$error) {
            return (new errores())->error("Error al actualizar esta_cancelado", $rs_exito);
        }

        $result_regenera_nomina_pdf = (new _finalizacion())->regenera_nomina_pdf(
            fc_row_layout_id: $fc_row_layout_id,
            link:  $link
        );
        if(errores::$error) {
            return (new errores())->error(mensaje: 'Error al regenera_rec_pdf', data: $result_regenera_nomina_pdf);
        }

        $out = new stdClass();
        $out->datos_rec = $datos_rec;
        $out->datos_cfdi = $datos_cfdi;
        $out->response = $response;
        $out->rs_exito = $rs_exito;
        $out->rs_esta_cancelado = $rs_esta_cancelado;
        return $out;

    }


}
