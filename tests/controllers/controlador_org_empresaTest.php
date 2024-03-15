<?php
namespace gamboamartin\facturacion\tests\controllers;


use gamboamartin\errores\errores;
use gamboamartin\facturacion\controllers\controlador_fc_factura;
use gamboamartin\facturacion\controllers\controlador_fc_partida;
use gamboamartin\facturacion\controllers\controlador_org_empresa;
use gamboamartin\facturacion\controllers\pdf;
use gamboamartin\facturacion\models\fc_csd;
use gamboamartin\facturacion\tests\base_test;
use gamboamartin\organigrama\models\org_empresa;
use gamboamartin\organigrama\models\org_sucursal;
use gamboamartin\test\liberator;
use gamboamartin\test\test;
use gamboamartin\facturacion\models\fc_factura;


use stdClass;


class controlador_org_empresaTest extends test {
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

    public function test_alta(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'fc_factura';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';


        $ctl = new controlador_org_empresa(link: $this->link, paths_conf: $this->paths_conf);
        //$ctl = new liberator($ctl);

        $header = false;
        $resultado = $ctl->alta($header);
        //print_r($resultado);exit;
        $this->assertIsString($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertStringContainsStringIgnoringCase("div class='control-group col-sm-6'><label class='control-label' for='codigo'>Codigo</label><",$resultado);
        errores::$error = false;
    }





}

