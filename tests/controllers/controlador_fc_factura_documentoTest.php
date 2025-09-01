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

        $this->link->beginTransaction();

        $sql = "DELETE FROM fc_factura_documento WHERE id = 1";

        $exe = \gamboamartin\modelo\modelo::ejecuta_transaccion($sql, $this->link);
        if(errores::$error){
            $error = (new errores())->error('Error', $exe);
            print_r($error);
            exit;
        }

        $sql = "DELETE FROM doc_documento WHERE id = 1";

        $exe = \gamboamartin\modelo\modelo::ejecuta_transaccion($sql, $this->link);
        if(errores::$error){
            $error = (new errores())->error('Error', $exe);
            print_r($error);
            exit;
        }

        $sql = "INSERT INTO doc_documento (id, nombre, status, usuario_alta_id, usuario_update_id, fecha_alta, 
                                fecha_update, ruta_absoluta, ruta_relativa, doc_tipo_documento_id, 
                                doc_extension_id, descripcion, descripcion_select, codigo, alias, codigo_bis, name_out) 
                VALUES (1, '1', 'activo', 2, 2, '2025-09-01 11:31:44', '2025-09-01 11:31:44', '1A-000001.pdf', '1A-000001.pdf', 1, 1, 
                        '1', '1', '1', '1', '1', 'SN');";

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
        $this->assertEquals('test',$resultado);
        errores::$error = false;

        $this->link->rollBack();
    }



}

