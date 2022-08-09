<?php
namespace tests\links\secciones;

use gamboamartin\errores\errores;
use gamboamartin\test\liberator;
use gamboamartin\test\test;
use JsonException;
use links\secciones\link_org_empresa;
use stdClass;


class link_org_empresaTest extends test {
    public errores $errores;
    private stdClass $paths_conf;
    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->errores = new errores();
    }

    /**
     * @throws JsonException
     */
    public function test_org_empresa_alta(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_GET['session_id'] = '1';
        $link = new link_org_empresa(-1);
        $link = new liberator($link);

        $resultado = $link->org_empresa_alta();


        $this->assertIsString($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals('./index.php?seccion=org_empresa&accion=alta', $resultado);


        errores::$error = false;
    }

    /**
     * @throws JsonException
     */
    public function test_org_empresa_ubicacion(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_GET['session_id'] = '1';
        $link = new link_org_empresa(-1);
        $link = new liberator($link);

        $resultado = $link->org_empresa_ubicacion(-1);
        $this->assertIsString($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals('./index.php?seccion=org_empresa&accion=ubicacion&registro_id=-1', $resultado);
        errores::$error = false;
    }







}

