<?php
/**
 * @author Martin Gamboa Vazquez
 * @version 1.0.0
 * @created 2022-05-14
 * @final En proceso
 *
 */
namespace gamboamartin\facturacion\controllers;
use stdClass;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\adm_usuario;

class controlador_adm_usuario extends \gamboamartin\acl\controllers\controlador_adm_usuario
{
    public function modifica_telefono(bool $header, bool $ws = false): array|stdClass
    {
        $rs = $this->base_modifica_custom();
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al procesar usuario',
                data: $rs,
                header: $header,
                ws: $ws
            );
        }

        $telefono = $this->registro['adm_usuario_telefono'] ?? '';

        $registro_stdClass = new stdClass();
        $registro_stdClass->telefono = $telefono;

        $this->inputs = new stdClass();

        $input_telefono = $this->html->input_text_required(
            cols: 6,
            disabled: false,
            name: 'telefono',
            place_holder: 'Telefono',
            row_upd: $registro_stdClass,
            value_vacio: '',
            title: 'Telefono'
        );

        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al generar input telefono',
                data: $input_telefono,
                header: $header,
                ws: $ws
            );
        }

        $this->inputs->telefono = $input_telefono;

        return [];
    }

    private function base_modifica_custom()
    {
        $adm_usuario_id = $this->registro_id;

        $adm_usuario_modelo = new adm_usuario($this->link);
        $adm_usuario_modelo->registro_id = $adm_usuario_id;

        $data = $adm_usuario_modelo->obten_data();
        if (errores::$error) {
            return $this->errores->error(
                mensaje: 'Error al obtener info de adm_usuario',
                data: $data
            );
        }

        $this->registro = $data;

        return $data;
    }

        public function modifica_telefono_bd(bool $header, bool $ws = false)
        {
            $adm_usuario_id = (int)($_POST['adm_usuario_id'] ?? 0);
            $telefono = (string)($_POST['telefono'] ?? '');
            $codigo_pais = (string)($_POST['codigo_pais'] ?? '');

            if ($adm_usuario_id <= 0) {
                return $this->retorno_error(
                    mensaje: 'Error adm_usuario_id es requerido',
                    data: $_POST,
                    header: $header,
                    ws: $ws
                );
            }

            if ($telefono === '') {
                return $this->retorno_error(
                    mensaje: 'Error telefono es requerido',
                    data: $_POST,
                    header: $header,
                    ws: $ws
                );
            }

            if ($codigo_pais === '') {
                return $this->retorno_error(
                    mensaje: 'Error codigo_pais es requerido',
                    data: $_POST,
                    header: $header,
                    ws: $ws
                );
            }

            $modelo_usuario = new adm_usuario(link: $this->link);

            $rs = $modelo_usuario->modifica_telefono(
                adm_usuario_id: $adm_usuario_id,
                telefono: $telefono,
                codigo_pais: $codigo_pais
            );

            if (errores::$error) {
                return $this->retorno_error(
                    mensaje: 'Error al modificar telefono de usuario',
                    data: $rs,
                    header: $header,
                    ws: $ws
                );
            }

            $_SESSION['exito'][]['mensaje'] = 'Teléfono modificado correctamente';

            $link = "index.php?seccion=adm_usuario&accion=modifica";
            $link .= "&registro_id=" . $adm_usuario_id;

            if (isset($_GET['adm_menu_id'])) {
                $link .= "&adm_menu_id=" . $_GET['adm_menu_id'];
            }

            if (isset($_GET['session_id'])) {
                $link .= "&session_id=" . $_GET['session_id'];
            }

            header("Location: " . $link);
            exit;
        }

        public function valida_telefono(bool $header, bool $ws = false)
        {
            $registro_id = $this->registro_id;

            $modelo_usuario = new adm_usuario(link: $this->link);

            $rs = $modelo_usuario->valida_telefono(registro_id: $registro_id);
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

            $_SESSION['exito'][]['mensaje'] = 'Link de validacion enviado exitosamente';

            $link = "index.php?seccion=adm_usuario&accion=lista";

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

            $rs = $n8n->request_validacion_telefono_adm_usuario(
                adm_usuario_id: (int)($data['adm_usuario_id'] ?? 0),
                nombre: (string)($data['nombre'] ?? ''),
                url_validacion: (string)($data['url_validacion'] ?? ($data['url'] ?? '')),
                codigo_pais: (string)($data['codigo_pais'] ?? '52'),
                telefono: (string)($data['telefono'] ?? ''),
                telefono_original: (string)($data['telefono_original'] ?? ''),
                estatus_telefono: (string)($data['estatus_telefono'] ?? ''),
                adm_grupo_id: (int)($data['adm_grupo_id'] ?? 0),
                grupo_descripcion: (string)($data['grupo_descripcion'] ?? ''),
                grupo_codigo: (string)($data['grupo_codigo'] ?? ''),
                grupo_alias: (string)($data['grupo_alias'] ?? '')
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

        private function retorna_json_admin_telefono(array $data): void
        {
            header('Content-Type: application/json; charset=utf-8');

            echo json_encode(
                $data,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );

            exit;
        }
}