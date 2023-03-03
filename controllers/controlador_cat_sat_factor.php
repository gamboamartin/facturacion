<?php
/**
 * @author Martin Gamboa Vazquez
 * @version 1.0.0
 * @created 2022-05-14
 * @final En proceso
 *
 */
namespace gamboamartin\facturacion\controllers;

use gamboamartin\template\html;
use PDO;
use stdClass;

class controlador_cat_sat_factor extends \gamboamartin\cat_sat\controllers\controlador_cat_sat_factor {
    public function __construct(PDO $link, html $html = new \gamboamartin\template_1\html(), stdClass $paths_conf = new stdClass())
    {
        parent::__construct(link: $link,html:  $html,paths_conf:  $paths_conf);

        $this->childrens_data['fc_conf_retenido']['title'] = 'Conf Retenido';
        $this->childrens_data['fc_conf_traslado']['title'] = 'Conf Traslado';
    }
}
