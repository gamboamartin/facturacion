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
use gamboamartin\facturacion\models\fc_partida;
use gamboamartin\facturacion\models\fc_traslado;
use gamboamartin\system\actions;
use gamboamartin\system\links_menu;
use gamboamartin\system\system;

use gamboamartin\template_1\html;
use html\fc_partida_html;
use PDO;
use stdClass;

class controlador_fc_partida extends system{

    public array $keys_selects = array();
    public controlador_fc_traslado $controlador_fc_traslado;
    public string $link_fc_traslado_alta_bd = '';

    public function __construct(PDO $link, html $html = new html(), stdClass $paths_conf = new stdClass()){
        $modelo = new fc_partida(link: $link);
        $html_ = new fc_partida_html(html: $html);
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

        $controladores = $this->init_controladores(paths_conf: $paths_conf);
        if(errores::$error){
            $error = $this->errores->error(mensaje: 'Error al inicializar controladores',data:  $controladores);
            print_r($error);
            die('Error');
        }

        $links = $this->init_links();
        if(errores::$error){
            $error = $this->errores->error(mensaje: 'Error al inicializar links',data:  $links);
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

        $this->row_upd->cantidad = 0;
        $this->row_upd->valor_unitario = 0;
        $this->row_upd->descuento = 0;

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
        $this->seccion_titulo = 'Partidas';
        $this->titulo_lista = 'Registro de Partidas';

        return $this;
    }

    private function init_controladores(stdClass $paths_conf): controler
    {
        $this->controlador_fc_traslado = new controlador_fc_traslado(link:$this->link, paths_conf: $paths_conf);

        return $this;
    }

    public function init_datatable(): stdClass
    {
        $columns["fc_partida_id"]["titulo"] = "Id";
        $columns["fc_partida_codigo"]["titulo"] = "Código";
        $columns["fc_factura_descripcion"]["titulo"] = "Factura";
        $columns["com_producto_descripcion"]["titulo"] = "Producto";
        $columns["fc_partida_cantidad"]["titulo"] = "Cantidad";
        $columns["fc_partida_valor_unitario"]["titulo"] = "Valor Unitario";
        $columns["fc_partida_descuento"]["titulo"] = "Descuento";

        $filtro = array("fc_partida.id","fc_partida.codigo","fc_factura.descripcion","com_producto.descripcion",
            "fc_partida.cantidad","fc_partida.valor_unitario","fc_partida.descuento");

        $datatables = new stdClass();
        $datatables->columns = $columns;
        $datatables->filtro = $filtro;

        return $datatables;
    }

    private function init_links(): array|string
    {
        $this->obj_link->genera_links($this);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al generar links para partida',data:  $this->obj_link);
        }

        $link = $this->obj_link->get_link(seccion: $this->seccion, accion: "nuevo_traslado_bd");
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener link: nuevo_traslado_bd',data:  $link);
        }
        $this->link_fc_traslado_alta_bd = $link;

        return $link;
    }

    private function init_inputs(): array
    {
        $identificador = "com_producto_id";
        $propiedades = array("label" => "Producto", "extra_params_keys" => array("com_producto_codigo",
            "com_producto_descripcion"));
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "fc_factura_id";
        $propiedades = array("label" => "Factura");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "codigo";
        $propiedades = array("place_holder" => "Código","cols" => 4);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "descripcion";
        $propiedades = array("place_holder" => "Partida","cols" => 8);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "cantidad";
        $propiedades = array("place_holder" => "Cantidad" ,"cols" => 4);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "valor_unitario";
        $propiedades = array("place_holder" => "Valor Unitario" ,"cols" => 4);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "descuento";
        $propiedades = array("place_holder" => "Descuento" ,"cols" => 4);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        return $this->keys_selects;
    }

    private function init_modifica(): array|stdClass
    {
        $r_modifica =  parent::modifica(header: false);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al generar template',data:  $r_modifica);
        }

        $identificador = "fc_factura_id";
        $propiedades = array("id_selected" => $this->row_upd->fc_factura_id);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "com_producto_id";
        $propiedades = array("id_selected" => $this->row_upd->com_producto_id);
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

    public function nuevo_traslado(bool $header, bool $ws = false): array|stdClass
    {
        $datatables = $this->controlador_fc_traslado->init_datatable();
        if(errores::$error){
            $error = $this->errores->error(mensaje: 'Error al inicializar datatable',data: $datatables);
            print_r($error);
            die('Error');
        }

        $datatables->columns["modifica"]["titulo"] = "Acciones";
        $datatables->columns["modifica"]["type"] = "button";
        $datatables->columns["modifica"]["campos"] = array("elimina_bd");

        $table = $this->datatable_init(columns: $datatables->columns, filtro: $datatables->filtro,
            identificador: "#fc_traslado", data: array("fc_partida.id" => $this->registro_id));
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al generar datatable', data: $table, header: $header, ws: $ws);
        }

        $alta = $this->controlador_fc_traslado->alta(header: false);
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al generar template', data: $alta, header: $header, ws: $ws);
        }

        $partida = (new fc_partida($this->link))->get_partida(fc_partida_id: $this->registro_id);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener partida', data: $alta, header: $header, ws: $ws);
        }

        $this->controlador_fc_traslado->row_upd->codigo = $partida["fc_partida_codigo"];
        $this->controlador_fc_traslado->row_upd->descripcion = $partida["fc_partida_descripcion"];

        $identificador = "fc_partida_id";
        $propiedades = array("id_selected" => $this->registro_id, "disabled" => true,
            "filtro" => array('fc_partida.id' => $this->registro_id));
        $this->controlador_fc_traslado->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

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
