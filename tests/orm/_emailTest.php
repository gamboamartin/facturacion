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

    public function __construct(?string $name = null)
    {
        parent::__construct($name);
        $this->errores = new errores();
        $this->paths_conf = new stdClass();
        $this->paths_conf->generales = '/var/www/html/facturacion/config/generales.php';
        $this->paths_conf->database = '/var/www/html/facturacion/config/database.php';
        $this->paths_conf->views = '/var/www/html/facturacion/config/views.php';
    }

    public function test_asunto(): void
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 2;
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


    public function test_maqueta_documentos(): array
    {
        errores::$error = false;
        $_SESSION['grupo_id'] = 1;
        $_SESSION['usuario_id'] = 2;
        $_GET['session_id'] = '1';

        $modelo = new _email();
        $modelo = new liberator($modelo);

        $_fc_documentos = array();
        $_fc_documentos[0]['doc_tipo_documento_descripcion'] = 'A';
        $_fc_documentos[1]['doc_tipo_documento_descripcion'] = 'xml_sin_timbrar';
        $_fc_documentos[2]['doc_tipo_documento_descripcion'] = 'CFDI PDF';
        $_fc_documentos[3]['doc_tipo_documento_descripcion'] = 'ADJUNTO';
        $resultado = $modelo->maqueta_documentos($_fc_documentos);

        $this->assertIsArray($resultado);
        $this->assertNotTrue(errores::$error);
        $this->assertEquals('xml_sin_timbrar', $resultado[0]['doc_tipo_documento_descripcion']);
        $this->assertEquals('CFDI PDF', $resultado[1]['doc_tipo_documento_descripcion']);
        $this->assertEquals('ADJUNTO', $resultado[2]['doc_tipo_documento_descripcion']);

        errores::$error = false;

        return $resultado;
    }

}

