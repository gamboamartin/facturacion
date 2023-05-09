<?php

namespace gamboamartin\facturacion\tests\orm;

use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\_email;
use gamboamartin\facturacion\models\_facturacion;
use gamboamartin\facturacion\models\fc_relacion;
use gamboamartin\test\liberator;
use gamboamartin\test\test;


use stdClass;


class _relacionTest extends test
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

        $modelo = new fc_relacion(link: $this->link);
        $modelo = new liberator($modelo);

        $cat_sat_tipo_relacion_codigo = 'a';
        $name_entidad = 'b';
        $relacionados = array();
        $row_relacionada['b_uuid'] = 'a';
        $resultado = $modelo->integra_relacionado($cat_sat_tipo_relacion_codigo, $name_entidad, $relacionados, $row_relacionada);

        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals('a', $resultado['a'][0]);
        errores::$error = false;
    }



}

