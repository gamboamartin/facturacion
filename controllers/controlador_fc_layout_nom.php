<?php
namespace gamboamartin\facturacion\controllers;

use gamboamartin\errores\errores;
use gamboamartin\facturacion\html\fc_layout_nom_html;
use gamboamartin\facturacion\models\fc_layout_nom;
use gamboamartin\facturacion\models\fc_row_layout;
use gamboamartin\facturacion\pac\_cnx_pac;
use gamboamartin\system\links_menu;
use gamboamartin\system\system;
use gamboamartin\template_1\html;
use JetBrains\PhpStorm\NoReturn;
use PDO;
use stdClass;

class controlador_fc_layout_nom extends system{

    public stdClass|array $keys_selects = array();
    public array $fc_rows_layout = array();

    public function __construct(PDO $link, html $html = new html(), stdClass $paths_conf = new stdClass()){
        $modelo = new fc_layout_nom(link: $link);
        $html_ = new fc_layout_nom_html(html: $html);
        $obj_link = new links_menu(link: $link, registro_id:  $this->registro_id);

        $this->rows_lista = array('id', 'codigo', 'descripcion','fecha_pago');

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
            $row->btn_timbra = $btn_timbra;
            $rows[$indice] = $row;
        }

        $this->fc_rows_layout = $rows;

        return $rows;

    }

    public function timbra_recibo(bool $header, bool $ws = false): array|stdClass
    {

        
        $fc_layout_nom = (new fc_layout_nom($this->link))->registro($this->registro_id,retorno_obj: true);
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al obtener layout', data: $fc_layout_nom, header: $header, ws: $ws);
        }

        $fc_row_layout = (new fc_row_layout($this->link))->registro($_GET['fc_row_layout_id'],retorno_obj: true);
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al obtener layout', data: $fc_row_layout, header: $header, ws: $ws);
        }

        $result = (new _make_json(link: $this->link,fc_row_layout:  $fc_row_layout))->getJson();

        $nomina_json = $result['json'];
        $jsonB64 = base64_encode( $nomina_json);

        $keyPEM = file_get_contents('/var/www/html/facturacion/pac/CSD_EKU9003173C9_key.pem');
        $cerPEM = file_get_contents('/var/www/html/facturacion/pac/CSD_EKU9003173C9_cer.pem');
        $plantilla = 'nomina';

        $rs = (new _cnx_pac())->operacion_timbrarJSON2($jsonB64, $keyPEM, $cerPEM, $plantilla);
        $rs = json_decode($rs, false);
        $codigo = trim($rs->codigo);
        if($codigo !== '200'){
            $JSON = json_decode($nomina_json,false);
            $extra_data = '';
            if($codigo === 'CFDI40145'){
                $extra_data ="RFC: {$JSON->Comprobante->Receptor->Rfc}";
                $extra_data .=" Nombre: {$JSON->Comprobante->Receptor->Nombre}";
            }

            if($codigo === '307'){
                print_r($rs);exit;
            }
            else {

                $error = (new errores())->error("Error al timbrar $rs->mensaje Code: $rs->codigo $extra_data", $rs);
                print_r($error);
                echo $nomina_json . "<br>";
                exit;
            }
        }

        $datos = $rs->datos;
        $datos = json_decode($datos,false);
        $xml = $datos->XML;
        $pdf = base64_decode($datos->PDF);
        $uuid = $datos->UUID;

        file_put_contents("/var/www/html/facturacion/archivos/$_GET[fc_row_layout_id].pdf", $pdf);
        file_put_contents("/var/www/html/facturacion/archivos/$_GET[fc_row_layout_id].xml", $xml);
        print_r($rs);exit;
        exit;

        return $fc_row_layout;


    }



}
