<?php
namespace gamboamartin\facturacion\models;

use config\pac;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\controllers\_make_json;
use PDO;
use stdClass;

class _timbra_nomina
{

    private function datos_csd(PDO $link): array|stdClass
    {
        $rutas = $this->rutas_csd($link);
        if (errores::$error) {
            return (new errores())->error(mensaje: 'Error al obtener $rutas', data: $rutas);
        }

        $keyPEM = file_get_contents($rutas['ruta_key_pem']);
        $cerPEM = file_get_contents($rutas['ruta_cer_pem']);

        $datos = (new stdClass());
        $datos->keyPEM = $keyPEM;
        $datos->cerPEM = $cerPEM;
        return $datos;

    }
    final public function datos_recibo(PDO $link, int $fc_row_layout_id): array|stdClass
    {
        $fc_row_layout = (new fc_row_layout($link))->registro($fc_row_layout_id,retorno_obj: true);
        if (errores::$error) {
            return (new errores())->error(mensaje: 'Error al obtener layout', data: $fc_row_layout);
        }

        $fc_layout_nom = (new fc_layout_nom($link))->registro($fc_row_layout->fc_row_layout_id,retorno_obj: true);
        if (errores::$error) {
            return (new errores())->error(mensaje: 'Error al obtener layout', data: $fc_layout_nom);
        }


        $datos = new stdClass();
        $datos->fc_layout_nom = $fc_layout_nom;
        $datos->fc_row_layout = $fc_row_layout;
        return $datos;

    }

    private function datos_response_timbre(PDO $link, stdClass $fc_row_layout): array|stdClass
    {
        $data_json = $this->jsonb64($link,$fc_row_layout);
        if (errores::$error) {
            return (new errores())->error(mensaje: 'Error al obtener json', data: $data_json);
        }

        $datos_csd = $this->datos_csd($link);
        if (errores::$error) {
            return (new errores())->error(mensaje: 'Error al obtener datos_csd', data: $datos_csd);
        }
        $plantilla = 'nomina';

        $datos = new stdClass();
        $datos->nomina_json = $data_json->nomina_json;
        $datos->jsonB64 = $data_json->jsonB64;
        $datos->keyPEM = $datos_csd->keyPEM;
        $datos->cerPEM = $datos_csd->cerPEM;
        $datos->plantilla = $plantilla;
        return $datos;

    }

    private function get_cer_pem_path(int $fc_csd_nomina_id, PDO $link): string|array
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

        $fc_cer_pem_modelo = new fc_cer_pem($link);
        $cer_pem_data = $fc_cer_pem_modelo->filtro_and(
            filtro: ['fc_cer_csd_id' => $fc_cer_csd_data->registros[0]['fc_cer_csd_id']]);
        if(errores::$error){
            return (new errores())->error('Error al obtener fc_cer_pem', $cer_pem_data);
        }

        if ((int)$cer_pem_data->n_registros < 1) {
            return (new errores())->error('Error no existe fc_cer_pem', $cer_pem_data);
        }

        return $cer_pem_data->registros[0]['doc_documento_ruta_absoluta'];

    }

    private function get_key_pem_path(int $fc_csd_nomina_id, PDO $link): string|array
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

        $fc_key_pem_modelo = new fc_key_pem($link);
        $key_pem_data = $fc_key_pem_modelo->filtro_and(filtro: ['fc_key_csd_id' => $fc_key_csd_data->registros[0]['fc_key_csd_id']]);
        if(errores::$error){
            return (new errores())->error('Error al obtener fc_key_pem', $key_pem_data);
        }

        if ((int)$key_pem_data->n_registros < 1) {
            return (new errores())->error('Error no existe fc_key_pem', $key_pem_data);
        }

        return $key_pem_data->registros[0]['doc_documento_ruta_absoluta'];

    }

    private function jsonb64(PDO $link, stdClass $fc_row_layout): array|stdClass
    {
        $result = (new _make_json(link: $link,fc_row_layout:  $fc_row_layout))->getJson();
        if (errores::$error) {
            return (new errores())->error(mensaje: 'Error al obtener json', data: $result);
        }
        $nomina_json = $result['json'];
        $data = new stdClass();
        $data->nomina_json = $nomina_json;
        $data->jsonB64 = base64_encode( $nomina_json);
        return $data;

    }

    private function obtener_cer_key(int $fc_csd_nomina_id, PDO $link): array
    {
        $cer = $this->get_cer_pem_path($fc_csd_nomina_id, $link);
        if(errores::$error){
            return (new errores())->error('Error al obtener ruta del $cer', $cer);
        }

        $key = $this->get_key_pem_path($fc_csd_nomina_id, $link);
        if(errores::$error){
            return (new errores())->error('Error al obtener ruta del $key', $key);
        }

        return [
            'ruta_cer_pem' => $cer,
            'ruta_key_pem' => $key,
        ];
    }

    private function rutas_csd(PDO $link): array
    {
        $rutas = $this->obtener_cer_key(pac::$fc_csd_nomina_id, $link);
        if (errores::$error) {
            return (new errores())->error(mensaje: 'Error al obtener $rutas', data: $rutas);
        }

        $pac = new pac();

        if(isset($pac->en_produccion) && !$pac->en_produccion){
            $rutas['ruta_key_pem'] = "/var/www/html/facturacion/pac/CSD_EKU9003173C9_key.pem";
            $rutas['ruta_cer_pem'] = "/var/www/html/facturacion/pac/CSD_EKU9003173C9_cer.pem";
        }

        return $rutas;

    }

}
