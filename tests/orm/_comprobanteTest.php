<?php

namespace gamboamartin\facturacion\tests\orm;

use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\_comprobante;
use gamboamartin\facturacion\models\_email;
use gamboamartin\facturacion\models\_facturacion;
use gamboamartin\facturacion\models\fc_factura;
use gamboamartin\facturacion\tests\base_test;
use gamboamartin\test\liberator;
use gamboamartin\test\test;


use stdClass;


class _comprobanteTest extends test
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

    public function test_comprobante(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $comp = new _comprobante($this->link);
        //$modelo = new liberator($modelo);

        $resultado = $comp->comprobante(factura: array());
        $this->assertIsArray($resultado);
        $this->assertTrue(errores::$error);
        $this->assertEquals("Error la factura pasada no tiene registros",$resultado['mensaje_limpio']);
        errores::$error = false;


        $del = (new base_test())->del_org_empresa(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error('Error al eliminar',$del);
            print_r($error);
            exit;
        }

        $del = (new base_test())->del_com_producto(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error('Error al eliminar',$del);
            print_r($error);
            exit;
        }

        $del = (new base_test())->del_fc_factura(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error('Error al eliminar',$del);
            print_r($error);
            exit;
        }
        $del = (new base_test())->del_com_cliente(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error('Error al eliminar',$del);
            print_r($error);
            exit;
        }

        $alta_fc_factura = (new base_test())->alta_fc_factura(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error('Error al dar de alta factura',$alta_fc_factura);
            print_r($error);
            exit;
        }

        $alta = (new base_test())->alta_fc_partida(link: $this->link, fc_factura_id: 1);
        if(errores::$error){
            $error = (new errores())->error('Error al insertar',$alta);
            print_r($error);
            exit;
        }

        $factura = (new fc_factura($this->link))->get_factura(fc_factura_id: $alta_fc_factura->registro_id);
        if(errores::$error){
            $error = (new errores())->error('Error al obtener factura',$factura);
            print_r($error);
            exit;
        }

        $resultado = $comp->comprobante(factura: $factura);

        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(12,count($resultado));
        errores::$error = false;
    }

    public function test_limpia_monto(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $com = new _comprobante();
        $com = new liberator($com);

        $monto = "$1";
        $resultado = $com->limpia_monto(monto: $monto);
        $this->assertIsString($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(1,$resultado);
        errores::$error = false;

        $monto = 2;
        $resultado = $com->limpia_monto(monto: $monto);
        $this->assertIsString($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(2,$resultado);
        errores::$error = false;
    }
    public function test_monto_dos_dec(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $com = new _comprobante($this->link);
        //$modelo = new liberator($modelo);

        $monto = "$1";
        $resultado = $com->monto_dos_dec(monto: $monto);
        $this->assertIsString($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals("1.00",$resultado);
        errores::$error = false;

        $monto = 2;
        $resultado = $com->monto_dos_dec(monto: $monto);
        $this->assertIsString($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals("2.00",$resultado);
        errores::$error = false;
    }


}

