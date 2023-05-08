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
use gamboamartin\comercial\models\com_sucursal;
use gamboamartin\compresor\compresor;
use gamboamartin\documento\models\doc_documento;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\html\fc_factura_html;
use gamboamartin\facturacion\html\fc_partida_html;
use gamboamartin\facturacion\models\_pdf;
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
use gamboamartin\facturacion\models\fc_partida_nc;
use gamboamartin\facturacion\models\fc_relacion;
use gamboamartin\facturacion\models\fc_retenido;
use gamboamartin\facturacion\models\fc_traslado;
use gamboamartin\proceso\models\pr_proceso;
use gamboamartin\system\actions;
use gamboamartin\system\html_controler;
use gamboamartin\template\html;
use gamboamartin\xml_cfdi_4\timbra;
use html\cat_sat_conf_imps_html;
use html\cat_sat_motivo_cancelacion_html;
use html\cat_sat_tipo_relacion_html;
use html\com_cliente_html;
use html\com_email_cte_html;
use html\com_sucursal_html;
use JsonException;
use PDO;
use stdClass;

class controlador_fc_factura extends _base_system_fc {

    public array|stdClass $keys_selects = array();
    public controlador_com_producto $controlador_com_producto;

    public string $button_fc_factura_relaciones = '';
    public string $button_fc_factura_timbra = '';

    public string $rfc = '';
    public string $razon_social = '';

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


        $r_alta_partida =  parent::alta_partida_bd($header, $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al insertar',data:  $r_alta_partida,header:  $header, ws: $ws);
        }

        return $r_alta_partida;

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

        $r_timbra = parent::timbra_xml($header, $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al timbrar',data:  $r_timbra,header:  $header, ws: $ws);
        }

        return $r_timbra;

    }


}
