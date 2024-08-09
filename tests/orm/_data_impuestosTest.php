<?php

namespace gamboamartin\facturacion\tests\orm;

use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\fc_retenido;
use gamboamartin\facturacion\models\fc_traslado;
use gamboamartin\facturacion\tests\base_test;

use gamboamartin\test\liberator;
use gamboamartin\test\test;
use stdClass;

class _data_impuestosTest extends test
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

    public function test_calcula_total(): void
    {
        errores::$error = false;

        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_retenido($this->link);
        $modelo = new liberator($modelo);

        $del = (new base_test())->del_cat_sat_factor(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error('Error al eliminar', $del);
            print_r($error);
            exit;
        }

        $alta = (new base_test())->alta_cat_sat_factor(link: $this->link, factor: .0125, id: 2);
        if(errores::$error){
            $error = (new errores())->error('Error al insertar', $alta);
            print_r($error);
            exit;
        }

        $cat_sat_factor_id = 2;
        $row_partida = new stdClass();
        $row_partida->sub_total = '850';

        $resultado = $modelo->calcula_total($cat_sat_factor_id, $row_partida);
        $this->assertIsFloat($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(10.63, $resultado);
        errores::$error = false;
    }

    public function test_get_data_row(): void
    {
        errores::$error = false;

        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_retenido($this->link);
        $modelo = new liberator($modelo);



        $del = (new base_test())->del_org_empresa(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error('Error al del', $del);
            print_r($error);
            exit;
        }

        $del = (new base_test())->del_fc_conf_traslado(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error('Error al del', $del);
            print_r($error);
            exit;
        }
        $del = (new base_test())->del_fc_conf_retenido(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error('Error al del', $del);
            print_r($error);
            exit;
        }

        $del = (new base_test())->del_cat_sat_tipo_factor(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error('Error al del', $del);
            print_r($error);
            exit;
        }

        $del = (new base_test())->del_cat_sat_factor(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error('Error al del', $del);
            print_r($error);
            exit;
        }


        $alta = (new base_test())->alta_fc_conf_traslado(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error('Error al insertar', $alta);
            print_r($error);
            exit;
        }
        $alta = (new base_test())->alta_fc_conf_retenido(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error('Error al insertar', $alta);
            print_r($error);
            exit;
        }



        $alta = (new base_test())->alta_fc_partida(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error('Error al insertar', $alta);
            print_r($error);
            exit;
        }

        $fc_partida_id = $alta->registro_id;

        $filtro['fc_partida.id'] = $fc_partida_id;
        $r_fc_retenido = (new fc_retenido(link: $this->link))->filtro_and(filtro: $filtro);
        if(errores::$error){
            $error = (new errores())->error('Error al obtener', $r_fc_retenido);
            print_r($error);
            exit;
        }

        $fc_retenido_id = $r_fc_retenido->registros[0]['fc_retenido_id'];


        $resultado = $modelo->get_data_row(registro_id: $fc_retenido_id);


        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals('0.01', $resultado['fc_retenido_total']);

        errores::$error = false;
    }

    public function test_get_data_rows(): void
    {
        errores::$error = false;

        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_retenido($this->link);
        $modelo = new liberator($modelo);

        $name_modelo_partida = 'fc_partida';
        $registro_partida_id = 1;

        $resultado = $modelo->get_data_rows($name_modelo_partida, $registro_partida_id);
        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals('1', $resultado->registros[0]['fc_partida_id']);

        errores::$error = false;
    }

    public function test_validaciones(): void
    {
        errores::$error = false;

        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_retenido($this->link);
        $modelo = new liberator($modelo);

        $data = array();
        $data['descripcion'] = 'a';
        $data['codigo'] = 'a';
        $data['vc_id'] = '1';
        $data['cat_sat_tipo_factor_id'] = '1';
        $data['cat_sat_factor_id'] = '1';
        $data['cat_sat_tipo_impuesto_id'] = '1';
        $name_modelo_partida = 'vc';
        $resultado = $modelo->validaciones($data, $name_modelo_partida);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        errores::$error = false;
    }


}

