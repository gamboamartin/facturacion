<?php

namespace gamboamartin\facturacion\tests\orm;

use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\_email;
use gamboamartin\facturacion\models\_facturacion;
use gamboamartin\facturacion\models\fc_complemento_pago;
use gamboamartin\facturacion\models\fc_complemento_pago_etapa;
use gamboamartin\facturacion\models\fc_cuenta_predial;
use gamboamartin\facturacion\models\fc_cuenta_predial_nc;
use gamboamartin\facturacion\models\fc_factura;
use gamboamartin\facturacion\models\fc_factura_etapa;
use gamboamartin\facturacion\models\fc_nota_credito;
use gamboamartin\facturacion\models\fc_nota_credito_etapa;
use gamboamartin\facturacion\models\fc_partida;
use gamboamartin\facturacion\models\fc_partida_cp;
use gamboamartin\facturacion\models\fc_partida_nc;
use gamboamartin\facturacion\models\fc_relacion;
use gamboamartin\facturacion\models\fc_retenido;
use gamboamartin\facturacion\models\fc_retenido_cp;
use gamboamartin\facturacion\models\fc_retenido_nc;
use gamboamartin\facturacion\models\fc_traslado;
use gamboamartin\facturacion\models\fc_traslado_nc;
use gamboamartin\facturacion\tests\base_test;
use gamboamartin\js_base\eventos\adm_seccion;
use gamboamartin\system\html_controler;
use gamboamartin\template\html;
use gamboamartin\test\liberator;
use gamboamartin\test\test;


use stdClass;


class _partidaTest extends test
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

    public function test_descripcion_mes_letra(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_partida_cp(link: $this->link);
        $modelo = new liberator($modelo);

        $descripcion = 'a';

        $resultado = $modelo->descripcion_mes_letra($descripcion);
        $this->assertIsString($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals('a',$resultado);

        errores::$error = false;

        $descripcion = 'a {{MES_LETRA}}';

        $resultado = $modelo->descripcion_mes_letra($descripcion);
        $this->assertIsString($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals('a AGOSTO',$resultado);

        errores::$error = false;

        $descripcion = 'a {{mes_letra}}';

        $resultado = $modelo->descripcion_mes_letra($descripcion);
        $this->assertIsString($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals('a AGOSTO',$resultado);
        errores::$error = false;


    }
    public function test_elimina_dependientes(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_partida(link: $this->link);
        $modelo = new liberator($modelo);


        $id = 1;
        $modelo_predial = new fc_cuenta_predial(link: $this->link);
        $modelo_retencion = new fc_retenido(link: $this->link);
        $modelo_traslado = new fc_traslado(link: $this->link);

        $resultado = $modelo->elimina_dependientes($id, $modelo_predial, $modelo_retencion, $modelo_traslado);
        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);

        errores::$error = false;

    }

    public function test_fc_entidad_total_descuento(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';



        $modelo = new fc_partida(link: $this->link);
        $modelo = new liberator($modelo);

        $key_filtro_entidad_id = 'fc_factura.id';
        $registro_entidad_id = 1;
        $resultado = $modelo->fc_entidad_total_descuento($key_filtro_entidad_id, $registro_entidad_id);
        $this->assertIsFloat($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(0.0, $resultado);
        errores::$error = false;

    }

    public function test_get_partida(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_partida(link: $this->link);
        //$modelo = new liberator($modelo);


        $registro_partida_id = 1;
        $resultado = $modelo->get_partida($registro_partida_id);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals('1', $resultado['fc_partida_id']);
        errores::$error = false;

    }

    public function test_get_partidas(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_partida_nc(link: $this->link);
        $modelo = new liberator($modelo);


        $key_filtro_entidad_id = 'fc_nota_credito.id';
        $registro_entidad_id = 1;

        $resultado = $modelo->get_partidas($key_filtro_entidad_id, $registro_entidad_id);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        errores::$error = false;
    }

    public function test_hijo(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_partida_nc(link: $this->link);
        $modelo = new liberator($modelo);

        $hijo = array();
        $name_modelo_impuesto = 'a';
        $resultado = $modelo->hijo($hijo, $name_modelo_impuesto);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals('fc_partida_nc_id', $resultado['a']['filtros']['fc_partida_nc.id']);
        errores::$error = false;
    }

    public function test_hijo_retenido(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_partida_cp(link: $this->link);
        $modelo = new liberator($modelo);

        $hijo = array();
        $modelo_retencion = new fc_retenido_cp(link: $this->link);
        $resultado = $modelo->hijo_retenido($hijo, $modelo_retencion);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals('fc_partida_cp_id', $resultado['fc_retenido_cp']['filtros']['fc_partida_cp.id']);
        errores::$error = false;

    }

    public function test_hijos_partida(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_partida(link: $this->link);
        $modelo = new liberator($modelo);


        $modelo_retencion = new fc_retenido(link: $this->link);
        $modelo_traslado = new fc_traslado(link: $this->link);
        $resultado = $modelo->hijos_partida($modelo_retencion, $modelo_traslado);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals('fc_partida_id', $resultado['fc_retenido']['filtros']['fc_partida.id']);
        $this->assertEquals('fc_partida_id', $resultado['fc_traslado']['filtros']['fc_partida.id']);
        errores::$error = false;

    }

    public function test_init_elimina_bd(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_partida_nc(link: $this->link);
        $modelo = new liberator($modelo);


        $id = 1;
        $modelo_entidad = new fc_nota_credito(link: $this->link);
        $modelo_etapa = new fc_nota_credito_etapa(link: $this->link);
        $modelo_predial = new fc_cuenta_predial_nc(link: $this->link);
        $modelo_retencion = new fc_retenido_nc(link: $this->link);
        $modelo_traslado = new fc_traslado_nc(link: $this->link);


        $del = (new base_test())->del_fc_nota_credito(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al eliminar',data:  $del);
            print_r($error);exit;
        }


        $alta = (new base_test())->alta_fc_partida_nc(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al insertar',data:  $alta);
            print_r($error);exit;
        }

        $resultado = $modelo->init_elimina_bd($id, $modelo_entidad, $modelo_etapa, $modelo_predial, $modelo_retencion,
            $modelo_traslado);


        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);

        errores::$error = false;
    }

    public function test_integra_button_partida(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $temp =  new html();
        $html = (new html_controler(html: $temp));

        $indice = 0;

        $name_modelo_entidad = 'a';
        $partida = array();
        $r_fc_registro_partida = new stdClass();
        $registro_entidad_id = 1;
        $partida['fc_partida_nc_id'] = 1;

        $modelo = new fc_partida_nc(link: $this->link);
        $modelo = new liberator($modelo);
        $resultado = $modelo->integra_button_partida($html, $indice, $name_modelo_entidad, $partida,
            $r_fc_registro_partida, $registro_entidad_id);

        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals("<a role='button' title='Eliminar' href='index.php?seccion=fc_partida_nc&accion=elimina_bd&registro_id=1&session_id=1&adm_menu_id=-1&seccion_retorno=a&accion_retorno=modifica&id_retorno=1' class='btn btn-danger col-sm-12 '><span class='bi bi-trash'></span></a>", $resultado->registros[0]['elimina_bd']);
        errores::$error = false;


    }

    public function test_integra_buttons_partida(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_partida_nc(link: $this->link);
        $modelo = new liberator($modelo);

        $filtro = array();
        $hijo = array();
        $html = new html();
        $html = new html_controler($html);
        $name_modelo_entidad = 'a';
        $registro_entidad_id = 1;

        $resultado = $modelo->integra_buttons_partida($filtro, $hijo, $html, $name_modelo_entidad, $registro_entidad_id);

        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);

        errores::$error = false;
    }

    public function test_integra_relacionado(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
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

    public function test_operacion_factor(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_partida(link: $this->link);
        $modelo = new liberator($modelo);

        $subtotal = 100;
        $row = array();
        $row['cat_sat_factor_factor'] = .16;
        $resultado = $modelo->operacion_factor($subtotal, $row);
        $this->assertIsFloat($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(16,$resultado);
        errores::$error = false;

        $subtotal = 100;
        $row = array();
        $row['cat_sat_factor_factor'] = 0.0125;
        $resultado = $modelo->operacion_factor($subtotal, $row);
        $this->assertIsFloat($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(1.25,$resultado);
        errores::$error = false;


        $subtotal = 100;
        $row = array();
        $row['cat_sat_factor_factor'] = 0.106666;
        $resultado = $modelo->operacion_factor($subtotal, $row);
        $this->assertIsFloat($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(10.67,$resultado);
        errores::$error = false;


        $subtotal = 9440.56;
        $row = array();
        $row['cat_sat_factor_factor'] = 0.106666;
        $resultado = $modelo->operacion_factor($subtotal, $row);
        $this->assertIsFloat($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(1006.99,$resultado);
        errores::$error = false;

    }

    public function test_params_button_partida(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_partida(link: $this->link);
        //$modelo = new liberator($modelo);

        $name_modelo_entidad = 'a';
        $registro_entidad_id = 1;
        $resultado = $modelo->params_button_partida($name_modelo_entidad, $registro_entidad_id);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals('a', $resultado['seccion_retorno']);
        $this->assertEquals('modifica', $resultado['accion_retorno']);
        $this->assertEquals(1, $resultado['id_retorno']);
        errores::$error = false;
    }

    public function test_params_calculo(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_partida_nc(link: $this->link);
        $modelo = new liberator($modelo);


        $del = (new base_test())->del_fc_conf_traslado(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al eliminar',data:  $del);
            print_r($error);exit;
        }

        $del = (new base_test())->del_fc_nota_credito(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al eliminar',data:  $del);
            print_r($error);exit;
        }


        $alta = (new base_test())->alta_fc_partida_nc(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al insertar',data:  $alta);
            print_r($error);exit;
        }

        $registro_partida_id = 1;
        $modelo_imp = new fc_retenido_nc(link: $this->link);
        $filtro = array();
        $resultado = $modelo->params_calculo($filtro, $modelo_imp, $registro_partida_id);
        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(.01,$resultado->data_imp->registros[0]['fc_retenido_nc_total']);
        $this->assertEquals(.99,$resultado->data_imp->registros_obj[0]->fc_nota_credito_total);
        $this->assertEquals(1,$resultado->subtotal);

        errores::$error = false;

    }

    public function test_partidas(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_partida(link: $this->link);
        //$modelo = new liberator($modelo);


        $html = new html();
        $html = new html_controler($html);
        $modelo_entidad = new fc_factura(link: $this->link);
        $modelo_retencion = new fc_retenido(link: $this->link);
        $modelo_traslado = new fc_traslado(link: $this->link);
        $registro_entidad_id = 1;

        $resultado = $modelo->partidas($html, $modelo_entidad, $modelo_retencion, $modelo_traslado, $registro_entidad_id);
        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);

        errores::$error = false;
    }

    public function test_resultado_operacion_imp(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_partida_nc(link: $this->link);
        $modelo = new liberator($modelo);


        $params = new stdClass();
        $params->data_imp = new stdClass();
        $params->data_imp->n_registros = 1;
        $params->data_imp->registros[0]['cat_sat_factor_factor'] = .1;
        $params->subtotal = '100';
        $resultado = $modelo->resultado_operacion_imp($params);
        $this->assertIsFloat($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(10,$resultado);
        errores::$error = false;
    }

    public function test_subtotal_partida(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_partida_nc(link: $this->link);
        //$modelo = new liberator($modelo);


        $del = (new base_test())->del_fc_nota_credito(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al eliminar',data:  $del);
            print_r($error);exit;
        }


        $alta = (new base_test())->alta_fc_partida_nc(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al insertar',data:  $alta);
            print_r($error);exit;
        }

        $registro_partida_id = 1;

        $resultado = $modelo->subtotal_partida($registro_partida_id);
        $this->assertIsFloat($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(1,$resultado);
        errores::$error = false;
    }

    public function test_valida_restriccion(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_partida_cp(link: $this->link);
        $modelo = new liberator($modelo);

        $modelo_entidad = new fc_complemento_pago(link: $this->link);
        $modelo_etapa = new fc_complemento_pago_etapa(link: $this->link);
        $row_partida = new stdClass();
        $row_partida->fc_complemento_pago_id = '1';

        $resultado = $modelo->valida_restriccion($modelo_entidad, $modelo_etapa, $row_partida);
        $this->assertIsBool($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertTrue($resultado);
        errores::$error = false;

    }

    public function test_valida_restriccion_partida(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_partida_cp(link: $this->link);
        $modelo = new liberator($modelo);


        $del = (new base_test())->del_com_producto(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al eliminar',data:  $del);
            print_r($error);exit;
        }


        $inserta = (new base_test())->alta_com_producto(link: $this->link, codigo: '84111506', id: '84111506');
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al insertar',data:  $inserta);
            print_r($error);exit;
        }

        $inserta = (new base_test())->alta_com_producto(link: $this->link, codigo: '99999999', id: '99999999');
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al insertar',data:  $inserta);
            print_r($error);exit;
        }



        $inserta = (new base_test())->alta_fc_partida_cp(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al insertar',data:  $inserta);
            print_r($error);exit;
        }

        $modelo_entidad = new fc_complemento_pago(link: $this->link);
        $modelo_etapa = new fc_complemento_pago_etapa(link: $this->link);
        $id = 1;
        $resultado = $modelo->valida_restriccion_partida($id, $modelo_entidad, $modelo_etapa);
        $this->assertIsBool($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertTrue($resultado);
        errores::$error = false;
    }





}

