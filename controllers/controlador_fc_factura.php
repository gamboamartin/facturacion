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
use gamboamartin\facturacion\html\fc_factura_html;
use gamboamartin\facturacion\models\fc_cancelacion;
use gamboamartin\facturacion\models\fc_cfdi_sellado;
use gamboamartin\facturacion\models\fc_cuenta_predial;
use gamboamartin\facturacion\models\fc_email;
use gamboamartin\facturacion\models\fc_factura;
use gamboamartin\facturacion\models\fc_factura_documento;
use gamboamartin\facturacion\models\fc_factura_etapa;
use gamboamartin\facturacion\models\fc_factura_relacionada;
use gamboamartin\facturacion\models\fc_notificacion;
use gamboamartin\facturacion\models\fc_partida;
use gamboamartin\facturacion\models\fc_relacion;
use gamboamartin\facturacion\models\fc_retenido;
use gamboamartin\facturacion\models\fc_traslado;
use gamboamartin\facturacion\models\fc_uuid_fc;
use gamboamartin\template\html;
use PDO;
use stdClass;

class controlador_fc_factura extends _base_system_fc {

    public array|stdClass $keys_selects = array();
    public controlador_com_producto $controlador_com_producto;




    public string $rfc = '';
    public string $razon_social = '';

    public string $link_fc_factura_nueva_partida = '';

    public string $link_fc_email_alta_bd = '';

    public string $link_com_producto = '';

    public string $link_factura_cancela = '';
    public string $link_factura_genera_xml = '';
    public string $link_factura_timbra_xml = '';
    public string $button_fc_factura_correo = '';

    public int $fc_factura_id = -1;
    public int $fc_partida_id = -1;


    public array$facturas_cliente = array();
    public function __construct(PDO $link, html $html = new \gamboamartin\template_1\html(),
                                stdClass $paths_conf = new stdClass()){
        $modelo = new fc_factura(link: $link);
        $this->modelo = $modelo;
        $this->cat_sat_tipo_de_comprobante = 'Ingreso';
        $html_ = new fc_factura_html(html: $html);
        $this->html_fc = $html_;

        parent::__construct(html_: $html_, link: $link,modelo:  $modelo, paths_conf: $paths_conf);


        $this->data_selected_alta['cat_sat_forma_pago_id']['id'] = -1;
        $this->data_selected_alta['cat_sat_forma_pago_id']['filtro'] = array();

        $this->data_selected_alta['cat_sat_metodo_pago_id']['id'] = -1;
        $this->data_selected_alta['cat_sat_metodo_pago_id']['filtro'] = array();

        $this->data_selected_alta['cat_sat_moneda_id']['id'] = -1;
        $this->data_selected_alta['cat_sat_moneda_id']['filtro'] = array();

        $this->data_selected_alta['com_tipo_cambio_id']['id'] = -1;
        $this->data_selected_alta['com_tipo_cambio_id']['filtro'] = array();

        $this->data_selected_alta['cat_sat_uso_cfdi_id']['id'] = -1;
        $this->data_selected_alta['cat_sat_uso_cfdi_id']['filtro'] = array();

        $init_ctl = (new _fc_base())->init_base_fc(controler: $this,name_modelo_email: 'fc_email');
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

        $thead_relacion = $this->thead_relacion();
        if(errores::$error){
            $error = $this->errores->error(mensaje: 'Error al generar html',data:  $thead_relacion);
            print_r($error);
            die('Error');
        }



    }

    public function alta_partida_bd(bool $header, bool $ws = false)
    {
        $this->modelo_entidad = $this->modelo;
        $this->modelo_partida = new fc_partida(link: $this->link);
        $this->modelo_predial = new fc_cuenta_predial(link: $this->link);
        $this->modelo_relacion = new fc_relacion(link: $this->link);
        $this->modelo_relacionada = new fc_factura_relacionada(link: $this->link);
        $this->modelo_retencion = new fc_retenido(link: $this->link);
        $this->modelo_traslado = new fc_traslado(link: $this->link);
        $this->modelo_uuid_ext = new fc_uuid_fc(link: $this->link);


        $r_alta_partida =  parent::alta_partida_bd($header, $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al insertar',data:  $r_alta_partida,header:  $header, ws: $ws);
        }

        return $r_alta_partida;

    }

    public function cancela(bool $header, bool $ws = false)
    {
        $this->modelo_entidad = new fc_factura(link: $this->link);

        $r_template = parent::cancela($header, $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar template',data:  $r_template,header:  $header, ws: $ws);
        }

        return $r_template;
    }

    public function cancela_bd(bool $header, bool $ws = false): array|stdClass
    {
        $this->modelo_entidad = new fc_factura(link: $this->link);
        $this->modelo_cancelacion = new fc_cancelacion(link: $this->link);
        $this->modelo_etapa = new fc_factura_etapa(link: $this->link);

        $r_cancela_bd = parent::cancela_bd($header, $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al cancelar',data:  $r_cancela_bd,header:  $header, ws: $ws);
        }

        return $r_cancela_bd;
    }

    public function correo(bool $header, bool $ws = false): array|stdClass
    {
        $this->modelo_entidad = new fc_factura(link: $this->link);
        $this->modelo_email = new fc_email(link: $this->link);

        $r_template = parent::correo($header, $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar template',data:  $r_template,header:  $header, ws: $ws);
        }

        return $r_template;
    }

    public function descarga_xml(bool $header, bool $ws = false)
    {
        $this->modelo_documento = new fc_factura_documento(link: $this->link);

        $r_template = parent::descarga_xml($header, $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar template',data:  $r_template,header:  $header, ws: $ws);
        }

        return $r_template;
    }

    public function duplica(bool $header, bool $ws = false)
    {
        $this->modelo_entidad = new fc_factura(link: $this->link);
        $this->modelo_partida = new fc_partida(link: $this->link);
        $this->modelo_retencion = new fc_retenido(link: $this->link);
        $this->modelo_traslado = new fc_traslado(link: $this->link);


        $r_duplica = parent::duplica($header, $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al r_duplica',data:  $r_duplica,header:  $header, ws: $ws);
        }

        return $r_duplica;
    }

    public function elimina_sin_restriccion(bool $header, bool $ws = false){

        $this->modelo_entidad = new fc_factura(link: $this->link);
        $this->modelo_entidad->valida_restriccion = false;


        $r_elimina_bd = parent::elimina_sin_restriccion($header, $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al r_elimina_bd',data:  $r_elimina_bd,header:  $header, ws: $ws);
        }

        return $r_elimina_bd;

    }

    public function envia_cfdi(bool $header, bool $ws = false)
    {
        $this->modelo_email = new fc_email(link: $this->link);
        $this->modelo_notificacion = new fc_notificacion(link: $this->link);
        $this->modelo_etapa = new fc_factura_etapa(link: $this->link);

        $r_envia = parent::envia_cfdi($header, $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al enviar',data:  $r_envia,header:  $header, ws: $ws);
        }

        return $r_envia;

    }

    public function exportar_documentos(bool $header, bool $ws = false)
    {

        $this->modelo_traslado = new fc_traslado(link: $this->link);
        $this->modelo_relacion = new fc_relacion(link: $this->link);
        $this->modelo_relacionada = new fc_factura_relacionada(link: $this->link);
        $this->modelo_retencion = new fc_retenido(link: $this->link);
        $this->modelo_predial = new fc_cuenta_predial(link: $this->link);
        $this->modelo_partida = new fc_partida(link: $this->link);
        $this->modelo_entidad = new fc_factura(link: $this->link);
        $this->modelo_documento = new fc_factura_documento(link: $this->link);
        $this->modelo_sello = new fc_cfdi_sellado(link: $this->link);
        $this->modelo_uuid_ext = new fc_uuid_fc(link: $this->link);

        $r_template = parent::exportar_documentos($header, $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al insertar relacion',data:  $r_template,header:  $header, ws: $ws);
        }
        return $r_template;
    }

    public function fc_factura_relacionada_alta_bd(bool $header, bool $ws = false)
    {

        $this->modelo_relacion = new fc_relacion(link: $this->link);
        $this->modelo_entidad = new fc_factura(link: $this->link);
        $this->modelo_relacionada = new fc_factura_relacionada(link: $this->link);
        $this->modelo_uuid_ext = new fc_uuid_fc(link: $this->link);

        $r_alta = parent::fc_factura_relacionada_alta_bd($header, $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al insertar relacion',data:  $r_alta,header:  $header, ws: $ws);
        }
        return $r_alta;

    }

    public function fc_relacion_alta_bd(bool $header, bool $ws = false)
    {
        $this->modelo_entidad = new fc_factura(link: $this->link);
        $this->modelo_relacion = new fc_relacion(link: $this->link);

        $r_alta = parent::fc_relacion_alta_bd($header, $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al insertar relacion',data:  $r_alta,header:  $header, ws: $ws);
        }
        return $r_alta;
    }

    public function genera_pdf(bool $header, bool $ws = false)
    {
        $this->modelo_documento = (new fc_factura_documento(link: $this->link));
        $this->modelo_entidad = $this->modelo;
        $this->modelo_partida = (new fc_partida(link: $this->link));
        $this->modelo_predial = (new fc_cuenta_predial(link: $this->link));
        $this->modelo_relacion = (new fc_relacion(link: $this->link));
        $this->modelo_relacionada = (new fc_factura_relacionada(link: $this->link));
        $this->modelo_retencion = (new fc_retenido(link: $this->link));
        $this->modelo_sello = (new fc_cfdi_sellado(link: $this->link));
        $this->modelo_traslado = (new fc_traslado(link: $this->link));
        $this->modelo_uuid_ext = (new fc_uuid_fc(link: $this->link));

        $r_genera = parent::genera_pdf($header, $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar pdf',data:  $r_genera,header:  $header, ws: $ws);
        }
        return $r_genera;
    }

    public function modifica(bool $header, bool $ws = false): array|stdClass
    {
        $this->ctl_partida = new controlador_fc_partida(link: $this->link);
        $this->modelo_entidad = $this->modelo;
        $this->modelo_partida = (new fc_partida(link: $this->link));
        $this->modelo_retencion = (new fc_retenido(link: $this->link));
        $this->modelo_traslado = (new fc_traslado(link: $this->link));
        $this->modelo_email = (new fc_email(link: $this->link));


        $r_modifica = parent::modifica($header, $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar template',data:  $r_modifica,header:  $header, ws: $ws);
        }

        return $r_modifica;
    }

    public function relaciones(bool $header, bool $ws = false)
    {
        $this->modelo_partida = new fc_partida(link: $this->link);
        $this->modelo_entidad = new fc_factura(link: $this->link);
        $this->modelo_retencion = new fc_retenido(link: $this->link);
        $this->modelo_traslado = new fc_traslado(link: $this->link);
        $this->modelo_relacion = new fc_relacion(link: $this->link);
        $this->modelo_relacionada = new fc_factura_relacionada(link: $this->link);
        $this->modelo_etapa = new fc_factura_etapa(link: $this->link);
        $this->modelo_uuid_ext = new fc_uuid_fc(link: $this->link);

        $r_modifica = parent::relaciones($header, $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar template',data:  $r_modifica,header:  $header, ws: $ws);
        }

        return $r_modifica;
    }

    public function timbra_xml(bool $header, bool $ws = false): array|stdClass
    {

        $this->modelo_entidad = $this->modelo;
        $this->modelo_documento = new fc_factura_documento(link: $this->link);
        $this->modelo_etapa = new fc_factura_etapa(link: $this->link);
        $this->modelo_partida = new fc_partida(link: $this->link);
        $this->modelo_predial = new fc_cuenta_predial(link: $this->link);
        $this->modelo_relacion = new fc_relacion(link: $this->link);
        $this->modelo_relacionada = new fc_factura_relacionada(link: $this->link);
        $this->modelo_retencion = new fc_retenido(link: $this->link);
        $this->modelo_sello = new fc_cfdi_sellado(link: $this->link);
        $this->modelo_traslado = new fc_traslado(link: $this->link);
        $this->modelo_uuid_ext = new fc_uuid_fc(link: $this->link);

        $r_timbra = parent::timbra_xml($header, $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al timbrar',data:  $r_timbra,header:  $header, ws: $ws);
        }

        return $r_timbra;

    }

    public function verifica_cancelacion(bool $header, bool $ws = false)
    {

        $this->modelo_partida = new fc_partida(link: $this->link);
        $this->modelo_entidad = new fc_factura(link: $this->link);
        $this->modelo_retencion = new fc_retenido(link: $this->link);
        $this->modelo_traslado = new fc_traslado(link: $this->link);
        $this->modelo_etapa = new fc_factura_etapa(link: $this->link);


        $r_verifica = parent::verifica_cancelacion($header, $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al timbrar',data:  $r_verifica,header:  $header, ws: $ws);
        }


        return $r_verifica;

    }


}
