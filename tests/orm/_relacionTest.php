<?php

namespace gamboamartin\facturacion\tests\orm;

use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\_email;
use gamboamartin\facturacion\models\_facturacion;
use gamboamartin\facturacion\models\fc_relacion;
use gamboamartin\facturacion\models\fc_relacion_nc;
use gamboamartin\facturacion\models\fc_uuid_nc;
use gamboamartin\test\liberator;
use gamboamartin\test\test;


use stdClass;


class _relacionTest extends test
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

    public function test_ajusta_relacionados(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_relacion_nc(link: $this->link);
        $modelo = new liberator($modelo);

        $cat_sat_tipo_relacion_codigo = '';
        $modelo_uuid_ext = new fc_uuid_nc(link: $this->link);
        $relacionados = array();
        $row_relacion = array();
        $row_relacion['fc_relacion_nc_id'] = 1;

        $resultado = $modelo->ajusta_relacionados($cat_sat_tipo_relacion_codigo, $modelo_uuid_ext, $relacionados, $row_relacion);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        errores::$error = false;

    }

    public function test_integra_relacion_nc(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_relacion(link: $this->link);
        $modelo = new liberator($modelo);

        $cat_sat_tipo_relacion_codigo = 'a';
        $relacionados = array();
        $filtro = array();
        $resultado = $modelo->integra_relacion_nc($cat_sat_tipo_relacion_codigo, $filtro, $relacionados);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        errores::$error = false;
    }

    public function test_integra_relacionado(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
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

    public function test_integra_relacionados(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_relacion(link: $this->link);
        $modelo = new liberator($modelo);

        $cat_sat_tipo_relacion_codigo = 'a';
        $name_entidad = 'v';
        $relacionados = array();
        $fc_rows_relacionadas[]['v_uuid'] = 'z';
        $resultado = $modelo->integra_relacionados($cat_sat_tipo_relacion_codigo, $fc_rows_relacionadas, $name_entidad, $relacionados);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals('z', $resultado['a'][0]);
        errores::$error = false;

    }

    public function test_relaciones_externas(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_relacion_nc(link: $this->link);
        $modelo = new liberator($modelo);

        $modelo_uuid_ext = new fc_uuid_nc(link: $this->link);
        $row_relacion = array();
        $row_relacion['fc_relacion_nc_id'] = 1;

        $resultado = $modelo->relaciones_externas($modelo_uuid_ext, $row_relacion);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);

        errores::$error = false;

    }



}

