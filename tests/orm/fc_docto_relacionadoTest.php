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
    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
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
        $_SESSION['grupo_id'] = 1;
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
        $del = (new base_test())->del_adm_seccion(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al del',data:  $del);
            print_r($error);exit;
        }

        $alta = (new base_test())->alta_pr_etapa_proceso(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al insertar',data:  $alta);
            print_r($error);exit;
        }

        $alta = (new base_test())->alta_fc_partida(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al insertar',data:  $alta);
            print_r($error);exit;
        }

        $alta = (new base_test())->alta_com_producto(link: $this->link, codigo: '84111506', id: '84111506');
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al insertar',data:  $alta);
            print_r($error);exit;
        }

        $del = (new base_test())->del_adm_seccion(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al del',data:  $del);
            print_r($error);exit;
        }

        $alta = (new base_test())->alta_pr_etapa_proceso(link: $this->link,adm_seccion_descripcion: 'fc_complemento_pago');
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



}

