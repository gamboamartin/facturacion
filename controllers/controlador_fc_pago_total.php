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
use gamboamartin\facturacion\html\fc_pago_total_html;
use gamboamartin\facturacion\models\fc_pago_total;
use gamboamartin\system\_ctl_base;
use gamboamartin\system\links_menu;
use gamboamartin\template\html;
use PDO;
use stdClass;

class controlador_fc_pago_total extends _ctl_base {

    public function __construct(PDO      $link, html $html = new \gamboamartin\template_1\html(),
                                stdClass $paths_conf = new stdClass())
    {
        $modelo = new fc_pago_total(link: $link);
        $html_ = new fc_pago_total_html(html: $html);
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

        $keys_selects = $this->key_select(cols:6, con_registros: true,filtro:  array(), key: 'fc_pago_id',
            keys_selects: array(), id_selected: -1, label: 'Pago');
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
        $keys->inputs = array('codigo','total_traslados_base_iva_16','total_traslados_base_iva_08',
            'total_traslados_base_iva_00','total_traslados_impuesto_iva_16','total_traslados_impuesto_iva_08',
            'total_traslados_impuesto_iva_00','total_retenciones_iva','total_retenciones_ieps',
            'total_retenciones_isr','monto_total_pagos');
        $keys->selects = array();

        $init_data = array();
        $init_data['fc_pago'] = "gamboamartin\\facturacion";
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

        $keys_selects = (new \base\controller\init())->key_select_txt(cols: 6,key: 'total_traslados_base_iva_16', keys_selects:$keys_selects, place_holder: 'Total Traslados Base IVA 16');
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al maquetar key_selects',data:  $keys_selects);
        }

        $keys_selects = (new \base\controller\init())->key_select_txt(cols: 6,key: 'total_traslados_base_iva_08', keys_selects:$keys_selects, place_holder: 'Total Traslados Base IVA 08');
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al maquetar key_selects',data:  $keys_selects);
        }

        $keys_selects = (new \base\controller\init())->key_select_txt(cols: 6,key: 'total_traslados_base_iva_00', keys_selects:$keys_selects, place_holder: 'Total Traslados Base IVA 00');
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al maquetar key_selects',data:  $keys_selects);
        }

        $keys_selects = (new \base\controller\init())->key_select_txt(cols: 6,key: 'total_traslados_impuesto_iva_16', keys_selects:$keys_selects, place_holder: 'Total Traslados Impuesto IVA 16');
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al maquetar key_selects',data:  $keys_selects);
        }

        $keys_selects = (new \base\controller\init())->key_select_txt(cols: 6,key: 'total_traslados_impuesto_iva_08', keys_selects:$keys_selects, place_holder: 'Total Traslados Impuesto IVA 08');
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al maquetar key_selects',data:  $keys_selects);
        }

        $keys_selects = (new \base\controller\init())->key_select_txt(cols: 6,key: 'total_traslados_impuesto_iva_00', keys_selects:$keys_selects, place_holder: 'Total Traslados Impuesto IVA 00');
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al maquetar key_selects',data:  $keys_selects);
        }

        $keys_selects = (new \base\controller\init())->key_select_txt(cols: 6,key: 'total_retenciones_iva', keys_selects:$keys_selects, place_holder: 'Total Retenciones IVA');
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al maquetar key_selects',data:  $keys_selects);
        }

        $keys_selects = (new \base\controller\init())->key_select_txt(cols: 6,key: 'total_retenciones_ieps', keys_selects:$keys_selects, place_holder: 'Total Retenciones IEPS');
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al maquetar key_selects',data:  $keys_selects);
        }

        $keys_selects = (new \base\controller\init())->key_select_txt(cols: 6,key: 'total_retenciones_isr', keys_selects:$keys_selects, place_holder: 'Total Retenciones ISR');
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al maquetar key_selects',data:  $keys_selects);
        }

        $keys_selects = (new \base\controller\init())->key_select_txt(cols: 6,key: 'monto_total_pagos', keys_selects:$keys_selects, place_holder: 'Monto Total Pagos');
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

        $keys_selects = $this->key_select(cols:6, con_registros: true,filtro:  array(), key: 'fc_pago_id',
            keys_selects: array(), id_selected: $this->registro['fc_pago_total_fc_pago_id'], label: 'Pago');
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
        $columns["fc_pago_total_id"]["titulo"] = "Id";
        $columns["fc_pago_total_codigo"]["titulo"] = "Codigo";
        $columns["fc_pago_total_descripcion_select"]["titulo"] = "Descripcion";
        $columns["fc_pago_total_alias"]["titulo"] = "Alias";

        $filtro = array("fc_pago_total.id","fc_pago_total.codigo","fc_pago_total.descripcion_select","fc_pago_total.alias");

        $datatables = new stdClass();
        $datatables->columns = $columns;
        $datatables->filtro = $filtro;

        return $datatables;
    }

}
