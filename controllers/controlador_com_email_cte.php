<?php
/**
 * @author Martin Gamboa Vazquez
 * @version 1.0.0
 * @created 2022-05-14
 * @final En proceso
 *
 */
namespace gamboamartin\facturacion\controllers;

use gamboamartin\facturacion\models\com_email_cte;
use gamboamartin\template\html;
use PDO;
use stdClass;

class controlador_com_email_cte extends \gamboamartin\comercial\controllers\controlador_com_email_cte {
    public function __construct(PDO $link, html $html = new \gamboamartin\template_1\html(), stdClass $paths_conf = new stdClass())
    {
        parent::__construct($link, $html, $paths_conf);
        $this->modelo = new com_email_cte(link: $this->link);
    }
}
