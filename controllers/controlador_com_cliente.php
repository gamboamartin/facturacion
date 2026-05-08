<?php
/**
 * @author Martin Gamboa Vazquez
 * @version 1.0.0
 * @created 2022-05-14
 * @final En proceso
 *
 */
namespace gamboamartin\facturacion\controllers;

use config\generales;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\com_agente;
use gamboamartin\facturacion\models\com_cliente;
use gamboamartin\facturacion\models\com_contacto;
use gamboamartin\facturacion\models\fc_layout_periodo;
use gamboamartin\template\html;
use PDO;
use stdClass;

class controlador_com_cliente extends \gamboamartin\comercial\controllers\controlador_com_cliente {
    public string $link_asigna_contacto_bd = '';
    public string $link_modifica_telefono_contacto_bd = '';
    public string $link_modifica_porcentaje_comision_bd = '';
    public string $link_modifica_asesor_bd = '';
    public string $descripcion_cliente = '';

    public bool $modo_modifica_telefono = false;
    public int $com_contacto_id_modifica = 0;
    public string $codigo_pais_contacto = '52';
    public string $nombre_contacto_modifica = '';

    public function __construct(
        PDO $link,
        html $html = new \gamboamartin\template_1\html(),
        stdClass $paths_conf = new stdClass()
    ) {
        parent::__construct(link: $link, html: $html, paths_conf: $paths_conf);

        $this->modelo = new com_cliente(link: $this->link);

        /*
        * Este link se deja EXACTAMENTE con la lógica actual.
        * Sirve para dar de alta contactos desde la vista asigna_contacto.
        */
        $link_asigna_contacto_bd = $this->obj_link->link_alta_bd(
            link: $this->link,
            seccion: 'com_contacto'
        );
        if (errores::$error) {
            $error = $this->errores->error(
                mensaje: 'Error al obtener link',
                data: $link_asigna_contacto_bd
            );
            print_r($error);
            exit;
        }

        $this->link_asigna_contacto_bd = $link_asigna_contacto_bd;

        /*
        * Nuevo link para modificar teléfono SIN entrar por com_contacto como módulo.
        * Entra por com_cliente para evitar problema de menú/permisos.
        */
        $this->link_modifica_telefono_contacto_bd = "index.php?seccion=com_cliente";
        $this->link_modifica_telefono_contacto_bd .= "&accion=modifica_telefono_contacto_bd";

        if (isset($_GET['session_id'])) {
            $this->link_modifica_telefono_contacto_bd .= "&session_id=" . $_GET['session_id'];
        }

        if (isset($_GET['adm_menu_id'])) {
            $this->link_modifica_telefono_contacto_bd .= "&adm_menu_id=" . $_GET['adm_menu_id'];
        }
    }

    public function asigna_contacto(bool $header, bool $ws = false, array $not_actions = array()): array|string
    {
        (new com_contacto($this->link))->valida_tiempo_tokens();

        $this->accion_titulo = 'Asignar contacto';

        /*
        * Defaults para que el alta normal siga funcionando igual.
        */
        $this->modo_modifica_telefono = false;
        $this->com_contacto_id_modifica = 0;
        $this->codigo_pais_contacto = '52';
        $this->nombre_contacto_modifica = '';

        $r_modifica = $this->init_modifica();
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al generar salida de template',
                data: $r_modifica,
                header: $header,
                ws: $ws
            );
        }

        $keys_selects = $this->init_selects_inputs();
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al inicializar selects',
                data: $keys_selects,
                header: $header,
                ws: $ws
            );
        }

        $this->row_upd->telefono = '';

        /*
        * MODO MODIFICAR TELÉFONO
        * Se activa solo si la URL trae com_contacto_id.
        *
        * Ejemplo:
        * index.php?seccion=com_cliente&accion=asigna_contacto&registro_id=213&com_contacto_id=999
        */
        $com_contacto_id = (int)($_GET['com_contacto_id'] ?? 0);

        if ($com_contacto_id > 0) {

            $this->modo_modifica_telefono = true;
            $this->com_contacto_id_modifica = $com_contacto_id;
            $this->accion_titulo = 'Modificar teléfono de contacto';

            $filtro = array();
            $filtro['com_contacto.id'] = $com_contacto_id;

            $r_contacto = (new com_contacto($this->link))->filtro_and(filtro: $filtro);
            if (errores::$error) {
                return $this->retorno_error(
                    mensaje: 'Error al obtener contacto',
                    data: $r_contacto,
                    header: $header,
                    ws: $ws
                );
            }

            if ((int)$r_contacto->n_registros <= 0) {
                return $this->retorno_error(
                    mensaje: 'Error no existe contacto',
                    data: $com_contacto_id,
                    header: $header,
                    ws: $ws
                );
            }

            $contacto = $r_contacto->registros[0];

            $this->row_upd->telefono = $contacto['com_contacto_telefono'] ?? '';
            $this->codigo_pais_contacto = $contacto['com_contacto_codigo_pais'] ?? '52';
            $this->nombre_contacto_modifica = $contacto['com_contacto_descripcion'] ?? '';
        }

        $base = $this->base_upd(
            keys_selects: $keys_selects,
            params: array(),
            params_ajustados: array()
        );
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al integrar base',
                data: $base,
                header: $header,
                ws: $ws
            );
        }

        $button = $this->html->button_href(
            accion: 'modifica',
            etiqueta: 'Ir a Cliente',
            registro_id: $this->registro_id,
            seccion: $this->tabla,
            style: 'warning',
            params: array()
        );
        if (errores::$error) {
            return $this->errores->error(
                mensaje: 'Error al generar link',
                data: $button
            );
        }

        $this->button_com_cliente_modifica = $button;

        $data_view = new stdClass();
        $data_view->names = array(
            'Id',
            'Tipo',
            'Contacto',
            'Teléfono',
            'Correo',
            'Validacion Correo',
            'Acciones'
        );

        $data_view->keys_data = array(
            'com_contacto_id',
            'com_tipo_contacto_descripcion',
            'com_contacto_descripcion',
            'com_contacto_telefono',
            'com_contacto_correo',
            'com_contacto_estatus_correo'
        );

        $data_view->key_actions = 'acciones';
        $data_view->namespace_model = 'gamboamartin\\comercial\\models';
        $data_view->name_model_children = 'com_contacto';

        $contenido_table = $this->contenido_children(
            data_view: $data_view,
            next_accion: __FUNCTION__,
            not_actions: $not_actions
        );
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al obtener tbody',
                data: $contenido_table,
                header: $header,
                ws: $ws
            );
        }

        return $contenido_table;
    }

    public function modifica_asesor(bool $header, bool $ws = false): array|stdClass
    {
        $registro_id = $this->registro_id;
        $this->modelo->registro_id = $registro_id;
        $data = $this->modelo->obten_data();

        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al obtener data de com_cliente', data: $data, header: $header, ws: $ws);
        }

        $this->descripcion_cliente = $data['com_cliente_razon_social'];

        $com_agente_asesor_id = $data['com_cliente_com_agente_asesor_id'];


        $link = "index.php?seccion=com_cliente&accion=modifica_asesor_bd&registro_id={$this->registro_id}&session_id={$_GET['session_id']}";
        $this->link_modifica_asesor_bd = $link;

        $this->inputs = new stdClass();

        $modelo_com_agente = new com_agente(link: $this->link);

        $com_tipo_agente_id = -1;

        if (isset(generales::$tipo_agente_asesor)){
            $com_tipo_agente_id = generales::$tipo_agente_asesor;
        }

        $filtro_select_agente_asesor = ['com_agente.com_tipo_agente_id' => $com_tipo_agente_id];

        $input_select_agente_asesor = $this->html->select_catalogo(cols: 12, con_registros: true, id_selected: $com_agente_asesor_id,
            modelo: $modelo_com_agente, columns_ds: ['com_agente_descripcion_select'],
            disabled: false, filtro: $filtro_select_agente_asesor, label: 'Asesor',name: 'com_agente_asesor_id',
            registros: [], required: true
        );
        if(errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al generar input_select_agente_asesor',
                data: $input_select_agente_asesor,
                header: $header, ws: $ws
            );
        }

        $this->inputs->input_select_agente_asesor = $input_select_agente_asesor;

        return [];

    }

    public function modifica_asesor_bd(bool $header, bool $ws = false): array|stdClass
    {

        $com_cliente_id = $_POST['com_cliente_id'];
        $com_agente_asesor_id = $_POST['com_agente_asesor_id'];

        $rs = $this->modelo->modifica_bd(
            registro: ['com_agente_asesor_id' => $com_agente_asesor_id],
            id: $com_cliente_id
        );

        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al modificar el asesor en com_cliente', data: $rs, header: $header, ws: $ws);
        }

        $_SESSION['exito'][]['mensaje'] = 'asesor modificado exitosamente';
        $link = "index.php?seccion=com_cliente&accion=lista&adm_menu_id=41";
        $link .= "&session_id={$_GET['session_id']}";
        header("Location: " . $link);
        exit;
    }

    public function modifica_porcentaje_comision(bool $header, bool $ws = false): array|string
    {

        $this->accion_titulo = 'Modificar porcentaje comision';
        $this->inputs = new stdClass();

        $com_cliente_modelo = new com_cliente($this->link);
        $com_cliente_modelo->registro_id = $this->registro_id;
        $data_cliente = $com_cliente_modelo->obten_data();
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al obtener $data_cliente',
                data: $data_cliente, header: $header, ws: $ws
            );
        }

        $com_cliente_porcentaje_comision = $data_cliente['com_cliente_porcentaje_comision'];

        $porcentaje_comision = $this->html->input_text(
            cols: 12,
            disabled: false,
            name: 'porcentaje_comision',
            place_holder: 'Porcentaje Comision',
            row_upd: new stdClass(),
            value_vacio: false,
            value: $com_cliente_porcentaje_comision,
        );
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al obtener inputs', data: $porcentaje_comision, header: $header, ws: $ws);
        }

        $this->inputs->porcentaje_comision = $porcentaje_comision;

        $link = "index.php?seccion=com_cliente&accion=modifica_porcentaje_comision_bd&registro_id={$this->registro_id}&session_id={$_GET['session_id']}";
        $this->link_modifica_porcentaje_comision_bd = $link;
        return [];

    }

    public function modifica_porcentaje_comision_bd(bool $header, bool $ws = false): array|string
    {

        $porcentaje_comision = $_POST ['porcentaje_comision'];
        $com_cliente_id = $_POST ['com_cliente_id'];

        $rs = (new com_cliente($this->link))->modifica_bd(
            registro: ['porcentaje_comision' => $porcentaje_comision],
            id: $com_cliente_id,
        );
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al modificar el porcentaje comision',
                data: $rs, header: $header, ws: $ws
            );
        }

        $_SESSION['exito'][]['mensaje'] = "Se modifico el porcentaje de comision del com_cliente con ID {$com_cliente_id}";
        $link = "index.php?seccion=com_cliente&accion=lista&adm_menu_id=41";
        $link .= "&session_id={$_GET['session_id']}";
        header("Location: " . $link);
        exit;

    }

    protected function init_datatable(): stdClass
    {
        // Definición de las columnas con sus respectivos títulos
        $columns["com_cliente_id"]["titulo"] = "Id";
        $columns["com_cliente_codigo"]["titulo"] = "Código";
        $columns["com_cliente_razon_social"]["titulo"] = "Razón Social";
        $columns["com_cliente_rfc"]["titulo"] = "RFC";
        $columns["cat_sat_regimen_fiscal_descripcion"]["titulo"] = "Régimen Fiscal";
        $columns["com_cliente_n_sucursales"]["titulo"] = "Sucursales";
        $columns["com_cliente_porcentaje_comision"]["titulo"] = "% Comision";

        // Filtros aplicables en la búsqueda del DataTable
        $filtro = array(
            "com_cliente.id",
            "com_cliente.codigo",
            "com_cliente.razon_social",
            "com_cliente.rfc",
            "cat_sat_regimen_fiscal.descripcion"
        );

        // Creación del objeto de configuración del DataTable
        $datatables = new stdClass();
        $datatables->columns = $columns; // Asignación de las columnas
        $datatables->filtro = $filtro; // Asignación de los filtros
        $datatables->menu_active = true; // Activación del menú

        return $datatables;
    }

    public function modifica_telefono_contacto_bd(bool $header, bool $ws = false): array|stdClass
    {
        $codigo_pais = trim($_POST['codigo_pais'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $com_contacto_id = (int)($_POST['com_contacto_id'] ?? 0);
        $com_cliente_id = (int)($_POST['com_cliente_id'] ?? 0);

        if ($com_contacto_id <= 0) {
            return $this->retorno_error(
                mensaje: 'Error com_contacto_id es requerido',
                data: $_POST,
                header: $header,
                ws: $ws
            );
        }

        if ($com_cliente_id <= 0) {
            return $this->retorno_error(
                mensaje: 'Error com_cliente_id es requerido',
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

        /*
        * Validamos que el contacto pertenezca al cliente.
        * Esto evita modificar un contacto ajeno desde otra URL.
        */
        $filtro = array();
        $filtro['com_contacto.id'] = $com_contacto_id;
        $filtro['com_contacto.com_cliente_id'] = $com_cliente_id;

        $r_contacto = (new com_contacto($this->link))->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al validar contacto de cliente',
                data: $r_contacto,
                header: $header,
                ws: $ws
            );
        }

        if ((int)$r_contacto->n_registros <= 0) {
            return $this->retorno_error(
                mensaje: 'Error el contacto no pertenece al cliente',
                data: $_POST,
                header: $header,
                ws: $ws
            );
        }

        /*
        * Aquí usamos la función del modelo com_contacto que ya habíamos preparado.
        */
        $rs = (new com_contacto($this->link))->modifica_telefono(
            com_contacto_id: $com_contacto_id,
            telefono: $telefono,
            codigo_pais: $codigo_pais
        );
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al modificar telefono',
                data: $rs,
                header: $header,
                ws: $ws
            );
        }

        $_SESSION['exito'][]['mensaje'] = 'Telefono modificado correctamente';

        $link = "index.php?seccion=com_cliente";
        $link .= "&accion=asigna_contacto";
        $link .= "&registro_id={$com_cliente_id}";

        if (isset($_GET['session_id'])) {
            $link .= "&session_id=" . $_GET['session_id'];
        }

        if (isset($_GET['adm_menu_id'])) {
            $link .= "&adm_menu_id=" . $_GET['adm_menu_id'];
        }

        header("Location: " . $link);
        exit;
    }
}