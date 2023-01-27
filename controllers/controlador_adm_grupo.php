<?php
namespace gamboamartin\facturacion\controllers;

use gamboamartin\template_1\html;
use PDO;
use stdClass;

class controlador_adm_grupo extends \gamboamartin\documento\controllers\controlador_adm_grupo {

    public function __construct(PDO $link, html $html = new html(), stdClass $paths_conf = new stdClass())
    {
        parent::__construct($link, $html, $paths_conf);
        $this->path_vendor_views = 'gamboa.martin/documento';
    }

}