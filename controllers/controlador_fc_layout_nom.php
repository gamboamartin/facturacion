<?php
namespace gamboamartin\facturacion\controllers;

use config\generales;
use config\pac;
use gamboamartin\documento\models\doc_documento;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\html\fc_layout_nom_html;
use gamboamartin\facturacion\models\_timbra_nomina;
use gamboamartin\facturacion\models\fc_cer_csd;
use gamboamartin\facturacion\models\fc_cer_pem;
use gamboamartin\facturacion\models\fc_cfdi_sellado_nomina;
use gamboamartin\facturacion\models\fc_empleado;
use gamboamartin\facturacion\models\fc_key_csd;
use gamboamartin\facturacion\models\fc_key_pem;
use gamboamartin\facturacion\models\fc_layout_nom;
use gamboamartin\facturacion\models\fc_row_layout;
use gamboamartin\facturacion\models\fc_row_nomina;
use gamboamartin\facturacion\pac\_cnx_pac;
use gamboamartin\modelo\modelo;
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

    private function cfdi_exito(stdClass $rs, stdClass $fc_row_layout): true|array
    {
        $datos = $rs->datos;
        $datos = json_decode($datos,false);
        $xml = $datos->XML;
        $pdf = base64_decode($datos->PDF);
        $uuid = $datos->UUID;

        $rs_sellos = $this->llenar_fc_cfdi_sellado_nomina(string_xml:  $xml, fc_row_layout: $fc_row_layout);
        if (errores::$error) {
            return (new errores())->error(mensaje: 'Error en llenar_fc_cfdi_sellado_nomina', data: $rs_sellos);
        }
        $docs = $this->subir_docs_timbre(pdf: $pdf,xml:  $xml,fc_row_layout:  $fc_row_layout);
        if (errores::$error) {
            return (new errores())->error(mensaje: 'Error al subir docs', data: $docs);
        }
        $rs = $this->upd_exito($uuid);
        if(errores::$error) {
            return (new errores())->error("Error al actualizar UUID", $rs);
        }

        $fc_empleado_modelo = new fc_empleado($this->link);

        $fc_empleado_upd['validado_sat'] = 'activo';
        $fc_empleado_upd['nombre_completo'] = $fc_row_layout->fc_row_layout_nombre_completo;
        $fc_empleado_upd['rfc'] = $fc_row_layout->fc_row_layout_rfc;
        $fc_empleado_upd['cp'] = $fc_row_layout->fc_row_layout_cp;
        $fc_empleado_upd['nss'] = $fc_row_layout->fc_row_layout_nss;
        $fc_empleado_upd['curp'] = $fc_row_layout->fc_row_layout_curp;
        $fc_empleado_upd['banco'] = $fc_row_layout->fc_row_layout_banco;
        $fc_empleado_upd['cuenta'] = $fc_row_layout->fc_row_layout_cuenta;
        $fc_empleado_upd['tarjeta'] = $fc_row_layout->fc_row_layout_tarjeta;

        $rs_upd = $fc_empleado_modelo->modifica_bd($fc_empleado_upd, $fc_row_layout->fc_row_layout_fc_empleado_id);
        if(errores::$error) {
            return (new errores())->error("Error al actualizar empleado", $rs_upd);
        }

        return $rs;

    }

    private function code_error(string $codigo, stdClass $rs, string $nomina_json)
    {
        if($codigo !== '200'){

            $JSON = json_decode($nomina_json,false);
            $extra_data = '';
            if($codigo === 'CFDI40145'){
                $extra_data ="RFC: {$JSON->Comprobante->Receptor->Rfc}";
                $extra_data .=" Nombre: {$JSON->Comprobante->Receptor->Nombre}";
            }

            if($codigo === '307'){
                errores::$error = false;
            }

            else {
                $upd = $this->upd_error($codigo, $rs);
                $error = (new errores())->error("Error al timbrar $rs->mensaje Code: $rs->codigo $extra_data", $rs);
                print_r($error);
                echo $nomina_json . "<br>";
                exit;
            }
        }
        return $nomina_json;

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
            $rows[$indice] = $row;
        }

        $this->fc_rows_layout = $rows;

        return $rows;

    }

    public function timbra_recibo(bool $header, bool $ws = false): array|stdClass
    {

        $fc_row_layout_id = $_GET['fc_row_layout_id'];


        $datos_rec = (new _timbra_nomina())->datos_recibo($this->link, $fc_row_layout_id);
        if(errores::$error){
            return $this->retorno_error(
                mensaje: 'Error al obtener datos de recibo', data: $datos_rec, header: $header, ws: $ws);
        }

        if($datos_rec->fc_row_layout->fc_row_layout_esta_timbrado === 'activo'){
            return $this->retorno_error(
                mensaje: 'Error ya esta timbrado', data: $datos_rec, header: $header, ws: $ws);
        }

        $datos_cfdi = (new _timbra_nomina())->datos_response_timbre($this->link, $datos_rec->fc_row_layout);
        if(errores::$error){
            return $this->retorno_error(
                mensaje: 'Error al obtener datos de datos_cfdi', data: $datos_cfdi, header: $header, ws: $ws);
        }

        $rs = (new _cnx_pac())->operacion_timbrarJSON2($datos_cfdi->jsonB64, $datos_cfdi->keyPEM,
            $datos_cfdi->cerPEM, $datos_cfdi->plantilla);
        $rs = json_decode($rs, false);
        $codigo = trim($rs->codigo);

        $code_error = $this->code_error($codigo, $rs, $datos_cfdi->nomina_json);
        if(errores::$error){
            $error = (new errores())->error("Error al timbrar", $code_error);
            print_r($error);
            echo $datos_cfdi->nomina_json . "<br>";
            exit;
        }

        $rs = $this->cfdi_exito($rs, $datos_rec->fc_row_layout);
        if(errores::$error) {
            $error = (new errores())->error("Error al actualizar UUID", $rs);
            print_r($error);
        }

        $result = $this->upd_estado_timbrado_fc_layout_nom(fc_layout_nom_id: $datos_rec->fc_layout_nom->fc_layout_nom_id);
        if(errores::$error) {
            $error = (new errores())->error("Error en upd_estado_timbrado_fc_layout_nom", $result);
            print_r($error);
        }

//        $result = $this->upd_total_dispersion_fc_layout_nom(fc_layout_nom_id: $datos_rec->fc_layout_nom->fc_layout_nom_id);
//        if(errores::$error) {
//            $error = (new errores())->error("Error en upd_total_dispersion_fc_layout_nom", $result);
//            print_r($error);
//        }

        header("Location: " . $_SERVER['HTTP_REFERER']);
        return $datos_rec->fc_row_layout;


    }



    private function subir_docs_timbre(string $pdf, string $xml, stdClass $fc_row_layout): array|stdClass
    {
        $result_upl_pdf = $this->subir_pdf(string_pdf: $pdf, fc_row_layout_id: $fc_row_layout->fc_row_layout_id);
        if (errores::$error) {
            return (new errores())->error(mensaje: 'Error al subir pdf', data: $result_upl_pdf);
        }

        $result_upl_xml = $this->subir_xml(string_xml: $xml, fc_row_layout_id: $fc_row_layout->fc_row_layout_id);
        if (errores::$error) {
            return (new errores())->error(mensaje: 'Error al subir pdf', data: $result_upl_xml);
        }

        $docs = new stdClass();
        $docs->pdf = $result_upl_pdf;
        $docs->xml = $result_upl_xml;

        return $docs;

    }

    private function subir_pdf(string $string_pdf, int $fc_row_layout_id): array
    {
        $nombre_archivo = $fc_row_layout_id.'.pdf';
        $ruta = (new generales())->path_base.'archivos/'.$nombre_archivo;

        $registro['doc_tipo_documento_id'] = 8;
        $file = array();
        $file['name'] = $nombre_archivo;
        $file['tmp_name'] = $ruta;
        file_put_contents($ruta, $string_pdf);

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

    private function subir_xml(string $string_xml, int $fc_row_layout_id): array
    {
        $nombre_archivo = $fc_row_layout_id.'.xml';
        $ruta = (new generales())->path_base.'archivos/'.$nombre_archivo;

        $registro['doc_tipo_documento_id'] = 2;
        $file = array();
        $file['name'] = $nombre_archivo;
        $file['tmp_name'] = $ruta;
        file_put_contents($ruta, $string_xml);

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

    private function upd_error(string $codigo, stdClass $rs_timbre): true
    {
        errores::$error = false;
        $sql = "UPDATE fc_row_layout SET fc_row_layout.error = 'Codigo: $codigo Mensaje: $rs_timbre->mensaje' WHERE fc_row_layout.id = $_GET[fc_row_layout_id]";
        modelo::ejecuta_transaccion($sql, $this->link);
        return true;

    }

    private function upd_exito(string $uuid): true
    {
        $sql = "UPDATE fc_row_layout SET fc_row_layout.uuid = '$uuid', fc_row_layout.error = '', 
                         esta_timbrado = 'activo' WHERE fc_row_layout.id = $_GET[fc_row_layout_id]";
        $rs = modelo::ejecuta_transaccion($sql, $this->link);
        if(errores::$error) {
            $error = (new errores())->error("Error al actualizar UUID", $rs);
            print_r($error);
        }
        return true;

    }

    private function upd_estado_timbrado_fc_layout_nom(int $fc_layout_nom_id): array
    {
        $sql = "UPDATE fc_layout_nom
                SET estado_timbrado = (
                    SELECT 
                      CASE 
                        WHEN SUM(esta_timbrado = 'activo') = 0 THEN 'SIN TIMBRAR'
                        WHEN SUM(esta_timbrado = 'activo') = COUNT(*) THEN 'TIMBRADO'
                        WHEN SUM(esta_timbrado = 'activo') > 0 
                             AND SUM(esta_timbrado = 'activo') < COUNT(*) 
                             AND SUM(error <> '') > 0 THEN 'TIMBRADO CON ERROR'
                        ELSE 'TIMBRADO PARCIAL'
                      END AS estado_timbrado
                    FROM fc_row_layout
                    WHERE fc_layout_nom_id = $fc_layout_nom_id
                )
                WHERE id = $fc_layout_nom_id";
        $rs = modelo::ejecuta_transaccion($sql, $this->link);
        if(errores::$error) {
            return (new errores())->error("Error al actualizar estado_timbrado_fc_layout_nom", $rs);
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

    private function llenar_fc_cfdi_sellado_nomina(string $string_xml, stdClass $fc_row_layout): array
    {// ToDo: metho aun no terminado

        $xml = simplexml_load_string($string_xml);

        // Leer atributos del comprobante
        $comprobante = $xml->attributes();
        $comprobante_sello = (string) $comprobante['Sello'];
        $comprobante_certificado = (string) $comprobante['Certificado'];
        $comprobante_no_certificado = (string) $comprobante['NoCertificado'];

        // Leer TimbreFiscalDigital (complemento)
        $xml->registerXPathNamespace('tfd', 'http://www.sat.gob.mx/TimbreFiscalDigital');
        $tfd = $xml->xpath('//tfd:TimbreFiscalDigital')[0];
        $complemento_tfd_fecha_timbrado = (string) $tfd['FechaTimbrado'];
        $complemento_tfd_no_certificado_sat = (string) $tfd['NoCertificadoSAT'];
        $complemento_tfd_rfc_prov_certif = (string) $tfd['RfcProvCertif'];
        $complemento_tfd_sello_cfd = (string) $tfd['SelloCFD'];
        $complemento_tfd_sello_sat = (string) $tfd['SelloSAT'];
        $uuid = (string) $tfd['UUID'];
        $complemento_tfd_tfd = $tfd->asXML();

        // Leer FechaPago de nómina
        $nomina = $xml->xpath('//nomina12:Nomina')[0];
        $fecha_pago = (string) $nomina['FechaPago'];

        $registro = [
            'codigo' => 'test_codigo',
            'descripcion' => 'test_descripcion',
            'fc_row_layout_id' => $fc_row_layout->fc_row_layout_id,
            'comprobante_sello' => $comprobante_sello,
            'comprobante_certificado' => $comprobante_certificado,
            'comprobante_no_certificado' => $comprobante_no_certificado,
            'complemento_tfd_fecha_timbrado' => $complemento_tfd_fecha_timbrado,
            'complemento_tfd_no_certificado_sat' => $complemento_tfd_no_certificado_sat,
            'complemento_tfd_rfc_prov_certif' => $complemento_tfd_rfc_prov_certif,
            'complemento_tfd_sello_cfd' => $complemento_tfd_sello_cfd,
            'complemento_tfd_sello_sat' => $complemento_tfd_sello_sat,
            'uuid' => $uuid,
            'complemento_tfd_tfd' => $complemento_tfd_tfd,
            'fecha_pago' => $fecha_pago,
        ];

        $fc_cfdi_sellado_nomina = new fc_cfdi_sellado_nomina($this->link);
        $fc_cfdi_sellado_nomina->registro = $registro;
        $result = $fc_cfdi_sellado_nomina->alta_bd();
        if(errores::$error) {
            return (new errores())->error("Error en alta_bd de fc_cfdi_sellado_nomina", $result);
        }

        return [];
    }




}
