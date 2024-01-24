<?php
namespace tests\controllers;

use gamboamartin\errores\errores;
use gamboamartin\facturacion\html\fc_csd_html;
use gamboamartin\facturacion\instalacion\instalacion;
use gamboamartin\test\liberator;
use gamboamartin\test\test;
use stdClass;


class instalacionTest extends test {
    public errores $errores;
    private stdClass $paths_conf;
    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
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
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $instalacion = new instalacion();
        $instalacion = new liberator($instalacion);

        $resultado = $instalacion->foraneas_factura();

        //print_r($resultado);exit;

        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertIsObject( $resultado['fc_csd_id']);
        $this->assertIsObject( $resultado['cat_sat_forma_pago_id']);
        $this->assertIsObject( $resultado['cat_sat_metodo_pago_id']);
        $this->assertIsObject( $resultado['cat_sat_moneda_id']);
        $this->assertIsObject( $resultado['com_tipo_cambio_id']);
        $this->assertIsObject( $resultado['cat_sat_uso_cfdi_id']);
        $this->assertIsObject( $resultado['cat_sat_tipo_de_comprobante_id']);
        $this->assertIsObject( $resultado['dp_calle_pertenece_id']);
        $this->assertIsObject( $resultado['cat_sat_regimen_fiscal_id']);
        $this->assertIsObject( $resultado['com_sucursal_id']);
        errores::$error = false;


    }


}

