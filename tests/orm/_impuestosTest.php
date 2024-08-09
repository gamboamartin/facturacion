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


}

