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

class controlador_fc_partida extends _ctl_partida {

    public controlador_fc_traslado $controlador_fc_traslado;
    public string $link_fc_traslado_alta_bd = '';


    public function __construct(PDO      $link, html $html = new \gamboamartin\template_1\html(),
                                stdClass $paths_conf = new stdClass())
    {
        $modelo = new fc_partida(link: $link);
        $html_ = new fc_partida_html(html: $html);

        // Compatibilidad durante carga concurrente de versiones: no invocar APIs aún ausentes.
        $acepta_config_datatable = false;
        foreach ((new \ReflectionMethod(_base::class, '__construct'))->getParameters() as $parametro) {
            if ($parametro->getName() === 'config_datatable') $acepta_config_datatable = true;
        }
        $config_datatable = null;
        if ($acepta_config_datatable && method_exists($this, 'configuracion_listado_partida')) {
            if (!isset($_GET['seccion']) || $_GET['seccion'] === 'fc_partida') {
                $config_datatable = $this->configuracion_listado_partida();
                $this->lista_get_data = true;
            }
            parent::__construct(html_: $html_, link: $link, modelo: $modelo, paths_conf: $paths_conf,
                config_datatable: $config_datatable);
        } else {
            // Ruta histórica completa mientras el proveedor cargado sea anterior.
            parent::__construct(html_: $html_, link: $link, modelo: $modelo, paths_conf: $paths_conf);
        }

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
        $this->name_entidad_factura = 'fc_factura';
    }


}
