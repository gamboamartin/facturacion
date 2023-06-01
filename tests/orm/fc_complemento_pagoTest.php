<?php
namespace gamboamartin\facturacion\tests\orm;


use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\fc_complemento_pago;
use gamboamartin\facturacion\models\fc_csd;
use gamboamartin\facturacion\models\fc_cuenta_predial;
use gamboamartin\facturacion\models\fc_factura_relacionada;
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


class fc_complemento_pagoTest extends test {
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

    public function test_integra_fc_traslado_p_part(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';


        $modelo = new fc_complemento_pago($this->link);
        //$modelo = new liberator($modelo);

        $fc_traslado_p_part = array();
        $fc_traslado_p_part['fc_traslado_p_part_base_p'] = '-1.0';
        $fc_traslado_p_part['cat_sat_tipo_impuesto_codigo'] = '-1';
        $fc_traslado_p_part['cat_sat_tipo_factor_codigo'] = '-1';
        $fc_traslado_p_part['cat_sat_factor_factor'] = '-1';
        $fc_traslado_p_part['fc_traslado_p_part_importe_p'] = '-1';

        $resultado = $modelo->integra_fc_traslado_p_part($fc_traslado_p_part);

        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals('-1.0',$resultado->BaseP);
        $this->assertEquals('-1',$resultado->ImpuestoP);
        $this->assertEquals('-1',$resultado->TipoFactorP);
        $this->assertEquals('-1',$resultado->TasaOCuotaP);
        $this->assertEquals('-1',$resultado->ImporteP);



        errores::$error = false;
    }






}

