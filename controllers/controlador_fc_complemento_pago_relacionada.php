<?php
/**
 * @author Martin Gamboa Vazquez
 * @version 1.0.0
 * @created 2022-05-14
 * @final En proceso
 *
 */
namespace gamboamartin\facturacion\controllers;

use base\controller\controler;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\html\fc_factura_html;
use gamboamartin\facturacion\html\fc_factura_relacionada_html;
use gamboamartin\facturacion\html\fc_complemento_pago_relacionada_html;
use gamboamartin\facturacion\html\fc_relacion_html;
use gamboamartin\facturacion\models\fc_factura_relacionada;


use gamboamartin\facturacion\models\fc_complemento_pago_relacionada;
use gamboamartin\template\html;
use PDO;
use stdClass;

class controlador_fc_complemento_pago_relacionada extends _ctl_relacionada {


    public function __construct(PDO      $link, html $html = new \gamboamartin\template_1\html(),
                                stdClass $paths_conf = new stdClass())
    {
        $modelo = new fc_complemento_pago_relacionada(link: $link);
        $html_ = new fc_complemento_pago_relacionada_html(html: $html);
        parent::__construct(html_: $html_,link:  $link,modelo:  $modelo, paths_conf: $paths_conf);

        $this->key_folio = $this->tabla.'_folio';

    }




}
