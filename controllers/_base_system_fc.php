<?php

namespace gamboamartin\facturacion\controllers;

use base\controller\controler;
use base\orm\modelo;
use config\pac;
use gamboamartin\cat_sat\models\cat_sat_forma_pago;
use gamboamartin\cat_sat\models\cat_sat_metodo_pago;
use gamboamartin\cat_sat\models\cat_sat_moneda;
use gamboamartin\cat_sat\models\cat_sat_regimen_fiscal;
use gamboamartin\cat_sat\models\cat_sat_tipo_de_comprobante;
use gamboamartin\cat_sat\models\cat_sat_uso_cfdi;
use gamboamartin\comercial\models\com_sucursal;
use gamboamartin\comercial\models\com_tipo_cambio;
use gamboamartin\compresor\compresor;
use gamboamartin\direccion_postal\models\dp_calle_pertenece;
use gamboamartin\documento\models\doc_documento;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\html\_base_fc_html;
use gamboamartin\facturacion\html\fc_csd_html;
use gamboamartin\facturacion\html\fc_factura_html;
use gamboamartin\facturacion\html\fc_partida_html;
use gamboamartin\facturacion\models\_cancelacion;
use gamboamartin\facturacion\models\_cuenta_predial;
use gamboamartin\facturacion\models\_data_impuestos;
use gamboamartin\facturacion\models\_data_mail;
use gamboamartin\facturacion\models\_doc;
use gamboamartin\facturacion\models\_etapa;
use gamboamartin\facturacion\models\_notificacion;
use gamboamartin\facturacion\models\_partida;
use gamboamartin\facturacion\models\_pdf;
use gamboamartin\facturacion\models\_plantilla;
use gamboamartin\facturacion\models\_relacion;
use gamboamartin\facturacion\models\_relacionada;
use gamboamartin\facturacion\models\_sellado;
use gamboamartin\facturacion\models\_transacciones_fc;
use gamboamartin\facturacion\models\_uuid_ext;
use gamboamartin\facturacion\models\com_producto;
use gamboamartin\facturacion\models\fc_complemento_pago;
use gamboamartin\facturacion\models\fc_complemento_pago_relacionada;
use gamboamartin\facturacion\models\fc_csd;
use gamboamartin\facturacion\models\fc_factura;
use gamboamartin\facturacion\models\fc_nc_rel;
use gamboamartin\facturacion\models\fc_notificacion;
use gamboamartin\facturacion\models\fc_partida;
use gamboamartin\facturacion\models\fc_retenido;
use gamboamartin\facturacion\models\fc_traslado;
use gamboamartin\facturacion\models\fc_uuid;
use gamboamartin\system\actions;
use gamboamartin\system\html_controler;
use gamboamartin\system\row;
use gamboamartin\xml_cfdi_4\timbra;
use html\cat_sat_motivo_cancelacion_html;
use html\cat_sat_obj_imp_html;
use html\cat_sat_tipo_relacion_html;
use html\com_cliente_html;
use html\com_email_cte_html;
use JsonException;
use PDO;
use stdClass;
use Throwable;

class _base_system_fc extends _base_system{

    protected string $cat_sat_tipo_de_comprobante;
    protected _base_fc_html $html_fc;
    protected array $data_selected_alta = array();
    protected _ctl_partida $ctl_partida;
    protected controlador_com_producto $controlador_com_producto;

    public string $button_fc_factura_relaciones = '';

    public _transacciones_fc $modelo_entidad;
    protected _partida $modelo_partida;
    protected _data_impuestos $modelo_retencion;
    protected _data_impuestos $modelo_traslado;
    protected _cuenta_predial $modelo_predial;
    public _relacionada $modelo_relacionada;
    public _relacion $modelo_relacion;

    protected _uuid_ext $modelo_uuid_ext;
    protected _notificacion $modelo_notificacion;
    protected _cancelacion $modelo_cancelacion;
    protected _doc $modelo_documento;
    protected _etapa $modelo_etapa;
    protected _data_mail $modelo_email;
    protected _sellado $modelo_sello;

    public string $link_fc_partida_alta_bd = '';
    public string $link_fc_email_alta_bd = '';
    public string $button_fc_factura_modifica = '';
    public string $button_fc_factura_correo = '';
    public string $button_fc_factura_envia = '';
    public string $buttons_base = '';

    public string $key_email_id = '';

    public stdClass $partidas;

    public string $link_fc_relacion_alta_bd = '';
    public string $link_fc_factura_relacionada_alta_bd = '';

    public array $relaciones = array();

    public string $key_uuid = '';
    public string $key_folio = '';

    public string $key_fecha = '';
    public string $key_etapa = '';
    public string $key_total = '';
    public string $key_saldo = '';
    public string $key_relacion_id = '';
    public string $key_entidad_id = '';

    public string $button_fc_factura_timbra = '';
    public string $t_head_producto = '';

    public string $thead_relacion;

    public string $inputs_relaciones = '';

    public bool $aplica_monto_relacion = false;

    public string $form_data_fc = '';

    private array $configuraciones_impuestos = array();

    public string $link_fc_factura_nueva_partida = '';
    public string $link_factura_genera_xml = '';
    public string $link_factura_cancela = '';
    public string $link_factura_timbra_xml = '';
    public string $link_adjunta_bd = '';
    public array $documentos = array();


    public function __construct(html_controler $html_, PDO $link, modelo $modelo, stdClass $paths_conf = new stdClass())
    {
        parent::__construct(html_: $html_,link:  $link,modelo:  $modelo,paths_conf:  $paths_conf);

        $this->configuraciones_impuestos['601']['PM']['permitidos'] = array(1,3,999);
        $this->configuraciones_impuestos['601']['PM']['default'] = 1;

        $this->configuraciones_impuestos['601']['PF']['permitidos'] = array(1,3,999);
        $this->configuraciones_impuestos['601']['PF']['default'] = 1;

        $this->configuraciones_impuestos['612']['PM']['permitidos'] = array(1,3,5,999);
        $this->configuraciones_impuestos['612']['PM']['default'] = 1;

        $this->configuraciones_impuestos['612']['PF']['permitidos'] = array(1,3,999);
        $this->configuraciones_impuestos['612']['PF']['default'] = 1;

        $this->configuraciones_impuestos['626']['PM']['permitidos'] = array(2,4,998,999);
        $this->configuraciones_impuestos['626']['PM']['default'] = 2;

        $this->configuraciones_impuestos['626']['PF']['permitidos'] = array(1,3,999);
        $this->configuraciones_impuestos['626']['PF']['default'] = 1;

        if($this->registro_id > 0) {
            $buttons = $this->buttons_base();
            if (errores::$error) {
                return $this->errores->error(mensaje: 'Error al obtener buttons', data: $buttons);
            }

            $this->buttons_base = $buttons;
        }

    }

    public function adjunta(bool $header, bool $ws = false): array|stdClass
    {


        $row_upd = $this->modelo_entidad->registro(registro_id: $this->registro_id, columnas_en_bruto: true, retorno_obj: true);
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al obtener registro', data: $row_upd, header: $header, ws: $ws);
        }

        $this->inputs = new stdClass();

        $columns_ds[] = $this->tabla.'_descripcion_select';
        $filtro[$this->tabla.'.id'] = $this->registro_id;
        $selector_id = $this->html_fc->select_fc_entidad_id(cols: 12,columns_ds:  $columns_ds, con_registros: true,
            disabled: true,filtro:  $filtro, id_selected: $this->registro_id,label: $this->modelo->etiqueta,
            modelo_entidad:  $this->modelo_entidad, registros: array());

        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al maquetar input', data: $selector_id, header: $header, ws: $ws);
        }

        $key_entidad_id = $this->modelo_entidad->key_id;
        $key_entidad_folio = $this->modelo_entidad->tabla.'_folio';

        $this->inputs->$key_entidad_id = $selector_id;


        $fc_factura_folio = $this->html_fc->input_folio(cols: 12,row_upd: $row_upd,
            value_vacio: false, disabled: true);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al maquetar input', data: $fc_factura_folio);
        }

        $this->inputs->$key_entidad_folio = $fc_factura_folio;

        $hidden_row_id = $this->html->hidden(name: $key_entidad_id,value:  $this->registro_id);
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

        $key_filer_id = $this->modelo_entidad->key_filtro_id;
        $filtro[$key_filer_id] = $this->registro_id;


        $button_fc_factura_modifica =  $this->html->button_href(accion: 'modifica', etiqueta: 'Ir a CFDI',
            registro_id: $this->registro_id, seccion: $this->tabla, style: 'warning', params: array());
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al generar link', data: $button_fc_factura_modifica);
        }
        $this->button_fc_factura_modifica = $button_fc_factura_modifica;


        $adjunto = $this->html->input_file(cols: 12,name:  'adjunto',row_upd:  $row_upd,value_vacio:  false,
            place_holder: 'Adjunto');
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al generar adjunto', data: $adjunto);
        }
        $this->inputs->adjunto = $adjunto;

        $link_adjunta = $this->obj_link->link_con_id(accion: 'adjunta_bd',
            link:  $this->link,registro_id: $this->registro_id,seccion: $this->seccion);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al generar link_adjunta', data: $link_adjunta);
        }

        $this->link_adjunta_bd = $link_adjunta;

        $filtro = array();
        $filtro[$this->tabla.'.id'] = $this->registro_id;
        $r_documentos = $this->modelo_documento->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al obtener documentos', data: $r_documentos);
        }

        $documentos = $r_documentos->registros;
        $documentos_out = array();
        $params = array();
        $params['seccion_retorno'] = $this->tabla;
        $params['accion_retorno'] = 'adjunta';
        $params['id_retorno'] = $this->registro_id;
        foreach ($documentos as $key=>$documento){
            if((int)$documento['doc_tipo_documento_id'] !== 9){
                continue;
            }
            $documentos_out[$key]['doc_documento_name_out'] = $documento['doc_documento_name_out'];
            $documentos_out[$key]['doc_tipo_documento_descripcion'] = $documento['doc_tipo_documento_descripcion'];
            $documentos_out[$key]['id'] = $documento[$this->modelo_documento->key_id];

            $btn_del = $this->html_base->button_href(accion:'elimina_bd',etiqueta: 'Elimina',
                registro_id:  $documento[$this->modelo_documento->key_id], seccion: $this->modelo_documento->tabla, style: 'danger', params: $params);

            if(errores::$error){
                return $this->errores->error(mensaje: 'Error al generar boton', data: $btn_del);
            }

            $btn_descarga = $this->html_base->button_href(accion:'descarga',etiqueta: 'Descarga',
                registro_id:  $documento[$this->modelo_documento->key_id], seccion: $this->modelo_documento->tabla,
                style: 'success', params: $params);

            if(errores::$error){
                return $this->errores->error(mensaje: 'Error al generar boton', data: $btn_del);
            }

            $documentos_out[$key]['del'] = $btn_del;
            $documentos_out[$key]['descarga'] = $btn_descarga;

        }

        $this->documentos = $documentos_out;


        return $this->inputs;
    }

    public function adjunta_bd(bool $header, bool $ws = false): array|stdClass
    {
        $this->link->beginTransaction();
        $siguiente_view = (new actions())->init_alta_bd(siguiente_view:"adjunta");
        if(errores::$error){
            $this->link->rollBack();
            return $this->retorno_error(mensaje: 'Error al obtener siguiente view', data: $siguiente_view,
                header:  $header, ws: $ws);
        }

        $file = $_FILES['adjunto'];

        $doc_documento_modelo = new doc_documento(link: $this->link);
        $doc_documento_ins['doc_tipo_documento_id'] = 9;
        $doc_documento_ins['name_out'] = $_FILES['adjunto']['name'];


        $doc_documento_alta = $doc_documento_modelo->alta_documento(registro:$doc_documento_ins,file: $file);
        if(errores::$error){
            $this->link->rollBack();
            return $this->errores->error(mensaje: 'Error al insertar doc', data: $doc_documento_alta);
        }

        $doc_documento_id = $doc_documento_alta->registro_id;

        $fc_factura_documento_ins[$this->modelo_entidad->key_id] = $this->registro_id;
        $fc_factura_documento_ins['doc_documento_id'] = $doc_documento_id;

        $fc_factura_doc = $this->modelo_documento->alta_registro(registro:$fc_factura_documento_ins);
        if(errores::$error){
            $this->link->rollBack();
            return $this->errores->error(mensaje: 'Error al insertar doc', data: $fc_factura_doc);
        }
        $this->link->commit();

        if($header){

            $retorno = (new actions())->retorno_alta_bd(link: $this->link, registro_id: $this->registro_id,
                seccion: $this->tabla, siguiente_view: "adjunta");
            if(errores::$error){
                return $this->retorno_error(mensaje: 'Error al dar de alta registro', data: $fc_factura_doc,
                    header:  true, ws: $ws);
            }
            header('Location:'.$retorno);
            exit;
        }
        if($ws){
            header('Content-Type: application/json');
            echo json_encode($fc_factura_doc, JSON_THROW_ON_ERROR);
            exit;
        }


        return $fc_factura_doc;
    }
    public function ajusta_hora(bool $header, bool $ws = false): array|stdClass
    {

        $controladores = $this->init_controladores(ctl_partida: $this->ctl_partida, paths_conf: $this->paths_conf);
        if(errores::$error){
            $error = $this->errores->error(mensaje: 'Error al inicializar controladores',data:  $controladores);
            print_r($error);
            die('Error');
        }

        $base = $this->init_modifica(fecha_original: true, modelo_entidad: $this->modelo_entidad);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al maquetar datos',data:  $base,
                header: $header,ws:$ws);
        }


        $v_fecha_hora = $this->row_upd->fecha;

        $fecha_hora = (new html_controler(html: $this->html_base))->input_fecha(cols: 6, row_upd: new stdClass(),
            value_vacio: false, value: $v_fecha_hora, value_hora: true);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al maquetar input',data:  $fecha_hora,
                header: $header,ws:$ws);
        }

        $this->inputs->fecha_hora = $fecha_hora;


        return $base->template;
    }
    public function alta(bool $header, bool $ws = false): array|string
    {
        /**
         * REFACTORIZAR
         */
        $parents = $this->parents();
        if(errores::$error){
            $error = $this->errores->error(mensaje: 'Error al obtener parents',data:  $parents);
            print_r($error);
            exit;
        }

        $r_alta =  parent::alta(header: false);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar template',data:  $r_alta, header: $header,ws:$ws);
        }

        $tipo_comprobante = $this->get_tipo_comprobante();
        if(errores::$error){
            $error = $this->errores->error(mensaje: 'Error al obtener tipo de comprobante',data:  $tipo_comprobante);
            print_r($error);
            die('Error');
        }

        $this->asignar_propiedad(identificador: 'cat_sat_tipo_de_comprobante_id',
            propiedades: ["id_selected" => $tipo_comprobante,
                "filtro" => array('cat_sat_tipo_de_comprobante.id' => $tipo_comprobante)]);

        $this->row_upd->fecha = date('Y-m-d');
        $this->row_upd->subtotal = 0;
        $this->row_upd->descuento = 0;
        $this->row_upd->impuestos_trasladados = 0;
        $this->row_upd->impuestos_retenidos = 0;
        $this->row_upd->total = 0;
        $this->row_upd->exportacion = '01';


        $inputs = $this->genera_inputs(keys_selects:  $this->keys_selects);
        if(errores::$error){
            $error = $this->errores->error(mensaje: 'Error al generar inputs',data:  $inputs);
            print_r($error);
            die('Error');
        }

        $observaciones = $this->html_fc->input_observaciones(cols: 12,row_upd: new stdClass(),value_vacio: false);
        if(errores::$error){
            $error = $this->errores->error(mensaje: 'Error al generar observaciones',data:  $observaciones);
            print_r($error);
            die('Error');
        }
        $this->inputs->observaciones = $observaciones;

        $com_tipos_cambio = (new com_tipo_cambio(link: $this->link))->registros_activos();
        if(errores::$error){
            $error = $this->errores->error(mensaje: 'Error al obtener tipos de cambio',data:  $com_tipos_cambio);
            print_r($error);
            die('Error');
        }


        $fc_csds = (new fc_csd(link: $this->link))->registros_activos();
        if(errores::$error){
            $error = $this->errores->error(mensaje: 'Error al obtener fc_csds',data:  $fc_csds);
            print_r($error);
            die('Error');
        }

        $id_selected = -1;
        if(count($fc_csds) === 1){
            $id_selected = $fc_csds[0]['fc_csd_id'];
        }
        $cols = 12;
        $link = $this->link;
        $fc_csd_id = (new fc_csd_html(html: $this->html_base))->select_fc_csd_id(cols: $cols,
            con_registros:  true,id_selected:  $id_selected, link: $link,label: 'Empresa');

        if(errores::$error){
            $error = $this->errores->error(mensaje: 'Error al obtener fc_csd_id',data:  $fc_csd_id);
            print_r($error);
            die('Error');
        }

        $this->inputs->fc_csd_id = $fc_csd_id;

        $filtro = array();
        $filtro[$this->tabla.'.es_plantilla'] = 'activo';
        $r_plantillas = $this->modelo_entidad->filtro_and(filtro: $filtro);
        if(errores::$error){
            $error = $this->errores->error(mensaje: 'Error al obtener plantilla',data:  $r_plantillas);
            print_r($error);
            die('Error');
        }

        $plantillas = $r_plantillas->registros;
        $disabled = true;
        if($r_plantillas->n_registros > 0){
            $disabled = false;
        }

        $columnas_ds[] = "com_cliente_rfc";
        $columnas_ds[] = "com_cliente_razon_social";
        $columnas_ds[] = $this->modelo_entidad->tabla."_total";
        $extra_params_keys[] = $this->modelo_entidad->key_id;
        $select_plantilla = (new html_controler(html: $this->html_base))->select_catalogo(cols: $cols,
            con_registros: true, id_selected: -1, modelo: $this->modelo_entidad, columns_ds: $columnas_ds,
            disabled: $disabled, extra_params_keys: $extra_params_keys, label: 'Plantilla', name: 'plantilla',
            registros: $plantillas);
        if(errores::$error){
            $error = $this->errores->error(mensaje: 'Error al obtener selector',data:  $select_plantilla);
            print_r($error);
            die('Error');
        }

        $this->inputs->plantillas = $select_plantilla;


        return $r_alta;
    }

    public function alta_partida_bd(bool $header, bool $ws = false){

        $this->link->beginTransaction();

        $siguiente_view = (new actions())->init_alta_bd();
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


        $factura = $this->modelo_entidad->get_factura(modelo_partida: $this->modelo_partida,
            modelo_predial: $this->modelo_predial, modelo_relacion: $this->modelo_relacion,
            modelo_relacionada: $this->modelo_relacionada, modelo_retencion: $this->modelo_retencion,
            modelo_traslado: $this->modelo_traslado, modelo_uuid_ext: $this->modelo_uuid_ext,
            registro_id: $this->registro_id);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener factura', data: $factura, header: $header, ws: $ws);
        }

        $registro = $_POST;
        $registro[$this->modelo_entidad->key_id] = $this->registro_id;

        $r_alta_partida_bd = $this->modelo_partida->alta_registro(registro:$registro); // TODO: Change the autogenerated stub
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
            try {
                echo json_encode($r_alta_partida_bd, JSON_THROW_ON_ERROR);
            }
            catch (Throwable $e){
                print_r($r_alta_partida_bd);
                print_r($e);
            }

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

    private function buttons_base(): array|string
    {
        $button_fc_factura_relaciones =  $this->html->button_href(accion: 'relaciones', etiqueta: 'Asignar Relacion',
            registro_id: $this->registro_id, seccion: $this->seccion, style: 'warning', cols: 2, params: array());
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al generar link', data: $button_fc_factura_relaciones);
        }

        $button_fc_factura_timbra =  $this->html->button_href(accion: 'timbra_xml', etiqueta: 'Timbrar',
            registro_id: $this->registro_id, seccion: $this->seccion, style: 'danger', cols: 2, params: array());
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al generar link', data: $button_fc_factura_timbra);
        }
        $button_fc_factura_correo =  $this->html->button_href(accion: 'correo', etiqueta: 'Agregar Correos',
            registro_id: $this->registro_id, seccion: $this->seccion, style: 'success', cols: 2, params: array());
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al generar link', data: $button_fc_factura_correo);
        }

        $button_fc_factura_envia =  $this->html->button_href(accion: 'envia_cfdi', etiqueta: 'Envia Por Correo',
            registro_id: $this->registro_id, seccion: $this->seccion, style: 'success', cols: 2, params: array());
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al generar link', data: $button_fc_factura_envia);
        }

        $button_fc_factura_exportar_documentos =  $this->html->button_href(accion: 'exportar_documentos', etiqueta: 'Descargar',
            registro_id: $this->registro_id, seccion: $this->seccion, style: 'success', cols: 2, params: array());
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al generar link', data: $button_fc_factura_exportar_documentos);
        }
        $button_fc_factura_adjunta =  $this->html->button_href(accion: 'adjunta', etiqueta: 'Adjunta Docs',
            registro_id: $this->registro_id, seccion: $this->seccion, style: 'info', cols: 2, params: array());
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al generar link', data: $button_fc_factura_exportar_documentos);
        }

        $buttons = $button_fc_factura_relaciones.$button_fc_factura_timbra.$button_fc_factura_correo.
            $button_fc_factura_envia.$button_fc_factura_exportar_documentos.$button_fc_factura_adjunta;

        return "<div class='col-md-12 buttons-form'>$buttons</div>";


    }

    public function correo(bool $header, bool $ws = false): array|stdClass
    {

        $this->key_email_id = $this->modelo_email->key_id;

        $row_upd = $this->modelo_entidad->registro(registro_id: $this->registro_id, columnas_en_bruto: true, retorno_obj: true);
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al obtener registro', data: $row_upd, header: $header, ws: $ws);
        }


        $this->inputs = new stdClass();


        $columns_ds[] = $this->tabla.'_descripcion_select';
        $filtro[$this->tabla.'.id'] = $this->registro_id;
        $selector_id = $this->html_fc->select_fc_entidad_id(cols: 12,columns_ds:  $columns_ds, con_registros: true,
            disabled: true,filtro:  $filtro, id_selected: $this->registro_id,label: $this->modelo->etiqueta,
            modelo_entidad:  $this->modelo_entidad, registros: array());

        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al maquetar input', data: $selector_id, header: $header, ws: $ws);
        }

        $key_entidad_id = $this->modelo_entidad->key_id;
        $key_entidad_folio = $this->modelo_entidad->tabla.'_folio';

        $this->inputs->$key_entidad_id = $selector_id;


        $fc_factura_folio = $this->html_fc->input_folio(cols: 12,row_upd: $row_upd,
            value_vacio: false, disabled: true);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al maquetar input', data: $fc_factura_folio);
        }

        $this->inputs->$key_entidad_folio = $fc_factura_folio;


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

        $hidden_row_id = $this->html->hidden(name: $key_entidad_id,value:  $this->registro_id);
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

        $key_filer_id = $this->modelo_entidad->key_filtro_id;
        $filtro[$key_filer_id] = $this->registro_id;

        $r_fc_email = $this->modelo_email->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al obtener correos', data: $r_fc_email);
        }

        $emails_facturas = $r_fc_email->registros;

        $key_email_id = $this->modelo_email->key_id;

        foreach ($emails_facturas as $indice=>$email_factura){

            $link_elimina = $this->button_elimina_correo(name_modelo_email: $this->modelo_email->tabla,
                name_modelo_entidad: $this->modelo_entidad->tabla, registro_email_id: $email_factura[$key_email_id],
                registro_entidad_id: $this->registro_id);
            if (errores::$error) {
                return $this->errores->error(mensaje: 'Error al generar link elimina_bd para partida', data: $link_elimina);
            }
            $emails_facturas[$indice]['elimina_bd'] = $link_elimina;

            $link_status = $this->button_status_correo(modelo_email: $this->modelo_email,
                name_modelo_entidad: $this->modelo_entidad->tabla, registro_email_id: $email_factura[$key_email_id],
                registro_entidad_id: $this->registro_id);
            if (errores::$error) {
                return $this->errores->error(mensaje: 'Error al generar link elimina_bd para partida', data: $link_elimina);
            }
            $emails_facturas[$indice]['status'] = $link_status;
        }


        $this->registros['emails_facturas'] = $emails_facturas;


        $button_fc_factura_modifica =  $this->html->button_href(accion: 'modifica', etiqueta: 'Ir a CFDI',
            registro_id: $this->registro_id, seccion: $this->tabla, style: 'warning', params: array());
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al generar link', data: $button_fc_factura_modifica);
        }

        $this->button_fc_factura_modifica = $button_fc_factura_modifica;
        return $this->inputs;
    }

    public function duplica(bool $header, bool $ws = false){
        $this->link->beginTransaction();

        $duplica = $this->modelo_entidad->duplica(modelo_partida: $this->modelo_partida,
            modelo_retencion: $this->modelo_retencion, modelo_traslado: $this->modelo_traslado, registro_id: $this->registro_id);
        if (errores::$error) {
            $this->link->rollBack();
            return $this->retorno_error(mensaje: 'Error al insertar registro', data: $duplica, header: $header,ws: $ws);
        }

        $this->link->commit();

        if($header){

            $retorno = (new actions())->retorno_alta_bd(link: $this->link, registro_id: $duplica,
                seccion: $this->tabla, siguiente_view: "modifica");
            if(errores::$error){
                return $this->retorno_error(mensaje: 'Error al dar de alta registro', data: $duplica,
                    header:  true, ws: $ws);
            }
            header('Location:'.$retorno);
            exit;
        }
        if($ws){
            header('Content-Type: application/json');
            echo json_encode($duplica, JSON_THROW_ON_ERROR);
            exit;
        }


        return $duplica;

    }

    public function es_plantilla(bool $header, bool $ws): array|stdClass
    {
        $en_transaccion = false;
        if($this->link->inTransaction()){
            $en_transaccion = true;
        }

        if(!$en_transaccion){
            $this->link->beginTransaction();
        }

        $upd = $this->row_upd(key: __FUNCTION__,verifica_permite_transaccion: false);
        if(errores::$error){
            $this->link->rollBack();
            return $this->retorno_error(mensaje: 'Error al obtener row upd',data:  $upd, header: $header,ws:  $ws);
        }
        $this->link->commit();

        $_SESSION[$upd->salida][]['mensaje'] = $upd->mensaje.' del id '.$this->registro_id;
        $this->header_out(result: $upd, header: $header,ws:  $ws);

        return $upd;
    }

    private function fc_externas(int $com_cliente_id){
        $filtro['com_cliente.id'] = $com_cliente_id;

        $r_fc_uuid = (new fc_uuid(link: $this->link))->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al obtener relaciones externas', data: $r_fc_uuid);
        }

        return $r_fc_uuid->registros;
    }

    private function get_tipo_comprobante(): array|int
    {
        $tipo_comprobante = $this->tipo_de_comprobante_predeterminado();
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener tipo de comprobante predeterminado',
                data:  $tipo_comprobante);
        }

        return $tipo_comprobante->registros[0]['cat_sat_tipo_de_comprobante_id'];
    }

    public function init_datatable(): stdClass
    {

        $columns[$this->modelo->tabla."_id"]["titulo"] = "Id";
        $columns[$this->modelo->tabla."_folio"]["titulo"] = "Fol";
        $columns["com_cliente_razon_social"]["titulo"] = "Cliente";
        $columns["com_cliente_rfc"]["titulo"] = "RFC";
        $columns[$this->modelo->tabla."_fecha"]["titulo"] = "Fecha";
        $columns[$this->modelo->tabla."_total"]["titulo"] = "Total";
        $columns[$this->modelo->tabla."_monto_saldo_aplicado"]["titulo"] = "Pagos";
        $columns[$this->modelo->tabla."_saldo"]["titulo"] = "Saldo";
        $columns[$this->modelo->tabla."_folio_fiscal"]["titulo"] = "UUID";
        $columns[$this->modelo->tabla."_etapa"]["titulo"] = "Estatus";


        $filtro = array($this->modelo->tabla.'.folio','com_cliente.razon_social','com_cliente.rfc',
            $this->modelo->tabla.'.fecha',$this->modelo->tabla.'.folio_fiscal');

        $datatables = new stdClass();
        $datatables->columns = $columns;
        $datatables->filtro = $filtro;

        return $datatables;
    }

    public function init_inputs(): array
    {
        $identificador = "fc_csd_id";
        $propiedades = array("label" => "Empresa", "cols" => 12,"extra_params_keys"=>array("fc_csd_serie"));
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "com_sucursal_id";
        $propiedades = array("label" => "Cliente", "cols" => 12,"extra_params_keys" => array("com_cliente_cat_sat_forma_pago_id",
            "com_cliente_cat_sat_metodo_pago_id","com_cliente_cat_sat_moneda_id","com_cliente_cat_sat_uso_cfdi_id"));
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "cat_sat_forma_pago_id";
        $propiedades = array("label" => "Forma Pago",
            'id_selected'=>$this->data_selected_alta['cat_sat_forma_pago_id']['id'],
            'filtro'=>$this->data_selected_alta['cat_sat_forma_pago_id']['filtro'],
            'extra_params_keys'=>array('cat_sat_forma_pago_codigo')
            );
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "cat_sat_metodo_pago_id";
        $propiedades = array("label" => "Metodo Pago",
            'id_selected'=>$this->data_selected_alta['cat_sat_metodo_pago_id']['id'],
            'filtro'=>$this->data_selected_alta['cat_sat_metodo_pago_id']['filtro'],
            'extra_params_keys'=>array('cat_sat_metodo_pago_codigo'));
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "cat_sat_moneda_id";
        $propiedades = array("label" => "Moneda",
            'id_selected'=>$this->data_selected_alta['cat_sat_moneda_id']['id'],
            'filtro'=>$this->data_selected_alta['cat_sat_moneda_id']['filtro']);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "com_tipo_cambio_id";
        $propiedades = array("label" => "Tipo Cambio",
            'id_selected'=>$this->data_selected_alta['com_tipo_cambio_id']['id'],
            'filtro'=>$this->data_selected_alta['com_tipo_cambio_id']['filtro']);


        $prop = $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al asignar propiedad',data:  $prop);
        }

        $identificador = "cat_sat_uso_cfdi_id";
        $propiedades = array("label" => "Uso CFDI",
            'id_selected'=>$this->data_selected_alta['cat_sat_uso_cfdi_id']['id'],
            'filtro'=>$this->data_selected_alta['cat_sat_uso_cfdi_id']['filtro']);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "cat_sat_tipo_de_comprobante_id";
        $propiedades = array("label" => "Tipo Comprobante");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "dp_calle_pertenece_id";
        $propiedades = array("label" => "Calle");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "cat_sat_regimen_fiscal_id";
        $propiedades = array("label" => "Regimen Fiscal");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "folio";
        $propiedades = array("place_holder" => "Folio", 'required'=>false, 'disabled'=>true);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "exportacion";
        $propiedades = array("place_holder" => "Exportación");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "serie";
        $propiedades = array("place_holder" => "Serie", 'required'=>false,'disabled'=>true);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "subtotal";
        $propiedades = array("place_holder" => "Subtotal", "cols" => 4,"disabled" => true);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "descuento";
        $propiedades = array("place_holder" => "Descuento", "cols" => 4,"disabled" => true);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "impuestos_trasladados";
        $propiedades = array("place_holder" => "Imp. Trasladados", "disabled" => true);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "impuestos_retenidos";
        $propiedades = array("place_holder" => "Imp. Retenidos", "disabled" => true);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "total";
        $propiedades = array("place_holder" => "Total", "cols" => 4, "disabled" => true);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "fecha";
        $propiedades = array("place_holder" => "Fecha");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        return $this->keys_selects;
    }

    public function inserta_factura_plantilla_bd(bool $header, bool $ws = false){
        $this->link->beginTransaction();


        $siguiente_view = (new actions())->init_alta_bd();
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener siguiente view', data: $siguiente_view,
                header:  $header, ws: $ws);
        }

        $seccion_retorno = $this->tabla;
        if(isset($_POST['seccion_retorno'])){
            $seccion_retorno = $_POST['seccion_retorno'];
            unset($_POST['seccion_retorno']);
        }

        $id_retorno = -1;
        if(isset($_POST['id_retorno'])){
            $id_retorno = $_POST['id_retorno'];
            unset($_POST['id_retorno']);
        }

        $plantilla = new _plantilla(modelo_entidad: $this->modelo_entidad, modelo_partida: $this->modelo_partida,
            modelo_retenido: $this->modelo_retencion, modelo_traslado: $this->modelo_traslado,
            row_entidad_id: $_GET['fc_factura_id']);


        $row_entidad_new = $plantilla->aplica_plantilla();
        if(errores::$error){
            $this->link->rollBack();
            return $this->retorno_error(mensaje: 'Error al integrar rows_imp_ins',data:  $row_entidad_new,
                    header: $header,ws:  $ws);
        }

        $this->link->commit();

        if($header){
            if($id_retorno === -1) {
                $id_retorno = $row_entidad_new->registro_id;
            }
            $this->retorno_base(registro_id:$id_retorno, result: $row_entidad_new, siguiente_view: $siguiente_view,
                ws:  $ws,seccion_retorno: $seccion_retorno, valida_permiso: true);
        }
        if($ws){
            header('Content-Type: application/json');
            try {
                echo json_encode($row_entidad_new, JSON_THROW_ON_ERROR);
            }
            catch (Throwable $e){
                $error = (new errores())->error(mensaje: 'Error al maquetar JSON' , data: $e);
                print_r($error);
            }
            exit;
        }
        $row_entidad_new->siguiente_view = $siguiente_view;
        return $row_entidad_new;

    }

    /**
     * Integra los parents de manera ordenada para su peticion
     * @return array
     * @version 8.10.0
     */
    private function parents(): array
    {
        $this->parents_verifica[] = (new com_sucursal(link: $this->link));
        $this->parents_verifica[] = (new cat_sat_regimen_fiscal(link: $this->link));
        $this->parents_verifica[] = (new dp_calle_pertenece(link: $this->link));
        $this->parents_verifica[] = (new cat_sat_tipo_de_comprobante(link: $this->link));
        $this->parents_verifica[] = (new cat_sat_uso_cfdi(link: $this->link));
        $this->parents_verifica[] = (new com_tipo_cambio(link: $this->link));
        $this->parents_verifica[] = (new cat_sat_moneda(link: $this->link));
        $this->parents_verifica[] = (new cat_sat_metodo_pago(link: $this->link));
        $this->parents_verifica[] = (new cat_sat_forma_pago(link: $this->link));
        $this->parents_verifica[] = (new fc_csd(link: $this->link));
        $this->parents_verifica[] = (new com_producto(link: $this->link));
        return $this->parents_verifica;
    }

    private function button_elimina_correo(string $name_modelo_email, string $name_modelo_entidad,
                                           int $registro_email_id, int $registro_entidad_id): array|string
    {
        $params = $this->params_button_partida(accion_retorno: 'correo', name_modelo_entidad: $name_modelo_entidad,
            registro_entidad_id: $registro_entidad_id);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al generar params', data: $params);
        }

        $link_elimina = $this->html->button_href(accion: 'elimina_bd', etiqueta: 'Eliminar',
            registro_id: $registro_email_id, seccion: $name_modelo_email, style: 'danger',icon: 'bi bi-trash',
            muestra_icono_btn: true, muestra_titulo_btn: false, params: $params);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al generar link elimina_bd para partida', data: $link_elimina);
        }
        return $link_elimina;
    }
    private function button_status_correo(_data_mail $modelo_email, string $name_modelo_entidad,
                                          int $registro_email_id, int $registro_entidad_id): array|string
    {
        $params = $this->params_button_partida(accion_retorno: 'correo', name_modelo_entidad: $name_modelo_entidad,
            registro_entidad_id: $registro_entidad_id);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al generar params', data: $params);
        }

        $fc_email = $modelo_email->registro(registro_id: $registro_email_id, retorno_obj: true);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al obtener fc_email', data: $fc_email);
        }

        $key_email_status = $modelo_email->tabla.'_status';
        $style = 'success';
        if($fc_email->$key_email_status === 'inactivo'){
            $style = 'danger';
        }


        $link_status = $this->html->button_href(accion: 'status', etiqueta: 'Status', registro_id: $registro_email_id,
            seccion: $modelo_email->tabla, style: $style,icon: 'bi bi-file-diff', muestra_icono_btn: true,
            muestra_titulo_btn: false, params: $params);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al generar link elimina_bd para partida', data: $link_status);
        }
        return $link_status;
    }

    public function cancela(bool $header, bool $ws = false){

        $key_filter_id = $this->tabla.'.id';
        $key_folio = $this->tabla.'_folio';
        $key_total = $this->tabla.'_total';
        $key_fecha = $this->tabla.'_fecha';
        $key_saldo = $this->tabla.'_saldo';

        $filtro[$key_filter_id] = $this->registro_id;
        $columns_ds = array($key_folio,'com_cliente_rfc',$key_total,$key_fecha);

        $fc_factura_id = $this->html_fc->select_fc_entidad_id(cols: 12, columns_ds: $columns_ds, con_registros: true,
            disabled: true, filtro: $filtro, id_selected: $this->registro_id, label: 'CFDI',
            modelo_entidad: $this->modelo_entidad, registros: array());
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

        $link_factura_cancela = $this->obj_link->link_con_id(accion: 'cancela_bd',link: $this->link,
            registro_id: $this->registro_id,seccion: $this->tabla);
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

        $r_fc_cancelacion = $this->modelo_entidad->cancela_bd(
            cat_sat_motivo_cancelacion_id: $_POST['cat_sat_motivo_cancelacion_id'],
            modelo_cancelacion: $this->modelo_cancelacion, modelo_etapa: $this->modelo_etapa,
            registro_id: $this->registro_id);
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



    private function data_partida(int $fc_partida_id): array|stdClass
    {
        $data_partida = (new fc_partida($this->link))->data_partida_obj(registro_partida_id: $fc_partida_id);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener partida',data:  $data_partida);
        }

        $data = new stdClass();
        $data->data_partida = $data_partida;

        return $data;
    }



    public function descarga_xml(bool $header, bool $ws = false){
        $ruta_xml = $this->modelo_documento->get_factura_documento(key_entidad_filter_id: $this->tabla.'.id',
            registro_id: $this->registro_id, tipo_documento: "xml_sin_timbrar");
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener XML',data:  $ruta_xml, header: $header,ws:$ws);
        }

        $fc_factura = $this->modelo->registro(registro_id: $this->registro_id, retorno_obj: true);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener factura',data:  $fc_factura, header: $header,ws:$ws);
        }

        $key_serie = $this->tabla.'_serie';
        $key_folio = $this->tabla.'_folio';

        $file_name = $fc_factura->$key_serie.$fc_factura->$key_folio.'.xml';

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
        return $file_name;

    }

    public function elimina_sin_restriccion(bool $header, bool $ws = false){


        $r_elimina = $this->modelo_entidad->elimina_bd(id: $this->registro_id);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al r_elimina factura',data:  $r_elimina, header: $header,ws:$ws);
        }

        if($header){

            $retorno = (new actions())->retorno_alta_bd(link: $this->link, registro_id: $this->registro_id,
                seccion: $this->tabla, siguiente_view: "lista");
            if(errores::$error){
                return $this->retorno_error(mensaje: 'Error al dar de alta registro', data: $r_elimina,
                    header:  true, ws: $ws);
            }
            header('Location:'.$retorno);
            exit;
        }
        if($ws){
            header('Content-Type: application/json');
            echo json_encode($r_elimina, JSON_THROW_ON_ERROR);
            exit;
        }

        return $r_elimina;
    }

    public function envia_cfdi(bool $header, bool $ws = false){

        $genera_pdf = $this->genera_pdf(header: false);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar pdf',data:  $genera_pdf, header: $header,ws:$ws);
        }

        $inserta_notificacion = $this->modelo_entidad->inserta_notificacion(modelo_doc: $this->modelo_documento,
            modelo_email: $this->modelo_email, modelo_notificacion: $this->modelo_notificacion, registro_id: $this->registro_id);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al insertar notificacion',data:  $inserta_notificacion, header: $header,ws:$ws);
        }


        $envia_notificacion = $this->modelo_entidad->envia_factura(
            modelo_notificacion: $this->modelo_notificacion, registro_id: $this->registro_id);
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

        $modelo_notificacion = new fc_notificacion(link: $this->link);

        $this->link->beginTransaction();
        $notifica = (new fc_factura(link: $this->link))->envia_factura(modelo_notificacion: $modelo_notificacion,
            registro_id: $this->registro_id);
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

    private function existe_uuid_externo(array $fc_uuid, string $name_entidad_relacion, _uuid_ext $modelo_uuid_ext,
                                         array $row_relacion_ext): bool|array
    {

        $key_filtro_id = $name_entidad_relacion.'.id';

        $key_relacion_id = $name_entidad_relacion.'_id';

        $filtro[$key_filtro_id] = $row_relacion_ext[$key_relacion_id];
        $filtro['fc_uuid.id'] = $fc_uuid['fc_uuid_id'];



        $existe = $modelo_uuid_ext->existe(filtro: $filtro);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al validar si existe', data: $existe);
        }


        return $existe;
    }

    public function exportar_documentos(bool $header, bool $ws = false){



        $ruta_xml = $this->modelo_documento->get_factura_documento(
            key_entidad_filter_id: $this->modelo_entidad->key_filtro_id, registro_id: $this->registro_id,
            tipo_documento: "xml_sin_timbrar");
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener XML',data:  $ruta_xml, header: $header,ws:$ws);
        }
        
        if(!file_exists($ruta_xml)){
            return $this->retorno_error(mensaje: 'Error al no existe xml',data:  $ruta_xml, header: $header,ws:$ws);
        }

        $ruta_pdf = (new _pdf())->pdf(descarga: false, guarda: true, link: $this->link,
            modelo_documento: $this->modelo_documento, modelo_entidad: $this->modelo_entidad,
            modelo_partida: $this->modelo_partida, modelo_predial: $this->modelo_predial,
            modelo_relacion: $this->modelo_relacion, modelo_relacionada: $this->modelo_relacionada,
            modelo_retencion: $this->modelo_retencion, modelo_sellado: $this->modelo_sello,
            modelo_traslado: $this->modelo_traslado, modelo_uuid_ext: $this->modelo_uuid_ext, registro_id: $this->registro_id);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar PDF',data:  $ruta_pdf, header: $header,ws:$ws);
        }


        $fc_factura = $this->modelo->registro(registro_id: $this->registro_id, retorno_obj: true);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener CFDI',data:  $fc_factura, header: $header,ws:$ws);
        }

        $key_serie = $this->tabla.'_serie';
        $key_folio = $this->tabla.'_folio';

        $name_zip = $fc_factura->$key_folio;

        $archivos = array();

        $archivos[$ruta_xml] = $fc_factura->$key_serie.$fc_factura->$key_folio.".xml";
        $archivos[$ruta_pdf] = $fc_factura->$key_serie.$fc_factura->$key_folio.".pdf";


        Compresor::descarga_zip_multiple(archivos: $archivos,name_zip: $name_zip);


        exit;
    }

    public function fc_factura_relacionada_alta_bd(bool $header, bool $ws = false){


        $fc_facturas_id = $_POST['fc_facturas_id'];



        $alta = array();
        foreach ($fc_facturas_id as $fc_factura_id=>$fc_relacion){

            $entidad_origen = key($fc_relacion);
            $fc_relacion_id = $fc_relacion[$entidad_origen];


            if($entidad_origen === $this->modelo_entidad->key_id) {

                $r_fc_factura_relacionada = $this->inserta_relacionada(
                    fc_facturas_montos: array(),
                    key_modelo_base_id: $this->modelo_entidad->key_id, key_modelo_rel_id: $this->modelo_relacion->key_id,
                    modelo_relacionada: $this->modelo_relacionada, registro_entidad_id: $fc_factura_id, relacion_id: $fc_relacion_id);
                if (errores::$error) {
                    return $this->retorno_error(mensaje: 'Error al dar de alta registro', data: $r_fc_factura_relacionada,
                        header: true, ws: $ws);
                }

            }
            else{

                if($this->tabla === 'fc_nota_credito' && $entidad_origen === 'fc_factura_id') {

                    $fc_facturas_montos = array();
                    if(isset($_POST['fc_facturas_id_monto'])){
                        $fc_facturas_montos = $_POST['fc_facturas_id_monto'];
                    }

                    $modelo_relacionada = new fc_nc_rel(link: $this->link);

                    $r_fc_factura_relacionada = $this->inserta_relacionada(fc_facturas_montos: $fc_facturas_montos,
                        key_modelo_base_id: 'fc_factura_id', key_modelo_rel_id: $this->modelo_relacion->key_id,
                        modelo_relacionada:  $modelo_relacionada, registro_entidad_id: $fc_factura_id,
                        relacion_id: $fc_relacion_id);

                    if (errores::$error) {
                        return $this->retorno_error(mensaje: 'Error al dar de alta registro',
                            data: $r_fc_factura_relacionada, header: true, ws: $ws);
                    }

                }
                else{

                    if($this->tabla === 'fc_complemento_pago'){
                        $fc_facturas_montos = array();
                        $modelo_relacionada = new fc_complemento_pago_relacionada(link: $this->link);

                        $r_fc_factura_relacionada = $this->inserta_relacionada(fc_facturas_montos: $fc_facturas_montos,
                            key_modelo_base_id: 'fc_complemento_pago_id', key_modelo_rel_id: $this->modelo_relacion->key_id,
                            modelo_relacionada:  $modelo_relacionada, registro_entidad_id: $fc_factura_id,
                            relacion_id: $fc_relacion_id);

                        if (errores::$error) {
                            return $this->retorno_error(mensaje: 'Error al dar de alta registro',
                                data: $r_fc_factura_relacionada, header: true, ws: $ws);
                        }
                    }
                    else {
                        $modelo_relacionada = $this->modelo_uuid_ext;

                        $r_fc_factura_relacionada = $this->inserta_relacionada(
                            fc_facturas_montos: array(),
                            key_modelo_base_id: 'fc_uuid_id', key_modelo_rel_id: $this->modelo_relacion->key_id,
                            modelo_relacionada: $modelo_relacionada, registro_entidad_id: $fc_factura_id, relacion_id: $fc_relacion_id);
                        if (errores::$error) {
                            return $this->retorno_error(mensaje: 'Error al dar de alta registro', data: $r_fc_factura_relacionada,
                                header: true, ws: $ws);
                        }
                    }


                }
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

    /**
     * @param bool $header
     * @param bool $ws
     * @return array|stdClass|void
     * @throws JsonException
     */
    public function fc_relacion_alta_bd(bool $header, bool $ws = false){


        $fc_relacion_ins[$this->modelo_entidad->key_id] = $this->registro_id;

        $fc_relacion_ins['cat_sat_tipo_relacion_id'] = $_POST['cat_sat_tipo_relacion_id'];
        $r_fc_relacion = $this->modelo_relacion->alta_registro(registro: $fc_relacion_ins);
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

    private function genera_base_upd(_transacciones_fc $modelo_entidad, _partida $modelo_partida,
                                     _data_impuestos $modelo_retencion, _data_impuestos $modelo_traslado,
                                     stdClass $row_upd, array $params): array|stdClass
    {
        $params = $this->params_base(params: $params,row_upd:  $row_upd);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al obtener params', data: $params);
        }

        $base = $this->init_modifica(fecha_original: false, modelo_entidad: $modelo_entidad, params: $params);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al maquetar datos',data:  $base);
        }

        return $base;
    }

    public function genera_pdf(bool $header, bool $ws = false){


        $pdf = (new _doctos())->pdf(modelo_documento: $this->modelo_documento,modelo_entidad:  $this->modelo_entidad,
            modelo_partida: $this->modelo_partida,modelo_predial:  $this->modelo_predial,
            modelo_relacion: $this->modelo_relacion,modelo_relacionada:  $this->modelo_relacionada,
            modelo_retencion:  $this->modelo_retencion,modelo_sello:  $this->modelo_sello,
            modelo_traslado:  $this->modelo_traslado, modelo_uuid_ext: $this->modelo_uuid_ext,
            row_entidad_id: $this->registro_id);

        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar pdf',data:  $pdf, header: $header,ws:$ws);
        }


        if($header){
            $fichero = $pdf->registro_obj->doc_documento_ruta_absoluta;
            header('Content-Type: application/pdf');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            ob_clean();
            flush();

            readfile($fichero);
            exit;

        }
        return $pdf;

    }


    public function genera_xml(bool $header, bool $ws = false){

        $tipo = (new pac())->tipo;


        $factura = $this->modelo_entidad->genera_xml(modelo_documento: $this->modelo_documento,
            modelo_etapa: $this->modelo_etapa, modelo_partida: $this->modelo_partida, modelo_predial: $this->modelo_predial,
            modelo_relacion: $this->modelo_relacion, modelo_relacionada: $this->modelo_relacionada,
            modelo_retencion: $this->modelo_retencion, modelo_traslado: $this->modelo_traslado, modelo_uuid_ext: $this->modelo_uuid_ext,
            registro_id: $this->registro_id, tipo: $tipo);
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
     * @param _ctl_partida $ctl_partida
     * @param stdClass $paths_conf Rutas de archivos de configuracion
     * @return controler
     * @version 4.25.0
     */
    private function init_controladores(_ctl_partida $ctl_partida, stdClass $paths_conf): controler
    {
        $this->ctl_partida= $ctl_partida;
        $this->controlador_com_producto = new controlador_com_producto(link:$this->link, paths_conf: $paths_conf);

        return $this;
    }

    public function init_links(string $name_modelo_email): array|string
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

        $link_fc_email_alta_bd = $this->obj_link->link_alta_bd(link: $this->link, seccion: $name_modelo_email);
        if(errores::$error){
            $error = $this->errores->error(mensaje: 'Error al obtener link',data:  $this->link_fc_email_alta_bd);
            print_r($error);
            exit;
        }
        $this->link_fc_email_alta_bd  = $link_fc_email_alta_bd;

        return $link;
    }


    private function init_modifica(bool $fecha_original, _transacciones_fc $modelo_entidad,
                                   array $params = array()): array|stdClass
    {

        $r_modifica =  parent::modifica(header: false);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al generar template',data:  $r_modifica);
        }
        if(!$fecha_original) {
            $es_fecha_hora_min_sec_esp = $this->validacion->valida_pattern(key: 'fecha_hora_min_sec_esp',
                txt: $this->row_upd->fecha);
            if (errores::$error) {
                return $this->errores->error(mensaje: 'Error al validar fecha', data: $es_fecha_hora_min_sec_esp);
            }
            if ($es_fecha_hora_min_sec_esp) {
                $hora_ex = explode(' ', $this->row_upd->fecha);
                $this->row_upd->fecha = $hora_ex[0];
            }
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

        $sub_total = $modelo_entidad->get_factura_sub_total(registro_id: $this->registro_id);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener sub_total',data:  $sub_total);
        }

        $descuento = $modelo_entidad->get_factura_descuento(registro_id: $this->registro_id);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener descuento',data:  $descuento);
        }

        $imp_trasladados = $modelo_entidad->get_factura_imp_trasladados( registro_id: $this->registro_id);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener imp_trasladados',data:  $imp_trasladados);
        }


        $imp_retenidos = $modelo_entidad->get_factura_imp_retenidos( registro_id: $this->registro_id);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener imp_retenidos',data:  $imp_retenidos);
        }

        $total = $modelo_entidad->get_factura_total(fc_factura_id: $this->registro_id);
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

    /**
     * Inicializa los elementos para views de modificacion
     * @param _transacciones_fc $modelo_entidad Modelo base factura complemento etc
     * @param _partida $modelo_partida Modelo partida fc_partida
     * @param _data_impuestos $modelo_retencion modelos de tipo impuestos
     * @param _data_impuestos $modelo_traslado modelos de tipo impuestos
     * @param int $registro_entidad_id Identificador base
     * @return array|stdClass
     */
    private function init_upd(_transacciones_fc $modelo_entidad, _partida $modelo_partida,
                              _data_impuestos $modelo_retencion, _data_impuestos $modelo_traslado,
                              int $registro_entidad_id): array|stdClass
    {
        $partidas  = $modelo_partida->partidas(html: $this->html, modelo_entidad: $modelo_entidad,
            modelo_retencion: $modelo_retencion, modelo_traslado: $modelo_traslado,
            registro_entidad_id: $registro_entidad_id);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al obtener partidas', data: $partidas);
        }

        $row_upd = $this->modelo->registro(registro_id: $registro_entidad_id, retorno_obj: true);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al obtener factura', data: $row_upd);
        }

        $data = new stdClass();
        $data->partidas = $partidas;
        $data->row_upd = $row_upd;
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



    public function inserta_notificacion(bool $header, bool $ws = false){

        $this->link->beginTransaction();
        $notificaciones = (new fc_factura(link: $this->link))->inserta_notificacion(modelo_doc: $this->modelo_documento,
            modelo_email: $this->modelo_email, modelo_notificacion: $this->modelo_notificacion, registro_id: $this->registro_id);
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

    private function inserta_relacionada(array $fc_facturas_montos, string $key_modelo_base_id,
                                         string $key_modelo_rel_id, modelo $modelo_relacionada,
                                         int $registro_entidad_id, int $relacion_id): array|stdClass
    {

        $valida = $this->valida_data_relacion(key_modelo_base_id: $key_modelo_base_id,
            key_modelo_rel_id:  $key_modelo_rel_id,registro_entidad_id:  $registro_entidad_id,
            relacion_id:  $relacion_id);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al validar datos de relacion',data:  $valida);
        }

        $fc_factura_relacionada_ins = $this->row_relacionada(fc_facturas_montos: $fc_facturas_montos,
            key_modelo_base_id: $key_modelo_base_id, key_modelo_rel_id: $key_modelo_rel_id,
            registro_entidad_id: $registro_entidad_id, relacion_id: $relacion_id);

        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al obtener registro de relacion', data: $fc_factura_relacionada_ins);
        }


        $r_fc_factura_relacionada = $modelo_relacionada->alta_registro(registro: $fc_factura_relacionada_ins);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al dar de alta registro', data: $r_fc_factura_relacionada);
        }
        return $r_fc_factura_relacionada;
    }


    public function modifica(bool $header, bool $ws = false): array|stdClass
    {

        $controladores = $this->init_controladores(ctl_partida: $this->ctl_partida, paths_conf: $this->paths_conf);
        if(errores::$error){
            $error = $this->errores->error(mensaje: 'Error al inicializar controladores',data:  $controladores);
            print_r($error);
            die('Error');
        }

        $partidas = (new _partidas_html())->genera_partidas_html(html: $this->html, link: $this->link,
            modelo_entidad: $this->modelo_entidad, modelo_partida: $this->modelo_partida,
            modelo_retencion: $this->modelo_retencion, modelo_traslado: $this->modelo_traslado,
            registro_entidad_id: $this->registro_id);



        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al generar html', data: $partidas);
            print_r($error);
            die('Error');
        }

        $this->partidas = $partidas;


        $base = $this->init_modifica(fecha_original: false,modelo_entidad: $this->modelo_entidad);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al maquetar datos',data:  $base,
                header: $header,ws:$ws);
        }


        $identificador = "com_producto_id";
        $propiedades = array("cols" => 12);
        $this->ctl_partida->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "cat_sat_conf_imps_id";
        $propiedades = array("cols" => 12);
        $this->ctl_partida->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "cat_sat_obj_imp_id";
        $propiedades = array("cols" => 12);
        $this->ctl_partida->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);


        $com_cliente_id = $this->registro['com_cliente_id'];

        $inputs = $this->nueva_partida_inicializa(com_cliente_id: $com_cliente_id);
        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al inicializar partida', data: $inputs);
            print_r($error);
            die('Error');
        }

        $this->inputs->partidas = $inputs;


        $cat_sat_conf_imps_id = (new fc_factura_html(html: $this->html_base))->select_cat_sat_imp_id(
            configuraciones_impuestos: $this->configuraciones_impuestos,modelo_entidad:  $this->modelo_entidad,
            registro_entidad_id:  $this->registro_id);
        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al inicializar input', data: $cat_sat_conf_imps_id);
            print_r($error);
            die('Error');
        }

        $this->inputs->partidas->cat_sat_conf_imps_id = $cat_sat_conf_imps_id;

        $cat_sat_obj_imp_id = (new cat_sat_obj_imp_html(html: $this->html_base))->select_cat_sat_obj_imp_id(cols:12,
            con_registros: true, id_selected: -1,label: 'Objeto Impuesto', link: $this->link);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al inicializar input', data: $cat_sat_obj_imp_id);
        }

        $this->inputs->partidas->cat_sat_obj_imp_id = $cat_sat_obj_imp_id;

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

        $filtro = array();
        $filtro[$this->modelo_entidad->key_filtro_id] = $this->registro_id;
        $r_fc_email = $this->modelo_email->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al obtener emails', data: $r_fc_email);
        }

        $this->registros['fc_emails'] = $r_fc_email->registros;





        $form_data_fc = $this->inputs->fc_csd_id;
        $form_data_fc.= $this->inputs->com_sucursal_id;
        $form_data_fc.= $this->inputs->serie;
        $form_data_fc.= $this->inputs->folio;
        $form_data_fc.= $this->inputs->exportacion;
        $form_data_fc.= $this->inputs->fecha;
        $form_data_fc.= $this->inputs->impuestos_trasladados;
        $form_data_fc.= $this->inputs->impuestos_retenidos;
        $form_data_fc.= $this->inputs->subtotal;
        $form_data_fc.= $this->inputs->descuento;
        $form_data_fc.= $this->inputs->total;
        $form_data_fc.= $this->inputs->cat_sat_tipo_de_comprobante_id;
        $form_data_fc.= $this->inputs->cat_sat_forma_pago_id;
        $form_data_fc.= $this->inputs->cat_sat_metodo_pago_id;
        $form_data_fc.= $this->inputs->cat_sat_moneda_id;
        $form_data_fc.= $this->inputs->com_tipo_cambio_id;
        $form_data_fc.= $this->inputs->cat_sat_uso_cfdi_id;
        $form_data_fc.= $this->inputs->observaciones;

        $this->form_data_fc = $form_data_fc;

        $input_registro_id = (new html_controler(html: $this->html_base))->hidden(name: 'registro_id',value:  $this->registro_id);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al obtener input_registro_id', data: $input_registro_id);
        }

        $this->inputs->registro_id = $input_registro_id;



        return $base->template;
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

        $modelo_entidad = (new fc_factura(link: $this->link));
        $modelo_traslado = (new fc_traslado(link: $this->link));
        $modelo_retencion = (new fc_retenido(link: $this->link));
        $partidas = (new fc_partida($this->link))->partidas(html: $this->html, modelo_entidad: $modelo_entidad,
            modelo_retencion: $modelo_retencion, modelo_traslado: $modelo_traslado, registro_entidad_id: $this->registro_id);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener sucursales',data:  $partidas, header: $header,ws:$ws);
        }

        $this->partidas = $partidas;
        $this->number_active = 6;

        return $inputs;

    }

    public function modifica_partida_bd(bool $header, bool $ws = false): array|stdClass
    {
        $this->link->beginTransaction();
        $siguiente_view = (new actions())->siguiente_view();
        if(errores::$error){
            $this->link->rollBack();
            return $this->retorno_error(mensaje: 'Error al obtener siguiente view sucursal',data:  $siguiente_view,
                header: $header,ws:$ws);
        }
        if(isset($_POST['guarda'])){
            unset($_POST['guarda']);
        }
        if(isset($_POST['btn_action_next'])){
            unset($_POST['btn_action_next']);
        }

        $registro_partida_id = -1;
        if(!isset($_GET['registro_partida_id'])){
            if(isset($_POST['registro_partida_id'])){
                $registro_partida_id = $_POST['registro_partida_id'];
            }
            else{
                $this->link->rollBack();
                return $this->retorno_error(mensaje: 'Error al no existe registro_partida_id', data: $registro_partida_id,
                    header:  true, ws: $ws);
            }
        }
        else{
            $registro_partida_id = $_GET['registro_partida_id'];
        }


        $fc_partida = $this->modelo_partida->registro(registro_id: $registro_partida_id);
        if(errores::$error){
            $this->link->rollBack();
            return $this->retorno_error(mensaje: 'Error al obtener partida', data: $fc_partida,
                header:  true, ws: $ws);
        }

        $existe_cuenta_predial = $this->modelo_predial->existe(
            filtro: array($this->modelo_partida->key_filtro_id=>$registro_partida_id));
        if(errores::$error){
            $this->link->rollBack();
            return $this->retorno_error(mensaje: 'Error al validar si existe predial', data: $existe_cuenta_predial,
                header:  true, ws: $ws);
        }
        $cuenta_predial = '';
        if($existe_cuenta_predial){
            $r_fc_cuenta_predial = $this->modelo_predial->filtro_and(columnas_en_bruto: true,
                filtro: array($this->modelo_partida->key_filtro_id=>$registro_partida_id));
            if(errores::$error){
                $this->link->rollBack();
                return $this->retorno_error(mensaje: 'Error al obtener predial', data: $r_fc_cuenta_predial,
                    header:  true, ws: $ws);
            }
            if($r_fc_cuenta_predial->n_registros > 1){
                $this->link->rollBack();
                return $this->retorno_error(mensaje: 'Error existe mas de un predial', data: $r_fc_cuenta_predial,
                    header:  true, ws: $ws);
            }
            if($r_fc_cuenta_predial->n_registros === 0){
                $this->link->rollBack();
                return $this->retorno_error(mensaje: 'Error no existe  predial', data: $r_fc_cuenta_predial,
                    header:  true, ws: $ws);
            }
            $cuenta_predial = $r_fc_cuenta_predial->registros[0]['descripcion'];
        }

        if($this->registro_id === -1){
            $this->registro_id = $fc_partida[$this->tabla.'_id'];
        }


        $upd = $this->modelo_partida->modifica_bd(registro: $_POST,id:  $registro_partida_id);
        if(errores::$error){
            $this->link->rollBack();
            return $this->retorno_error(mensaje: 'Error al modificar partida', data: $upd,
                header:  true, ws: $ws);
        }


        $this->link->commit();
        if($header){
            $params = array('fc_partida_id'=>$registro_partida_id);
            $retorno = (new actions())->retorno_alta_bd(link: $this->link, registro_id: $this->registro_id,
                seccion: $this->tabla, siguiente_view: $siguiente_view, params: $params);
            if(errores::$error){
                return $this->retorno_error(mensaje: 'Error al dar de alta registro', data: $upd,
                    header:  true, ws: $ws);
            }
            header('Location:'.$retorno);
            exit;
        }
        if($ws){
            ob_clean();
            header('Content-Type: application/json');
            try {
                echo json_encode($upd, JSON_THROW_ON_ERROR);
            }
            catch (Throwable $e){
                print_r($e);
            }

            exit;
        }
        return $upd;

    }


    public function nueva_partida(bool $header, bool $ws = false): array|stdClass
    {

        $row = $this->modelo->registro(registro_id: $this->registro_id);
        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al obtener partida', data: $row);
            print_r($error);
            die('Error');
        }

        $this->inputs = $this->nueva_partida_inicializa(com_cliente_id: $row['com_cliente_id']);
        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al inicializar partida', data: $this->inputs);
            print_r($error);
            die('Error');
        }

        return $this->inputs;
    }

    private function nueva_partida_inicializa(int $com_cliente_id): array|stdClass{

        $r_template = $this->ctl_partida->alta(header: false);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al obtener template', data: $r_template);
        }

        $keys_selects = $this->ctl_partida->init_selects_inputs();
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al inicializar selects', data: $keys_selects);
        }

        $keys_selects['fc_factura_id']->id_selected = $this->registro_id;
        $keys_selects['fc_factura_id']->filtro = array("fc_factura.id" => $this->registro_id);
        $keys_selects['fc_factura_id']->disabled = true;
        $keys_selects['fc_factura_id']->cols = 12;
        $keys_selects['com_producto_id']->cols = 12;
        $keys_selects['cat_sat_conf_imps_id']->cols = 12;
        $keys_selects['cat_sat_obj_imp_id']->cols = 12;

        $com_productos = (new com_producto(link: $this->link))->registros_activos();
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al obtener productos', data: $com_productos);
        }

        foreach ($com_productos as $indice=>$com_producto){
            $precio = (new com_producto(link: $this->link))->precio(com_cliente_id: $com_cliente_id,
                com_producto_id:  $com_producto['com_producto_id']);
            if (errores::$error) {
                return $this->errores->error(mensaje: 'Error al obtener precio', data: $precio);
            }
            $com_productos[$indice]['com_producto_precio'] = $precio;

            $cat_sat_conf_imps_id = $com_producto['cat_sat_conf_imps_id'];
            $precio_cliente_row = (new com_producto(link: $this->link))->precio_cliente_row(
                com_cliente_id: $com_cliente_id,com_producto_id:  $com_producto['com_producto_id']);
            if(count($precio_cliente_row)>0){
                $cat_sat_conf_imps_id = $precio_cliente_row['cat_sat_conf_imps_id'];
            }
            $com_productos[$indice]['cat_sat_conf_imps_id']  = $cat_sat_conf_imps_id;

        }

        $keys_selects['com_producto_id']->registros = $com_productos;

        $inputs = $this->ctl_partida->inputs(keys_selects: $keys_selects);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al obtener inputs', data: $inputs);
        }





        return $inputs;
    }

    private function link_elimina_rel(string $name_modelo_relacionada, int $registro_entidad_id, int $registro_relacionada_id): array|string
    {
        $params = $this->params_button_partida(accion_retorno: 'relaciones',
            name_modelo_entidad: $this->tabla, registro_entidad_id: $registro_entidad_id);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al generar params', data: $params);
        }

        $link_elimina_rel = $this->html->button_href(accion: 'elimina_bd', etiqueta: 'Eliminar',
            registro_id: $registro_relacionada_id, seccion: $name_modelo_relacionada, style: 'danger',icon: 'bi bi-trash',
            muestra_icono_btn: true, muestra_titulo_btn: false, params: $params);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al generar link elimina_bd para partida', data: $link_elimina_rel);
        }
        return $link_elimina_rel;
    }

    private function links_elimina(int $indice, string $key_relacionada_id, array $relacion, array $relaciones): array
    {
        foreach ($relacion['fc_facturas_relacionadas'] as $indice_fr=>$fc_factura_relacionada){

            $link_elimina_rel = $this->link_elimina_rel(name_modelo_relacionada: $this->modelo_relacionada->tabla,
                registro_entidad_id:  $this->registro_id, registro_relacionada_id: $fc_factura_relacionada[$key_relacionada_id]);

            if (errores::$error) {
                return $this->errores->error(mensaje: 'Error al generar link elimina_bd para partida', data: $link_elimina_rel);
            }

            $relaciones[$indice]['fc_facturas_relacionadas'][$indice_fr]['elimina_bd'] = $link_elimina_rel;

        }
        return $relaciones;
    }



    private function params_base(array $params, stdClass $row_upd): array
    {
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

        return $params;
    }

    /**
     * Genera los parametros para integrar en un boton de tipo link
     * @param string $accion_retorno Accion de regreso
     * @param string $name_modelo_entidad Nombre del modelo base facturacion, nota credito etc
     * @param int $registro_entidad_id Identificador base de la entidad base
     * @return array
     */
    private function params_button_partida(string $accion_retorno, string $name_modelo_entidad, int $registro_entidad_id): array
    {
        $params = array();
        $params['seccion_retorno'] = $name_modelo_entidad;
        $params['accion_retorno'] = $accion_retorno;
        $params['id_retorno'] = $registro_entidad_id;
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


        $modelo_entidad = (new fc_factura(link: $this->link));
        $modelo_traslado = (new fc_traslado(link: $this->link));
        $modelo_retencion = (new fc_retenido(link: $this->link));

        $partidas = (new fc_partida($this->link))->partidas(html: $this->html, modelo_entidad: $modelo_entidad,
            modelo_retencion: $modelo_retencion, modelo_traslado: $modelo_traslado, registro_entidad_id: $this->registro_id);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener sucursales',data:  $partidas, header: $header,ws:$ws);
        }


        $this->partidas = $partidas;
        $this->number_active = 6;

        return $inputs;

    }

    public function relaciones(bool $header, bool $ws = false){

        $this->key_uuid = $this->modelo_entidad->tabla.'_uuid';
        $this->key_folio = $this->modelo_entidad->tabla.'_folio';
        $this->key_fecha = $this->modelo_entidad->tabla.'_fecha';
        $this->key_total = $this->modelo_entidad->tabla.'_total';
        $this->key_saldo = $this->modelo_entidad->tabla.'_saldo';
        $this->key_etapa = $this->modelo_etapa->tabla;
        $this->key_relacion_id = $this->modelo_relacion->key_id;
        $this->key_entidad_id = $this->modelo_entidad->key_id;


        $datos = $this->init_upd(modelo_entidad: $this->modelo_entidad,modelo_partida:  $this->modelo_partida,
            modelo_retencion:  $this->modelo_retencion,modelo_traslado:  $this->modelo_traslado,
            registro_entidad_id:  $this->registro_id);

        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al obtener datos', data: $datos);
            print_r($error);
            die('Error');
        }


        $this->partidas = $datos->partidas;


        $params = array();

        $params['fc_csd_id']['filtro']['fc_csd.id'] = $datos->row_upd->fc_csd_id;
        $params['fc_csd_id']['disabled'] = true;

        $base = $this->init_modifica(fecha_original: false, modelo_entidad: $this->modelo_entidad, params: $params);
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


        $link = $this->obj_link->link_con_id(accion: 'fc_relacion_alta_bd',link:  $this->link,
            registro_id: $this->registro_id, seccion: $this->tabla);
        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al obtener link', data: $link);
            print_r($error);
            die('Error');
        }

        $this->link_fc_relacion_alta_bd = $link;

        $link = $this->obj_link->link_con_id(accion: 'fc_factura_relacionada_alta_bd',link:  $this->link,
            registro_id: $this->registro_id, seccion: $this->tabla);
        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al obtener link', data: $link);
            print_r($error);
            die('Error');
        }

        $this->link_fc_factura_relacionada_alta_bd = $link;

        $relaciones = (new _relaciones_base())->genera_relaciones(com_cliente_id: $datos->row_upd->com_cliente_id,
            controller: $this, modelo_uuid_ext: $this->modelo_uuid_ext, name_entidad: $this->tabla,
            org_empresa_id: $datos->row_upd->org_empresa_id);
        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al obtener relaciones', data: $relaciones);
            print_r($error);
            die('Error');
        }



        $key_relacionada_id = $this->modelo_relacionada->key_id;
        $key_relacion_id = $this->modelo_relacion->key_id;
        foreach ($relaciones as $indice=>$relacion){

            $relaciones = $this->links_elimina(indice: $indice,key_relacionada_id:  $key_relacionada_id,relacion:  $relacion,relaciones:  $relaciones);
            if (errores::$error) {
                return $this->errores->error(mensaje: 'Error al generar links elimina_bd para partida', data: $relaciones);
            }

            $link_elimina_rel = $this->link_elimina_rel(name_modelo_relacionada: $this->modelo_relacion->tabla,
                registro_entidad_id:  $this->registro_id, registro_relacionada_id: $relacion[$key_relacion_id]);

            if (errores::$error) {
                return $this->errores->error(mensaje: 'Error al generar link elimina_bd para partida', data: $link_elimina_rel);
            }

            $relaciones[$indice]['elimina_bd'] = $link_elimina_rel;

        }



        $fc_externas = $this->fc_externas(com_cliente_id: $datos->row_upd->com_cliente_id);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al obtener relaciones externas', data: $fc_externas);
        }


        foreach ($relaciones as $indice=>$relacion){
            $relaciones[$indice]['fc_externas'] = array();

            foreach ($fc_externas as $fc_uuid){

                $existe  = $this->existe_uuid_externo(fc_uuid: $fc_uuid, name_entidad_relacion: $this->modelo_relacion->tabla,
                    modelo_uuid_ext: $this->modelo_uuid_ext, row_relacion_ext: $relacion);

                if (errores::$error) {
                    return $this->errores->error(mensaje: 'Error al validar si existe', data: $existe);
                }


                if(!$existe) {
                    /**
                     * POR REVISAR EXTRA PARAMS CLASESS CSS
                     */
                    $checkbox = (new _relaciones_base())->input_chk_rel(clases_css: array(),
                        entidad_origen_key: 'fc_uuid', extra_params: array(), relacion_id: $relacion[$key_relacion_id],
                        row_entidad_id: $fc_uuid['fc_uuid_id']);
                    if (errores::$error) {
                        return $this->errores->error(mensaje: 'Error al generar checkbox', data: $checkbox);
                    }
                }
                else{

                    $filtro['fc_uuid.id'] = $fc_uuid['fc_uuid_id'];
                    $key_filtro_id = $this->modelo_relacion->key_filtro_id;
                    $filtro[$key_filtro_id] = $relacion[$key_relacion_id];

                    $r_fc_uuid_fc = $this->modelo_uuid_ext->filtro_and(filtro: $filtro);
                    if (errores::$error) {
                        return $this->errores->error(mensaje: 'Error al validar si existe', data: $existe);
                    }
                    if($r_fc_uuid_fc->n_registros === 0){
                        return $this->errores->error(mensaje: 'Error no existe relacion', data: $r_fc_uuid_fc);
                    }
                    if($r_fc_uuid_fc->n_registros > 1){
                        return $this->errores->error(mensaje: 'Error  existe mas de una relacion', data: $r_fc_uuid_fc);
                    }
                    $fc_uuid_fc = $r_fc_uuid_fc->registros[0];

                    $link_elimina_rel = $this->link_elimina_rel(name_modelo_relacionada: $this->modelo_uuid_ext->tabla,
                        registro_entidad_id:  $this->registro_id, registro_relacionada_id: $fc_uuid_fc[$this->modelo_uuid_ext->key_id]);

                    if (errores::$error) {
                        return $this->errores->error(mensaje: 'Error al generar link elimina_bd para partida', data: $link_elimina_rel);
                    }

                    $checkbox = $link_elimina_rel;
                }
                $fc_uuid['seleccion'] = $checkbox;

                $relaciones[$indice]['fc_externas'][] = $fc_uuid;

            }

        }


        if($this->tabla === 'fc_nota_credito'){


            foreach ($relaciones as $indice=>$relacion){
                $relaciones[$indice]['fc_facturas_relacionadas_factura'] = array();
                $filtro = array();
                $filtro['fc_relacion_nc.id'] = $relacion['fc_relacion_nc_id'];
                $r_fc_nc_rel = (new fc_nc_rel(link: $this->link))->filtro_and(filtro: $filtro);
                if (errores::$error) {
                    return $this->errores->error(mensaje: 'Error al obtener relacion', data: $r_fc_nc_rel);
                }
                $fc_nc_rels = $r_fc_nc_rel->registros;

                foreach ($fc_nc_rels as $indice_fr=>$fc_factura_relacionada){

                    $link_elimina_rel = $this->link_elimina_rel(name_modelo_relacionada: 'fc_nc_rel',
                        registro_entidad_id:  $this->registro_id, registro_relacionada_id: $fc_factura_relacionada['fc_nc_rel_id']);

                    if (errores::$error) {
                        return $this->errores->error(mensaje: 'Error al generar link elimina_bd para partida', data: $link_elimina_rel);
                    }

                   $relaciones[$indice]['fc_facturas_relacionadas_factura'][$indice_fr] = $fc_factura_relacionada;
                   $relaciones[$indice]['fc_facturas_relacionadas_factura'][$indice_fr]['elimina_bd'] = $link_elimina_rel;

                }

            }

        }

        if($this->tabla === 'fc_complemento_pago'){


            foreach ($relaciones as $indice=>$relacion){

                $filtro['com_cliente.id'] = $datos->row_upd->com_cliente_id;
                $r_fc_complemento_pago = (new fc_complemento_pago(link: $this->link))->filtro_and(filtro: $filtro);
                if (errores::$error) {
                    return $this->errores->error(mensaje: 'Error al obtener complementos', data: $r_fc_complemento_pago);
                }
                $fc_complementos_pago = $r_fc_complemento_pago->registros;

                $fc_complementos_pago_env = array();
                foreach ($fc_complementos_pago as $fc_complemento_pago){
                    $checkbox = (new _relaciones_base())->input_chk_rel(clases_css: array(),
                        entidad_origen_key: 'fc_complemento_pago', extra_params: array(), relacion_id: $relacion[$key_relacion_id],
                        row_entidad_id: $fc_complemento_pago['fc_complemento_pago_id']);
                    if (errores::$error) {
                        return $this->errores->error(mensaje: 'Error al generar checkbox', data: $checkbox);
                    }

                    if((int)$fc_complemento_pago['fc_complemento_pago_id'] === $this->registro_id){
                        continue;
                    }
                    $fc_complemento_pago['seleccion'] = $checkbox;
                    $fc_complementos_pago_env[] = $fc_complemento_pago;
                }
                $relaciones[$indice]['fc_complementos_pago'] = $fc_complementos_pago_env;
            }

            foreach ($relaciones as $indice=>$relacion){
                foreach ($relacion['fc_complementos_pago'] as $indice_row_sin_rel=>$row_sin_rel){
                    foreach ($relacion['fc_facturas_relacionadas'] as $row_rel){
                        if($row_rel['fc_complemento_pago_id'] === $row_sin_rel['fc_complemento_pago_id']){
                            unset($relaciones[$indice]['fc_complementos_pago'][$indice_row_sin_rel]);
                            break;
                        }
                    }
                }
            }




        }


        $this->relaciones = $relaciones;

        //print_r($relaciones);exit;


        $button_fc_factura_modifica =  $this->html->button_href(accion: 'modifica', etiqueta: 'Ir a CFDI',
            registro_id: $this->registro_id,
            seccion: $this->tabla, style: 'warning', params: array());
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al generar link', data: $button_fc_factura_modifica);
        }

        $this->button_fc_factura_modifica = $button_fc_factura_modifica;


        $inputs_relaciones = '';
        $inputs_relaciones.= $this->inputs->fc_csd_id;
        $inputs_relaciones.= $this->inputs->com_sucursal_id;
        $inputs_relaciones.= $this->inputs->serie;
        $inputs_relaciones.= $this->inputs->folio;
        $inputs_relaciones.= $this->inputs->impuestos_trasladados;
        $inputs_relaciones.= $this->inputs->impuestos_retenidos;
        $inputs_relaciones.= $this->inputs->subtotal;
        $inputs_relaciones.= $this->inputs->descuento;
        $inputs_relaciones.= $this->inputs->total;
        $inputs_relaciones.= $this->inputs->cat_sat_tipo_relacion_id;

        $this->inputs_relaciones = $inputs_relaciones;




        return $base->template;
    }

    /**
     * Integra un elemento para insercion de una relacion
     * @param array $fc_facturas_montos
     * @param string $key_modelo_base_id Key del modelo base
     * @param string $key_modelo_rel_id Ker del modelo a relacionar
     * @param int $registro_entidad_id Registro id de la entidad base
     * @param int $relacion_id Relacion base
     * @return array
     */
    private function row_relacionada(array $fc_facturas_montos, string $key_modelo_base_id, string $key_modelo_rel_id,
                                     int $registro_entidad_id, int $relacion_id): array
    {

        if(isset($fc_facturas_montos[$registro_entidad_id]['fc_relacion_id'][$relacion_id])){
            $fc_factura_relacionada_ins['monto_aplicado_factura']
                = round($fc_facturas_montos[$registro_entidad_id]['fc_relacion_id'][$relacion_id],2);
        }


        $valida = $this->valida_data_relacion(key_modelo_base_id: $key_modelo_base_id,
            key_modelo_rel_id:  $key_modelo_rel_id,registro_entidad_id:  $registro_entidad_id,
            relacion_id:  $relacion_id);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al validar datos de relacion',data:  $valida);
        }

        $fc_factura_relacionada_ins[$key_modelo_rel_id] = $relacion_id;
        $fc_factura_relacionada_ins[$key_modelo_base_id] = $registro_entidad_id;
        return $fc_factura_relacionada_ins;
    }

    final public function row_upd(string $key, bool $verifica_permite_transaccion = true): array|stdClass
    {
        if($this->registro_id<=0){
            return $this->errores->error(mensaje: 'Error this->registro_id debe ser mayor a 0',
                data:  $this->registro_id);
        }
        $key = trim($key);
        if($key === ''){
            return $this->errores->error(mensaje: 'Error key esta vacio', data:  $key);
        }


        $row_upd = (new row())->integra_row_upd(key: $key, modelo: $this->modelo, registro_id: $this->registro_id);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener row upd',data:  $row_upd);
        }

        $upd = $this->modelo_entidad->modifica_bd(registro: $row_upd, id: $this->registro_id,
            verifica_permite_transaccion: $verifica_permite_transaccion);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al modificar adm_accion',data:  $upd);
        }
        return $upd;
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

    final protected function thead_relacion(): string
    {
        $th_aplica_monto = '';
        if($this->aplica_monto_relacion){
            $th_aplica_monto = '<th>Monto</th>';
        }
        $html = '<thead>
                                        <tr>
                                            <th>UUID</th>
                                            <th>Cliente</th>
                                            <th>Folio</th>
                                            <th>Fecha</th>
                                            <th>Total</th>
                                            <th>Saldo</th>
                                            <th>Estatus</th>
                                            <th>Tipo de CFDI</th>
                                            '.$th_aplica_monto.'
                                            <th>Selecciona</th>
                                        </tr>
                                        </thead>';

        $this->thead_relacion = $html;
        return $html;
    }

    public function timbra_xml(bool $header, bool $ws = false): array|stdClass{

        $timbre = $this->modelo_entidad->timbra_xml(modelo_documento: $this->modelo_documento,
            modelo_etapa: $this->modelo_etapa, modelo_partida: $this->modelo_partida,
            modelo_predial: $this->modelo_predial, modelo_relacion: $this->modelo_relacion,
            modelo_relacionada: $this->modelo_relacionada, modelo_retencion: $this->modelo_retencion,
            modelo_sello: $this->modelo_sello, modelo_traslado: $this->modelo_traslado,
            modelo_uuid_ext: $this->modelo_uuid_ext, registro_id: $this->registro_id);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al timbrar XML', data: $timbre, header: $header, ws: $ws,
                class: __CLASS__, file: __FILE__, function: __FUNCTION__, line: __LINE__);
        }

        $this->link->beginTransaction();
        $siguiente_view = (new actions())->init_alta_bd();
        if(errores::$error){
            $this->link->rollBack();
            return $this->retorno_error(mensaje: 'Error al obtener siguiente view', data: $siguiente_view,
                header:  $header, ws: $ws,class: __CLASS__, file: __FILE__, function: __FUNCTION__, line: __LINE__);
        }

        if($header){

            $retorno = (new actions())->retorno_alta_bd(link: $this->link, registro_id: $this->registro_id,
                seccion: $this->tabla, siguiente_view: "modifica");
            if(errores::$error){
                return $this->retorno_error(mensaje: 'Error cambiar de view', data: $retorno,
                    header:  true, ws: $ws,class: __CLASS__, file: __FILE__, function: __FUNCTION__, line: __LINE__);
            }
            header('Location:'.$retorno);
            exit;
        }
        if($ws){
            try {
                ob_clean();
                header('Content-Type: application/json');
                echo json_encode($timbre, JSON_THROW_ON_ERROR);
            }
            catch (Throwable $e){
                print_r($e);
            }
            exit;
        }

        return $timbre;
    }

    private function tipo_de_comprobante_predeterminado(): array|stdClass
    {
        $filtro['cat_sat_tipo_de_comprobante.descripcion'] = $this->cat_sat_tipo_de_comprobante;
        $tipo_comprobante = (new cat_sat_tipo_de_comprobante($this->link))->filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener tipo de comprobante',data:  $this->conf_generales);
        }

        if ($tipo_comprobante->n_registros === 0){
            $tipo_comprobante = (new cat_sat_tipo_de_comprobante($this->link))->get_tipo_comprobante_predeterminado();
            if(errores::$error){
                return $this->errores->error(mensaje: 'Error al obtener tipo de comprobante predeterminado',
                    data:  $tipo_comprobante);
            }
        }
        return $tipo_comprobante;
    }

    final public function tr_relacion(bool $aplica_monto, array $fc_factura, string $key_etapa, string $key_fecha,
                                      string $key_folio, string $key_saldo, string $key_total,
                                      string $key_uuid): string|array
    {

        $keys = array($key_uuid,'com_cliente_rfc', $key_folio, $key_fecha, $key_etapa,
            'cat_sat_tipo_de_comprobante_descripcion','seleccion', $key_total, $key_saldo);

        $valida = $this->validacion->valida_existencia_keys(keys: $keys,registro:  $fc_factura);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al validar',data:  $valida, header: true,ws: false);

        }

        $td_monto = '';
        if($aplica_monto){
            $td_monto = "<td class='td_monto'>$fc_factura[input_monto]</td>";
        }

        return "<tr>
                    <td>$fc_factura[$key_uuid]</td>
                    <td>$fc_factura[com_cliente_rfc]</td>
                    <td>$fc_factura[$key_folio]</td>
                    <td>$fc_factura[$key_fecha]</td>
                    <td>$fc_factura[$key_total]</td>
                    <td>$fc_factura[$key_saldo]</td>
                    <td>$fc_factura[$key_etapa]</td>
                    <td>$fc_factura[cat_sat_tipo_de_comprobante_descripcion]</td>
                    $td_monto
                    <td class='td_chk'>$fc_factura[seleccion]</td>
                    </tr>";
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


    /**
     * POR DOCUMENTAR EN WIKI
     * La función valida_data_relacion verifica la corrección de los parámetros ingresados,
     * que representan la relación entre dos modelos en una entidad.
     *
     * @param string $key_modelo_base_id Clave de identificación del modelo base.
     * @param string $key_modelo_rel_id Clave de identificación del modelo relacionado.
     * @param int $registro_entidad_id Identificación del registro de la entidad donde se establece la relación.
     * @param int $relacion_id Identificación de la relación.
     *
     * @return true|array Retorna verdadero si los parámetros son válidos.
     * Si se encuentra alguna inconsistencia con los datos de entrada,
     * retorna un array con la descripción del error.
     * @version 27.39.0
     */
    private function valida_data_relacion(string $key_modelo_base_id, string $key_modelo_rel_id,
                                          int $registro_entidad_id, int $relacion_id): true|array
    {
        $key_modelo_base_id = trim($key_modelo_base_id);
        if($key_modelo_base_id === ''){
            return $this->errores->error(mensaje: 'Error key_modelo_base_id esta vacio',data:  $key_modelo_base_id);
        }
        $key_modelo_rel_id = trim($key_modelo_rel_id);
        if($key_modelo_rel_id === ''){
            return $this->errores->error(mensaje: 'Error key_modelo_rel_id esta vacio',data:  $key_modelo_rel_id);
        }
        if($registro_entidad_id <=0){
            return $this->errores->error(mensaje: 'Error registro_entidad_id debe ser mayor a 0',
                data:  $registro_entidad_id);
        }
        if($relacion_id <=0){
            return $this->errores->error(mensaje: 'Error relacion_id debe ser mayor a 0',data:  $relacion_id);
        }
        return true;
    }

    public function verifica_cancelacion(bool $header, bool $ws = false){

        $this->link->beginTransaction();
        $fc_factura = $this->modelo->registro(registro_id: $this->registro_id);
        if(errores::$error){
            $this->link->rollBack();
            return $this->retorno_error(mensaje: 'Error al obtener factura',data:  $fc_factura,header:  $header, ws: $ws);
        }


        $key_factura_total = $this->tabla.'_total';
        $key_factura_uuid = $this->tabla.'_uuid';
        $key_factura_id_filter = $this->tabla.'.id';

        $verifica = (new timbra())->consulta_estado_sat($fc_factura['org_empresa_rfc'], $fc_factura['com_cliente_rfc'],
            $fc_factura[$key_factura_total], $fc_factura[$key_factura_uuid]);
        if(errores::$error){
            $this->link->rollBack();
            return $this->retorno_error(mensaje: 'Error al consulta estado',data:  $verifica,header:  $header, ws: $ws);
        }

        $this->modelo_etapa->verifica_permite_transaccion = false;

        $integra_etapa = (new _fc_base())->integra_etapa(key_factura_id_filter: $key_factura_id_filter,
            modelo: $this->modelo_entidad, modelo_etapa: $this->modelo_etapa, registro_id: $this->registro_id,
            verifica: $verifica);
        if(errores::$error){
            $this->link->rollBack();
            return $this->retorno_error(mensaje: 'Error al si aplica etapa',data:  $integra_etapa,header:  $header, ws: $ws);
        }

        $this->link->commit();

        $datos = $this->init_upd(modelo_entidad: $this->modelo_entidad,modelo_partida:  $this->modelo_partida,
            modelo_retencion:  $this->modelo_retencion,modelo_traslado:  $this->modelo_traslado,
            registro_entidad_id:  $this->registro_id);

        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al obtener datos', data: $datos);
            print_r($error);
            die('Error');
        }

        $this->partidas = $datos->partidas;

        $params = array();

        $base = $this->init_modifica(fecha_original: false, modelo_entidad: $this->modelo_entidad, params: $params);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al maquetar datos',data:  $base,
                header: $header,ws:$ws);
        }


        $this->mensaje = $verifica->mensaje;
        if(isset($verifica->result->EstatusCancelacion)){
            $this->mensaje .= ' Estado de Cancelacion '.$verifica->result->EstatusCancelacion;
        }

        return $verifica;



    }



}
