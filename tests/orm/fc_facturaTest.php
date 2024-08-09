<?php
namespace gamboamartin\facturacion\tests\orm;


use gamboamartin\cat_sat\models\cat_sat_metodo_pago;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\fc_csd;
use gamboamartin\facturacion\models\fc_cuenta_predial;
use gamboamartin\facturacion\models\fc_factura_relacionada;
use gamboamartin\facturacion\models\fc_partida;
use gamboamartin\facturacion\models\fc_relacion;
use gamboamartin\facturacion\models\fc_retenido;
use gamboamartin\facturacion\models\fc_traslado;
use gamboamartin\facturacion\models\fc_uuid_fc;
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
    public function __construct(?string $name = null)
    {
        parent::__construct($name);
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
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';


        $modelo = new fc_factura($this->link);
        $modelo = new liberator($modelo);



        $del = (new base_test())->del_org_empresa($this->link);
        if(errores::$error){
            $error = (new errores())->error('Error al eliminar',$del);
            print_r($error);
            exit;
        }

        $del = (new base_test())->del_com_producto($this->link);
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
        $modelo_partida = (new fc_partida(link: $this->link));
        $resultado = $modelo->carga_descuento($descuento, $modelo_partida, $partida);

        $this->assertIsFloat($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(11,$resultado);



        errores::$error = false;
    }



    public function test_descuento_partida(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_factura($this->link);
        $modelo = new liberator($modelo);


        $del = (new base_test())->del_fc_partida(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error('Error al del',$del);
            print_r($error);
            exit;
        }

        $alta = (new base_test())->alta_fc_partida(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error('Error al insertar',$alta);
            print_r($error);
            exit;
        }


        $fc_partida_id = 1;
        $modelo_partida = new fc_partida(link: $this->link);
        $resultado = $modelo->descuento_partida($modelo_partida, $fc_partida_id);

        $this->assertIsFloat($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(0,$resultado);
        errores::$error = false;
    }

    public function test_get_factura(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
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
        $del = (new base_test())->del_cat_sat_tipo_factor(link: $this->link);
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
        $modelo_partida = new fc_partida(link: $this->link);
        $modelo_predial = new fc_cuenta_predial(link: $this->link);
        $modelo_relacion = new fc_relacion(link: $this->link);
        $modelo_relacionada = new fc_factura_relacionada(link: $this->link);
        $modelo_retencion = new fc_retenido(link: $this->link);
        $modelo_traslado = new fc_traslado(link: $this->link);
        $modelo_uuid_ext = new fc_uuid_fc(link: $this->link);
        $registro_id = 1;
        $resultado = $modelo->get_factura(modelo_partida: $modelo_partida, modelo_predial: $modelo_predial,
            modelo_relacion: $modelo_relacion, modelo_relacionada: $modelo_relacionada,
            modelo_retencion: $modelo_retencion, modelo_traslado: $modelo_traslado,
            modelo_uuid_ext: $modelo_uuid_ext, registro_id: $registro_id);

        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(5567.21, $resultado['fc_factura_total']);

        $del = (new base_test())->del_fc_partida(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error('Error al insertar',$del);
            print_r($error);
            exit;
        }

        $alta = (new base_test())->alta_fc_partida(link: $this->link, codigo: 1, cantidad: 1, id: 1, valor_unitario: 2830.0);
        if(errores::$error){
            $error = (new errores())->error('Error al insertar',$alta);
            print_r($error);
            exit;
        }
        $alta = (new base_test())->alta_fc_partida(link: $this->link, codigo: 2, cantidad: 1, id: 2, valor_unitario: 380);
        if(errores::$error){
            $error = (new errores())->error('Error al insertar',$alta);
            print_r($error);
            exit;
        }
        $alta = (new base_test())->alta_fc_partida(link: $this->link, codigo: 3, cantidad: 1, id: 3, valor_unitario: 190);
        if(errores::$error){
            $error = (new errores())->error('Error al insertar',$alta);
            print_r($error);
            exit;
        }
        $alta = (new base_test())->alta_fc_partida(link: $this->link, codigo: 4, cantidad: 1, id: 4, valor_unitario: 1699);
        if(errores::$error){
            $error = (new errores())->error('Error al insertar',$alta);
            print_r($error);
            exit;
        }

        $modelo_partida = new fc_partida(link: $this->link);
        $modelo_predial = new fc_cuenta_predial(link: $this->link);
        $modelo_relacion = new fc_relacion(link: $this->link);
        $modelo_relacionada = new fc_factura_relacionada(link: $this->link);
        $modelo_retencion = new fc_retenido(link: $this->link);
        $modelo_uuid_ext = new fc_uuid_fc(link: $this->link);
        $modelo_traslado = new fc_traslado(link: $this->link);
        $registro_id = 1;
        $resultado = $modelo->get_factura(modelo_partida: $modelo_partida, modelo_predial: $modelo_predial,
            modelo_relacion: $modelo_relacion, modelo_relacionada: $modelo_relacionada,
            modelo_retencion: $modelo_retencion, modelo_traslado: $modelo_traslado,
            modelo_uuid_ext: $modelo_uuid_ext, registro_id: $registro_id);


        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(5099, $resultado['fc_factura_sub_total_base']);
        $this->assertEquals(5099, $resultado['fc_factura_sub_total']);
        $this->assertEquals(815.84, $resultado['fc_factura_total_traslados']);
        $this->assertEquals(63.75, round($resultado['fc_factura_total_retenciones'],2));
        $this->assertEquals(5851.09, $resultado['fc_factura_total']);


        errores::$error = false;
    }
    public function test_get_factura_descuento(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
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

        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_factura($this->link);

        $del = (new base_test())->del_org_empresa(link: $this->link);
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

        $alta_fc_factura = (new base_test())->alta_fc_factura(link: $this->link, id: 999);
        if(errores::$error){
            $error = (new errores())->error('Error al dar de alta factura',$alta_fc_factura);
            print_r($error);
            exit;
        }


        $resultado = $modelo->get_factura_imp_trasladados(999);

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
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_factura($this->link);

        $del = (new base_test())->del_org_sucursal(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error('Error al eliminar',$del);
            print_r($error);
            exit;
        }

        $alta_fc_factura = (new base_test())->alta_fc_factura(link: $this->link, id: 999);
        if(errores::$error){
            $error = (new errores())->error('Error al dar de alta factura',$alta_fc_factura);
            print_r($error);
            exit;
        }

        $resultado = $modelo->get_factura_sub_total(registro_id: $alta_fc_factura->registro_id);
        $this->assertIsFloat($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(0,$resultado);


        $del = (new base_test())->del_org_empresa(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error('Error al eliminar',$del);
            print_r($error);
            exit;
        }


        $del = (new base_test())->del_cat_sat_tipo_factor(link: $this->link);
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
        $resultado = $modelo->get_factura_sub_total(1);
        $this->assertIsFloat($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(4851.6,$resultado);

        $del = (new base_test())->del_org_empresa(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error('Error al eliminar',$del);
            print_r($error);
            exit;
        }


        $del = (new base_test())->del_cat_sat_tipo_factor(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error('Error al eliminar',$del);
            print_r($error);
            exit;
        }


        /**
         * CRITICA
         */

        $alta = (new base_test())->alta_fc_factura(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error('Error al insertar',$alta);
            print_r($error);
            exit;
        }

        $resultado = $modelo->get_factura_sub_total(1);
        $this->assertIsFloat($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(0.0, $resultado);



        errores::$error = false;
    }

    public function test_get_factura_total(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
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
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_factura($this->link);
        $modelo = new liberator($modelo);

        $fc_factura_id = 1;
        $modelo_partida = new fc_partida(link: $this->link);
        $resultado = $modelo->get_partidas(name_entidad: 'fc_factura',modelo_partida: $modelo_partida,
            registro_entidad_id: $fc_factura_id);


        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);


        errores::$error = false;
    }

    public function test_limpia_alta_factura(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
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



    public function test_limpia_si_existe(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
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




    public function test_sub_total(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
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

        $registro_id = 1;
        $modelo_partida = new fc_partida(link: $this->link);
        $name_entidad = 'fc_factura';
        $resultado = $modelo->sub_total($modelo_partida, $name_entidad, $registro_id);
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
        $_SESSION['grupo_id'] = 2;
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
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_factura($this->link);
        $modelo = new liberator($modelo);

        $del = (new base_test())->del_fc_partida(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error('Error al del',$del);
            print_r($error);
            exit;
        }

        $alta = (new base_test())->alta_fc_partida(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error('Error al insertar',$alta);
            print_r($error);
            exit;
        }

        $partidas = array();
        $partidas[0]['fc_partida_id'] = 1;
        $modelo_partida = new fc_partida(link: $this->link);
        $resultado = $modelo->suma_descuento_partida($modelo_partida, $partidas);
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
        $_SESSION['grupo_id'] = 2;
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

        $alta = (new base_test())->alta_fc_partida(link:$this->link);
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
        $_SESSION['grupo_id'] = 2;
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

        $alta = (new base_test())->alta_fc_partida(link: $this->link);
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
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_factura($this->link);
        //$modelo = new liberator($modelo);
        $del = (new base_test())->del_fc_partida(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error('error al del', $del);
            print_r($error);
            exit;
        }

        $registro_id = 1;
        $modelo_partida = new fc_partida(link: $this->link);
        $name_entidad = 'fc_factura';


        $resultado = $modelo->total($modelo_partida, $name_entidad, $registro_id);
        //print_r($resultado);exit;
        $this->assertIsArray($resultado);
        $this->assertTrue(errores::$error);
        $this->assertStringContainsStringIgnoringCase('Error al obtener sub total',$resultado['mensaje']);
        errores::$error = false;



        $alta = (new base_test())->alta_fc_partida(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error('error al insertar', $alta);
            print_r($error);
            exit;
        }

        $resultado = $modelo->total($modelo_partida, $name_entidad, $registro_id);


        $this->assertIsFloat($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(1.0,$resultado);
        errores::$error = false;
    }

    public function test_ultimo_folio(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_factura($this->link);
        $modelo = new liberator($modelo);



        $del = (new base_test())->del_org_empresa($this->link,);
        if (errores::$error) {
            $error = (new errores())->error('Error al eliminar', $del);
            print_r($error);
            exit;
        }

        $alta = (new base_test())->alta_fc_partida(link: $this->link, fc_factura_folio: '1-1');
        if(errores::$error){
            $error = (new errores())->error('error al insertar', $alta);
            print_r($error);
            exit;
        }

        $fc_csd_id = 1;

        $resultado = $modelo->ultimo_folio($fc_csd_id);

        $this->assertIsString($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals('1-000002', $resultado);
        errores::$error = false;


    }


}

