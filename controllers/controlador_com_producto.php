<?php
namespace gamboamartin\facturacion\controllers;

use base\controller\controler;

use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\com_producto;
use gamboamartin\facturacion\models\fc_conf_retenido;
use gamboamartin\facturacion\models\fc_conf_traslado;
use gamboamartin\system\actions;
use gamboamartin\template\html;
use PDO;
use stdClass;

class controlador_com_producto extends  \gamboamartin\comercial\controllers\controlador_com_producto {

    public controlador_fc_conf_traslado $controlador_fc_conf_traslado;
    public controlador_fc_conf_retenido $controlador_fc_conf_retenido;
    public string $link_fc_conf_traslado_alta_bd = '';
    public string $link_fc_conf_retenido_alta_bd = '';

    public string $link_com_producto = '';

    public function __construct(PDO $link, html $html = new \gamboamartin\template_1\html(),
                                stdClass $paths_conf = new stdClass())
    {
        parent::__construct(link: $link,html:  $html,paths_conf:  $paths_conf);

        $this->modelo = new com_producto(link: $this->link);


        $links = $this->init_links();
        if(errores::$error){
            $error = $this->errores->error(mensaje: 'Error al inicializar links',data:  $links);
            print_r($error);
            die('Error');
        }


        $this->childrens_data['fc_conf_retenido']['title'] = 'Confs Retenciones';
        $this->childrens_data['fc_conf_traslado']['title'] = 'Confs Traslados';

        $this->lista_get_data = true;
    }

    /**
     * Integra los controladores de configuracion de impuestos
     * @param stdClass $paths_conf Rutas de configuracion
     * @return controler
     */
    private function init_controladores(stdClass $paths_conf): controler
    {
        $this->controlador_fc_conf_traslado= new controlador_fc_conf_traslado(link:$this->link, paths_conf: $paths_conf);
        $this->controlador_fc_conf_retenido= new controlador_fc_conf_retenido(link:$this->link, paths_conf: $paths_conf);

        return $this;
    }

    public function init_links(): array|string
    {
        $this->obj_link->genera_links(controler: $this);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al generar links para productos',data:  $this->obj_link);
        }

        $link = $this->obj_link->get_link(seccion: $this->seccion,accion: "productos");
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener link productos',data:  $link);
        }

        $this->link_com_producto = $link;

        $link = $this->obj_link->get_link(seccion: $this->seccion,accion: "nueva_conf_traslado_alta_bd");
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener link nueva_conf_traslado_alta_bd',data:  $link);
        }
        $this->link_fc_conf_traslado_alta_bd = $link;

        $link = $this->obj_link->get_link(seccion: $this->seccion,accion: "nueva_conf_retenido_alta_bd");
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener link nueva_conf_retenido_alta_bd',data:  $link);
        }
        $this->link_fc_conf_retenido_alta_bd = $link;

        return $link;
    }

    public function nueva_conf_traslado(bool $header, bool $ws = false): array|stdClass
    {

        $controladores = $this->init_controladores(paths_conf: $this->paths_conf);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al inicializar controladores', data: $controladores,
                header: $header, ws: $ws);
        }

        $datatables = $this->controlador_fc_conf_traslado->init_datatable();
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al inicializar datatable', data: $datatables, header: $header, ws: $ws);
        }

        $datatables->columns["modifica"]["titulo"] = "Acciones";
        $datatables->columns["modifica"]["type"] = "button";
        $datatables->columns["modifica"]["campos"] = array("elimina_bd");

        $table = $this->datatable_init(columns: $datatables->columns, filtro: $datatables->filtro,
            identificador: "#fc_conf_traslado", data: array("com_producto.id" => $this->registro_id));
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al generar datatable', data: $table, header: $header, ws: $ws);
        }

        $producto = (new com_producto($this->link))->get_producto(com_producto_id: $this->registro_id);
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al obtener producto', data: $table, header: $header, ws: $ws);
        }

        $identificador = "com_tipo_producto_id";
        $propiedades = array("id_selected" => $producto['com_tipo_producto_id'], "disabled" => true);
        $this->controlador_fc_conf_traslado->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "com_producto_id";
        $propiedades = array("id_selected" => $this->registro_id, "con_registros" => true, "disabled" => true,
            "filtro" => array('com_tipo_producto.id' => $producto['com_tipo_producto_id']));
        $this->controlador_fc_conf_traslado->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $alta = $this->controlador_fc_conf_traslado->alta(header: false);
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al generar template', data: $alta, header: $header, ws: $ws);
        }

        $this->inputs = $this->controlador_fc_conf_traslado->genera_inputs(
            keys_selects:  $this->controlador_fc_conf_traslado->keys_selects);
        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al generar inputs', data: $this->inputs);
            print_r($error);
            die('Error');
        }

        return $this->inputs;
    }

    public function nueva_conf_retenido(bool $header, bool $ws = false): array|stdClass
    {

        $controladores = $this->init_controladores(paths_conf: $this->paths_conf);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al inicializar controladores', data: $controladores,
                header: $header, ws: $ws);
        }

        $datatables = $this->controlador_fc_conf_retenido->init_datatable();
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al inicializar datatable', data: $datatables, header: $header, ws: $ws);
        }

        $datatables->columns["modifica"]["titulo"] = "Acciones";
        $datatables->columns["modifica"]["type"] = "button";
        $datatables->columns["modifica"]["campos"] = array("elimina_bd");

        $table = $this->datatable_init(columns: $datatables->columns, filtro: $datatables->filtro,
            identificador: "#fc_conf_retenido", data: array("com_producto.id" => $this->registro_id));
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al generar datatable', data: $table, header: $header, ws: $ws);
        }

        $producto = (new com_producto($this->link))->get_producto(com_producto_id: $this->registro_id);
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al obtener producto', data: $table, header: $header, ws: $ws);
        }

        $identificador = "com_tipo_producto_id";
        $propiedades = array("id_selected" => $producto['com_tipo_producto_id'], "disabled" => true);
        $this->controlador_fc_conf_retenido->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "com_producto_id";
        $propiedades = array("id_selected" => $this->registro_id, "con_registros" => true, "disabled" => true,
            "filtro" => array('com_tipo_producto.id' => $producto['com_tipo_producto_id']));
        $this->controlador_fc_conf_retenido->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $alta = $this->controlador_fc_conf_retenido->alta(header: false);
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al generar template', data: $alta, header: $header, ws: $ws);
        }

        $this->inputs = $this->controlador_fc_conf_retenido->genera_inputs(
            keys_selects:  $this->controlador_fc_conf_retenido->keys_selects);
        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al generar inputs', data: $this->inputs);
            print_r($error);
            die('Error');
        }

        return $this->inputs;
    }

    public function nueva_conf_traslado_alta_bd(bool $header, bool $ws = false){

        $this->link->beginTransaction();

        $siguiente_view = (new actions())->init_alta_bd(siguiente_view: "nueva_conf_traslado");
        if(errores::$error){
            $this->link->rollBack();
            return $this->retorno_error(mensaje: 'Error al obtener siguiente view', data: $siguiente_view,
                header:  $header, ws: $ws);
        }

        if(isset($_POST['guarda'])){
            unset($_POST['guarda']);
        }
        if(isset($_POST['btn_action_next'])){
            unset($_POST['btn_action_next']);
        }

        $registro = $_POST;
        $registro['com_producto_id'] = $this->registro_id;

        $alta_conf_traslado = (new fc_conf_traslado($this->link))->alta_registro(registro:$registro);
        if(errores::$error){
            $this->link->rollBack();
            return $this->retorno_error(mensaje: 'Error al dar de alta conf_traslado',data:  $alta_conf_traslado,
                header: $header,ws:$ws);
        }

        $this->link->commit();

        if($header){

            $retorno = (new actions())->retorno_alta_bd(link: $this->link, registro_id: $this->registro_id,
                seccion: $this->tabla, siguiente_view: "nueva_conf_traslado");
            if(errores::$error){
                return $this->retorno_error(mensaje: 'Error al dar de alta registro', data: $alta_conf_traslado,
                    header:  true, ws: $ws);
            }
            header('Location:'.$retorno);
            exit;
        }
        if($ws){
            header('Content-Type: application/json');
            echo json_encode($alta_conf_traslado, JSON_THROW_ON_ERROR);
            exit;
        }

        return $alta_conf_traslado;

    }

    public function nueva_conf_retenido_alta_bd(bool $header, bool $ws = false){

        $this->link->beginTransaction();

        $siguiente_view = (new actions())->init_alta_bd(siguiente_view: "nueva_conf_retenido");
        if(errores::$error){
            $this->link->rollBack();
            return $this->retorno_error(mensaje: 'Error al obtener siguiente view', data: $siguiente_view,
                header:  $header, ws: $ws);
        }

        if(isset($_POST['guarda'])){
            unset($_POST['guarda']);
        }
        if(isset($_POST['btn_action_next'])){
            unset($_POST['btn_action_next']);
        }

        $registro = $_POST;
        $registro['com_producto_id'] = $this->registro_id;

        $alta_conf_retenido = (new fc_conf_retenido($this->link))->alta_registro(registro:$registro);
        if(errores::$error){
            $this->link->rollBack();
            return $this->retorno_error(mensaje: 'Error al dar de alta conf_retenido',data:  $alta_conf_retenido,
                header: $header,ws:$ws);
        }

        $this->link->commit();

        if($header){

            $retorno = (new actions())->retorno_alta_bd(link: $this->link, registro_id: $this->registro_id,
                seccion: $this->tabla, siguiente_view: "nueva_conf_retenido");
            if(errores::$error){
                return $this->retorno_error(mensaje: 'Error al dar de alta registro', data: $alta_conf_retenido,
                    header:  true, ws: $ws);
            }
            header('Location:'.$retorno);
            exit;
        }
        if($ws){
            header('Content-Type: application/json');
            echo json_encode($alta_conf_retenido, JSON_THROW_ON_ERROR);
            exit;
        }

        return $alta_conf_retenido;

    }

}
