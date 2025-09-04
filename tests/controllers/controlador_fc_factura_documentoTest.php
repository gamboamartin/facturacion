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
        $ins_fc_factura_documento = (new base_test())->insert_fc_factura_documento($this->link);

        
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

