<?php

namespace gamboamartin\facturacion\models\_cancela_nomina;

use config\generales;
use config\pac;
use gamboamartin\documento\models\doc_documento;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\_timbra_nomina\_certificado;
use gamboamartin\facturacion\models\_timbra_nomina\_datos;
use gamboamartin\facturacion\models\_timbra_nomina\_finalizacion;
use gamboamartin\facturacion\models\fc_cancelacion_recibo;
use gamboamartin\facturacion\models\fc_cer_csd;
use gamboamartin\facturacion\models\fc_cer_pem;
use gamboamartin\facturacion\models\fc_csd;
use gamboamartin\facturacion\models\fc_key_csd;
use gamboamartin\facturacion\models\fc_row_layout;
use gamboamartin\facturacion\models\fc_row_nomina;
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

        if($datos_rec->fc_row_layout->fc_row_layout_esta_timbrado === 'inactivo'){
            return (new errores())->error(mensaje: 'Error no se puede cancelar un registro que no esta timbrado', data: $datos_rec);
        }

        $datos_cfdi = $this->datos_response_cancelacion($link, $datos_rec->fc_row_layout);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al obtener datos de datos_cfdi', data: $datos_cfdi);
        }

        $response = (new _cnx_pac())->operacion_cancelar2(
            apikey: (new pac)->usuario_integrador,
            keyCSD: $datos_cfdi->key,
            cerCSD: $datos_cfdi->cer,
            passCSD: $datos_cfdi->csd_password,
            uuid: $datos_cfdi->uuid,
            rfcEmisor: $datos_cfdi->emisorRFC,
            rfcReceptor: $datos_cfdi->receptorRFC,
            total: $datos_cfdi->total,
            motivo: 'cancelacion desde el sistema',
            folioSustitucion: '',
        );

        $response = json_decode($response, false);
        $resultado = $response->resultado;
        $codigo = (int)$response->codigo;
        $acuse = $response->acuse;

        if ($resultado !== 'success') {
            return (new errores())->error(mensaje: $response->mensaje, data: $response);
        }

        $codigos = [311];
        if (in_array($codigo, $codigos)) {
            $subir_acuse_result = $this->subir_xml_acuse_cancelacion(string_xml: $acuse, fc_row_layout_id: $fc_row_layout_id, link: $link);
            if(errores::$error){
                return (new errores())->error(mensaje: 'Error en subir_xml_acuse_cancelacion', data: $subir_acuse_result);
            }
        }

        $result_layout = $this->upd_fc_row_layout(link: $link, fc_row_layout_id: $fc_row_layout_id);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error en upd_fc_row_layout', data: $result_layout);
        }

        $result_nomina = $this->upd_fc_row_nomina(link: $link, fc_row_layout_id: $fc_row_layout_id);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error en upd_fc_row_nomina', data: $result_nomina);
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
        $out->codigo = $codigo;
        $out->acuse = $acuse;
        $out->resultado = $resultado;
        return $out;

    }

    private function upd_fc_row_layout(PDO $link, int $fc_row_layout_id): array
    {

        $upd_row['esta_cancelado'] = 'activo';

        $fc_row_layout_modelo = new fc_row_layout(link: $link);
        $upd_result = $fc_row_layout_modelo->modifica_bd($upd_row, $fc_row_layout_id);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error en upd fc_row_layout', data: $upd_result);
        }
        return [];
    }

    private function upd_fc_row_nomina(PDO $link, int $fc_row_layout_id)
    {
        $fc_row_nomina_modelo = new fc_row_nomina(link: $link);

        $filtro = [
            'fc_row_nomina.fc_row_layout_id' => $fc_row_layout_id,
            'fc_row_nomina.status' => 'activo',
        ];

        $result = $fc_row_nomina_modelo->filtro_and(columnas: ['fc_row_nomina_id'], filtro: $filtro);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error en filtro_and de $fc_row_nomina_modelo', data: $result);
        }

        $registros = $result->registros;

        $upd_row['status'] = 'inactivo';
        $response = [];
        foreach ($registros as $registro) {
            $upd_result = $fc_row_nomina_modelo->modifica_bd($upd_row, $registro['fc_row_nomina_id']);
            if(errores::$error){
                return (new errores())->error(mensaje: 'Error en upd fc_row_nomina', data: $upd_result);
            }
            $response[] = $upd_result;
        }

        return $response;
    }

    private function datos_response_cancelacion(PDO $link, stdClass $fc_row_layout): array|stdClass
    {

        $datos_csd = $this->datos_csd(link: $link);
        if (errores::$error) {
            return (new errores())->error(mensaje: 'Error al obtener datos_csd', data: $datos_csd);
        }

        $emisor_rfc = $this->obtener_rfc_emisor(link: $link, fc_csd_id: pac::$fc_csd_nomina_id);
        if (errores::$error) {
            return (new errores())->error(mensaje: 'Error al obtener RFC del emisor', data: $emisor_rfc);
        }

        $datos = new stdClass();
        $datos->key = $datos_csd->key;
        $datos->cer = $datos_csd->cer;
        $datos->csd_password = $datos_csd->csd_password;
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

    private function datos_csd(PDO $link): array|stdClass
    {
        $ruta_cer = $this->get_cer_path(fc_csd_nomina_id: pac::$fc_csd_nomina_id, link: $link);
        if (errores::$error) {
            return (new errores())->error(mensaje: 'Error al obtener ruta_cer', data: $ruta_cer);
        }

        $ruta_key = $this->get_key_path(fc_csd_nomina_id: pac::$fc_csd_nomina_id, link: $link);
        if (errores::$error) {
            return (new errores())->error(mensaje: 'Error al obtener ruta_key', data: $ruta_cer);
        }

        $cer = base64_encode(file_get_contents($ruta_cer));
        $key = base64_encode(file_get_contents($ruta_key));

        $csd_password = $this->get_csd_password(fc_csd_nomina_id: pac::$fc_csd_nomina_id, link: $link);
        if (errores::$error) {
            return (new errores())->error(mensaje: 'Error al obtener ruta_key', data: $ruta_cer);
        }

        $datos = (new stdClass());
        $datos->key = $key;
        $datos->cer = $cer;
        $datos->csd_password = $csd_password;
        return $datos;

    }

    private function get_cer_path(int $fc_csd_nomina_id, PDO $link): string|array
    {

        $filtro = ['fc_csd_id' => $fc_csd_nomina_id];
        $fc_cer_csd_modelo = new fc_cer_csd($link);
        $fc_cer_csd_data = $fc_cer_csd_modelo->filtro_and(filtro: $filtro);
        if(errores::$error){
            return (new errores())->error('Error al obtener fc_cer_csd', $fc_cer_csd_data);
        }

        if ((int)$fc_cer_csd_data->n_registros < 1) {
            return (new errores())->error('Error no existe fc_cer_csd', $fc_cer_csd_data);
        }

        $registro = $fc_cer_csd_data->registros[0];

        $ruta_absoluta = $registro['doc_documento_ruta_absoluta'];

        $pac = new pac();

        if(isset($pac->en_produccion) && !$pac->en_produccion){
            $ruta_absoluta = "/var/www/html/facturacion/pac/CSD_EKU9003173C9.cer";
        }

        return $ruta_absoluta;

    }

    private function get_key_path(int $fc_csd_nomina_id, PDO $link): string|array
    {

        $filtro = ['fc_csd_id' => $fc_csd_nomina_id];
        $fc_key_csd_modelo = new fc_key_csd($link);
        $fc_key_csd_data = $fc_key_csd_modelo->filtro_and(filtro: $filtro);
        if(errores::$error){
            return (new errores())->error('Error al obtener fc_key_csd', $fc_key_csd_data);
        }

        if ((int)$fc_key_csd_data->n_registros < 1) {
            return (new errores())->error('Error no existe fc_key_csd', $fc_key_csd_data);
        }

        $registro = $fc_key_csd_data->registros[0];

        $ruta_absoluta = $registro['doc_documento_ruta_absoluta'];

        $pac = new pac();

        if(isset($pac->en_produccion) && !$pac->en_produccion){
            $ruta_absoluta = "/var/www/html/facturacion/pac/CSD_EKU9003173C9.key";
        }

        return $ruta_absoluta;

    }

    private function get_csd_password(int $fc_csd_nomina_id, PDO $link): string|array
    {
        $filtro = ['fc_csd.id' => $fc_csd_nomina_id];
        $fc_csd_modelo = new fc_csd($link);
        $fc_csd_data = $fc_csd_modelo->filtro_and(filtro: $filtro);
        if(errores::$error){
            return (new errores())->error('Error al obtener fc_csd', $fc_csd_data);
        }

        if ((int)$fc_csd_data->n_registros < 1) {
            return (new errores())->error('Error no existe fc_csd', $fc_csd_data);
        }

        $registro = $fc_csd_data->registros[0];
        $password = $registro['fc_csd_password'];

        if(isset($pac->en_produccion) && !$pac->en_produccion){
            $password = "12345678a";
        }

        return $password;

    }

    private function subir_xml_acuse_cancelacion(string $string_xml, int $fc_row_layout_id, PDO $link): array
    {
        $nombre_archivo = $fc_row_layout_id.'.xml';
        $ruta = (new generales())->path_base.'archivos/'.$nombre_archivo;

        $registro['doc_tipo_documento_id'] = 12;
        $file = array();
        $file['name'] = $nombre_archivo;
        $file['tmp_name'] = $ruta;
        file_put_contents($ruta, $string_xml);

        $alta = (new doc_documento(link: $link))->alta_documento(registro: $registro,file: $file);
        if(errores::$error){
            return (new errores())->error('Error al insertar', $alta);
        }

        unlink($ruta);
        $doc_documento_id = $alta->registro_id;

        $fc_cancela_recibo_registro = [
            'doc_documento_id' => $doc_documento_id,
            'fc_row_layout_id' => $fc_row_layout_id,
        ];

        $fc_cancelacion_recibo_modelo = new fc_cancelacion_recibo($link);
        $fc_cancelacion_recibo_modelo->registro = $fc_cancela_recibo_registro;
        $result = $fc_cancelacion_recibo_modelo->alta_bd();
        if(errores::$error){
            return (new errores())->error('Error al insertar fc_cancelacion_recibo', $result);
        }

        return [];
    }

}