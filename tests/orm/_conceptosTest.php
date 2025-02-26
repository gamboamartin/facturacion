<?php

namespace gamboamartin\facturacion\tests\orm;

use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\_conceptos;
use gamboamartin\facturacion\models\fc_cuenta_predial;
use gamboamartin\facturacion\models\fc_factura;
use gamboamartin\facturacion\models\fc_factura_etapa;
use gamboamartin\facturacion\models\fc_partida;
use gamboamartin\facturacion\models\fc_retenido;
use gamboamartin\facturacion\models\fc_traslado;
use gamboamartin\test\liberator;
use gamboamartin\test\test;


use stdClass;


class _conceptosTest extends test
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

    public function test_acumula_totales(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $trs = new _conceptos();
        $trs = new liberator($trs);


        $data_partida = new stdClass();
        $data_partida->partida['cat_sat_producto_codigo'] = 'cat_sat_producto_codigo';
        $modelo_partida = new fc_partida($this->link);
        $total_impuestos_retenidos = 10;
        $total_impuestos_trasladados = 10;

        $data_partida->partida['fc_partida_total_traslados'] = 15;

        $resultado = $trs->acumula_totales($data_partida, $modelo_partida, $total_impuestos_retenidos, $total_impuestos_trasladados);

        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(10,$resultado->total_impuestos_retenidos);
        $this->assertEquals(25,$resultado->total_impuestos_trasladados);

        errores::$error = false;

    }
    public function test_concepto(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $trs = new _conceptos();
        $trs = new liberator($trs);


        $data_partida = new stdClass();
        $keys_part = new stdClass();
        $keys_part->cantidad = 'cantidad';
        $keys_part->descripcion = 'descripcion';
        $keys_part->valor_unitario = 'valor_unitario';
        $keys_part->importe = 'importe';

        $data_partida->partida = array();
        $data_partida->partida['cat_sat_producto_codigo'] = 'cat_sat_producto_codigo';
        $data_partida->partida['cantidad'] = '1';
        $data_partida->partida['cat_sat_unidad_codigo'] = 'cat_sat_unidad_codigo';
        $data_partida->partida['descripcion'] = 'descripcion';
        $data_partida->partida['valor_unitario'] = '2';
        $data_partida->partida['importe'] = '3';
        $data_partida->partida['cat_sat_obj_imp_codigo'] = 'cat_sat_obj_imp_codigo';
        $data_partida->partida['com_producto_codigo'] = 'com_producto_codigo';
        $data_partida->partida['cat_sat_unidad_descripcion'] = 'cat_sat_unidad_descripcion';

        $resultado = $trs->concepto($data_partida, $keys_part);

        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals('cat_sat_producto_codigo',$resultado->clave_prod_serv);
        $this->assertEquals(1,$resultado->cantidad);
        $this->assertEquals('cat_sat_unidad_codigo',$resultado->clave_unidad);
        $this->assertEquals('descripcion',$resultado->descripcion);
        $this->assertEquals('2.00',$resultado->valor_unitario);
        $this->assertEquals('3.00',$resultado->importe);
        $this->assertEquals('cat_sat_obj_imp_codigo',$resultado->objeto_imp);
        $this->assertEquals('com_producto_codigo',$resultado->no_identificacion);
        $this->assertEquals('cat_sat_unidad_descripcion',$resultado->unidad);

        errores::$error = false;

    }


    public function test_cuenta_predial(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $trs = new _conceptos();
        $trs = new liberator($trs);


        $concepto = new stdClass();
        $modelo_predial = new fc_cuenta_predial($this->link);
        $partida = array();
        $registro_id = 1;
        $resultado = $trs->cuenta_predial($concepto,'fc_factura.id',$modelo_predial,$partida,$registro_id);
        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);

        errores::$error = false;

    }

    public function test_data_partida(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $trs = new _conceptos();
        $trs = new liberator($trs);


        $modelo_partida = new fc_partida($this->link);
        $modelo_retencion = new fc_retenido($this->link);
        $modelo_traslado = new fc_traslado($this->link);
        $partida = array();
        $partida['fc_partida_id'] = 1;

        $resultado = $trs->data_partida($modelo_partida,$modelo_retencion,$modelo_traslado,$partida);
        //print_r($resultado);exit;
        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertIsArray($resultado->retenidos->registros);
        errores::$error = false;

    }

    public function test_descuento(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $trs = new _conceptos();
        $trs = new liberator($trs);

        $data_partida = new stdClass();
        $key_descuento = 'descuento';
        $data_partida->partida[$key_descuento] = '$10.00';
        $resultado = $trs->descuento($data_partida,$key_descuento);
        $this->assertIsNumeric($resultado);
        $this->assertEquals('10.00',$resultado);
        $this->assertNotTrue(errores::$error);
        errores::$error = false;
    }

    public function test_fc_cuenta_predial_numero(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $trs = new _conceptos();
        $trs = new liberator($trs);


        $modelo_fc_retenido = new fc_retenido($this->link);

        $del = $modelo_fc_retenido->elimina_todo();
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al $del $modelo_fc_retenido',data: $del);
            print_r($error);
            exit;
        }


        $modelo_factura_etapa = new fc_factura_etapa($this->link);

        $del = $modelo_factura_etapa->elimina_todo();
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al $del $modelo_factura_etapa',data: $del);
            print_r($error);
            exit;
        }

        $modelo_factura = new fc_factura($this->link);
        $modelo_partida = new fc_partida($this->link);
        $modelo_predial = new fc_cuenta_predial($this->link);

        $del = $modelo_predial->elimina_todo();
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al $del $modelo_predial',data: $del);
            print_r($error);
            exit;
        }

        $del = $modelo_partida->elimina_todo();
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al $del $modelo_partida',data: $del);
            print_r($error);
            exit;
        }

        $del = $modelo_factura->elimina_todo();
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al $del fc_factura',data: $del);
            print_r($error);
            exit;
        }

        $factura['id'] = 1;
        $factura['fc_csd_id'] = 1;
        $factura['com_sucursal_id'] = 1;
        $factura['cat_sat_metodo_pago_id'] = 1;
        $factura['cat_sat_forma_pago_id'] = 3;
        $factura['cat_sat_moneda_id'] = 161;
        $factura['com_tipo_cambio_id'] = 1;
        $factura['cat_sat_uso_cfdi_id'] = 3;
        $factura['cat_sat_tipo_de_comprobante_id'] = 1;
        $factura['exportacion'] = '02';


        $alta = $modelo_factura->alta_registro($factura);
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al $alta $factura',data: $alta);
            print_r($error);
            exit;
        }


        $partida['com_producto_id'] = 84111506;
        $partida['fc_factura_id'] = 1;
        $partida['cantidad'] = 1;

        $alta = $modelo_partida->alta_registro($partida);
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al $alta $partida',data: $alta);
            print_r($error);
            exit;
        }



        $del = $modelo_predial->elimina_todo();
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al $del predial',data: $del);
            print_r($error);
            exit;
        }

        $fc_cuenta_predial_ins['fc_partida_id'] = 1;
        $fc_cuenta_predial_ins['descripcion'] = 'xxx';

        $r_alta = $modelo_predial->alta_registro($fc_cuenta_predial_ins);
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al insertar predial',data: $r_alta);
            print_r($error);
            exit;
        }

        $registro_id= 1;
        $resultado = $trs->fc_cuenta_predial_numero('fc_factura.id',$modelo_predial,$registro_id);
        $this->assertIsString($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals('xxx',$resultado);


        errores::$error = false;

    }

    public function test_genera_concepto(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $trs = new _conceptos();
        $trs = new liberator($trs);

        $modelo_partida = new fc_partida($this->link);
        $modelo_retencion = new fc_retenido($this->link);
        $modelo_traslado = new fc_traslado($this->link);
        $modelo_predial = new fc_cuenta_predial($this->link);
        $data_partida = new stdClass();
        $data_partida->partida['cat_sat_producto_codigo'] = 'cat_sat_producto_codigo';
        $data_partida->partida['fc_partida_cantidad'] = '15';
        $data_partida->partida['cat_sat_unidad_codigo'] = 'cat_sat_unidad_codigo';
        $data_partida->partida['fc_partida_descripcion'] = 'fc_partida_descripcion';
        $data_partida->partida['fc_partida_valor_unitario'] = '20';
        $data_partida->partida['fc_partida_sub_total_base'] = '30';
        $data_partida->partida['cat_sat_obj_imp_codigo'] = 'cat_sat_obj_imp_codigo';
        $data_partida->partida['com_producto_codigo'] = 'com_producto_codigo';
        $data_partida->partida['cat_sat_unidad_descripcion'] = 'cat_sat_unidad_descripcion';
        $data_partida->traslados = new stdClass();
        $data_partida->retenidos = new stdClass();

        $key_filtro_id = 'a';
        $registro_id = 1;

        $resultado = $trs->genera_concepto($data_partida, $key_filtro_id, $modelo_partida, $modelo_predial,
            $modelo_retencion, $modelo_traslado, $registro_id);

        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals("30.00", $resultado->importe);


        errores::$error = false;

    }

    public function test_get_impuestos_partida(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $trs = new _conceptos();
        $trs = new liberator($trs);

        $modelo_partida = new fc_partida($this->link);
        $modelo_retencion = new fc_retenido($this->link);
        $modelo_traslado = new fc_traslado($this->link);
        $partida = array();
        $partida['fc_partida_id'] = 1;

        $resultado = $trs->get_impuestos_partida($modelo_partida,$modelo_retencion,$modelo_traslado,$partida);

        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals("SELECT fc_traslado.id AS fc_traslado_id, fc_traslado.codigo AS fc_traslado_codigo, fc_traslado.status AS fc_traslado_status, fc_traslado.usuario_alta_id AS fc_traslado_usuario_alta_id, fc_traslado.usuario_update_id AS fc_traslado_usuario_update_id, fc_traslado.fecha_alta AS fc_traslado_fecha_alta, fc_traslado.fecha_update AS fc_traslado_fecha_update, fc_traslado.descripcion_select AS fc_traslado_descripcion_select, fc_traslado.alias AS fc_traslado_alias, fc_traslado.codigo_bis AS fc_traslado_codigo_bis, fc_traslado.descripcion AS fc_traslado_descripcion, fc_traslado.fc_partida_id AS fc_traslado_fc_partida_id, fc_traslado.cat_sat_tipo_factor_id AS fc_traslado_cat_sat_tipo_factor_id, fc_traslado.cat_sat_factor_id AS fc_traslado_cat_sat_factor_id, fc_traslado.cat_sat_tipo_impuesto_id AS fc_traslado_cat_sat_tipo_impuesto_id, fc_traslado.total AS fc_traslado_total, fc_partida.id AS fc_partida_id, fc_partida.codigo AS fc_partida_codigo, fc_partida.status AS fc_partida_status, fc_partida.usuario_alta_id AS fc_partida_usuario_alta_id, fc_partida.usuario_update_id AS fc_partida_usuario_update_id, fc_partida.fecha_alta AS fc_partida_fecha_alta, fc_partida.fecha_update AS fc_partida_fecha_update, fc_partida.descripcion_select AS fc_partida_descripcion_select, fc_partida.alias AS fc_partida_alias, fc_partida.codigo_bis AS fc_partida_codigo_bis, fc_partida.com_producto_id AS fc_partida_com_producto_id, fc_partida.cantidad AS fc_partida_cantidad, fc_partida.descripcion AS fc_partida_descripcion, fc_partida.valor_unitario AS fc_partida_valor_unitario, fc_partida.descuento AS fc_partida_descuento, fc_partida.fc_factura_id AS fc_partida_fc_factura_id, fc_partida.sub_total_base AS fc_partida_sub_total_base, fc_partida.sub_total AS fc_partida_sub_total, fc_partida.total AS fc_partida_total, fc_partida.total_traslados AS fc_partida_total_traslados, fc_partida.total_retenciones AS fc_partida_total_retenciones, fc_partida.cat_sat_obj_imp_id AS fc_partida_cat_sat_obj_imp_id, cat_sat_tipo_factor.id AS cat_sat_tipo_factor_id, cat_sat_tipo_factor.descripcion AS cat_sat_tipo_factor_descripcion, cat_sat_tipo_factor.codigo AS cat_sat_tipo_factor_codigo, cat_sat_tipo_factor.status AS cat_sat_tipo_factor_status, cat_sat_tipo_factor.usuario_alta_id AS cat_sat_tipo_factor_usuario_alta_id, cat_sat_tipo_factor.usuario_update_id AS cat_sat_tipo_factor_usuario_update_id, cat_sat_tipo_factor.fecha_alta AS cat_sat_tipo_factor_fecha_alta, cat_sat_tipo_factor.fecha_update AS cat_sat_tipo_factor_fecha_update, cat_sat_tipo_factor.descripcion_select AS cat_sat_tipo_factor_descripcion_select, cat_sat_tipo_factor.alias AS cat_sat_tipo_factor_alias, cat_sat_tipo_factor.codigo_bis AS cat_sat_tipo_factor_codigo_bis, cat_sat_tipo_factor.predeterminado AS cat_sat_tipo_factor_predeterminado, cat_sat_factor.id AS cat_sat_factor_id, cat_sat_factor.codigo AS cat_sat_factor_codigo, cat_sat_factor.status AS cat_sat_factor_status, cat_sat_factor.usuario_alta_id AS cat_sat_factor_usuario_alta_id, cat_sat_factor.usuario_update_id AS cat_sat_factor_usuario_update_id, cat_sat_factor.fecha_alta AS cat_sat_factor_fecha_alta, cat_sat_factor.fecha_update AS cat_sat_factor_fecha_update, cat_sat_factor.descripcion_select AS cat_sat_factor_descripcion_select, cat_sat_factor.alias AS cat_sat_factor_alias, cat_sat_factor.codigo_bis AS cat_sat_factor_codigo_bis, cat_sat_factor.factor AS cat_sat_factor_factor, cat_sat_tipo_impuesto.id AS cat_sat_tipo_impuesto_id, cat_sat_tipo_impuesto.descripcion AS cat_sat_tipo_impuesto_descripcion, cat_sat_tipo_impuesto.codigo AS cat_sat_tipo_impuesto_codigo, cat_sat_tipo_impuesto.status AS cat_sat_tipo_impuesto_status, cat_sat_tipo_impuesto.usuario_alta_id AS cat_sat_tipo_impuesto_usuario_alta_id, cat_sat_tipo_impuesto.usuario_update_id AS cat_sat_tipo_impuesto_usuario_update_id, cat_sat_tipo_impuesto.fecha_alta AS cat_sat_tipo_impuesto_fecha_alta, cat_sat_tipo_impuesto.fecha_update AS cat_sat_tipo_impuesto_fecha_update, cat_sat_tipo_impuesto.descripcion_select AS cat_sat_tipo_impuesto_descripcion_select, cat_sat_tipo_impuesto.alias AS cat_sat_tipo_impuesto_alias, cat_sat_tipo_impuesto.codigo_bis AS cat_sat_tipo_impuesto_codigo_bis, com_producto.id AS com_producto_id, com_producto.descripcion AS com_producto_descripcion, com_producto.codigo AS com_producto_codigo, com_producto.status AS com_producto_status, com_producto.usuario_alta_id AS com_producto_usuario_alta_id, com_producto.usuario_update_id AS com_producto_usuario_update_id, com_producto.fecha_alta AS com_producto_fecha_alta, com_producto.fecha_update AS com_producto_fecha_update, com_producto.descripcion_select AS com_producto_descripcion_select, com_producto.alias AS com_producto_alias, com_producto.codigo_bis AS com_producto_codigo_bis, com_producto.cat_sat_producto_id AS com_producto_cat_sat_producto_id, com_producto.cat_sat_unidad_id AS com_producto_cat_sat_unidad_id, com_producto.cat_sat_obj_imp_id AS com_producto_cat_sat_obj_imp_id, com_producto.com_tipo_producto_id AS com_producto_com_tipo_producto_id, com_producto.aplica_predial AS com_producto_aplica_predial, com_producto.cat_sat_conf_imps_id AS com_producto_cat_sat_conf_imps_id, com_producto.es_automatico AS com_producto_es_automatico, com_producto.precio AS com_producto_precio, com_producto.codigo_sat AS com_producto_codigo_sat, com_producto.cat_sat_cve_prod_id AS com_producto_cat_sat_cve_prod_id, fc_factura.id AS fc_factura_id, fc_factura.codigo AS fc_factura_codigo, fc_factura.status AS fc_factura_status, fc_factura.usuario_alta_id AS fc_factura_usuario_alta_id, fc_factura.usuario_update_id AS fc_factura_usuario_update_id, fc_factura.fecha_alta AS fc_factura_fecha_alta, fc_factura.fecha_update AS fc_factura_fecha_update, fc_factura.descripcion_select AS fc_factura_descripcion_select, fc_factura.alias AS fc_factura_alias, fc_factura.codigo_bis AS fc_factura_codigo_bis, fc_factura.fc_csd_id AS fc_factura_fc_csd_id, fc_factura.folio AS fc_factura_folio, fc_factura.serie AS fc_factura_serie, fc_factura.cat_sat_forma_pago_id AS fc_factura_cat_sat_forma_pago_id, fc_factura.cat_sat_metodo_pago_id AS fc_factura_cat_sat_metodo_pago_id, fc_factura.cat_sat_moneda_id AS fc_factura_cat_sat_moneda_id, fc_factura.com_tipo_cambio_id AS fc_factura_com_tipo_cambio_id, fc_factura.cat_sat_uso_cfdi_id AS fc_factura_cat_sat_uso_cfdi_id, fc_factura.version AS fc_factura_version, fc_factura.fecha AS fc_factura_fecha, fc_factura.cat_sat_tipo_de_comprobante_id AS fc_factura_cat_sat_tipo_de_comprobante_id, fc_factura.dp_calle_pertenece_id AS fc_factura_dp_calle_pertenece_id, fc_factura.exportacion AS fc_factura_exportacion, fc_factura.cat_sat_regimen_fiscal_id AS fc_factura_cat_sat_regimen_fiscal_id, fc_factura.com_sucursal_id AS fc_factura_com_sucursal_id, fc_factura.descripcion AS fc_factura_descripcion, fc_factura.observaciones AS fc_factura_observaciones, fc_factura.total_descuento AS fc_factura_total_descuento, fc_factura.sub_total_base AS fc_factura_sub_total_base, fc_factura.sub_total AS fc_factura_sub_total, fc_factura.total_traslados AS fc_factura_total_traslados, fc_factura.total_retenciones AS fc_factura_total_retenciones, fc_factura.aplica_saldo AS fc_factura_aplica_saldo, fc_factura.total AS fc_factura_total, fc_factura.monto_pago_nc AS fc_factura_monto_pago_nc, fc_factura.monto_pago_cp AS fc_factura_monto_pago_cp, fc_factura.saldo AS fc_factura_saldo, fc_factura.monto_saldo_aplicado AS fc_factura_monto_saldo_aplicado, fc_factura.folio_fiscal AS fc_factura_folio_fiscal, fc_factura.etapa AS fc_factura_etapa, fc_factura.es_plantilla AS fc_factura_es_plantilla, fc_factura.cantidad AS fc_factura_cantidad, fc_factura.valor_unitario AS fc_factura_valor_unitario, fc_factura.descuento AS fc_factura_descuento,(fc_partida.sub_total_base) AS fc_partida_importe,(fc_partida.sub_total) AS fc_partida_importe_con_descuento,fc_traslado.total AS fc_traslado_importe FROM fc_traslado AS fc_traslado LEFT JOIN fc_partida AS fc_partida ON fc_partida.id = fc_traslado.fc_partida_id LEFT JOIN cat_sat_tipo_factor AS cat_sat_tipo_factor ON cat_sat_tipo_factor.id = fc_traslado.cat_sat_tipo_factor_id LEFT JOIN cat_sat_factor AS cat_sat_factor ON cat_sat_factor.id = fc_traslado.cat_sat_factor_id LEFT JOIN cat_sat_tipo_impuesto AS cat_sat_tipo_impuesto ON cat_sat_tipo_impuesto.id = fc_traslado.cat_sat_tipo_impuesto_id LEFT JOIN com_producto AS com_producto ON com_producto.id = fc_partida.com_producto_id LEFT JOIN fc_factura AS fc_factura ON fc_factura.id = fc_partida.fc_factura_id WHERE ((fc_partida.id = '1'))",
            $resultado->traslados->sql);

        $this->assertIsArray( $resultado->retenidos->registros);


        errores::$error = false;

    }

    public function test_impuestos_globales(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $trs = new _conceptos();
        //$trs = new liberator($trs);

        $modelo_partida = new fc_partida($this->link);
        $modelo_retencion = new fc_retenido($this->link);
        $modelo_traslado = new fc_traslado($this->link);
        $data_partida = new stdClass();
        $data_partida->traslados = new stdClass();
        $data_partida->traslados->registros[0] = array();
        $data_partida->traslados->registros[0]['cat_sat_tipo_factor_id'] = '1';
        $data_partida->traslados->registros[0]['cat_sat_factor_id'] = '1';
        $data_partida->traslados->registros[0]['cat_sat_tipo_impuesto_id'] = '1';
        $data_partida->retenidos = new stdClass();
        $ret_global = array();
        $trs_global = array();

        $resultado = $trs->impuestos_globales($data_partida, $modelo_partida, $modelo_retencion, $modelo_traslado,
            $ret_global, $trs_global);


        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals("0.00", $resultado->trs_global['1.1.1']->base);



        errores::$error = false;

    }

    public function test_integra_descuento(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $trs = new _conceptos();
        $trs = new liberator($trs);

        $modelo_partida = new fc_partida($this->link);
        $data_partida = new stdClass();
        $data_partida->partida = array();
        $data_partida->partida['cat_sat_producto_codigo'] = 'cat_sat_producto_codigo';
        $data_partida->partida['fc_partida_cantidad'] = '1';
        $data_partida->partida['cat_sat_unidad_codigo'] = 'cat_sat_unidad_codigo';
        $data_partida->partida['fc_partida_descripcion'] = 'fc_partida_descripcion';
        $data_partida->partida['fc_partida_valor_unitario'] = '2';
        $data_partida->partida['fc_partida_sub_total_base'] = '3';
        $data_partida->partida['cat_sat_obj_imp_codigo'] = 'cat_sat_obj_imp_codigo';
        $data_partida->partida['com_producto_codigo'] = 'com_producto_codigo';
        $data_partida->partida['cat_sat_unidad_descripcion'] = 'cat_sat_unidad_descripcion';
        $data_partida->partida['fc_partida_descuento'] = '2';
        $resultado = $trs->integra_descuento($data_partida,$modelo_partida);

        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals("cat_sat_producto_codigo", $resultado->clave_prod_serv);
        $this->assertEquals("1", $resultado->cantidad);
        $this->assertEquals("cat_sat_unidad_codigo", $resultado->clave_unidad);
        $this->assertEquals("fc_partida_descripcion", $resultado->descripcion);
        $this->assertEquals("2.00", $resultado->valor_unitario);
        $this->assertEquals("3.00", $resultado->importe);
        $this->assertEquals("cat_sat_obj_imp_codigo", $resultado->objeto_imp);
        $this->assertEquals("com_producto_codigo", $resultado->no_identificacion);
        $this->assertEquals("cat_sat_unidad_descripcion", $resultado->unidad);
        $this->assertEquals("2", $resultado->descuento);



        errores::$error = false;

    }

    public function test_integra_retenciones(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $trs = new _conceptos();
        $trs = new liberator($trs);

        $modelo_partida = new fc_partida($this->link);
        $modelo_retencion = new fc_retenido($this->link);
        $data_partida = new stdClass();
        $data_partida->retenidos = new stdClass();
        $data_partida->retenidos->registros = array();
        $data_partida->retenidos->registros[] = array();

        $concepto = new stdClass();

        $resultado = $trs->integra_retenciones(concepto: $concepto,data_partida:  $data_partida,
            modelo_partida:  $modelo_partida,modelo_retencion:  $modelo_retencion);

        //print_r($resultado);exit;
        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals("0.00", $resultado->impuestos[0]->retenciones[0]->base);
        $this->assertEquals("", $resultado->impuestos[0]->retenciones[0]->impuesto);
        $this->assertEquals("", $resultado->impuestos[0]->retenciones[0]->tipo_factor);
        $this->assertEquals("0.000000", $resultado->impuestos[0]->retenciones[0]->tasa_o_cuota);
        $this->assertEquals("0.00", $resultado->impuestos[0]->retenciones[0]->importe);




        errores::$error = false;

    }
    public function test_integra_traslados(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $trs = new _conceptos();
        $trs = new liberator($trs);

        $modelo_partida = new fc_partida($this->link);
        $modelo_traslado = new fc_traslado($this->link);
        $data_partida = new stdClass();
        $data_partida->traslados = new stdClass();
        $data_partida->traslados->registros = array();
        $data_partida->traslados->registros[] = array();

        $concepto = new stdClass();

        $resultado = $trs->integra_traslados(concepto: $concepto,data_partida:  $data_partida,
            modelo_partida:  $modelo_partida,modelo_traslado:  $modelo_traslado);

        //print_r($resultado);exit;
        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals("0.00", $resultado->impuestos[0]->traslados[0]->base);
        $this->assertEquals("", $resultado->impuestos[0]->traslados[0]->impuesto);
        $this->assertEquals("", $resultado->impuestos[0]->traslados[0]->tipo_factor);
        $this->assertEquals("0.000000", $resultado->impuestos[0]->traslados[0]->tasa_o_cuota);
        $this->assertEquals("0.00", $resultado->impuestos[0]->traslados[0]->importe);




        errores::$error = false;

    }

    public function test_keys_partida(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $trs = new _conceptos();
        $trs = new liberator($trs);

        $modelo_partida = new fc_partida($this->link);

        $resultado = $trs->keys_partida($modelo_partida);
        //print_r($resultado);exit;

        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals("fc_partida_cantidad", $resultado->cantidad);
        $this->assertEquals("fc_partida_descripcion", $resultado->descripcion);
        $this->assertEquals("fc_partida_valor_unitario", $resultado->valor_unitario);
        $this->assertEquals("fc_partida_sub_total_base", $resultado->importe);
        $this->assertEquals("fc_partida_descuento", $resultado->descuento);



        errores::$error = false;

    }

    public function test_inicializa_impuestos_de_concepto(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $trs = new _conceptos();
        $trs = new liberator($trs);

        $concepto = new stdClass();

        $resultado = $trs->inicializa_impuestos_de_concepto($concepto);
        //print_r($resultado);exit;
        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEmpty($resultado->impuestos[0]->traslados);
        $this->assertEmpty($resultado->impuestos[0]->retenciones);

        errores::$error = false;

    }

    public function test_r_fc_cuenta_predial(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';


        $modelo = new _conceptos();
        $modelo = new liberator($modelo);

        $modelo_predial = new fc_cuenta_predial(link: $this->link);

        $del = $modelo_predial->elimina_todo();
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al $del predial',data: $del);
            print_r($error);
            exit;
        }

        $modelo_partida = new fc_partida(link: $this->link);
        $partida['com_producto_id'] = 84111506;
        $partida['fc_factura_id'] = 1;
        $partida['cantidad'] = 1;

        $alta = $modelo_partida->alta_registro($partida);
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al $alta $partida',data: $alta);
            print_r($error);
            exit;
        }


        $fc_cuenta_predial_ins['fc_partida_id'] = 1;
        $fc_cuenta_predial_ins['descripcion'] = 'xxx';

        $r_alta = $modelo_predial->alta_registro($fc_cuenta_predial_ins);
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al insertar predial',data: $r_alta);
            print_r($error);
            exit;
        }

        $registro_id = 1;

        $resultado = $modelo->r_fc_cuenta_predial('fc_factura.id',$modelo_predial, $registro_id);

        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals('xxx',$resultado->registros[0]['fc_cuenta_predial_descripcion']);

        errores::$error = false;

    }

    public function test_valida_partida(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';


        $modelo = new _conceptos();
        $modelo = new liberator($modelo);

        $data_partida = new stdClass();
        $data_partida->partida['cat_sat_producto_codigo'] = 'cat_sat_producto_codigo';
        $data_partida->partida['cantidad'] = '1';
        $data_partida->partida['cat_sat_unidad_codigo'] = 'cat_sat_unidad_codigo';
        $data_partida->partida['descripcion'] = 'descripcion';
        $data_partida->partida['valor_unitario'] = '2';
        $data_partida->partida['importe'] = '4';
        $data_partida->partida['cat_sat_obj_imp_codigo'] = 'cat_sat_obj_imp_codigo';
        $data_partida->partida['com_producto_codigo'] = 'com_producto_codigo';
        $data_partida->partida['cat_sat_unidad_descripcion'] = 'cat_sat_unidad_descripcion';
        $keys_part = new stdClass();
        $keys_part->cantidad = 'cantidad';
        $keys_part->descripcion = 'descripcion';
        $keys_part->valor_unitario = 'valor_unitario';
        $keys_part->importe = 'importe';


        $resultado = $modelo->valida_partida($data_partida,$keys_part);

        $this->assertIsBool($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertTrue($resultado);

        errores::$error = false;

    }

    public function test_verifica_partida(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';


        $modelo = new _conceptos();
        $modelo = new liberator($modelo);

        $data_partida = new stdClass();
        $data_partida->partida['cat_sat_producto_codigo'] = 'cat_sat_producto_codigo';
        $data_partida->partida['fc_partida_cantidad'] = '1';
        $data_partida->partida['cat_sat_unidad_codigo'] = 'cat_sat_unidad_codigo';
        $data_partida->partida['fc_partida_descripcion'] = 'descripcion';
        $data_partida->partida['fc_partida_valor_unitario'] = '2';
        $data_partida->partida['fc_partida_sub_total_base'] = '4';
        $data_partida->partida['cat_sat_obj_imp_codigo'] = 'cat_sat_obj_imp_codigo';
        $data_partida->partida['com_producto_codigo'] = 'com_producto_codigo';
        $data_partida->partida['cat_sat_unidad_descripcion'] = 'cat_sat_unidad_descripcion';

        $modelo_partida = new fc_partida($this->link);


        $resultado = $modelo->verifica_partida($data_partida,$modelo_partida);

        $this->assertIsBool($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertTrue($resultado);

        errores::$error = false;

    }

}

