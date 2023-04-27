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
use gamboamartin\errores\errores;

use gamboamartin\template\html;
use PDO;
use stdClass;

class controlador_fc_traslado_cp extends _base_system_conf {

    public array|stdClass $keys_selects = array();

    public function __construct(PDO $link, html $html = new \gamboamartin\template_1\html(),
                                stdClass $paths_conf = new stdClass()){
        $modelo = new fc_traslado_cp(link: $link);
        $html_ = new fc_traslado_cp_html(html: $html);
        parent::__construct(html_: $html_, link: $link,modelo:  $modelo, paths_conf: $paths_conf);

        $inputs = $this->init_inputs();
        if(errores::$error){
            $error = $this->errores->error(mensaje: 'Error al inicializar inputs',data:  $inputs);
            print_r($error);
            die('Error');
        }
    }




    private function init_configuraciones(): controler
    {
        $this->seccion_titulo = 'Traslados';
        $this->titulo_lista = 'Registro de Traslados';

        return $this;
    }

    public function init_datatable(): stdClass
    {
        $columns["fc_traslado_cp_id"]["titulo"] = "Id";
        $columns["fc_traslado_cp_codigo"]["titulo"] = "Código";
        $columns["fc_partida_cp_descripcion"]["titulo"] = "Partida";
        $columns["fc_traslado_cp_descripcion"]["titulo"] = "Descripción";
        $columns["cat_sat_tipo_factor_descripcion"]["titulo"] = "Tipo Factor";
        $columns["cat_sat_factor_factor"]["titulo"] = "Factor";
        $columns["cat_sat_tipo_impuesto_descripcion"]["titulo"] = "Tipo Impuesto";

        $filtro = array("fc_traslado_cp.id","fc_traslado_cp.codigo","fc_traslado_cp.descripcion","fc_partida_cp.descripcion",
            "cat_sat_tipo_factor.descripcion","cat_sat_factor.factor","cat_sat_tipo_impuesto.descripcion");

        $datatables = new stdClass();
        $datatables->columns = $columns;
        $datatables->filtro = $filtro;

        return $datatables;
    }

    private function init_inputs(): array
    {
        $identificador = "fc_partida_cp_id";
        $propiedades = array("label" => "Partida", "cols" => 12, "extra_params_keys" => array("fc_partida_cp_codigo",
            "fc_partida_cp_descripcion"));
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

        $identificador = "fc_partida_cp_id";
        $propiedades = array("id_selected" => $this->row_upd->fc_partida_cp_id);
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
