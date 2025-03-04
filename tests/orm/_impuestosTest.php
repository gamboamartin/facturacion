<?php

namespace gamboamartin\facturacion\tests\orm;

use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\_impuestos;
use gamboamartin\facturacion\models\fc_traslado;
use gamboamartin\facturacion\tests\base_test;

use gamboamartin\test\liberator;
use gamboamartin\test\test;
use stdClass;

class _impuestosTest extends test
{

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

    public function test_acumulado_global_imp(): void
    {
        errores::$error = false;

        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $imp = new _impuestos();
        $imp = new liberator($imp);

        $global_imp = array();
        $impuesto = array('key_importe'=>10,'fc_partida_importe_con_descuento'=>60);
        $key_gl = 'key_gl';
        $key_importe = 'key_importe';

        $global_imp[$key_gl] = new stdClass();
        $global_imp[$key_gl]->base = 250;

        $resultado = $imp->acumulado_global_imp($global_imp,$impuesto,$key_gl,$key_importe);
        //print_r($resultado);exit;

        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(310,$resultado->base_ac);
        $this->assertEquals(10,$resultado->importe_ac);

        errores::$error = false;


    }

    public function test_acumulado_global_impuesto(): void
    {
        errores::$error = false;

        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $imp = new _impuestos();
        $imp = new liberator($imp);


        $impuesto = array('fc_partida_importe_con_descuento'=>150);
        $key_gl = 'key_gl';
        $key_importe = 'key_importe';

        $global_imp = array();
        $global_imp['key_gl'] = new stdClass();
        $global_imp['key_gl']->base = 250;
        $resultado = $imp->acumulado_global_impuesto($global_imp,$impuesto,$key_gl,$key_importe);

        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(400,$resultado['key_gl']->base);


        errores::$error = false;


    }

    public function test_carga_global(): void
    {
        errores::$error = false;

        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $imp = new _impuestos();
        $imp = new liberator($imp);

        $data_imp = new stdClass();
        $impuestos = array();
        $imp_global = array();
        $key_gl = 'key_gl';

        $resultado = $imp->carga_global($data_imp,$impuestos,$imp_global,$key_gl);


        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(0.0,$resultado['key_gl']->base);

        errores::$error = false;


    }



    public function test_impuesto_limpia(): void
    {
        errores::$error = false;

        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $imp = new _impuestos();
        $imp = new liberator($imp);

        $impuesto = array();
        $impuesto['cat_sat_tipo_impuesto_id'] = 'x ';
        $impuesto['cat_sat_tipo_factor_id'] = 'r ';
        $impuesto['cat_sat_factor_id'] = 'z ';
        $resultado = $imp->impuesto_limpia($impuesto);

        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals('x',$resultado['cat_sat_tipo_impuesto_id']);
        $this->assertEquals('r',$resultado['cat_sat_tipo_factor_id']);
        $this->assertEquals('z',$resultado['cat_sat_factor_id']);

        errores::$error = false;


    }

    public function test_impuesto_validado(): void
    {
        errores::$error = false;

        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $imp = new _impuestos();
        $imp = new liberator($imp);

        $impuesto = array();
        $impuesto['cat_sat_tipo_factor_id'] = ' 1';
        $impuesto['cat_sat_factor_id'] = ' 2';
        $impuesto['cat_sat_tipo_impuesto_id'] = ' 1';
        $resultado = $imp->impuesto_validado($impuesto);

        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(1,$resultado['cat_sat_tipo_factor_id']);
        $this->assertEquals(2,$resultado['cat_sat_factor_id']);
        $this->assertEquals(1,$resultado['cat_sat_tipo_impuesto_id']);

        errores::$error = false;


    }

    public function test_impuestos_globales(): void
    {
        errores::$error = false;

        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $imp = new _impuestos();
        //$imp = new liberator($imp);

        $impuestos = new stdClass();
        $global_imp = array();
        $key_importe = 'key_importe';
        $name_tabla_partida = 'name_tabla_partida';
        $impuestos->registros = array();
        $impuestos->registros[0] = array();
        $impuestos->registros[0]['cat_sat_tipo_factor_id'] = 7;
        $impuestos->registros[0]['cat_sat_factor_id'] = 7;
        $impuestos->registros[0]['cat_sat_tipo_impuesto_id'] = 7;

        $resultado = $imp->impuestos_globales($impuestos,$global_imp,$key_importe,$name_tabla_partida);


        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(0.00,$resultado['7.7.7']->base);

        errores::$error = false;


    }

    public function test_init_base(): void
    {
        errores::$error = false;

        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $imp = new _impuestos();
        $imp = new liberator($imp);

        $global_imp = array();
        $key_gl = '$key_gl';
        $key_importe = '$key_importe';

        $resultado = $imp->init_base($global_imp,$key_gl,$key_importe);

        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);

        errores::$error = false;


    }

    public function test_init_globales(): void
    {
        errores::$error = false;

        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $imp = new _impuestos();
        $imp = new liberator($imp);

        $global_nodo = array();
        $impuesto = array();
        $key = 'key';
        $key_importe = 'key_importe';
        $name_tabla_partida = 'name_tabla_partida';

        $resultado = $imp->init_globales($global_nodo, $impuesto,$key,$key_importe,$name_tabla_partida);


        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(0.0,$resultado->base);

        errores::$error = false;


    }

    public function test_init_imp_global(): void
    {
        errores::$error = false;

        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $imp = new _impuestos();
        $imp = new liberator($imp);

        $global_nodo = array();
        $impuesto = array();
        $key_importe = 'key_importe';
        $name_tabla_partida = 'name_tabla_partida';
        $key_gl = 'key_gl';

        $resultado = $imp->init_imp_global($global_nodo,$impuesto,$key_gl,$key_importe,$name_tabla_partida);

        //print_r($resultado);exit;
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(0.0,$resultado['key_gl']->base);
        $this->assertEquals(0.0,$resultado['key_gl']->tasa_o_cuota);

        errores::$error = false;


    }

    public function test_integra_ac_impuesto(): void
    {
        errores::$error = false;

        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $imp = new _impuestos();
        $imp = new liberator($imp);

        $global_imp = array();
        $impuesto = array();
        $key_gl = 'key_gl';
        $key_importe = 'key_importe';
        $name_tabla_partida = 'name_tabla_partida';

        $resultado = $imp->integra_ac_impuesto($global_imp,$impuesto,$key_gl,$key_importe, $name_tabla_partida);

        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(0.0,$resultado['key_gl']->base);
        $this->assertEquals(0.0,$resultado['key_gl']->tasa_o_cuota);

        errores::$error = false;


    }

    public function test_key_gl(): void
    {
        errores::$error = false;

        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $imp = new _impuestos();
        $imp = new liberator($imp);


        $impuesto = array();
        $impuesto['cat_sat_tipo_factor_id'] = 1;
        $impuesto['cat_sat_factor_id'] = 1;
        $impuesto['cat_sat_tipo_impuesto_id'] = 1;
        $resultado = $imp->key_gl($impuesto);

        $this->assertIsString($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals("1.1.1",$resultado);

        errores::$error = false;


    }

    public function test_maqueta_impuesto(): void
    {
        errores::$error = false;

        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $imp = new _impuestos();
        //$imp = new liberator($imp);

        $impuestos = new stdClass();
        $key_importe_impuesto = 'kii';
        $name_tabla_partida = 'ntp';
        $impuestos->registros = array();
        $impuestos->registros[0] = array();
        $resultado = $imp->maqueta_impuesto($impuestos,$key_importe_impuesto,$name_tabla_partida);

        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(0.0,$resultado[0]->base);

        errores::$error = false;


    }
    public function test_tiene_tasa(): void
    {
        errores::$error = false;

        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $imp = new _impuestos();
        $imp = new liberator($imp);

        $row_entidad = array();
        $resultado = $imp->tiene_tasa($row_entidad);
        $this->assertIsBool($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertNotTrue($resultado);

        errores::$error = false;

        $row_entidad = array();
        $row_entidad['traslados'] = '';
        $resultado = $imp->tiene_tasa($row_entidad);
        $this->assertIsArray($resultado);
        $this->assertTrue(errores::$error);
        $this->assertEquals('Error $row_entidad[traslados] debe ser un array',$resultado['mensaje_limpio']);
        errores::$error = false;


        $row_entidad = array();
        $row_entidad['traslados'] = array();
        $resultado = $imp->tiene_tasa($row_entidad);
        $this->assertIsBool($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertNotTrue($resultado);

        errores::$error = false;


        $row_entidad = array();
        $row_entidad['traslados'][0] = '';
        $resultado = $imp->tiene_tasa($row_entidad);
        $this->assertIsArray($resultado);
        $this->assertTrue(errores::$error);
        $this->assertEquals('Error al validar impuesto',$resultado['mensaje_limpio']);
        errores::$error = false;



        $row_entidad = array();
        $row_entidad['traslados'][0] = new stdClass();
        $resultado = $imp->tiene_tasa($row_entidad);
        $this->assertIsArray($resultado);
        $this->assertTrue(errores::$error);
        $this->assertEquals('Error al validar impuesto',$resultado['mensaje_limpio']);
        errores::$error = false;


        $row_entidad = array();
        $row_entidad['traslados'][0] = new stdClass();
        $row_entidad['traslados'][0]->tipo_factor = 'Exento';
        $resultado = $imp->tiene_tasa($row_entidad);
        $this->assertIsBool($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertNotTrue($resultado);

        errores::$error = false;

        $row_entidad = array();
        $row_entidad['traslados'][0] = new stdClass();
        $row_entidad['traslados'][0]->tipo_factor = 'Exento';
        $row_entidad['traslados'][1] = new stdClass();
        $resultado = $imp->tiene_tasa($row_entidad);
        $this->assertIsArray($resultado);
        $this->assertTrue(errores::$error);
        $this->assertEquals('Error al validar impuesto',$resultado['mensaje_limpio']);
        errores::$error = false;


        $row_entidad = array();
        $row_entidad['traslados'][0] = new stdClass();
        $row_entidad['traslados'][0]->tipo_factor = 'Exento';
        $row_entidad['traslados'][1] = new stdClass();
        $row_entidad['traslados'][1]->tipo_factor = 'Tasa';
        $resultado = $imp->tiene_tasa($row_entidad);
        $this->assertIsBool($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertTrue($resultado);

        errores::$error = false;

    }

    public function test_valida_vacio(): void
    {
        errores::$error = false;

        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $imp = new _impuestos();
        $imp = new liberator($imp);

        $impuesto = array();
        $impuesto['cat_sat_tipo_factor_id'] = 'a';
        $impuesto['cat_sat_factor_id'] = 'v';
        $impuesto['cat_sat_tipo_impuesto_id'] = 'c';

        $resultado = $imp->valida_vacio($impuesto);

        $this->assertIsBool($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertTrue($resultado);

        errores::$error = false;


    }

    public function test_valida_base(): void
    {
        errores::$error = false;

        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $imp = new _impuestos();
        $imp = new liberator($imp);

        $key_gl = '$key_gl';
        $key_importe = '$key_importe';
        $name_tabla_partida = '$name_tabla_partida';
        $resultado = $imp->valida_base($key_gl, $key_importe, $name_tabla_partida);

        $this->assertIsBool($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertTrue($resultado);

        errores::$error = false;


    }

    public function test_valida_foraneas(): void
    {
        errores::$error = false;

        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $imp = new _impuestos();
        $imp = new liberator($imp);

        $impuesto = array();
        $impuesto['cat_sat_tipo_factor_id'] = ' 1 ';
        $impuesto['cat_sat_factor_id'] = ' 3 ';
        $impuesto['cat_sat_tipo_impuesto_id'] = ' 4 ';

        $resultado = $imp->valida_foraneas($impuesto);

        $this->assertIsBool($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertTrue($resultado);

        errores::$error = false;


    }

    public function test_valida_keys_existentes(): void
    {
        errores::$error = false;

        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $imp = new _impuestos();
        $imp = new liberator($imp);

        $impuesto = array();
        $impuesto['cat_sat_tipo_factor_id'] = '-1';
        $impuesto['cat_sat_factor_id'] = '-1';
        $impuesto['cat_sat_tipo_impuesto_id'] = '-1';
        $resultado = $imp->valida_keys_existentes($impuesto);

        $this->assertIsBool($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertTrue($resultado);

        errores::$error = false;


    }

    public function test_valida_negativo(): void
    {
        errores::$error = false;

        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $imp = new _impuestos();
        $imp = new liberator($imp);

        $impuesto = array();
        $impuesto['cat_sat_tipo_factor_id'] = 1;
        $impuesto['cat_sat_factor_id'] = 1;
        $impuesto['cat_sat_tipo_impuesto_id'] = 1;

        $resultado = $imp->valida_negativo($impuesto);

        $this->assertIsBool($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertTrue($resultado);

        errores::$error = false;


    }
    public function test_valida_numeric(): void
    {
        errores::$error = false;

        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $imp = new _impuestos();
        $imp = new liberator($imp);

        $impuesto = array();
        $impuesto['cat_sat_tipo_factor_id'] = '1';
        $impuesto['cat_sat_factor_id'] = '2';
        $impuesto['cat_sat_tipo_impuesto_id'] = '3';

        $resultado = $imp->valida_numeric($impuesto);

        $this->assertIsBool($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertTrue($resultado);

        errores::$error = false;


    }


}

