<?php
namespace controllers;


use gamboamartin\errores\errores;
use gamboamartin\facturacion\controllers\_fc_base;
use gamboamartin\facturacion\controllers\controlador_fc_factura;
use gamboamartin\test\test;



use stdClass;


class _fc_baseTest extends test {
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

    public function test_init_base_fc(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'fc_factura';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';



        $ctl = new _fc_base();
        //$ctl = new liberator($ctl);

        $controler = new controlador_fc_factura(link: $this->link,paths_conf: $this->paths_conf);
        $name_modelo_email = 'a';
        $resultado = $ctl->init_base_fc($controler,$name_modelo_email);

        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals('fc_csd_serie',$resultado->inputs['fc_csd_id']->extra_params_keys[0]);
        $this->assertEquals('com_cliente_cat_sat_metodo_pago_id',$resultado->inputs['com_sucursal_id']->extra_params_keys[1]);

        errores::$error = false;
    }



}

