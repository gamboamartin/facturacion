<?php
/**
 * @author Martin Gamboa Vazquez
 * @version 1.0.0
 * @created 2022-05-14
 * @final En proceso
 *
 */
namespace gamboamartin\facturacion\controllers;

use gamboamartin\errores\errores;
use gamboamartin\facturacion\html\fc_conf_aut_producto_html;
use gamboamartin\facturacion\html\fc_conf_automatico_html;
use gamboamartin\facturacion\html\fc_ejecucion_automatica_html;
use gamboamartin\facturacion\models\com_producto;
use gamboamartin\facturacion\models\fc_conf_aut_producto;
use gamboamartin\facturacion\models\fc_conf_automatico;
use gamboamartin\facturacion\models\fc_ejecucion_automatica;
use gamboamartin\system\links_menu;
use gamboamartin\system\system;
use gamboamartin\template\html;
use html\com_producto_html;
use PDO;
use stdClass;

class controlador_fc_ejecucion_automatica extends system{

    public array|stdClass $keys_selects = array();



    public function __construct(PDO $link, html $html = new \gamboamartin\template_1\html(),
                                stdClass $paths_conf = new stdClass()){
        $modelo = new fc_ejecucion_automatica(link: $link);
        $html_ = new fc_ejecucion_automatica_html(html: $html);
        $obj_link = new links_menu(link: $link, registro_id:  $this->registro_id);


        $datatables = $this->init_datatable();
        if(errores::$error){
            $error = $this->errores->error(mensaje: 'Error al inicializar datatable',data: $datatables);
            print_r($error);
            die('Error');
        }

        parent::__construct(html:$html_, link: $link,modelo:  $modelo, obj_link: $obj_link, datatables: $datatables,
            paths_conf: $paths_conf);


        $this->lista_get_data = true;

        $this->parents_verifica[] = (new fc_conf_automatico(link: $this->link));


        $this->verifica_parents_alta = false;

    }


    final public function init_datatable(): stdClass
    {

        $columns["fc_ejecucion_automatica_id"]["titulo"] = "Fol";
        $columns["fc_conf_automatico_descripcion"]["titulo"] = "Configuracion";


        $filtro = array("fc_ejecucion_automatica.id",'fc_conf_automatico.descripcion');

        $datatables = new stdClass();
        $datatables->columns = $columns;
        $datatables->filtro = $filtro;

        return $datatables;
    }

    public function alta(bool $header, bool $ws = false): array|string
    {
        $r_alta =  parent::alta($header, $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al insertar',data:  $r_alta,header:  $header,ws:  $ws);
        }


        $fc_conf_automatico_id = (new fc_conf_automatico_html(html: $this->html_base))->select_fc_conf_automatico_id(cols:12,
            con_registros:  true,id_selected: -1,link: $this->link);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar input',data:  $fc_conf_automatico_id,header:  $header,ws:  $ws);
        }

        $descripcion = $this->html->input_descripcion(cols: 12, row_upd: new stdClass(), value_vacio: false);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar input',data:  $descripcion,header:  $header,ws:  $ws);
        }

        $this->inputs = new stdClass();
        $this->inputs->fc_conf_automatico_id = $fc_conf_automatico_id;
        $this->inputs->descripcion = $descripcion;
        return $r_alta;
    }

    public function modifica(bool $header, bool $ws = false): array|stdClass
    {
        $r_modifica = parent::modifica($header, $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar template',data:  $r_modifica,header:  $header,ws:  $ws);
        }

        $fc_conf_automatico_id = (new fc_conf_automatico_html(html: $this->html_base))->select_fc_conf_automatico_id(cols:12,
            con_registros:  true,id_selected: $this->row_upd->fc_conf_automatico_id,link: $this->link);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar input',data:  $fc_conf_automatico_id,header:  $header,ws:  $ws);
        }

        $descripcion = $this->html->input_descripcion(cols: 12, row_upd: $this->row_upd, value_vacio: false);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar input',data:  $descripcion,header:  $header,ws:  $ws);
        }


        $this->inputs = new stdClass();
        $this->inputs->fc_conf_automatico_id = $fc_conf_automatico_id;
        $this->inputs->descripcion = $descripcion;

        return $r_modifica;
    }


}
