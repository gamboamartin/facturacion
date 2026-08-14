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
use gamboamartin\facturacion\models\datos_adicionales_com_cliente_artistik;
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
    public string $foto_actual = ''; 

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
        if (property_exists(generales::class, 'datos_adicionales_com_cliente') && generales::$datos_adicionales_com_cliente) {
        $columns["com_cliente_curp"]["titulo"] = "CURP";
        $columns["com_cliente_telefono_emergencia"]["titulo"] = "Tel. Emergencia";
        $columns["com_cliente_horario"]["titulo"] = "Horario";
        } else {
            $columns["com_cliente_rfc"]["titulo"] = "RFC";
            $columns["cat_sat_regimen_fiscal_descripcion"]["titulo"] = "Régimen Fiscal";
            $columns["com_cliente_n_sucursales"]["titulo"] = "Sucursales";
            $columns["com_cliente_porcentaje_comision"]["titulo"] = "% Comision";
        }
      
        

         if (property_exists(generales::class, 'datos_adicionales_com_cliente') && generales::$datos_adicionales_com_cliente) {
            $columns["com_cliente_nombre_emergencia"]["titulo"] = "Contacto Emergencia";
            $columns["com_cliente_foto"]["titulo"] = "Foto";
        }

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

  public function alta(bool $header, bool $ws = false): array|string
{
    $r_alta = parent::alta(header: $header, ws: $ws);
    if (errores::$error) {
        return $this->retorno_error(
            mensaje: 'Error al inicializar alta', data: $r_alta, header: $header, ws: $ws
        );
    }

    if ((property_exists(generales::class, 'datos_adicionales_com_cliente') && generales::$datos_adicionales_com_cliente)) {

        $horario = $this->html->input_text(cols: 6, disabled: false, name: 'horario',
            place_holder: 'Horario', row_upd: new stdClass(), value_vacio: false);
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al obtener input', data: $horario, header: $header, ws: $ws);
        }

        $telefono_emergencia = $this->html->input_text(cols: 6, disabled: false, name: 'telefono_emergencia',
            place_holder: 'Teléfono Emergencia', row_upd: new stdClass(), value_vacio: false);
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al obtener input', data: $telefono_emergencia, header: $header, ws: $ws);
        }

        $nombre_emergencia = $this->html->input_text(cols: 6, disabled: false, name: 'nombre_emergencia',
            place_holder: 'Nombre Emergencia', row_upd: new stdClass(), value_vacio: false);
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al obtener input', data: $nombre_emergencia, header: $header, ws: $ws);
        }

        $curp = $this->html->input_text(cols: 6, disabled: false, name: 'curp',
            place_holder: 'CURP', row_upd: new stdClass(), value_vacio: false);
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al obtener input', data: $curp, header: $header, ws: $ws);
        }

        $foto = $this->html->input_file(cols: 6, name: 'foto', row_upd: new stdClass(), 
            value_vacio: false, place_holder: 'Foto del Estudiante', required: false);
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al obtener input', data: $foto, header: $header, ws: $ws);
        }
       
        $this->inputs->foto = $foto;
        $this->inputs->horario = $horario;
        $this->inputs->telefono_emergencia = $telefono_emergencia;
        $this->inputs->nombre_emergencia = $nombre_emergencia;
        $this->inputs->curp = $curp;
    }

    $this->include_inputs_alta = (new generales())->path_base . 'templates/inputs/com_cliente/alta.php';


    return $r_alta;
}
    public function alta_bd(bool $header, bool $ws = false): array|stdClass
    {
        $datos_adicionales = [];

        if (property_exists(generales::class, 'datos_adicionales_com_cliente') && generales::$datos_adicionales_com_cliente) {
            $campos_extra = ['horario', 'telefono_emergencia', 'nombre_emergencia', 'curp'];
            foreach ($campos_extra as $campo) {
                $datos_adicionales[$campo] = $_POST[$campo] ?? null;
                unset($_POST[$campo]);
            }
        }

        $r_alta = parent::alta_bd(header: false, ws: false);
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al dar de alta cliente', data: $r_alta, header: $header, ws: $ws
            );
        }

       if (!empty($datos_adicionales) && isset($r_alta->registro_id)) {
        $datos_adicionales['com_cliente_id'] = $r_alta->registro_id;
        $datos_adicionales['codigo'] = 'DAC_' . $r_alta->registro_id;
        $datos_adicionales['descripcion'] = 'Datos adicionales cliente ' . $r_alta->registro_id;
        $datos_adicionales['descripcion_select'] = 'DAC_' . $r_alta->registro_id;
        $datos_adicionales['alias'] = 'DAC_' . $r_alta->registro_id;
        $datos_adicionales['codigo_bis'] = 'DAC_' . $r_alta->registro_id;

        // Manejo de foto
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $extension = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            $extensiones_validas = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($extension, $extensiones_validas)) {
                return $this->retorno_error(
                    mensaje: 'Error: formato de foto no válido. Use JPG, PNG o WEBP',
                    data: $_FILES['foto'], header: $header, ws: $ws
                );
            }

            $nombre_archivo = 'cliente_' . $r_alta->registro_id . '_' . time() . '.' . $extension;
            $ruta_destino = (new generales())->path_base . 'archivos/fotos_clientes/' . $nombre_archivo;

            if (!move_uploaded_file($_FILES['foto']['tmp_name'], $ruta_destino)) {
                return $this->retorno_error(
                    mensaje: 'Error al guardar foto',
                    data: $ruta_destino, header: $header, ws: $ws
                );
            }

            $datos_adicionales['foto'] = 'archivos/fotos_clientes/' . $nombre_archivo;
        }

        $modelo_adicional = new datos_adicionales_com_cliente_artistik(link: $this->link);
        $modelo_adicional->registro = $datos_adicionales;
        $r_adicional = $modelo_adicional->alta_bd();
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al dar de alta datos adicionales',
                data: $r_adicional, header: $header, ws: $ws
            );
        }
    }

        if ($header) {
            $this->retorno_base(registro_id: $r_alta->registro_id, result: $r_alta,
                siguiente_view: 'modifica', ws: $ws);
        }
        if ($ws) {
            header('Content-Type: application/json');
            echo json_encode($r_alta, JSON_THROW_ON_ERROR);
            exit;
        }

        return $r_alta;
    }

  public function modifica_datos_adicionales(bool $header, bool $ws = false): array|string
    {

        if (!property_exists(generales::class, 'datos_adicionales_com_cliente') || !generales::$datos_adicionales_com_cliente) {
            return $this->retorno_error(
                mensaje: 'Acción no disponible en este sistema',
                data: [], header: $header, ws: $ws
            );
        }

        $this->accion_titulo = 'Modificar Datos Adicionales';
        $this->inputs = new stdClass();

        // Cargar datos existentes
        $modelo_adicional = new datos_adicionales_com_cliente_artistik(link: $this->link);
        $filtro = array('datos_adicionales_com_cliente_artistik.com_cliente_id' => $this->registro_id);
        $r_datos = $modelo_adicional->filtro_and(filtro: $filtro);

        $row_datos = new stdClass();
        $row_datos->horario = '';
        $row_datos->telefono_emergencia = '';
        $row_datos->nombre_emergencia = '';
        $row_datos->curp = '';
        $this->foto_actual = '';

        if (!errores::$error && $r_datos->n_registros > 0) {
            $datos = $r_datos->registros[0];
            $row_datos->horario = $datos['datos_adicionales_com_cliente_artistik_horario'] ?? '';
            $row_datos->telefono_emergencia = $datos['datos_adicionales_com_cliente_artistik_telefono_emergencia'] ?? '';
            $row_datos->nombre_emergencia = $datos['datos_adicionales_com_cliente_artistik_nombre_emergencia'] ?? '';
            $row_datos->curp = $datos['datos_adicionales_com_cliente_artistik_curp'] ?? '';
            $this->foto_actual = $datos['datos_adicionales_com_cliente_artistik_foto'] ?? '';
        }

        $horario = $this->html->input_text(cols: 6, disabled: false, name: 'horario',
            place_holder: 'Horario', row_upd: new stdClass(), value_vacio: false,
            value: $row_datos->horario);
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al obtener input', data: $horario, header: $header, ws: $ws);
        }
        $this->inputs->horario = $horario;

        $telefono_emergencia = $this->html->input_text(cols: 6, disabled: false, name: 'telefono_emergencia',
            place_holder: 'Teléfono Emergencia', row_upd: new stdClass(), value_vacio: false,
            value: $row_datos->telefono_emergencia);
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al obtener input', data: $telefono_emergencia, header: $header, ws: $ws);
        }
        $this->inputs->telefono_emergencia = $telefono_emergencia;

        $nombre_emergencia = $this->html->input_text(cols: 6, disabled: false, name: 'nombre_emergencia',
            place_holder: 'Nombre Emergencia', row_upd: new stdClass(), value_vacio: false,
            value: $row_datos->nombre_emergencia);
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al obtener input', data: $nombre_emergencia, header: $header, ws: $ws);
        }
        $this->inputs->nombre_emergencia = $nombre_emergencia;

        $curp = $this->html->input_text(cols: 6, disabled: false, name: 'curp',
            place_holder: 'CURP', row_upd: new stdClass(), value_vacio: false,
            value: $row_datos->curp);
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al obtener input', data: $curp, header: $header, ws: $ws);
        }
        $this->inputs->curp = $curp;

        // Botón para volver a modifica
        $button = $this->html->button_href(accion: 'modifica', etiqueta: 'Ir a Cliente',
            registro_id: $this->registro_id, seccion: $this->tabla, style: 'warning', params: array());
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al generar link', data: $button);
        }
        $this->button_com_cliente_modifica = $button;

        return [];
    }

    public function modifica_datos_adicionales_bd(bool $header, bool $ws = false): array|stdClass
    {

        if (!property_exists(generales::class, 'datos_adicionales_com_cliente') || !generales::$datos_adicionales_com_cliente) {
            return $this->retorno_error(
                mensaje: 'Acción no disponible en este sistema',
                data: [], header: $header, ws: $ws
            );
        }

        $campos_extra = ['horario', 'telefono_emergencia', 'nombre_emergencia', 'curp'];
        $datos_adicionales = [];
        foreach ($campos_extra as $campo) {
            $datos_adicionales[$campo] = $_POST[$campo] ?? null;
        }

        $modelo_adicional = new datos_adicionales_com_cliente_artistik(link: $this->link);
        $filtro = array('datos_adicionales_com_cliente_artistik.com_cliente_id' => $this->registro_id);
        $r_datos = $modelo_adicional->filtro_and(filtro: $filtro);

        // Manejo de foto
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $extension = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            $extensiones_validas = ['jpg', 'jpeg', 'png', 'webp'];
            if (in_array($extension, $extensiones_validas)) {
                $nombre_archivo = 'cliente_' . $this->registro_id . '_' . time() . '.' . $extension;
                $ruta_destino = (new generales())->path_base . 'archivos/fotos_clientes/' . $nombre_archivo;
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $ruta_destino)) {
                    $datos_adicionales['foto'] = 'archivos/fotos_clientes/' . $nombre_archivo;
                }
            }
        } elseif (isset($_POST['eliminar_foto']) && $_POST['eliminar_foto'] === '1') {
            $datos_adicionales['foto'] = '';
        }

        if (!errores::$error && $r_datos->n_registros > 0) {
            $id_adicional = $r_datos->registros[0]['datos_adicionales_com_cliente_artistik_id'];
            $r_mod = $modelo_adicional->modifica_bd(registro: $datos_adicionales, id: $id_adicional);
            if (errores::$error) {
                return $this->retorno_error(
                    mensaje: 'Error al modificar datos adicionales',
                    data: $r_mod, header: $header, ws: $ws
                );
            }
        } else {
            $datos_adicionales['com_cliente_id'] = $this->registro_id;
            $datos_adicionales['codigo'] = 'DAC_' . $this->registro_id;
            $datos_adicionales['descripcion'] = 'Datos adicionales cliente ' . $this->registro_id;
            $datos_adicionales['descripcion_select'] = 'DAC_' . $this->registro_id;
            $datos_adicionales['alias'] = 'DAC_' . $this->registro_id;
            $datos_adicionales['codigo_bis'] = 'DAC_' . $this->registro_id;
            $modelo_adicional->registro = $datos_adicionales;
            $r_mod = $modelo_adicional->alta_bd();
            if (errores::$error) {
                return $this->retorno_error(
                    mensaje: 'Error al crear datos adicionales',
                    data: $r_mod, header: $header, ws: $ws
                );
            }
        }

        $_SESSION['exito'][]['mensaje'] = 'Datos adicionales modificados correctamente';

        $link = "index.php?seccion=com_cliente&accion=modifica_datos_adicionales";
        $link .= "&registro_id=" . $this->registro_id;
        $link .= "&session_id=" . $_GET['session_id'];
        if (isset($_GET['adm_menu_id'])) {
            $link .= "&adm_menu_id=" . $_GET['adm_menu_id'];
        }
        header("Location: " . $link);
        exit;
    }
}