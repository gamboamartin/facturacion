<?php
namespace controllers;


use gamboamartin\errores\errores;
use gamboamartin\facturacion\controllers\controlador_fc_ejecucion_automatica;
use gamboamartin\facturacion\controllers\controlador_fc_factura;
use gamboamartin\facturacion\controllers\controlador_fc_factura_automatica;
use gamboamartin\facturacion\controllers\controlador_fc_nota_credito;
use gamboamartin\facturacion\controllers\pdf;
use gamboamartin\facturacion\models\fc_csd;
use gamboamartin\facturacion\tests\base_test;
use gamboamartin\organigrama\models\org_empresa;
use gamboamartin\organigrama\models\org_sucursal;
use gamboamartin\test\liberator;
use gamboamartin\test\test;
use gamboamartin\facturacion\models\fc_factura;


use stdClass;


class _automaticosTest extends test {
    public errores $errores;
    private stdClass $paths_conf;
    public function __construct(?string $name = null)
    {
        parent::__construct($name);
        $this->errores = new errores();
        $this->paths_conf = new stdClass();
        $this->paths_conf->generales = '/var/www/html/facturacion/config/generales.php';
        $this->paths_conf->database = '/var/www/html/facturacion/config/database.php';
        $this->paths_conf->views = '/var/www/html/facturacion/config/views.php';
    }

    public function test_buttons_html(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'fc_factura';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';


        $ctl = new controlador_fc_ejecucion_automatica(link: $this->link,paths_conf: $this->paths_conf);
        $controlador_factura = new controlador_fc_factura(link: $this->link, paths_conf: $this->paths_conf);
        $controlador_factura->registro = array();
        $controlador_factura->registro['fc_factura_id'] = 1;
        $ctl = new liberator($ctl);

        $resultado = $ctl->buttons_html($controlador_factura);

        $this->assertIsString($resultado);
        $this->assertNotTrue(errores::$error);
        errores::$error = false;
    }




}

