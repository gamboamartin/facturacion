<?php
namespace tests\orm;


use gamboamartin\errores\errores;
use gamboamartin\test\liberator;
use gamboamartin\test\test;
use gamboamartin\facturacion\models\fc_factura;


use stdClass;


class fc_facturaTest extends test {
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

    public function test_carga_descuento(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_factura($this->link);
        $modelo = new liberator($modelo);

        $descuento = 10;
        $partida = array();
        $partida['fc_partida_id'] = 1;
        $resultado = $modelo->carga_descuento($descuento, $partida);

        $this->assertIsFloat($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(11,$resultado);
        errores::$error = false;
    }

    public function test_descuento_partida(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_factura($this->link);
        $modelo = new liberator($modelo);

        $fc_partida_id = 1;
        $resultado = $modelo->descuento_partida($fc_partida_id);
        $this->assertIsFloat($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(1,$resultado);
        errores::$error = false;
    }

    public function test_get_factura_descuento(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_factura($this->link);
        //$modelo = new liberator($modelo);



        $fc_factura_id = 1;

        $resultado = $modelo->get_factura_descuento($fc_factura_id);
        $this->assertIsFloat($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(1,$resultado);
        errores::$error = false;
    }

    public function test_get_partidas(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_factura($this->link);
        $modelo = new liberator($modelo);

        $fc_factura_id = 1;
        $resultado = $modelo->get_partidas($fc_factura_id);


        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);


        errores::$error = false;
    }

    public function test_limpia_si_existe(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_factura($this->link);
        $modelo = new liberator($modelo);

        $key = 'a';
        $registro = array();
        $registro['a'] = 'z';
        $resultado = $modelo->limpia_si_existe($key, $registro);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEmpty($resultado);
        errores::$error = false;
    }

    public function test_sub_total(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_factura($this->link);
        //$modelo = new liberator($modelo);

        $fc_partida_id = 1;
        $resultado = $modelo->sub_total($fc_partida_id);
        $this->assertIsFloat($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(1,$resultado);
        errores::$error = false;

    }

    public function test_sub_total_partida(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_factura($this->link);
        $modelo = new liberator($modelo);

        $fc_partida_id = 1;
        $resultado = $modelo->sub_total_partida($fc_partida_id);
        $this->assertIsFloat($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(1,$resultado);
        errores::$error = false;
    }

    public function test_suma_descuento_partida(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_factura($this->link);
        $modelo = new liberator($modelo);


        $partidas = array();
        $partidas[0]['fc_partida_id'] = 1;

        $resultado = $modelo->suma_descuento_partida($partidas);
        $this->assertIsFloat($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(1,$resultado);
        errores::$error = false;
    }

    public function test_total(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_factura($this->link);
        //$modelo = new liberator($modelo);


        $fc_factura_id = 1;

        $resultado = $modelo->total($fc_factura_id);
        $this->assertIsFloat($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(0,$resultado);
        errores::$error = false;
    }


}

