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
    public function __construct(?string $name = null)
    {
        parent::__construct($name);
        $this->errores = new errores();
        $this->paths_conf = new stdClass();
        $this->paths_conf->generales = '/var/www/html/cat_sat/config/generales.php';
        $this->paths_conf->database = '/var/www/html/cat_sat/config/database.php';
        $this->paths_conf->views = '/var/www/html/cat_sat/config/views.php';
    }

    public function test_campos_doubles_facturacion(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $instalacion = new instalacion();
        $instalacion = new liberator($instalacion);

        $resultado = $instalacion->campos_doubles_facturacion();
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals( 'cantidad',$resultado[0]);
        $this->assertEquals( 'valor_unitario',$resultado[1]);
        $this->assertEquals( 'descuento',$resultado[2]);
        $this->assertEquals( 'total_traslados',$resultado[3]);
        $this->assertEquals( 'total_retenciones',$resultado[4]);
        $this->assertEquals( 'total',$resultado[5]);
        $this->assertEquals( 'monto_pago_nc',$resultado[6]);
        $this->assertEquals( 'monto_pago_cp',$resultado[7]);
        $this->assertEquals( 'saldo',$resultado[8]);
        $this->assertEquals( 'monto_saldo_aplicado',$resultado[9]);
        $this->assertEquals( 'total_descuento',$resultado[10]);
        $this->assertEquals( 'sub_total_base',$resultado[11]);
        $this->assertEquals( 'sub_total',$resultado[12]);
        errores::$error = false;
    }

    public function test_campos_doubles_facturacion_integra(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $instalacion = new instalacion();
        $instalacion = new liberator($instalacion);

        $campos = new stdClass();
        $link = $this->link;
        $resultado = $instalacion->campos_double_facturacion_integra($campos, $link);
        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals( 'double',$resultado->cantidad->tipo_dato);
        $this->assertEquals( '0',$resultado->cantidad->default);
        $this->assertEquals( '100,2',$resultado->cantidad->longitud);

        errores::$error = false;
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

    public function test_limpia(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $instalacion = new instalacion();
        //$instalacion = new liberator($instalacion);

        $resultado = $instalacion->limpia(link: $this->link);


        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);

        errores::$error = false;

    }


}

