<?php
namespace gamboamartin\facturacion\models\_timbra_nomina;

use gamboamartin\errores\errores;
use gamboamartin\facturacion\controllers\_make_json;
use gamboamartin\facturacion\models\fc_layout_nom;
use gamboamartin\facturacion\models\fc_row_layout;
use PDO;
use stdClass;

class _datos{

    final public function datos_recibo(PDO $link, int $fc_row_layout_id): array|stdClass
    {
        // 1) Guard clauses: validar parámetros
        if ($fc_row_layout_id <= 0) {
            return (new errores())->error(
                mensaje: 'Parámetro $fc_row_layout_id inválido: debe ser entero positivo (>=1)',
                data: ['fc_row_layout_id' => $fc_row_layout_id]
            );
        }

        // 2) Obtener fc_row_layout
        $rowModel = new fc_row_layout($link);
        $fc_row_layout = $rowModel->registro($fc_row_layout_id, retorno_obj: true);
        if (errores::$error) {
            return (new errores())->error(
                mensaje: 'Error al obtener fc_row_layout',
                data: ['fc_row_layout_id' => $fc_row_layout_id, 'detalle' => $fc_row_layout]
            );
        }
        if (!($fc_row_layout instanceof stdClass)) {
            return (new errores())->error(
                mensaje: 'Respuesta inesperada al obtener fc_row_layout',
                data: ['tipo' => get_debug_type($fc_row_layout)]
            );
        }

        // 3) Validar fc_layout_nom_id dentro del fc_row_layout
        $layoutNomId = $fc_row_layout->fc_layout_nom_id ?? null;
        if (!is_numeric($layoutNomId) || (int)$layoutNomId <= 0) {
            return (new errores())->error(
                mensaje: 'fc_layout_nom_id ausente o inválido en fc_row_layout',
                data: ['fc_row_layout' => $fc_row_layout]
            );
        }
        $layoutNomId = (int)$layoutNomId;

        // 4) Obtener fc_layout_nom
        $layoutModel = new fc_layout_nom($link);
        $fc_layout_nom = $layoutModel->registro($layoutNomId, retorno_obj: true);
        if (errores::$error) {
            return (new errores())->error(
                mensaje: 'Error al obtener fc_layout_nom',
                data: ['fc_layout_nom_id' => $layoutNomId, 'detalle' => $fc_layout_nom]
            );
        }
        if (!($fc_layout_nom instanceof stdClass)) {
            return (new errores())->error(
                mensaje: 'Respuesta inesperada al obtener fc_layout_nom',
                data: ['tipo' => get_debug_type($fc_layout_nom)]
            );
        }

        // 5) Componer respuesta
        $datos = new stdClass();
        $datos->fc_layout_nom  = $fc_layout_nom;
        $datos->fc_row_layout  = $fc_row_layout;

        return $datos;
    }

    /**
     * OUT
     * @param PDO $link
     * @param stdClass $fc_row_layout
     * @return array|stdClass
     */
    final public function datos_response_timbre(PDO $link, stdClass $fc_row_layout): array|stdClass
    {
        $data_json = $this->jsonb64($link,$fc_row_layout);
        if (errores::$error) {
            return (new errores())->error(mensaje: 'Error al obtener json', data: $data_json);
        }

        $datos_csd = (new _certificado())->datos_csd($link);
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

    /**
     * OUT
     * @param PDO $link
     * @param stdClass $fc_row_layout
     * @return array|stdClass
     */
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



}
