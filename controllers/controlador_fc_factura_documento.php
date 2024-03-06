<?php
/**
 * @author Martin Gamboa Vazquez
 * @version 1.0.0
 * @created 2022-05-14
 * @final En proceso
 *
 */
namespace gamboamartin\facturacion\controllers;


use gamboamartin\facturacion\html\fc_factura_documento_html;
use gamboamartin\facturacion\models\fc_factura_documento;
use gamboamartin\system\links_menu;

use gamboamartin\template_1\html;
use PDO;
use stdClass;

class controlador_fc_factura_documento extends _entidad_docto {

    public array|stdClass $keys_selects = array();

    public function __construct(PDO $link, html $html = new html(), stdClass $paths_conf = new stdClass()){
        $modelo = new fc_factura_documento(link: $link);
        $html_ = new fc_factura_documento_html(html: $html);
        $obj_link = new links_menu(link: $link, registro_id:  $this->registro_id);

        parent::__construct(html:$html_, link: $link,modelo:  $modelo, obj_link: $obj_link,
            paths_conf: $paths_conf);

        $this->lista_get_data = true;

    }



}
