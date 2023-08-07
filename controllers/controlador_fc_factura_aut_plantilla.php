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
use gamboamartin\facturacion\html\fc_ejecucion_aut_plantilla_html;
use gamboamartin\facturacion\html\fc_factura_aut_plantilla_html;
use gamboamartin\facturacion\html\fc_factura_html;
use gamboamartin\facturacion\models\fc_ejecucion_aut_plantilla;
use gamboamartin\facturacion\models\fc_factura;
use gamboamartin\facturacion\models\fc_factura_aut_plantilla;
use gamboamartin\system\links_menu;
use gamboamartin\system\system;
use gamboamartin\template\html;
use PDO;
use stdClass;

class controlador_fc_factura_aut_plantilla extends system{

    public array|stdClass $keys_selects = array();



    public function __construct(PDO $link, html $html = new \gamboamartin\template_1\html(),
                                stdClass $paths_conf = new stdClass()){
        $modelo = new fc_factura_aut_plantilla(link: $link);
        $html_ = new fc_factura_aut_plantilla_html(html: $html);
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

        $this->parents_verifica[] = (new fc_factura(link: $this->link));
        $this->parents_verifica[] = (new fc_ejecucion_aut_plantilla(link: $this->link));

        $this->verifica_parents_alta = false;

    }


    final public function init_datatable(): stdClass
    {

        $columns["fc_ejecucion_aut_plantilla_id"]["titulo"] = "Id";
        $columns["fc_factura_folio"]["titulo"] = "Folio";
        $columns["fc_ejecucion_aut_plantilla_fecha_alta"]["titulo"] = "Fecha alta";
        $columns["com_tipo_cliente_descripcion"]["titulo"] = "Tipo de Cliente";

        $filtro = array("fc_ejecucion_aut_plantilla.id","fc_factura.folio",'fc_ejecucion_aut_plantilla.fecha_alta',
            'com_tipo_cliente.descripcion');

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
        $fc_factura_id = (new fc_factura_html(html: $this->html_base))->select_fc_factura_id(cols:12,
            con_registros:  true,id_selected: -1,link: $this->link);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar input',data:  $fc_factura_id,header:  $header,ws:  $ws);
        }

        $fc_ejecucion_aut_plantilla_id = (new fc_ejecucion_aut_plantilla_html(html: $this->html_base))->select_fc_ejecucion_aut_plantilla_id(cols:12,
            con_registros:  true,id_selected: -1,link: $this->link);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar input',data:  $fc_ejecucion_aut_plantilla_id,header:  $header,ws:  $ws);
        }


        $this->inputs = new stdClass();
        $this->inputs->fc_factura_id = $fc_factura_id;
        $this->inputs->fc_ejecucion_aut_plantilla_id = $fc_ejecucion_aut_plantilla_id;
        return $r_alta;
    }

    public function modifica(bool $header, bool $ws = false): array|stdClass
    {
        $r_modifica = parent::modifica($header, $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar template',data:  $r_modifica,header:  $header,ws:  $ws);
        }
        $fc_factura_id = (new fc_factura_html(html: $this->html_base))->select_fc_factura_id(cols:12,
            con_registros:  true,id_selected: $this->row_upd->fc_factura_id,link: $this->link);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar input',data:  $fc_factura_id,header:  $header,ws:  $ws);
        }

        $fc_ejecucion_aut_plantilla_id = (new fc_ejecucion_aut_plantilla_html(html: $this->html_base))->select_fc_ejecucion_aut_plantilla_id(cols:12,
            con_registros:  true,id_selected: $this->row_upd->fc_ejecucion_aut_plantilla_id,link: $this->link);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar input',data:  $fc_ejecucion_aut_plantilla_id,header:  $header,ws:  $ws);
        }



        $this->inputs = new stdClass();
        $this->inputs->fc_factura_id = $fc_factura_id;
        $this->inputs->fc_ejecucion_aut_plantilla_id = $fc_ejecucion_aut_plantilla_id;

        return $r_modifica;
    }


}
