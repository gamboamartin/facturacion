<?php
namespace gamboamartin\facturacion\tests\controllers;


use gamboamartin\errores\errores;
use gamboamartin\facturacion\controllers\pdf;
use gamboamartin\facturacion\models\fc_csd;
use gamboamartin\facturacion\tests\base_test;
use gamboamartin\organigrama\models\org_empresa;
use gamboamartin\organigrama\models\org_sucursal;
use gamboamartin\test\liberator;
use gamboamartin\test\test;
use gamboamartin\facturacion\models\fc_factura;


use stdClass;


class pdfTest extends test {
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

    public function test_concepto_producto(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';



        $pdf = new pdf($this->link);
        $pdf = new liberator($pdf);

        $concepto = array();
        $concepto['fc_partida_descripcion'] = 'A';
        $resultado = $pdf->concepto_producto($concepto,'fc_partida');
        $this->assertIsString($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals("<tr  ><td class='border color negrita' colspan='11'>Descripción</td></tr><tr  ><td class='border' colspan='11'>A</td></tr>",$resultado);


        errores::$error = false;

    }

    public function test_html(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';



        $pdf = new pdf($this->link);
        $pdf = new liberator($pdf);

        $etiqueta = 'a';
        $resultado = $pdf->html($etiqueta);
        $this->assertIsString($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals('<a  ></a>',$resultado);
        errores::$error = false;
    }

    public function test_keys_no_ob(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';



        $pdf = new pdf();
        $pdf = new liberator($pdf);

        $concepto = array();
        $resultado = $pdf->keys_no_ob($concepto);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(0,$resultado['fc_partida_descuento']);


        errores::$error = false;
    }

    public function test_limpia_monto(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';



        $pdf = new pdf($this->link);
        $pdf = new liberator($pdf);

        $monto = '1';
        $resultado = $pdf->limpia_monto($monto);

        $this->assertIsString($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals('1',$resultado);


        errores::$error = false;
    }

    public function test_monto_moneda(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';



        $pdf = new pdf();
        $pdf = new liberator($pdf);

        $monto = '1';
        $resultado = $pdf->monto_moneda($monto);
        $this->assertIsString($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals('$1.00',$resultado);


        errores::$error = false;

    }




}

