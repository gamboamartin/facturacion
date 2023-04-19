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
use gamboamartin\facturacion\html\fc_cer_csd_html;
use gamboamartin\facturacion\models\fc_cer_csd;
use gamboamartin\system\links_menu;
use gamboamartin\system\system;

use gamboamartin\template\html;
use PDO;
use stdClass;

class controlador_fc_cer_csd extends _base_system_csd {

    public array|stdClass $keys_selects = array();

    public function __construct(PDO $link, html $html = new \gamboamartin\template_1\html(),
                                stdClass $paths_conf = new stdClass()){
        $modelo = new fc_cer_csd(link: $link);
        $html_ = new fc_cer_csd_html(html: $html);
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
        $this->seccion_titulo = 'Cers CSD';
        $this->titulo_lista = 'Registro de Cers CSD';

        return $this;
    }

    /**
     * Integra los elementos de view para datatables
     * @return stdClass
     */
    public function init_datatable(): stdClass
    {
        $columns["fc_cer_csd_id"]["titulo"] = "Id";
        $columns["fc_cer_csd_codigo"]["titulo"] = "Código";
        $columns["fc_csd_descripcion"]["titulo"] = "CSD";
        $columns["fc_cer_csd_descripcion"]["titulo"] = "Cer CSD";
        $columns["doc_documento_descripcion"]["titulo"] = "Documento";

        $filtro = array("fc_cer_csd.id","fc_cer_csd.codigo","fc_cer_csd.descripcion","fc_csd.descripcion",
            "doc_documento.descripcion");

        $datatables = new stdClass();
        $datatables->columns = $columns;
        $datatables->filtro = $filtro;

        return $datatables;
    }


}
