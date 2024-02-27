<?php
/**
 * @author Martin Gamboa Vazquez
 * @version 1.0.0
 * @created 2022-05-14
 * @final En proceso
 *
 */
namespace gamboamartin\facturacion\controllers;

use base\controller\controler;
use config\generales;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\html\fc_key_csd_html;
use gamboamartin\facturacion\models\_cert;
use gamboamartin\facturacion\models\fc_key_csd;
use gamboamartin\facturacion\models\fc_key_pem;
use gamboamartin\plugins\ssl;
use gamboamartin\system\actions;
use gamboamartin\template\html;

use PDO;
use stdClass;

class controlador_fc_key_csd extends _base_system_csd {

    public array|stdClass $keys_selects = array();

    public function __construct(PDO $link, html $html = new \gamboamartin\template_1\html(),
                                stdClass $paths_conf = new stdClass()){
        $modelo = new fc_key_csd(link: $link);
        $html_ = new fc_key_csd_html(html: $html);
        parent::__construct(html_: $html_, link: $link,modelo:  $modelo, paths_conf: $paths_conf);

        $inputs = $this->init_inputs();
        if(errores::$error){
            $error = $this->errores->error(mensaje: 'Error al inicializar inputs',data:  $inputs);
            print_r($error);
            die('Error');
        }
    }

    public function genera_pem(bool $header, bool $ws = false): array|string|stdClass{

        $seccion_retorno = $this->tabla;
        if(isset($_POST['seccion_retorno'])){
            $seccion_retorno = $_POST['seccion_retorno'];
            unset($_POST['seccion_retorno']);
        }


        $data = (new fc_key_csd(link: $this->link))->genera_pem_full(fc_key_csd_id: $this->registro_id);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar pem', data: $data,header:  $header,ws:  $ws);
        }

        $out = $this->out_alta(header: $header,id_retorno:  -1,r_alta_bd:  $data,
            seccion_retorno:  $seccion_retorno,siguiente_view:  'lista',ws:  $ws);
        if(errores::$error){
            print_r($out);
            die('Error');
        }

        return $data;

    }
    private function init_configuraciones(): controler
    {
        $this->seccion_titulo = 'Keys CSD';
        $this->titulo_lista = 'Registro de Keys CSD';

        return $this;
    }

    public function init_datatable(): stdClass
    {
        $columns["fc_key_csd_id"]["titulo"] = "Id";
        $columns["fc_key_csd_codigo"]["titulo"] = "Código";
        $columns["fc_csd_descripcion"]["titulo"] = "CSD";
        $columns["fc_key_csd_descripcion"]["titulo"] = "Key CSD";
        $columns["doc_documento_descripcion"]["titulo"] = "Documento";

        $filtro = array("fc_key_csd.id","fc_key_csd.codigo","fc_key_csd.descripcion","fc_csd.descripcion",
            "doc_documento.descripcion");

        $datatables = new stdClass();
        $datatables->columns = $columns;
        $datatables->filtro = $filtro;

        return $datatables;
    }

}
