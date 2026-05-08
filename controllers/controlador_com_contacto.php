<?php
/**
 * @author Martin Gamboa Vazquez
 * @version 1.0.0
 * @created 2022-05-14
 * @final En proceso
 *
 */
namespace gamboamartin\facturacion\controllers;

use gamboamartin\comercial\models\com_tipo_contacto;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\_email_validacion;
use gamboamartin\facturacion\models\com_contacto;
use gamboamartin\facturacion\models\fc_empleado_contacto;
use gamboamartin\template\html;
use PDO;
use stdClass;

class controlador_com_contacto extends \gamboamartin\comercial\controllers\controlador_com_contacto {
    public const string STATUS_NO_VALIDADO = 'no validado';
    public const string STATUS_LINK_ENVIADO = 'link enviado';
    public const string STATUS_VALIDADO = 'validado';

    public function valida_correo(bool $header, bool $ws = false)
    {

        $data = $this->modelo->obten_data();
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener data',
                data: $data, header: $header, ws: $ws);

        }

        $com_cliente_id = $data['com_cliente_id'];
        $registro_id = $data['com_contacto_id'];
        $correo = $data['com_contacto_correo'];

        $new_data = [
            'correo' => $data['com_contacto_correo'],
            'telefono' => $data['com_contacto_telefono'],
            'nombre' => $data['com_contacto_nombre'],
            'ap' => $data['com_contacto_ap'],
            'am' => $data['com_contacto_am'],
        ];

        if ($data['com_contacto_estatus_correo'] === self::STATUS_VALIDADO) {
            return $this->retorno_error(mensaje: 'El correo ya fue validado',
                data: $data, header: $header, ws: $ws);
        }

        $modelo_com_contacto = new com_contacto(link: $this->link);

        $url_validacion_cliente = $modelo_com_contacto->genera_link_validacion($new_data, $registro_id);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener url validacion cliente',
                data: $url_validacion_cliente, header: $header, ws: $ws);
        }

        $rs = $modelo_com_contacto->actualiza_estado_correo(
            registro_id: $registro_id,
            estado: self::STATUS_LINK_ENVIADO
        );
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error en actualiza_estado_correo',
                data: $rs, header: $header, ws: $ws);
        }

        $email = new _email_validacion();
        $resultado = $email->enviar_validacion(
            correo_destino: $correo,
            url_validacion: $url_validacion_cliente,
            link: $this->link
        );
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error en enviar_validacion',
                data: $resultado, header: $header, ws: $ws);
        }

        $_SESSION['exito'][]['mensaje'] = 'link de validacion enviado exitosamente';
        $link = "index.php?seccion=com_cliente&accion=asigna_contacto&adm_menu_id=41";
        $link .= "&session_id={$_GET['session_id']}&registro_id={$com_cliente_id}";
        header("Location: " . $link);
        exit;

    }

    public function valida_telefono(bool $header, bool $ws = false)
    {
        $registro_id = $this->registro_id;
        $new_modelo_contacto = new com_contacto(link: $this->link);

        $rs = $new_modelo_contacto->valida_telefono(registro_id: $registro_id);
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al validar telefono',
                data: $rs,
                header: $header,
                ws: $ws
            );
        }

        $r_n8n = $this->envia_validacion_telefono_n8n(data: $rs);
        if ($r_n8n['error']) {
            return $this->retorno_error(
                mensaje: 'Error al enviar link de validacion a n8n',
                data: $r_n8n,
                header: $header,
                ws: $ws
            );
        }

        $_SESSION['exito'][]['mensaje'] = 'link de validacion enviado exitosamente';

        $com_cliente_id = (int)($rs['com_cliente_id'] ?? $rs['com_contacto_com_cliente_id'] ?? 0);

        if ($com_cliente_id <= 0) {
            $filtro = array();
            $filtro['com_contacto.id'] = $registro_id;

            $r_contacto = $new_modelo_contacto->filtro_and(filtro: $filtro);
            if (errores::$error) {
                return $this->retorno_error(
                    mensaje: 'Error al obtener contacto para redireccionar',
                    data: $r_contacto,
                    header: $header,
                    ws: $ws
                );
            }

            if ((int)$r_contacto->n_registros <= 0) {
                return $this->retorno_error(
                    mensaje: 'Error no existe contacto para redireccionar',
                    data: $registro_id,
                    header: $header,
                    ws: $ws
                );
            }

            $contacto = $r_contacto->registros[0];
            $com_cliente_id = (int)($contacto['com_contacto_com_cliente_id'] ?? $contacto['com_cliente_id'] ?? 0);
        }

        if ($com_cliente_id <= 0) {
            return $this->retorno_error(
                mensaje: 'Error com_cliente_id no encontrado para redireccionar',
                data: $rs,
                header: $header,
                ws: $ws
            );
        }

        $link = "index.php?seccion=com_cliente&accion=asigna_contacto";
        $link .= "&registro_id=" . $com_cliente_id;

        if (isset($_GET['adm_menu_id'])) {
            $link .= "&adm_menu_id=" . $_GET['adm_menu_id'];
        }

        if (isset($_GET['session_id'])) {
            $link .= "&session_id=" . $_GET['session_id'];
        }

        header("Location: " . $link);
        exit;
    }

    private function envia_validacion_telefono_n8n(array $data): array
    {
        $n8n = new _n8n_request();

        $rs = $n8n->request_validacion_telefono_contacto(
            com_contacto_id: (int)($data['com_contacto_id'] ?? 0),
            com_cliente_id: (int)($data['com_cliente_id'] ?? 0),
            nombre: (string)($data['nombre'] ?? ''),
            url_validacion: (string)($data['url_validacion'] ?? ($data['url'] ?? '')),
            codigo_pais: (string)($data['codigo_pais'] ?? '52'),
            telefono: (string)($data['telefono'] ?? ''),
            telefono_original: (string)($data['telefono_original'] ?? ''),
            estatus_telefono: (string)($data['estatus_telefono'] ?? '')
        );

        if (errores::$error) {
            return [
                'error' => true,
                'mensaje' => 'Error al enviar link de validacion a n8n',
                'data' => $rs
            ];
        }

        $http_code = (int)($rs['status'] ?? 0);
        $curl_error = (string)($rs['error'] ?? '');
        $response = $rs['response'] ?? '';

        if ($curl_error !== '') {
            return [
                'error' => true,
                'mensaje' => 'Error CURL al conectar con n8n',
                'data' => $curl_error
            ];
        }

        if ($http_code < 200 || $http_code >= 300) {
            return [
                'error' => true,
                'mensaje' => 'n8n respondio con error',
                'http_code' => $http_code,
                'response' => $response
            ];
        }

        return [
            'error' => false,
            'http_code' => $http_code,
            'response' => $response
        ];
    }
}
