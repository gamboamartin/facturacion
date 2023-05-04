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
use gamboamartin\cat_sat\models\cat_sat_factor;
use gamboamartin\cat_sat\models\cat_sat_tipo_factor;
use gamboamartin\cat_sat\models\cat_sat_tipo_impuesto;
use gamboamartin\comercial\models\com_producto;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\html\fc_retenido_nc_html;
use gamboamartin\facturacion\models\fc_retenido_nc;
use gamboamartin\system\links_menu;
use gamboamartin\system\system;

use gamboamartin\template\html;
use PDO;
use stdClass;

class controlador_fc_retenido_nc extends _base_system_conf {

    public array|stdClass $keys_selects = array();

    public function __construct(PDO $link, html $html = new \gamboamartin\template_1\html(),
                                stdClass $paths_conf = new stdClass()){
        $modelo = new fc_retenido_nc(link: $link);
        $html_ = new fc_retenido_nc_html(html: $html);
        parent::__construct(html_: $html_, link: $link,modelo:  $modelo, paths_conf: $paths_conf);

        $inputs = $this->init_inputs();
        if(errores::$error){
            $error = $this->errores->error(mensaje: 'Error al inicializar inputs',data:  $inputs);
            print_r($error);
            die('Error');
        }

        $this->parents_verifica[] = (new com_producto(link: $this->link));
        $this->parents_verifica[] = (new cat_sat_tipo_factor(link: $this->link));
        $this->parents_verifica[] = (new cat_sat_factor(link: $this->link));
        $this->parents_verifica[] = (new cat_sat_tipo_impuesto(link: $this->link));

        $this->verifica_parents_alta = true;

    }



    private function init_configuraciones(): controler
    {
        $this->seccion_titulo = 'Retenidos';
        $this->titulo_lista = 'Registro de Retenidos';

        return $this;
    }

    public function init_datatable(): stdClass
    {
        $columns["fc_retenido_nc_id"]["titulo"] = "Id";
        $columns["fc_retenido_nc_codigo"]["titulo"] = "Codigo";
        $columns["fc_partida_descripcion"]["titulo"] = "Partida";
        $columns["fc_retenido_nc_descripcion"]["titulo"] = "Descripción";
        $columns["cat_sat_tipo_factor_descripcion"]["titulo"] = "Tipo Factor";
        $columns["cat_sat_factor_factor"]["titulo"] = "Factor";
        $columns["cat_sat_tipo_impuesto_descripcion"]["titulo"] = "Tipo Impuesto";

        $filtro = array("fc_retenido_nc.id","fc_retenido_nc.codigo","fc_retenido_nc.descripcion","fc_partida.descripcion",
            "cat_sat_tipo_factor.descripcion","cat_sat_factor.factor","cat_sat_tipo_impuesto.descripcion");

        $datatables = new stdClass();
        $datatables->columns = $columns;
        $datatables->filtro = $filtro;

        return $datatables;
    }

    private function init_inputs(): array
    {
        $identificador = "fc_partida_id";
        $propiedades = array("label" => "Partida", "cols" => 12, "extra_params_keys" => array("fc_partida_codigo",
            "fc_partida_descripcion"));
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "cat_sat_tipo_factor_id";
        $propiedades = array("label" => "Tipo Factor");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "cat_sat_factor_id";
        $propiedades = array("label" => "Factor");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "cat_sat_tipo_impuesto_id";
        $propiedades = array("label" => "Tipo Impuesto", "cols" => 12);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "descripcion";
        $propiedades = array("place_holder" => "Descripción","cols" => 12);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        return $this->keys_selects;
    }

    private function init_modifica(): array|stdClass
    {
        $r_modifica =  parent::modifica(header: false);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al generar template',data:  $r_modifica);
        }

        $identificador = "fc_partida_id";
        $propiedades = array("id_selected" => $this->row_upd->fc_partida_id);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "cat_sat_tipo_factor_id";
        $propiedades = array("id_selected" => $this->row_upd->cat_sat_tipo_factor_id);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "cat_sat_factor_id";
        $propiedades = array("id_selected" => $this->row_upd->cat_sat_factor_id);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "cat_sat_tipo_impuesto_id";
        $propiedades = array("id_selected" => $this->row_upd->cat_sat_tipo_impuesto_id);
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
