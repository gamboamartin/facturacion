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
use gamboamartin\facturacion\html\fc_cer_pem_html;
use gamboamartin\facturacion\models\fc_cer_pem;
use gamboamartin\system\links_menu;
use gamboamartin\system\system;

use gamboamartin\template\html;
use PDO;
use stdClass;

class controlador_fc_cer_pem extends _base_system_conf {

    public array|stdClass $keys_selects = array();

    public function __construct(PDO $link, html $html = new \gamboamartin\template_1\html(),
                                stdClass $paths_conf = new stdClass()){
        $modelo = new fc_cer_pem(link: $link);
        $html_ = new fc_cer_pem_html(html: $html);
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
        $this->seccion_titulo = 'PEMS CSD';
        $this->titulo_lista = 'Registro de PEMS CSD';

        return $this;
    }

    /**
     * Integra los elementos de view para datatables
     * @return stdClass
     */
    public function init_datatable(): stdClass
    {
        $columns["fc_cer_pem_id"]["titulo"] = "Id";
        $columns["fc_cer_pem_codigo"]["titulo"] = "Código";
        $columns["doc_documento_descripcion"]["titulo"] = "Documento";

        $filtro = array("fc_cer_pem.id","fc_cer_pem.codigo","doc_documento.descripcion");

        $datatables = new stdClass();
        $datatables->columns = $columns;
        $datatables->filtro = $filtro;

        return $datatables;
    }

    private function init_inputs(): array
    {
        $identificador = "fc_cer_csd_id";
        $propiedades = array("label" => "CSD CER", "cols" => 12);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "doc_documento_id";
        $propiedades = array("label" => "Documento", "cols" => 8);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "documento";
        $propiedades = array("place_holder" => "Documento", "cols" => 8);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "codigo";
        $propiedades = array("place_holder" => "Código", "cols" => 4);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);


        return $this->keys_selects;
    }

    private function init_modifica(): array|stdClass
    {
        $r_modifica =  parent::modifica(header: false);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al generar template',data:  $r_modifica);
        }

        $identificador = "fc_cer_csd_id";
        $propiedades = array("id_selected" => $this->row_upd->fc_cer_csd_id);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "doc_documento_id";
        $propiedades = array("id_selected" => $this->row_upd->doc_documento_id);
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
