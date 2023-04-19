<?php
namespace gamboamartin\facturacion\controllers;

use base\controller\controler;
use base\orm\modelo;
use gamboamartin\errores\errores;
use gamboamartin\system\_ctl_base;
use gamboamartin\system\html_controler;
use gamboamartin\system\links_menu;
use PDO;
use stdClass;

class _base extends _ctl_base {
    public function __construct(html_controler $html_,PDO $link, modelo $modelo, stdClass $paths_conf = new stdClass())
    {



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

    /**
     * Funcion debe estar definida en controlador que hereda
     * @return controler
     */
    private function init_configuraciones(): controler
    {
        return $this;
    }

    /**
     * Funcion debe estar definida en controlador que hereda
     * @return stdClass
     */
    private function init_datatable(): stdClass
    {
        return new stdClass();
    }

}
