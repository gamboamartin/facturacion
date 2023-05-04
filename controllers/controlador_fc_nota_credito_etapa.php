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
use gamboamartin\facturacion\html\fc_nota_credito_etapa_html;
use gamboamartin\facturacion\models\fc_nota_credito_etapa;

use gamboamartin\template\html;
use PDO;
use stdClass;

class controlador_fc_nota_credito_etapa extends _base {


    public function __construct(PDO      $link, html $html = new \gamboamartin\template_1\html(),
                                stdClass $paths_conf = new stdClass())
    {
        $modelo = new fc_nota_credito_etapa(link: $link);
        $html_ = new fc_nota_credito_etapa_html(html: $html);
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
        $columns["fc_nota_credito_id"]["titulo"] = "Id";
        $columns["pr_etapa_descripcion"]["titulo"] = "Etapa";
        $columns["fc_nota_credito_etapa_fecha"]["titulo"] = "Fecha";

        $filtro = array("fc_nota_credito.id","pr_etapa.descripcion","fc_nota_credito_etapa.fecha");

        $datatables = new stdClass();
        $datatables->columns = $columns;
        $datatables->filtro = $filtro;

        return $datatables;
    }


}
