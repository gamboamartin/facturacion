<?php
namespace tests\controllers;

use gamboamartin\errores\errores;
use gamboamartin\facturacion\html\fc_csd_html;
use gamboamartin\test\test;
use stdClass;


class fc_csd_htmlTest extends test {
    public errores $errores;
    private stdClass $paths_conf;
    public function __construct(?string $name = null)
    {
        parent::__construct($name);
        $this->errores = new errores();
        $this->paths_conf = new stdClass();
        $this->paths_conf->generales = '/var/www/html/cat_sat/config/generales.php';
        $this->paths_conf->database = '/var/www/html/cat_sat/config/database.php';
        $this->paths_conf->views = '/var/www/html/cat_sat/config/views.php';
    }

    public function test_input_serie(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $html_ = new \gamboamartin\template_1\html();
        $html = new fc_csd_html($html_);


        $cols = 1;
        $row_upd = new stdClass();
        $value_vacio = false;
        $resultado = $html->input_serie($cols, $row_upd, $value_vacio);
        $this->assertIsString($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals("<div class='control-group col-sm-1'><label class='control-label' for='serie'>Serie</label><div class='controls'><input type='text' name='serie' value='' class='form-control' required id='serie' placeholder='Serie' title='Serie' /></div></div>", $resultado);
        errores::$error = false;


    }


}

