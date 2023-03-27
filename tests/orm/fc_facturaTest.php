<?php
namespace gamboamartin\facturacion\tests\orm;


use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\fc_csd;
use gamboamartin\facturacion\tests\base_test;
use gamboamartin\facturacion\tests\base_test2;
use gamboamartin\organigrama\models\org_empresa;
use gamboamartin\organigrama\models\org_sucursal;
use gamboamartin\test\liberator;
use gamboamartin\test\test;
use gamboamartin\facturacion\models\fc_factura;


use stdClass;


class fc_facturaTest extends test {
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

    public function test_carga_descuento(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';



        $modelo = new fc_factura($this->link);
        $modelo = new liberator($modelo);




        $del = (new base_test())->del_cat_sat_metodo_pago($this->link);
        if(errores::$error){
            $error = (new errores())->error('Error al eliminar',$del);
            print_r($error);
            exit;
        }
        $del = (new base_test())->del_cat_sat_forma_pago($this->link);
        if(errores::$error){
            $error = (new errores())->error('Error al eliminar',$del);
            print_r($error);
            exit;
        }

        $del = (new base_test())->del_org_empresa($this->link);
        if(errores::$error){
            $error = (new errores())->error('Error al eliminar',$del);
            print_r($error);
            exit;
        }



        $alta = (new base_test())->alta_fc_partida($this->link);
        if(errores::$error){
            $error = (new errores())->error('Error al insertar partida',$alta);
            print_r($error);
            exit;
        }

        $descuento = 11;
        $partida = array();
        $partida['fc_partida_id'] = 1;
        $resultado = $modelo->carga_descuento($descuento, $partida);

        $this->assertIsFloat($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(11,$resultado);





        errores::$error = false;
    }

    public function test_comprobante(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_factura($this->link);
        $modelo = new liberator($modelo);

        $resultado = $modelo->comprobante(factura: array());
        $this->assertIsArray($resultado);
        $this->assertTrue(errores::$error);
        $this->assertEquals("Error la factura pasada no tiene registros",$resultado['mensaje_limpio']);
        errores::$error = false;

        $del = (new base_test())->del_fc_factura(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error('Error al eliminar',$del);
            print_r($error);
            exit;
        }

        $alta_fc_factura = (new base_test())->alta_fc_factura(link: $this->link, codigo: '999', id: 999);
        if(errores::$error){
            $error = (new errores())->error('Error al dar de alta factura',$alta_fc_factura);
            print_r($error);
            exit;
        }

        $factura = (new fc_factura($this->link))->get_factura(fc_factura_id: $alta_fc_factura->registro_id);
        if(errores::$error){
            $error = (new errores())->error('Error al obtener factura',$factura);
            print_r($error);
            exit;
        }

        $resultado = $modelo->comprobante(factura: $factura);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(10,count($resultado));
        errores::$error = false;
    }

    public function test_descuento_partida(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_factura($this->link);
        $modelo = new liberator($modelo);

        $del = (new base_test())->del_cat_sat_moneda($this->link,);
        if(errores::$error){
            $error = (new errores())->error('Error al eliminar',$del);
            print_r($error);
            exit;
        }

        $del = (new base_test())->del_cat_sat_metodo_pago($this->link,);
        if(errores::$error){
            $error = (new errores())->error('Error al eliminar',$del);
            print_r($error);
            exit;
        }

        $alta = (new base_test())->alta_fc_partida($this->link,);
        if(errores::$error){
            $error = (new errores())->error('Error al insertar',$alta);
            print_r($error);
            exit;
        }


        $fc_partida_id = 1;
        $resultado = $modelo->descuento_partida($fc_partida_id);

        $this->assertIsFloat($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(0,$resultado);
        errores::$error = false;
    }

    public function test_get_factura_descuento(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_factura($this->link);
        //$modelo = new liberator($modelo);



        $fc_factura_id = 1;

        $resultado = $modelo->get_factura_descuento($fc_factura_id);
        $this->assertIsFloat($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(0,$resultado);
        errores::$error = false;
    }

    public function test_get_factura_imp_trasladados(): void
    {
        errores::$error = false;

        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_factura($this->link);

        $del = (new base_test())->del_fc_factura(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error('Error al eliminar',$del);
            print_r($error);
            exit;
        }

        $alta_fc_factura = (new base_test())->alta_fc_factura(link: $this->link,id: 999);
        if(errores::$error){
            $error = (new errores())->error('Error al dar de alta factura',$alta_fc_factura);
            print_r($error);
            exit;
        }

        $resultado = $modelo->get_factura_imp_trasladados($alta_fc_factura->registro_id);
        $this->assertIsFloat($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(0,$resultado);
        errores::$error = false;
    }

    public function test_get_factura_sub_total(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_factura($this->link);

        $del = (new base_test())->del_fc_factura(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error('Error al eliminar',$del);
            print_r($error);
            exit;
        }

        $alta_fc_factura = (new base_test())->alta_fc_factura(link: $this->link,id: 999);
        if(errores::$error){
            $error = (new errores())->error('Error al dar de alta factura',$alta_fc_factura);
            print_r($error);
            exit;
        }

        $resultado = $modelo->get_factura_sub_total(fc_factura_id: $alta_fc_factura->registro_id);
        $this->assertIsFloat($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(0,$resultado);
        errores::$error = false;
    }

    public function test_get_factura_total(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';


        $modelo = new fc_factura($this->link);
        //$modelo = new liberator($modelo);

        $del = (new base_test())->del_org_empresa(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error('Error al eliminar',$del);
            print_r($error);
            exit;
        }


        $del = (new base_test())->del_cat_sat_factor(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error('Error al eliminar',$del);
            print_r($error);
            exit;
        }

        $alta = (new base_test())->alta_fc_conf_traslado(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error('Error al insertar',$alta);
            print_r($error);
            exit;
        }

        $alta = (new base_test())->alta_fc_conf_retenido(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error('Error al insertar',$alta);
            print_r($error);
            exit;
        }

        $alta = (new base_test())->alta_fc_partida(link: $this->link, cantidad: 2, valor_unitario: 2425.8);
        if(errores::$error){
            $error = (new errores())->error('Error al insertar',$alta);
            print_r($error);
            exit;
        }

        /**
         * CRITICA
         */
        $resultado = $modelo->get_factura_total(1);

        $this->assertIsFloat($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(5567.21, $resultado);


        errores::$error = false;
    }

    public function test_get_partidas(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_factura($this->link);
        $modelo = new liberator($modelo);

        $fc_factura_id = 1;
        $resultado = $modelo->get_partidas($fc_factura_id);


        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);


        errores::$error = false;
    }

    public function test_limpia_alta_factura(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $registro = array();
        $registro['descuento'] = 10;
        $modelo = new fc_factura($this->link);
        $modelo = new liberator($modelo);
        $resultado = $modelo->limpia_alta_factura($registro);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEmpty($resultado);
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

        $modelo = new fc_factura($this->link);
        $modelo = new liberator($modelo);

        $monto = "$1";
        $resultado = $modelo->limpia_monto(monto: $monto);
        $this->assertIsString($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(1,$resultado);
        errores::$error = false;

        $monto = 2;
        $resultado = $modelo->limpia_monto(monto: $monto);
        $this->assertIsString($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(2,$resultado);
        errores::$error = false;
    }

    public function test_limpia_si_existe(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_factura($this->link);
        $modelo = new liberator($modelo);

        $key = 'a';
        $registro = array();
        $registro['a'] = 'z';
        $resultado = $modelo->limpia_si_existe($key, $registro);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEmpty($resultado);
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

        $modelo = new fc_factura($this->link);
        $modelo = new liberator($modelo);

        $monto = "$1";
        $resultado = $modelo->monto_dos_dec(monto: $monto);
        $this->assertIsString($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals("1.00",$resultado);
        errores::$error = false;

        $monto = 2;
        $resultado = $modelo->monto_dos_dec(monto: $monto);
        $this->assertIsString($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals("2.00",$resultado);
        errores::$error = false;
    }


    public function test_sub_total(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_factura($this->link);
        //$modelo = new liberator($modelo);

        $del = (new base_test())->del_fc_factura(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error('Error al del', $del);
            print_r($error);
            exit;
        }


        $alta = (new base_test())->alta_fc_partida(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error('Error al insertar', $alta);
            print_r($error);
            exit;
        }

        $fc_partida_id = 1;
        $resultado = $modelo->sub_total($fc_partida_id);
        $this->assertIsFloat($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(1,$resultado);
        errores::$error = false;

    }


    public function test_sub_total_partida(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_factura($this->link);
        $modelo = new liberator($modelo);

        $fc_partida_id = 1;
        $resultado = $modelo->sub_total_partida($fc_partida_id);
        $this->assertIsFloat($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(1,$resultado);
        errores::$error = false;
    }

    public function test_suma_descuento_partida(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_factura($this->link);
        $modelo = new liberator($modelo);

        $del = (new base_test())->del_cat_sat_moneda($this->link,);
        if(errores::$error){
            $error = (new errores())->error('Error al eliminar',$del);
            print_r($error);
            exit;
        }

        $del = (new base_test())->del_cat_sat_metodo_pago($this->link,);
        if(errores::$error){
            $error = (new errores())->error('Error al eliminar',$del);
            print_r($error);
            exit;
        }

        $alta = (new base_test())->alta_fc_partida($this->link,);
        if(errores::$error){
            $error = (new errores())->error('Error al insertar',$alta);
            print_r($error);
            exit;
        }

        $partidas = array();
        $partidas[0]['fc_partida_id'] = 1;

        $resultado = $modelo->suma_descuento_partida($partidas);
        $this->assertIsFloat($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(0,$resultado);
        errores::$error = false;
    }

    public function test_suma_sub_total(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';



        $modelo = new fc_factura($this->link);
        $modelo = new liberator($modelo);

        $del = (new base_test())->del_fc_factura($this->link);
        if(errores::$error){
            $error = (new errores())->error('Error al eliminar',$del);
            print_r($error);
            exit;
        }

        $alta = (new base_test())->alta_fc_partida($this->link,);
        if(errores::$error){
            $error = (new errores())->error('Error al insertar',$alta);
            print_r($error);
            exit;
        }

        $subtotal = 10;
        $fc_partida['fc_partida_id'] = 1;
        $resultado = $modelo->suma_sub_total($fc_partida, $subtotal);
        $this->assertIsFloat($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(11,$resultado);
        errores::$error = false;
    }

    public function test_suma_sub_totales(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';



        $modelo = new fc_factura($this->link);
        $modelo = new liberator($modelo);

        $del = (new base_test())->del_fc_factura($this->link,);
        if(errores::$error){
            $error = (new errores())->error('Error al eliminar',$del);
            print_r($error);
            exit;
        }

        $alta = (new base_test())->alta_fc_partida($this->link,);
        if(errores::$error){
            $error = (new errores())->error('Error al insertar',$alta);
            print_r($error);
            exit;
        }
        $fc_partidas = array();
        $fc_partidas[0]['fc_partida_id'] = 1;

        $resultado = $modelo->suma_sub_totales($fc_partidas);
        $this->assertIsFloat($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(1.0,$resultado);
        errores::$error = false;

    }

    public function test_total(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_factura($this->link);
        //$modelo = new liberator($modelo);

        $del = (new base_test())->del_cat_sat_moneda($this->link,);
        if(errores::$error){
            $error = (new errores())->error('Error al eliminar',$del);
            print_r($error);
            exit;
        }

        $del = (new base_test())->del_cat_sat_metodo_pago($this->link,);
        if(errores::$error){
            $error = (new errores())->error('Error al eliminar',$del);
            print_r($error);
            exit;
        }

        $fc_factura_id = 1;

        $resultado = $modelo->total($fc_factura_id);

        $this->assertIsArray($resultado);
        $this->assertTrue(errores::$error);
        $this->assertStringContainsStringIgnoringCase('Error al obtener sub total',$resultado['mensaje']);
        errores::$error = false;



        $alta = (new base_test())->alta_fc_partida($this->link);
        if(errores::$error){
            $error = (new errores())->error('error al insertar', $alta);
            print_r($error);
            exit;
        }

        $resultado = $modelo->total($fc_factura_id);


        $this->assertIsFloat($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(1.0,$resultado);
        errores::$error = false;
    }


}

