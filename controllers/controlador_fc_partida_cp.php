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
use gamboamartin\facturacion\html\fc_partida_html;
use gamboamartin\facturacion\models\fc_partida;

use gamboamartin\template\html;
use PDO;
use stdClass;

class controlador_fc_partida_cp extends _ctl_partida {

    public controlador_fc_traslado $controlador_fc_traslado;
    public string $link_fc_traslado_alta_bd = '';

    public function __construct(PDO      $link, html $html = new \gamboamartin\template_1\html(),
                                stdClass $paths_conf = new stdClass())
    {
        $modelo = new fc_partida(link: $link);
        $html_ = new fc_partida_html(html: $html);

        parent::__construct(html_: $html_,link:  $link,modelo:  $modelo, paths_conf: $paths_conf);

        $init_controladores = $this->init_controladores(paths_conf: $paths_conf);
        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al inicializar controladores', data: $init_controladores);
            print_r($error);
            die('Error');
        }

        $init_links = $this->init_links();
        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al inicializar links', data: $init_links);
            print_r($error);
            die('Error');
        }
        $this->name_entidad_factura = 'fc_complemento_pago';
    }


}
