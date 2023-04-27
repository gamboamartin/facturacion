<?php
/**
 * @author Martin Gamboa Vazquez
 * @version 1.0.0
 * @created 2022-05-14
 * @final En proceso
 *
 */
namespace gamboamartin\facturacion\controllers;

use base\controller\controler;
use config\pac;
use gamboamartin\compresor\compresor;
use gamboamartin\documento\models\doc_documento;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\html\fc_complemento_pago_html;
use gamboamartin\facturacion\html\fc_factura_html;
use gamboamartin\facturacion\html\fc_partida_html;
use gamboamartin\facturacion\models\_pdf;
use gamboamartin\facturacion\models\fc_complemento_pago;
use gamboamartin\facturacion\models\fc_email;
use gamboamartin\facturacion\models\fc_factura;
use gamboamartin\facturacion\models\fc_factura_documento;
use gamboamartin\facturacion\models\fc_factura_etapa;
use gamboamartin\facturacion\models\fc_factura_relacionada;
use gamboamartin\facturacion\models\fc_partida;
use gamboamartin\facturacion\models\fc_relacion;
use gamboamartin\proceso\models\pr_proceso;
use gamboamartin\system\actions;
use gamboamartin\template\html;
use gamboamartin\xml_cfdi_4\timbra;
use html\cat_sat_conf_imps_html;
use html\cat_sat_motivo_cancelacion_html;
use html\cat_sat_tipo_relacion_html;
use html\com_cliente_html;
use html\com_email_cte_html;
use JsonException;
use PDO;
use stdClass;

class controlador_fc_complemento_pago extends _base_system_fc {

    public array|stdClass $keys_selects = array();
    public controlador_fc_partida $controlador_fc_partida;
    public controlador_com_producto $controlador_com_producto;

    public string $button_fc_factura_modifica = '';
    public string $button_fc_factura_relaciones = '';
    public string $button_fc_factura_timbra = '';

    public string $rfc = '';
    public string $razon_social = '';
    public string $link_fc_partida_alta_bd = '';
    public string $link_fc_partida_modifica_bd = '';
    public string $link_fc_factura_partidas = '';
    public string $link_fc_factura_nueva_partida = '';

    public string $link_fc_email_alta_bd = '';
    public string $link_fc_relacion_alta_bd = '';
    public string $link_com_producto = '';

    public string $link_factura_cancela = '';
    public string $link_factura_genera_xml = '';
    public string $link_factura_timbra_xml = '';
    public string $button_fc_factura_correo = '';
    public string $link_fc_factura_relacionada_alta_bd = '';
    public int $fc_factura_id = -1;
    public int $fc_partida_id = -1;
    public stdClass $partidas;

    public array $relaciones = array();
    public array$facturas_cliente = array();
    public function __construct(PDO $link, html $html = new \gamboamartin\template_1\html(),
                                stdClass $paths_conf = new stdClass()){
        $modelo = new fc_complemento_pago(link: $link);
        $this->modelo = $modelo;
        $this->cat_sat_tipo_de_comprobante = 'Pago';
        $html_ = new fc_complemento_pago_html(html: $html);
        $this->html_fc = $html_;

        parent::__construct(html_: $html_, link: $link,modelo:  $modelo, paths_conf: $paths_conf);

        $this->data_selected_alta['cat_sat_forma_pago_id']['id'] = 99;
        $this->data_selected_alta['cat_sat_forma_pago_id']['filtro'] = array('cat_sat_forma_pago.id'=>99);

        $this->data_selected_alta['cat_sat_metodo_pago_id']['id'] = 1;
        $this->data_selected_alta['cat_sat_metodo_pago_id']['filtro'] = array('cat_sat_metodo_pago.id'=>1);

        $this->data_selected_alta['cat_sat_moneda_id']['id'] = 163;
        $this->data_selected_alta['cat_sat_moneda_id']['filtro'] = array('cat_sat_moneda.id'=>163);

        $this->data_selected_alta['com_tipo_cambio_id']['id'] = -1;
        $this->data_selected_alta['com_tipo_cambio_id']['filtro'] = array();

        $this->data_selected_alta['cat_sat_uso_cfdi_id']['id'] = 23;
        $this->data_selected_alta['cat_sat_uso_cfdi_id']['filtro'] = array('cat_sat_uso_cfdi.id'=>23);

        $init_ctl = (new _fc_base())->init_base_fc(controler: $this);
        if(errores::$error){
            $error = $this->errores->error(mensaje: 'Error al inicializar',data:  $init_ctl);
            print_r($error);
            die('Error');
        }


        if(isset($_GET['fc_partida_id'])){
            $this->fc_partida_id = $_GET['fc_partida_id'];
        }

        $this->lista_get_data = true;


        $this->verifica_parents_alta = true;




    }


    public function alta_partida_bd(bool $header, bool $ws = false){

        $this->link->beginTransaction();

        $siguiente_view = (new actions())->init_alta_bd(siguiente_view: "modifica");
        if(errores::$error){
            $this->link->rollBack();
            return $this->retorno_error(mensaje: 'Error al obtener siguiente view', data: $siguiente_view,
                header:  $header, ws: $ws);
        }

        if(isset($_POST['guarda'])){
            unset($_POST['guarda']);
        }
        if(isset($_POST['btn_action_next'])){
            unset($_POST['btn_action_next']);
        }


        $factura = (new fc_factura($this->link))->get_factura(fc_factura_id: $this->registro_id);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener factura', data: $factura, header: $header, ws: $ws);
        }

        $registro = $_POST;
        $registro['fc_factura_id'] = $this->registro_id;

        $r_alta_partida_bd = (new fc_partida($this->link))->alta_registro(registro:$registro); // TODO: Change the autogenerated stub
        if(errores::$error){
            $this->link->rollBack();
            return $this->retorno_error(mensaje: 'Error al dar de alta partida',data:  $r_alta_partida_bd,
                header: $header,ws:$ws);
        }

        $this->link->commit();

        if($header){

            $retorno = (new actions())->retorno_alta_bd(link: $this->link, registro_id: $this->registro_id,
                seccion: $this->tabla, siguiente_view: "modifica");
            if(errores::$error){
                return $this->retorno_error(mensaje: 'Error al dar de alta registro', data: $r_alta_partida_bd,
                    header:  true, ws: $ws);
            }
            header('Location:'.$retorno);
            exit;
        }
        if($ws){
            header('Content-Type: application/json');
            echo json_encode($r_alta_partida_bd, JSON_THROW_ON_ERROR);
            exit;
        }

        return $r_alta_partida_bd;

    }

    private function base_data_partida(int $fc_partida_id): array|stdClass
    {
        $data = $this->data_partida(fc_partida_id: $fc_partida_id);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al cargar datos de partida', data: $data);
        }

        $htmls = $this->htmls_partida();
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener htmls',data:  $htmls);
        }
        $data_return = new stdClass();
        $data_return->data = $data;
        $data_return->htmls = $htmls;
        return $data_return;
    }

    private function button_elimina_correo(int $fc_email_id, int $fc_factura_id): array|string
    {
        $params = $this->params_button_partida(accion_retorno: 'correo', fc_factura_id: $fc_factura_id);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al generar params', data: $params);
        }

        $link_elimina = $this->html->button_href(accion: 'elimina_bd', etiqueta: 'Eliminar', registro_id: $fc_email_id,
            seccion: 'fc_email', style: 'danger',icon: 'bi bi-trash', muestra_icono_btn: true,
            muestra_titulo_btn: false, params: $params);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al generar link elimina_bd para partida', data: $link_elimina);
        }
        return $link_elimina;
    }
    private function button_status_correo(int $fc_email_id, int $fc_factura_id): array|string
    {
        $params = $this->params_button_partida(accion_retorno: 'correo', fc_factura_id: $fc_factura_id);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al generar params', data: $params);
        }

        $fc_email = (new fc_email(link: $this->link))->registro(registro_id: $fc_email_id, retorno_obj: true);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al obtener fc_email', data: $fc_email);
        }

        $style = 'success';
        if($fc_email->fc_email_status === 'inactivo'){
            $style = 'danger';
        }


        $link_status = $this->html->button_href(accion: 'status', etiqueta: 'Status', registro_id: $fc_email_id,
            seccion: 'fc_email', style: $style,icon: 'bi bi-file-diff', muestra_icono_btn: true,
            muestra_titulo_btn: false, params: $params);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al generar link elimina_bd para partida', data: $link_status);
        }
        return $link_status;
    }

    public function cancela(bool $header, bool $ws = false){
        $filtro['fc_factura.id'] = $this->registro_id;
        $columns_ds = array('fc_factura_folio','com_cliente_rfc','fc_factura_total','fc_factura_fecha');
        $fc_factura_id = $this->html_fc->select_fc_factura_id(cols: 12, con_registros: true,
            id_selected: $this->registro_id, link: $this->link, columns_ds: $columns_ds, disabled: true,
            filtro: $filtro);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener factura',data:  $fc_factura_id, header: $header,ws:$ws);
        }

        $cat_sat_motivo_cancelacion_id =
            (new cat_sat_motivo_cancelacion_html(html: $this->html_base))->select_cat_sat_motivo_cancelacion_id(
                cols: 12, con_registros: true, id_selected: -1, link: $this->link);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener cat_sat_motivo_cancelacion_id',
                data:  $cat_sat_motivo_cancelacion_id, header: $header,ws:$ws);
        }

        $link_factura_cancela = $this->obj_link->link_con_id(accion: 'cancela_bd',link: $this->link,registro_id: $this->registro_id,seccion: $this->tabla);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener link_factura_cancela',
                data:  $link_factura_cancela, header: $header,ws:$ws);
        }


        $this->link_factura_cancela = $link_factura_cancela;


        $this->inputs = new stdClass();
        $this->inputs->fc_factura_id = $fc_factura_id;
        $this->inputs->cat_sat_motivo_cancelacion_id = $cat_sat_motivo_cancelacion_id;


    }

    /**
     * @throws JsonException
     */
    public function cancela_bd(bool $header, bool $ws = false): array|stdClass
    {

        $r_fc_cancelacion = (new fc_factura(link: $this->link))->cancela_bd(
            cat_sat_motivo_cancelacion_id: $_POST['cat_sat_motivo_cancelacion_id'], fc_factura_id: $this->registro_id);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al cancelar factura',data:  $r_fc_cancelacion, header: $header,ws:$ws);
        }

        if($header){

            $retorno = (new actions())->retorno_alta_bd(link: $this->link, registro_id: $this->registro_id,
                seccion: $this->tabla, siguiente_view: "lista");
            if(errores::$error){
                return $this->retorno_error(mensaje: 'Error al dar de alta registro', data: $r_fc_cancelacion,
                    header:  true, ws: $ws);
            }
            header('Location:'.$retorno);
            exit;
        }
        if($ws){
            header('Content-Type: application/json');
            echo json_encode($r_fc_cancelacion, JSON_THROW_ON_ERROR);
            exit;
        }

        return $r_fc_cancelacion;

    }

    public function correo(bool $header, bool $ws = false): array|stdClass
    {

        $row_upd = $this->modelo->registro(registro_id: $this->registro_id, columnas_en_bruto: true, retorno_obj: true);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al obtener registro', data: $row_upd);
        }


        $this->inputs = new stdClass();
        $fc_factura_id = (new fc_factura_html(html: $this->html_base))->select_fc_factura_id(cols: 12,
            con_registros: true, id_selected: $this->registro_id, link: $this->link,
            disabled: true, filtro: array('fc_factura.id'=>$this->registro_id));
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al maquetar input', data: $fc_factura_id);
        }

        $this->inputs->fc_factura_id = $fc_factura_id;

        $fc_factura_folio = (new fc_factura_html(html: $this->html_base))->input_folio(cols: 12,row_upd: $row_upd,
            value_vacio: false, disabled: true);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al maquetar input', data: $fc_factura_folio);
        }

        $this->inputs->fc_factura_folio = $fc_factura_folio;


        $com_cliente_razon_social= (new com_cliente_html(html: $this->html_base))->input_razon_social(cols: 12,
            row_upd: $row_upd, value_vacio: false, disabled: true);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al maquetar input', data: $com_cliente_razon_social);
        }

        $this->inputs->com_cliente_razon_social = $com_cliente_razon_social;

        $com_email_cte_descripcion= (new com_email_cte_html(html: $this->html_base))->input_email(cols: 12,
            row_upd:  new stdClass(),value_vacio:  false, name: 'descripcion');
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al maquetar input', data: $com_email_cte_descripcion);
        }

        $this->inputs->com_email_cte_descripcion = $com_email_cte_descripcion;


        $com_email_cte_id= (new com_email_cte_html(html: $this->html_base))->select_com_email_cte_id(cols: 12,
            con_registros:  true,id_selected:  -1, link: $this->link);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al maquetar input', data: $com_email_cte_descripcion);
        }

        $this->inputs->com_email_cte_id = $com_email_cte_id;

        $hidden_row_id = $this->html->hidden(name: 'fc_factura_id',value:  $this->registro_id);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al maquetar input', data: $hidden_row_id);
        }

        $hidden_seccion_retorno = $this->html->hidden(name: 'seccion_retorno',value:  $this->tabla);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al maquetar input', data: $hidden_seccion_retorno);
        }
        $hidden_id_retorno = $this->html->hidden(name: 'id_retorno',value:  $this->registro_id);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al maquetar input', data: $hidden_id_retorno);
        }

        $this->inputs->hidden_row_id = $hidden_row_id;
        $this->inputs->hidden_seccion_retorno = $hidden_seccion_retorno;
        $this->inputs->hidden_id_retorno = $hidden_id_retorno;

        $filtro['fc_factura.id'] = $this->registro_id;

        $r_fc_email = (new fc_email(link: $this->link))->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al obtener correos', data: $r_fc_email);
        }

        $emails_facturas = $r_fc_email->registros;

        foreach ($emails_facturas as $indice=>$email_factura){

            $link_elimina = $this->button_elimina_correo(fc_email_id: $email_factura['fc_email_id'], fc_factura_id: $this->registro_id);
            if (errores::$error) {
                return $this->errores->error(mensaje: 'Error al generar link elimina_bd para partida', data: $link_elimina);
            }
            $emails_facturas[$indice]['elimina_bd'] = $link_elimina;

            $link_status = $this->button_status_correo(fc_email_id: $email_factura['fc_email_id'], fc_factura_id: $this->registro_id);
            if (errores::$error) {
                return $this->errores->error(mensaje: 'Error al generar link elimina_bd para partida', data: $link_elimina);
            }
            $emails_facturas[$indice]['status'] = $link_status;
        }


        $this->registros['emails_facturas'] = $emails_facturas;


        $button_fc_factura_modifica =  $this->html->button_href(accion: 'modifica', etiqueta: 'Ir a Factura',
            registro_id: $this->registro_id,
            seccion: 'fc_factura', style: 'warning', params: array());
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al generar link', data: $button_fc_factura_modifica);
        }

        $this->button_fc_factura_modifica = $button_fc_factura_modifica;
        return $this->inputs;
    }

    private function data_partida(int $fc_partida_id): array|stdClass
    {
        $data_partida = (new fc_partida($this->link))->data_partida_obj(fc_partida_id: $fc_partida_id);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener partida',data:  $data_partida);
        }

        $data = new stdClass();
        $data->data_partida = $data_partida;

        return $data;
    }

    public function descarga_xml(bool $header, bool $ws = false){
        $ruta_xml = (new fc_factura_documento(link: $this->link))->get_factura_documento(fc_factura_id: $this->registro_id,
            tipo_documento: "xml_sin_timbrar");
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener XML',data:  $ruta_xml, header: $header,ws:$ws);
        }

        $fc_factura = $this->modelo->registro(registro_id: $this->registro_id, retorno_obj: true);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener factura',data:  $fc_factura, header: $header,ws:$ws);
        }

        $file_name = $fc_factura->fc_factura_serie.$fc_factura->fc_factura_folio.'.xml';

        if(!empty($ruta_xml) && file_exists($ruta_xml)){
            // Define headers
            header("Cache-Control: public");
            header("Content-Description: File Transfer");
            header("Content-Disposition: attachment; filename=$file_name");
            header("Content-Type: application/xml");
            header("Content-Transfer-Encoding: binary");

            // Read the file
            readfile($ruta_xml);
            exit;
        }else{
            echo 'The file does not exist.';
        }

    }

    private function doc_documento_id(stdClass $fc_factura, string $pdf){
        $doc_documento_ins = array();

        $file = $this->file_doc_pdf(fc_factura: $fc_factura,pdf:  $pdf);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al maquetar file',data:  $file);
        }

        /**
         * AJUSTAR PARA ELIMIANR HARDCODEO
         */
        $doc_documento_ins['doc_tipo_documento_id'] = 8;

        $r_doc_documento = (new doc_documento(link: $this->link))->alta_documento(registro: $doc_documento_ins,file: $file);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al al insertar documento',data:  $r_doc_documento);
        }
        return $r_doc_documento->registro_id;
    }

    public function envia_cfdi(bool $header, bool $ws = false){

        $genera_pdf = $this->genera_pdf(header: false);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar pdf',data:  $genera_pdf, header: $header,ws:$ws);
        }

        $inserta_notificacion = (new fc_factura(link: $this->link))->inserta_notificacion(registro_id: $this->registro_id);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al insertar notificacion',data:  $inserta_notificacion, header: $header,ws:$ws);
        }

        $envia_notificacion = (new fc_factura(link: $this->link))->envia_factura(fc_factura_id: $this->registro_id);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al enviar notificacion',data:  $envia_notificacion, header: $header,ws:$ws);
        }

        if($header){

            $retorno = (new actions())->retorno_alta_bd(link: $this->link, registro_id: $this->registro_id,
                seccion: $this->tabla, siguiente_view: "lista");
            if(errores::$error){
                return $this->retorno_error(mensaje: 'Error cambiar de view', data: $retorno,
                    header:  true, ws: $ws);
            }
            header('Location:'.$retorno);
            exit;
        }
        if($ws){
            header('Content-Type: application/json');
            echo json_encode($envia_notificacion, JSON_THROW_ON_ERROR);
            exit;
        }
        return $envia_notificacion;


    }

    public function envia_factura(bool $header, bool $ws = false){

        $this->link->beginTransaction();
        $notifica = (new fc_factura(link: $this->link))->envia_factura(fc_factura_id: $this->registro_id);
        if(errores::$error){
            $this->link->rollBack();
            return $this->retorno_error(mensaje: 'Error al enviar notificacion',data:  $notifica, header: $header,ws:$ws);
        }
        $this->link->commit();

        if($header){

            $retorno = (new actions())->retorno_alta_bd(link: $this->link, registro_id: $this->registro_id,
                seccion: $this->tabla, siguiente_view: "lista");
            if(errores::$error){
                return $this->retorno_error(mensaje: 'Error cambiar de view', data: $retorno,
                    header:  true, ws: $ws);
            }
            header('Location:'.$retorno);
            exit;
        }
        if($ws){
            header('Content-Type: application/json');
            echo json_encode($notifica, JSON_THROW_ON_ERROR);
            exit;
        }
        return $notifica;

    }

    public function exportar_documentos(bool $header, bool $ws = false){

        $ruta_xml = (new fc_factura_documento(link: $this->link))->get_factura_documento(fc_factura_id: $this->registro_id,
            tipo_documento: "xml_sin_timbrar");
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener XML',data:  $ruta_xml, header: $header,ws:$ws);
        }


        $ruta_pdf = (new _pdf())->pdf(descarga: false,fc_factura_id: $this->registro_id,guarda: true,link:  $this->link);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar PDF',data:  $ruta_pdf, header: $header,ws:$ws);
        }


        $fc_factura = $this->modelo->registro(registro_id: $this->registro_id, retorno_obj: true);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener Factura',data:  $fc_factura, header: $header,ws:$ws);
        }

        $name_zip = $fc_factura->fc_factura_serie.$fc_factura->fc_factura_folio;

        $archivos = array();
        $archivos[$ruta_xml] = "$fc_factura->fc_factura_serie$fc_factura->fc_factura_folio.xml";
        $archivos[$ruta_pdf] = "$fc_factura->fc_factura_serie$fc_factura->fc_factura_folio.pdf";

        Compresor::descarga_zip_multiple(archivos: $archivos,name_zip: $name_zip);


        exit;
    }

    private function fc_factura_documento_ins(int $doc_documento_id, int $fc_factura_id): array
    {
        $fc_factura_documento_ins['fc_factura_id'] = $fc_factura_id;
        $fc_factura_documento_ins['doc_documento_id'] = $doc_documento_id;
        return $fc_factura_documento_ins;
    }

    public function fc_factura_relacionada_alta_bd(bool $header, bool $ws = false){

        $fc_facturas_id = $_POST['fc_facturas_id'];
        $alta = array();
        foreach ($fc_facturas_id as $fc_relacion_id=>$fc_factura_id){
            $fc_factura_relacionada_ins['fc_relacion_id'] = $fc_relacion_id;
            $fc_factura_relacionada_ins['fc_factura_id'] = $fc_factura_id;
            $r_fc_factura_relacionada = (new fc_factura_relacionada(link: $this->link))->alta_registro(registro: $fc_factura_relacionada_ins);
            if(errores::$error){
                return $this->retorno_error(mensaje: 'Error al dar de alta registro', data: $r_fc_factura_relacionada,
                    header:  true, ws: $ws);
            }
            $alta[] = $r_fc_factura_relacionada;
        }

        if($header){
            $params = array();
            $retorno = (new actions())->retorno_alta_bd(link: $this->link, registro_id: $this->registro_id,
                seccion: $this->tabla, siguiente_view: 'relaciones', params: $params);
            if(errores::$error){
                return $this->retorno_error(mensaje: 'Error al dar de alta registro', data: $alta,
                    header:  true, ws: $ws);
            }
            header('Location:'.$retorno);
            exit;
        }
        if($ws){
            header('Content-Type: application/json');
            echo json_encode($alta, JSON_THROW_ON_ERROR);
            exit;
        }

        return $alta;

    }

    public function fc_relacion_alta_bd(bool $header, bool $ws = false){
        $fc_relacion_ins['fc_factura_id'] = $this->registro_id;
        $fc_relacion_ins['cat_sat_tipo_relacion_id'] = $_POST['cat_sat_tipo_relacion_id'];
        $r_fc_relacion = (new fc_relacion(link: $this->link))->alta_registro(registro: $fc_relacion_ins);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al insertar relacion',data:  $r_fc_relacion, header: $header,ws:$ws);
        }

        if($header){
            $params = array();
            $retorno = (new actions())->retorno_alta_bd(link: $this->link, registro_id: $this->registro_id,
                seccion: $this->tabla, siguiente_view: 'relaciones', params: $params);
            if(errores::$error){
                return $this->retorno_error(mensaje: 'Error al dar de alta registro', data: $r_fc_relacion,
                    header:  true, ws: $ws);
            }
            header('Location:'.$retorno);
            exit;
        }
        if($ws){
            header('Content-Type: application/json');
            echo json_encode($r_fc_relacion, JSON_THROW_ON_ERROR);
            exit;
        }

        return $r_fc_relacion;



    }

    private function file_doc_pdf(stdClass $fc_factura, string $pdf): array
    {
        $file['name'] = $fc_factura->fc_factura_folio.'.pdf';
        $file['tmp_name'] = $pdf;
        return $file;
    }

    private function genera_factura_pdf(stdClass $fc_factura, int $fc_factura_id, string $pdf): array|stdClass
    {
        $doc_documento_id = $this->doc_documento_id(fc_factura: $fc_factura,pdf:  $pdf);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al al insertar documento',data:  $doc_documento_id);
        }

        $r_fc_factura_documento = $this->inserta_fc_factura_documento(doc_documento_id: $doc_documento_id,fc_factura_id: $fc_factura_id);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al al insertar factura_doc',data:  $r_fc_factura_documento);
        }
        return $r_fc_factura_documento;
    }

    public function genera_pdf(bool $header, bool $ws = false){

        $pdf = (new _pdf())->pdf(descarga: false, fc_factura_id: $this->registro_id,guarda: true,link: $this->link);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar pdf',data:  $pdf, header: $header,ws:$ws);
        }

        $fc_factura = (new fc_factura(link: $this->link))->registro(registro_id: $this->registro_id, retorno_obj: true);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener factura',data:  $fc_factura);
        }

        $r_fc_factura_documento = $this->inicializa_factura_documento(fc_factura: $fc_factura, fc_factura_id: $this->registro_id,pdf:  $pdf);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al al insertar factura_doc',data:  $r_fc_factura_documento, header: $header,ws:$ws);
        }

        if($header){
            $fichero = $r_fc_factura_documento->registro_obj->doc_documento_ruta_absoluta;
            header('Content-Type: application/pdf');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            ob_clean();
            flush();

            readfile($fichero);
            exit;

        }
        return $r_fc_factura_documento;

    }

    public function genera_xml(bool $header, bool $ws = false){

        $tipo = (new pac())->tipo;

        $factura = (new fc_factura(link: $this->link))->genera_xml(fc_factura_id: $this->registro_id, tipo: $tipo);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar XML',data:  $factura, header: $header,ws:$ws);
        }

        unlink($factura->file_xml_st);
        ob_clean();
        echo trim(file_get_contents($factura->doc_documento_ruta_absoluta));
        if($tipo === 'json'){
            header('Content-Type: application/json');
        }
        else{
            header('Content-Type: text/xml');
        }

        exit;
    }

    public function get_factura_pac(bool $header, bool $ws = false){
        $fc_factura = $this->modelo->registro(registro_id: $this->registro_id);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener fc_factura', data:  $fc_factura);
        }



    }



    private function htmls_partida(): stdClass
    {
        $fc_partida_html = (new fc_partida_html(html: $this->html_base));

        $data = new stdClass();
        $data->fc_partida = $fc_partida_html;

        return $data;
    }

    private function inicializa_factura_documento(stdClass $fc_factura, int $fc_factura_id, string $pdf): array|stdClass
    {


        $r_fc_factura_documento = $this->init_factura_documento(fc_factura_id: $fc_factura_id);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al al eliminar registro',data:  $r_fc_factura_documento);
        }

        $r_fc_factura_documento = $this->genera_factura_pdf(fc_factura: $fc_factura, fc_factura_id: $fc_factura_id, pdf: $pdf);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al al insertar factura_doc',data:  $r_fc_factura_documento);
        }
        return $r_fc_factura_documento;
    }

    /**
     * Inicializa las configuraciones de views para facturas
     * @return controler
     * @version 4.19.0
     */
    private function init_configuraciones(): controler
    {
        $this->seccion_titulo = 'Facturas';
        $this->titulo_lista = 'Registro de Facturas';

        return $this;
    }

    /**
     * Inicializa los controladores default
     * @param stdClass $paths_conf Rutas de archivos de configuracion
     * @return controler
     */
    private function init_controladores(stdClass $paths_conf): controler
    {
        $this->controlador_fc_partida_cp= new controlador_fc_partida_cp(link:$this->link, paths_conf: $paths_conf);
        $this->controlador_com_producto = new controlador_com_producto(link:$this->link, paths_conf: $paths_conf);

        return $this;
    }



    private function init_factura_documento(int $fc_factura_id): bool|array
    {
        $filtro['fc_factura.id'] = $fc_factura_id;
        $filtro['doc_tipo_documento.id'] = 8;

        $existe_factura_documento = (new fc_factura_documento(link: $this->link))->existe(filtro: $filtro);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al validar si existe documento',data:  $existe_factura_documento);
        }

        if($existe_factura_documento){
            $r_fc_factura_documento = (new fc_factura_documento(link: $this->link))->elimina_con_filtro_and(filtro: $filtro);
            if(errores::$error){
                return $this->errores->error(mensaje: 'Error al al eliminar registro',data:  $r_fc_factura_documento);
            }
        }
        return $existe_factura_documento;
    }

    public function init_links(): array|string
    {



        $this->obj_link->genera_links(controler: $this);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al generar links para partida',data:  $this->obj_link);
        }

        $link = $this->obj_link->get_link($this->seccion,"nueva_partida");
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener link nueva_partida',data:  $link);
        }
        $this->link_fc_factura_nueva_partida = $link;

        $link = $this->obj_link->get_link($this->seccion,"alta_partida_bd");
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener link alta_partida_bd',data:  $link);
        }
        $this->link_fc_partida_alta_bd = $link;

        $link = $this->obj_link->get_link($this->seccion,"genera_xml");
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener link genera_xml',data:  $link);
        }
        $this->link_factura_genera_xml = $link;

        $link = $this->obj_link->get_link($this->seccion,"timbra_xml");
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener link genera_xml',data:  $link);
        }
        $this->link_factura_timbra_xml = $link;

        $link_fc_email_alta_bd = $this->obj_link->link_alta_bd(link: $this->link, seccion: 'fc_email');
        if(errores::$error){
            $error = $this->errores->error(mensaje: 'Error al obtener link',data:  $this->link_fc_email_alta_bd);
            print_r($error);
            exit;
        }
        $this->link_fc_email_alta_bd  = $link_fc_email_alta_bd;

        return $link;
    }


    private function init_modifica(array $params = array()): array|stdClass
    {
        $r_modifica =  parent::modifica(header: false);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al generar template',data:  $r_modifica);
        }

        $identificador = "fc_csd_id";
        $propiedades = array("id_selected" => $this->row_upd->fc_csd_id);

        if(isset($params[$identificador])){
            foreach ($params[$identificador] as $key=>$param){
                $propiedades[$key] = $param;
            }
        }

        $prop = $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al asignar propiedad',data:  $prop);
        }

        $identificador = "com_sucursal_id";
        $propiedades = array("id_selected" => $this->row_upd->com_sucursal_id);
        if(isset($params[$identificador])){
            foreach ($params[$identificador] as $key=>$param){
                $propiedades[$key] = $param;
            }
        }
        $prop =$this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al asignar propiedad',data:  $prop);
        }

        $identificador = "cat_sat_forma_pago_id";

        $propiedades = array("id_selected" => $this->row_upd->cat_sat_forma_pago_id);
        if(isset($params[$identificador])){
            foreach ($params[$identificador] as $key=>$param){
                $propiedades[$key] = $param;
            }
        }
        $prop =$this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al asignar propiedad',data:  $prop);
        }

        $identificador = "cat_sat_metodo_pago_id";

        $propiedades = array("id_selected" => $this->row_upd->cat_sat_metodo_pago_id);
        if(isset($params[$identificador])){
            foreach ($params[$identificador] as $key=>$param){
                $propiedades[$key] = $param;
            }
        }
        $prop = $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al asignar propiedad',data:  $prop);
        }

        $identificador = "cat_sat_moneda_id";

        $propiedades = array("id_selected" => $this->row_upd->cat_sat_moneda_id);
        if(isset($params[$identificador])){
            foreach ($params[$identificador] as $key=>$param){
                $propiedades[$key] = $param;
            }
        }
        $prop =$this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al asignar propiedad',data:  $prop);
        }

        $identificador = "com_tipo_cambio_id";

        $propiedades = array("id_selected" => $this->row_upd->com_tipo_cambio_id);
        if(isset($params[$identificador])){
            foreach ($params[$identificador] as $key=>$param){
                $propiedades[$key] = $param;
            }
        }
        $prop =$this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al asignar propiedad',data:  $prop);
        }

        $identificador = "cat_sat_uso_cfdi_id";

        $propiedades = array("id_selected" => $this->row_upd->cat_sat_uso_cfdi_id);
        if(isset($params[$identificador])){
            foreach ($params[$identificador] as $key=>$param){
                $propiedades[$key] = $param;
            }
        }
        $prop =$this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al asignar propiedad',data:  $prop);
        }


        $identificador = "cat_sat_tipo_de_comprobante_id";

        $propiedades = array("id_selected" => $this->row_upd->cat_sat_tipo_de_comprobante_id,
            "filtro" => array('cat_sat_tipo_de_comprobante.id' => $this->row_upd->cat_sat_tipo_de_comprobante_id));

        if(isset($params[$identificador])){
            foreach ($params[$identificador] as $key=>$param){
                $propiedades[$key] = $param;
            }
        }

        $prop =$this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al asignar propiedad',data:  $prop);
        }

        $sub_total = (new fc_factura($this->link))->get_factura_sub_total(fc_factura_id: $this->registro_id);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener sub_total',data:  $sub_total);
        }

        $descuento = (new fc_factura($this->link))->get_factura_descuento(fc_factura_id: $this->registro_id);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener descuento',data:  $descuento);
        }

        $imp_trasladados = (new fc_factura($this->link))->get_factura_imp_trasladados(fc_factura_id:
            $this->registro_id);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener imp_trasladados',data:  $imp_trasladados);
        }

        $imp_retenidos = (new fc_factura($this->link))->get_factura_imp_retenidos(fc_factura_id:
            $this->registro_id);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener imp_retenidos',data:  $imp_retenidos);
        }

        $total = (new fc_factura($this->link))->get_factura_total(fc_factura_id: $this->registro_id);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener total factura',data:  $total);
        }

        $this->row_upd->subtotal = $sub_total;
        $this->row_upd->descuento = $descuento;
        $this->row_upd->impuestos_trasladados = $imp_trasladados;
        $this->row_upd->impuestos_retenidos = $imp_retenidos;
        $this->row_upd->total = $total;

        $inputs = $this->genera_inputs(keys_selects:  $this->keys_selects);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al generar inputs',data:  $inputs);
        }

        $data = new stdClass();
        $data->template = $r_modifica;
        $data->inputs = $inputs;

        return $data;
    }

    private function inputs_partida(fc_partida_html $html, stdClass $fc_partida,
                                    stdClass         $params = new stdClass()): array|stdClass{
        $partida_codigo_disabled = $params->partida_codigo->disabled ?? true;
        $fc_partida_codigo = $html->input_codigo(cols: 4,row_upd:  $fc_partida, value_vacio: false,
            disabled: $partida_codigo_disabled);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener partida_codigo select',data:  $fc_partida_codigo);
        }

        $partida_codigo_bis_disabled = $params->partida_codigo_bis->disabled ?? true;
        $fc_partida_codigo_bis = $html->input_codigo_bis(cols: 4,row_upd:  $fc_partida, value_vacio: false,
            disabled: $partida_codigo_bis_disabled);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener partida_codigo_bis',
                data:  $fc_partida_codigo_bis);
        }

        $partida_descripcion_disabled = $params->partida_descripcion->disabled ?? true;
        $fc_partida_descripcion = $html->input_descripcion(cols: 12,row_upd:  $fc_partida, value_vacio: false,
            disabled: $partida_descripcion_disabled);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener descripcion',data:  $fc_partida_descripcion);
        }

        $partida_cantidad_disabled = $params->partida_cantidad->disabled ?? true;
        $fc_partida_cantidad = $html->input_cantidad(cols: 4, row_upd:  $fc_partida,
            value_vacio: false, disabled: $partida_cantidad_disabled);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener $fc_partida_cantidad',data:  $fc_partida_cantidad);
        }

        $partida_valor_unitario_disabled = $params->partida_valor_unitario->disabled ?? true;
        $fc_partida_valor_unitario = $html->input_valor_unitario(cols: 4, row_upd:  $fc_partida,
            value_vacio: false, disabled: $partida_valor_unitario_disabled);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener $fc_partida_valor_unitario',data:  $fc_partida_valor_unitario);
        }

        $partida_descuento_disabled = $params->partida_descuento->disabled ?? true;
        $fc_partida_descuento = $html->input_descuento(cols: 4, row_upd:  $fc_partida,
            value_vacio: false, disabled: $partida_descuento_disabled);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener $fc_partida_descuento',data:  $fc_partida_descuento);
        }

        $factura_id_disabled = $params->fc_factura_id->disabled ?? true;
        $fc_factura_id = $html->input_id(cols: 12,row_upd:  $fc_partida, value_vacio: false,
            disabled: $factura_id_disabled,place_holder: 'ID factura');
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener sucursal_id select',data:  $fc_factura_id);
        }

        $com_producto_id_disabled = $params->com_producto_id->disabled ?? true;
        $com_producto_id = $html->input_id(cols: 4,row_upd:  $fc_partida, value_vacio: false,
            disabled: $com_producto_id_disabled,place_holder: 'ID Producto');
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener sucursal_id select',data:  $com_producto_id);
        }

        $this->inputs->fc_factura_id = $fc_factura_id;
        $this->inputs->com_producto_id = $com_producto_id;
        $this->inputs->codigo = $fc_partida_codigo;
        $this->inputs->codigo_bis = $fc_partida_codigo_bis;
        $this->inputs->descripcion = $fc_partida_descripcion;
        $this->inputs->cantidad = $fc_partida_cantidad;
        $this->inputs->valor_unitario = $fc_partida_valor_unitario;
        $this->inputs->descuento = $fc_partida_descuento;

        return $this->inputs;
    }

    private function inserta_fc_factura_documento(int $doc_documento_id, int $fc_factura_id): array|stdClass
    {
        $fc_factura_documento_ins = $this->fc_factura_documento_ins(doc_documento_id: $doc_documento_id,fc_factura_id: $fc_factura_id);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener fc_factura_documento_ins',data:  $fc_factura_documento_ins);
        }

        $r_fc_factura_documento = (new fc_factura_documento(link: $this->link))->alta_registro(registro: $fc_factura_documento_ins);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al al insertar factura_doc',data:  $r_fc_factura_documento);
        }
        return $r_fc_factura_documento;
    }

    public function inserta_notificacion(bool $header, bool $ws = false){

        $this->link->beginTransaction();
        $notificaciones = (new fc_factura(link: $this->link))->inserta_notificacion(registro_id: $this->registro_id);
        if (errores::$error) {
            $this->link->rollBack();
            $error = $this->errores->error(mensaje: 'Error al insertar notificaciones', data: $notificaciones);
            print_r($error);
            die('Error');
        }
        $this->link->commit();

        if($header){

            $retorno = (new actions())->retorno_alta_bd(link: $this->link, registro_id: $this->registro_id,
                seccion: $this->tabla, siguiente_view: "lista");
            if(errores::$error){
                return $this->retorno_error(mensaje: 'Error cambiar de view', data: $retorno,
                    header:  true, ws: $ws);
            }
            header('Location:'.$retorno);
            exit;
        }
        if($ws){
            header('Content-Type: application/json');
            echo json_encode($notificaciones, JSON_THROW_ON_ERROR);
            exit;
        }
        return $notificaciones;

    }

    public function modifica(bool $header, bool $ws = false): array|stdClass
    {

        $controladores = $this->init_controladores(paths_conf: $this->paths_conf);
        if(errores::$error){
            $error = $this->errores->error(mensaje: 'Error al inicializar controladores',data:  $controladores);
            print_r($error);
            die('Error');
        }

        $partidas = (new _partidas_html())->genera_partidas_html(fc_factura_id: $this->registro_id,html: $this->html,link: $this->link);
        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al generar html', data: $partidas);
            print_r($error);
            die('Error');
        }

        $this->partidas = $partidas;

        $base = $this->init_modifica();
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al maquetar datos',data:  $base,
                header: $header,ws:$ws);
        }

        $identificador = "com_producto_id";
        $propiedades = array("cols" => 12);
        $this->controlador_fc_partida->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "cat_sat_conf_imps_id";
        $propiedades = array("cols" => 12);
        $this->controlador_fc_partida->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $inputs = $this->nueva_partida_inicializa();
        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al inicializar partida', data: $inputs);
            print_r($error);
            die('Error');
        }

        $this->inputs->partidas = $inputs;

        $cat_sat_conf_imps_id = (new cat_sat_conf_imps_html(html: $this->html_base))->select_cat_sat_conf_imps_id(
            cols: 12,con_registros:  true,id_selected: -1,link: $this->link);
        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al inicializar input', data: $cat_sat_conf_imps_id);
            print_r($error);
            die('Error');
        }

        $this->inputs->partidas->cat_sat_conf_imps_id = $cat_sat_conf_imps_id;


        $t_head_producto = (new _html_factura())->thead_producto();
        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al generar html', data: $t_head_producto);
            print_r($error);
            die('Error');
        }
        $this->t_head_producto = $t_head_producto;


        $observaciones = $this->html_fc->input_observaciones(cols: 12, row_upd: $this->row_upd, value_vacio: false);
        if(errores::$error){
            $error = $this->errores->error(mensaje: 'Error al generar observaciones',data:  $observaciones);
            print_r($error);
            die('Error');
        }
        $this->inputs->observaciones = $observaciones;

        $button_fc_factura_relaciones =  $this->html->button_href(accion: 'relaciones', etiqueta: 'Relaciones',
            registro_id: $this->registro_id,
            seccion: 'fc_factura', style: 'warning', params: array());
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al generar link', data: $button_fc_factura_relaciones);
        }

        $this->button_fc_factura_relaciones = $button_fc_factura_relaciones;

        $button_fc_factura_timbra =  $this->html->button_href(accion: 'timbra_xml', etiqueta: 'Timbra',
            registro_id: $this->registro_id,
            seccion: 'fc_factura', style: 'danger', params: array());
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al generar link', data: $button_fc_factura_relaciones);
        }

        $this->button_fc_factura_timbra = $button_fc_factura_timbra;

        $button_fc_factura_correo =  $this->html->button_href(accion: 'correo', etiqueta: 'Correos',
            registro_id: $this->registro_id,
            seccion: 'fc_factura', style: 'success', params: array());
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al generar link', data: $button_fc_factura_correo);
        }

        $this->button_fc_factura_correo = $button_fc_factura_correo;

        $filtro = array();
        $filtro['fc_factura.id'] = $this->registro_id;
        $r_fc_email = (new fc_email(link: $this->link))->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al obtener emails', data: $r_fc_email);
        }

        $this->registros['fc_emails'] = $r_fc_email->registros;


        return $base->template;
    }


    public function nueva_partida(bool $header, bool $ws = false): array|stdClass
    {
        $this->inputs = $this->nueva_partida_inicializa();
        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al inicializar partida', data: $this->inputs);
            print_r($error);
            die('Error');
        }

        return $this->inputs;
    }

    private function nueva_partida_inicializa(): array|stdClass{

        $r_template = $this->controlador_fc_partida->alta(header: false);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al obtener template', data: $r_template);
        }

        $keys_selects = $this->controlador_fc_partida->init_selects_inputs();
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al inicializar selects', data: $keys_selects);
        }

        $keys_selects['fc_factura_id']->id_selected = $this->registro_id;
        $keys_selects['fc_factura_id']->filtro = array("fc_factura.id" => $this->registro_id);
        $keys_selects['fc_factura_id']->disabled = true;
        $keys_selects['fc_factura_id']->cols = 12;
        $keys_selects['com_producto_id']->cols = 12;
        $keys_selects['cat_sat_conf_imps_id']->cols = 12;

        $inputs = $this->controlador_fc_partida->inputs(keys_selects: $keys_selects);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al obtener inputs', data: $inputs);
        }



        return $inputs;
    }


    private function params_button_partida(string $accion_retorno, int $fc_factura_id): array
    {
        $params = array();
        $params['seccion_retorno'] = 'fc_factura';
        $params['accion_retorno'] = $accion_retorno;
        $params['id_retorno'] = $fc_factura_id;
        return $params;
    }



    /*
     * POR REVISAR
     */

    public function partidas(bool $header, bool $ws = false): array|stdClass
    {

        $r_modifica =  parent::modifica(header: false,aplica_form:  false); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al generar template',data:  $r_modifica);
        }

        $inputs = (new fc_factura_html(html: $this->html_base))->genera_inputs_fc_partida(controler:$this,
            link: $this->link);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al inicializar inputs',data:  $inputs, header: $header,ws:$ws);
        }

        $select = $this->select_fc_factura_id();
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar select datos',data:  $select,
                header: $header,ws:$ws);
        }


        $partidas = (new fc_partida($this->link))->partidas(fc_factura_id: $this->fc_factura_id, html: $this->html);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener sucursales',data:  $partidas, header: $header,ws:$ws);
        }


        $this->partidas = $partidas;
        $this->number_active = 6;

        return $inputs;

    }

    public function modifica_partida(bool $header, bool $ws = false): array|stdClass
    {

        $r_modifica =  parent::modifica(header: false,aplica_form:  false); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al generar template',data:  $r_modifica);
        }

        $registro = (new fc_partida($this->link))->registro(registro_id: $this->fc_partida_id,retorno_obj: true);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al generar template',data:  $registro);
        }

        $this->row_upd = $registro;

        $inputs = (new fc_factura_html(html: $this->html_base))->genera_inputs_fc_partida_modifica(controler:$this,
            link: $this->link);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al inicializar inputs',data:  $inputs, header: $header,ws:$ws);
        }

        $select = $this->select_fc_factura_id();
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar select datos',data:  $select,
                header: $header,ws:$ws);
        }

        $partidas = (new fc_partida($this->link))->partidas(fc_factura_id: $this->fc_factura_id,html: $this->html);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener sucursales',data:  $partidas, header: $header,ws:$ws);
        }

        $this->partidas = $partidas;
        $this->number_active = 6;

        return $inputs;

    }

    public function modifica_partida_bd(bool $header, bool $ws = false): array|stdClass
    {
        $siguiente_view = (new actions())->siguiente_view();
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener siguiente view sucursal',data:  $siguiente_view,
                header: $header,ws:$ws);
        }
        if(isset($_POST['guarda'])){
            unset($_POST['guarda']);
        }
        if(isset($_POST['btn_action_next'])){
            unset($_POST['btn_action_next']);
        }

        $fc_partida_modelo = new fc_partida($this->link);

        $fc_partida_ins = $_POST;
        $fc_partida_ins['fc_factura_id'] = $this->registro_id;

        $r_modifica_bd = $fc_partida_modelo->modifica_bd(registro:$fc_partida_ins, id: $this->fc_partida_id);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al modificar partida',data:  $r_modifica_bd,
                header: $header,ws:$ws);
        }

        if($header){
            $params = array('fc_partida_id'=>$this->fc_partida_id);
            $retorno = (new actions())->retorno_alta_bd(link: $this->link, registro_id: $this->registro_id,
                seccion: $this->tabla, siguiente_view: $siguiente_view, params: $params);
            if(errores::$error){
                return $this->retorno_error(mensaje: 'Error al dar de alta registro', data: $r_modifica_bd,
                    header:  true, ws: $ws);
            }
            header('Location:'.$retorno);
            exit;
        }
        if($ws){
            header('Content-Type: application/json');
            echo json_encode($r_modifica_bd, JSON_THROW_ON_ERROR);
            exit;
        }
        return $r_modifica_bd;

    }

    public function relaciones(bool $header, bool $ws = false){
        $partidas  = (new fc_partida($this->link))->partidas(fc_factura_id: $this->registro_id,html: $this->html);
        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al obtener partidas', data: $partidas);
            print_r($error);
            die('Error');
        }

        $row_upd = $this->modelo->registro(registro_id: $this->registro_id, retorno_obj: true);
        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al obtener factura', data: $row_upd);
            print_r($error);
            die('Error');
        }

        $this->partidas = $partidas;


        $params = array();

        $params['fc_csd_id']['filtro']['fc_csd.id'] = $row_upd->fc_csd_id;
        $params['fc_csd_id']['disabled'] = true;

        $params['com_sucursal_id']['filtro']['com_sucursal.id'] = $row_upd->com_sucursal_id;
        $params['com_sucursal_id']['disabled'] = true;

        $params['cat_sat_tipo_de_comprobante_id']['filtro']['cat_sat_tipo_de_comprobante.id'] = $row_upd->cat_sat_tipo_de_comprobante_id;
        $params['cat_sat_tipo_de_comprobante_id']['disabled'] = true;

        $params['cat_sat_forma_pago_id']['filtro']['cat_sat_forma_pago.id'] = $row_upd->cat_sat_forma_pago_id;
        $params['cat_sat_forma_pago_id']['disabled'] = true;

        $params['cat_sat_metodo_pago_id']['filtro']['cat_sat_metodo_pago.id'] = $row_upd->cat_sat_metodo_pago_id;
        $params['cat_sat_metodo_pago_id']['disabled'] = true;

        $params['cat_sat_moneda_id']['filtro']['cat_sat_moneda.id'] = $row_upd->cat_sat_moneda_id;
        $params['cat_sat_moneda_id']['disabled'] = true;

        $params['com_tipo_cambio_id']['filtro']['com_tipo_cambio.id'] = $row_upd->com_tipo_cambio_id;
        $params['com_tipo_cambio_id']['disabled'] = true;

        $params['cat_sat_uso_cfdi_id']['filtro']['cat_sat_uso_cfdi.id'] = $row_upd->cat_sat_uso_cfdi_id;
        $params['cat_sat_uso_cfdi_id']['disabled'] = true;

        $base = $this->init_modifica(params: $params);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al maquetar datos',data:  $base,
                header: $header,ws:$ws);
        }

        $cat_sat_tipo_relacion_id = (new cat_sat_tipo_relacion_html(html: $this->html_base))
            ->select_cat_sat_tipo_relacion_id(cols: 12,con_registros: true,id_selected: -1,link:  $this->link,
                required: true);
        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al obtener cat_sat_tipo_relacion_id',
                data: $cat_sat_tipo_relacion_id);
            print_r($error);
            die('Error');
        }

        $this->inputs->cat_sat_tipo_relacion_id = $cat_sat_tipo_relacion_id;


        $link = $this->obj_link->link_con_id(accion: 'fc_relacion_alta_bd',link:  $this->link, registro_id: $this->registro_id, seccion: $this->tabla);
        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al obtener link', data: $link);
            print_r($error);
            die('Error');
        }

        $this->link_fc_relacion_alta_bd = $link;

        $link = $this->obj_link->link_con_id(accion: 'fc_factura_relacionada_alta_bd',link:  $this->link, registro_id: $this->registro_id, seccion: $this->tabla);
        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al obtener link', data: $link);
            print_r($error);
            die('Error');
        }

        $this->link_fc_factura_relacionada_alta_bd = $link;

        $relaciones = (new fc_factura(link: $this->link))->get_data_relaciones(fc_factura_id: $this->registro_id);
        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al obtener relaciones', data: $relaciones);
            print_r($error);
            die('Error');
        }


        $filtro = array();
        $filtro['com_cliente.id'] = $row_upd->com_cliente_id;
        $filtro['org_empresa.id'] = $row_upd->org_empresa_id;
        $r_fc_factura = (new fc_factura(link: $this->link))->filtro_and(filtro: $filtro);
        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al obtener facturas', data: $r_fc_factura);
            print_r($error);
            die('Error');
        }
        $facturas_cliente = $r_fc_factura->registros;

        foreach ($relaciones as $indice=>$relacion){
            $facturas_cliente_ = array();
            foreach ($facturas_cliente as $factura_cliente){
                $existe_factura_rel = false;
                foreach ($relacion['fc_facturas_relacionadas'] as $fc_factura_relacionada){

                    if($factura_cliente['fc_factura_id'] === $fc_factura_relacionada['fc_factura_id']){
                        $existe_factura_rel = true;
                        break;
                    }
                }
                if(!$existe_factura_rel){
                    $facturas_cliente_[] = $factura_cliente;
                }
            }

            $relaciones[$indice]['fc_facturas'] = $facturas_cliente_;

        }


        foreach ($relaciones as $indice=>$relacion){

            foreach ($relacion['fc_facturas_relacionadas'] as $indice_fr=>$fc_factura_relacionada){

                $params = $this->params_button_partida(accion_retorno: 'relaciones', fc_factura_id: $this->registro_id);
                if (errores::$error) {
                    return $this->errores->error(mensaje: 'Error al generar params', data: $params);
                }

                $link_elimina_rel = $this->html->button_href(accion: 'elimina_bd', etiqueta: 'Eliminar',
                    registro_id: $fc_factura_relacionada['fc_factura_relacionada_id'],
                    seccion: 'fc_factura_relacionada', style: 'danger',icon: 'bi bi-trash',
                    muestra_icono_btn: true, muestra_titulo_btn: false, params: $params);
                if (errores::$error) {
                    return $this->errores->error(mensaje: 'Error al generar link elimina_bd para partida', data: $link_elimina_rel);
                }
                $relaciones[$indice]['fc_facturas_relacionadas'][$indice_fr]['elimina_bd'] = $link_elimina_rel;

            }

            $params = $this->params_button_partida(accion_retorno: 'relaciones', fc_factura_id: $this->registro_id);
            if (errores::$error) {
                return $this->errores->error(mensaje: 'Error al generar params', data: $params);
            }

            $link_elimina_rel = $this->html->button_href(accion: 'elimina_bd', etiqueta: 'Eliminar',
                registro_id: $relacion['fc_relacion_id'],
                seccion: 'fc_relacion', style: 'danger',icon: 'bi bi-trash',
                muestra_icono_btn: true, muestra_titulo_btn: false, params: $params);
            if (errores::$error) {
                return $this->errores->error(mensaje: 'Error al generar link elimina_bd para partida', data: $link_elimina_rel);
            }
            $relaciones[$indice]['elimina_bd'] = $link_elimina_rel;


        }

        $this->relaciones = $relaciones;


        $button_fc_factura_modifica =  $this->html->button_href(accion: 'modifica', etiqueta: 'Ir a Factura',
            registro_id: $this->registro_id,
            seccion: 'fc_factura', style: 'warning', params: array());
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al generar link', data: $button_fc_factura_modifica);
        }

        $this->button_fc_factura_modifica = $button_fc_factura_modifica;




        return $base->template;
    }

    private function select_fc_factura_id(): array|string
    {
        $select = (new fc_factura_html(html: $this->html_base))->select_fc_factura_id(cols:12,con_registros: true,
            id_selected: $this->registro_id,link:  $this->link, disabled: true);

        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al generar select datos',data:  $select);
        }
        $this->inputs->select->fc_factura_id = $select;

        return $select;
    }

    public function timbra_xml(bool $header, bool $ws = false): array|stdClass{

        $timbre = (new fc_factura(link: $this->link))->timbra_xml(fc_factura_id: $this->registro_id);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al timbrar XML',data:  $timbre, header: $header,ws:$ws);
        }

        $this->link->beginTransaction();
        $siguiente_view = (new actions())->init_alta_bd();
        if(errores::$error){
            $this->link->rollBack();
            return $this->retorno_error(mensaje: 'Error al obtener siguiente view', data: $siguiente_view,
                header:  $header, ws: $ws);
        }

        if($header){

            $retorno = (new actions())->retorno_alta_bd(link: $this->link, registro_id: $this->registro_id,
                seccion: $this->tabla, siguiente_view: "lista");
            if(errores::$error){
                return $this->retorno_error(mensaje: 'Error cambiar de view', data: $retorno,
                    header:  true, ws: $ws);
            }
            header('Location:'.$retorno);
            exit;
        }
        if($ws){
            header('Content-Type: application/json');
            echo json_encode($timbre, JSON_THROW_ON_ERROR);
            exit;
        }

        return $timbre;
    }



    public function ve_partida(bool $header, bool $ws = false): array|stdClass
    {

        $keys = array('fc_partida_id','registro_id');
        $valida = $this->validacion->valida_ids(keys: $keys, registro: $_GET);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al validar GET',data:  $valida, header: $header,ws:$ws);
        }

        $r_modifica =  parent::modifica(header: false,aplica_form:  false); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al generar template',data:  $r_modifica);
        }

        $inputs = (new fc_factura_html(html: $this->html_base))->genera_inputs_fc_partida(controler:$this,
            link: $this->link);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al inicializar inputs',data:  $inputs, header: $header,ws:$ws);
        }

        $data_base = $this->base_data_partida(fc_partida_id: $_GET['fc_partida_id']);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar base',data:  $data_base->htmls,
                header: $header,ws:$ws);
        }

        $inputs_partida = $this->inputs_partida(html: $data_base->htmls->fc_partida,
            fc_partida: $data_base->data->data_partida->fc_partida);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar inputs partida',
                data:  $inputs_partida, header: $header,ws:$ws);
        }

        return $inputs_partida;
    }

    public function verifica_cancelacion(bool $header, bool $ws = false){

        $this->link->beginTransaction();
        $fc_factura = $this->modelo->registro(registro_id: $this->registro_id);
        if(errores::$error){
            $this->link->rollBack();
            return $this->retorno_error(mensaje: 'Error al obtener factura',data:  $fc_factura,header:  $header, ws: $ws);
        }

        $verifica = (new timbra())->consulta_estado_sat($fc_factura['org_empresa_rfc'], $fc_factura['com_cliente_rfc'],
            $fc_factura['fc_factura_total'], $fc_factura['fc_factura_uuid']);

        /*$verifica = (new timbra())->consulta_estado_sat('EKU9003173C9', 'XAXX010101000',
            '1.16', '4a5dc24d-e0a9-4172-9fdd-38b2dfbd4435');*/


        $aplica_etapa = false;
        if($verifica->mensaje === 'Cancelado'){

            $filtro['pr_etapa.descripcion'] = 'cancelado_sat';
            $filtro['fc_factura.id'] = $this->registro_id;
            $existe = (new fc_factura_etapa(link: $this->link))->existe(filtro: $filtro);
            if(errores::$error){
                $this->link->rollBack();
                return $this->retorno_error(mensaje: 'Error al validar etapa',data:  $existe,header:  $header, ws: $ws);
            }
            if(!$existe){
                $aplica_etapa = true;
            }

        }

        if($aplica_etapa) {
            $r_alta_factura_etapa = (new pr_proceso(link: $this->link))->inserta_etapa(adm_accion: 'cancelado_sat', fecha: '',
                modelo: $this->modelo, modelo_etapa: $this->modelo->modelo_etapa, registro_id: $this->registro_id, valida_existencia_etapa: true);
            if (errores::$error) {
                $this->link->rollBack();
                return $this->retorno_error(mensaje: 'Error al insertar etapa', data: $r_alta_factura_etapa, header: $header, ws: $ws);
            }
        }
        $this->link->commit();



        $partidas  = (new fc_partida($this->link))->partidas(fc_factura_id: $this->registro_id,html: $this->html);
        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al obtener partidas', data: $partidas);
            print_r($error);
            die('Error');
        }

        $row_upd = $this->modelo->registro(registro_id: $this->registro_id, retorno_obj: true);
        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al obtener factura', data: $row_upd);
            print_r($error);
            die('Error');
        }

        $this->partidas = $partidas;


        $params = array();



        $params['com_sucursal_id']['filtro']['com_sucursal.id'] = $row_upd->com_sucursal_id;
        $params['com_sucursal_id']['disabled'] = true;

        $params['cat_sat_tipo_de_comprobante_id']['filtro']['cat_sat_tipo_de_comprobante.id'] = $row_upd->cat_sat_tipo_de_comprobante_id;
        $params['cat_sat_tipo_de_comprobante_id']['disabled'] = true;

        $params['cat_sat_forma_pago_id']['filtro']['cat_sat_forma_pago.id'] = $row_upd->cat_sat_forma_pago_id;
        $params['cat_sat_forma_pago_id']['disabled'] = true;

        $params['cat_sat_metodo_pago_id']['filtro']['cat_sat_metodo_pago.id'] = $row_upd->cat_sat_metodo_pago_id;
        $params['cat_sat_metodo_pago_id']['disabled'] = true;

        $params['cat_sat_moneda_id']['filtro']['cat_sat_moneda.id'] = $row_upd->cat_sat_moneda_id;
        $params['cat_sat_moneda_id']['disabled'] = true;

        $params['com_tipo_cambio_id']['filtro']['com_tipo_cambio.id'] = $row_upd->com_tipo_cambio_id;
        $params['com_tipo_cambio_id']['disabled'] = true;

        $params['cat_sat_uso_cfdi_id']['filtro']['cat_sat_uso_cfdi.id'] = $row_upd->cat_sat_uso_cfdi_id;
        $params['cat_sat_uso_cfdi_id']['disabled'] = true;

        $base = $this->init_modifica(params: $params);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al maquetar datos',data:  $base,
                header: $header,ws:$ws);
        }

        $this->mensaje = $verifica->mensaje;

        return $verifica;



    }




}
