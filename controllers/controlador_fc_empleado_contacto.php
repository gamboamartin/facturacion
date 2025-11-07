<?php

namespace gamboamartin\facturacion\controllers;


use gamboamartin\facturacion\html\fc_layout_nom_html;
use gamboamartin\facturacion\models\fc_empleado_contacto;
use gamboamartin\facturacion\models\fc_layout_nom;
use gamboamartin\system\html_controler;
use gamboamartin\system\links_menu;
use gamboamartin\system\system;
use gamboamartin\template_1\html;
use PDO;
use stdClass;


class controlador_fc_empleado_contacto extends system {
    public function __construct(PDO $link, html $html = new html(), stdClass $paths_conf = new stdClass()){

        $this->rows_lista = ['telefono','correo','nombre'];
        $this->inputs_alta = ['telefono','correo','nombre'];

        $modelo = new fc_empleado_contacto(link: $link);
        $html_ = new html_controler($html);
        $obj_link = new links_menu(link: $link, registro_id:  $this->registro_id);

        parent::__construct(html:$html_, link: $link,modelo:  $modelo, obj_link: $obj_link, paths_conf: $paths_conf);



    }
}
