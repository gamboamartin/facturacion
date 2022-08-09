<?php
namespace tests\links\secciones;

use gamboamartin\errores\errores;
use gamboamartin\organigrama\controllers\controlador_org_empresa;
use gamboamartin\test\liberator;
use gamboamartin\test\test;
use JsonException;
use links\secciones\link_org_empresa;
use models\org_empresa;
use models\org_sucursal;
use stdClass;


class controlador_org_empresaTest extends test {
    public errores $errores;
    private stdClass $paths_conf;
    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->errores = new errores();
        $this->paths_conf = new stdClass();
        $this->paths_conf->generales = '/var/www/html/cat_sat/config/generales.php';
        $this->paths_conf->database = '/var/www/html/cat_sat/config/database.php';
        $this->paths_conf->views = '/var/www/html/cat_sat/config/views.php';


    }

    /**
     * @throws JsonException
     */
    public function test_ubicacion(): void
    {
        errores::$error = false;

        $_GET['seccion'] = 'org_empresa';
        $_GET['accion'] = 'ubicacion';

        $_SESSION['grupo_id'] = 1;
        $_GET['session_id'] = '1';
        $_SESSION['usuario_id'] = '2';
        $ctl = new controlador_org_empresa(link: $this->link, paths_conf: $this->paths_conf);

        $registro= array();
        $registro['razon_social'] = 'a';
        $registro['rfc'] = 'a';
        $registro['descripcion'] = 'a';
        $registro['codigo'] = 'a';
        $registro['nombre_comercial'] = 'a';
        $registro['fecha_inicio_operaciones'] = 'a';
        $registro['fecha_ultimo_cambio_sat'] = 'a';
        $registro['email_sat'] = 'a';

        $r_elimina_sucursales = (new org_sucursal($this->link))->elimina_todo();
        if(errores::$error){
            $error = (new errores())->error('Error al eliminar empresa', $r_elimina_sucursales);
            print_r($error);
            exit;
        }

        $r_elimina_empresas = (new org_empresa($this->link))->elimina_todo();
        if(errores::$error){
            $error = (new errores())->error('Error al eliminar empresa', $r_elimina_empresas);
            print_r($error);
            exit;
        }

        $r_alta_org_empresa = (new org_empresa($this->link))->alta_registro($registro);
        if(errores::$error){
            $error = (new errores())->error('Error al insertar empresa', $r_alta_org_empresa);
            print_r($error);
            exit;
        }


        $_GET['registro_id'] = $r_alta_org_empresa->registro_id;
        $ctl->registro_id = $r_alta_org_empresa->registro_id;

        $resultado = $ctl->ubicacion(false);

        $this->assertIsObject($resultado);
        $this->assertNotTrue(errores::$error);

        errores::$error = false;
    }









}

