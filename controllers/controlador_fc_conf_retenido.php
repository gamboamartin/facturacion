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
use gamboamartin\comercial\models\com_producto;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\fc_conf_retenido;
use gamboamartin\facturacion\models\fc_conf_traslado;
use gamboamartin\system\links_menu;
use gamboamartin\system\system;

use gamboamartin\template\html;
use html\fc_conf_retenido_html;
use html\fc_conf_traslado_html;
use PDO;
use stdClass;

class controlador_fc_conf_retenido extends system{

    public array $keys_selects = array();

    public function __construct(PDO $link, html $html = new \gamboamartin\template_1\html(),
                                stdClass $paths_conf = new stdClass()){
        $modelo = new fc_conf_retenido(link: $link);
        $html_ = new fc_conf_retenido_html(html: $html);
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

    public function asignar_propiedad(string $identificador, mixed $propiedades)
    {
        if (!array_key_exists($identificador,$this->keys_selects)){
            $this->keys_selects[$identificador] = new stdClass();
        }

        foreach ($propiedades as $key => $value){
            $this->keys_selects[$identificador]->$key = $value;
        }
    }

    private function init_configuraciones(): controler
    {
        $this->seccion_titulo = 'Configuraci??n de Retenidos';
        $this->titulo_lista = 'Registro de Configuraciones';

        return $this;
    }

    public function init_datatable(): stdClass
    {
        $columns["fc_conf_retenido_id"]["titulo"] = "Id";
        $columns["fc_conf_retenido_codigo"]["titulo"] = "C??digo";
        $columns["com_producto_descripcion"]["titulo"] = "Producto";
        $columns["cat_sat_tipo_factor_descripcion"]["titulo"] = "Tipo Factor";
        $columns["cat_sat_factor_factor"]["titulo"] = "Factor";
        $columns["cat_sat_tipo_impuesto_descripcion"]["titulo"] = "Tipo Impuesto";
        $columns["fc_conf_retenido_descripcion"]["titulo"] = "Descripci??n";

        $filtro = array("fc_conf_retenido.id","fc_conf_retenido.codigo","fc_conf_retenido.descripcion",
            "com_producto.descripcion", "cat_sat_tipo_factor.descripcion","cat_sat_factor.factor",
            "cat_sat_tipo_impuesto.descripcion");

        $datatables = new stdClass();
        $datatables->columns = $columns;
        $datatables->filtro = $filtro;

        return $datatables;
    }

    private function init_inputs(): array
    {
        $identificador = "com_tipo_producto_id";
        $propiedades = array("label" => "Tipo Producto");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "com_producto_id";
        $propiedades = array("label" => "Producto", "con_registros" => false,"cols" => 12);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "cat_sat_tipo_factor_id";
        $propiedades = array("label" => "Tipo Factor");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "cat_sat_factor_id";
        $propiedades = array("label" => "Factor");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "cat_sat_tipo_impuesto_id";
        $propiedades = array("label" => "Tipo Impuesto");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        return $this->keys_selects;
    }

    private function init_modifica(): array|stdClass
    {
        $r_modifica =  parent::modifica(header: false);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al generar template',data:  $r_modifica);
        }

        $producto = (new com_producto($this->link))->get_producto($this->row_upd->com_producto_id);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener producto',data:  $producto);
        }

        $identificador = "com_tipo_producto_id";
        $propiedades = array("id_selected" => $producto['com_tipo_producto_id']);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "com_producto_id";
        $propiedades = array("id_selected" => $this->row_upd->com_producto_id, "con_registros" => true,
            "filtro" => array('com_tipo_producto.id' => $producto['com_tipo_producto_id']));
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
