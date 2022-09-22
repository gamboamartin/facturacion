<?php
namespace tests\orm;

use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\fc_partida;
use gamboamartin\test\liberator;
use gamboamartin\test\test;

use stdClass;


class fc_partidaTest extends test {
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

    public function test_valida_partida_alta(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_partida($this->link);
        $modelo = new liberator($modelo);

        $registro = array();
        $resultado = $modelo->valida_partida_alta($registro);

        $this->assertIsArray($resultado);
        $this->assertTrue(errores::$error);
        $this->assertStringContainsStringIgnoringCase('Error al validar registro', $resultado['mensaje']);
        errores::$error = false;

        $registro = array();
        $registro['fc_factura_id'] = 1;
        $resultado = $modelo->valida_partida_alta($registro);
        $this->assertIsArray($resultado);
        $this->assertTrue(errores::$error);
        $this->assertStringContainsStringIgnoringCase('Error al validar registro', $resultado['mensaje']);
        errores::$error = false;


        $registro = array();
        $registro['fc_factura_id'] = 1;
        $registro['com_producto_id'] = 1;
        $resultado = $modelo->valida_partida_alta($registro);
        $this->assertIsBool($resultado);
        $this->assertNotTrue(errores::$error);

        errores::$error = false;
    }


}

