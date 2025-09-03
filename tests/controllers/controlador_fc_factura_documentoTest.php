<?php
namespace gamboamartin\facturacion\tests\controllers;


use base\orm\modelo;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\controllers\controlador_fc_factura;
use gamboamartin\facturacion\controllers\controlador_fc_factura_documento;
use gamboamartin\facturacion\controllers\controlador_fc_partida;
use gamboamartin\facturacion\controllers\pdf;
use gamboamartin\facturacion\models\fc_csd;
use gamboamartin\facturacion\models\fc_factura_documento;
use gamboamartin\facturacion\tests\base_test;
use gamboamartin\organigrama\models\org_empresa;
use gamboamartin\organigrama\models\org_sucursal;
use gamboamartin\test\liberator;
use gamboamartin\test\test;
use gamboamartin\facturacion\models\fc_factura;


use stdClass;


class controlador_fc_factura_documentoTest extends test {
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

    public function test_descarga(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'fc_factura';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';


        $delete = (new base_test())->delete_org_empresa($this->link);
        $ins_org_empresa = (new base_test())->insert_org_empresa($this->link);
        $ins_org_sucursal = (new base_test())->insert_org_sucursal($this->link);
        $ins_doc_documento = (new base_test())->insert_doc_documento($this->link);




        $sql = "INSERT INTO fc_factura (id, codigo, status, usuario_alta_id, usuario_update_id, fecha_alta, 
                        fecha_update, descripcion_select, alias, codigo_bis, fc_csd_id, folio, serie, 
                        cat_sat_forma_pago_id, cat_sat_metodo_pago_id, cat_sat_moneda_id, 
                        com_tipo_cambio_id, cat_sat_uso_cfdi_id, version, fecha, cat_sat_tipo_de_comprobante_id, 
                        dp_calle_pertenece_id, exportacion, cat_sat_regimen_fiscal_id, com_sucursal_id,
                        descripcion, observaciones, total_descuento, sub_total_base, sub_total, total_traslados, 
                        total_retenciones, aplica_saldo, total, monto_pago_nc, monto_pago_cp, saldo,
                        monto_saldo_aplicado, folio_fiscal, etapa, es_plantilla, cantidad, valor_unitario, descuento) 
                VALUES (1, 'A A-9999999', 'activo', 2, 2, '2025-08-21 17:48:37', '2025-08-21 20:21:02', 
                        'A-000001 RECURSOS Y RESULTADOS HARIMENI FIXMOBILE MEXICO', 
                        'A-000001 RECURSOS Y RESULTADOS HARIMENI FIXMOBILE MEXICO', 'A A-9999999', 1, 
                        'A-9999999', 'A', 99, 2, 161, 350, 3, '', '2025-08-21 09:48:58', 1, 632, '01', 601, 125, 
                        'A-9999999 RECURSOS Y RESULTADOS HARIMENI FIXMOBILE MEXICO', NULL, 0.0000, 91034.6600, 91034.6600, 
                        14565.5500, 0.0000, 'inactivo', 105600.2100, 0.0000, 0.0000, 105600.2100, 0.0000, 
                        '12b77e93-3d4e-4197-8bff-b1ce487f8dd4', 'CANCELADO', 'inactivo', 0.00, 0.00, 0.00);";

        $exe = \gamboamartin\modelo\modelo::ejecuta_transaccion($sql, $this->link);
        if(errores::$error){
            $error = (new errores())->error('Error', $exe);
            print_r($error);
            exit;
        }

        $sql = "INSERT INTO fc_factura_documento (id, codigo, status, usuario_alta_id, usuario_update_id, fecha_alta, 
                                  fecha_update, descripcion_select, alias, codigo_bis, descripcion, fc_factura_id, 
                                  doc_documento_id) VALUES (1, '1', 'actvo', 2, 2, '2025-09-01 11:35:21', 
                                                            '2025-09-01 11:35:21', '2', '2', '2', '2', 1, 1);";

        $exe = \gamboamartin\modelo\modelo::ejecuta_transaccion($sql, $this->link);
        if(errores::$error){
            $error = (new errores())->error('Error', $exe);
            print_r($error);
            exit;
        }

        $ctl = new controlador_fc_factura_documento(link: $this->link, paths_conf: $this->paths_conf);
        $ctl->registro_id = 1;
        //$ctl = new liberator($ctl);

        $header = false;
        $resultado = $ctl->descarga($header);
        //print_r($resultado);exit;
        $this->assertIsString($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertStringContainsString('test',$resultado);
        errores::$error = false;


    }



}

