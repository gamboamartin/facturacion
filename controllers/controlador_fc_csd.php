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
use gamboamartin\facturacion\models\fc_csd;
use gamboamartin\facturacion\models\fc_key_csd;
use gamboamartin\system\actions;
use gamboamartin\system\links_menu;
use gamboamartin\system\system;

use gamboamartin\template\html;
use html\fc_csd_html;
use PDO;
use stdClass;

class controlador_fc_csd extends system{

    public array $keys_selects = array();
    public controlador_fc_key_csd $controlador_fc_key_csd;
    public controlador_fc_cer_csd $controlador_fc_cer_csd;

    public string $link_fc_key_csd_alta_bd = '';
    public string $link_fc_cer_csd_alta_bd = '';

    public function __construct(PDO $link, html $html = new \gamboamartin\template_1\html(),
                                stdClass $paths_conf = new stdClass()){
        $modelo = new fc_csd(link: $link);
        $html_ = new fc_csd_html(html: $html);
        $obj_link = new links_menu(link: $link, registro_id:  $this->registro_id);

        $columns["fc_csd_id"]["titulo"] = "Id";
        $columns["fc_csd_codigo"]["titulo"] = "Codigo";
        $columns["fc_csd_descripcion"]["titulo"] = "Descripcion";
        $columns["fc_csd_serie"]["titulo"] = "Serie";
        $columns["org_sucursal_descripcion"]["titulo"] = "Sucursal";

        $filtro = array("fc_csd.id","fc_csd.codigo","fc_csd.descripcion","org_sucursal.descripcion");

        $datatables = new stdClass();
        $datatables->columns = $columns;
        $datatables->filtro = $filtro;

        parent::__construct(html:$html_, link: $link,modelo:  $modelo, obj_link: $obj_link, datatables: $datatables,
            paths_conf: $paths_conf);

        $this->titulo_lista = 'Empresas';

        $this->controlador_fc_key_csd = new controlador_fc_key_csd(link:$this->link, paths_conf: $paths_conf);
        $this->controlador_fc_cer_csd = new controlador_fc_cer_csd(link:$this->link, paths_conf: $paths_conf);

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

    private function base(): array|stdClass
    {
        $r_modifica =  parent::modifica(header: false,aplica_form:  false);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al generar template',data:  $r_modifica);
        }

        $this->asignar_propiedad(identificador:'org_sucursal_id',
            propiedades: ["id_selected"=>$this->row_upd->org_sucursal_id]);

        $inputs = $this->genera_inputs(keys_selects:  $this->keys_selects);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al inicializar inputs',data:  $inputs);
        }

        $data = new stdClass();
        $data->template = $r_modifica;
        $data->inputs = $inputs;

        return $data;
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

    private function inicializa_links(): array|string
    {
        $this->obj_link->genera_links($this);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al generar links para factura',data:  $this->obj_link);
        }

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

    private function inicializa_priedades(): array
    {
        $identificador = "org_sucursal_id";
        $propiedades = array("label" => "Sucursal","cols" => 12);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "codigo";
        $propiedades = array("place_holder" => "Codigo");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "codigo_bis";
        $propiedades = array("place_holder" => "Codigo BIS");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "serie";
        $propiedades = array("place_holder" => "serie");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        return $this->keys_selects;
    }

    public function modifica(bool $header, bool $ws = false, string $breadcrumbs = '', bool $aplica_form = true,
                             bool $muestra_btn = true): array|string
    {
        $base = $this->base();
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al maquetar datos',data:  $base,
                header: $header,ws:$ws);
        }

        return $base->template;
    }

    public function subir_key(bool $header, bool $ws = false): array|stdClass
    {
        $columns["fc_key_csd_id"]["titulo"] = "Id";
        $columns["fc_key_csd_codigo"]["titulo"] = "Codigo";
        $columns["fc_key_csd_descripcion"]["titulo"] = "Descripcion";
        $columns["fc_csd_descripcion"]["titulo"] = "CSD";
        $columns["doc_documento_descripcion"]["titulo"] = "Documento";
        $columns["modifica"]["titulo"] = "Acciones";
        $columns["modifica"]["type"] = "button";
        $columns["modifica"]["campos"] = array("elimina_bd");

        $colums_rs =$this->datatable_init(columns: $columns,identificador: "#fc_key_csd",
            data: array("fc_csd.id" => $this->registro_id));
        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al inicializar links', data: $colums_rs);
            print_r($error);
            die('Error');
        }

        $alta = $this->controlador_fc_key_csd->alta(header: false);
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al generar template', data: $alta, header: $header, ws: $ws);
        }

        $this->controlador_fc_key_csd->asignar_propiedad(identificador: 'fc_csd_id',
            propiedades: ["id_selected" => $this->registro_id, "disabled" => true, "cols" => 12,
                "filtro" => array('fc_csd.id' => $this->registro_id)]);
        $this->controlador_fc_key_csd->asignar_propiedad(identificador: 'documento', propiedades: ["cols" => 12]);

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

        $registro = $_POST;
        $registro['fc_csd_id'] = $this->registro_id;

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
