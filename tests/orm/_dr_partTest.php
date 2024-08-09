<?php

namespace gamboamartin\facturacion\tests\orm;

use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\_email;
use gamboamartin\facturacion\models\_facturacion;
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
use gamboamartin\facturacion\models\fc_traslado_dr_part;
use gamboamartin\facturacion\tests\base_test;
use gamboamartin\js_base\eventos\adm_seccion;
use gamboamartin\test\liberator;
use gamboamartin\test\test;


use stdClass;


class _dr_partTest extends test
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

    public function test_codigo(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_retencion_dr_part(link: $this->link);
        $modelo = new liberator($modelo);

        $registro = array();
        $registro['a_id'] = 1;
        $entidad_dr = 'a';
        $resultado = $modelo->codigo($entidad_dr, $registro);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals('1', $resultado['a_id']);
        errores::$error = false;
    }

    public function test_filtro_impuestos_dr_part(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_retencion_dr_part(link: $this->link);
        $modelo = new liberator($modelo);

        $fc_pago_id = 1;
        $cat_sat_factor_id = 1;
        $resultado = $modelo->filtro_impuestos_dr_part($cat_sat_factor_id, $fc_pago_id);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals('1', $resultado['cat_sat_factor.id']);
        $this->assertEquals('1', $resultado['fc_pago.id']);
        errores::$error = false;

    }

    /**
     * prueba critica
     * @return void
     */
    public function test_importe_mxn_otra_moneda(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_traslado_dr_part(link: $this->link);
        $modelo = new liberator($modelo);

        $dr_part = array();
        $dr_part['fc_traslado_dr_part_base_dr'] = 1;
        $dr_part['fc_traslado_dr_part_importe_dr'] = 1;
        $dr_part['com_tipo_cambio_pago_monto'] = 20;
        $importes = new stdClass();
        $importes->base_dr = 100;
        $importes->importe_dr = 10;

        $resultado = $modelo->importe_mxn_otra_moneda($dr_part, $importes);
        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(5.0, $resultado->base_dr);
        $this->assertEquals(.5, $resultado->importe_dr);
        errores::$error = false;
    }

    /**
     * PRUEBA CRITICA RESPETAR VALORES
     * @return void
     */
    public function test_importes_p_init(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_traslado_dr_part(link: $this->link);
        $modelo = new liberator($modelo);

        $dr_part = array();
        $dr_part['fc_traslado_dr_part_base_dr'] = 1;
        $dr_part['fc_traslado_dr_part_importe_dr'] = 1;
        $resultado = $modelo->importes_p_init(dr_part: $dr_part);
        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(1.0, $resultado->base_dr);
        $this->assertEquals(1.0, $resultado->importe_dr);
        errores::$error = false;
    }

    public function test_importe_otra_moneda_mxn(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_traslado_dr_part(link: $this->link);
        $modelo = new liberator($modelo);

        $dr_part = array();
        $dr_part['fc_traslado_dr_part_base_dr'] = 1;
        $dr_part['fc_traslado_dr_part_importe_dr'] = 1;
        $dr_part['com_tipo_cambio_factura_monto'] = 20;
        $importes = new stdClass();
        $importes->base_dr = 100;
        $importes->importe_dr = 10;

        $resultado = $modelo->importe_otra_moneda_mxn($dr_part, $importes);
        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(2000.0, $resultado->base_dr);
        $this->assertEquals(200.0, $resultado->importe_dr);
        errores::$error = false;
    }

    /**
     * CRITICA
     * @return void
     */
    public function test_importes_monedas_diferentes(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_traslado_dr_part(link: $this->link);
        $modelo = new liberator($modelo);

        $dr_part = array();
        $dr_part['fc_traslado_dr_part_base_dr'] = 1;
        $dr_part['fc_traslado_dr_part_importe_dr'] = 1;
        $dr_part['com_tipo_cambio_factura_monto'] = 20;
        $dr_part['com_tipo_cambio_pago_monto'] = 20;
        $dr_part['com_tipo_cambio_factura_cat_sat_moneda_id'] = 1;
        $dr_part['com_tipo_cambio_pago_cat_sat_moneda_id'] = 1;
        $importes = new stdClass();
        $importes->base_dr = 100;
        $importes->importe_dr = 10;

        $resultado = $modelo->importes_monedas_diferentes($dr_part, $importes);
        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(100.0, $resultado->base_dr);
        $this->assertEquals(10.0, $resultado->importe_dr);
        errores::$error = false;

        $dr_part = array();
        $dr_part['fc_traslado_dr_part_base_dr'] = 1;
        $dr_part['fc_traslado_dr_part_importe_dr'] = 1;
        $dr_part['com_tipo_cambio_factura_monto'] = 1;
        $dr_part['com_tipo_cambio_pago_monto'] = 20;
        $dr_part['com_tipo_cambio_factura_cat_sat_moneda_id'] = 161;
        $dr_part['com_tipo_cambio_pago_cat_sat_moneda_id'] = 2;
        $importes = new stdClass();
        $importes->base_dr = 100;
        $importes->importe_dr = 10;

        $resultado = $modelo->importes_monedas_diferentes($dr_part, $importes);
        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(5.0, $resultado->base_dr);
        $this->assertEquals(.5, $resultado->importe_dr);
        errores::$error = false;



        $dr_part = array();
        $dr_part['fc_traslado_dr_part_base_dr'] = 1;
        $dr_part['fc_traslado_dr_part_importe_dr'] = 1;
        $dr_part['com_tipo_cambio_factura_monto'] = 20;
        $dr_part['com_tipo_cambio_pago_monto'] = 1;
        $dr_part['com_tipo_cambio_factura_cat_sat_moneda_id'] = 1;
        $dr_part['com_tipo_cambio_pago_cat_sat_moneda_id'] = 161;
        $importes = new stdClass();
        $importes->base_dr = 100;
        $importes->importe_dr = 10;

        $resultado = $modelo->importes_monedas_diferentes($dr_part, $importes);
        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals(2000.0, $resultado->base_dr);
        $this->assertEquals(200.0, $resultado->importe_dr);
        errores::$error = false;


    }



    public function test_tipo_impuestos_validos(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_traslado_dr_part(link: $this->link);
        $modelo = new liberator($modelo);


        $resultado = $modelo->tipo_impuestos_validos();
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals('retenciones', $resultado[0]);
        $this->assertEquals('traslados', $resultado[1]);
        errores::$error = false;
    }


}

