<?php
/**
 * @author Martin Gamboa Vazquez
 * @version 1.0.0
 * @created 2022-05-14
 * @final En proceso
 *
 */
namespace gamboamartin\facturacion\controllers;

use PDO;
use stdClass;

class controlador_cat_sat_moneda extends \gamboamartin\comercial\controllers\controlador_cat_sat_moneda {
    public function __construct(PDO $link, stdClass $paths_conf = new stdClass())
    {
        parent::__construct(link: $link,paths_conf:  $paths_conf);

        $this->childrens_data['com_cliente']['title'] = 'Cliente';

    }
}
