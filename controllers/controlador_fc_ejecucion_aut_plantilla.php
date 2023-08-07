<?php
/**
 * @author Martin Gamboa Vazquez
 * @version 1.0.0
 * @created 2022-05-14
 * @final En proceso
 *
 */
namespace gamboamartin\facturacion\controllers;

use gamboamartin\comercial\models\com_tipo_cliente;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\html\fc_conf_automatico_html;
use gamboamartin\facturacion\html\fc_ejecucion_aut_plantilla_html;
use gamboamartin\facturacion\models\fc_ejecucion_aut_plantilla;
use gamboamartin\facturacion\models\fc_factura_aut_plantilla;
use gamboamartin\system\links_menu;
use gamboamartin\system\out_permisos;
use gamboamartin\system\system;
use gamboamartin\template\html;
use html\com_tipo_cliente_html;
use PDO;
use stdClass;

class controlador_fc_ejecucion_aut_plantilla extends system{

    public array|stdClass $keys_selects = array();
    public string $link_timbra = '';



    public function __construct(PDO $link, html $html = new \gamboamartin\template_1\html(),
                                stdClass $paths_conf = new stdClass()){
        $modelo = new fc_ejecucion_aut_plantilla(link: $link);
        $html_ = new fc_ejecucion_aut_plantilla_html(html: $html);
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

        $this->parents_verifica[] = (new com_tipo_cliente(link: $this->link));


        $this->verifica_parents_alta = false;

    }

    public function facturas(bool $header, bool $ws = false): array|stdClass
    {
        $fc_ejecucion_automatica = $this->modelo->registro(registro_id: $this->registro_id,retorno_obj: true);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener fc_ejecucion_automatica',
                data:  $fc_ejecucion_automatica,header:  $header,ws:  $ws);
        }
        $filtro['fc_factura_aut_plantilla.id'] = $this->registro_id;
        $r_fc_factura_aut_plantillas = (new fc_factura_aut_plantilla(link: $this->link))->filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener facturas',
                data:  $r_fc_factura_aut_plantillas,header:  $header,ws:  $ws);
        }
        $fc_factura_aut_plantillas = $r_fc_factura_aut_plantillas->registros;

        $controlador_fc_factura = new controlador_fc_factura(link: $this->link);
        $controlador_fc_factura->seccion = 'fc_factura';

        foreach ($fc_factura_aut_plantillas as $indice=>$fc_factura){
           // print_r($fc_factura);exit;
            $controlador_fc_factura->registro_id = $fc_factura['fc_factura_id'];
            $controlador_fc_factura->registro = $fc_factura;

            $buttons = (new out_permisos())->buttons_view(controler:$controlador_fc_factura,
                not_actions: array(), params: array());
            if(errores::$error){
                return $this->retorno_error(mensaje: 'Error al obtener botones',
                    data: $buttons, header: $header, ws: $ws);
            }
            $buttons_html = implode('', $buttons);
            $fc_factura_aut_plantillas[$indice]['fc_factura_acciones'] = $buttons_html;

            $input_chk = "<input type='checkbox' value='$fc_factura[fc_factura_id]' name='fc_facturas_id[]' class='fc_factura_chk'>";
            $fc_factura_aut_plantillas[$indice]['fc_factura_selecciona'] = $input_chk;
        }

        $this->registros = $fc_factura_aut_plantillas;
        $clases_css[] = 'btn_timbra';
        $button_timbra = $this->html->directivas->btn(ids_css: $ids_css = array(), clases_css: $clases_css, extra_params: array(),
            label: 'Timbra', name: 'btn_timbra', value: 'Timbra', cols: 2, style: 'success',type: 'submit' );
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener boton', data: $button_timbra, header: $header, ws: $ws);
        }
        $this->buttons['button_timbra'] = $button_timbra;

        $link_timbra = $this->obj_link->link_con_id(accion: 'timbra',link:  $this->link,registro_id: $this->registro_id,seccion: $this->seccion);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener boton link_timbra', data: $link_timbra, header: $header, ws: $ws);
        }

        $this->link_timbra = $link_timbra;

        return $r_fc_factura_aut_plantillas;
    }


    final public function init_datatable(): stdClass
    {

        $columns["fc_ejecucion_aut_plantilla_id"]["titulo"] = "Fol";
        $columns["com_tipo_cliente_descripcion"]["titulo"] = "Tipo de cliente";
        $columns["fc_ejecucion_aut_plantilla_descripcion"]["titulo"] = "Descripcion";
        $columns["fc_ejecucion_aut_plantilla_fecha_alta"]["titulo"] = "Fecha";


        $filtro = array("fc_ejecucion_aut_plantilla.id",'com_tipo_cliente.descripcion',
            'fc_ejecucion_aut_plantilla.descripcion','fc_ejecucion_aut_plantilla.fecha_alta');

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


        $com_tipo_cliente_id = (new com_tipo_cliente_html(html: $this->html_base))->select_com_tipo_cliente_id(cols:12,
            con_registros:  true,id_selected: -1,link: $this->link);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar input',data:  $com_tipo_cliente_id,header:  $header,ws:  $ws);
        }

        $descripcion = $this->html->input_descripcion(cols: 12, row_upd: new stdClass(), value_vacio: false);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar input',data:  $descripcion,header:  $header,ws:  $ws);
        }

        $this->inputs = new stdClass();
        $this->inputs->com_tipo_cliente_id = $com_tipo_cliente_id;
        $this->inputs->descripcion = $descripcion;
        return $r_alta;
    }

    public function modifica(bool $header, bool $ws = false): array|stdClass
    {
        $r_modifica = parent::modifica($header, $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar template',data:  $r_modifica,header:  $header,ws:  $ws);
        }

        $com_tipo_cliente_id = (new fc_conf_automatico_html(html: $this->html_base))->select_fc_conf_automatico_id(cols:12,
            con_registros:  true,id_selected: $this->row_upd->com_tipo_cliente_id,link: $this->link);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar input',data:  $com_tipo_cliente_id,header:  $header,ws:  $ws);
        }

        $descripcion = $this->html->input_descripcion(cols: 12, row_upd: $this->row_upd, value_vacio: false);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar input',data:  $descripcion,header:  $header,ws:  $ws);
        }


        $this->inputs = new stdClass();
        $this->inputs->com_tipo_cliente_id = $com_tipo_cliente_id;
        $this->inputs->descripcion = $descripcion;

        return $r_modifica;
    }




}
