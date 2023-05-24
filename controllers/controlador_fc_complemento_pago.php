<?php
/**
 * @author Martin Gamboa Vazquez
 * @version 1.0.0
 * @created 2022-05-14
 * @final En proceso
 *
 */
namespace gamboamartin\facturacion\controllers;

use gamboamartin\errores\errores;
use gamboamartin\facturacion\html\fc_complemento_pago_html;
use gamboamartin\facturacion\models\fc_cancelacion_cp;
use gamboamartin\facturacion\models\fc_cfdi_sellado_cp;
use gamboamartin\facturacion\models\fc_complemento_pago;
use gamboamartin\facturacion\models\fc_complemento_pago_documento;
use gamboamartin\facturacion\models\fc_complemento_pago_etapa;
use gamboamartin\facturacion\models\fc_complemento_pago_relacionada;
use gamboamartin\facturacion\models\fc_cuenta_predial_cp;
use gamboamartin\facturacion\models\fc_email_cp;
use gamboamartin\facturacion\models\fc_notificacion_cp;
use gamboamartin\facturacion\models\fc_pago;
use gamboamartin\facturacion\models\fc_partida_cp;
use gamboamartin\facturacion\models\fc_relacion_cp;
use gamboamartin\facturacion\models\fc_retenido_cp;
use gamboamartin\facturacion\models\fc_traslado_cp;
use gamboamartin\facturacion\models\fc_uuid_cp;
use gamboamartin\template\html;

use html\cat_sat_forma_pago_html;
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
    public int $fc_complemento_pago_id = -1;
    public int $fc_partida_cp_id = -1;
    public stdClass $partidas;

    public string $link_fc_pago_pago_alta_bd = '';


    public array $relaciones = array();
    public array$complementos_pago_cliente = array();
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

    public function alta_partida_bd(bool $header, bool $ws = false)
    {
        $this->modelo_entidad = $this->modelo;
        $this->modelo_partida = new fc_partida_cp(link: $this->link);
        $this->modelo_predial = new fc_cuenta_predial_cp(link: $this->link);
        $this->modelo_relacion = new fc_relacion_cp(link: $this->link);
        $this->modelo_relacionada = new fc_complemento_pago_relacionada(link: $this->link);
        $this->modelo_retencion = new fc_retenido_cp(link: $this->link);
        $this->modelo_traslado = new fc_traslado_cp(link: $this->link);


        $r_alta_partida =  parent::alta_partida_bd($header, $ws); // TODO: Change the autogenerated stub
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

        $r_envia = parent::envia_cfdi($header, $ws); // TODO: Change the autogenerated stub
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

    public function fc_factura_relacionada_alta_bd(bool $header, bool $ws = false)
    {

        $this->modelo_relacion = new fc_relacion_cp(link: $this->link);
        $this->modelo_entidad = new fc_complemento_pago(link: $this->link);
        $this->modelo_relacionada = new fc_complemento_pago_relacionada(link: $this->link);

        $r_alta = parent::fc_factura_relacionada_alta_bd($header, $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al insertar relacion',data:  $r_alta,header:  $header, ws: $ws);
        }
        return $r_alta;

    }

    public function genera_pdf(bool $header, bool $ws = false)
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

        $r_genera = parent::genera_pdf($header, $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar pdf',data:  $r_genera,header:  $header, ws: $ws);
        }
        return $r_genera;
    }

    public function modifica(bool $header, bool $ws = false): array|stdClass
    {
        $this->ctl_partida = new controlador_fc_partida_cp(link: $this->link);
        $this->modelo_entidad = $this->modelo;
        $this->modelo_partida = (new fc_partida_cp(link: $this->link));
        $this->modelo_retencion = (new fc_retenido_cp(link: $this->link));
        $this->modelo_traslado = (new fc_traslado_cp(link: $this->link));
        $this->modelo_email = (new fc_email_cp(link: $this->link));


        $r_modifica = parent::modifica($header, $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar template',data:  $r_modifica,header:  $header, ws: $ws);
        }

        $value_fecha_pago = date('Y-m-d H:i:s');
        $fecha_pago = (new fc_complemento_pago_html(html: $this->html_base))->input_fecha(cols: 6,
            row_upd: new stdClass(), value_vacio: false, name: 'fecha_pago', value: $value_fecha_pago,
            value_hora: true);

        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar fecha_pago',data:  $fecha_pago,header:  $header, ws: $ws);
        }

        $this->inputs->fecha_pago = $fecha_pago;

        $monto = (new fc_complemento_pago_html(html: $this->html_base))->input_monto(cols: 6,row_upd: new stdClass(),value_vacio: false);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar monto',data:  $monto,header:  $header, ws: $ws);
        }

        $this->inputs->monto = $monto;

        $cat_sat_forma_pago_id_full = (new cat_sat_forma_pago_html(html: $this->html_base))->select_cat_sat_forma_pago_id(
            cols: 6,con_registros: true,id_selected: -1,link: $this->link);

        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar cat_sat_forma_pago_id_full',data:  $cat_sat_forma_pago_id_full,header:  $header, ws: $ws);
        }

        $this->inputs->cat_sat_forma_pago_id_full = $cat_sat_forma_pago_id_full;

        $link_fc_pago_pago_alta_bd = $this->obj_link->link_alta_bd(link:  $this->link,seccion: 'fc_pago_pago');
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar link_fc_pago_pago_alta_bd',data:  $link_fc_pago_pago_alta_bd,header:  $header, ws: $ws);
        }

        $this->link_fc_pago_pago_alta_bd = $link_fc_pago_pago_alta_bd;

        $filtro['fc_complemento_pago.id'] = $this->registro_id;
        $r_fc_pago = (new fc_pago(link: $this->link))->filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener r_fc_pago',data:  $r_fc_pago,header:  $header, ws: $ws);
        }
        $fc_pago_id = (new fc_complemento_pago_html(html: $this->html_base))->hidden(name: 'fc_pago_id',value:  $r_fc_pago->registros[0]['fc_pago_id']);
        $this->inputs->fc_pago_id = $fc_pago_id;

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

        $r_modifica = parent::relaciones($header, $ws); // TODO: Change the autogenerated stub
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

        $r_timbra = parent::timbra_xml($header, $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al timbrar',data:  $r_timbra,header:  $header, ws: $ws);
        }

        return $r_timbra;

    }






}
