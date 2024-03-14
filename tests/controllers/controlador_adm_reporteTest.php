<?php
namespace gamboamartin\facturacion\tests\controllers;


use gamboamartin\errores\errores;
use gamboamartin\facturacion\controllers\controlador_adm_reporte;
use gamboamartin\test\liberator;
use gamboamartin\test\test;


use stdClass;


class controlador_adm_reporteTest extends test {
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

    public function test_filtro_rango_post(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'fc_factura';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';


        $ctl = new controlador_adm_reporte(link: $this->link, paths_conf: $this->paths_conf);
        $ctl = new liberator($ctl);

        $table = 'A';
        $resultado = $ctl->filtro_rango_post($table);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);

        errores::$error = false;
    }

    public function test_init_fecha_inicial(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'fc_factura';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';


        $ctl = new controlador_adm_reporte(link: $this->link, paths_conf: $this->paths_conf);
        $ctl = new liberator($ctl);

        $_POST['fecha_inicial'] = '';
        $data = new stdClass();
        $resultado = $ctl->init_fecha_inicial($data);
        //print_r($resultado);exit;

        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertTrue($resultado->existe_alguna_fecha);
        $this->assertTrue($resultado->existe_fecha_inicial);

        errores::$error = false;
    }
    public function test_init_filtro_fecha(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'fc_factura';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';


        $ctl = new controlador_adm_reporte(link: $this->link, paths_conf: $this->paths_conf);
        $ctl = new liberator($ctl);


        $resultado = $ctl->init_filtro_fecha();

        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertFalse($resultado->existe_alguna_fecha);
        $this->assertFalse($resultado->existe_fecha_inicial);
        $this->assertFalse($resultado->existe_fecha_final);


        errores::$error = false;
    }

    public function test_td(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'fc_factura';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';


        $ctl = new controlador_adm_reporte(link: $this->link, paths_conf: $this->paths_conf);
        $ctl = new liberator($ctl);

        $key_registro = 'a';
        $registro = array();
        $registro['a'] = '';
        $resultado = $ctl->td(false,$key_registro, $registro);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals("<td></td>",$resultado);
        $this->assertIsString($resultado);
        errores::$error = false;
    }

}

