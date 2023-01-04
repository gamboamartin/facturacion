<?php

namespace gamboamartin\facturacion\tests\orm;

use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\_facturacion;
use gamboamartin\test\liberator;
use gamboamartin\test\test;


use stdClass;


class _facturacionTest extends test
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

    public function test_fc_partida_importe(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new _facturacion();
        $modelo = new liberator($modelo);

        $salida = "ROUND((ROUND(IFNULL(fc_partida.cantidad,0),2) * ";
        $salida .= "ROUND(IFNULL(fc_partida.valor_unitario,0),2)),2)";
        $resultado = $modelo->fc_partida_importe();
        $this->assertIsString($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals($salida, $resultado);
        errores::$error = false;
    }

    public function test_fc_partida_importe_con_descuento(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new _facturacion();
        $modelo = new liberator($modelo);

        $salida = "ROUND((ROUND((ROUND(IFNULL(fc_partida.cantidad,0),2) * ";
        $salida .= "ROUND(IFNULL(fc_partida.valor_unitario,0),2)),2) - ";
        $salida .= "ROUND(IFNULL(fc_partida.descuento,0),2)),2)";
        $resultado = $modelo->fc_partida_importe_con_descuento();
        $this->assertIsString($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals($salida, $resultado);
        errores::$error = false;
    }
}

