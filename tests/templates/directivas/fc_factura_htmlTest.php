<?php
namespace tests\controllers;


use gamboamartin\errores\errores;
use gamboamartin\facturacion\html\fc_factura_html;
use gamboamartin\facturacion\models\fc_factura;
use gamboamartin\template\html;
use gamboamartin\test\test;

use stdClass;


class fc_factura_htmlTest extends test {
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

    public function test_valida_partida_alta(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $html_ = new html();
        $html = new fc_factura_html($html_);


        $cols = 1;
        $row_upd = new stdClass();

        $row_upd->exportacion = '1';
        $link = $this->link;
        $modelo = new fc_factura(link: $link);
        $resultado = $html->select_exportacion($cols, $link, $modelo, $row_upd);

        //print_r($resultado);exit;

        $this->assertIsString($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertStringContainsStringIgnoringCase("div class='control-group col-sm-1'><div class='controls'><select ", $resultado);
        $this->assertStringContainsStringIgnoringCase("<select class='form-control selectpicker color-secondary exp", $resultado);
        $this->assertStringContainsStringIgnoringCase("exportacion ' data-live-search='true' id='exportacion'", $resultado);
        $this->assertStringContainsStringIgnoringCase("name='exportacion' required ><option value=''  >Sel", $resultado);
        $this->assertStringContainsStringIgnoringCase("Selecciona una opcion</option>", $resultado);
        errores::$error = false;


    }


}

