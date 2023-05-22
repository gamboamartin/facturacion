<?php

namespace gamboamartin\facturacion\tests\orm;

use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\_comprobante;
use gamboamartin\facturacion\models\_email;
use gamboamartin\facturacion\models\_facturacion;
use gamboamartin\facturacion\models\_transacciones_fc;
use gamboamartin\facturacion\models\fc_complemento_pago;
use gamboamartin\facturacion\models\fc_complemento_pago_etapa;
use gamboamartin\facturacion\models\fc_factura;
use gamboamartin\facturacion\models\fc_factura_etapa;
use gamboamartin\facturacion\models\fc_nota_credito;
use gamboamartin\facturacion\models\fc_nota_credito_etapa;
use gamboamartin\facturacion\tests\base_test;
use gamboamartin\test\liberator;
use gamboamartin\test\test;


use stdClass;


class _transacciones_fcTest extends test
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

    public function test_default_alta_emisor_data(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $trs = new fc_nota_credito($this->link);
        $trs = new liberator($trs);

        $registro = array();
        $registro_csd = new stdClass();
        $registro_csd->dp_calle_pertenece_id = 1;
        $registro_csd->cat_sat_regimen_fiscal_id = 1;
        $resultado = $trs->default_alta_emisor_data($registro, $registro_csd);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        errores::$error = false;
    }

    public function test_etapas(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $trs = new fc_factura($this->link);
        $trs = new liberator($trs);

        $modelo_fc_etapa = new fc_factura_etapa(link: $this->link);
        $registro_id = '1';

        $resultado = $trs->etapas($modelo_fc_etapa, $registro_id);


        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        errores::$error = false;

        $trs = new fc_nota_credito($this->link);
        $trs = new liberator($trs);

        $modelo_fc_etapa = new fc_nota_credito_etapa(link: $this->link);

        $resultado = $trs->etapas($modelo_fc_etapa, $registro_id);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        errores::$error = false;

        $trs = new fc_complemento_pago($this->link);
        $trs = new liberator($trs);

        $modelo_fc_etapa = new fc_complemento_pago_etapa(link: $this->link);

        $resultado = $trs->etapas($modelo_fc_etapa, $registro_id);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);


        errores::$error = false;
    }

    public function test_permite_transaccion(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';


        $modelo = new fc_nota_credito(link: $this->link);
        $modelo = new liberator($modelo);

        $modelo_etapa = new fc_nota_credito_etapa(link: $this->link);

        $registro_id = 1;

        $resultado = $modelo->permite_transaccion($modelo_etapa, $registro_id);
        $this->assertIsBool($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertTrue($resultado);

        errores::$error = false;

    }

    public function test_valida_permite_transaccion(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';


        $modelo = new fc_complemento_pago(link: $this->link);
        $modelo = new liberator($modelo);
        $etapas = array();

        $resultado = $modelo->valida_permite_transaccion($etapas);
        $this->assertIsBool($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertTrue($resultado);

        errores::$error = false;

        $etapas[] = array('pr_etapa_descripcion'=>'CANCELADO');

        $resultado = $modelo->valida_permite_transaccion($etapas);
        $this->assertIsBool($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertNotTrue($resultado);

        errores::$error = false;

        $etapas = array();
        $etapas[] = array('pr_etapa_descripcion'=>'TIMBRADO');

        $resultado = $modelo->valida_permite_transaccion($etapas);
        $this->assertIsBool($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertNotTrue($resultado);

        errores::$error = false;
    }

    public function test_verifica_permite_transaccion(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'cat_sat_tipo_persona';
        $_GET['accion'] = 'lista';
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';


        $modelo = new fc_factura(link: $this->link);
        $modelo_etapa = new fc_factura_etapa(link: $this->link);
        //$modelo = new liberator($modelo);
        $registro_id = 1;

        $resultado = $modelo->verifica_permite_transaccion($modelo_etapa, $registro_id);
        $this->assertIsBool($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertTrue($resultado);

        errores::$error = false;

    }




}

