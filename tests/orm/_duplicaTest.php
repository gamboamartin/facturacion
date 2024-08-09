<?php

namespace gamboamartin\facturacion\tests\orm;

use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\_duplica;
use gamboamartin\facturacion\models\fc_retenido;
use gamboamartin\facturacion\models\fc_traslado;
use gamboamartin\facturacion\tests\base_test;

use gamboamartin\test\liberator;
use gamboamartin\test\test;
use stdClass;

class _duplicaTest extends test
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

    public function test_row_entidad_ins(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';




        $_duplica = new _duplica();
        $_duplica = new liberator($_duplica);

        $row_entidad = new stdClass();
        $row_entidad->fc_csd_id = 1;
        $row_entidad->cat_sat_forma_pago_id = 1;
        $row_entidad->cat_sat_metodo_pago_id = 1;
        $row_entidad->cat_sat_moneda_id = 1;
        $row_entidad->com_tipo_cambio_id = 1;
        $row_entidad->cat_sat_uso_cfdi_id = 1;
        $row_entidad->dp_calle_pertenece_id = 1;
        $row_entidad->cat_sat_tipo_de_comprobante_id = 1;
        $row_entidad->exportacion = 1;
        $row_entidad->cat_sat_regimen_fiscal_id = 1;
        $row_entidad->com_sucursal_id = 1;
        $resultado = $_duplica->row_entidad_ins($row_entidad);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);


        errores::$error = false;

    }

}

