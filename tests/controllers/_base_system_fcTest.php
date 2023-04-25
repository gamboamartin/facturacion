<?php
namespace gamboamartin\facturacion\tests\controllers;


use gamboamartin\errores\errores;
use gamboamartin\facturacion\controllers\controlador_fc_factura;
use gamboamartin\facturacion\controllers\pdf;
use gamboamartin\facturacion\models\fc_csd;
use gamboamartin\facturacion\tests\base_test;
use gamboamartin\organigrama\models\org_empresa;
use gamboamartin\organigrama\models\org_sucursal;
use gamboamartin\test\liberator;
use gamboamartin\test\test;
use gamboamartin\facturacion\models\fc_factura;


use stdClass;


class _base_system_fcTest extends test {
    public errores $errores;
    private stdClass $paths_conf;
    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->errores = new errores();
        $this->paths_conf = new stdClass();
        $this->paths_conf->generales = '/var/www/html/facturacion/config/generales.php';
        $this->paths_conf->database = '/var/www/html/facturacion/config/database.php';
        $this->paths_conf->views = '/var/www/html/facturacion/config/views.php';
    }

    public function test_parents(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'fc_factura';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';


        $ctl = new controlador_fc_factura(link: $this->link, paths_conf: $this->paths_conf);
        $ctl = new liberator($ctl);

        $resultado = $ctl->parents();
        //print_r($resultado);exit;
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertIsObject($resultado[0]);
        $this->assertIsObject($resultado[1]);
        $this->assertIsObject($resultado[2]);
        $this->assertIsObject($resultado[3]);
        $this->assertIsObject($resultado[4]);
        $this->assertIsObject($resultado[5]);
        $this->assertIsObject($resultado[6]);
        $this->assertIsObject($resultado[7]);
        $this->assertIsObject($resultado[8]);
        $this->assertIsObject($resultado[9]);
        $this->assertEquals('cat_sat_uso_cfdi',$resultado[4]->tabla);
        errores::$error = false;
    }



}

