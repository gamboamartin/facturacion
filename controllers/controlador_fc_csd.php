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
use gamboamartin\facturacion\models\fc_cer_csd;
use gamboamartin\facturacion\models\fc_csd;
use gamboamartin\facturacion\models\fc_key_csd;
use gamboamartin\organigrama\models\org_sucursal;
use gamboamartin\system\actions;
use gamboamartin\system\links_menu;
use gamboamartin\system\system;

use gamboamartin\template\html;
use html\fc_csd_html;
use PDO;
use stdClass;

class controlador_fc_csd extends system{

    public array|stdClass $keys_selects = array();
    public controlador_fc_key_csd $controlador_fc_key_csd;
    public controlador_fc_cer_csd $controlador_fc_cer_csd;

    public string $link_fc_key_csd = '';
    public string $link_fc_cer_csd = '';
    public string $link_fc_key_csd_alta_bd = '';
    public string $link_fc_cer_csd_alta_bd = '';

    public function __construct(PDO $link, html $html = new \gamboamartin\template_1\html(),
                                stdClass $paths_conf = new stdClass()){
        $modelo = new fc_csd(link: $link);
        $html_ = new fc_csd_html(html: $html);
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

        $this->parents_verifica[] = new org_sucursal(link: $this->link);
        $this->verifica_parents_alta = true;
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



    public function get_csd(bool $header, bool $ws = true): array|stdClass
    {
        $keys['org_sucursal'] = array('id','descripcion','codigo','codigo_bis');;

        $salida = $this->get_out(header: $header,keys: $keys, ws: $ws);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar salida',data:  $salida,header: $header,ws: $ws);
        }

        return $salida;
    }

    private function init_configuraciones(): controler
    {
        $this->seccion_titulo = 'Certificados de Sello Digital';
        $this->titulo_lista = 'Registro de Certificados de Sello Digital';

        return $this;
    }

    private function init_controladores(stdClass $paths_conf): controler
    {
        $this->controlador_fc_key_csd = new controlador_fc_key_csd(link:$this->link, paths_conf: $paths_conf);
        $this->controlador_fc_cer_csd = new controlador_fc_cer_csd(link:$this->link, paths_conf: $paths_conf);

        return $this;
    }

    public function init_datatable(): stdClass
    {
        $columns["fc_csd_id"]["titulo"] = "Id";
        $columns["fc_csd_codigo"]["titulo"] = "Código";
        $columns["fc_csd_descripcion"]["titulo"] = "CSD";
        $columns["fc_csd_serie"]["titulo"] = "Serie";
        $columns["org_sucursal_descripcion"]["titulo"] = "Sucursal";

        $filtro = array("fc_csd.id","fc_csd.codigo","fc_csd.descripcion","fc_csd.serie", "org_sucursal.descripcion");

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

        $link = $this->obj_link->get_link($this->seccion,"subir_key");
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener link subir_key_alta_bd',data:  $link);
        }
        $this->link_fc_key_csd = $link;

        $link = $this->obj_link->get_link($this->seccion,"subir_cer");
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener link subir_key_alta_bd',data:  $link);
        }
        $this->link_fc_cer_csd = $link;

        $link = $this->obj_link->get_link($this->seccion,"subir_key_alta_bd");
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener link subir_key_alta_bd',data:  $link);
        }
        $this->link_fc_key_csd_alta_bd = $link;

        $link = $this->obj_link->get_link($this->seccion,"subir_cer_alta_bd");
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener link subir_cer_alta_bd',data:  $link);
        }
        $this->link_fc_cer_csd_alta_bd = $link;

        return $link;
    }

    private function init_inputs(): array
    {
        $identificador = "org_sucursal_id";
        $propiedades = array("label" => "Sucursal","cols" => 8);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "serie";
        $propiedades = array("place_holder" => "Serie", "cols" => 4);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        return $this->keys_selects;
    }

    private function init_modifica(): array|stdClass
    {
        $r_modifica =  parent::modifica(header: false);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al generar template',data:  $r_modifica);
        }

        $identificador = "org_sucursal_id";
        $propiedades = array("id_selected" => $this->row_upd->org_sucursal_id);
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

    public function subir_cer(bool $header, bool $ws = false): array|stdClass
    {
        $datatables = $this->controlador_fc_cer_csd->init_datatable();
        if(errores::$error){
            $error = $this->errores->error(mensaje: 'Error al inicializar datatable',data: $datatables);
            print_r($error);
            die('Error');
        }

        $datatables->columns["modifica"]["titulo"] = "Acciones";
        $datatables->columns["modifica"]["type"] = "button";
        $datatables->columns["modifica"]["campos"] = array("elimina_bd");

        $table = $this->datatable_init(columns: $datatables->columns, filtro: $datatables->filtro,
            identificador: "#fc_cer_csd", data: array("fc_csd.id" => $this->registro_id));
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al generar datatable', data: $table, header: $header, ws: $ws);
        }

        $alta = $this->controlador_fc_cer_csd->alta(header: false);
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al generar template', data: $alta, header: $header, ws: $ws);
        }

        $identificador = "fc_csd_id";
        $propiedades = array("id_selected" => $this->registro_id, "disabled" => true,
            "filtro" => array('fc_csd.id' => $this->registro_id));
        $this->controlador_fc_cer_csd->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "documento";
        $propiedades = array("cols" => 12);
        $this->controlador_fc_cer_csd->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $this->inputs = $this->controlador_fc_cer_csd->genera_inputs(
            keys_selects:  $this->controlador_fc_cer_csd->keys_selects);
        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al generar inputs', data: $this->inputs);
            print_r($error);
            die('Error');
        }

        return $this->inputs;
    }

    public function subir_cer_alta_bd(bool $header, bool $ws = false): array|stdClass
    {
        $this->link->beginTransaction();

        $siguiente_view = (new actions())->init_alta_bd(siguiente_view:"subir_cer");
        if(errores::$error){
            $this->link->rollBack();
            return $this->retorno_error(mensaje: 'Error al obtener siguiente view', data: $siguiente_view,
                header:  $header, ws: $ws);
        }

        if(isset($_POST['btn_action_next'])){
            unset($_POST['btn_action_next']);
        }

        $csd = (new fc_csd($this->link))->get_csd(fc_csd_id: $this->registro_id);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener csd', data: $csd, header: $header, ws: $ws);
        }

        $nombre_archivo = explode(".",$_FILES['documento']['name'])[0];

        $registro = $_POST;
        $registro['fc_csd_id'] = $this->registro_id;
        $registro['codigo'] = $csd['fc_csd_id'].$nombre_archivo;

        $r_alta_cer_csd_bd = (new fc_cer_csd($this->link))->alta_registro(registro:$registro);
        if(errores::$error){
            $this->link->rollBack();
            return $this->retorno_error(mensaje: 'Error al dar de alta cer csd',data:  $r_alta_cer_csd_bd,
                header: $header,ws:$ws);
        }

        $this->link->commit();

        if($header){

            $retorno = (new actions())->retorno_alta_bd(link: $this->link, registro_id: $this->registro_id,
                seccion: $this->tabla, siguiente_view: "$siguiente_view");
            if(errores::$error){
                return $this->retorno_error(mensaje: 'Error al dar de alta registro', data: $r_alta_cer_csd_bd,
                    header:  true, ws: $ws);
            }
            header('Location:'.$retorno);
            exit;
        }
        if($ws){
            header('Content-Type: application/json');
            echo json_encode($r_alta_cer_csd_bd, JSON_THROW_ON_ERROR);
            exit;
        }

        return $r_alta_cer_csd_bd;
    }

    public function subir_key(bool $header, bool $ws = false): array|stdClass
    {
        $datatables = $this->controlador_fc_key_csd->init_datatable();
        if(errores::$error){
            $error = $this->errores->error(mensaje: 'Error al inicializar datatable',data: $datatables);
            print_r($error);
            die('Error');
        }

        $datatables->columns["modifica"]["titulo"] = "Acciones";
        $datatables->columns["modifica"]["type"] = "button";
        $datatables->columns["modifica"]["campos"] = array("elimina_bd");

        $table = $this->datatable_init(columns: $datatables->columns, filtro: $datatables->filtro,
            identificador: "#fc_key_csd", data: array("fc_csd.id" => $this->registro_id));
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al generar datatable', data: $table, header: $header, ws: $ws);
        }

        $alta = $this->controlador_fc_key_csd->alta(header: false);
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al generar template', data: $alta, header: $header, ws: $ws);
        }

        $identificador = "fc_csd_id";
        $propiedades = array("id_selected" => $this->registro_id, "disabled" => true,
            "filtro" => array('fc_csd.id' => $this->registro_id));
        $this->controlador_fc_key_csd->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "documento";
        $propiedades = array("cols" => 12);
        $this->controlador_fc_key_csd->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $this->inputs = $this->controlador_fc_key_csd->genera_inputs(
            keys_selects:  $this->controlador_fc_key_csd->keys_selects);
        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al generar inputs', data: $this->inputs);
            print_r($error);
            die('Error');
        }

        return $this->inputs;
    }

    public function subir_key_alta_bd(bool $header, bool $ws = false): array|stdClass
    {
        $this->link->beginTransaction();

        $siguiente_view = (new actions())->init_alta_bd(siguiente_view:"subir_key");
        if(errores::$error){
            $this->link->rollBack();
            return $this->retorno_error(mensaje: 'Error al obtener siguiente view', data: $siguiente_view,
                header:  $header, ws: $ws);
        }

        if(isset($_POST['btn_action_next'])){
            unset($_POST['btn_action_next']);
        }

        $csd = (new fc_csd($this->link))->get_csd(fc_csd_id: $this->registro_id);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener csd', data: $csd, header: $header, ws: $ws);
        }

        $nombre_archivo = explode(".",$_FILES['documento']['name'])[0];

        $registro = $_POST;
        $registro['fc_csd_id'] = $this->registro_id;
        $registro['codigo'] = $csd['fc_csd_id'].$nombre_archivo;

        $r_alta_key_csd_bd = (new fc_key_csd($this->link))->alta_registro(registro:$registro);
        if(errores::$error){
            $this->link->rollBack();
            return $this->retorno_error(mensaje: 'Error al dar de alta key csd',data:  $r_alta_key_csd_bd,
                header: $header,ws:$ws);
        }

        $this->link->commit();

        if($header){

            $retorno = (new actions())->retorno_alta_bd(link: $this->link, registro_id: $this->registro_id,
                seccion: $this->tabla, siguiente_view: "$siguiente_view");
            if(errores::$error){
                return $this->retorno_error(mensaje: 'Error al dar de alta registro', data: $r_alta_key_csd_bd,
                    header:  true, ws: $ws);
            }
            header('Location:'.$retorno);
            exit;
        }
        if($ws){
            header('Content-Type: application/json');
            echo json_encode($r_alta_key_csd_bd, JSON_THROW_ON_ERROR);
            exit;
        }

        return $r_alta_key_csd_bd;
    }

}
