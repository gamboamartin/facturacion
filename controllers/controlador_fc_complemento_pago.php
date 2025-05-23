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
use Dompdf\Dompdf;
use Dompdf\Options;
use gamboamartin\comercial\models\com_tipo_cambio;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\html\fc_complemento_pago_html;
use gamboamartin\facturacion\models\_doctos_rel;
use gamboamartin\facturacion\models\_pdf;
use gamboamartin\facturacion\models\fc_cancelacion_cp;
use gamboamartin\facturacion\models\fc_cfdi_sellado_cp;
use gamboamartin\facturacion\models\fc_complemento_pago;
use gamboamartin\facturacion\models\fc_complemento_pago_documento;
use gamboamartin\facturacion\models\fc_complemento_pago_etapa;
use gamboamartin\facturacion\models\fc_complemento_pago_relacionada;
use gamboamartin\facturacion\models\fc_cuenta_predial_cp;
use gamboamartin\facturacion\models\fc_docto_relacionado;
use gamboamartin\facturacion\models\fc_email_cp;
use gamboamartin\facturacion\models\fc_factura;
use gamboamartin\facturacion\models\fc_notificacion_cp;
use gamboamartin\facturacion\models\fc_pago;
use gamboamartin\facturacion\models\fc_pago_pago;
use gamboamartin\facturacion\models\fc_pago_total;
use gamboamartin\facturacion\models\fc_partida_cp;
use gamboamartin\facturacion\models\fc_relacion_cp;
use gamboamartin\facturacion\models\fc_retenido_cp;
use gamboamartin\facturacion\models\fc_traslado_cp;
use gamboamartin\facturacion\models\fc_uuid_cp;
use gamboamartin\organigrama\models\org_logo;
use gamboamartin\system\actions;
use gamboamartin\template\html;

use html\cat_sat_forma_pago_html;
use NumberFormatter;
use PDO;
use stdClass;

class controlador_fc_complemento_pago extends _base_system_fc {

    public array|stdClass $keys_selects = array();
    public controlador_fc_partida_cp $controlador_fc_partida_cp;
    public controlador_com_producto $controlador_com_producto;

    public string $button_fc_complemento_pago_modifica = '';
    public string $button_fc_complemento_pago_relaciones = '';
    public string $button_fc_complemento_pago_timbra = '';

    public string $rfc = '';
    public string $razon_social = '';
    public string $link_fc_partida_cp_alta_bd = '';
    public string $link_fc_partida_cp_modifica_bd = '';
    public string $link_fc_factura_cp_partidas = '';
    public string $link_fc_complemento_pago_nueva_partida = '';

    public string $link_fc_email_cp_alta_bd = '';
    public string $link_fc_relacion_cp_alta_bd = '';
    public string $link_com_producto = '';

    public string $link_complemento_pago_cancela = '';
    public string $link_complemento_pago_xml = '';
    public string $link_complemento_pago_timbra_xml = '';
    public string $button_fc_complemento_pago_correo = '';
    public string $link_fc_complemento_pago_relacionada_alta_bd = '';
    public string $link_fc_docto_relacionado_alta_bd = '';
    public int $fc_complemento_pago_id = -1;
    public int $fc_partida_cp_id = -1;
    public stdClass $partidas;

    public array $fc_pagos = array();
    public array $fc_facturas = array();

    public string $link_fc_pago_pago_alta_bd = '';


    public array $relaciones = array();

    public float $saldo_total = 0.0;
    public bool $tiene_pago = false;
    public array$complementos_pago_cliente = array();
    public float$total_pagos = 0.0;
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

        $this->data_selected_alta['cat_sat_metodo_pago_id']['id'] = 2;
        $this->data_selected_alta['cat_sat_metodo_pago_id']['filtro'] = array('cat_sat_metodo_pago.id'=>2);

        $this->data_selected_alta['cat_sat_moneda_id']['id'] = 163;
        $this->data_selected_alta['cat_sat_moneda_id']['filtro'] = array('cat_sat_moneda.id'=>163);

        $this->data_selected_alta['com_tipo_cambio_id']['id'] = -1;
        $this->data_selected_alta['com_tipo_cambio_id']['filtro'] = array();

        $this->data_selected_alta['cat_sat_uso_cfdi_id']['id'] = 23;
        $this->data_selected_alta['cat_sat_uso_cfdi_id']['filtro'] = array('cat_sat_uso_cfdi.id'=>23);

        $com_tipo_cambio_id = -1;
        $filtro['com_tipo_cambio.fecha'] = date('Y-m-d');


        $r_com_tipo_cambio = (new com_tipo_cambio(link: $this->link))->filtro_and(filtro: $filtro);
        if(errores::$error){
            $error = $this->errores->error(mensaje: 'Error al OBTENER TIPO DE CAMBIO',data:  $r_com_tipo_cambio);
            print_r($error);
            die('Error');
        }
        if($r_com_tipo_cambio->n_registros > 0){
            $com_tipo_cambio_id = $r_com_tipo_cambio->registros[0]['com_tipo_cambio_id'];
        }


        $this->data_selected_alta['com_tipo_cambio_id']['id'] = $com_tipo_cambio_id;
        $this->data_selected_alta['com_tipo_cambio_id']['filtro'] = array();

        $init_ctl = (new _fc_base())->init_base_fc(controler: $this,name_modelo_email: 'fc_email_cp');
        if(errores::$error){
            $error = $this->errores->error(mensaje: 'Error al inicializar',data:  $init_ctl);
            print_r($error);
            die('Error');
        }


        if(isset($_GET['fc_partida_id'])){
            $this->fc_partida_cp_id = $_GET['fc_partida_cp_id'];
        }

        $this->lista_get_data = true;


        $this->verifica_parents_alta = true;


    }

    public function adjunta(bool $header, bool $ws = false): array|stdClass
    {
        $this->modelo_entidad = new fc_complemento_pago(link: $this->link);
        $this->modelo_documento = new fc_complemento_pago_documento(link: $this->link);

        $r_template = parent::adjunta(header: $header, ws: $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar template',data:  $r_template,header:  $header, ws: $ws);
        }

        return $r_template;
    }

    public function adjunta_bd(bool $header, bool $ws = false): array|stdClass
    {
        $this->modelo_entidad = new fc_complemento_pago(link: $this->link);
        $this->modelo_documento = new fc_complemento_pago_documento(link: $this->link);

        $r_template = parent::adjunta_bd(header: $header, ws: $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar template',data:  $r_template,header:  $header, ws: $ws);
        }

        return $r_template;
    }

    public function ajusta_hora(bool $header, bool $ws = false): array|stdClass
    {
        $this->ctl_partida = new controlador_fc_partida_cp(link: $this->link);
        $this->modelo_entidad = new fc_complemento_pago(link: $this->link);
        $this->modelo_partida = new fc_partida_cp(link: $this->link);
        $this->modelo_retencion = new fc_retenido_cp(link: $this->link);
        $this->modelo_traslado = new fc_traslado_cp(link: $this->link);

        $result =  parent::ajusta_hora(header: $header,ws:  $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar template',data:  $result,header:  $header, ws: $ws);
        }
        return $result;

    }


    private function ajusta_fc_pagos(fc_docto_relacionado $fc_docto_relacionado_modelo, fc_factura $fc_factura_modelo,
                                    array $fc_pago, fc_pago_pago $fc_pago_pago_modelo,
                                    fc_pago_total $fc_pago_total_modelo, array $fc_pagos, int $indice_fc_pagos): array
    {
        $data_pagos = (new _pagos())->data_pagos(controlador_fc_complemento_pago: $this,
            fc_docto_relacionado_modelo:  $fc_docto_relacionado_modelo,fc_factura_modelo:  $fc_factura_modelo,
            fc_pago: $fc_pago, fc_pago_pago_modelo: $fc_pago_pago_modelo, fc_pago_total_modelo: $fc_pago_total_modelo);

        $fc_pagos[$indice_fc_pagos]['fc_pago_totales'] = $data_pagos->fc_pago_totales;
        $fc_pagos[$indice_fc_pagos]['fc_pago_pagos'] = $data_pagos->fc_pago_pagos;

        return $fc_pagos;
    }

    public function alta(bool $header, bool $ws = false): array|string
    {

        $this->modelo_entidad = $this->modelo;

        $r_alta = parent::alta(header: $header,ws:  $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar template',data:  $r_alta,header:  $header, ws: $ws);
        }

        return $r_alta;
    }

    public function alta_partida_bd(bool $header, bool $ws = false)
    {
        $this->modelo_entidad = $this->modelo;
        $this->modelo_partida = new fc_partida_cp(link: $this->link);
        $this->modelo_predial = new fc_cuenta_predial_cp(link: $this->link);
        $this->modelo_relacion = new fc_relacion_cp(link: $this->link);
        $this->modelo_relacionada = new fc_complemento_pago_relacionada(link: $this->link);
        $this->modelo_retencion = new fc_retenido_cp(link: $this->link);
        $this->modelo_traslado = new fc_traslado_cp(link: $this->link);


        $r_alta_partida =  parent::alta_partida_bd(header: $header,ws:  $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al insertar',data:  $r_alta_partida,header:  $header, ws: $ws);
        }

        return $r_alta_partida;

    }

    public function cancela(bool $header, bool $ws = false)
    {
        $this->modelo_entidad = new fc_complemento_pago(link: $this->link);

        $r_template = parent::cancela($header, $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar template',data:  $r_template,header:  $header, ws: $ws);
        }

        return $r_template;
    }

    public function cancela_bd(bool $header, bool $ws = false): array|stdClass
    {
        $this->modelo_entidad = new fc_complemento_pago(link: $this->link);
        $this->modelo_cancelacion = new fc_cancelacion_cp(link: $this->link);
        $this->modelo_etapa = new fc_complemento_pago_etapa(link: $this->link);

        $r_cancela_bd = parent::cancela_bd($header, $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al cancelar',data:  $r_cancela_bd,header:  $header, ws: $ws);
        }

        return $r_cancela_bd;
    }

    public function correo(bool $header, bool $ws = false): array|stdClass
    {
        $this->modelo_entidad = new fc_complemento_pago(link: $this->link);
        $this->modelo_email = new fc_email_cp(link: $this->link);

        $r_template = parent::correo($header, $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar template',data:  $r_template,header:  $header, ws: $ws);
        }

        return $r_template;
    }

    private function data_modifica(float $saldo_total): array|stdClass
    {

        $cat_sat_forma_pago_html = (new cat_sat_forma_pago_html(html: $this->html_base));
        $fc_complemento_pago_html = (new fc_complemento_pago_html(html: $this->html_base));

        $value_fecha_pago = date('Y-m-d H:i:s');
        $fecha_pago = $fc_complemento_pago_html->input_fecha(cols: 6,
            row_upd: new stdClass(), value_vacio: false, name: 'fecha_pago', value: $value_fecha_pago,
            value_hora: true);

        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al generar fecha_pago',data:  $fecha_pago);
        }

        $this->inputs->fecha_pago = $fecha_pago;

        $monto = $fc_complemento_pago_html->input_monto(cols: 6,row_upd: new stdClass(),value_vacio: false,
            value: $saldo_total);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al generar monto',data:  $monto);
        }

        $this->inputs->monto = $monto;

        $cat_sat_forma_pago_id_full = $cat_sat_forma_pago_html->select_cat_sat_forma_pago_id(
            cols: 6,con_registros: true,id_selected: -1,link: $this->link);

        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al generar cat_sat_forma_pago_id_full',data:  $cat_sat_forma_pago_id_full);
        }

        $this->inputs->cat_sat_forma_pago_id_full = $cat_sat_forma_pago_id_full;

        $link_fc_pago_pago_alta_bd = $this->obj_link->link_alta_bd(link:  $this->link,seccion: 'fc_pago_pago');
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al generar link_fc_pago_pago_alta_bd',data:  $link_fc_pago_pago_alta_bd);
        }

        $this->link_fc_pago_pago_alta_bd = $link_fc_pago_pago_alta_bd;

        $link_fc_docto_relacionado_alta_bd = $this->obj_link->link_con_id(accion: 'fc_docto_rel_alta_bd',
            link: $this->link,registro_id: $this->registro_id,seccion: $this->tabla);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al generar link_fc_docto_relacionado_alta_bd',
                data:  $link_fc_docto_relacionado_alta_bd);
        }

        $this->link_fc_docto_relacionado_alta_bd = $link_fc_docto_relacionado_alta_bd;

        $filtro['fc_complemento_pago.id'] = $this->registro_id;
        $r_fc_pago = (new fc_pago(link: $this->link))->filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener r_fc_pago',data:  $r_fc_pago);
        }

        $fc_pago_id = $fc_complemento_pago_html->hidden(name: 'fc_pago_id',value:  $r_fc_pago->registros[0]['fc_pago_id']);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener fc_pago_id',data:  $fc_pago_id);
        }
        $this->inputs->fc_pago_id = $fc_pago_id;

        $seccion_retorno = $fc_complemento_pago_html->hidden(name: 'seccion_retorno',value:  $this->tabla);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener seccion_retorno',data:  $seccion_retorno);
        }
        $this->inputs->seccion_retorno = $seccion_retorno;

        $id_retorno = $fc_complemento_pago_html->hidden(name: 'id_retorno',value:  $this->registro_id);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener id_retorno',data:  $id_retorno);
        }
        $this->inputs->id_retorno = $id_retorno;

        return $this->inputs;
    }


    public function descarga_xml(bool $header, bool $ws = false)
    {
        $this->modelo_documento = new fc_complemento_pago_documento(link: $this->link);

        $r_template = parent::descarga_xml($header, $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar template',data:  $r_template,header:  $header, ws: $ws);
        }

        return $r_template;
    }

    public function envia_cfdi(bool $header, bool $ws = false)
    {
        $this->modelo_email = new fc_email_cp(link: $this->link);
        $this->modelo_notificacion = new fc_notificacion_cp(link: $this->link);
        $this->modelo_etapa = new fc_complemento_pago_etapa(link: $this->link);
        $this->modelo_documento = new fc_complemento_pago_documento(link: $this->link);

        $r_envia = parent::envia_cfdi(header: $header,ws:  $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al enviar',data:  $r_envia,header:  $header, ws: $ws);
        }

        return $r_envia;

    }

    public function exportar_documentos(bool $header, bool $ws = false)
    {

        $this->modelo_traslado = new fc_traslado_cp(link: $this->link);
        $this->modelo_relacion = new fc_relacion_cp(link: $this->link);
        $this->modelo_relacionada = new fc_complemento_pago_relacionada(link: $this->link);
        $this->modelo_retencion = new fc_retenido_cp(link: $this->link);
        $this->modelo_predial = new fc_cuenta_predial_cp(link: $this->link);
        $this->modelo_partida = new fc_partida_cp(link: $this->link);
        $this->modelo_entidad = new fc_complemento_pago(link: $this->link);
        $this->modelo_documento = new fc_complemento_pago_documento(link: $this->link);
        $this->modelo_sello = new fc_cfdi_sellado_cp(link: $this->link);
        $this->modelo_uuid_ext = new fc_uuid_cp(link: $this->link);

        $r_template = parent::exportar_documentos($header, $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al insertar relacion',data:  $r_template,header:  $header, ws: $ws);
        }
        return $r_template;
    }

    public function fc_docto_rel_alta_bd(bool $header, bool $ws = false){

        $this->link->beginTransaction();

        $montos = $_POST['monto'];

        $altas = (new _doctos_rel())->inserta_doctos_relacionados(link: $this->link, montos: $montos);
        if (errores::$error) {
            $this->link->rollBack();
            return $this->retorno_error(mensaje: 'Error al insertar', data: $altas, header: $header, ws: $ws);
        }

        $siguiente_view = (new actions())->init_alta_bd();
        if(errores::$error){
            $this->link->rollBack();
            return $this->retorno_error(mensaje: 'Error al obtener siguiente view', data: $siguiente_view,
                header:  $header, ws: $ws);
        }

        $this->link->commit();
        if($header){

            $retorno = (new actions())->retorno_alta_bd(link: $this->link, registro_id: $this->registro_id,
                seccion: $this->tabla, siguiente_view: "modifica");
            if(errores::$error){
                return $this->retorno_error(mensaje: 'Error cambiar de view', data: $retorno,
                    header:  true, ws: $ws);
            }
            header('Location:'.$retorno);
            exit;
        }
        if($ws){
            header('Content-Type: application/json');
            echo json_encode($altas, JSON_THROW_ON_ERROR);
            exit;
        }
        return $altas;


    }


    public function fc_factura_relacionada_alta_bd(bool $header, bool $ws = false)
    {

        $this->modelo_relacion = new fc_relacion_cp(link: $this->link);
        $this->modelo_entidad = new fc_complemento_pago(link: $this->link);
        $this->modelo_relacionada = new fc_complemento_pago_relacionada(link: $this->link);
        $this->modelo_uuid_ext = new fc_uuid_cp(link: $this->link);

        $r_alta = parent::fc_factura_relacionada_alta_bd($header, $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al insertar relacion',data:  $r_alta,header:  $header, ws: $ws);
        }
        return $r_alta;

    }

    private function fc_pagos(fc_docto_relacionado $fc_docto_relacionado_modelo, fc_factura $fc_factura_modelo,
                              fc_pago $fc_pago_modelo, fc_pago_pago $fc_pago_pago_modelo, fc_pago_total $fc_pago_total_modelo){
        $fc_pagos = (new _pagos())->fc_pagos(fc_complemento_pago_id: $this->registro_id, fc_pago_modelo: $fc_pago_modelo);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener fc_pagos',data:  $fc_pagos);
        }

        foreach ($fc_pagos as $indice_fc_pagos=>$fc_pago){
            $fc_pagos = $this->ajusta_fc_pagos(fc_docto_relacionado_modelo: $fc_docto_relacionado_modelo,
                fc_factura_modelo:  $fc_factura_modelo, fc_pago: $fc_pago, fc_pago_pago_modelo: $fc_pago_pago_modelo,
                fc_pago_total_modelo:  $fc_pago_total_modelo,fc_pagos:  $fc_pagos,indice_fc_pagos:  $indice_fc_pagos);

        }
        return $fc_pagos;
    }


    public function fc_relacion_alta_bd(bool $header, bool $ws = false)
    {
        $this->modelo_entidad = new fc_complemento_pago(link: $this->link);
        $this->modelo_relacion = new fc_relacion_cp(link: $this->link);

        $r_alta = parent::fc_relacion_alta_bd($header, $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al insertar relacion',data:  $r_alta,header:  $header, ws: $ws);
        }
        return $r_alta;
    }
    public function genera_pdf(bool $header, bool $ws = false, bool $descarga = true, bool $guarda = false)
    {
        $this->modelo_documento = (new fc_complemento_pago_documento(link: $this->link));
        $this->modelo_entidad = $this->modelo;
        $this->modelo_partida = (new fc_partida_cp(link: $this->link));
        $this->modelo_predial = (new fc_cuenta_predial_cp(link: $this->link));
        $this->modelo_relacion = (new fc_relacion_cp(link: $this->link));
        $this->modelo_relacionada = (new fc_complemento_pago_relacionada(link: $this->link));
        $this->modelo_retencion = (new fc_retenido_cp(link: $this->link));
        $this->modelo_sello = (new fc_cfdi_sellado_cp(link: $this->link));
        $this->modelo_traslado = (new fc_traslado_cp(link: $this->link));
        $this->modelo_uuid_ext = (new fc_uuid_cp(link: $this->link));

        $r_genera = parent::genera_pdf($header, $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar pdf',data:  $r_genera,header:  $header, ws: $ws);
        }
        return $r_genera;
    }

    public function datos_reporte()
    {
        $reporte = $this->modelo->get_factura(modelo_partida: new fc_partida_cp(link: $this->link),
            modelo_predial: new fc_cuenta_predial_cp(link: $this->link), modelo_relacion: new fc_relacion_cp(link: $this->link),
            modelo_relacionada: new fc_complemento_pago_relacionada(link: $this->link), modelo_retencion: new fc_retenido_cp(link: $this->link),
            modelo_traslado: new fc_traslado_cp(link: $this->link), modelo_uuid_ext: new fc_uuid_cp(link: $this->link), registro_id: $this->registro_id);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al obtener datos para el reporte', data: $reporte);
        }

        print_r($reporte);exit();

        $filtro_cfdi[$this->modelo->key_id] = $reporte[$this->modelo->key_id];
        $cfdi_sellado = (new fc_cfdi_sellado_cp(link: $this->link))->filtro_and(filtro: $filtro_cfdi);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al obtener cfdi_sellado', data: $cfdi_sellado);
        }

        $data = (new _pdf())->data_factura(cfdi_sellado: $cfdi_sellado, name_entidad_sellado: (new fc_cfdi_sellado_cp(link: $this->link))->tabla);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al asignar datos', data: $data);
        }

        $filtro_ruta['org_empresa.id'] = $reporte['org_empresa_id'];
        $ruta_logo = (new org_logo(link: $this->link))->filtro_and(filtro: $filtro_ruta);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al obtener Logo', data: $ruta_logo);
        }

        $ruta_logo = $ruta_logo->n_registros > 0 ? $ruta_logo->registros[0]['doc_documento_ruta_relativa'] : '';

        $ruta_qr = (new fc_complemento_pago_documento(link: $this->link))->get_factura_documento(key_entidad_filter_id: $this->modelo->key_filtro_id,
            registro_id: $this->registro_id, tipo_documento: "qr_cfdi");
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al obtener QR', data: $ruta_qr);
        }

        $uso_cfdi = trim($reporte['cat_sat_uso_cfdi_codigo']) . ' ' . trim($reporte['cat_sat_uso_cfdi_descripcion']);
        $uso_cfdi = trim($uso_cfdi);
        $uso_cfdi = mb_convert_encoding($uso_cfdi, 'ISO-8859-1', 'UTF-8');

        $domicilio_receptor = trim($reporte['com_sucursal_calle']);
        $domicilio_receptor = trim($domicilio_receptor) . ' ' . trim($reporte['com_sucursal_numero_exterior']);
        $domicilio_receptor = trim($domicilio_receptor) . ' ' . trim($reporte['com_sucursal_numero_interior']);
        $domicilio_receptor = trim($domicilio_receptor) . ' ' . trim($reporte['com_sucursal_municipio']);
        $domicilio_receptor = trim($domicilio_receptor) . ' ' . trim($reporte['com_sucursal_estado']);
        $domicilio_receptor = trim($domicilio_receptor) . ' ' . $reporte['com_sucursal_cp'];
        $domicilio_receptor = mb_convert_encoding($domicilio_receptor, 'ISO-8859-1', 'UTF-8');

        $regimen = mb_convert_encoding($reporte['cat_sat_regimen_fiscal_cliente_codigo'] . ' ' . $reporte['cat_sat_regimen_fiscal_cliente_descripcion'], 'ISO-8859-1', 'UTF-8');
        $regimen = trim($regimen);

        $fecha_certificacion = '';
        if (isset($data->fecha_timbrado)) {
            $fecha_certificacion = $data->fecha_timbrado;
        }

        $folio_fiscal = '';
        if (isset($data->folio_fiscal)) {
            $folio_fiscal = $data->folio_fiscal;
        }

        $no_certificado = '';
        if (isset($data->no_certificado)) {
            $no_certificado = $data->no_certificado;
        }

        $no_certificado_sat = '';
        if (isset($data->no_certificado_sat)) {
            $no_certificado_sat = $data->no_certificado_sat;
        }

        $forma_pago = mb_convert_encoding($reporte['cat_sat_forma_pago_codigo'] . ' ' . $reporte['cat_sat_forma_pago_descripcion'], 'ISO-8859-1', 'UTF-8');
        $metodo_pago = mb_convert_encoding($reporte['cat_sat_metodo_pago_codigo'] . ' ' . $reporte['cat_sat_metodo_pago_descripcion'], 'ISO-8859-1', 'UTF-8');
        $tipo_comprobante = mb_convert_encoding($reporte['cat_sat_tipo_de_comprobante_codigo'] . ' ' . $reporte['cat_sat_tipo_de_comprobante_descripcion'], 'ISO-8859-1', 'UTF-8');
        $condiciones_pago = mb_convert_encoding($reporte['cat_sat_metodo_pago_descripcion'], 'ISO-8859-1', 'UTF-8');
        $moneda = mb_convert_encoding($reporte['cat_sat_moneda_codigo'] . ' ' . $reporte['cat_sat_moneda_descripcion'], 'ISO-8859-1', 'UTF-8');

        $fmt = new NumberFormatter('es_MX', NumberFormatter::CURRENCY);
        $totales_sub_total = round($reporte['fc_complemento_pago_sub_total'], 2);
        $totales_sub_total = $fmt->formatCurrency($totales_sub_total, "MXN");

        $totales_descuento = round($reporte['fc_complemento_pago_descuento'], 2);
        $totales_descuento = $fmt->formatCurrency($totales_descuento, "MXN");

        $totales_iva = round($reporte['fc_complemento_pago_total_traslados'], 2);
        $totales_iva = $fmt->formatCurrency($totales_iva, "MXN");

        $totales_isr_retenidos = round($reporte['fc_complemento_pago_total_retenciones'], 2);
        $totales_isr_retenidos = $fmt->formatCurrency($totales_isr_retenidos, "MXN");

        $totales_iva_retenidos = round($reporte['fc_complemento_pago_total_retenciones'], 2);
        $totales_iva_retenidos = $fmt->formatCurrency($totales_iva_retenidos, "MXN");

        $totales_total = round($reporte['fc_complemento_pago_total'], 2);
        $totales_total = $fmt->formatCurrency($totales_total, "MXN");

        $fc_factura_total = round($reporte['fc_complemento_pago_total'], 2);

        $formatterES = new NumberFormatter("es-ES", NumberFormatter::SPELLOUT);
        $izquierda = intval(floor($fc_factura_total));
        $derecha = intval(($fc_factura_total - floor($fc_factura_total)) * 100);
        $letra = $formatterES->format($izquierda) . " pesos ";
        if ((float)$derecha === 0.0) {
            $derecha = '00';
        }
        $letra .= $derecha . "/100 M.N.";

        $letra = strtoupper($letra);
        $letra = mb_convert_encoding($letra, 'ISO-8859-1', 'UTF-8');

        $productos = array();

        foreach ($reporte['partidas'] as $partida) {
            $cantidad = $partida['fc_partida_cp_cantidad'];
            $unidad = $partida['cat_sat_unidad_codigo'];
            $clave = $partida['com_producto_codigo_sat'];
            $descripcion = mb_convert_encoding($partida['fc_partida_cp_descripcion'], 'ISO-8859-1', 'UTF-8');
            $obj_impuesto = mb_convert_encoding($partida['cat_sat_obj_imp_descripcion'], 'ISO-8859-1', 'UTF-8');
            $valor_unitario = round($partida['fc_partida_cp_valor_unitario'], 2);
            $valor_unitario = $fmt->formatCurrency($valor_unitario, "MXN");
            $importe = round($partida['fc_partida_cp_sub_total'], 2);
            $importe = $fmt->formatCurrency($importe, "MXN");

            $productos[] = [
                'cantidad' => $cantidad,
                'unidad' => $unidad,
                'clave' => $clave,
                'descripcion' => $descripcion,
                'obj_impuesto' => $obj_impuesto,
                'valor_unitario' => $valor_unitario,
                'importe' => $importe
            ];
        }

        $datos = [
            'folio' => $reporte['fc_complemento_pago_folio'],
            'logo' => $ruta_logo,
            'qr' => $ruta_qr,
            'emisor' => [
                'emisor' => $reporte['org_empresa_razon_social'],
                'nombre' => $reporte['org_empresa_nombre_comercial'],
                'rfc' => $reporte['org_empresa_rfc'],
                'regimen' => $reporte['cat_sat_regimen_fiscal_descripcion'],
                'direccion' => "Av. Vallarta 6503 - Int. C2, Col. Ciudad Granja,45010, Zapopan, Jalisco",
                'telefono' => $reporte['org_empresa_telefono_1']
            ],
            'fechas' => [
                'fecha_emision' => $reporte['fc_complemento_pago_fecha'],
                'fecha_certificacion' => $fecha_certificacion,
                'cp_expedicion' => ''
            ],
            'receptor' => [
                'nombre' => $reporte['com_cliente_razon_social'],
                'rfc' => $reporte['com_cliente_rfc'],
                'uso_cfdi' => $uso_cfdi,
                'direccion' => $domicilio_receptor,
                'regimen' => $regimen
            ],
            'fiscales' => [
                'folio_sat' => $folio_fiscal,
                'certificado_emisor' => $no_certificado,
                'certificado_sat' => $no_certificado_sat,
                'leyenda' => '',
                'exportacion' => 'No aplica',
            ],
            'productos' => $productos,
            'pagos' => [
                'forma_pago' => $forma_pago,
                'metodo_pago' => $metodo_pago,
                'tipo_comprobante' => $tipo_comprobante,
                'condiciones_pago' => $condiciones_pago,
                'moneda' => $moneda,
            ],
            'totales' => [
                'subtotal' => $totales_sub_total,
                'descuento' => $totales_descuento,
                'iva' => $totales_iva,
                'isr_retenido' => $totales_isr_retenidos,
                'iva_retenido' => $totales_iva_retenidos,
                'total' => $totales_total,
                'letra' => $letra,
            ],
            'sellos' => [
                'cadena_original' => $data->complento,
                'sello_cfdi' => $data->sello_cfdi,
                'sello_sat' => $data->sello_sat
            ]

        ];




        return $datos;
    }


    public function genera_xml(bool $header, bool $ws = false)
    {
        $this->modelo_entidad = new fc_complemento_pago(link: $this->link);
        $this->modelo_documento = new fc_complemento_pago_documento(link: $this->link);
        $this->modelo_etapa = new fc_complemento_pago_etapa(link: $this->link);
        $this->modelo_partida = new fc_partida_cp(link: $this->link);
        $this->modelo_predial = new fc_cuenta_predial_cp(link: $this->link);
        $this->modelo_relacion = new fc_relacion_cp(link: $this->link);
        $this->modelo_relacionada = new fc_complemento_pago_relacionada(link: $this->link);
        $this->modelo_retencion = new fc_retenido_cp(link: $this->link);
        $this->modelo_traslado = new fc_traslado_cp(link: $this->link);
        $this->modelo_uuid_ext = new fc_uuid_cp(link: $this->link);


        $r_xml = parent::genera_xml(header: $header,ws:  $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar xml',data:  $r_xml,header:  $header, ws: $ws);
        }
        return $r_xml;
    }

    public function init_datatable(): stdClass
    {

        $columns[$this->modelo->tabla."_id"]["titulo"] = "Id";
        $columns[$this->modelo->tabla."_folio"]["titulo"] = "Fol";
        $columns["com_cliente_razon_social"]["titulo"] = "Cliente";
        $columns["com_cliente_rfc"]["titulo"] = "RFC";
        $columns[$this->modelo->tabla."_fecha"]["titulo"] = "Fecha";
        $columns[$this->modelo->tabla."_total_pagos"]["titulo"] = "Total";
        $columns[$this->modelo->tabla."_uuid"]["titulo"] = "UUID";
        $columns[$this->modelo->tabla."_etapa"]["titulo"] = "Estatus";


        $filtro = array($this->modelo->tabla.".folio","com_cliente.razon_social",
            "com_cliente.rfc",$this->modelo->tabla.'.fecha',$this->modelo->tabla.'_etapa');

        $datatables = new stdClass();
        $datatables->columns = $columns;
        $datatables->filtro = $filtro;
        $datatables->menu_active = true;

        return $datatables;
    }


    public function modifica(bool $header, bool $ws = false): array|stdClass
    {
        $this->ctl_partida = new controlador_fc_partida_cp(link: $this->link);
        $this->modelo_entidad = $this->modelo;
        $this->modelo_partida = (new fc_partida_cp(link: $this->link));
        $this->modelo_retencion = (new fc_retenido_cp(link: $this->link));
        $this->modelo_traslado = (new fc_traslado_cp(link: $this->link));
        $this->modelo_email = (new fc_email_cp(link: $this->link));


        $r_modifica = parent::modifica(header: $header,ws:  $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar template',data:  $r_modifica,header:  $header, ws: $ws);
        }


        $fc_pago_modelo = new fc_pago(link: $this->link);
        $fc_pago_total_modelo = new fc_pago_total(link: $this->link);
        $fc_pago_pago_modelo = new fc_pago_pago(link: $this->link);
        $fc_docto_relacionado_modelo = new fc_docto_relacionado(link: $this->link);
        $fc_factura_modelo = new fc_factura(link: $this->link);



        $fc_pagos = $this->fc_pagos(fc_docto_relacionado_modelo: $fc_docto_relacionado_modelo,
                fc_factura_modelo: $fc_factura_modelo, fc_pago_modelo: $fc_pago_modelo,
                fc_pago_pago_modelo: $fc_pago_pago_modelo, fc_pago_total_modelo: $fc_pago_total_modelo);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener fc_pagos',data:  $fc_pagos,header:  $header, ws: $ws);
        }

        $total_pagos = (new _pagos())->fc_pago_totales_by_complemento(fc_complemento_pago_id: $this->registro_id,
            fc_pago_pago_modelo: $fc_pago_pago_modelo);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener total_pagos',data:  $total_pagos,header:  $header, ws: $ws);
        }

        $this->total_pagos = $total_pagos;


        $com_tipo_cambio_pago_monto = 1;
        $com_tipo_cambio_pago_cat_sat_moneda_id = 161;


        foreach ($fc_pagos as $fc_pago){
            $fc_pago_pagos = $fc_pago['fc_pago_pagos'];
            foreach ($fc_pago_pagos as $fc_pago_pago){
                $com_tipo_cambio_pago_monto = $fc_pago_pago['com_tipo_cambio_monto'];
                $com_tipo_cambio_pago_cat_sat_moneda_id = $fc_pago_pago['cat_sat_moneda_id'];
            }
        }


        $saldos = (new _pagos())->data_saldos_fc(com_tipo_cambio_pago_cat_sat_moneda_id: $com_tipo_cambio_pago_cat_sat_moneda_id,
            com_tipo_cambio_pago_monto: $com_tipo_cambio_pago_monto, fc_complemento_pago_id: $this->registro_id, link: $this->link,
            total_pagos: $total_pagos);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener saldos',data:  $saldos,header:  $header, ws: $ws);
        }


        $data = $this->data_modifica(saldo_total: $saldos->saldo_total);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar template',data:  $data,header:  $header, ws: $ws);
        }

        $this->saldo_total = $saldos->saldo_total;
        $this->fc_facturas = $saldos->fc_facturas;
        $this->fc_pagos = $fc_pagos;


        return $r_modifica;
    }


    public function relaciones(bool $header, bool $ws = false)
    {
        $this->modelo_partida = new fc_partida_cp(link: $this->link);
        $this->modelo_entidad = new fc_complemento_pago(link: $this->link);
        $this->modelo_retencion = new fc_retenido_cp(link: $this->link);
        $this->modelo_traslado = new fc_traslado_cp(link: $this->link);
        $this->modelo_relacion = new fc_relacion_cp(link: $this->link);
        $this->modelo_relacionada = new fc_complemento_pago_relacionada(link: $this->link);
        $this->modelo_etapa = new fc_complemento_pago_etapa(link: $this->link);
        $this->modelo_uuid_ext = new fc_uuid_cp(link: $this->link);

        $r_modifica = parent::relaciones(header: $header,ws:  $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar template',data:  $r_modifica,header:  $header, ws: $ws);
        }

        return $r_modifica;
    }


    public function timbra_xml(bool $header, bool $ws = false): array|stdClass
    {

        $this->modelo_entidad = $this->modelo;
        $this->modelo_documento = new fc_complemento_pago_documento(link: $this->link);
        $this->modelo_etapa = new fc_complemento_pago_etapa(link: $this->link);
        $this->modelo_partida = new fc_partida_cp(link: $this->link);
        $this->modelo_predial = new fc_cuenta_predial_cp(link: $this->link);
        $this->modelo_relacion = new fc_relacion_cp(link: $this->link);
        $this->modelo_relacionada = new fc_complemento_pago_relacionada(link: $this->link);
        $this->modelo_retencion = new fc_retenido_cp(link: $this->link);
        $this->modelo_sello = new fc_cfdi_sellado_cp(link: $this->link);
        $this->modelo_traslado = new fc_traslado_cp(link: $this->link);
        $this->modelo_uuid_ext = new fc_uuid_cp(link: $this->link);

        $r_timbra = parent::timbra_xml(header: $header,ws:  $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al timbrar',data:  $r_timbra,header:  $header, ws: $ws);
        }



        return $r_timbra;

    }






}
