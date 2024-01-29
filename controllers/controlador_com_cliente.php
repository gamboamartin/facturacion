<?php
/**
 * @author Martin Gamboa Vazquez
 * @version 1.0.0
 * @created 2022-05-14
 * @final En proceso
 *
 */
namespace gamboamartin\facturacion\controllers;

use gamboamartin\facturacion\models\com_cliente;
use gamboamartin\template\html;
use PDO;
use stdClass;

class controlador_com_cliente extends \gamboamartin\comercial\controllers\controlador_com_cliente {
    public function __construct(PDO $link, html $html = new \gamboamartin\template_1\html(),
                                stdClass $paths_conf = new stdClass())
    {
        parent::__construct(link: $link,html:  $html,paths_conf:  $paths_conf);

        $this->modelo = new com_cliente(link: $this->link);


    }
}
