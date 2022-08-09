<?php
namespace tests\links\secciones;

use gamboamartin\errores\errores;
use gamboamartin\organigrama\controllers\controlador_org_empresa;
use gamboamartin\test\liberator;
use gamboamartin\test\test;
use JsonException;
use links\secciones\link_org_empresa;
use models\base\limpieza;
use models\org_empresa;
use stdClass;


class limpiezaTest extends test {
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

    /**
     * @throws JsonException
     */
    public function test_init_data_base_org_empresa(): void
    {
        errores::$error = false;

        $lim = new limpieza();
        $lim = new liberator($lim);

        $registro = array();
        $registro['razon_social'] = 'a';
        $registro['rfc'] = 'b';

        $resultado = $lim->init_data_base_org_empresa($registro);


        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals('a',$resultado['razon_social']);
        $this->assertEquals('b',$resultado['rfc']);
        $this->assertEquals('a',$resultado['descripcion']);
        $this->assertEquals('b',$resultado['codigo_bis']);
        $this->assertEquals('a',$resultado['descripcion_select']);
        $this->assertEquals('a',$resultado['alias']);

        errores::$error = false;
    }

    public function test_limpia_foraneas_org_empresa(): void
    {
        errores::$error = false;

        $lim = new limpieza();
        $lim = new liberator($lim);

        $registro = array();
        $registro['razon_social'] = 'a';
        $registro['rfc'] = 'b';
        $registro['cat_sat_regimen_fiscal_id'] = 'b';

        $resultado = $lim->limpia_foraneas_org_empresa($registro);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals('b',$resultado['cat_sat_regimen_fiscal_id']);

        errores::$error = false;

        $registro = array();
        $registro['razon_social'] = 'a';
        $registro['rfc'] = 'b';
        $registro['cat_sat_regimen_fiscal_id'] = '1';

        $resultado = $lim->limpia_foraneas_org_empresa($registro);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(1,$resultado['cat_sat_regimen_fiscal_id']);

        errores::$error = false;

        $registro = array();
        $registro['razon_social'] = 'a';
        $registro['rfc'] = 'b';
        $registro['cat_sat_regimen_fiscal_id'] = '-1';

        $resultado = $lim->limpia_foraneas_org_empresa($registro);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertArrayNotHasKey('cat_sat_regimen_fiscal_id',$resultado);
        errores::$error = false;

    }








}

