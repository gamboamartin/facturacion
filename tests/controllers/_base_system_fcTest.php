<?php
namespace gamboamartin\facturacion\tests\controllers;


use gamboamartin\errores\errores;
use gamboamartin\facturacion\controllers\controlador_fc_factura;
use gamboamartin\facturacion\controllers\controlador_fc_nota_credito;
use gamboamartin\facturacion\controllers\pdf;
use gamboamartin\facturacion\models\fc_csd;
use gamboamartin\facturacion\tests\base_test;
use gamboamartin\organigrama\models\org_empresa;
use gamboamartin\organigrama\models\org_sucursal;
use gamboamartin\test\liberator;
use gamboamartin\test\test;
use gamboamartin\facturacion\models\fc_factura;


use stdClass;


class _base_system_fcTest extends test {
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

    public function test_parents(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'fc_factura';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';


        $ctl = new controlador_fc_factura(link: $this->link, paths_conf: $this->paths_conf);
        $ctl = new liberator($ctl);



        $resultado = $ctl->parents();
        //print_r($resultado);exit;
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertIsObject($resultado[0]);
        $this->assertIsObject($resultado[1]);
        $this->assertIsObject($resultado[2]);
        $this->assertIsObject($resultado[3]);
        $this->assertIsObject($resultado[4]);
        $this->assertIsObject($resultado[5]);
        $this->assertIsObject($resultado[6]);
        $this->assertIsObject($resultado[7]);
        $this->assertIsObject($resultado[8]);
        $this->assertIsObject($resultado[9]);
        $this->assertEquals('cat_sat_uso_cfdi',$resultado[4]->tabla);
        errores::$error = false;
    }

    public function test_row_relacionada(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'fc_factura';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';



        $ctl = new controlador_fc_nota_credito(link: $this->link, paths_conf: $this->paths_conf);
        $ctl = new liberator($ctl);

        $key_modelo_base_id = 'a';
        $key_modelo_rel_id = 'b';
        $registro_entidad_id = 1;
        $relacion_id = 1;
        $resultado = $ctl->row_relacionada(array(),$key_modelo_base_id, $key_modelo_rel_id, $registro_entidad_id, $relacion_id);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(1,$resultado['b']);
        $this->assertEquals(1,$resultado['a']);
        errores::$error = false;
    }

    public function test_valida_data_relacion(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'fc_factura';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';



        $ctl = new controlador_fc_factura(link: $this->link, paths_conf: $this->paths_conf);
        $ctl = new liberator($ctl);

        $key_modelo_base_id = 'a';
        $key_modelo_rel_id = 'v';
        $registro_entidad_id = 1;
        $relacion_id = 1;
        $resultado = $ctl->valida_data_relacion($key_modelo_base_id, $key_modelo_rel_id, $registro_entidad_id, $relacion_id);
        //print_r($resultado);exit;
        $this->assertIsBool($resultado);
        $this->assertNotTrue(errores::$error);
        errores::$error = false;
    }



}

