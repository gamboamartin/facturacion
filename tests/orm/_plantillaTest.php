<?php

namespace gamboamartin\facturacion\tests\orm;

use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\_email;
use gamboamartin\facturacion\models\_facturacion;
use gamboamartin\facturacion\models\_plantilla;
use gamboamartin\facturacion\models\fc_factura;
use gamboamartin\facturacion\models\fc_nota_credito;
use gamboamartin\facturacion\models\fc_partida;
use gamboamartin\facturacion\models\fc_partida_nc;
use gamboamartin\facturacion\models\fc_relacion;
use gamboamartin\facturacion\models\fc_relacion_nc;
use gamboamartin\facturacion\models\fc_retenido;
use gamboamartin\facturacion\models\fc_retenido_nc;
use gamboamartin\facturacion\models\fc_traslado;
use gamboamartin\facturacion\models\fc_traslado_nc;
use gamboamartin\facturacion\models\fc_uuid_nc;
use gamboamartin\facturacion\tests\base_test;
use gamboamartin\test\liberator;
use gamboamartin\test\test;


use stdClass;


class _plantillaTest extends test
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

    public function test_genera_row_entidad_ins(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';


        $modelo_entidad = new fc_factura(link: $this->link);
        $modelo_partida = new fc_partida(link: $this->link);
        $modelo_retenido = new fc_retenido(link: $this->link);
        $modelo_traslado = new fc_traslado(link: $this->link);
        $row_entidad_id = 1;

        $plantilla = new _plantilla($modelo_entidad, $modelo_partida, $modelo_retenido, $modelo_traslado, $row_entidad_id);
        $plantilla = new liberator($plantilla);


        $del = (new base_test())->del_org_empresa(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error('Error al del',$del);
            print_r($error);
            exit;
        }

        $del = (new base_test())->del_com_producto(link: $this->link);
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

        $resultado = $plantilla->genera_row_entidad_ins();

        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(1, $resultado['cat_sat_forma_pago_id']);
        errores::$error = false;

    }

    public function test_inserta_row_entidad(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';


        $modelo_entidad = new fc_factura(link: $this->link);
        $modelo_partida = new fc_partida(link: $this->link);
        $modelo_retenido = new fc_retenido(link: $this->link);
        $modelo_traslado = new fc_traslado(link: $this->link);
        $row_entidad_id = 1;

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

        $alta = (new base_test())->alta_fc_partida(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error('Error al insertar',$alta);
            print_r($error);
            exit;
        }

        $plantilla = new _plantilla($modelo_entidad, $modelo_partida, $modelo_retenido, $modelo_traslado, $row_entidad_id);
        $plantilla = new liberator($plantilla);


        $resultado = $plantilla->inserta_row_entidad();
        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals("Registro insertado con éxito", $resultado->mensaje);
        errores::$error = false;
    }

    public function test_keys_impuestos(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';


        $modelo_entidad = new fc_nota_credito(link: $this->link);
        $modelo_partida = new fc_partida_nc(link: $this->link);
        $modelo_retenido = new fc_retenido_nc(link: $this->link);
        $modelo_traslado = new fc_traslado_nc(link: $this->link);
        $row_entidad_id = 1;

        $plantilla = new _plantilla($modelo_entidad, $modelo_partida, $modelo_retenido, $modelo_traslado, $row_entidad_id);
        $plantilla = new liberator($plantilla);


        $resultado = $plantilla->keys_impuestos();
        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals("fc_partida_nc_n_traslados",$resultado->key_n_traslados);
        $this->assertEquals("fc_partida_nc_n_retenidos",$resultado->key_n_retenidos);

        errores::$error = false;

    }

    public function test_row_entidad(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';



        $del = (new base_test())->del_fc_factura(link: $this->link);
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

        $modelo_entidad = new fc_factura(link: $this->link);
        $modelo_partida = new fc_partida(link: $this->link);
        $modelo_retenido = new fc_retenido(link: $this->link);
        $modelo_traslado = new fc_traslado(link: $this->link);
        $row_entidad_id = 1;

        $plantilla = new _plantilla($modelo_entidad, $modelo_partida, $modelo_retenido, $modelo_traslado, $row_entidad_id);
        $plantilla = new liberator($plantilla);

        $resultado = $plantilla->row_entidad();


        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);

        errores::$error = false;

    }

    public function test_row_entidad_ins(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';


        $modelo_entidad = new fc_nota_credito(link: $this->link);
        $modelo_partida = new fc_partida_nc(link: $this->link);
        $modelo_retenido = new fc_retenido_nc(link: $this->link);
        $modelo_traslado = new fc_traslado_nc(link: $this->link);
        $row_entidad_id = 1;

        $plantilla = new _plantilla($modelo_entidad, $modelo_partida, $modelo_retenido, $modelo_traslado, $row_entidad_id);
        $plantilla = new liberator($plantilla);

        $com_tipo_cambio = array();
        $row_entidad = new stdClass();
        $row_entidad->fc_csd_id = '1';
        $row_entidad->cat_sat_forma_pago_id = '1';
        $row_entidad->cat_sat_metodo_pago_id = '1';
        $row_entidad->cat_sat_moneda_id = '1';
        $row_entidad->cat_sat_uso_cfdi_id = '1';
        $row_entidad->cat_sat_tipo_de_comprobante_id = '1';
        $row_entidad->dp_calle_pertenece_id = '1';
        $row_entidad->exportacion = '02';
        $row_entidad->cat_sat_regimen_fiscal_id = '1';
        $row_entidad->com_sucursal_id = '1';
        $row_entidad->observaciones = '';
        $row_entidad->total_descuento = '0';
        $row_entidad->sub_total_base = '0';
        $row_entidad->sub_total = '0';
        $row_entidad->total_traslados = '0';
        $row_entidad->total_retenciones = '0';
        $row_entidad->total = '0';
        $com_tipo_cambio['com_tipo_cambio_id'] = 1;
        $resultado = $plantilla->row_entidad_ins($com_tipo_cambio, $row_entidad);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(1, $resultado['fc_csd_id']);
        $this->assertEquals(1, $resultado['cat_sat_forma_pago_id']);
        $this->assertEquals(1, $resultado['cat_sat_metodo_pago_id']);
        $this->assertEquals(1, $resultado['cat_sat_moneda_id']);
        $this->assertEquals(1, $resultado['com_tipo_cambio_id']);
        $this->assertEquals(1, $resultado['cat_sat_uso_cfdi_id']);
        $this->assertEquals(1, $resultado['cat_sat_tipo_de_comprobante_id']);
        $this->assertEquals(1, $resultado['dp_calle_pertenece_id']);
        $this->assertEquals('02', $resultado['exportacion']);
        $this->assertEquals(1, $resultado['cat_sat_regimen_fiscal_id']);
        $this->assertEquals(1, $resultado['com_sucursal_id']);
        $this->assertEquals(0, $resultado['total_descuento']);
        $this->assertEquals(0, $resultado['sub_total_base']);
        $this->assertEquals(0, $resultado['sub_total']);
        $this->assertEquals(0, $resultado['total_traslados']);
        $this->assertEquals(0, $resultado['total_retenciones']);
        $this->assertEquals(0, $resultado['total']);
        errores::$error = false;
    }

    public function test_row_partida_ins(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';


        $modelo_entidad = new fc_nota_credito(link: $this->link);
        $modelo_partida = new fc_partida_nc(link: $this->link);
        $modelo_retenido = new fc_retenido_nc(link: $this->link);
        $modelo_traslado = new fc_traslado_nc(link: $this->link);
        $row_entidad_id = 1;


        $plantilla = new _plantilla($modelo_entidad, $modelo_partida, $modelo_retenido, $modelo_traslado, $row_entidad_id);
        $plantilla = new liberator($plantilla);

        $row_entidad_new_id = 1;
        $row_partida_origen = array();
        $row_partida_origen['com_producto_id'] = 1;
        $row_partida_origen['cantidad'] = 1;
        $row_partida_origen['descripcion'] = -1;
        $row_partida_origen['valor_unitario'] = 1;
        $row_partida_origen['descuento'] = 0;
        $row_partida_origen['sub_total_base'] = 1;
        $row_partida_origen['sub_total'] = 1;
        $row_partida_origen['total'] = 1;
        $row_partida_origen['total_traslados'] = 0;
        $row_partida_origen['total_retenciones'] = 0;
        $row_partida_origen['fc_partida_nc_n_traslados'] = 0;
        $row_partida_origen['fc_partida_nc_n_retenidos'] = 0;
        $resultado = $plantilla->row_partida_ins($row_entidad_new_id, $row_partida_origen);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(1,$resultado['cantidad']);
        $this->assertEquals(1,$resultado['com_producto_id']);
        $this->assertEquals(1,$resultado['fc_nota_credito_id']);
        $this->assertEquals(0,$resultado['fc_partida_nc_n_traslados']);
        $this->assertEquals(0,$resultado['fc_partida_nc_n_retenidos']);
        errores::$error = false;

    }

    public function test_rows_partidas(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

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
        $del = (new base_test())->del_com_producto(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error('Error al eliminar',$del);
            print_r($error);
            exit;
        }
        $del = (new base_test())->del_org_empresa(link: $this->link);
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


        $modelo_entidad = new fc_factura(link: $this->link);
        $modelo_partida = new fc_partida(link: $this->link);
        $modelo_retenido = new fc_retenido(link: $this->link);
        $modelo_traslado = new fc_traslado(link: $this->link);
        $row_entidad_id = 1;

        $plantilla = new _plantilla($modelo_entidad, $modelo_partida, $modelo_retenido, $modelo_traslado, $row_entidad_id);
        $plantilla = new liberator($plantilla);

        $resultado = $plantilla->rows_partidas();
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(1,$resultado[0]['cantidad']);
        errores::$error = false;

    }

    public function test_valida_existe_key_partida(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';


        $modelo_entidad = new fc_factura(link: $this->link);
        $modelo_partida = new fc_partida(link: $this->link);
        $modelo_retenido = new fc_retenido(link: $this->link);
        $modelo_traslado = new fc_traslado(link: $this->link);
        $row_entidad_id = 1;

        $plantilla = new _plantilla($modelo_entidad, $modelo_partida, $modelo_retenido, $modelo_traslado, $row_entidad_id);
        $plantilla = new liberator($plantilla);

        $keys_imps= new stdClass();
        $keys_imps->key_n_traslados = 'a';
        $keys_imps->key_n_retenidos = 'b';

        $row_partida_origen= array();
        $row_partida_origen['com_producto_id'] = 1;
        $row_partida_origen['cantidad'] = 1;
        $row_partida_origen['descripcion'] = 1;
        $row_partida_origen['valor_unitario'] = 1;
        $row_partida_origen['descuento'] = 1;
        $row_partida_origen['sub_total_base'] = 1;
        $row_partida_origen['sub_total'] = 1;
        $row_partida_origen['total'] = 1;
        $row_partida_origen['total_traslados'] = 1;
        $row_partida_origen['total_retenciones'] = 1;
        $row_partida_origen['a'] = 1;
        $row_partida_origen['b'] = 1;
        $resultado = $plantilla->valida_existe_key_partida($keys_imps, $row_partida_origen);
        $this->assertIsBool($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertTrue($resultado);
        errores::$error = false;
    }

    public function test_valida_ids_partida(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';


        $modelo_entidad = new fc_factura(link: $this->link);
        $modelo_partida = new fc_partida(link: $this->link);
        $modelo_retenido = new fc_retenido(link: $this->link);
        $modelo_traslado = new fc_traslado(link: $this->link);
        $row_entidad_id = 1;

        $plantilla = new _plantilla($modelo_entidad, $modelo_partida, $modelo_retenido, $modelo_traslado, $row_entidad_id);
        $plantilla = new liberator($plantilla);


        $row_partida_origen= array();
        $row_partida_origen['com_producto_id'] = -1;

        $resultado = $plantilla->valida_ids_partida($row_partida_origen);
        $this->assertIsArray($resultado);
        $this->assertTrue(errores::$error);

        errores::$error = false;

        $row_partida_origen= array();
        $row_partida_origen['com_producto_id'] = 1;

        $resultado = $plantilla->valida_ids_partida($row_partida_origen);

        $this->assertIsBool($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertTrue($resultado);
        errores::$error = false;
    }

    public function test_valida_monto_mayor_0(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';


        $modelo_entidad = new fc_factura(link: $this->link);
        $modelo_partida = new fc_partida(link: $this->link);
        $modelo_retenido = new fc_retenido(link: $this->link);
        $modelo_traslado = new fc_traslado(link: $this->link);
        $row_entidad_id = 1;

        $plantilla = new _plantilla($modelo_entidad, $modelo_partida, $modelo_retenido, $modelo_traslado, $row_entidad_id);
        $plantilla = new liberator($plantilla);


        $row_partida_origen= array();
        $row_partida_origen['cantidad'] = 1;
        $row_partida_origen['valor_unitario'] = 1;
        $row_partida_origen['sub_total_base'] = 1;
        $row_partida_origen['sub_total'] = 1;
        $row_partida_origen['total'] = 1;

        $resultado = $plantilla->valida_monto_mayor_0($row_partida_origen);
        $this->assertIsBool($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertTrue($resultado);
        errores::$error = false;
    }

    public function test_valida_monto_mayor_igual_0(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';


        $modelo_entidad = new fc_factura(link: $this->link);
        $modelo_partida = new fc_partida(link: $this->link);
        $modelo_retenido = new fc_retenido(link: $this->link);
        $modelo_traslado = new fc_traslado(link: $this->link);
        $row_entidad_id = 1;

        $plantilla = new _plantilla($modelo_entidad, $modelo_partida, $modelo_retenido, $modelo_traslado, $row_entidad_id);
        $plantilla = new liberator($plantilla);


        $row_partida_origen= array();
        $keys_imps = new stdClass();
        $keys_imps->key_n_traslados = 'a';
        $keys_imps->key_n_retenidos = 'b';

        $row_partida_origen['descuento'] = 0;
        $row_partida_origen['total_traslados'] = 0;
        $row_partida_origen['total_retenciones'] = 0;
        $row_partida_origen['a'] = 0;
        $row_partida_origen['b'] = 0;

        $resultado = $plantilla->valida_monto_mayor_igual_0($keys_imps, $row_partida_origen);
        $this->assertIsBool($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertTrue($resultado);
        errores::$error = false;
    }

    public function test_valida_montos(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';


        $modelo_entidad = new fc_factura(link: $this->link);
        $modelo_partida = new fc_partida(link: $this->link);
        $modelo_retenido = new fc_retenido(link: $this->link);
        $modelo_traslado = new fc_traslado(link: $this->link);
        $row_entidad_id = 1;

        $plantilla = new _plantilla($modelo_entidad, $modelo_partida, $modelo_retenido, $modelo_traslado, $row_entidad_id);
        $plantilla = new liberator($plantilla);


        $row_partida_origen= array();
        $keys_imps = new stdClass();
        $keys_imps->key_n_traslados = 'a';
        $keys_imps->key_n_retenidos = 'b';

        $row_partida_origen['descuento'] = 0;
        $row_partida_origen['total_traslados'] = 0;
        $row_partida_origen['total_retenciones'] = 0;
        $row_partida_origen['a'] = 0;
        $row_partida_origen['b'] = 0;
        $row_partida_origen['cantidad'] = 1;
        $row_partida_origen['valor_unitario'] = 1;
        $row_partida_origen['sub_total_base'] = 1;
        $row_partida_origen['sub_total'] = 1;
        $row_partida_origen['total'] = 1;

        $resultado = $plantilla->valida_montos($keys_imps, $row_partida_origen);
        $this->assertIsBool($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertTrue($resultado);
        errores::$error = false;
    }

    public function test_valida_row_entidad(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';


        $modelo_entidad = new fc_nota_credito(link: $this->link);
        $modelo_partida = new fc_partida_nc(link: $this->link);
        $modelo_retenido = new fc_retenido_nc(link: $this->link);
        $modelo_traslado = new fc_traslado_nc(link: $this->link);
        $row_entidad_id = 1;

        $plantilla = new _plantilla($modelo_entidad, $modelo_partida, $modelo_retenido, $modelo_traslado, $row_entidad_id);
        $plantilla = new liberator($plantilla);

        $com_tipo_cambio = array();
        $row_entidad = new stdClass();
        $row_entidad->fc_csd_id = '1';
        $row_entidad->cat_sat_forma_pago_id = '1';
        $row_entidad->cat_sat_metodo_pago_id = '1';
        $row_entidad->cat_sat_moneda_id = '1';
        $row_entidad->cat_sat_uso_cfdi_id = '1';
        $row_entidad->cat_sat_tipo_de_comprobante_id = '1';
        $row_entidad->dp_calle_pertenece_id = '1';
        $row_entidad->exportacion = '02';
        $row_entidad->cat_sat_regimen_fiscal_id = '1';
        $row_entidad->com_sucursal_id = '1';
        $row_entidad->observaciones = '';
        $row_entidad->total_descuento = '0';
        $row_entidad->sub_total_base = '0';
        $row_entidad->sub_total = '0';
        $row_entidad->total_traslados = '0';
        $row_entidad->total_retenciones = '0';
        $row_entidad->total = '0';
        $com_tipo_cambio['com_tipo_cambio_id'] = 1;
        $resultado = $plantilla->valida_row_entidad($com_tipo_cambio, $row_entidad);
        $this->assertIsBool($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertTrue($resultado);
        errores::$error = false;
    }



}

