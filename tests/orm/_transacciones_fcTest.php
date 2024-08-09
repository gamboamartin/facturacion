<?php

namespace gamboamartin\facturacion\tests\orm;

use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\_comprobante;
use gamboamartin\facturacion\models\_email;
use gamboamartin\facturacion\models\_facturacion;
use gamboamartin\facturacion\models\_transacciones_fc;
use gamboamartin\facturacion\models\fc_complemento_pago;
use gamboamartin\facturacion\models\fc_complemento_pago_etapa;
use gamboamartin\facturacion\models\fc_factura;
use gamboamartin\facturacion\models\fc_factura_etapa;
use gamboamartin\facturacion\models\fc_nota_credito;
use gamboamartin\facturacion\models\fc_nota_credito_etapa;
use gamboamartin\facturacion\models\fc_partida;
use gamboamartin\facturacion\models\fc_retenido;
use gamboamartin\facturacion\models\fc_traslado;
use gamboamartin\facturacion\tests\base_test;
use gamboamartin\test\liberator;
use gamboamartin\test\test;


use stdClass;


class _transacciones_fcTest extends test
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

    public function test_data_para_folio(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $trs = new fc_complemento_pago($this->link);
        $trs = new liberator($trs);

        $del = (new base_test())->del_org_empresa(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al eliminar', data: $del);
            print_r($error);
            exit;
        }

        $alta = (new base_test())->alta_fc_csd(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al alta', data: $alta);
            print_r($error);
            exit;
        }

        $fc_csd_id =  1;
        $resultado = $trs->data_para_folio($fc_csd_id);
        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals('1',$resultado->fc_csd_serie);
        errores::$error = false;

    }

    public function test_default_alta_emisor_data(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $trs = new fc_nota_credito($this->link);
        $trs = new liberator($trs);

        $registro = array();
        $registro_csd = new stdClass();
        $registro_csd->dp_calle_pertenece_id = 1;
        $registro_csd->cat_sat_regimen_fiscal_id = 1;
        $resultado = $trs->default_alta_emisor_data($registro, $registro_csd);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        errores::$error = false;
    }

    public function test_emisor(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $trs = new fc_complemento_pago($this->link);
        $trs = new liberator($trs);

        $row_entidad = array();

        $row_entidad['org_empresa_rfc'] = 'AAA010101AAA';
        $row_entidad['org_empresa_razon_social'] = 'a';
        $row_entidad['cat_sat_regimen_fiscal_codigo'] = 'a';

        $resultado = $trs->emisor($row_entidad);

        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        errores::$error = false;
    }

    public function test_etapas(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $trs = new fc_factura($this->link);
        $trs = new liberator($trs);

        $modelo_fc_etapa = new fc_factura_etapa(link: $this->link);
        $registro_id = '1';

        $resultado = $trs->etapas($modelo_fc_etapa, $registro_id);


        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        errores::$error = false;

        $trs = new fc_nota_credito($this->link);
        $trs = new liberator($trs);

        $modelo_fc_etapa = new fc_nota_credito_etapa(link: $this->link);

        $resultado = $trs->etapas($modelo_fc_etapa, $registro_id);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        errores::$error = false;

        $trs = new fc_complemento_pago($this->link);
        $trs = new liberator($trs);

        $modelo_fc_etapa = new fc_complemento_pago_etapa(link: $this->link);

        $resultado = $trs->etapas($modelo_fc_etapa, $registro_id);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);


        errores::$error = false;
    }

    public function test_fc_csd_serie(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $trs = new fc_nota_credito($this->link);
        $trs = new liberator($trs);

        $fc_csd_id= 1;
        $resultado = $trs->fc_csd_serie($fc_csd_id);
        $this->assertIsString($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals('1',$resultado);


        errores::$error = false;

    }

    public function test_integra_fecha(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $trs = new fc_nota_credito($this->link);
        $trs = new liberator($trs);

        $registro = array();
        $registro['fecha'] = '2020-01-01';

        $resultado = $trs->integra_fecha($registro);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);


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

        $trs = new fc_factura($this->link);
        $trs = new liberator($trs);


        $registro = array();
        $registro['D'] = 'xxx';
        $key = 'D';
        $resultado = $trs->limpia_si_existe(key: $key,registro: $registro);

        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEmpty($resultado);

        errores::$error = false;
    }

    public function test_modifica_bd(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $trs = new fc_complemento_pago($this->link);
        //$trs = new liberator($trs);

        $del = (new base_test())->del_org_empresa(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al eliminar', data: $del);
            print_r($error);
            exit;
        }

        $del = (new base_test())->del_com_producto(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al eliminar', data: $del);
            print_r($error);
            exit;
        }

        $alta = (new base_test())->alta_com_producto(link: $this->link, codigo: '99999999', id: 99999999);
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al alta', data: $alta);
            print_r($error);
            exit;
        }


        $alta = (new base_test())->alta_fc_complemento_pago(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al alta', data: $alta);
            print_r($error);
            exit;
        }

        $registro = array();
        $registro['observaciones'] = '1';
        $id = 1;
        $resultado = $trs->modifica_bd($registro, $id);
        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals("UPDATE fc_complemento_pago SET observaciones = '1',usuario_update_id=2  WHERE id = 1",$resultado->sql);

        errores::$error = false;
    }

    public function test_limpia_traslado_exento(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $trs = new fc_nota_credito($this->link);
        $trs = new liberator($trs);



        $indice =  1;
        $registro =  array();
        $resultado = $trs->limpia_traslado_exento($indice, $registro);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEmpty($resultado);

        errores::$error = false;

    }

    public function test_partidas_base(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $trs = new fc_factura($this->link);
        //$trs = new liberator($trs);

        $modelo_partida = new fc_partida(link: $this->link);
        $registro_id = 1;

        $del = (new base_test())->del_fc_factura(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al del', data: $del);
            print_r($error);
            exit;
        }


        $alta = (new base_test())->alta_fc_partida(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al alta', data: $alta);
            print_r($error);
            exit;
        }

        $resultado = $trs->partidas_base($modelo_partida, $registro_id);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(1,$resultado[0]['fc_partida_id']);

        errores::$error = false;

    }
    public function test_permite_transaccion(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';


        $modelo = new fc_nota_credito(link: $this->link);
        $modelo = new liberator($modelo);

        $modelo_etapa = new fc_nota_credito_etapa(link: $this->link);

        $registro_id = 1;

        $resultado = $modelo->permite_transaccion($modelo_etapa, $registro_id);
        $this->assertIsBool($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertTrue($resultado);

        errores::$error = false;

    }



    public function test_valida_permite_transaccion(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';


        $modelo = new fc_complemento_pago(link: $this->link);
        $modelo = new liberator($modelo);
        $etapas = array();

        $resultado = $modelo->valida_permite_transaccion($etapas);
        $this->assertIsBool($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertTrue($resultado);

        errores::$error = false;

        $etapas[] = array('pr_etapa_descripcion'=>'CANCELADO');

        $resultado = $modelo->valida_permite_transaccion($etapas);
        $this->assertIsBool($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertNotTrue($resultado);

        errores::$error = false;

        $etapas = array();
        $etapas[] = array('pr_etapa_descripcion'=>'TIMBRADO');

        $resultado = $modelo->valida_permite_transaccion($etapas);
        $this->assertIsBool($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertNotTrue($resultado);

        errores::$error = false;
    }

    public function test_verifica_permite_transaccion(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';


        $modelo = new fc_factura(link: $this->link);
        $modelo_etapa = new fc_factura_etapa(link: $this->link);
        //$modelo = new liberator($modelo);
        $registro_id = 1;

        $resultado = $modelo->verifica_permite_transaccion($modelo_etapa, $registro_id);
        $this->assertIsBool($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertTrue($resultado);

        errores::$error = false;

    }




}

