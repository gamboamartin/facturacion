<?php
/**
 * @author Martin Gamboa Vazquez
 * @version 1.0.0
 * @created 2022-05-14
 * @final En proceso
 *
 */
namespace gamboamartin\facturacion\controllers;

use gamboamartin\facturacion\html\fc_cfdi_html;
use gamboamartin\facturacion\models\fc_cfdi;
use gamboamartin\system\links_menu;
use gamboamartin\system\system;

use gamboamartin\template_1\html;
use PDO;
use stdClass;

class controlador_fc_cfdi extends system{

    public stdClass|array $keys_selects = array();

    public function __construct(PDO $link, html $html = new html(), stdClass $paths_conf = new stdClass()){
        $modelo = new fc_cfdi(link: $link);
        $html_ = new fc_cfdi_html(html: $html);
        $obj_link = new links_menu(link: $link, registro_id:  $this->registro_id);

        parent::__construct(html:$html_, link: $link,modelo:  $modelo, obj_link: $obj_link,
            paths_conf: $paths_conf);

    }

}
