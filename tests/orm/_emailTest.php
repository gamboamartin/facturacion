<?php

namespace gamboamartin\facturacion\tests\orm;

use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\_email;
use gamboamartin\facturacion\models\_facturacion;
use gamboamartin\test\liberator;
use gamboamartin\test\test;


use stdClass;


class _emailTest extends test
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

    public function test_asunto(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $email = new _email();
        $email = new liberator($email);

        $row_entidad = new stdClass();
        $row_entidad->org_empresa_razon_social = 'ALFA';
        $row_entidad->org_empresa_rfc = 'BETA';
        $uuid = 'a';
        $resultado = $email->asunto($row_entidad, $uuid);
        $this->assertIsString($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals('CFDI de ALFA RFC: BETA Folio: a', $resultado);
        errores::$error = false;
    }

    public function test_fc_partida_importe_con_descuento(): string
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new _facturacion();
        $modelo = new liberator($modelo);

        $salida = "ROUND((ROUND((ROUND(IFNULL(fc_partida.cantidad,0),2) * ";
        $salida .= "ROUND(IFNULL(fc_partida.valor_unitario,0),2)),2) - ";
        $salida .= "ROUND(IFNULL(fc_partida.descuento,0),2)),2)";
        $resultado = $modelo->fc_partida_importe_con_descuento('fc_partida');
        $this->assertIsString($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals($salida, $resultado);
        errores::$error = false;

        return $resultado;
    }

    public function test_importes_base(): array
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new _facturacion();

        $fc_partida_importe = "ROUND((ROUND((ROUND(IFNULL(fc_partida.cantidad,0),2) * ROUND(IFNULL(fc_partida.valor_unitario,0),2)),2) - ROUND(IFNULL(fc_partida.descuento,0),2)),2)";
        //$fc_partida_importe .= "ROUND(IFNULL(fc_partida.valor_unitario,0),2)),2)";

        $fc_partida_importe_con_descuento = "ROUND((ROUND((ROUND(IFNULL(fc_partida.cantidad,0),2) * ROUND(IFNULL(fc_partida.valor_unitario,0),2)),2) - ROUND(IFNULL(fc_partida.descuento,0),2)),2)";
        //$fc_partida_importe_con_descuento .= "ROUND(IFNULL(fc_partida.descuento,0),2)),2)";

        $resultado = (array)$modelo->importes_base('fc_partida');
        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals($fc_partida_importe, $resultado['fc_partida_entidad_importe']);
        $this->assertEquals($fc_partida_importe_con_descuento, $resultado['fc_partida_entidad_importe_con_descuento']);
        errores::$error = false;

        return $resultado;
    }

    public function test_fc_impuesto_importe(): string
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new _facturacion();

        $importes_base = $this->test_importes_base();
        if (errores::$error) {
            $error = (new errores())->error('Error al ejecutar test_importes_base', $importes_base);
            print_r($error);
            exit;
        }

        $salida = "ROUND(" . $importes_base['fc_partida_entidad_importe_con_descuento'];
        $salida .= " * ROUND(IFNULL(cat_sat_factor.factor,0),4),2)";
        $resultado = $modelo->fc_impuesto_importe(
            fc_partida_importe_con_descuento: $importes_base['fc_partida_entidad_importe_con_descuento']);
        $this->assertIsString($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals($salida, $resultado);
        errores::$error = false;

        return  $resultado;
    }

    public function test_impuesto_partida(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new _facturacion();

        $importe_con_descuento = $this->test_fc_partida_importe_con_descuento();
        if (errores::$error) {
            $error = (new errores())->error('Error al ejecutar test_fc_partida_importe_con_descuento',
                $importe_con_descuento);
            print_r($error);
            exit;
        }

        $impuesto_importe = $this->test_fc_impuesto_importe();
        if (errores::$error) {
            $error = (new errores())->error('Error al ejecutar test_fc_impuesto_importe', $impuesto_importe);
            print_r($error);
            exit;
        }

        $tabla_impuesto = 'fc_traslado';

        $inner_join_cat_sat_factor = "INNER JOIN cat_sat_factor ON cat_sat_factor.id = $tabla_impuesto.cat_sat_factor_id";
        $where = "WHERE $tabla_impuesto.fc_partida_id = fc_partida.id";
        $salida = "(SELECT ROUND(SUM($impuesto_importe),2) FROM $tabla_impuesto $inner_join_cat_sat_factor $where)";
        $resultado = $modelo->impuesto_partida(name_entidad_partida: 'fc_partida', tabla_impuesto: $tabla_impuesto);
        $this->assertIsString($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals($salida, $resultado);
        errores::$error = false;
    }

}

