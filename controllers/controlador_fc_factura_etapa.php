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
use gamboamartin\facturacion\html\fc_factura_etapa_html;
use gamboamartin\facturacion\models\fc_factura_etapa;
use gamboamartin\system\_ctl_base;
use gamboamartin\system\links_menu;

use gamboamartin\template\html;
use PDO;
use stdClass;

class controlador_fc_factura_etapa extends _base {


    public function __construct(PDO      $link, html $html = new \gamboamartin\template_1\html(),
                                stdClass $paths_conf = new stdClass())
    {
        $modelo = new fc_factura_etapa(link: $link);
        $html_ = new fc_factura_etapa_html(html: $html);
        parent::__construct(html_: $html_,link:  $link,modelo:  $modelo, paths_conf: $paths_conf);


    }



    private function init_configuraciones(): controler
    {
        $this->seccion_titulo = 'Etapas';
        $this->titulo_lista = 'Registro de Etapas';

        return $this;
    }



    private function init_datatable(): stdClass
    {
        $columns["fc_factura_id"]["titulo"] = "Id";
        $columns["pr_etapa_descripcion"]["titulo"] = "Etapa";
        $columns["fc_factura_etapa_fecha"]["titulo"] = "Fecha";

        $filtro = array("fc_factura.id","pr_etapa.descripcion","fc_factura_etapa.fecha");

        $datatables = new stdClass();
        $datatables->columns = $columns;
        $datatables->filtro = $filtro;

        return $datatables;
    }


}
