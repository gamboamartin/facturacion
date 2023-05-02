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
use gamboamartin\facturacion\html\fc_docto_relacionado_html;
use gamboamartin\facturacion\models\fc_docto_relacionado;
use gamboamartin\system\_ctl_base;
use gamboamartin\system\links_menu;
use gamboamartin\template\html;
use PDO;
use stdClass;

class controlador_fc_docto_relacionado extends _ctl_base {

    public function __construct(PDO      $link, html $html = new \gamboamartin\template_1\html(),
                                stdClass $paths_conf = new stdClass())
    {
        $modelo = new fc_docto_relacionado(link: $link);
        $html_ = new fc_docto_relacionado_html(html: $html);
        $obj_link = new links_menu(link: $link, registro_id:  $this->registro_id);

        $datatables = $this->init_datatable();
        if(errores::$error){
            $error = $this->errores->error(mensaje: 'Error al inicializar datatable',data: $datatables);
            print_r($error);
            die('Error');
        }

        parent::__construct(html:$html_, link: $link,modelo:  $modelo, obj_link: $obj_link, datatables: $datatables,
            paths_conf: $paths_conf);
    }

    public function alta(bool $header, bool $ws = false): array|string
    {
        $r_alta = $this->init_alta();
        if(errores::$error){
            return $this->retorno_error(
                mensaje: 'Error al inicializar alta',data:  $r_alta, header: $header,ws:  $ws);
        }

        $keys_selects = $this->key_select(cols:6, con_registros: true,filtro:  array(), key: 'fc_factura_id',
            keys_selects: array(), id_selected: -1, label: 'Factura');
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al maquetar key_selects',data:  $keys_selects, header: $header,ws:  $ws);
        }

        $keys_selects = $this->key_select(cols:6, con_registros: true,filtro:  array(), key: 'cat_sat_obj_imp_id',
            keys_selects: $keys_selects, id_selected: -1, label: 'Objeto de impuesto');
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al maquetar key_selects',data:  $keys_selects, header: $header,ws:  $ws);
        }

        $keys_selects = $this->key_select(cols:6, con_registros: true,filtro:  array(), key: 'fc_pago_pago_id',
            keys_selects: $keys_selects, id_selected: -1, label: 'Pago Pago');
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al maquetar key_selects',data:  $keys_selects, header: $header,ws:  $ws);
        }

        $inputs = $this->inputs(keys_selects: $keys_selects);
        if(errores::$error){
            return $this->retorno_error(
                mensaje: 'Error al obtener inputs',data:  $inputs, header: $header,ws:  $ws);
        }

        return $r_alta;
    }

    protected function campos_view(): array
    {
        $keys = new stdClass();
        $keys->inputs = array('codigo', 'equivalencia_dr', 'num_parcialidad', 'imp_saldo_ant', 'imp_pagado', 'imp_saldo_insoluto');
        $keys->selects = array();

        $init_data = array();
        $init_data['fc_factura'] = "gamboamartin\\facturacion";
        $init_data['cat_sat_obj_imp'] = "gamboamartin\\cat_sat";
        $init_data['fc_pago_pago'] = "gamboamartin\\facturacion ";
        $campos_view = $this->campos_view_base(init_data: $init_data,keys:  $keys);

        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al inicializar campo view',data:  $campos_view);
        }


        return $campos_view;
    }

    protected function key_selects_txt(array $keys_selects): array
    {

        $keys_selects = (new \base\controller\init())->key_select_txt(cols: 6,key: 'codigo', keys_selects:$keys_selects, place_holder: 'Codigo');
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al maquetar key_selects',data:  $keys_selects);
        }
        $keys_selects = (new \base\controller\init())->key_select_txt(cols: 6,key: 'equivalencia_dr', keys_selects:$keys_selects, place_holder: 'Equivalencia');
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al maquetar key_selects',data:  $keys_selects);
        }
        $keys_selects = (new \base\controller\init())->key_select_txt(cols: 6,key: 'num_parcialidad', keys_selects:$keys_selects, place_holder: 'Num. Parcialidad');
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al maquetar key_selects',data:  $keys_selects);
        }
        $keys_selects = (new \base\controller\init())->key_select_txt(cols: 6,key: 'imp_saldo_ant', keys_selects:$keys_selects, place_holder: 'Imp. Saldo Ant.');
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al maquetar key_selects',data:  $keys_selects);
        }
        $keys_selects = (new \base\controller\init())->key_select_txt(cols: 6,key: 'imp_pagado', keys_selects:$keys_selects, place_holder: 'Imp. Pagado');
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al maquetar key_selects',data:  $keys_selects);
        }
        $keys_selects = (new \base\controller\init())->key_select_txt(cols: 6,key: 'imp_saldo_insoluto', keys_selects:$keys_selects, place_holder: 'Imp. Saldo Insoluto');
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al maquetar key_selects',data:  $keys_selects);
        }

        return $keys_selects;
    }

    public function modifica(bool $header, bool $ws = false): array|stdClass
    {

        $r_modifica = $this->init_modifica(); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(
                mensaje: 'Error al generar salida de template',data:  $r_modifica,header: $header,ws: $ws);
        }

        $keys_selects = $this->key_select(cols:6, con_registros: true,filtro:  array(), key: 'fc_factura_id',
            keys_selects: array(), id_selected: $this->registro['fc_docto_relacionado_fc_factura_id'], label: 'Factura');
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al maquetar key_selects',data:  $keys_selects, header: $header,ws:  $ws);
        }

        $keys_selects = $this->key_select(cols:6, con_registros: true,filtro:  array(), key: 'cat_sat_obj_imp_id',
            keys_selects: $keys_selects, id_selected: $this->registro['fc_docto_relacionado_cat_sat_obj_imp_id'], label: 'Objeto de Impuesto');
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al maquetar key_selects',data:  $keys_selects, header: $header,ws:  $ws);
        }

        $keys_selects = $this->key_select(cols:6, con_registros: true,filtro:  array(), key: 'fc_pago_pago_id',
            keys_selects: $keys_selects, id_selected: $this->registro['fc_docto_relacionado_fc_pago_pago_id'], label: 'Pago Pago');
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al maquetar key_selects',data:  $keys_selects, header: $header,ws:  $ws);
        }

        $base = $this->base_upd(keys_selects: $keys_selects, params: array(),params_ajustados: array());
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al integrar base',data:  $base, header: $header,ws:  $ws);
        }

        return $r_modifica;
    }

    private function init_datatable(): stdClass
    {
        $columns["fc_docto_relacionado_id"]["titulo"] = "Id";
        $columns["fc_docto_relacionado_codigo"]["titulo"] = "Codigo";
        $columns["fc_docto_relacionado_descripcion_select"]["titulo"] = "Descripcion";

        $filtro = array("fc_docto_relacionado.id","fc_docto_relacionado.codigo","fc_docto_relacionado.descripcion_select");

        $datatables = new stdClass();
        $datatables->columns = $columns;
        $datatables->filtro = $filtro;

        return $datatables;
    }


}
