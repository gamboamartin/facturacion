<?php
namespace gamboamartin\facturacion\tests\orm;


use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\fc_csd;
use gamboamartin\facturacion\models\fc_cuenta_predial;
use gamboamartin\facturacion\models\fc_docto_relacionado;
use gamboamartin\facturacion\models\fc_factura_relacionada;
use gamboamartin\facturacion\models\fc_pago_pago;
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


class fc_docto_relacionadoTest extends test {
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

    public function test_alta_bd(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';


        $modelo = new fc_docto_relacionado($this->link);
        //$modelo = new liberator($modelo);

        $del = (new base_test())->del_com_cliente(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al del',data:  $del);
            print_r($error);exit;
        }
        $del = (new base_test())->del_org_empresa(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al del',data:  $del);
            print_r($error);exit;
        }

        $del = (new base_test())->del_com_producto(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al del',data:  $del);
            print_r($error);exit;
        }

        $alta = (new base_test())->alta_com_producto(link: $this->link,codigo: '99999999',id: 99999999);
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al insertar',data:  $alta);
            print_r($error);exit;
        }

        $alta = (new base_test())->alta_fc_partida(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al insertar',data:  $alta);
            print_r($error);exit;
        }




        $alta = (new base_test())->alta_fc_pago_pago(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al insertar',data:  $alta);
            print_r($error);exit;
        }

        $modelo->registro = array();
        $modelo->registro['fc_factura_id'] = 1;
        $modelo->registro['fc_pago_pago_id'] = 1;
        $modelo->registro['imp_pagado'] = '1';

        $resultado = $modelo->alta_bd();

        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);

        errores::$error = false;
    }

    public function test_codigo_alta(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';


        $modelo = new fc_docto_relacionado($this->link);
        $modelo = new liberator($modelo);


        errores::$error = false;
        $registro = array();
        $registro['fc_factura_id'] = 1;
        $registro['imp_pagado'] = 0.1;
        $registro['fc_pago_pago_id'] = 1;
        $resultado = $modelo->codigo_alta($registro);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals('1',$resultado['fc_pago_pago_id']);
        $this->assertStringContainsStringIgnoringCase('1-0.1-1-',$resultado['codigo']);
        errores::$error = false;

    }

    public function test_descripcion_alta(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';


        $modelo = new fc_docto_relacionado($this->link);
        $modelo = new liberator($modelo);


        errores::$error = false;
        $registro = array();
        $registro['fc_factura_id'] = 1;
        $registro['imp_pagado'] = 0.1;
        $registro['fc_pago_pago_id'] = 1;
        $registro['codigo'] = 1;
        $resultado = $modelo->descripcion_alta($registro);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals('1',$resultado['descripcion']);

        errores::$error = false;

    }

    public function test_importe_pagado_tc_pago(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';


        $modelo = new fc_docto_relacionado($this->link);
        $modelo = new liberator($modelo);


        errores::$error = false;

        $com_tipo_cambio_pago_monto = 10;
        $importe_pagado = 10;
        $resultado = $modelo->importe_pagado_tc_pago($com_tipo_cambio_pago_monto, $importe_pagado);
        $this->assertIsfloat($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(100.0,$resultado);

        errores::$error = false;
    }

    /**
     * Esta prueba es base, el resultado se debe de respetar siempre
     * @return void
     */
    public function test_monto_pagado_tc_dif(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';


        $modelo = new fc_docto_relacionado($this->link);
        $modelo = new liberator($modelo);


        errores::$error = false;
        $fc_docto_relacionado = array();
        $monto_pagado_tc = 0;
        $fc_docto_relacionado['fc_docto_relacionado_imp_pagado'] = 100;
        $fc_docto_relacionado['com_tipo_cambio_factura_cat_sat_moneda_id'] = 1;
        $fc_docto_relacionado['com_tipo_cambio_pago_cat_sat_moneda_id'] = 1;
        $fc_docto_relacionado['com_tipo_cambio_pago_monto'] = 1;
        $fc_docto_relacionado['com_tipo_cambio_factura_monto'] = 1;
        $resultado = $modelo->monto_pagado_tc_dif($fc_docto_relacionado, $monto_pagado_tc);
        $this->assertIsfloat($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(100.0,$resultado);
        errores::$error = false;

        $fc_docto_relacionado = array();
        $monto_pagado_tc = 0;
        $fc_docto_relacionado['fc_docto_relacionado_imp_pagado'] = 100;
        $fc_docto_relacionado['com_tipo_cambio_factura_cat_sat_moneda_id'] = 161;
        $fc_docto_relacionado['com_tipo_cambio_pago_cat_sat_moneda_id'] = 2;
        $fc_docto_relacionado['com_tipo_cambio_pago_monto'] = 20;
        $fc_docto_relacionado['com_tipo_cambio_factura_monto'] = 1;
        $resultado = $modelo->monto_pagado_tc_dif($fc_docto_relacionado, $monto_pagado_tc);
        $this->assertIsfloat($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(5.0,$resultado);
        errores::$error = false;


        $fc_docto_relacionado = array();
        $monto_pagado_tc = 0;
        $fc_docto_relacionado['fc_docto_relacionado_imp_pagado'] = 100;
        $fc_docto_relacionado['com_tipo_cambio_factura_cat_sat_moneda_id'] = 161;
        $fc_docto_relacionado['com_tipo_cambio_pago_cat_sat_moneda_id'] = 161;
        $fc_docto_relacionado['com_tipo_cambio_pago_monto'] = 1;
        $fc_docto_relacionado['com_tipo_cambio_factura_monto'] = 1;
        $resultado = $modelo->monto_pagado_tc_dif($fc_docto_relacionado, $monto_pagado_tc);
        $this->assertIsfloat($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(100.0,$resultado);
        errores::$error = false;



        $fc_docto_relacionado = array();
        $monto_pagado_tc = 0;
        $fc_docto_relacionado['fc_docto_relacionado_imp_pagado'] = 100;
        $fc_docto_relacionado['com_tipo_cambio_factura_cat_sat_moneda_id'] = 164;
        $fc_docto_relacionado['com_tipo_cambio_pago_cat_sat_moneda_id'] = 164;
        $fc_docto_relacionado['com_tipo_cambio_pago_monto'] = 20;
        $fc_docto_relacionado['com_tipo_cambio_factura_monto'] = 20;
        $resultado = $modelo->monto_pagado_tc_dif($fc_docto_relacionado, $monto_pagado_tc);
        $this->assertIsfloat($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(100.0,$resultado);
        errores::$error = false;


        $fc_docto_relacionado = array();
        $monto_pagado_tc = 0;
        $fc_docto_relacionado['fc_docto_relacionado_imp_pagado'] = 100;
        $fc_docto_relacionado['com_tipo_cambio_factura_cat_sat_moneda_id'] = 164;
        $fc_docto_relacionado['com_tipo_cambio_pago_cat_sat_moneda_id'] = 161;
        $fc_docto_relacionado['com_tipo_cambio_pago_monto'] = 1;
        $fc_docto_relacionado['com_tipo_cambio_factura_monto'] = 20;
        $resultado = $modelo->monto_pagado_tc_dif($fc_docto_relacionado, $monto_pagado_tc);

        $this->assertIsfloat($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(2000.0,$resultado);
        errores::$error = false;



    }

    public function test_valida_alta_docto(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';


        $modelo = new fc_docto_relacionado($this->link);
        $modelo->registro['fc_factura_id'] = 1;
        $modelo->registro['fc_pago_pago_id'] = 1;
        $modelo->registro['imp_pagado'] = 1;

        $modelo = new liberator($modelo);



        errores::$error = false;


        $resultado = $modelo->valida_alta_docto();
        $this->assertIsBool($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertTrue($resultado);
        errores::$error = false;

    }



}

