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
use gamboamartin\facturacion\models\fc_partida;
use gamboamartin\facturacion\models\fc_traslado;
use gamboamartin\system\actions;
use gamboamartin\system\links_menu;
use gamboamartin\system\system;

use gamboamartin\template\html;
use html\fc_partida_html;
use PDO;
use stdClass;

class controlador_fc_partida extends system{

    public array $keys_selects = array();
    public controlador_fc_traslado $controlador_fc_traslado;

    public string $link_fc_traslado_alta_bd = '';

    public function __construct(PDO $link, html $html = new \gamboamartin\template_1\html(),
                                stdClass $paths_conf = new stdClass()){
        $modelo = new fc_partida(link: $link);
        $html_ = new fc_partida_html(html: $html);
        $obj_link = new links_menu(link: $link, registro_id:  $this->registro_id);

        $columns["fc_partida_id"]["titulo"] = "Id";
        $columns["fc_partida_codigo"]["titulo"] = "Codigo";
        $columns["fc_partida_descripcion"]["titulo"] = "Factura";
        $columns["com_producto_descripcion"]["titulo"] = "Producto";
        $columns["fc_partida_cantidad"]["titulo"] = "Cantidad";
        $columns["fc_partida_valor_unitario"]["titulo"] = "Valor Unitario";
        $columns["fc_partida_descuento"]["titulo"] = "Descuento";

        $filtro = array("fc_partida.id","fc_partida.codigo","fc_partida.descripcion","com_producto.descripcion");

        $datatables = new stdClass();
        $datatables->columns = $columns;
        $datatables->filtro = $filtro;

        parent::__construct(html:$html_, link: $link,modelo:  $modelo, obj_link: $obj_link, datatables: $datatables,
            paths_conf: $paths_conf);

        $this->controlador_fc_traslado = new controlador_fc_traslado(link:$this->link, paths_conf: $paths_conf);

        $this->titulo_lista = 'Partidas';

        $links = $this->inicializa_links();
        if(errores::$error){
            $error = $this->errores->error(mensaje: 'Error al inicializar links',data:  $links);
            print_r($error);
            die('Error');
        }

        $propiedades = $this->inicializa_priedades();
        if(errores::$error){
            $error = $this->errores->error(mensaje: 'Error al inicializar propiedades',data:  $propiedades);
            print_r($error);
            die('Error');
        }
    }

    public function alta(bool $header, bool $ws = false): array|string
    {
        $r_alta =  parent::alta(header: false, ws: false);
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

    private function base(): array|stdClass
    {
        $r_modifica =  parent::modifica(header: false,aplica_form:  false);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al generar template',data:  $r_modifica);
        }

        $this->asignar_propiedad(identificador:'fc_factura_id',
            propiedades: ["id_selected"=>$this->row_upd->fc_factura_id]);
        $this->asignar_propiedad(identificador:'com_producto_id',
            propiedades: ["id_selected"=>$this->row_upd->com_producto_id]);

        $inputs = $this->genera_inputs(keys_selects:  $this->keys_selects);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al inicializar inputs',data:  $inputs);
        }

        $data = new stdClass();
        $data->template = $r_modifica;
        $data->inputs = $inputs;

        return $data;
    }

    private function inicializa_links(): array|string
    {
        $this->obj_link->genera_links($this);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al generar links para partida',data:  $this->obj_link);
        }

        $link = $this->obj_link->get_link($this->seccion,"nuevo_traslado_bd");
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener link partida alta',data:  $link);
        }
        $this->link_fc_traslado_alta_bd = $link;

        return $link;
    }

    private function inicializa_priedades(): array
    {
        $identificador = "com_producto_id";
        $propiedades = array("label" => "Producto");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "fc_factura_id";
        $propiedades = array("label" => "Factura","cols" => 12);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "codigo";
        $propiedades = array("place_holder" => "Codigo");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "cantidad";
        $propiedades = array("place_holder" => "Cantidad");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "valor_unitario";
        $propiedades = array("place_holder" => "Valor Unitario");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "descuento";
        $propiedades = array("place_holder" => "Descuento");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        return $this->keys_selects;
    }

    public function nuevo_traslado(bool $header, bool $ws = false): array|stdClass
    {
        $columns["fc_traslado_id"]["titulo"] = "Id";
        $columns["fc_traslado_codigo"]["titulo"] = "Codigo";
        $columns["fc_traslado_descripcion"]["titulo"] = "Descripcion";
        $columns["cat_sat_tipo_factor_descripcion"]["titulo"] = "Tipo Factor";
        $columns["cat_sat_factor_factor"]["titulo"] = "Factor";
        $columns["cat_sat_tipo_impuesto_descripcion"]["titulo"] = "Tipo Impuesto";
        $columns["modifica"]["titulo"] = "Acciones";
        $columns["modifica"]["type"] = "button";
        $columns["modifica"]["campos"] = array("elimina_bd");

        $colums_rs =$this->datatable_init(columns: $columns,identificador: "#fc_traslado",
            data: array("fc_partida.id" => $this->registro_id));
        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al inicializar links', data: $colums_rs);
            print_r($error);
            die('Error');
        }

        $alta = $this->controlador_fc_traslado->alta(header: false);
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al generar template', data: $alta, header: $header, ws: $ws);
        }

        $this->controlador_fc_traslado->asignar_propiedad(identificador: 'fc_partida_id',
            propiedades: ["id_selected" => $this->registro_id, "disabled" => true, "cols" => 12,
                "filtro" => array('fc_partida.id' => $this->registro_id)]);

        $this->controlador_fc_traslado->asignar_propiedad(identificador: 'cat_sat_factor_id',
            propiedades: ["cols" => 12]);

        $this->inputs = $this->controlador_fc_traslado->genera_inputs(
            keys_selects:  $this->controlador_fc_traslado->keys_selects);
        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al generar inputs', data: $this->inputs);
            print_r($error);
            die('Error');
        }

        return $this->inputs;
    }

    public function nuevo_traslado_bd(bool $header, bool $ws = false): array|stdClass
    {
        $this->link->beginTransaction();

        $siguiente_view = (new actions())->init_alta_bd();
        if(errores::$error){
            $this->link->rollBack();
            return $this->retorno_error(mensaje: 'Error al obtener siguiente view', data: $siguiente_view,
                header:  $header, ws: $ws);
        }

        if(isset($_POST['btn_action_next'])){
            unset($_POST['btn_action_next']);
        }

        $registro = $_POST;
        $registro['fc_partida_id'] = $this->registro_id;

        $alta = (new fc_traslado($this->link))->alta_registro(registro:$registro);
        if(errores::$error){
            $this->link->rollBack();
            return $this->retorno_error(mensaje: 'Error al dar de alta traslado',data:  $alta,
                header: $header,ws:$ws);
        }

        $this->link->commit();

        if($header){

            $retorno = (new actions())->retorno_alta_bd(link: $this->link, registro_id: $this->registro_id,
                seccion: $this->tabla, siguiente_view: $siguiente_view);
            if(errores::$error){
                return $this->retorno_error(mensaje: 'Error al dar de alta registro', data: $retorno,
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

    public function modifica(bool $header, bool $ws = false, string $breadcrumbs = '', bool $aplica_form = true,
                             bool $muestra_btn = true): array|stdClass
    {
        $base = $this->base();
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al maquetar datos',data:  $base,
                header: $header,ws:$ws);
        }

        return $base->template;
    }
}
