<?php

namespace gamboamartin\facturacion\tests\orm;

use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\_email;
use gamboamartin\facturacion\models\_facturacion;
use gamboamartin\facturacion\models\fc_partida;
use gamboamartin\facturacion\models\fc_relacion;
use gamboamartin\facturacion\models\fc_traslado;
use gamboamartin\test\liberator;
use gamboamartin\test\test;


use stdClass;


class _partidaTest extends test
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

    public function test_integra_relacionado(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_partida(link: $this->link);
        $modelo = new liberator($modelo);

        $modelo_impuesto = new fc_traslado(link: $this->link);

        $resultado = $modelo->tabla_impuesto($modelo_impuesto);

        $this->assertIsString($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals('fc_traslado', $resultado);
        errores::$error = false;
    }





}

