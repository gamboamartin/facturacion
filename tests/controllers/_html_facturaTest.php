<?php
namespace gamboamartin\facturacion\tests\controllers;


use gamboamartin\errores\errores;
use gamboamartin\facturacion\controllers\_html_factura;
use gamboamartin\facturacion\controllers\_tmps;
use gamboamartin\facturacion\controllers\controlador_fc_factura;
use gamboamartin\facturacion\controllers\pdf;
use gamboamartin\facturacion\models\fc_csd;
use gamboamartin\facturacion\tests\base_test;
use gamboamartin\organigrama\models\org_empresa;
use gamboamartin\organigrama\models\org_sucursal;
use gamboamartin\system\html_controler;
use gamboamartin\template_1\html;
use gamboamartin\test\liberator;
use gamboamartin\test\test;
use gamboamartin\facturacion\models\fc_factura;


use stdClass;


class _html_facturaTest extends test {
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

    public function test_data_impuesto(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'fc_factura';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $html = new _html_factura();

        $button_del = 'c';

        $impuesto = array();
        $impuesto['cat_sat_tipo_impuesto_descripcion'] = 'a';
        $impuesto['cat_sat_tipo_factor_descripcion'] = 'a';
        $impuesto['cat_sat_factor_factor'] = 'a';
        $impuesto['z'] = 'a';
        $key = 'z';


        $resultado = $html->data_impuesto($button_del, $impuesto, $key);
        $this->assertIsString($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertStringContainsStringIgnoringCase("<td>a</td>",$resultado);
        errores::$error = false;

    }

    public function test_data_producto(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'fc_factura';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $html = new _html_factura();
        //$html = new liberator($html);

        $html_ = new html();
        $html_controler = new html_controler(html: $html_);

        $name_entidad_partida = 'a';

        $partida = array();
        $partida['com_producto_id'] = 1;
        $partida['cat_sat_producto_codigo'] = 1;
        $partida['com_producto_codigo'] = 1;
        $partida['cat_sat_unidad_descripcion'] = 1;
        $partida['cat_sat_obj_imp_descripcion'] = 1;
        $partida['elimina_bd'] = 1;
        $partida['a_sub_total'] = 1;
        $partida['a_descuento'] = 1;

        $resultado = $html->data_producto($html_controler, $this->link, $name_entidad_partida, $partida);
        $this->assertIsString($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertStringContainsStringIgnoringCase("name='cantidad' value='0' class='form-control' ",$resultado);
        errores::$error = false;
    }

    public function test_integra_key(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'fc_factura';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $html = new _html_factura();
        $html = new liberator($html);

        $campo = 'b';
        $name_entidad_partida = 'a';

        $resultado = $html->integra_key($campo, $name_entidad_partida);

        $this->assertIsString($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals('a_b',$resultado);
        errores::$error = false;
    }

    public function test_keys_producto(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'fc_factura';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $html = new _html_factura();
        $html = new liberator($html);


        $name_entidad_partida = 'a';

        $resultado = $html->keys_producto($name_entidad_partida);

        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals('a_cantidad',$resultado->key_cantidad);
        $this->assertEquals('a_valor_unitario',$resultado->key_valor_unitario);
        $this->assertEquals('a_sub_total',$resultado->key_importe);
        $this->assertEquals('a_descuento',$resultado->key_descuento);
        errores::$error = false;

    }

    public function test_inputs_producto(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'fc_factura';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $html = new _html_factura();
        $html = new liberator($html);

        $html_ = new html();
        $html_controler = new html_controler(html: $html_);

        $key_cantidad = 'a';
        $key_valor_unitario = 'b';
        $partida = array();
        $resultado = $html->inputs_producto($html_controler, $key_cantidad, $key_valor_unitario, $partida);
        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals("<div class='control-group col-sm-12'><input type='text' name='cantidad' value='0' class='form-control' required id='cantidad' placeholder='Cantidad' /></div>",$resultado->input_cantidad);
        $this->assertEquals("<div class='control-group col-sm-12'><input type='text' name='valor_unitario' value='0' class='form-control' required id='valor_unitario' placeholder='Valor Unitario' /></div>",$resultado->input_valor_unitario);
        errores::$error = false;
    }

    public function test_thead_impuesto(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'fc_factura';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $html = new _html_factura();
        $html = new liberator($html);

        $resultado = $html->thead_impuesto();
        $this->assertIsString($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals("<tr><th>Tipo Impuesto</th><th>Tipo Factor</th><th>Factor</th><th>Importe</th><th>Elimina</th></tr>",$resultado);
        errores::$error = false;
    }

    public function test_tr_impuestos_html(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'fc_factura';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $html = new _html_factura();
        $html = new liberator($html);

        $impuesto_html = 'a';
        $tag_tipo_impuesto = 'b';
        $resultado = $html->tr_impuestos_html($impuesto_html, $tag_tipo_impuesto);
        $this->assertIsString($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertStringContainsStringIgnoringCase("<th colspan='5'>b</th>",$resultado);
        errores::$error = false;
    }

    public function test_tr_producto(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'fc_factura';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $html = new _html_factura();
        $html = new liberator($html);


        $input_cantidad = 'c';
        $input_valor_unitario = 'd';
        $key_descuento = 'a';
        $key_importe = 'b';
        $partida = array();
        $partida['cat_sat_producto_codigo'] = 'z';
        $partida['com_producto_codigo'] = 'y';
        $partida['cat_sat_unidad_descripcion'] = 'x';
        $partida['b'] = 'l';
        $partida['a'] = 'm';
        $partida['cat_sat_obj_imp_descripcion'] = 'n';
        $partida['elimina_bd'] = 'd';
        $resultado = $html->tr_producto($input_cantidad, $input_valor_unitario, $key_descuento, $key_importe, $partida);
        $this->assertIsString($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertStringContainsStringIgnoringCase("<td>z</td>",$resultado);
        $this->assertStringContainsStringIgnoringCase("<td>y</td>",$resultado);
        $this->assertStringContainsStringIgnoringCase("<td>n</td>",$resultado);
        errores::$error = false;
    }

    public function test_valida_tr(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'fc_factura';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $html = new _html_factura();
        $html = new liberator($html);


        $input_cantidad = '';
        $input_valor_unitario = '';
        $key_descuento = '';
        $key_importe = '';
        $partida = array();
        $partida['cat_sat_producto_codigo'] = '';
        $partida['com_producto_codigo'] = '';
        $partida['cat_sat_unidad_descripcion'] = '';
        $partida['b'] = '';
        $partida['a'] = '';
        $partida['cat_sat_obj_imp_descripcion'] = '';
        $partida['elimina_bd'] = '';
        $resultado = $html->valida_tr($key_descuento, $key_importe, $input_cantidad, $input_valor_unitario, $partida);
        $this->assertIsArray($resultado);
        $this->assertTrue(errores::$error);
        $this->assertEquals("Error key_descuento esta vacio",$resultado['mensaje_limpio']);
        errores::$error = false;

        $input_cantidad = 'c';
        $input_valor_unitario = 'd';
        $key_descuento = 'a';
        $key_importe = 'b';
        $partida = array();
        $partida['cat_sat_producto_codigo'] = 'e';
        $partida['com_producto_codigo'] = 'f';
        $partida['cat_sat_unidad_descripcion'] = 'g';
        $partida['b'] = 'h';
        $partida['a'] = 'i';
        $partida['cat_sat_obj_imp_descripcion'] = 'j';
        $partida['elimina_bd'] = 'k';
        $resultado = $html->valida_tr($key_descuento, $key_importe, $input_cantidad, $input_valor_unitario, $partida);
        $this->assertIsBool($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertTrue($resultado);
    }



}

