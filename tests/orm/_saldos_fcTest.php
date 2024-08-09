<?php

namespace gamboamartin\facturacion\tests\orm;

use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\_email;
use gamboamartin\facturacion\models\_facturacion;
use gamboamartin\facturacion\models\_saldos_fc;
use gamboamartin\facturacion\models\fc_complemento_pago;
use gamboamartin\facturacion\models\fc_complemento_pago_etapa;
use gamboamartin\facturacion\models\fc_factura;
use gamboamartin\facturacion\models\fc_factura_etapa;
use gamboamartin\facturacion\models\fc_partida;
use gamboamartin\facturacion\models\fc_partida_cp;
use gamboamartin\facturacion\models\fc_partida_nc;
use gamboamartin\facturacion\models\fc_relacion;
use gamboamartin\facturacion\models\fc_retencion_dr_part;
use gamboamartin\facturacion\models\fc_retenido;
use gamboamartin\facturacion\models\fc_retenido_cp;
use gamboamartin\facturacion\models\fc_traslado;
use gamboamartin\facturacion\tests\base_test;
use gamboamartin\js_base\eventos\adm_seccion;
use gamboamartin\test\liberator;
use gamboamartin\test\test;


use stdClass;


class _saldos_fcTest extends test
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

    public function test_get_pagos_nc(){

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        errores::$error = false;
        $modelo = new _saldos_fc($this->link);
        $modelo = new liberator($modelo);

        $del = (new base_test())->del_fc_factura(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error('Error al eliminar',$del);
            print_r($error);
            exit;
        }


        $resultado = $modelo->get_pagos_nc(1, $this->link);
        $this->assertIsFloat($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(0.0, $resultado);

        errores::$error = false;

        $del = (new base_test())->del_fc_complemento_pago(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error('Error al eliminar',$del);
            print_r($error);
            exit;
        }

        $del = (new base_test())->del_fc_nota_credito(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error('Error al eliminar',$del);
            print_r($error);
            exit;
        }

        $del = (new base_test())->del_fc_uuid(link: $this->link);
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

        $del = (new base_test())->del_com_tipo_cambio(link: $this->link);
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


        $resultado = $modelo->get_pagos_nc(1, $this->link);
        $this->assertIsFloat($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(0.0, $resultado);

        errores::$error = false;



        $alta = (new base_test())->alta_fc_nc_rel(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error('Error al insertar',$alta);
            print_r($error);
            exit;
        }

        $resultado = $modelo->get_pagos_nc(1, $this->link);
        $this->assertIsFloat($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(0.0, $resultado);


        errores::$error = false;

        $alta = (new base_test())->alta_fc_nc_rel(link: $this->link, id: 2, monto_aplicado_factura : 1);
        if(errores::$error){
            $error = (new errores())->error('Error al insertar',$alta);
            print_r($error);
            exit;
        }


        $resultado = $modelo->get_pagos_nc(1, $this->link);
        $this->assertIsFloat($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(1.0, $resultado);


        errores::$error = false;

    }


}

