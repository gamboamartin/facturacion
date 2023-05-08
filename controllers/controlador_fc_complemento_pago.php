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
use gamboamartin\facturacion\models\fc_complemento_pago;
use gamboamartin\template\html;

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




}
