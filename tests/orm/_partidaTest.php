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
use gamboamartin\facturacion\models\fc_retenido;
use gamboamartin\facturacion\models\fc_retenido_cp;
use gamboamartin\facturacion\models\fc_traslado;
use gamboamartin\facturacion\tests\base_test;
use gamboamartin\test\liberator;
use gamboamartin\test\test;


use stdClass;


class _partidaTest extends test
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

    public function test_hijo(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_partida_nc(link: $this->link);
        $modelo = new liberator($modelo);

        $hijo = array();
        $name_modelo_impuesto = 'a';
        $resultado = $modelo->hijo($hijo, $name_modelo_impuesto);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals('fc_partida_nc_id', $resultado['a']['filtros']['fc_partida_nc.id']);
        errores::$error = false;
    }

    public function test_hijo_retenido(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_partida_cp(link: $this->link);
        $modelo = new liberator($modelo);

        $hijo = array();
        $modelo_retencion = new fc_retenido_cp(link: $this->link);
        $resultado = $modelo->hijo_retenido($hijo, $modelo_retencion);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals('fc_partida_cp_id', $resultado['fc_retenido_cp']['filtros']['fc_partida_cp.id']);
        errores::$error = false;

    }

    public function test_hijos_partida(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_partida(link: $this->link);
        $modelo = new liberator($modelo);


        $modelo_retencion = new fc_retenido(link: $this->link);
        $modelo_traslado = new fc_traslado(link: $this->link);
        $resultado = $modelo->hijos_partida($modelo_retencion, $modelo_traslado);
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals('fc_partida_id', $resultado['fc_retenido']['filtros']['fc_partida.id']);
        $this->assertEquals('fc_partida_id', $resultado['fc_traslado']['filtros']['fc_partida.id']);
        errores::$error = false;

    }

    public function test_integra_relacionado(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_partida(link: $this->link);
        $modelo = new liberator($modelo);

        $modelo_impuesto = new fc_traslado(link: $this->link);

        $resultado = $modelo->tabla_impuesto($modelo_impuesto);

        $this->assertIsString($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals('fc_traslado', $resultado);
        errores::$error = false;
    }

    public function test_valida_restriccion(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_partida_cp(link: $this->link);
        $modelo = new liberator($modelo);

        $modelo_entidad = new fc_complemento_pago(link: $this->link);
        $modelo_etapa = new fc_complemento_pago_etapa(link: $this->link);
        $row_partida = new stdClass();
        $row_partida->fc_complemento_pago_id = '1';

        $resultado = $modelo->valida_restriccion($modelo_entidad, $modelo_etapa, $row_partida);
        $this->assertIsBool($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertTrue($resultado);
        errores::$error = false;

    }

    public function test_valida_restriccion_partida(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new fc_partida_cp(link: $this->link);
        $modelo = new liberator($modelo);


        $del = (new base_test())->del_com_producto(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al eliminar',data:  $del);
            print_r($error);exit;
        }


        $inserta = (new base_test())->alta_com_producto(link: $this->link, codigo: '84111506', id: '84111506');
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al insertar',data:  $inserta);
            print_r($error);exit;
        }


        $inserta = (new base_test())->alta_fc_partida_cp(link: $this->link);
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al insertar',data:  $inserta);
            print_r($error);exit;
        }

        $modelo_entidad = new fc_complemento_pago(link: $this->link);
        $modelo_etapa = new fc_complemento_pago_etapa(link: $this->link);
        $id = 1;
        $resultado = $modelo->valida_restriccion_partida($id, $modelo_entidad, $modelo_etapa);
        $this->assertIsBool($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertTrue($resultado);
        errores::$error = false;
    }





}

