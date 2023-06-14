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

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->errores = new errores();
        $this->paths_conf = new stdClass();
        $this->paths_conf->generales = '/var/www/html/facturacion/config/generales.php';
        $this->paths_conf->database = '/var/www/html/facturacion/config/database.php';
        $this->paths_conf->views = '/var/www/html/facturacion/config/views.php';
    }

    public function test_get_data_row(): void
    {
        errores::$error = false;

        $_SESSION['grupo_id'] = 1;
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

        $del = (new base_test())->del_adm_seccion(link: $this->link);
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

        $alta = (new base_test())->alta_pr_etapa_proceso(link: $this->link,adm_seccion_descripcion: 'fc_factura');
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


}

