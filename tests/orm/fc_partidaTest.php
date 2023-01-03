<?php

namespace gamboamartin\facturacion\tests\orm;

use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\fc_partida;
use gamboamartin\facturacion\tests\base_test;
use gamboamartin\facturacion\tests\base_test2;
use gamboamartin\test\test;
use stdClass;

class fc_partidaTest extends test
{

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

    public function test_subtotal_partida(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_partida($this->link);
        //$modelo = new liberator($modelo);

        $fc_partida_id = -1;
        $resultado = $modelo->subtotal_partida(fc_partida_id: $fc_partida_id);
        $this->assertIsArray($resultado);
        $this->assertTrue(errores::$error);
        $this->assertEquals('<b><span style="color:red">Error el id de la partida es incorrecto</span></b>',
            $resultado['mensaje']);
        errores::$error = false;

        $fc_partida_id = (new base_test2())->alta_fc_partida(link: $this->link, id: 999);
        if (errores::$error) {
            $error = (new errores())->error('Error al obtener id de la partida', $fc_partida_id);
            print_r($error);
            exit;
        }

        $resultado = $modelo->subtotal_partida(fc_partida_id: $fc_partida_id);
        $this->assertIsFloat($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(1, $resultado);
        errores::$error = false;
    }

    public function test_total_partida(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_partida($this->link);
        //$modelo = new liberator($modelo);

        $fc_partida_id = -1;
        $resultado = $modelo->subtotal_partida(fc_partida_id: $fc_partida_id);
        $this->assertIsArray($resultado);
        $this->assertTrue(errores::$error);
        $this->assertEquals('<b><span style="color:red">Error el id de la partida es incorrecto</span></b>',
            $resultado['mensaje']);
        errores::$error = false;

        $fc_partida_id = (new base_test2())->alta_fc_partida(link: $this->link, id: 999);
        if (errores::$error) {
            $error = (new errores())->error('Error al obtener id de la partida', $fc_partida_id);
            print_r($error);
            exit;
        }

        $resultado = $modelo->total_partida(fc_partida_id: $fc_partida_id);
        $this->assertIsFloat($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(1, $resultado);
        errores::$error = false;
    }


}

