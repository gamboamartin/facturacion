<?php

namespace gamboamartin\facturacion\tests\orm;

use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\_email;
use gamboamartin\facturacion\models\_facturacion;
use gamboamartin\facturacion\models\_plantilla;
use gamboamartin\facturacion\models\fc_factura;
use gamboamartin\facturacion\models\fc_partida;
use gamboamartin\facturacion\models\fc_relacion;
use gamboamartin\facturacion\models\fc_relacion_nc;
use gamboamartin\facturacion\models\fc_retenido;
use gamboamartin\facturacion\models\fc_traslado;
use gamboamartin\facturacion\models\fc_uuid_nc;
use gamboamartin\facturacion\tests\base_test;
use gamboamartin\test\liberator;
use gamboamartin\test\test;


use stdClass;


class _plantillaTest extends test
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

    public function test_row_entidad(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $del = (new base_test())->del_fc_factura(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error('Error al eliminar',$del);
            print_r($error);
            exit;
        }
        $del = (new base_test())->del_adm_seccion(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error('Error al eliminar',$del);
            print_r($error);
            exit;
        }

        $alta = (new base_test())->alta_pr_etapa_proceso(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error('Error al insertar',$alta);
            print_r($error);
            exit;
        }

        $alta = (new base_test())->alta_fc_partida(link: $this->link, cat_sat_metodo_pago_codigo: 'PPD', cat_sat_metodo_pago_id: 2);
        if(errores::$error){
            $error = (new errores())->error('Error al insertar',$alta);
            print_r($error);
            exit;
        }

        $modelo_entidad = new fc_factura(link: $this->link);
        $modelo_partida = new fc_partida(link: $this->link);
        $modelo_retenido = new fc_retenido(link: $this->link);
        $modelo_traslado = new fc_traslado(link: $this->link);
        $row_entidad_id = 1;

        $plantilla = new _plantilla($modelo_entidad, $modelo_partida, $modelo_retenido, $modelo_traslado, $row_entidad_id);
        $plantilla = new liberator($plantilla);

        $resultado = $plantilla->row_entidad();

        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(999, $resultado->cat_sat_moneda_id);
        errores::$error = false;

    }



}

