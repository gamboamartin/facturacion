<?php

namespace gamboamartin\facturacion\tests\orm;

use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\_email;
use gamboamartin\facturacion\models\_facturacion;
use gamboamartin\facturacion\models\fc_complemento_pago_relacionada;
use gamboamartin\facturacion\models\fc_factura;
use gamboamartin\facturacion\models\fc_factura_relacionada;
use gamboamartin\facturacion\models\fc_nota_credito;
use gamboamartin\facturacion\models\fc_nota_credito_relacionada;
use gamboamartin\facturacion\models\fc_relacion;
use gamboamartin\facturacion\models\fc_relacion_cp;
use gamboamartin\facturacion\models\fc_relacion_nc;
use gamboamartin\facturacion\models\fc_uuid;
use gamboamartin\facturacion\models\fc_uuid_fc;
use gamboamartin\facturacion\models\fc_uuid_nc;
use gamboamartin\test\liberator;
use gamboamartin\test\test;


use stdClass;


class _relacionTest extends test
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

    public function test_ajusta_relacionados(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_relacion_nc(link: $this->link);
        $modelo = new liberator($modelo);

        $cat_sat_tipo_relacion_codigo = '';
        $modelo_uuid_ext = new fc_uuid_nc(link: $this->link);
        $relacionados = array();
        $row_relacion = array();
        $row_relacion['fc_relacion_nc_id'] = 1;

        $resultado = $modelo->ajusta_relacionados($cat_sat_tipo_relacion_codigo, $modelo_uuid_ext, $relacionados, $row_relacion);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        errores::$error = false;

    }

    public function test_facturas_relacionadas(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_relacion_cp(link: $this->link);
        //$modelo = new liberator($modelo);

        $modelo_relacionada = new fc_complemento_pago_relacionada(link: $this->link);
        $row_relacion = array();
        $row_relacion['fc_relacion_cp_id'] = 1;
        $resultado = $modelo->facturas_relacionadas($modelo_relacionada,$row_relacion);


        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        errores::$error = false;

    }

    public function test_genera_relacionado(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_relacion_nc(link: $this->link);
        $modelo = new liberator($modelo);

        $modelo_relacionada = new fc_nota_credito_relacionada(link: $this->link);
        $modelo_uuid_ext = new fc_uuid_nc($this->link);
        $name_entidad = 'fc_nota_credito';
        $relacionados = array();
        $row_relacion = array();
        $row_relacion['fc_relacion_nc_id'] = 1;
        $row_relacion['cat_sat_tipo_relacion_codigo'] = 1;
        $resultado = $modelo->genera_relacionado($modelo_relacionada,$modelo_uuid_ext,$name_entidad,$relacionados,$row_relacion);


        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        errores::$error = false;

    }

    public function test_get_relaciones(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_relacion(link: $this->link);
        //$modelo = new liberator($modelo);

        $modelo_relacionada = new fc_factura_relacionada(link: $this->link);
        $modelo_uuid_ext = new fc_uuid_fc($this->link);
        $modelo_entidad = new fc_factura($this->link);
        $registro_entidad_id = 1;

        $resultado = $modelo->get_relaciones($modelo_entidad,$modelo_relacionada,$modelo_uuid_ext,$registro_entidad_id);


        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        errores::$error = false;

    }

    public function test_integra_relacion_nc(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_relacion(link: $this->link);
        $modelo = new liberator($modelo);

        $cat_sat_tipo_relacion_codigo = 'a';
        $relacionados = array();
        $filtro = array();
        $resultado = $modelo->integra_relacion_nc($cat_sat_tipo_relacion_codigo, $filtro, $relacionados);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        errores::$error = false;
    }

    public function test_integra_relacionado(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_relacion(link: $this->link);
        $modelo = new liberator($modelo);

        $cat_sat_tipo_relacion_codigo = 'a';
        $name_entidad = 'b';
        $relacionados = array();
        $row_relacionada['b_uuid'] = 'a';
        $resultado = $modelo->integra_relacionado($cat_sat_tipo_relacion_codigo, $name_entidad, $relacionados, $row_relacionada);

        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals('a', $resultado['a'][0]);
        errores::$error = false;
    }

    public function test_integra_relacionados(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_relacion(link: $this->link);
        $modelo = new liberator($modelo);

        $cat_sat_tipo_relacion_codigo = 'a';
        $name_entidad = 'v';
        $relacionados = array();
        $fc_rows_relacionadas[]['v_uuid'] = 'z';
        $resultado = $modelo->integra_relacionados($cat_sat_tipo_relacion_codigo, $fc_rows_relacionadas, $name_entidad, $relacionados);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals('z', $resultado['a'][0]);
        errores::$error = false;

    }

    public function test_relaciones(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_relacion_nc(link: $this->link);
        //$modelo = new liberator($modelo);

        $modelo_entidad = new fc_nota_credito(link: $this->link);
        $registro_entidad_id = 1;
        $resultado = $modelo->relaciones($modelo_entidad,$registro_entidad_id);

        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        errores::$error = false;

    }

    public function test_relaciones_externas(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_relacion_nc(link: $this->link);
        $modelo = new liberator($modelo);

        $modelo_uuid_ext = new fc_uuid_nc(link: $this->link);
        $row_relacion = array();
        $row_relacion['fc_relacion_nc_id'] = 1;

        $resultado = $modelo->relaciones_externas($modelo_uuid_ext, $row_relacion);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);

        errores::$error = false;

    }

    public function test_relacionados(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_relacion(link: $this->link);
        $modelo = new liberator($modelo);

        $modelo_relacionada = new fc_factura_relacionada($this->link);
        $modelo_uuid_ext = new fc_uuid_fc(link: $this->link);
        $row_relaciones = array();
        $name_entidad = 'fc_factura';

        $row_relaciones[0]['fc_relacion_id'] = 1;
        $row_relaciones[0]['cat_sat_tipo_relacion_codigo'] = 1;


        $resultado = $modelo->relacionados($modelo_relacionada,$modelo_uuid_ext,$name_entidad, $row_relaciones);

        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        errores::$error = false;

    }

    public function test_valida_base(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_relacion_cp(link: $this->link);
        $modelo = new liberator($modelo);

        $name_entidad = 'a';
        $row_relacion = array();
        $row_relacion['fc_relacion_cp_id'] = 1;
        $row_relacion['cat_sat_tipo_relacion_codigo'] = 1;
        $resultado = $modelo->valida_base($name_entidad,$row_relacion );

        $this->assertIsBool($resultado);
        $this->assertNotTrue(errores::$error);
        errores::$error = false;

    }

    public function test_valida_data_rel(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_relacion(link: $this->link);
        $modelo = new liberator($modelo);

        $row_relacion = array();
        $row_relacion['fc_relacion_id'] = 1;
        $resultado = $modelo->valida_data_rel($row_relacion);

        $this->assertIsBool($resultado);
        $this->assertNotTrue(errores::$error);
        errores::$error = false;

    }



}

