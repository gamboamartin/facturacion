<?php

namespace gamboamartin\facturacion\tests\orm;

use config\generales;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\_cert;
use gamboamartin\facturacion\models\_doc;
use gamboamartin\facturacion\models\_email;
use gamboamartin\facturacion\models\_facturacion;
use gamboamartin\facturacion\models\fc_complemento_pago;
use gamboamartin\facturacion\models\fc_complemento_pago_etapa;
use gamboamartin\facturacion\models\fc_factura;
use gamboamartin\facturacion\models\fc_factura_etapa;
use gamboamartin\facturacion\models\fc_nota_credito_documento;
use gamboamartin\facturacion\models\fc_partida;
use gamboamartin\facturacion\models\fc_partida_cp;
use gamboamartin\facturacion\models\fc_partida_nc;
use gamboamartin\facturacion\models\fc_relacion;
use gamboamartin\facturacion\models\fc_retenido;
use gamboamartin\facturacion\models\fc_retenido_cp;
use gamboamartin\facturacion\models\fc_traslado;
use gamboamartin\facturacion\tests\base_test;
use gamboamartin\plugins\files;
use gamboamartin\test\liberator;
use gamboamartin\test\test;


use stdClass;


class _certTest extends test
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

    public function test_ruta_out_base(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $cert = new _cert();
        $cert = new liberator($cert);

        $resultado = $cert->ruta_out_base();
        //print_r($resultado);exit;

        $this->assertIsNumeric($resultado);
        $this->assertNotTrue(errores::$error);

        errores::$error = false;
    }

    public function test_ruta_temporales(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $ruta = (new generales())->path_base.'archivos/temporales/';

        if(file_exists($ruta)) {
            $del = files::del_dir_full(dir: $ruta);
            if (errores::$error) {
                $error = (new errores())->error(mensaje: ' Error al eliminar archivo', data: $del);
                print_r($error);
                exit;
            }
        }

        $cert = new _cert();
        $cert = new liberator($cert);

        $resultado = $cert->ruta_temporales();

        $this->assertIsString($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals($ruta, $resultado);
        $this->assertFileExists($ruta);

        errores::$error = false;
    }


}

