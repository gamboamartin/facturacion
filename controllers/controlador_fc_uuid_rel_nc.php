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
use gamboamartin\facturacion\html\fc_uuid_rel_nc_html;
use gamboamartin\facturacion\models\fc_cfdi;
use gamboamartin\facturacion\models\fc_uuid_rel_nc;
use gamboamartin\facturacion\models\fc_relacion_nc;
use gamboamartin\system\links_menu;
use gamboamartin\system\system;
use gamboamartin\template\html;

use PDO;
use stdClass;

class controlador_fc_uuid_rel_nc extends _base_system_conf {

    public array|stdClass $keys_selects = array();

    public function __construct(PDO $link, html $html = new \gamboamartin\template_1\html(),
                                stdClass $paths_conf = new stdClass()){
        $modelo = new fc_uuid_rel_nc(link: $link);
        $html_ = new fc_uuid_rel_nc_html(html: $html);
        parent::__construct(html_: $html_, link: $link,modelo:  $modelo, paths_conf: $paths_conf);

        $inputs = $this->init_inputs();
        if(errores::$error){
            $error = $this->errores->error(mensaje: 'Error al inicializar inputs',data:  $inputs);
            print_r($error);
            die('Error');
        }

        /*$this->parents_verifica[] = (new fc_cfdi(link: $this->link));*/
        $this->parents_verifica[] = (new fc_relacion_nc(link: $this->link));

        $this->verifica_parents_alta = true;


    }

    private function init_configuraciones(): controler
    {
        $this->seccion_titulo = 'Relacion notas de credito';
        $this->titulo_lista = 'Registro de relacion de las Notas de Créditos';

        return $this;
    }

    public function init_datatable(): stdClass
    {

        $columns["fc_uuid_rel_nc_id"]["titulo"] = "Id";
        $columns["fc_uuid_rel_nc_codigo"]["titulo"] = "Código";
        $columns["fc_uuid_rel_nc_descripcion"]["titulo"] = "Relacion UUID";

        /*$columns["fc_cfdi_descripcion"]["titulo"] = "Tipo de Comprobante";*/
        $columns["fc_relacion_nc_descripcion"]["titulo"] = "Regimen Fiscal";


        $filtro = array("fc_uuid_rel_nc.id", "fc_uuid_rel_nc.codigo", "fc_uuid_rel_nc.descripcion",
            /*"fc_cfdi.descripcion", */"fc_relacion_nc.descripcion");

        $datatables = new stdClass();
        $datatables->columns = $columns;
        $datatables->filtro = $filtro;

        return $datatables;
    }

    private function init_inputs(): array
    {
        $identificador = "fc_relacion_nc_id";
        $propiedades = array("label" => "Notas de credito");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "fc_relacion_nc_id";
        $propiedades = array("label" => "Relacion NC");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        return $this->keys_selects;
    }

    private function init_modifica(): array|stdClass
    {
        $r_modifica =  parent::modifica(header: false);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al generar template',data:  $r_modifica);
        }
        $identificador = "fc_uuid_rel_nc_id";

        $csd = (new fc_relacion_nc($this->link))->get_relacion_nc($this->row_upd->fc_relacion_nc_id);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener csd',data:  $csd);
        }


        /*$identificador = "fc_cfdi_id";
        $propiedades = array("id_selected" => $csd['fc_cfdi_id']);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);*/
        
        $identificador = "fc_relacion_nc_id";
        $propiedades = array("id_selected" => $this->row_upd->fc_relacion_nc_id);
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
