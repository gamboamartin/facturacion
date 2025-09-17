<?php
namespace gamboamartin\facturacion\controllers;

use config\generales;
use gamboamartin\documento\models\doc_documento;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\html\fc_layout_nom_html;
use gamboamartin\facturacion\models\_timbra_nomina;
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

    public stdClass|array $keys_selects = array();
    public array $fc_rows_layout = array();

    public string $link_modifica_datos_bd = '';
    public bool $disabled = false;
    public string $fecha_emision = '';

    public function __construct(PDO $link, html $html = new html(), stdClass $paths_conf = new stdClass()){
        $modelo = new fc_layout_nom(link: $link);
        $html_ = new fc_layout_nom_html(html: $html);
        $obj_link = new links_menu(link: $link, registro_id:  $this->registro_id);

        $this->rows_lista = array('id', 'codigo', 'descripcion','estado_timbrado','fecha_pago');

        parent::__construct(html:$html_, link: $link,modelo:  $modelo, obj_link: $obj_link,
            paths_conf: $paths_conf);

        $this->lista_get_data = true;

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

        return $alta;
    }

    public function alta_bd(bool $header, bool $ws = false): array|stdClass
    {

        $r_alta = parent::alta_bd($header, $ws);
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al guardar', data: $r_alta, header: $header, ws: $ws);
        }
        return $r_alta;

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

    public function genera_dispersion(bool $header, bool $ws = false)
    {
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

        $datos = (new _xls_dispersion())->lee_layout_base(fc_layout_nom: $fc_layout_nom);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener $datos', data: $datos,
                header: $header, ws: $ws);
        }

        $xls = (new _xls_dispersion())->write_dispersion(hoja_base: $datos->hoja,
            layout_dispersion: $datos->layout_dispersion);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar $layout_dispersion', data: $xls,
                header: $header, ws: $ws);
        }

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
        if ($fc_row_layout->fc_row_layout_esta_timbrado === 'activo') {
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
        if ($fc_row_layout->fc_row_layout_esta_timbrado === 'activo') {
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

    public function ver_empleados(bool $header, bool $ws = false): array|stdClass
    {
        $fc_layout_nom = (new fc_layout_nom($this->link))->registro($this->registro_id,retorno_obj: true);
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al obtener layout', data: $fc_layout_nom, header: $header, ws: $ws);
        }

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
            if($row->fc_row_layout_error !== '' && $row->fc_row_layout_error !== NULL){
                $params = array();
                $params['fc_row_layout_id'] = $row->fc_row_layout_id;
                $btn_modifica = (new html())->button_href(accion: 'modifica_datos',etiqueta:  'Modificar',
                    registro_id: $this->registro_id,seccion: 'fc_layout_nom',style: 'info',params: $params);

                if(errores::$error){
                    return $this->retorno_error(
                        mensaje: 'Error al obtener btn_timbra', data: $btn_modifica, header: $header, ws: $ws);
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
            $row->btn_descarga_xml = $btn_descarga_xml;
            $row->btn_timbra = $btn_timbra;
            $row->btn_modifica = $btn_modifica;
            $rows[$indice] = $row;
        }

        $this->fc_rows_layout = $rows;

        return $rows;

    }

    public function timbra_recibo(bool $header, bool $ws = false): array|stdClass
    {
        if($this->registro_id <= 178){
            $error = (new errores())->error(mensaje: 'Error timbrado version anterior', data: $this->registro_id);
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

    public function timbra_recibos(bool $header, bool $ws = false): array|stdClass
    {

        if($this->registro_id <= 178){
            $error = (new errores())->error(mensaje: 'Error timbrado version anterior', data: $this->registro_id);
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

    public function descarga_timbres(bool $header, bool $ws = false)
    {
        $filtro['fc_layout_nom.id'] = $this->registro_id;
        $filtro['fc_row_layout.esta_timbrado'] = 'activo';

        $r_rows = (new fc_row_layout($this->link))->filtro_and(filtro: $filtro);
        if(errores::$error) {
            $error = (new errores())->error("Error al obtener registros", $r_rows);
            print_r($error);
            exit;
        }

        $rows = $r_rows->registros;

        if (count($rows) === 0) {
            $error = (new errores())->error("Error el layout no tiene registros timbrados", $r_rows);
            print_r($error);
            exit;
        }

        $archivos = [];
        foreach ($rows as $row) {
            $fc_row_layout_id = $row['fc_row_layout_id'];
            $filtro['fc_row_nomina.fc_row_layout_id'] =$fc_row_layout_id;
            $result = (new fc_row_nomina($this->link))->filtro_and(filtro: $filtro);
            if(errores::$error) {
                $error = (new errores())->error("Error en filtro_and de fc_row_nomina", $result);
                print_r($error);
                exit;
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
            return $this->retorno_error(
                mensaje: 'Error al generar spreadsheet', data: $spreadsheet, header: $header, ws: $ws);
        }

        $nombre_original_excel = $rows[0]['fc_layout_nom_descripcion'];
        $info = pathinfo($nombre_original_excel);
        $nombre_zip = $info['filename'] . '.zip';
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
            return $this->retorno_error(
                mensaje: 'Error al generar archivo zip', data: [], header: $header, ws: $ws);
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
        unlink($xlsxPath);
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






}
