<?php

namespace gamboamartin\facturacion\tests\orm;

use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\fc_csd;
use gamboamartin\facturacion\models\fc_factura;
use gamboamartin\facturacion\models\fc_partida;
use gamboamartin\facturacion\models\fc_traslado;
use gamboamartin\facturacion\tests\base_test;
use gamboamartin\facturacion\tests\base_test2;
use gamboamartin\organigrama\models\org_empresa;
use gamboamartin\organigrama\models\org_sucursal;
use gamboamartin\test\liberator;
use gamboamartin\test\test;
use stdClass;

class fc_partidaTest extends test
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

    public function test_calculo_imp_trasladado(): void
    {
        errores::$error = false;

        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_partida($this->link);



        $del = (new base_test())->del_fc_factura(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error('Error al del', $del);
            print_r($error);
            exit;
        }

        $del = (new base_test())->del_fc_conf_traslado(link: $this->link);
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

        $modelo_traslado = new fc_traslado(link: $this->link);
        $resultado = $modelo->calculo_imp_trasladado(modelo_traslado: $modelo_traslado,
            registro_partida_id: $alta->registro_id);
        $this->assertIsFloat($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(0.0,$resultado);
        errores::$error = false;
    }

    public function test_get_partida(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_partida($this->link);
        //$modelo = new liberator($modelo);

        $fc_partida_id = -1;
        $resultado = $modelo->get_partida(registro_partida_id: $fc_partida_id);
        $this->assertIsArray($resultado);
        $this->assertTrue(errores::$error);
        $this->assertEquals('<b><span style="color:red">Error registro_partida_id debe ser mayor a 0</span></b>',
            $resultado['mensaje']);
        errores::$error = false;

        $del = (new base_test())->del_fc_factura(link: $this->link);
        if (errores::$error) {
            $error = (new errores())->error('Error al eliminar', $del);
            print_r($error);
            exit;
        }


        $fc_partida = (new base_test())->alta_fc_partida(link: $this->link, cantidad: 10, id: 999, valor_unitario: 5.57);
        if (errores::$error) {
            $error = (new errores())->error('Error al obtener id de la partida', $fc_partida);
            print_r($error);
            exit;
        }

        $resultado = $modelo->get_partida(registro_partida_id: $fc_partida->registro_id);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        errores::$error = false;
    }

    public function test_get_partidas(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_partida($this->link);
        $modelo = new liberator($modelo);

        $fc_factura_id = -1;
        $resultado = $modelo->get_partidas(key_filtro_entidad_id: 'fc_factura.id', registro_entidad_id: $fc_factura_id);
        $this->assertIsArray($resultado);
        $this->assertTrue(errores::$error);
        $this->assertEquals('<b><span style="color:red">Error registro_entidad_id debe ser mayor a 0</span></b>',
            $resultado['mensaje']);
        errores::$error = false;

        $fc_factura_id = 1;
        $resultado = $modelo->get_partidas(key_filtro_entidad_id: 'fc_factura.id', registro_entidad_id: $fc_factura_id);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        errores::$error = false;
    }

    public function test_hijo_traslado(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_partida($this->link);
        $modelo = new liberator($modelo);

        $hijo = array();
        $modelo_traslado = new fc_traslado(link: $this->link);

        $resultado = $modelo->hijo_traslado($hijo,modelo_traslado:$modelo_traslado );

        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals('fc_partida_id',$resultado['fc_traslado']['filtros']['fc_partida.id']);
        errores::$error = false;
    }

    public function test_subtotal_partida(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_partida($this->link);
        //$modelo = new liberator($modelo);

        $fc_partida_id = -1;
        $resultado = $modelo->subtotal_partida(registro_partida_id: $fc_partida_id);
        $this->assertIsArray($resultado);
        $this->assertTrue(errores::$error);
        $this->assertEquals('<b><span style="color:red">Error el id de la partida es incorrecto</span></b>',
            $resultado['mensaje']);
        errores::$error = false;



        $del = (new base_test())->del_fc_factura(link: $this->link);
        if (errores::$error) {
            $error = (new errores())->error('Error al eliminar', $del);
            print_r($error);
            exit;
        }


        $fc_partida = (new base_test())->alta_fc_partida(link: $this->link, cantidad: 10, id: 999, valor_unitario: 5.57);
        if (errores::$error) {
            $error = (new errores())->error('Error al obtener id de la partida', $fc_partida);
            print_r($error);
            exit;
        }

        $resultado = $modelo->subtotal_partida(registro_partida_id: $fc_partida->registro_id);
        $this->assertIsFloat($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(55.7, $resultado);
        errores::$error = false;
    }

    public function test_total_partida(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_partida($this->link);
        //$modelo = new liberator($modelo);

        $fc_partida_id = -1;
        $resultado = $modelo->subtotal_partida(registro_partida_id: $fc_partida_id);
        $this->assertIsArray($resultado);
        $this->assertTrue(errores::$error);
        $this->assertEquals('<b><span style="color:red">Error el id de la partida es incorrecto</span></b>',
            $resultado['mensaje']);
        errores::$error = false;

        $del = (new base_test())->del_fc_factura(link: $this->link);
        if (errores::$error) {
            $error = (new errores())->error('Error al eliminar', $del);
            print_r($error);
            exit;
        }

        $fc_partida = (new base_test())->alta_fc_partida(link: $this->link, cantidad: 10, descuento: 9.35, id: 999,
            valor_unitario: 5.57);
        if (errores::$error) {
            $error = (new errores())->error('Error al obtener id de la partida', $fc_partida);
            print_r($error);
            exit;
        }



        $resultado = $modelo->total_partida(registro_partida_id: $fc_partida->registro_id);
        $this->assertIsFloat($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(46.35, $resultado);
        errores::$error = false;
    }


}

