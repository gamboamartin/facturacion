<?php
namespace gamboamartin\facturacion\tests\orm;


use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\fc_complemento_pago;
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


class fc_complemento_pagoTest extends test {
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

    public function test_data_factura(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';


        $modelo = new fc_complemento_pago($this->link);
        $modelo = new liberator($modelo);
        $modelo->registro_id = 1;

        $del = (new base_test())->del_org_empresa(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al eliminar',data:  $del);
            print_r($error);
            exit;
        }
        $del = (new base_test())->del_com_producto(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al eliminar',data:  $del);
            print_r($error);
            exit;
        }
        $del = (new base_test())->del_cat_sat_tipo_factor(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al eliminar',data:  $del);
            print_r($error);
            exit;
        }

        $del = (new base_test())->del_cat_sat_factor(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al eliminar',data:  $del);
            print_r($error);
            exit;
        }

        $alta = (new base_test())->alta_com_producto(link: $this->link,codigo: '99999999',id: 99999999);
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al insertar',data:  $alta);
            print_r($error);
            exit;
        }


        $alta = (new base_test())->alta_fc_partida(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al insertar',data:  $alta);
            print_r($error);
            exit;
        }


        $alta = (new base_test())->alta_fc_docto_relacionado(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al insertar',data:  $alta);
            print_r($error);
            exit;
        }

        $alta = (new base_test())->alta_cat_sat_tipo_factor(link: $this->link, id: 5);
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al insertar',data:  $alta);
            print_r($error);
            exit;
        }

        $alta = (new base_test())->alta_fc_traslado_dr_part(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al insertar',data:  $alta);
            print_r($error);
            exit;
        }



        $row_entidad = array();
        $row_entidad['fc_complemento_pago_sub_total'] = '1';
        $row_entidad['fc_complemento_pago_sub_total_base'] = '1';
        $row_entidad['fc_complemento_pago_total'] = '1';
        $row_entidad['fc_complemento_pago_total_descuento'] = '1';
        $row_entidad['dp_cp_descripcion'] = '1';
        $row_entidad['cat_sat_tipo_de_comprobante_codigo'] = '1';
        $row_entidad['cat_sat_moneda_codigo'] = '1';
        $row_entidad['fc_complemento_pago_exportacion'] = '1';
        $row_entidad['fc_complemento_pago_folio'] = '1';
        $row_entidad['fc_complemento_pago_forma_pago'] = '1';
        $row_entidad['cat_sat_metodo_pago_codigo'] = '1';
        $row_entidad['cat_sat_forma_pago_codigo'] = '1';
        $row_entidad['fc_complemento_pago_fecha'] = '1';
        $row_entidad['org_empresa_rfc'] = 'AAA010101AAA';
        $row_entidad['org_empresa_razon_social'] = '1';
        $row_entidad['cat_sat_regimen_fiscal_codigo'] = '1';
        $row_entidad['com_sucursal_id'] = '1';
        $row_entidad['cat_sat_uso_cfdi_codigo'] = '1';
        $row_entidad['conceptos'] = array();
        $row_entidad['total_impuestos_trasladados'] = '1';
        $row_entidad['total_impuestos_retenidos'] = '1';
        $resultado = $modelo->data_factura($row_entidad);

       // print_r($resultado);exit;

        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals('002',$resultado->Complemento[0]->Pagos20->Pago[0]->DoctoRelacionado[0]->ImpuestosDR->TrasladosDR[0]->ImpuestoDR);
        errores::$error = false;
    }

    public function test_fc_partida_cp_ins(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';


        $modelo = new fc_complemento_pago($this->link);
        $modelo = new liberator($modelo);

        $cat_sat_unidad_id = 1;
        $com_producto_id = 1;
        $fc_complemento_pago_id = 1;

        $resultado = $modelo->fc_partida_cp_ins($cat_sat_unidad_id, $com_producto_id, $fc_complemento_pago_id);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(1,$resultado['com_producto_id']);
        $this->assertEquals(1,$resultado['cantidad']);
        $this->assertEquals('Pago',$resultado['descripcion']);
        $this->assertEquals(0,$resultado['valor_unitario']);
        $this->assertEquals(0,$resultado['descuento']);
        $this->assertEquals(1,$resultado['cat_sat_unidad_id']);
        $this->assertEquals(1,$resultado['fc_complemento_pago_id']);
        $this->assertEquals(999,$resultado['cat_sat_conf_imps_id']);
        errores::$error = false;
    }



    public function test_integra_fc_row_p_part(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';


        $modelo = new fc_complemento_pago($this->link);
        $modelo = new liberator($modelo);

        $fc_traslado_p_part = array();
        $fc_traslado_p_part['fc_traslado_p_part_base_p'] = '-1.0';
        $fc_traslado_p_part['cat_sat_tipo_impuesto_codigo'] = '-1';
        $fc_traslado_p_part['cat_sat_tipo_factor_codigo'] = '-1';
        $fc_traslado_p_part['cat_sat_factor_factor'] = '-1';
        $fc_traslado_p_part['fc_traslado_p_part_importe_p'] = '-1';

        $resultado = $modelo->integra_fc_row_p_part($fc_traslado_p_part,'traslado');

        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals('-1.0',$resultado->BaseP);
        $this->assertEquals('-1',$resultado->ImpuestoP);
        $this->assertEquals('-1',$resultado->TipoFactorP);
        $this->assertEquals('-1.000000',$resultado->TasaOCuotaP);
        $this->assertEquals('-1',$resultado->ImporteP);



        errores::$error = false;
    }



}

