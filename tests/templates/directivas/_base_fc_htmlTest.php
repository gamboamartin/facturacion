<?php
namespace tests\controllers;

use gamboamartin\errores\errores;
use gamboamartin\facturacion\html\_base_fc_html;
use gamboamartin\facturacion\html\fc_csd_html;
use gamboamartin\test\liberator;
use gamboamartin\test\test;
use stdClass;


class _base_fc_htmlTest extends test {
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

    public function test_txt_null_normalizado(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $html_ = new \gamboamartin\template_1\html();
        $html = new _base_fc_html($html_);
        $html = new liberator($html);


        $key = 'a';
        $row = array();

        $resultado = $html->txt_null_normalizado($key, $row);

        $this->assertIsString($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals("", $resultado);
        errores::$error = false;


    }


}

