<?php
namespace gamboamartin\facturacion\controllers;

use config\generales;
use DateTime;
use gamboamartin\comercial\models\com_sucursal;
use gamboamartin\documento\models\doc_documento;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\html\fc_layout_nom_html;
use gamboamartin\facturacion\models\_cancela_nomina\_cancela_nomina;
use gamboamartin\facturacion\models\_email_nomina_cliente;
use gamboamartin\facturacion\models\_timbra_nomina;
use gamboamartin\facturacion\models\_timbra_nomina\_finalizacion;
use gamboamartin\facturacion\models\com_agente;
use gamboamartin\facturacion\models\fc_factura;
use gamboamartin\facturacion\models\fc_layout_factura;
use gamboamartin\facturacion\models\fc_layout_nom;
use gamboamartin\facturacion\models\fc_row_layout;
use gamboamartin\facturacion\models\fc_row_nomina;
use gamboamartin\modelo\modelo;
use gamboamartin\system\links_menu;
use gamboamartin\system\system;
use gamboamartin\template_1\html;
use JetBrains\PhpStorm\NoReturn;
use PDO;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use stdClass;
use ZipArchive;

class controlador_fc_layout_nom extends system{

    public const string ESTADO_LAYOUT_INICIAL = 'Descarga Layout';
    public const string ESTADO_LAYOUT_INTERMEDIO = 'Descargado o Generado';
    public const string ESTADO_LAYOUT_PAGADO = 'Pagado';
    public const string ESTADO_LAYOUT_FINAL = 'Enviado al Cliente';
    public const string ESTADO_SIN_TIMBRAR = 'SIN TIMBRAR';
    public const string ESTADO_TIMBRADO_PARCIAL = 'TIMBRADO PARCIAL';
    public const string ESTADO_TIMBRADO_CON_ERROR = 'TIMBRADO CON ERROR';
    public const string ESTADO_TIMBRADO = 'TIMBRADO';

    public stdClass|array $keys_selects = array();
    public array $fc_rows_layout = array();

    public string $link_modifica_datos_bd = '';
    public string $link_modifica_sucursal_bd = '';
    public string $link_asigna_factura_bd = '';
    public string $link_reporte_facturacion_bd = '';
    public string $link_reporte_ventas_por_operador_bd = '';
    public string $descripcion_nom_layout = '';
    public string $folio_factura_asignada = '';
    public bool $disabled = false;
    public string $fecha_emision = '';
    public string $btn_modifica_fecha_emision = '';

    public int $tipo_dispersion;
    public bool $aplica_relacion_layout_factura;

    public function __construct(PDO $link, html $html = new html(), stdClass $paths_conf = new stdClass()){
        $modelo = new fc_layout_nom(link: $link);
        $html_ = new fc_layout_nom_html(html: $html);
        $obj_link = new links_menu(link: $link, registro_id:  $this->registro_id);

        $columns["fc_layout_nom_id"]["titulo"] = "Id";
        $columns["fc_layout_nom_codigo"]["titulo"] = "Codigo";
        $columns["com_cliente_razon_social"]["titulo"] = "Cliente Razon Social";
        $columns["com_cliente_rfc"]["titulo"] = "Cliente RFC";
        $columns["fc_layout_nom_descripcion"]["titulo"] = "Descripcion";
        $columns["fc_layout_nom_estado_timbrado"]["titulo"] = "Estado Timbrado";
        $columns["fc_layout_nom_estado_layout"]["titulo"] = "Estado Layout";
        $columns["fc_layout_nom_fecha_pago"]["titulo"] = "Fecha Pago";

        $filtro = [
            "fc_layout_nom.id",
            "fc_layout_nom.codigo",
            "com_cliente.razon_social",
            "com_cliente.rfc",
            "fc_layout_nom.descripcion",
            "fc_layout_nom.estado_timbrado",
            "fc_layout_nom.estado_layout",
            "fc_layout_nom.fecha_pago",
        ];

        $datatables = new stdClass();
        $datatables->columns = $columns;
        $datatables->filtro = $filtro;

        parent::__construct(html:$html_, link: $link,modelo:  $modelo, obj_link: $obj_link,
            datatables: $datatables, paths_conf: $paths_conf);

        $this->lista_get_data = true;

        $this->tipo_dispersion = 1;
        if (isset($this->conf_generales->tipo_dispersion)) {
            $this->tipo_dispersion = $this->conf_generales->tipo_dispersion;
        }

        $this->aplica_relacion_layout_factura = false;
        if (isset($this->conf_generales->aplica_relacion_layout_factura)) {
            $this->aplica_relacion_layout_factura = $this->conf_generales->aplica_relacion_layout_factura;
        }

    }

    public function alta(bool $header, bool $ws = false): array|string
    {

        $alta = parent::alta($header, $ws);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar input', data: $alta,header:  $header,ws:  $ws);
        }
        $this->inputs = new stdClass();

        $documento = $this->html->input_file(cols: 12, name: 'documento', row_upd: new stdClass(), value_vacio: false,
            place_holder: 'Layout', required: false);
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al obtener inputs', data: $documento, header: $header, ws: $ws);
        }

        $this->inputs->documento = $documento;


        $input_descripcion= $this->html->input_descripcion(cols: 9,row_upd: new stdClass(),value_vacio: false);

        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar $input_descripcion',
                data: $input_descripcion,header:  $header,ws:  $ws);
        }
        $this->inputs->descripcion = $input_descripcion;

        $hoy = date('Y-m-d');
        $input_fecha_pago= $this->html->input_fecha(cols: 3, row_upd: new stdClass(), value_vacio: false,
            name: 'fecha_pago', place_holder: 'Fecha de Pago', value: $hoy);

        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar $input_descripcion',
                data: $input_descripcion,header:  $header,ws:  $ws);
        }
        $this->inputs->fecha_pago = $input_fecha_pago;

        $modelo_com_sucursal = new com_sucursal(link: $this->link);
        $filtro_input_select_sucursal = [];

        $input_select = $this->html->select_catalogo(cols: 12, con_registros: true, id_selected: -1,
            modelo: $modelo_com_sucursal, columns_ds: ['com_sucursal_descripcion_select'],
            disabled: false, filtro: $filtro_input_select_sucursal, label: 'Sucursal',name: 'com_sucursal_id',
            registros: [], required: true
        );
        if(errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al generar input_select_sucursal',
                data: $input_select,
                header: $header, ws: $ws
            );
        }

        $this->inputs->sucursal = $input_select;

        $modelo_fc_factura = new fc_factura(link: $this->link);

        $input_select_factura = $this->html->select_catalogo(cols: 12, con_registros: false, id_selected: -1,
            modelo: $modelo_fc_factura, columns_ds: [],
            disabled: false, filtro: [], label: 'Factura',name: 'fc_factura_id',
            registros: [], required: true
        );
        if(errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al generar input_select_factura',
                data: $input_select_factura,
                header: $header, ws: $ws
            );
        }

        $this->inputs->input_select_factura = $input_select_factura;

        return $alta;
    }

    public function alta_bd(bool $header, bool $ws = false): array|stdClass
    {

        if (isset($_POST['fc_factura_id'])){
            if ((int)$_POST['fc_factura_id'] !== -1 ) {
                $fc_factura_id = $_POST['fc_factura_id'];
            }
            unset($_POST['fc_factura_id']);
        }

        $r_alta = parent::alta_bd(header: false, ws: $ws);
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al guardar', data: $r_alta, header: $header, ws: $ws);
        }

        if (isset($fc_factura_id)) {
            $fc_layout_nom_id = $r_alta->registro_id;
            $rs = (new fc_layout_factura($this->link))->relaciona_layout_con_factura(
                fc_layout_nom_id: $fc_layout_nom_id,
                fc_factura_id: $fc_factura_id
            );
            if (errores::$error) {
                return $this->retorno_error(
                    mensaje: 'Error en relaciona_layout_con_factura',
                    data: $rs, header: $header, ws: $ws
                );
            }
        }

        $link = "index.php?seccion=fc_layout_nom&accion=lista";
        if (isset($_GET['session_id'])) {
            $link .= "&session_id={$_GET['session_id']}";
        }
        header("Location: " . $link);
        exit;
    }
    public function actualiza_porcentaje_comision(bool $header, bool $ws = false)
    {
        $this->modelo->registro_id = $this->registro_id;
        $data = $this->modelo->obten_data();
        if(errores::$error) {
            return $this->retorno_error(mensaje: 'Error al obtener data fc_layout_nom',data: $data,
                header: $header, ws: $ws);
        }

        $fc_layout_nom_com_sucursal_id = $data['fc_layout_nom_com_sucursal_id'];

        $porcentaje_comision_cliente = $this->modelo->obtener_porcentaje_comision_by_com_sucursal_id(
            com_sucursal_id: $fc_layout_nom_com_sucursal_id
        );
        if(errores::$error) {
            return $this->retorno_error(mensaje: 'Error al obtener el porcentaje_comision_cliente',
                data: $porcentaje_comision_cliente,
                header: $header,
                ws: $ws
            );
        }

        $r_modifica_bd = $this->modelo->modifica_bd(
            registro: ['porcentaje_comision_cliente' => $porcentaje_comision_cliente],
            id: $this->registro_id
        );
        if(errores::$error) {
            return $this->retorno_error(mensaje: 'Error al modificar fc_layout_nom',
                data: $r_modifica_bd,
                header: $header,
                ws: $ws
            );
        }

        $filtro = [
            'fc_row_layout.fc_layout_nom_id' => $this->registro_id,
        ];

        $fc_row_layout_modelo = new fc_row_layout($this->link);

        $rs = $fc_row_layout_modelo->filtro_and(filtro: $filtro);
        if(errores::$error) {
            return $this->retorno_error(mensaje: 'Error al obtener datos fc_row_layout',
                data: $rs,
                header: $header,
                ws: $ws
            );
        }

        foreach ($rs->registros as $registro) {
            $fc_row_layout_id = $registro['fc_row_layout_id'];
            $r_modifica_bd = $fc_row_layout_modelo->modifica_bd(
                registro: ['porcentaje_comision_cliente' => $porcentaje_comision_cliente],
                id: $fc_row_layout_id
            );
            if(errores::$error) {
                return $this->retorno_error(mensaje: 'Error al modificar fc_row_layout',
                    data: $r_modifica_bd,
                    header: $header,
                    ws: $ws
                );
            }

        }

        $_SESSION['exito'][]['mensaje'] = "fc_layout_nom_id={$this->registro_id} porcentaje comision cliente ";
        $_SESSION['exito'][]['mensaje'] .= "actualizado correctamente al {$porcentaje_comision_cliente}%";
        $link = "index.php?seccion=fc_layout_nom&accion=lista&adm_menu_id=75";
        $link .= "&session_id={$_GET['session_id']}";
        header("Location: " . $link);
        exit;

    }
    public function asigna_factura(bool $header, bool $ws = false)
    {
        $fc_layout_nom_id = $this->registro_id;
        $this->modelo->registro_id = $fc_layout_nom_id;
        $data = $this->modelo->obten_data();
        if(errores::$error) {
            return $this->retorno_error(mensaje: 'Error al obtener data fc_layout_nom',data: $data,
                header: $header, ws: $ws);
        }

        $filtro = [
            'fc_layout_factura.fc_layout_nom_id' => $fc_layout_nom_id,
        ];

        $rs1 = (new fc_layout_factura($this->link))->filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->retorno_error(
                mensaje: 'Error al buscar registro fc_layout_factura',
                data: $data,
                header: $header, ws: $ws);
        }

        if ($rs1->n_registros > 0) {
            $this->folio_factura_asignada = $rs1->registros[0]['fc_factura_folio'];
        }

        $com_cliente_id = $data['com_cliente_id'];

        $this->descripcion_nom_layout = $data['fc_layout_nom_descripcion'];

        $link = "index.php?seccion=fc_layout_nom&accion=asigna_factura_bd&registro_id={$this->registro_id}&session_id={$_GET['session_id']}";
        $this->link_asigna_factura_bd = $link;

        $this->inputs = new stdClass();

        $modelo_fc_factura = new fc_factura(link: $this->link);
        $filtro_input_select_factura = [
            'com_cliente.id' => $com_cliente_id,
            'fc_factura.etapa' => 'TIMBRADO',
            'fc_factura.asignado_fc_layout' => 'inactivo',
        ];
        $columnas_input_select_factura = [
            'fc_factura_folio','fc_factura_total','com_cliente_razon_social',
            'fc_factura_fecha'
        ];

        $input_select_factura = $this->html->select_catalogo(cols: 12, con_registros: true, id_selected: -1,
            modelo: $modelo_fc_factura, columns_ds: $columnas_input_select_factura,
            disabled: false, filtro: $filtro_input_select_factura, label: 'Factura',name: 'fc_factura_id',
            registros: [], required: true
        );
        if(errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al generar input_select_factura',
                data: $input_select_factura,
                header: $header, ws: $ws
            );
        }

        $this->inputs->input_select_factura = $input_select_factura;

        return [];

    }

    public function asigna_factura_bd(bool $header, bool $ws = false)
    {

        $fc_factura_id = $_POST['fc_factura_id'];
        $fc_layout_nom_id = $_POST['fc_layout_nom_id'];

        $rs = (new fc_layout_factura($this->link))->relaciona_layout_con_factura(
            fc_layout_nom_id: $fc_layout_nom_id,
            fc_factura_id: $fc_factura_id,
        );
        if(errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al relaciona_factura_con_layout',
                data: $rs,
                header: $header, ws: $ws
            );
        }

        $_SESSION['exito'][]['mensaje'] = "fc_factura_id={$fc_factura_id} asignada correctamente al fc_layout_nom_id={$this->registro_id}";
        $link = "index.php?seccion=fc_layout_nom&accion=lista&adm_menu_id=75";
        $link .= "&session_id={$_GET['session_id']}";
        header("Location: " . $link);
        exit;
    }

    public function cancelar_recibo(bool $header, bool $ws = false)
    {
        $fc_row_layout_id = $_GET['fc_row_layout_id'];

        $result = (new _cancela_nomina())->cancela_recibo(
            link: $this->link,
            fc_row_layout_id: $fc_row_layout_id
        );
        if(errores::$error) {
            return $this->retorno_error(mensaje: 'Error al cancelar recibo',data: $result,
                header: $header, ws: $ws);
        }

        $link = "index.php?seccion=fc_layout_nom&accion=ver_empleados&registro_id={$this->registro_id}";
        $link .= "&adm_menu_id=-1&session_id={$_GET['session_id']}";
        header("Location: " . $link);
        exit;

    }

    public function cancela_recibos(bool $header, bool $ws = false)
    {
        $filtro = [
            'fc_row_layout.fc_layout_nom_id' => $this->registro_id,
            'fc_row_layout.esta_timbrado' => 'activo',
        ];

        $fc_row_layout = (new fc_row_layout($this->link))->filtro_and(columnas: ['fc_row_layout_id'], filtro: $filtro);


        if ((int)$fc_row_layout->n_registros === 0) {
            return $this->retorno_error(mensaje: 'No existen registros timbrados',data: $fc_row_layout,
                header: $header, ws: $ws);
        }

        $registros = $fc_row_layout->registros;

        foreach ($registros as $registro) {

            $fc_row_layout_id = $registro['fc_row_layout_id'];

            $result = (new _cancela_nomina())->cancela_recibo(
                link: $this->link,
                fc_row_layout_id: $fc_row_layout_id
            );
            if(errores::$error) {
                return $this->retorno_error(
                    mensaje: 'Error al regenera_rec_pdf fc_row_layout_id='.$fc_row_layout_id,
                    data: $result,
                    header: $header,
                    ws: $ws
                );
            }
        }

        $link = "index.php?seccion=fc_layout_nom&accion=lista&session_id={$_GET['session_id']}";
        header("Location: " . $link);
        exit;
    }

    #[NoReturn]
    public function carga_empleados(bool $header, bool $ws = false): void
    {
        $rows_empleados = (new _xls_empleados())->carga_empleados($this->link,$this->registro_id);
        if(errores::$error){

            $error =  (new errores())->error('Error al generar row', $rows_empleados);
            print_r($error);
            exit;
        }
        exit;

    }

    public function carga_files_layout(bool $header, bool $ws = false)
    {
        if(!isset($_GET['dir'])){
            echo 'Integra dir por get';
            exit;
        }
        $dir = $_GET['dir'];
        $generales = new generales();

        $dir_explode = (explode(".",$dir));
        $fecha_pago = $dir_explode[0].'-'.$dir_explode[1].'-'.$dir_explode[2];


        $ruta_carpeta = $generales->path_base."/archivos/doc_documento/$dir";
        $elementos = scandir($ruta_carpeta);
        $model = new fc_layout_nom($this->link);

        foreach ($elementos as $elemento) {
            if ($elemento != "." && $elemento != "..") {
                $explode_doc = explode('.',$elemento);
                $row_insert = array();
                $row_insert['id'] = $explode_doc[0];

                $name_new = "";
                foreach ($explode_doc as $indice=>$explode_ind) {
                    if($indice === 0){
                        continue;
                    }
                    $punto = '';
                    if($name_new !== ''){
                        $punto = '.';
                    }
                    $name_new .= $punto.$explode_ind;
                }

                $name_new = str_replace('  ', ' ', $name_new);
                $name_new = trim($name_new);

                $file_ruta = $ruta_carpeta."/".$elemento;
                $_FILES['documento']['name'] = $name_new;
                $_FILES['documento']['tmp_name'] = $file_ruta;


                $row_insert['descripcion'] = trim($name_new);
                $row_insert['descripcion_select'] = trim($name_new);
                $row_insert['fecha_pago'] = $fecha_pago;
                $_POST['fecha_pago'] = $fecha_pago;

                $model->registro = $row_insert;

                $alta_row = $model->alta_bd();
                if(errores::$error){
                    $error =  (new errores())->error('Error al generar row', $alta_row);
                    print_r($error);
                    exit;

                }
                print_r($alta_row);
            }
        }

        print_r($elementos);exit;

        exit;

    }

    public function descarga_original(bool $header, bool $ws = false)
    {
        if($this->registro_id <= 0 ){
            return $this->retorno_error(mensaje: 'Error id debe ser mayor a 0',data:  $this->registro_id,
                header:  $header,ws:  $ws);
        }
        $fc_layout_nom = (new fc_layout_nom($this->link))->registro($this->registro_id,retorno_obj: true);
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al obtener layout', data: $fc_layout_nom, header: $header, ws: $ws);
        }

        $file_url = $fc_layout_nom->doc_documento_ruta_absoluta;

        if(!file_exists($file_url)){
            return $this->retorno_error(mensaje: 'Error file_url no existe', data: $file_url,
                header:  $header,ws:  $ws);
        }

        $base_name = $this->registro_id.".".$fc_layout_nom->doc_documento_name_out;

        ob_clean();
        if($header) {
            header('Content-Type: application/octet-stream');
            header("Content-Transfer-Encoding: Binary");
            header("Content-disposition: attachment; filename=\"" . basename($base_name) . "\"");
            readfile($file_url);
            exit;
        }
        return file_get_contents($file_url);


    }

    public function regenera_rec_pdf(bool $header, bool $ws = false)
    {
        $fc_row_layout_id = $_GET['fc_row_layout_id'];

        $result = (new _finalizacion())->regenera_nomina_pdf(
            fc_row_layout_id: $fc_row_layout_id,
            link:  $this->link
        );
        if(errores::$error) {
            return $this->retorno_error(mensaje: 'Error al regenera_rec_pdf',data: $result,
                header: $header, ws: $ws);
        }

        $link = "index.php?seccion=fc_layout_nom&accion=ver_empleados&registro_id={$this->registro_id}";
        $link .= "&adm_menu_id=-1&session_id={$_GET['session_id']}";
        header("Location: " . $link);
        exit;
    }

    public function regenera_rec_pdfs(bool $header, bool $ws = false)
    {
        $filtro = [
            'fc_row_layout.fc_layout_nom_id' => $this->registro_id,
            'fc_row_layout.esta_timbrado' => 'activo',
        ];

        $fc_row_layout = (new fc_row_layout($this->link))->filtro_and(columnas: ['fc_row_layout_id'], filtro: $filtro);


        if ((int)$fc_row_layout->n_registros === 0) {
            return $this->retorno_error(mensaje: 'No existen registros timbrados',data: $fc_row_layout,
                header: $header, ws: $ws);
        }

        $registros = $fc_row_layout->registros;

        foreach ($registros as $registro) {

            $fc_row_layout_id = $registro['fc_row_layout_id'];

            $result = (new _finalizacion())->regenera_nomina_pdf(
                fc_row_layout_id: $fc_row_layout_id,
                link:  $this->link
            );
            if(errores::$error) {
                return $this->retorno_error(
                    mensaje: 'Error al regenera_rec_pdf fc_row_layout_id='.$fc_row_layout_id,
                    data: $result,
                    header: $header,
                    ws: $ws
                );
            }
        }

        $link = "index.php?seccion=fc_layout_nom&accion=lista&session_id={$_GET['session_id']}";
        header("Location: " . $link);
        exit;

    }

    public function descarga_rec_pdf(bool $header, bool $ws = false)
    {

        $datos_recibo = (new _timbra_nomina\_datos())->datos_recibo(link: $this->link,
            fc_row_layout_id: $_GET['fc_row_layout_id']);
        if(errores::$error) {
            return $this->retorno_error(mensaje: 'Error al obtener datos del recibo',data: $datos_recibo,
                header: $header, ws: $ws);
        }
        $filtro['fc_row_layout.id'] = $_GET['fc_row_layout_id'];
        $filtro['doc_tipo_documento.id'] = 8;
        $filtro['fc_row_nomina.status'] = 'activo';
        $r_fc_row_nomina = (new fc_row_nomina($this->link))->filtro_and(filtro: $filtro);
        if(errores::$error) {
            return $this->retorno_error(mensaje: 'Error al obtener datos del recibo',data: $r_fc_row_nomina,
                header: $header, ws: $ws);
        }
        if($r_fc_row_nomina->n_registros === 0){
            return $this->retorno_error(mensaje: 'No existe xml',data: $r_fc_row_nomina,header: $header, ws: $ws);
        }

        $fc_row_nomina = $r_fc_row_nomina->registros[0];

        if (!is_file($fc_row_nomina['doc_documento_ruta_absoluta'])) {
            http_response_code(404);
            exit('Archivo no encontrado');
        }

        $filename = $fc_row_nomina['fc_layout_nom_id'].'.'.$fc_row_nomina['fc_row_layout_rfc'].'.pdf';

        // Sanitiza filename (sin separadores ni control chars)
        $safeName = preg_replace('/[\x00-\x1F\x7F\/\\\:\*\?"<>\|]+/', '_', $filename);
        if (!preg_match('/\.pdf$/i', $safeName)) {
            $safeName .= '.pdf';
        }
        if (function_exists('ob_get_level')) {
            while (ob_get_level() > 0) { ob_end_clean(); }
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="'.$safeName.'"; filename*=UTF-8\'\''.rawurlencode($safeName));
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('X-Content-Type-Options: nosniff');
        header('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime($fc_row_nomina['doc_documento_ruta_absoluta'])).' GMT');
        header('Content-Length: '.filesize($fc_row_nomina['doc_documento_ruta_absoluta']));

        // (Opcional) si quieres Content-Length:
        // header('Content-Length: '.strlen($xmlString));

        readfile($fc_row_nomina['doc_documento_ruta_absoluta']);
        exit;


    }

    public function descarga_rec_xml(bool $header, bool $ws = false)
    {

        $datos_recibo = (new _timbra_nomina\_datos())->datos_recibo(link: $this->link,
            fc_row_layout_id: $_GET['fc_row_layout_id']);
        if(errores::$error) {
            return $this->retorno_error(mensaje: 'Error al obtener datos del recibo',data: $datos_recibo,
                header: $header, ws: $ws);
        }
        $filtro['fc_row_layout.id'] = $_GET['fc_row_layout_id'];
        $filtro['doc_tipo_documento.id'] = 2;
        $filtro['fc_row_nomina.status'] = 'activo';
        $r_fc_row_nomina = (new fc_row_nomina($this->link))->filtro_and(filtro: $filtro);
        if(errores::$error) {
            return $this->retorno_error(mensaje: 'Error al obtener datos del recibo',data: $r_fc_row_nomina,
                header: $header, ws: $ws);
        }
        if($r_fc_row_nomina->n_registros === 0){
            return $this->retorno_error(mensaje: 'No existe xml',data: $r_fc_row_nomina,header: $header, ws: $ws);
        }

        $fc_row_nomina = $r_fc_row_nomina->registros[0];

        $xmlString = file_get_contents($fc_row_nomina['doc_documento_ruta_absoluta']);

        if (!str_starts_with($xmlString, '<?xml')) {
            $xmlString = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" . $xmlString;
        }
        // Quita BOM si existiera
        $xmlString = preg_replace('/^\xEF\xBB\xBF/', '', $xmlString);


        $filename = $fc_row_nomina['fc_layout_nom_id'].'.'.$fc_row_nomina['fc_row_layout_rfc'].'.xml';

        // Sanitiza filename (sin separadores ni control chars)
        $safeName = preg_replace('/[\x00-\x1F\x7F\/\\\:\*\?"<>\|]+/', '_', $filename);
        if (!preg_match('/\.xml$/i', $safeName)) {
            $safeName .= '.xml';
        }

        // Limpia buffers para evitar bytes extra
        if (function_exists('ob_get_level')) {
            while (ob_get_level() > 0) { ob_end_clean(); }
        }

        header('Content-Type: application/xml; charset=UTF-8');
        header('Content-Disposition: attachment; filename="'.$safeName.'"; filename*=UTF-8\'\''.rawurlencode($safeName));
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('X-Content-Type-Options: nosniff');
        header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');

        // (Opcional) si quieres Content-Length:
        // header('Content-Length: '.strlen($xmlString));

        echo $xmlString;
        exit;


    }

    public function descarga_rec_zip(bool $header, bool $ws = false)
    {
        $generales = new generales();
        $path_base = $generales->path_base."archivos/";
        $datos_recibo = (new _timbra_nomina\_datos())->datos_recibo(link: $this->link,
            fc_row_layout_id: $_GET['fc_row_layout_id']);
        if(errores::$error) {
            return $this->retorno_error(mensaje: 'Error al obtener datos del recibo',data: $datos_recibo,
                header: $header, ws: $ws);
        }
        $filtro = array();
        $filtro['fc_row_layout.id'] = $_GET['fc_row_layout_id'];
        $filtro['doc_tipo_documento.id'] = 2;
        $filtro['fc_row_nomina.status'] = 'activo';
        $r_fc_row_nomina = (new fc_row_nomina($this->link))->filtro_and(filtro: $filtro);
        if(errores::$error) {
            return $this->retorno_error(mensaje: 'Error al obtener datos del recibo',data: $r_fc_row_nomina,
                header: $header, ws: $ws);
        }
        if($r_fc_row_nomina->n_registros === 0){
            return $this->retorno_error(mensaje: 'No existe xml',data: $r_fc_row_nomina,header: $header, ws: $ws);
        }

        $fc_row_nomina_xml = $r_fc_row_nomina->registros[0];

        $filtro = array();
        $filtro['fc_row_layout.id'] = $_GET['fc_row_layout_id'];
        $filtro['doc_tipo_documento.id'] = 8;
        $filtro['fc_row_nomina.status'] = 'activo';
        $r_fc_row_nomina = (new fc_row_nomina($this->link))->filtro_and(filtro: $filtro);
        if(errores::$error) {
            return $this->retorno_error(mensaje: 'Error al obtener datos del recibo',data: $r_fc_row_nomina,
                header: $header, ws: $ws);
        }
        if($r_fc_row_nomina->n_registros === 0){
            return $this->retorno_error(mensaje: 'No existe xml',data: $r_fc_row_nomina,header: $header, ws: $ws);
        }

        $fc_row_nomina_pdf = $r_fc_row_nomina->registros[0];

        $zip = new ZipArchive();
        $zipFile = $path_base.$fc_row_nomina_pdf['fc_layout_nom_id'].'.'.$fc_row_nomina_pdf['fc_row_layout_rfc'].'.zip';
        if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            exit("No se pudo crear el archivo ZIP\n");
        }

        //print_r($zipFile);exit;

        $f_xml = $fc_row_nomina_pdf['fc_layout_nom_id'].'.'.$fc_row_nomina_pdf['fc_row_layout_rfc'].'.xml';
        $f_pdf = $fc_row_nomina_pdf['fc_layout_nom_id'].'.'.$fc_row_nomina_pdf['fc_row_layout_rfc'].'.pdf';

        $f_xml_ct = file_get_contents($fc_row_nomina_xml['doc_documento_ruta_absoluta']);
        $f_pdf_ct = file_get_contents($fc_row_nomina_pdf['doc_documento_ruta_absoluta']);

        $zip->addFromString($f_xml, $f_xml_ct);
        $zip->addFromString($f_pdf, $f_pdf_ct);
        $zip->close();

        $name = $fc_row_nomina_pdf['fc_layout_nom_id'].'.'.$fc_row_nomina_pdf['fc_row_layout_rfc'].".zip";
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="'.$name.'"');
        header('Content-Length: ' . filesize($zipFile));
        readfile($zipFile);
        unlink($zipFile);

        exit;


    }

    public function envia_nomina_cliente(bool $header, bool $ws = false)
    {
        $n_correos_enviados = $this->enviar_nomina_cliente_fc_layout_nom_id(
            fc_layout_nom_id:  $this->registro_id
        );
        if(errores::$error) {
            $error = (new errores())->error(
                mensaje:  "Error al enviar_nomina_cliente_fc_layout_nom_id",
                data: $n_correos_enviados
            );
            print_r($error);
            exit;
        }

        $_SESSION['exito'][]['mensaje'] = "Se enviaron {$n_correos_enviados} correos de manera exitosa al cliente";
        $link = "index.php?seccion=fc_layout_nom&accion=lista&adm_menu_id=75";
        $link .= "&session_id={$_GET['session_id']}";
        header("Location: " . $link);
        exit;
    }

    public function envia_nomina_empleados(bool $header, bool $ws = false): void
    {
        $n_correos_enviados = $this->enviar_nomina_empleados_fc_layout_nom_id(
            fc_layout_nom_id:  $this->registro_id
        );
        if(errores::$error) {
            $error = (new errores())->error(
                mensaje:  "Error al enviar_nomina_empleados_fc_layout_nom_id",
                data: $n_correos_enviados
            );
            print_r($error);
            exit;
        }

        $_SESSION['exito'][]['mensaje'] = "Se envió el correo de manera exitosa a {$n_correos_enviados} empleados";
        $link = "index.php?seccion=fc_layout_nom&accion=lista&adm_menu_id=75";
        $link .= "&session_id={$_GET['session_id']}";
        header("Location: " . $link);
        exit;
    }

    public function envia_nominas(bool $header, bool $ws = false)
    {
        $fc_layout_nom_modelo = new fc_layout_nom($this->link);
        $fc_layout_nom_modelo->registro_id = $this->registro_id;
        $data = $fc_layout_nom_modelo->obten_data();
        if(errores::$error) {
            $error = (new errores())->error(
                mensaje:  "Error al obtener datos del fc_layout_nom",
                data: $data
            );
            print_r($error);
            exit;
        }

        $fc_layout_nom_envia_nomina_empleado = $data['fc_layout_nom_envia_nomina_empleado'];
        $fc_layout_nom_envia_nomina_cliente = $data['fc_layout_nom_envia_nomina_cliente'];

        if ($fc_layout_nom_envia_nomina_empleado === 'inactivo' && $fc_layout_nom_envia_nomina_cliente === 'inactivo') {
            $msj = "El fc_layout_nom_id={$this->registro_id} no tiene el envió de correo activo para ningún tipo de contacto";
            $_SESSION['exito'][]['mensaje'] = $msj;
            $link = "index.php?seccion=fc_layout_nom&accion=lista&adm_menu_id=75";
            $link .= "&session_id={$_GET['session_id']}";
            header("Location: " . $link);
            exit;
        }

        $msj = '';

        if ($fc_layout_nom_envia_nomina_empleado === 'activo') {
            $n_correos_enviados = $this->enviar_nomina_empleados_fc_layout_nom_id(
                fc_layout_nom_id:  $this->registro_id
            );
            if(errores::$error) {
                $error = (new errores())->error(
                    mensaje:  "Error al enviar_nomina_empleados_fc_layout_nom_id",
                    data: $n_correos_enviados
                );
                print_r($error);
                exit;
            }
            $msj .= "\nSe envió el correo de manera exitosa a {$n_correos_enviados} empleados";
        }

        if ($fc_layout_nom_envia_nomina_cliente === 'activo') {
            $n_correos_enviados = $this->enviar_nomina_cliente_fc_layout_nom_id(
                fc_layout_nom_id:  $this->registro_id
            );
            if(errores::$error) {
                $error = (new errores())->error(
                    mensaje:  "Error al enviar_nomina_cliente_fc_layout_nom_id",
                    data: $n_correos_enviados
                );
                print_r($error);
                exit;
            }
            $msj .= "\nSe enviaron {$n_correos_enviados} correos de manera exitosa al cliente";
        }

        $_SESSION['exito'][]['mensaje'] = $msj;
        $link = "index.php?seccion=fc_layout_nom&accion=lista&adm_menu_id=75";
        $link .= "&session_id={$_GET['session_id']}";
        header("Location: " . $link);
        exit;


    }

    public function genera_dispersion(bool $header, bool $ws = false)
    {
        $clase_dispersion = new _xls_dispersion();
        if ($this->tipo_dispersion === 2) {
            $clase_dispersion = new _xls_dispersion2($this->link);
        }
        $fc_layout_nom = (new fc_layout_nom($this->link))->registro($this->registro_id,retorno_obj: true);
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al obtener layout', data: $fc_layout_nom, header: $header, ws: $ws);
        }

        $verif_empleado = (new _xls_empleados())->carga_empleados($this->link,$this->registro_id);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al verificar empleado', data: $verif_empleado,
                header: $header, ws: $ws);
        }

        $datos = $clase_dispersion->lee_layout_base(fc_layout_nom: $fc_layout_nom);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener $datos', data: $datos,
                header: $header, ws: $ws);
        }

        $rs = (new fc_layout_nom($this->link))->cambiar_status_layout(
            registro_id: $this->registro_id,
            status_layout: controlador_fc_layout_nom::ESTADO_LAYOUT_INTERMEDIO,
        );
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al cambiar_status_layout', data: $rs,
                header: $header, ws: $ws);
        }

        $xls = $clase_dispersion->write_dispersion(hoja_base: $datos->hoja,
            layout_dispersion: $datos->layout_dispersion);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar $layout_dispersion', data: $xls,
                header: $header, ws: $ws);
        }

        exit;


    }

    public function layout_pagado(bool $header, bool $ws = false): array
    {

        $rs = (new fc_layout_nom($this->link))->cambiar_status_layout(
            registro_id: $this->registro_id,
            status_layout: self::ESTADO_LAYOUT_PAGADO,
        );
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al cambiar_status_layout', data: $rs, header: $header, ws: $ws);
        }

        $_SESSION['exito'][]['mensaje'] = 'Se cambio el estado layout correctamente';
        $link = "index.php?seccion=fc_layout_nom&accion=lista&adm_menu_id=75";
        $link .= "&session_id={$_GET['session_id']}";
        header("Location: " . $link);
        exit;
    }

    public function modifica_datos(bool $header, bool $ws = false)
    {
        // Validar que fc_row_layout_id esté presente
        if(!isset($_GET['fc_row_layout_id']) || empty($_GET['fc_row_layout_id'])){
            return $this->retorno_error(
                mensaje: 'Error: fc_row_layout_id es requerido', data: array(), header: $header, ws: $ws);
        }

        $fc_row_layout = (new fc_row_layout($this->link))->registro($_GET['fc_row_layout_id'],retorno_obj: true);
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al obtener layout', data: $fc_row_layout, header: $header, ws: $ws);
        }

        // Validar que el fc_row_layout pertenece al fc_layout_nom actual
        if($fc_row_layout->fc_row_layout_fc_layout_nom_id != $this->registro_id){
            return $this->retorno_error(
                mensaje: 'Error: No tiene permisos para modificar este registro', data: array(), header: $header, ws: $ws);
        }

        // Validar que el registro no esté timbrado
        if ($fc_row_layout->fc_row_layout_esta_timbrado === 'activo' && $fc_row_layout->fc_row_layout_esta_cancelado === 'inactivo') {
            return $this->retorno_error(
                mensaje: 'Error: No se puede modificar un registro que ya está timbrado', data: array(), header: $header, ws: $ws);
        }

        $this->fecha_emision = $fc_row_layout->fc_row_layout_fecha_emision;


        $this->inputs = new stdClass();

        $cp = $this->html->input_text(
            cols: 6,
            disabled: $this->disabled,
            name: 'cp',
            place_holder: 'CODIGO POSTAL',
            row_upd: new stdClass(),
            value_vacio: false,
            value: $fc_row_layout->fc_row_layout_cp,

        );
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al obtener inputs', data: $cp, header: $header, ws: $ws);
        }

        $this->inputs->cp = $cp;

        $nss = $this->html->input_text(
            cols: 6,
            disabled: $this->disabled,
            name: 'nss',
            place_holder: 'NSS',
            row_upd: new stdClass(),
            value_vacio: false,
            value: $fc_row_layout->fc_row_layout_nss,
        );
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al obtener inputs', data: $nss, header: $header, ws: $ws);
        }

        $this->inputs->nss = $nss;

        $rfc = $this->html->input_text(
            cols: 6,
            disabled: $this->disabled,
            name: 'rfc',
            place_holder: 'RFC',
            row_upd: new stdClass(),
            value_vacio: false,
            value: $fc_row_layout->fc_row_layout_rfc,
        );
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al obtener inputs', data: $rfc, header: $header, ws: $ws);
        }

        $this->inputs->rfc = $rfc;

        $curp = $this->html->input_text(
            cols: 6,
            disabled: $this->disabled,
            name: 'curp',
            place_holder: 'CURP',
            row_upd: new stdClass(),
            value_vacio: false,
            value: $fc_row_layout->fc_row_layout_curp,
        );
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al obtener inputs', data: $curp, header: $header, ws: $ws);
        }

        $this->inputs->curp = $curp;

        $nombre_completo = $this->html->input_text(
            cols: 6,
            disabled: $this->disabled,
            name: 'nombre_completo',
            place_holder: 'Nombre Completo',
            row_upd: new stdClass(),
            value_vacio: false,
            value: $fc_row_layout->fc_row_layout_nombre_completo,
        );
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al obtener inputs', data: $nombre_completo, header: $header, ws: $ws);
        }

        // Nuevos inputs: banco, cuenta, clabe, neto_depositar
        $banco = $this->html->input_text(
            cols: 6,
            disabled: $this->disabled,
            name: 'banco',
            place_holder: 'BANCO',
            row_upd: new stdClass(),
            value_vacio: false,
            value: $fc_row_layout->fc_row_layout_banco,
        );
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al obtener input banco', data: $banco, header: $header, ws: $ws);
        }

        $cuenta = $this->html->input_text(
            cols: 6,
            disabled: $this->disabled,
            name: 'cuenta',
            place_holder: 'CUENTA',
            row_upd: new stdClass(),
            value_vacio: true,
            required: false,
            value: $fc_row_layout->fc_row_layout_cuenta,
        );
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al obtener input cuenta', data: $cuenta, header: $header, ws: $ws);
        }

        $clabe = $this->html->input_text(
            cols: 6,
            disabled: $this->disabled,
            name: 'clabe',
            place_holder: 'CLABE',
            row_upd: new stdClass(),
            value_vacio: false,
            value: $fc_row_layout->fc_row_layout_clabe,
        );
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al obtener input clabe', data: $clabe, header: $header, ws: $ws);
        }

        $neto_depositar = $this->html->input_text(
            cols: 6,
            disabled: $this->disabled,
            name: 'neto_depositar',
            place_holder: 'NETO A DEPOSITAR',
            row_upd: new stdClass(),
            value_vacio: false,
            value: $fc_row_layout->fc_row_layout_neto_depositar,
        );
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al obtener input neto_depositar', data: $neto_depositar, header: $header, ws: $ws);
        }

        $link = "index.php?seccion=fc_layout_nom&accion=modifica_datos_bd&registro_id={$this->registro_id}&session_id={$_GET['session_id']}";
        $link .= "&fc_row_layout_id={$_GET['fc_row_layout_id']}";
        $this->link_modifica_datos_bd = $link;

        $fecha_emision = $this->html->input_fecha(
            cols: 6,
            disabled: $this->disabled,
            name: 'fecha_emision',
            place_holder: 'Fecha de Emisión',
            row_upd: new stdClass(),
            value_vacio: false,
            value: $fc_row_layout->fc_row_layout_fecha_emision,
        );
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al obtener inputs', data: $fecha_emision, header: $header, ws: $ws);
        }

        $this->inputs->fecha_emision = $fecha_emision;
        $this->inputs->nombre_completo = $nombre_completo;
        $this->inputs->banco = $banco;
        $this->inputs->cuenta = $cuenta;
        $this->inputs->clabe = $clabe;
        $this->inputs->neto_depositar = $neto_depositar;
    }

    public function modifica_datos_bd(bool $header, bool $ws = false): array|stdClass
    {
        // Validar que fc_row_layout_id esté presente
        if(!isset($_GET['fc_row_layout_id']) || empty($_GET['fc_row_layout_id'])){
            return $this->retorno_error(
                mensaje: 'Error: fc_row_layout_id es requerido', data: array(), header: $header, ws: $ws);
        }

        $fc_row_layout_id = $_GET['fc_row_layout_id'];

        // Validar que el registro existe y obtener sus datos
        $fc_row_layout = (new fc_row_layout($this->link))->registro($fc_row_layout_id, retorno_obj: true);
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al obtener fc_row_layout', data: $fc_row_layout, header: $header, ws: $ws);
        }

        // Validar que el fc_row_layout pertenece al fc_layout_nom actual
        if($fc_row_layout->fc_row_layout_fc_layout_nom_id != $this->registro_id){
            return $this->retorno_error(
                mensaje: 'Error: No tiene permisos para modificar este registro', data: array(), header: $header, ws: $ws);
        }

        // Validar que el registro no esté timbrado
        if ($fc_row_layout->fc_row_layout_esta_timbrado === 'activo' && $fc_row_layout->fc_row_layout_esta_cancelado === 'inactivo') {
            return $this->retorno_error(
                mensaje: 'Error: No se puede modificar un registro que ya está timbrado', data: array(), header: $header, ws: $ws);
        }

        $fc_row_layout_modelo = new fc_row_layout($this->link);
        $upd_row['cp'] = $_POST['cp'];
        $upd_row['nss'] = $_POST['nss'];
        $upd_row['rfc'] = $_POST['rfc'];
        $upd_row['curp'] = $_POST['curp'];
        $upd_row['nombre_completo'] = $_POST['nombre_completo'];
        $upd_row['fecha_emision'] = $_POST['fecha_emision'];
        // Nuevos campos a actualizar
        if (isset($_POST['banco'])) $upd_row['banco'] = $_POST['banco'];
        if (isset($_POST['cuenta'])) $upd_row['cuenta'] = $_POST['cuenta'];
        if (isset($_POST['clabe'])) $upd_row['clabe'] = $_POST['clabe'];
        if (isset($_POST['neto_depositar'])) $upd_row['neto_depositar'] = $_POST['neto_depositar'];
        $result = $fc_row_layout_modelo->modifica_bd($upd_row, $fc_row_layout_id);
        if(errores::$error){
            return $this->retorno_error(
                mensaje: 'Error al modifica_bd de fc_row_layout', data: $result, header: $header, ws: $ws);
        }

        $link = "index.php?seccion=fc_layout_nom&accion=ver_empleados&registro_id={$this->registro_id}";
        $link .= "&adm_menu_id=-1&session_id={$_GET['session_id']}";
        header("Location: " . $link);
        return $result;
    }

    public function modifica_fecha_emision(bool $header, bool $ws = false): array|stdClass
    {
        $fc_layout_nom = (new fc_layout_nom($this->link))->registro($this->registro_id, retorno_obj: true);
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al obtener fc_layout_nom', data: $fc_layout_nom, header: $header, ws: $ws);
        }

        // Verificar que el layout no esté completamente timbrado
        $estado_timbrado = $fc_layout_nom->fc_layout_nom_estado_timbrado ?? $fc_layout_nom->estado_timbrado ?? 'SIN TIMBRAR';
        if ($estado_timbrado === 'TIMBRADO') {
            return $this->retorno_error(
                mensaje: 'Error: No se puede modificar la fecha de emisión de un layout completamente timbrado',
                data: array(), header: $header, ws: $ws);
        }

        // Configurar el link para el formulario
        $link = "index.php?seccion=fc_layout_nom&accion=modifica_fecha_emision_bd&registro_id={$this->registro_id}&session_id={$_GET['session_id']}";
        $this->link_modifica_datos_bd = $link;

        // Obtener la fecha de emisión actual o usar fecha_pago como default
        $fecha_emision_valor = $fc_layout_nom->fc_layout_nom_fecha_emision ?? $fc_layout_nom->fecha_emision ?? null;
        if (empty($fecha_emision_valor) || $fecha_emision_valor === '1900-01-01') {
            $fecha_emision_valor = $fc_layout_nom->fc_layout_nom_fecha_pago ?? $fc_layout_nom->fecha_pago ?? '1900-01-01';
        }

        // Convertir la fecha al formato datetime-local para el input HTML
        if (!empty($fecha_emision_valor) && $fecha_emision_valor !== '1900-01-01') {
            $this->fecha_emision = date('Y-m-d\TH:i', strtotime($fecha_emision_valor));
        } else {
            $this->fecha_emision = '';
        }

        return $fc_layout_nom;
    }

    public function modifica_fecha_emision_bd(bool $header, bool $ws = false): array|stdClass
    {
        // Validar que se recibió la fecha de emisión
        if (!isset($_POST['fecha_emision']) || empty($_POST['fecha_emision'])) {
            return $this->retorno_error(
                mensaje: 'Error: La fecha de emisión es requerida', data: array(), header: $header, ws: $ws);
        }

        // Obtener el registro actual
        $fc_layout_nom = (new fc_layout_nom($this->link))->registro($this->registro_id, retorno_obj: true);
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al obtener fc_layout_nom', data: $fc_layout_nom, header: $header, ws: $ws);
        }

        // Verificar que el layout no esté completamente timbrado
        $estado_timbrado = $fc_layout_nom->fc_layout_nom_estado_timbrado ?? $fc_layout_nom->estado_timbrado ?? 'SIN TIMBRAR';
        if ($estado_timbrado === 'TIMBRADO') {
            return $this->retorno_error(
                mensaje: 'Error: No se puede modificar la fecha de emisión de un layout completamente timbrado', 
                data: array(), header: $header, ws: $ws);
        }

        // Convertir la fecha del formato datetime-local a formato de base de datos
        $fecha_emision_input = $_POST['fecha_emision'];
        $fecha_emision_bd = date('Y-m-d H:i:s', strtotime($fecha_emision_input));

        $fc_layout_nom_modelo = new fc_layout_nom($this->link);
        $upd_row = [
            'fecha_emision' => $fecha_emision_bd,
        ];
        $rs = $fc_layout_nom_modelo->modifica_bd($upd_row, $this->registro_id);
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al actualizar la fecha de emisión', data: $rs, header: $header, ws: $ws);
        }

        // Redireccionar de vuelta a la lista de layouts de nómina
        $link = "index.php?seccion=fc_layout_nom&accion=lista&session_id={$_GET['session_id']}";
        header("Location: " . $link);
        exit;
    }

    public function modifica_sucursal(bool $header, bool $ws = false)
    {
        $registro_id = $this->registro_id;
        $this->modelo->registro_id = $registro_id;
        $data = $this->modelo->obten_data();

        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al obtener data de fc_layout_nom', data: $data, header: $header, ws: $ws);
        }


        $this->descripcion_nom_layout = $data['fc_layout_nom_descripcion'];

        $fc_layout_nom_com_sucursal_id = $data['fc_layout_nom_com_sucursal_id'];
        $select_sucursal_id = -1;

        if ((int)$fc_layout_nom_com_sucursal_id > 0) {
            $select_sucursal_id = $fc_layout_nom_com_sucursal_id;
        }

        // Configurar el link para el formulario
        $link = "index.php?seccion=fc_layout_nom&accion=modifica_sucursal_bd&registro_id={$this->registro_id}&session_id={$_GET['session_id']}";
        $this->link_modifica_sucursal_bd = $link;

        $this->inputs = new stdClass();

        $modelo_com_sucursal = new com_sucursal(link: $this->link);
        $filtro_input_select_sucursal = [];

        $input_select = $this->html->select_catalogo(cols: 12, con_registros: true, id_selected: $select_sucursal_id,
            modelo: $modelo_com_sucursal, columns_ds: ['com_sucursal_descripcion_select'],
            disabled: false, filtro: $filtro_input_select_sucursal, label: 'Sucursal',name: 'com_sucursal_id',
            registros: [], required: true
        );
        if(errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al generar input_select_sucursal',
                data: $input_select,
                header: $header, ws: $ws
            );
        }

        $this->inputs->sucursal = $input_select;
    }

    public function modifica_sucursal_bd(bool $header, bool $ws = false)
    {
        $fc_layout_nom_id = $_POST['fc_layout_nom_id'];
        $com_sucursal_id = $_POST['com_sucursal_id'];

        $rs = $this->modelo->modifica_bd(
            registro: ['com_sucursal_id' => $com_sucursal_id],
            id: $fc_layout_nom_id
        );

        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al modificar la sucursal en fc_layout_nom', data: $rs, header: $header, ws: $ws);
        }

        $_SESSION['exito'][]['mensaje'] = 'sucursal modificada exitosamente';
        $link = "index.php?seccion=fc_layout_nom&accion=lista&adm_menu_id=75";
        $link .= "&session_id={$_GET['session_id']}";
        header("Location: " . $link);
        exit;

    }

    public function ver_empleados(bool $header, bool $ws = false): array|stdClass
    {
        $fc_layout_nom = (new fc_layout_nom($this->link))->registro($this->registro_id,retorno_obj: true);
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al obtener layout', data: $fc_layout_nom, header: $header, ws: $ws);
        }

        // Título dinámico para identificar el origen de los empleados
        $descripcion_layout = $fc_layout_nom->fc_layout_nom_descripcion ?? ($fc_layout_nom->descripcion ?? '');
        $nombre_archivo = $fc_layout_nom->doc_documento_name_out ?? '';
        // Sección y título de lista usados por los templates head/title.php y head/subtitulo.php
        $this->seccion_titulo = 'Empleados del Layout';
        if ($descripcion_layout !== '') {
            $this->seccion_titulo .= ': ' . $descripcion_layout;
        }
        $this->titulo_lista = 'Archivo origen: ' . ($nombre_archivo !== '' ? $nombre_archivo : 'No disponible');

        $filtro['fc_layout_nom.id'] = $this->registro_id;
        $rs = (new fc_row_layout($this->link))->filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->retorno_error(
                mensaje: 'Error al obtener r_rows', data: $rs, header: $header, ws: $ws);
        }
        $rows = $rs->registros;
        foreach ($rows as $indice => $row) {
            $row = (object)$row;
            $btn_modifica = '';
            if( ($row->fc_row_layout_error !== '' && $row->fc_row_layout_error !== NULL) || ($row->fc_row_layout_esta_timbrado === 'activo' && $row->fc_row_layout_esta_cancelado === 'activo')){
                $params = array();
                $params['fc_row_layout_id'] = $row->fc_row_layout_id;
                $btn_modifica = (new html())->button_href(accion: 'modifica_datos',etiqueta:  'Modificar',
                    registro_id: $this->registro_id,seccion: 'fc_layout_nom',style: 'info',params: $params);

                if(errores::$error){
                    return $this->retorno_error(
                        mensaje: 'Error al obtener btn_timbra', data: $btn_modifica, header: $header, ws: $ws);
                }
            }

            $btn_retimbra = '';
            if($row->fc_row_layout_esta_timbrado === 'activo' && $row->fc_row_layout_esta_cancelado === 'activo'){
                $params = array();
                $params['fc_row_layout_id'] = $row->fc_row_layout_id;
                $btn_retimbra = (new html())->button_href(accion: 'retimbrar_recibo',etiqueta:  'RETIMBRAR RECIBO',
                    registro_id: $this->registro_id,seccion: 'fc_layout_nom',style: 'warning',params: $params);

                if(errores::$error){
                    return $this->retorno_error(
                        mensaje: 'Error al obtener btn_retimbra', data: $btn_retimbra, header: $header, ws: $ws);
                }
            }

            $btn_timbra = '';
            if($row->fc_row_layout_esta_timbrado === 'inactivo'){
                $params = array();
                $params['fc_row_layout_id'] = $row->fc_row_layout_id;
                $btn_timbra = (new html())->button_href(accion: 'timbra_recibo',etiqueta:  'Timbra',
                    registro_id: $this->registro_id,seccion: 'fc_layout_nom',style: 'warning',params: $params);

                if(errores::$error){
                    return $this->retorno_error(
                        mensaje: 'Error al obtener btn_timbra', data: $btn_timbra, header: $header, ws: $ws);
                }
            }
            $btn_descarga_xml = '';
            if($row->fc_row_layout_esta_timbrado === 'activo'){
                $params = array();
                $params['fc_row_layout_id'] = $row->fc_row_layout_id;
                $btn_descarga_xml = (new html())->button_href(accion: 'descarga_rec_xml',etiqueta:  'XML',
                    registro_id: $this->registro_id,seccion: 'fc_layout_nom',style: 'success',params: $params);

                if(errores::$error){
                    return $this->retorno_error(
                        mensaje: 'Error al obtener btn_descarga_xml', data: $btn_descarga_xml, header: $header, ws: $ws);
                }
            }


            $btn_descarga_pdf = '';
            if($row->fc_row_layout_esta_timbrado === 'activo'){
                $params = array();
                $params['fc_row_layout_id'] = $row->fc_row_layout_id;
                $btn_descarga_pdf = (new html())->button_href(accion: 'descarga_rec_pdf',etiqueta:  'PDF',
                    registro_id: $this->registro_id,seccion: 'fc_layout_nom',style: 'success',params: $params);

                if(errores::$error){
                    return $this->retorno_error(
                        mensaje: 'Error al obtener btn_descarga_pdf', data: $btn_descarga_pdf, header: $header, ws: $ws);
                }
            }

            $btn_regenera_pdf = '';
            if($row->fc_row_layout_esta_timbrado === 'activo'){
                $params = array();
                $params['fc_row_layout_id'] = $row->fc_row_layout_id;
                $btn_regenera_pdf = (new html())->button_href(accion: 'regenera_rec_pdf',etiqueta:  'REGENERA PDF',
                    registro_id: $this->registro_id,seccion: 'fc_layout_nom',style: 'success',params: $params);

                if(errores::$error){
                    return $this->retorno_error(
                        mensaje: 'Error al obtener btn_regenera_pdf', data: $btn_regenera_pdf, header: $header, ws: $ws);
                }
            }

            $btn_cancelar_recibo = '';
            if($row->fc_row_layout_esta_timbrado === 'activo' && $row->fc_row_layout_esta_cancelado === 'inactivo'){
                $params = array();
                $params['fc_row_layout_id'] = $row->fc_row_layout_id;
                $btn_cancelar_recibo = (new html())->button_href(accion: 'cancelar_recibo',etiqueta:  'CANCELAR RECIBO',
                    registro_id: $this->registro_id,seccion: 'fc_layout_nom',style: 'danger',params: $params);

                if(errores::$error){
                    return $this->retorno_error(
                        mensaje: 'Error al obtener btn_regenera_pdf', data: $btn_cancelar_recibo, header: $header, ws: $ws);
                }
            }

            $btn_descarga_zip = '';
            if($row->fc_row_layout_esta_timbrado === 'activo'){
                $params = array();
                $params['fc_row_layout_id'] = $row->fc_row_layout_id;
                $btn_descarga_zip = (new html())->button_href(accion: 'descarga_rec_zip',etiqueta:  'ZIP',
                    registro_id: $this->registro_id,seccion: 'fc_layout_nom',style: 'success',params: $params);

                if(errores::$error){
                    return $this->retorno_error(
                        mensaje: 'Error al obtener btn_descarga_pdf', data: $btn_descarga_pdf, header: $header, ws: $ws);
                }
            }

            $row->btn_descarga_zip = $btn_descarga_zip;
            $row->btn_descarga_pdf = $btn_descarga_pdf;
            $row->btn_regenera_pdf = $btn_regenera_pdf;
            $row->btn_descarga_xml = $btn_descarga_xml;
            $row->btn_cancelar_recibo = $btn_cancelar_recibo;
            $row->btn_timbra = $btn_timbra;
            $row->btn_retimbra = $btn_retimbra;
            $row->btn_modifica = $btn_modifica;
            $rows[$indice] = $row;
        }

        // Agregar botón para modificar fecha de emisión del layout completo
        $btn_modifica_fecha_emision = '';
        $estado_timbrado = $fc_layout_nom->fc_layout_nom_estado_timbrado ?? $fc_layout_nom->estado_timbrado ?? 'SIN TIMBRAR';
        if ($estado_timbrado !== 'TIMBRADO') {
            $btn_modifica_fecha_emision = (new html())->button_href(
                accion: 'modifica_fecha_emision',
                etiqueta: 'Modificar Fecha Emisión',
                registro_id: $this->registro_id,
                seccion: 'fc_layout_nom',
                style: 'primary'
            );

            if (errores::$error) {
                return $this->retorno_error(
                    mensaje: 'Error al obtener btn_modifica_fecha_emision', 
                    data: $btn_modifica_fecha_emision, header: $header, ws: $ws);
            }
        }

        $this->btn_modifica_fecha_emision = $btn_modifica_fecha_emision;
        $this->fc_rows_layout = $rows;

        return $rows;

    }

    public function timbra_recibo(bool $header, bool $ws = false): array|stdClass
    {
        if($this->registro_id <= 178){
       //     $error = (new errores())->error(mensaje: 'Error timbrado version anterior', data: $this->registro_id);
       //     print_r($error);
       //     exit;
        }

        $rs = $this->valida_timbrado(fc_layout_nom_id: $this->registro_id);
        if(errores::$error) {
            $error = (new errores())->error("Error al valida_timbrado", $rs);
            print_r($error);
            exit;
        }

        $result = (new _timbra_nomina())->timbra_recibo(link: $this->link,fc_row_layout_id:  $_GET['fc_row_layout_id']);
        if(errores::$error) {
            $error = (new errores())->error("Error al timbrar", $result);
            print_r($error);
            exit;
        }

        header("Location: " . $_SERVER['HTTP_REFERER']);
        return $result->datos_rec->fc_row_layout;


    }

    public function reporte_facturacion(bool $header, bool $ws = false)
    {
        $link = "index.php?seccion=fc_layout_nom&accion=reporte_facturacion_bd&registro_id={$this->registro_id}&session_id={$_GET['session_id']}";
        $this->link_reporte_facturacion_bd = $link;

        $this->inputs = new stdClass();

        $fecha_actual = new DateTime();
        $fecha_semana_antes = (clone $fecha_actual)->modify('-1 week');

        $fecha_fin_valor = $fecha_actual->format('Y-m-d');
        $fecha_inicio_valor = $fecha_semana_antes->format('Y-m-d');

        $fecha_inicio = $this->html->input_fecha(
            cols: 6,
            row_upd: new stdClass(),
            value_vacio: false,
            name: 'fecha_inicio',
            place_holder: 'Fecha Inicio',
            value: $fecha_inicio_valor,
        );
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al obtener inputs', data: $fecha_inicio, header: $header, ws: $ws);
        }

        $fecha_fin = $this->html->input_fecha(
            cols: 6,
            row_upd: new stdClass(),
            value_vacio: false,
            name: 'fecha_fin',
            place_holder: 'Fecha Fin',
            value: $fecha_fin_valor,
        );
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al obtener inputs', data: $fecha_fin, header: $header, ws: $ws);
        }

        $this->inputs->fecha_inicio = $fecha_inicio;
        $this->inputs->fecha_fin = $fecha_fin;
    }

    public function reporte_facturacion_bd(bool $header, bool $ws = false)
    {
        $fecha_inicial = $_POST['fecha_inicio'] ?? null;
        $fecha_fin     = $_POST['fecha_fin'] ?? null;

        if (!$fecha_inicial || !$fecha_fin) {
            return $this->retorno_error(
                mensaje: 'Fechas no proporcionadas',
                data: $_POST,
                header: $header,
                ws: $ws
            );
        }

        $registros = (new fc_factura($this->link))->obtener_registros_reporte_facturacion(request: $_POST);
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al obtener registros para reporte_facturacion',
                data: $registros, header: $header, ws: $ws
            );
        }

        $spreadsheet = (new _reporte_facturacion(registros: $registros))->descarga_reporte();

        $fi = DateTime::createFromFormat('Y-m-d', $fecha_inicial);
        $ff = DateTime::createFromFormat('Y-m-d', $fecha_fin);

        $filename = sprintf(
            'Reporte_Facturacion_%s_al_%s.xlsx',
            $fi->format('Ymd'),
            $ff->format('Ymd')
        );

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header('Cache-Control: max-age=0');
        header('Expires: 0');
        header('Pragma: public');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;

    }

    public function reporte_ventas_por_operador(bool $header, bool $ws = false)
    {
        $link = "index.php?seccion=fc_layout_nom&accion=reporte_ventas_por_operador_bd&registro_id={$this->registro_id}&session_id={$_GET['session_id']}";
        $this->link_reporte_ventas_por_operador_bd = $link;

        $this->inputs = new stdClass();

        $fecha_actual = new DateTime();
        $fecha_semana_antes = (clone $fecha_actual)->modify('-1 week');

        $fecha_fin_valor = $fecha_actual->format('Y-m-d');
        $fecha_inicio_valor = $fecha_semana_antes->format('Y-m-d');

        $fecha_inicio = $this->html->input_fecha(
            cols: 6,
            row_upd: new stdClass(),
            value_vacio: false,
            name: 'fecha_inicio',
            place_holder: 'Fecha Inicio',
            value: $fecha_inicio_valor,
        );
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al obtener inputs', data: $fecha_inicio, header: $header, ws: $ws);
        }

        $fecha_fin = $this->html->input_fecha(
            cols: 6,
            row_upd: new stdClass(),
            value_vacio: false,
            name: 'fecha_fin',
            place_holder: 'Fecha Fin',
            value: $fecha_fin_valor,
        );
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al obtener inputs', data: $fecha_fin, header: $header, ws: $ws);
        }

        $this->inputs->fecha_inicio = $fecha_inicio;
        $this->inputs->fecha_fin = $fecha_fin;
    }

    public function reporte_ventas_por_operador_bd(bool $header, bool $ws = false)
    {
        $fecha_inicial = $_POST['fecha_inicio'] ?? null;
        $fecha_fin     = $_POST['fecha_fin'] ?? null;

        if (!$fecha_inicial || !$fecha_fin) {
            return $this->retorno_error(
                mensaje: 'Fechas no proporcionadas',
                data: $_POST,
                header: $header,
                ws: $ws
            );
        }

        $registros = (new fc_factura($this->link))->obtener_registros_reporte_ventas(request: $_POST);
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al obtener registros para reporte_facturacion',
                data: $registros, header: $header, ws: $ws
            );
        }

        $operadores = (new com_agente($this->link))->obtener_operadores();
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al obtener registros para reporte_facturacion',
                data: $registros, header: $header, ws: $ws
            );
        }

        $spreadsheet = (new _reporte_ventas(registros: $registros, operadores: $operadores))
            ->descarga_reporte();

        $fi = DateTime::createFromFormat('Y-m-d', $fecha_inicial);
        $ff = DateTime::createFromFormat('Y-m-d', $fecha_fin);

        $filename = sprintf(
            'Reporte_Ventas_Por_Operador_%s_al_%s.xlsx',
            $fi->format('Ymd'),
            $ff->format('Ymd')
        );

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header('Cache-Control: max-age=0');
        header('Expires: 0');
        header('Pragma: public');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;



    }

    public function retimbrar_recibo(bool $header, bool $ws = false): array|stdClass
    {
        $result = (new _timbra_nomina())->retimbra_recibo(link: $this->link,fc_row_layout_id:  $_GET['fc_row_layout_id']);
        if(errores::$error) {
            $error = (new errores())->error("Error al retimbrar", $result);
            print_r($error);
            exit;
        }

        header("Location: " . $_SERVER['HTTP_REFERER']);
        return $result->datos_rec->fc_row_layout;


    }

    public function timbra_recibos(bool $header, bool $ws = false): array|stdClass
    {

        if($this->registro_id <= 178){
            //$error = (new errores())->error(mensaje: 'Error timbrado version anterior', data: $this->registro_id);
            //print_r($error);
            //exit;
        }

        $rs = $this->valida_timbrado(fc_layout_nom_id: $this->registro_id);
        if(errores::$error) {
            $error = (new errores())->error("Error al valida_timbrado", $rs);
            print_r($error);
            exit;
        }

        $filtro['fc_layout_nom.id'] = $this->registro_id;
        $filtro['fc_row_layout.esta_timbrado'] = 'inactivo';

        $r_rows = (new fc_row_layout($this->link))->filtro_and(filtro: $filtro);
        if(errores::$error) {
            $error = (new errores())->error("Error al obtener registros", $r_rows);
            print_r($error);
            exit;
        }
        $rows = $r_rows->registros;

        $nombre_original_excel = $rows[0]['fc_layout_nom_descripcion'];

        foreach ($rows as $row) {
            $timbra = (new _timbra_nomina())->timbra_recibo(link: $this->link,fc_row_layout_id:  $row['fc_row_layout_id']);
            if(errores::$error) {
                (new errores())->error("Error al timbrar", $timbra);
                errores::$error = false;
            }
        }

        $spreadsheet = $this->generar_spreadsheet(fc_layout_nom_id: $this->registro_id);
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al generar spreadsheet', data: $spreadsheet, header: $header, ws: $ws);
        }

        if (ob_get_length()) {
            ob_end_clean();
        }

        $info = pathinfo($nombre_original_excel);
        $nuevo_nombre_excel = $info['filename'] . '_TIMBRADO.' . $info['extension'];

        $filename = $nuevo_nombre_excel;
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0, no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');

        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false);
        $writer->save('php://output');
        exit;
        print_r($rows);exit;

        header("Location: " . $_SERVER['HTTP_REFERER']);
        return $rows;

    }

    public function retimbrar_recibos(bool $header, bool $ws = false): array|stdClass
    {
        $filtro['fc_layout_nom.id'] = $this->registro_id;
        $filtro['fc_row_layout.esta_timbrado'] = 'activo';
        $filtro['fc_row_layout.esta_cancelado'] = 'activo';

        $r_rows = (new fc_row_layout($this->link))->filtro_and(filtro: $filtro);
        if(errores::$error) {
            $error = (new errores())->error("Error al obtener registros", $r_rows);
            print_r($error);
            exit;
        }

        if ($r_rows->n_registros === 0) {
            $error = (new errores())->error("Error no existen registros que retimbrar", $r_rows);
            print_r($error);
            exit;
        }

        $rows = $r_rows->registros;

        foreach ($rows as $row) {
            $retimbra = (new _timbra_nomina())->retimbra_recibo(link: $this->link,fc_row_layout_id:  $row['fc_row_layout_id']);
            if(errores::$error) {
                (new errores())->error("Error al retimbra_recibo", $retimbra);
                errores::$error = false;
            }
        }

        header("Location: " . $_SERVER['HTTP_REFERER']);
        return $rows;

    }

    public function descarga_timbres(bool $header, bool $ws = false)
    {
        $zip = $this->obten_ruta_nombre_zip(fc_layout_nom_id: $this->registro_id);
        if(errores::$error) {
            $error = (new errores())->error("Error al obtener ruta y nombre del zip", $zip);
            print_r($error);
            exit;
        }

        $tmpZip = $zip['ruta'];
        $zipName = $zip['nombre_archivo'];

        // Limpiamos buffers
        while (ob_get_level() > 0) { ob_end_clean(); }

        // Cabeceras para descarga
        header('Content-Type: application/zip');
        header("Content-Disposition: attachment; filename*=UTF-8''" . rawurlencode($zipName));
        header('Content-Length: ' . filesize($tmpZip));
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('X-Accel-Buffering: no');

        // Enviamos el archivo
        $fp = fopen($tmpZip, 'rb');
        $chunkSize = 1024 * 1024; // 1MB
        while (!feof($fp)) {
            echo fread($fp, $chunkSize);
            flush();
        }
        fclose($fp);

        unlink($tmpZip);
        exit;

    }

    private function generar_spreadsheet(int $fc_layout_nom_id): Spreadsheet|array
    {
        $filtro = array();
        $filtro['fc_layout_nom.id'] = $fc_layout_nom_id;

        $r_rows = (new fc_row_layout($this->link))->filtro_and(filtro: $filtro);
        if(errores::$error) {
            return (new errores())->error("Error al obtener registros", $r_rows);
        }

        $rows = $r_rows->registros;

        $filas_exito = array();
        foreach ($rows as $row) {
            if($row['fc_row_layout_esta_timbrado'] === 'inactivo'){
                continue;
            }
            $row_exito = [];
            $row_exito[] = $row['fc_row_layout_rfc'];
            $row_exito[] = $row['fc_row_layout_nss'];
            $row_exito[] = $row['fc_row_layout_curp'];
            $row_exito[] = $row['fc_row_layout_cp'];
            $row_exito[] = $row['fc_row_layout_nombre_completo'];
            $row_exito[] = $row['fc_row_layout_uuid'];
            $filas_exito[] = $row_exito;
        }

        $filas_error = array();
        foreach ($rows as $row) {
            if(!isset($row['fc_row_layout_error'])){
                $row['fc_row_layout_error'] = '';
            }

            if(trim($row['fc_row_layout_error']) === ''){
                continue;
            }
            $row_error = [];
            $row_error[] = $row['fc_row_layout_rfc'];
            $row_error[] = $row['fc_row_layout_nss'];
            $row_error[] = $row['fc_row_layout_curp'];
            $row_error[] = $row['fc_row_layout_cp'];
            $row_error[] = $row['fc_row_layout_nombre_completo'];
            $row_error[] = $row['fc_row_layout_error'];
            $filas_error[] = $row_error;
        }

        $ss = new Spreadsheet();
        $hoja = $ss->getActiveSheet();
        $hoja->setTitle('EXITO');

        $encabezados = ['RFC', 'NSS', 'CURP', 'CP','NOMBRE COMPLETO','UUID'];
        $hoja->fromArray($encabezados, null, 'A1');

        $ultimaCol = Coordinate::stringFromColumnIndex(count($encabezados));
        $rangoEnc = "A1:{$ultimaCol}1";
        $hoja->getStyle($rangoEnc)->getFont()->setBold(true);
        $hoja->getStyle($rangoEnc)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        if ($filas_exito !== []) {
            $hoja->fromArray($filas_exito, null, 'A2', true);
        }

        foreach (range('A', $hoja->getHighestDataColumn()) as $col) {
            $hoja->getColumnDimension($col)->setAutoSize(true);
        }
        $hoja->freezePane('A2');

        $hoja = $ss->createSheet();
        $hoja->setTitle('ERROR');
        $encabezados = ['RFC', 'NSS', 'CURP', 'CP','NOMBRE COMPLETO','ERROR'];
        $hoja->fromArray($encabezados, null, 'A1');

        $ultimaCol = Coordinate::stringFromColumnIndex(count($encabezados));
        $rangoEnc = "A1:{$ultimaCol}1";
        $hoja->getStyle($rangoEnc)->getFont()->setBold(true);
        $hoja->getStyle($rangoEnc)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        if ($filas_error !== []) {
            $hoja->fromArray($filas_error, null, 'A2', true);
        }

        foreach (range('A', $hoja->getHighestDataColumn()) as $col) {
            $hoja->getColumnDimension($col)->setAutoSize(true);
        }

        $hoja->freezePane('A2');

        $ss->getProperties()
            ->setCreator('Sistema de Facturación')
            ->setTitle('Reporte XLSX')
            ->setDescription('Archivo generado automáticamente');

        return $ss;
    }

    private function subir_qr(string $string_qr_jpg, int $fc_row_layout_id): array
    {
        $nombre_archivo = $fc_row_layout_id.'.jpg';
        $ruta = (new generales())->path_base.'archivos/'.$nombre_archivo;

        $registro['doc_tipo_documento_id'] = 3;
        $file = array();
        $file['name'] = $nombre_archivo;
        $file['tmp_name'] = $ruta;
        file_put_contents($ruta, $string_qr_jpg);

        $alta = (new doc_documento(link: $this->link))->alta_documento(registro: $registro,file: $file);
        if(errores::$error){
            return (new errores())->error('Error al insertar', $alta);


        }

        unlink($ruta);
        $doc_documento_id = $alta->registro_id;

        $fc_row_nomina_registro = [
            'doc_documento_id' => $doc_documento_id,
            'fc_row_layout_id' => $fc_row_layout_id,
        ];

        $fc_row_nomina_modelo = new fc_row_nomina($this->link);
        $fc_row_nomina_modelo->registro = $fc_row_nomina_registro;
        $result = $fc_row_nomina_modelo->alta_bd();
        if(errores::$error){
            return (new errores())->error('Error al insertar fc_row_nomina', $result);
        }

        return [];
    }

    private function upd_total_dispersion_fc_layout_nom(int $fc_layout_nom_id): array
    {
        $sql = "UPDATE fc_layout_nom
                SET total_dispersion = (
                    SELECT IFNULL(SUM(neto_depositar), 0)
                    FROM fc_row_layout
                    WHERE fc_layout_nom_id = fc_layout_nom.id
                )
                WHERE id = $fc_layout_nom_id ";
        $rs = modelo::ejecuta_transaccion($sql, $this->link);
        if(errores::$error) {
            return (new errores())->error("Error al actualizar upd_total_dispersion_fc_layout_nom", $rs);
        }
        return [];
    }

    private function valida_timbrado(int $fc_layout_nom_id): array
    {
        $fc_layout_nom_modelo = new fc_layout_nom($this->link);
        $fc_layout_nom_modelo->registro_id = $fc_layout_nom_id;
        $data = $fc_layout_nom_modelo->obten_data();

        $fc_layout_nom_estado_layout = $data['fc_layout_nom_estado_layout'];

        if ($fc_layout_nom_estado_layout !== controlador_fc_layout_nom::ESTADO_LAYOUT_PAGADO) {
            return (new errores())->error(
                "Error no se puede timbrar si el Layout no esta ". controlador_fc_layout_nom::ESTADO_LAYOUT_PAGADO,
                []
            );
        }

        return [];
    }
    private function valida_envio(int $fc_layout_nom_id): array
    {
        $fc_layout_nom_modelo = new fc_layout_nom($this->link);
        $fc_layout_nom_modelo->registro_id = $fc_layout_nom_id;
        $data = $fc_layout_nom_modelo->obten_data();

        $fc_layout_nom_estado_layout = $data['fc_layout_nom_estado_layout'];
        $fc_layout_nom_estado_timbrado = $data['fc_layout_nom_estado_timbrado'];

        if ($fc_layout_nom_estado_layout !== controlador_fc_layout_nom::ESTADO_LAYOUT_PAGADO) {
            return (new errores())->error(
                "Error no se puede enviar si el Layout no esta ". controlador_fc_layout_nom::ESTADO_LAYOUT_PAGADO,
                []
            );
        }

        if ($fc_layout_nom_estado_timbrado !== controlador_fc_layout_nom::ESTADO_TIMBRADO) {
            return (new errores())->error(
                "Error no se puede enviar si el Layout no esta ". controlador_fc_layout_nom::ESTADO_TIMBRADO,
                []
            );
        }

        return [];
    }

    private function obten_ruta_nombre_zip(int $fc_layout_nom_id): array
    {
        $filtro['fc_layout_nom.id'] = $fc_layout_nom_id;
        $filtro['fc_row_layout.esta_timbrado'] = 'activo';

        $r_rows = (new fc_row_layout($this->link))->filtro_and(filtro: $filtro);

        if(errores::$error) {
            return (new errores())->error(
                mensaje: 'Error al obtener registros',
                data: $r_rows
            );
        }

        $rows = $r_rows->registros;

        if (count($rows) === 0) {
            return (new errores())->error("Error el layout no tiene registros timbrados", $r_rows);
        }

        $archivos = [];
        foreach ($rows as $row) {
            $fc_row_layout_id = $row['fc_row_layout_id'];
            $filtro['fc_row_nomina.fc_row_layout_id'] =$fc_row_layout_id;
            $filtro['fc_row_nomina.status'] = 'activo';
            $result = (new fc_row_nomina($this->link))->filtro_and(filtro: $filtro);
            if(errores::$error) {
                return (new errores())->error("Error en filtro_and de fc_row_nomina", $result);
            }
            $docs = $result->registros;
            foreach ($docs as $doc) {
                $archivo = [
                    'ruta' => $doc['doc_documento_ruta_absoluta'],
                    'rfc' => $doc['fc_row_layout_rfc'],
                    'fecha_pago' => $doc['fc_row_layout_fecha_pago'],
                ];
                $archivos[] = $archivo;
            }

        }//  end foreach ($rows as $row)

        // --- Guardar XLSX en archivo temporal ---

        $spreadsheet = $this->generar_spreadsheet(fc_layout_nom_id: $this->registro_id);
        if (errores::$error) {
            return (new errores())->error("Error al generar spreadsheet", $spreadsheet);
        }

        $nombre_original_excel = $rows[0]['fc_layout_nom_descripcion'];
        $info = pathinfo($nombre_original_excel);
        $nombre_zip = $this->registro_id.'.'.$info['filename'] . '.zip';
        $nuevo_nombre_excel = $info['filename'] . '_TIMBRADO.' . $info['extension'];

        $tmpDir = sys_get_temp_dir();
        $xlsxName = $nuevo_nombre_excel;
        $xlsxPath = $tmpDir . DIRECTORY_SEPARATOR . $xlsxName;

        $writer = new Xlsx($spreadsheet);
        $writer->save($xlsxPath);

        // ---Finaliza Guardar XLSX en archivo temporal ---

        $zipName = $nombre_zip;
        $tmpZip = tempnam(sys_get_temp_dir(), 'zip_');

        $zip = new ZipArchive();
        if ($zip->open($tmpZip, ZipArchive::OVERWRITE) !== true) {
            return (new errores())->error("Error al generar archivo zip", []);
        }

        // Guardamos el excel dentro del ZIP
        $nombreDentroZip = $nuevo_nombre_excel;
        $zip->addFile($xlsxPath, $nombreDentroZip);

        foreach ($archivos as $item) {
            $ruta = $item['ruta'];
            if (!file_exists($ruta)) {
                continue;
            }

            // Obtenemos la extensión del archivo original
            $extension = pathinfo($ruta, PATHINFO_EXTENSION);

            // Construimos el nombre con el formato RFC_FECHA.ext
            $nombreArchivo = $item['rfc'] . '_' . $item['fecha_pago'] . '.' . $extension;

            // Agregamos al ZIP con el nuevo nombre
            $zip->addFile($ruta, $nombreArchivo);
        }

        $zip->close();
        unlink($xlsxPath);

        return [
            'ruta' => $tmpZip,
            'nombre_archivo' => $zipName,
        ];
    }

    private function enviar_nomina_empleados_fc_layout_nom_id(int $fc_layout_nom_id)
    {
        $rs = $this->valida_envio(fc_layout_nom_id: $fc_layout_nom_id);
        if(errores::$error) {
            return (new errores())->error("Error al envia_nominas", $rs);
        }

        $filtro['fc_layout_nom.id'] = $fc_layout_nom_id;
        $filtro['fc_row_layout.nomina_enviada'] = 'inactivo';

        $r_rows = (new fc_row_layout($this->link))->filtro_and(filtro: $filtro);
        if(errores::$error) {
            $error = (new errores())->error("Error al obtener registros", $r_rows);
            print_r($error);
            exit;
        }
        $rows = $r_rows->registros;

        $n_correos_enviados = 0;
        foreach ($rows as $row) {
            $enviar_nomina = (new fc_row_layout($this->link))->envia_nomina(
                fc_row_layout_id: $row['fc_row_layout_id']
            );
            if(errores::$error) {
                (new errores())->error("Error al envia_nomina", $enviar_nomina);
                errores::$error = false;
            }
            if ((int)count($enviar_nomina) > 0) {
                $n_correos_enviados++;
            }
        }

        return (int)$n_correos_enviados;
    }

    private function enviar_nomina_cliente_fc_layout_nom_id(int $fc_layout_nom_id): array|int
    {
        $fc_layout_nom_modelo = new fc_layout_nom($this->link);
        $rs = $this->valida_envio(fc_layout_nom_id: $fc_layout_nom_id);
        if(errores::$error) {
            return (new errores())->error("Error al envia_nomina_cliente", $rs);
        }

        $correos = $fc_layout_nom_modelo->obten_correos_contactos_cliente(
            fc_layout_nom_id: $fc_layout_nom_id
        );
        if(errores::$error) {
            return (new errores())->error("Error al obtener correos", $correos);
        }

        $fc_layout_nom_modelo->registro_id = $this->registro_id;
        $data = $fc_layout_nom_modelo->obten_data();
        if(errores::$error) {
            return (new errores())->error("Error en obten_data de fc_layout_nom", $data);
        }

        $com_sucursal_nombre_contacto = $data['com_sucursal_nombre_contacto'];
        $fc_layout_nom_fecha_pago = $data['fc_layout_nom_fecha_pago'];

        $asunto = "Nomina $fc_layout_nom_fecha_pago $com_sucursal_nombre_contacto";


        $zip = $this->obten_ruta_nombre_zip(fc_layout_nom_id: $this->registro_id);
        if(errores::$error) {
            return (new errores())->error("Error al obtener ruta y nombre del zip", $zip);
        }

        $adjuntos[] = [
            'doc_documento_ruta_absoluta' => $zip['ruta'],
            'not_adjunto_name_out' => $zip['nombre_archivo'],
        ];

        foreach ($correos as $correo) {
            $result = (new _email_nomina_cliente())->enviar_nomina_cliente(
                asunto: $asunto,
                correo: $correo,
                adjuntos: $adjuntos,
                link: $this->link
            );

            if(errores::$error) {
                return (new errores())->error("Error al enviar correo a $correo", $result);
            }
        }

        unlink($zip['ruta']);
        return count($correos);

    }



}
