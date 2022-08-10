<?php
/**
 * @author Martin Gamboa Vazquez
 * @version 1.0.0
 * @created 2022-05-14
 * @final En proceso
 *
 */
namespace gamboamartin\facturacion\controllers;

use gamboamartin\errores\errores;
use gamboamartin\system\actions;
use gamboamartin\system\init;
use gamboamartin\system\links_menu;
use gamboamartin\system\system;

use gamboamartin\template\html;
use html\fc_factura_html;
use models\fc_factura;
use PDO;
use phpDocumentor\Reflection\DocBlock\Tags\Link;
use stdClass;

class controlador_fc_factura extends system{
    public string $rfc = '';
    public string $razon_social = '';

    public function __construct(PDO $link, html $html = new \gamboamartin\template_1\html(),
                                stdClass $paths_conf = new stdClass()){
        $modelo = new fc_factura(link: $link);

        $html_ = new fc_factura_html(html: $html);
        $obj_link = new links_menu($this->registro_id);

        parent::__construct(html:$html_, link: $link,modelo:  $modelo, obj_link: $obj_link, paths_conf: $paths_conf);

        $this->titulo_lista = 'Empresas';

    }

    

}
