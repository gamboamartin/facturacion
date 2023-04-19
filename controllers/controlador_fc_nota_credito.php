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
use gamboamartin\cat_sat\models\cat_sat_regimen_fiscal;
use gamboamartin\cat_sat\models\cat_sat_tipo_de_comprobante;
use gamboamartin\cat_sat\models\cat_sat_metodo_pago;
use gamboamartin\cat_sat\models\cat_sat_moneda;
use gamboamartin\cat_sat\models\cat_sat_forma_pago;
use gamboamartin\cat_sat\models\cat_sat_uso_cfdi;
use gamboamartin\comercial\models\com_tipo_cambio;
use gamboamartin\comercial\models\com_sucursal;
use gamboamartin\direccion_postal\models\dp_calle_pertenece;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\html\fc_nota_credito_html;
use gamboamartin\facturacion\models\fc_nota_credito;
use gamboamartin\facturacion\models\fc_csd;
use gamboamartin\system\links_menu;
use gamboamartin\system\system;

use gamboamartin\template\html;

use PDO;
use stdClass;

class controlador_fc_nota_credito extends system{

    public array|stdClass $keys_selects = array();

    public function __construct(PDO $link, html $html = new \gamboamartin\template_1\html(),
                                stdClass $paths_conf = new stdClass()){
        $modelo = new fc_nota_credito(link: $link);
        $html_ = new fc_nota_credito_html(html: $html);
        $obj_link = new links_menu(link: $link, registro_id:  $this->registro_id);

        $datatables = $this->init_datatable();
        if(errores::$error){
            $error = $this->errores->error(mensaje: 'Error al inicializar datatable',data: $datatables);
            print_r($error);
            die('Error');
        }

        parent::__construct(html:$html_, link: $link,modelo:  $modelo, obj_link: $obj_link, datatables: $datatables,
            paths_conf: $paths_conf);

        $configuraciones = $this->init_configuraciones();
        if(errores::$error){
            $error = $this->errores->error(mensaje: 'Error al inicializar configuraciones',data: $configuraciones);
            print_r($error);
            die('Error');
        }

        $inputs = $this->init_inputs();
        if(errores::$error){
            $error = $this->errores->error(mensaje: 'Error al inicializar inputs',data:  $inputs);
            print_r($error);
            die('Error');
        }

        $this->parents_verifica[] = (new dp_calle_pertenece(link: $this->link));
        $this->parents_verifica[] = (new cat_sat_tipo_de_comprobante(link: $this->link));
        $this->parents_verifica[] = (new cat_sat_regimen_fiscal(link: $this->link));
        $this->parents_verifica[] = (new com_sucursal(link: $this->link));
        $this->parents_verifica[] = (new fc_csd(link: $this->link));
        $this->parents_verifica[] = (new com_tipo_cambio(link: $this->link));
        $this->parents_verifica[] = (new cat_sat_forma_pago(link: $this->link));
        $this->parents_verifica[] = (new cat_sat_metodo_pago(link: $this->link));
        $this->parents_verifica[] = (new cat_sat_moneda(link: $this->link));
        $this->parents_verifica[] = (new cat_sat_uso_cfdi(link: $this->link));

        $this->verifica_parents_alta = true;


    }

    public function alta(bool $header, bool $ws = false): array|string
    {
        $r_alta =  parent::alta(header: false);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar template',data:  $r_alta, header: $header,ws:$ws);
        }

        $inputs = $this->genera_inputs(keys_selects:  $this->keys_selects);
        if(errores::$error){
            $error = $this->errores->error(mensaje: 'Error al generar inputs',data:  $inputs);
            print_r($error);
            die('Error');
        }

        return $r_alta;
    }


    private function init_configuraciones(): controler
    {
        $this->seccion_titulo = 'Notas de Créditos';
        $this->titulo_lista = 'Registro de Notas de Créditos';

        return $this;
    }

    public function init_datatable(): stdClass
    {

        $columns["fc_nota_credito_id"]["titulo"] = "Id";
        $columns["fc_nota_credito_codigo"]["titulo"] = "Código";
        $columns["dp_calle_pertenece_descripcion"]["titulo"] = "Calle Pertenece";
        $columns["cat_sat_tipo_de_comprobante_descripcion"]["titulo"] = "Tipo de Comprobante";
        $columns["cat_sat_regimen_fiscal_factor"]["titulo"] = "Regimen Fiscal";
        $columns["com_sucursal_descripcion"]["titulo"] = "Sucursal";
        $columns["fc_csd_descripcion"]["titulo"] = "Certificado de Sello Digital";
        $columns["cat_sat_forma_pago_descripcion"]["titulo"] = "Forma de Pago";
        $columns["cat_sat_metodo_pago_descripcion"]["titulo"] = "Metodo de Pago";
        $columns["com_tipo_cambio_descripcion"]["titulo"] = "Tipo de Cambio";
        $columns["cat_sat_uso_cfdi"]["titulo"] = "Uso de cfdi";
        $columns["cat_sat_moneda_descripcion"]["titulo"] = "Moneda";
        $columns["fc_nota_credito_descripcion"]["titulo"] = "Nota de Créditos";


        $filtro = array("fc_nota_credito.id", "fc_nota_credito.codigo", "fc_nota_credito.descripcion",
            "dp_calle_pertenece.descripcion", "cat_sat_tipo_de_comprobante.descripcion","cat_sat_regimen_fiscal.factor",
            "com_sucursal.descripcion", "fc_csd.descripcion", "com_tipo_cambio.descripcion", "cat_sat_forma_pago.descripcion",
            "cat_sat_metodo_pago.descripcion", "cat_sat_moneda.descripcion", "cat_sat_uso_cfdi.descripcion");

        $datatables = new stdClass();
        $datatables->columns = $columns;
        $datatables->filtro = $filtro;

        return $datatables;
    }

    private function init_inputs(): array
    {
        $identificador = "fc_csd_id";
        $propiedades = array("label" => "CSD");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "dp_calle_pertenece_id";
        $propiedades = array("label" => "Calle pertenece");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "com_sucursal_id";
        $propiedades = array("label" => "Sucursal");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "com_tipo_cambio_id";
        $propiedades = array("label" => "Tipo de Cambio");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "cat_sat_tipo_de_comprobante_id";
        $propiedades = array("label" => "Tipo de Comprobante");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "cat_sat_regimen_fiscal_id";
        $propiedades = array("label" => "Regimen Fiscalr");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "cat_sat_forma_pago_id";
        $propiedades = array("label" => "Forma de pago");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "cat_sat_metodo_pago_id";
        $propiedades = array("label" => "Metodo de pago");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "cat_sat_moneda_id";
        $propiedades = array("label" => "Moneda");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "com_tipo_cambio_id";
        $propiedades = array("label" => "Tipo de cambio");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "cat_sat_uso_cfdi_id";
        $propiedades = array("label" => "Uso de cfdi");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        return $this->keys_selects;
    }

    private function init_modifica(): array|stdClass
    {
        $r_modifica =  parent::modifica(header: false);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al generar template',data:  $r_modifica);
        }
        $identificador = "com_sucursal_id";

        $csd = (new fc_csd($this->link))->get_csd($this->row_upd->fc_csd_id);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener csd',data:  $csd);
        }


        $identificador = "dp_calle_pertenece_id";
        $propiedades = array("id_selected" => $csd['dp_calle_pertenece_id']);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);
        
        $identificador = "com_sucursal_id";
        $propiedades = array("id_selected" => $this->row_upd->com_sucursal_id);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "cat_sat_tipo_de_comprobante_id";
        $propiedades = array("id_selected" => $this->row_upd->cat_sat_tipo_de_comprobante_id);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "cat_sat_regimen_fiscal_id";
        $propiedades = array("id_selected" => $this->row_upd->cat_sat_regimen_fiscal_id);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "cat_sat_forma_pago_id";
        $propiedades = array("id_selected" => $this->row_upd->com_sucursal_id);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "cat_sat_metodo_pago_id";
        $propiedades = array("id_selected" => $this->row_upd->com_sucursal_id);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "cat_sat_moneda_id";
        $propiedades = array("id_selected" => $this->row_upd->com_sucursal_id);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "cat_sat_uso_cfdi_id";
        $propiedades = array("id_selected" => $this->row_upd->com_sucursal_id);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $inputs = $this->genera_inputs(keys_selects:  $this->keys_selects);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al generar inputs',data:  $inputs);
        }

        $data = new stdClass();
        $data->template = $r_modifica;
        $data->inputs = $inputs;

        return $data;
    }

    public function modifica(bool $header, bool $ws = false): array|stdClass
    {
        $base = $this->init_modifica();
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al maquetar datos',data:  $base,
                header: $header,ws:$ws);
        }

        return $base->template;
    }
}
