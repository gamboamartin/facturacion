<?php
namespace gamboamartin\facturacion\controllers;

use base\controller\controler;
use base\orm\modelo;
use gamboamartin\errores\errores;
use gamboamartin\system\html_controler;
use gamboamartin\system\links_menu;
use gamboamartin\system\system;
use PDO;
use stdClass;

class _base_system extends system {

    public function __construct(html_controler $html_, PDO $link, modelo $modelo, stdClass $paths_conf = new stdClass())
    {
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
    }

    /**
     * La funcion debe estar definida en el controlador que hereda
     */
    private function init_configuraciones(): controler{
        return $this;
    }

    /**
     * La funcion debe estar definida en el controlador que hereda
     * @return stdClass
     */
    public function init_datatable(): stdClass
    {

        return new stdClass();
    }

}
