<?php
namespace gamboamartin\facturacion\tests\controllers;


use gamboamartin\errores\errores;
use gamboamartin\facturacion\controllers\_html_factura;
use gamboamartin\facturacion\controllers\_partidas_html;
use gamboamartin\facturacion\controllers\_tmps;
use gamboamartin\facturacion\controllers\controlador_fc_factura;
use gamboamartin\facturacion\controllers\pdf;
use gamboamartin\facturacion\models\fc_csd;
use gamboamartin\facturacion\models\fc_partida;
use gamboamartin\facturacion\models\fc_partida_nc;
use gamboamartin\facturacion\models\fc_retenido;
use gamboamartin\facturacion\models\fc_traslado;
use gamboamartin\facturacion\tests\base_test;
use gamboamartin\organigrama\models\org_empresa;
use gamboamartin\organigrama\models\org_sucursal;
use gamboamartin\system\html_controler;
use gamboamartin\template_1\html;
use gamboamartin\test\liberator;
use gamboamartin\test\test;
use gamboamartin\facturacion\models\fc_factura;


use stdClass;


class _partidas_htmlTest extends test {
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

    public function test_aplica_aplica_impuesto(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'fc_factura';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $html = new _partidas_html();
        $html = new liberator($html);

        $tipo = 'a';
        $partida = array();
        $partida['a'] = array();

        $resultado = $html->aplica_aplica_impuesto($tipo, $partida);

        $this->assertIsBool($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertNotTrue($resultado);
        errores::$error = false;

        $tipo = 'a';
        $partida = array();
        $partida['a'] = array('');

        $resultado = $html->aplica_aplica_impuesto($tipo, $partida);

        $this->assertIsBool($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertTrue($resultado);
        errores::$error = false;
    }

    public function test_descripcion_html(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'fc_factura';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $html = new _partidas_html();
        $html = new liberator($html);


        $t_head_producto = 'v';
        $fc_partida_descripcion = 'a';

        $resultado = $html->descripcion_html($fc_partida_descripcion, $t_head_producto);
        $this->assertIsString($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertStringContainsStringIgnoringCase("<table class='table table-striped' style='font-size: 14px;'>",$resultado);
        errores::$error = false;
    }

    public function test_genera_descripcion_html(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'fc_factura';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $html = new _partidas_html();
        $html = new liberator($html);



        $input_descripcion = 'a';

        $resultado = $html->genera_descripcion_html($input_descripcion);
        $this->assertIsString($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertStringContainsStringIgnoringCase("<thead><tr><th>Descripcion</th></tr></thead>",$resultado);
        errores::$error = false;
    }

    public function test_genera_impuesto(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'fc_factura';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $html = new _partidas_html();
        $html = new liberator($html);


        $tag_tipo_impuesto = 'c';
        $html_ = new html();
        $html_controler = new html_controler($html_);
        $modelo_partida = new fc_partida(link: $this->link);
        $name_modelo_entidad = 'b';
        $partida = array();
        $partida['a'][0]['a_id'] = '1';
        $partida['a'][0]['b_id'] = '1';
        $partida['a'][0]['a_importe'] = '1';
        $partida['a'][0]['cat_sat_tipo_impuesto_descripcion'] = '1';
        $partida['a'][0]['cat_sat_tipo_factor_descripcion'] = '1';
        $partida['a'][0]['cat_sat_factor_factor'] = '1';
        $tipo = 'a';

        $resultado = $html->genera_impuesto($html_controler, $modelo_partida, $name_modelo_entidad, $partida,
            $tag_tipo_impuesto, $tipo);
        $this->assertIsString($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertStringContainsStringIgnoringCase("mpuesto</th><th>Tipo Factor</th><th",$resultado);
        errores::$error = false;
    }

    public function test_genera_impuesto_html(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'fc_factura';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $html = new _partidas_html();
        $html = new liberator($html);

        $html_ = new html();
        $html_controler = new html_controler($html_);

        $impuesto = array();
        $key_importe = 'c';
        $modelo_partida = new fc_partida(link: $this->link);
        $name_entidad_impuesto = 'a';
        $name_modelo_entidad = 'b';
        $impuesto['a_id'] = 1;
        $impuesto['b_id'] = 1;
        $impuesto['c'] = 1;
        $impuesto['cat_sat_tipo_impuesto_descripcion'] = 1;
        $impuesto['cat_sat_tipo_factor_descripcion'] = 1;
        $impuesto['cat_sat_factor_factor'] = 1;

        $resultado = $html->genera_impuesto_html($html_controler, $impuesto, $key_importe, $modelo_partida,
            $name_entidad_impuesto, $name_modelo_entidad);
        $this->assertIsString($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertStringContainsStringIgnoringCase("seccion=a&accion=elimina_bd&session_id=1",$resultado);
        errores::$error = false;
    }

    public function test_genera_partidas_html(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'fc_factura';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $html = new _partidas_html();
        //$html = new liberator($html);

        $html_ = new html();
        $html_controler = new html_controler(html: $html_);

        $link = $this->link;
        $modelo_partida = new fc_partida(link: $this->link);

        $modelo_entidad = new fc_factura(link: $this->link);
        $modelo_retencion = new fc_retenido(link: $this->link);
        $modelo_traslado = new fc_traslado(link: $this->link);
        $registro_entidad_id = 1;

        $resultado = $html->genera_partidas_html($html_controler, $link, $modelo_entidad, $modelo_partida, $modelo_retencion,
            $modelo_traslado, $registro_entidad_id);

        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);
        errores::$error = false;
    }

    public function test_impuesto_html(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'fc_factura';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $html = new _partidas_html();
        $html = new liberator($html);

        $aplica = true;
        $impuesto_html_completo = 'a';
        $tag_tipo_impuesto = 'v';

        $resultado = $html->impuesto_html($aplica, $impuesto_html_completo, $tag_tipo_impuesto);

        $this->assertIsString($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertStringContainsStringIgnoringCase("<td class='nested' colspan='9'>",$resultado);
        errores::$error = false;
    }
    public function test_impuesto_html_completo(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'fc_factura';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $html = new _partidas_html();
        $html = new liberator($html);

        $html_ = new html();
        $html_controler = new html_controler($html_);


        $modelo_partida = new fc_partida(link: $this->link);
        $name_entidad_impuesto = 'a';
        $name_modelo_entidad = 'b';
        $impuesto_html_completo = '';
        $partida = array();
        $partida['a'][0]['a_id'] = 1;
        $partida['a'][0]['b_id'] = 1;
        $partida['a'][0]['a_importe'] = 1;
        $partida['a'][0]['cat_sat_tipo_impuesto_descripcion'] = 1;
        $partida['a'][0]['cat_sat_tipo_factor_descripcion'] = 1;
        $partida['a'][0]['cat_sat_factor_factor'] = 1;

        $resultado = $html->impuesto_html_completo($html_controler, $impuesto_html_completo, $modelo_partida,
            $name_entidad_impuesto, $name_modelo_entidad, $partida);
        $this->assertIsString($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertStringContainsStringIgnoringCase("seccion=a&accion=elimina_bd&re",$resultado);
        errores::$error = false;
    }

    public function test_impuestos_html(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'fc_factura';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $html = new _partidas_html();
        $html = new liberator($html);


        $html_ = new html();
        $html_controler = new html_controler($html_);
        $modelo_partida = new fc_partida_nc(link: $this->link);
        $name_entidad_retenido = 'c';
        $name_entidad_traslado = 'b';
        $name_modelo_entidad = 'a';
        $partida = array();
        $partida['b'] = array();
        $partida['c'] = array();

        $resultado = $html->impuestos_html($html_controler, $modelo_partida, $name_entidad_retenido,
            $name_entidad_traslado, $name_modelo_entidad, $partida);
        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals("",$resultado->traslados);
        $this->assertEquals("",$resultado->retenidos);

        errores::$error = false;
    }

    public function test_integra_impuesto_html(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'fc_factura';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $html = new _partidas_html();
        $html = new liberator($html);

        $aplica = true;
        $tag_tipo_impuesto = 'c';
        $html_ = new html();
        $html_controler = new html_controler($html_);
        $modelo_partida = new fc_partida(link: $this->link);
        $name_entidad_impuesto = 'a';
        $name_modelo_entidad = 'b';
        $partida = array();
        $partida['a'] = array();

        $resultado = $html->integra_impuesto_html($aplica, $html_controler, $modelo_partida, $name_entidad_impuesto,
            $name_modelo_entidad, $partida, $tag_tipo_impuesto);
        $this->assertIsString($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertStringContainsStringIgnoringCase("<th>Importe</th><th>Elimina",$resultado);
        errores::$error = false;
    }

    public function test_partida_html(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'fc_factura';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $html = new _partidas_html();
        $html = new liberator($html);

        $html_ = new html();
        $html_controler = new html_controler(html: $html_);

        $indice = 0;
        $link = $this->link;
        $modelo_partida = new fc_partida(link: $this->link);
        $name_entidad_retenido = 'a';
        $name_entidad_traslado = 'b';
        $name_modelo_entidad = 'a';
        $partida = array();
        $partida['com_producto_id'] = 1;
        $partida['cat_sat_producto_codigo'] = 1;
        $partida['com_producto_codigo'] = 1;
        $partida['cat_sat_unidad_descripcion'] = 1;
        $partida['cat_sat_obj_imp_descripcion'] = 1;
        $partida['elimina_bd'] = 1;
        $partida['fc_partida_sub_total'] = 1;
        $partida['fc_partida_descuento'] = 1;
        $partida['fc_partida_descripcion'] = 1;
        $partida['b'] = array();
        $partida['a'] = array();

        $partidas = new stdClass();

        $resultado = $html->partida_html($html_controler, $indice, $link, $modelo_partida, $name_entidad_retenido,
            $name_entidad_traslado, $name_modelo_entidad, $partida, $partidas);
        //print_r($resultado);exit;
        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertStringContainsString(" name='cantidad' value='0'",$resultado->registros[0]['data_producto_html']);
        errores::$error = false;
    }

    public function test_valida_impuesto_partida(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'fc_factura';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $html = new _partidas_html();
        $html = new liberator($html);


        $partida = array();
        $tipo = 'a';
        $partida['a'] = array();

        $resultado = $html->valida_impuesto_partida($partida, $tipo);
        $this->assertIsBool($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertTrue($resultado);
        errores::$error = false;
    }

    public function test_valida_partida_html(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'fc_factura';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $html = new _partidas_html();
        //$html = new liberator($html);



        $partida = array();
        $partida['com_producto_id'] = 1;
        $partida['cat_sat_producto_codigo'] = 1;
        $partida['com_producto_codigo'] = 1;
        $partida['cat_sat_unidad_descripcion'] = 1;
        $partida['cat_sat_obj_imp_descripcion'] = 1;
        $partida['elimina_bd'] = 1;

        $resultado = $html->valida_partida_html($partida);
        $this->assertIsBool($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertTrue($resultado);
        errores::$error = false;
    }



}

