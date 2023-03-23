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


class controlador_fc_facturaTest extends test {
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

    public function test_init_configuraciones(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'fc_factura';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';


        $ctl = new controlador_fc_factura(link: $this->link, paths_conf: $this->paths_conf);
        $ctl = new liberator($ctl);

        $resultado = $ctl->init_configuraciones();
        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);
        errores::$error = false;
    }

    public function test_init_controladores(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'fc_factura';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';


        $ctl = new controlador_fc_factura(link: $this->link, paths_conf: $this->paths_conf);
        $ctl = new liberator($ctl);

        $resultado = $ctl->init_controladores($this->paths_conf);
        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);
        errores::$error = false;
    }

    public function test_init_datatable(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'fc_factura';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';


        $ctl = new controlador_fc_factura(link: $this->link, paths_conf: $this->paths_conf);
        //$ctl = new liberator($ctl);

        $resultado = $ctl->init_datatable();
        //print_r($resultado);exit;

        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals('Id',$resultado->columns['fc_factura_id']['titulo']);
        $this->assertEquals('Serie',$resultado->columns['fc_factura_serie']['titulo']);
        $this->assertEquals('Folio',$resultado->columns['fc_factura_folio']['titulo']);
        $this->assertEquals('RFC',$resultado->columns['org_empresa_rfc']['titulo']);
        $this->assertEquals('Cliente',$resultado->columns['com_cliente_rfc']['titulo']);
        $this->assertEquals('fc_factura.id',$resultado->filtro[0]);
        $this->assertEquals('fc_factura.serie',$resultado->filtro[1]);
        $this->assertEquals('fc_factura.folio',$resultado->filtro[2]);
        $this->assertEquals('org_empresa.rfc',$resultado->filtro[3]);
        $this->assertEquals('com_cliente.rfc',$resultado->filtro[4]);
        $this->assertEquals('fc_factura.fecha',$resultado->filtro[5]);


        errores::$error = false;
    }




}

