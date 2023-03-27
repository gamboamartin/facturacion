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
use gamboamartin\facturacion\models\fc_factura_etapa;
use gamboamartin\system\_ctl_base;
use gamboamartin\system\links_menu;

use gamboamartin\template\html;
use html\fc_factura_etapa_html;
use PDO;
use stdClass;

class controlador_fc_factura_etapa extends _ctl_base{


    public function __construct(PDO      $link, html $html = new \gamboamartin\template_1\html(),
                                stdClass $paths_conf = new stdClass())
    {
        $modelo = new fc_factura_etapa(link: $link);
        $html_ = new fc_factura_etapa_html(html: $html);
        $obj_link = new links_menu(link: $link, registro_id: $this->registro_id);

        $datatables = $this->init_datatable();
        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al inicializar datatable', data: $datatables);
            print_r($error);
            die('Error');
        }

        parent::__construct(html: $html_, link: $link, modelo: $modelo, obj_link: $obj_link, datatables: $datatables,
            paths_conf: $paths_conf);

        $configuraciones = $this->init_configuraciones();
        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al inicializar configuraciones', data: $configuraciones);
            print_r($error);
            die('Error');
        }


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
