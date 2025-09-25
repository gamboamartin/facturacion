<?php

namespace gamboamartin\facturacion\models\_cancela_nomina;

use config\pac;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\_timbra_nomina\_certificado;
use gamboamartin\facturacion\models\_timbra_nomina\_datos;
use gamboamartin\facturacion\models\fc_csd;
use gamboamartin\facturacion\pac\_cnx_pac;
use PDO;
use stdClass;

class _cancela_nomina
{

    /**
     * out
     * @param PDO $link
     * @param int $fc_row_layout_id
     * @return array|stdClass
     */
    final public function cancela_recibo(PDO $link, int $fc_row_layout_id): array|stdClass
    {

        $datos_rec = (new _datos())->datos_recibo($link, $fc_row_layout_id);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al obtener datos de recibo', data: $datos_rec);
        }

        $datos_cfdi = $this->datos_response_cancelacion($link, $datos_rec->fc_row_layout);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al obtener datos de datos_cfdi', data: $datos_cfdi);
        }

//        $response = (new _cnx_pac())->operacion_timbrarJSON2($datos_cfdi->jsonB64, $datos_cfdi->keyPEM,
//            $datos_cfdi->cerPEM, $datos_cfdi->plantilla);
//        $response = json_decode($response, false);



        $out = new stdClass();
        return $out;

    }

    private function datos_response_cancelacion(PDO $link, stdClass $fc_row_layout): array|stdClass
    {

        $datos_csd = (new _certificado())->datos_csd($link);
        if (errores::$error) {
            return (new errores())->error(mensaje: 'Error al obtener datos_csd', data: $datos_csd);
        }

        $emisor_rfc = $this->obtener_rfc_emisor(link: $link, fc_csd_id: pac::$fc_csd_nomina_id);
        if (errores::$error) {
            return (new errores())->error(mensaje: 'Error al obtener RFC del emisor', data: $emisor_rfc);
        }

        $datos = new stdClass();
        $datos->keyPEM = $datos_csd->keyPEM;
        $datos->cerPEM = $datos_csd->cerPEM;
        $datos->uuid = $fc_row_layout->fc_row_layout_uuid;
        $datos->receptorRFC = $fc_row_layout->fc_row_layout_rfc;
        $datos->emisorRFC = $emisor_rfc;
        $datos->total = $fc_row_layout->fc_row_layout_neto_depositar;

        return $datos;

    }

    private function obtener_rfc_emisor(PDO $link, int $fc_csd_id): array|string
    {
        $fc_csd_modelo = new fc_csd($link);
        $fc_csd_modelo->registro_id = $fc_csd_id;

        $result = $fc_csd_modelo->obten_data();
        if(errores::$error){
            return (new errores())->error('Error en obten_data de fc_csd', $result);
        }

        $pac = new pac();

        if(isset($pac->en_produccion) && !$pac->en_produccion){
            return "EKU9003173C9";
        }

        return (string)$result['org_empresa_rfc'];
    }
//$fc_row_layout_id = $_GET['fc_row_layout_id'];
//$r = (new _cancela_nomina())->cancela_recibo(link: $this->link, fc_row_layout_id: $fc_row_layout_id);
//echo '<pre>';
//print_r($r);
//echo '</pre>';exit;

}