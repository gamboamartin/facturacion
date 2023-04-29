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
use gamboamartin\facturacion\html\fc_retencion_dr_part_html;
use gamboamartin\facturacion\models\fc_docto_relacionado;
use gamboamartin\facturacion\models\fc_factura;
use gamboamartin\facturacion\models\fc_impuesto_dr;
use gamboamartin\facturacion\models\fc_retencion_dr;
use gamboamartin\facturacion\models\fc_retencion_dr_part;
use gamboamartin\system\_ctl_base;
use gamboamartin\system\links_menu;
use gamboamartin\template\html;
use PDO;
use stdClass;

class controlador_fc_retencion_dr_part extends _ctl_base {

    public function __construct(PDO $link, html $html = new \gamboamartin\template_1\html(),
                                stdClass $paths_conf = new stdClass())
    {
        $modelo = new fc_retencion_dr_part(link: $link);
        $html_ = new fc_retencion_dr_part_html(html: $html);
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

        $keys_selects = $this->key_select(cols:6, con_registros: true,filtro:  array(), key: 'fc_retencion_dr_id',
            keys_selects: array(), id_selected: -1, label: 'Retencion dr');
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al maquetar key_selects',data:  $keys_selects, header: $header,ws:  $ws);
        }

        $keys_selects = $this->key_select(cols:6, con_registros: true,filtro:  array(), key: 'cat_sat_tipo_impuesto_id',
            keys_selects: $keys_selects, id_selected: -1, label: 'Tipo de impuesto');
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al maquetar key_selects',data:  $keys_selects, header: $header,ws:  $ws);
        }

        $keys_selects = $this->key_select(cols:6, con_registros: true,filtro:  array(), key: 'cat_sat_tipo_factor_id',
            keys_selects: $keys_selects, id_selected: -1, label: 'Tipo de factor');
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al maquetar key_selects',data:  $keys_selects, header: $header,ws:  $ws);
        }

        $keys_selects = $this->key_select(cols:6, con_registros: true,filtro:  array(), key: 'cat_sat_factor_id',
            keys_selects: $keys_selects, id_selected: -1, label: 'Factor');
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
    public function calcula_base_dr(bool $header, bool $ws = false)
    {
        $fc_retencion_dr_part_id = $this->registro_id;

        $filtro_retencion_dr_part['fc_retencion_dr_part.id'] = $this->registro_id;
        $fc_retencion_dr_part = (new fc_retencion_dr_part(link: $this->link))->filtro_and(filtro: $filtro_retencion_dr_part);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al obtener fc_retencion_dr_part', data: $fc_retencion_dr_part);
        }

        $fc_retencion_dr_id = $fc_retencion_dr_part->registros[0]['fc_retencion_dr_part_fc_retencion_dr_id'];
        $filtro_retencion_dr['fc_retencion_dr.id'] = $fc_retencion_dr_id;
        $fc_retencion_dr = (new fc_retencion_dr($this->link))->filtro_and(filtro: $filtro_retencion_dr);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al obtener $fc_retencion_dr', data: $fc_retencion_dr);
        }

        $fc_impuesto_dr_id = $fc_retencion_dr->registros[0]['fc_retencion_dr_fc_impuesto_dr_id'];
        $filtro_impuesto_dr['fc_impuesto_dr.id'] = $fc_impuesto_dr_id;
        $fc_impuesto_dr = (new fc_impuesto_dr($this->link))->filtro_and(filtro: $filtro_impuesto_dr);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al obtener $fc_impuesto_dr', data: $fc_impuesto_dr);
        }

        $fc_docto_relacionado_id = $fc_impuesto_dr->registros[0]['fc_impuesto_dr_fc_docto_relacionado_id'];
        $filtro_docto_relacionado['fc_docto_relacionado.id'] = $fc_docto_relacionado_id;
        $fc_docto_relacionado = (new fc_docto_relacionado($this->link))->filtro_and(filtro: $filtro_docto_relacionado);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al obtener $fc_docto_relacionado', data: $fc_docto_relacionado);
        }

        $fc_factura_id = $fc_docto_relacionado->registros[0]['fc_docto_relacionado_fc_factura_id'];
        $filtro_fc_factura['fc_factura.id'] = $fc_factura_id;
        $fc_factura = (new fc_factura($this->link))->filtro_and(filtro: $filtro_fc_factura);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al obtener fc_factura', data: $fc_factura);
        }

        $factura = (new fc_factura(link: $this->link))->get_factura(fc_factura_id: $fc_factura->registros[0]['fc_factura_id']);

        print_r($factura);

        exit;
    }

    /**
     * public function calcula_base_dr(float $total, float $factor_traslado, float $factor_retencion): float|array
    {
        return $total / ( 1 + ($factor_traslado - $factor_retencion));
    }
     */

    protected function campos_view(): array
    {
        $keys = new stdClass();
        $keys->inputs = array('codigo','base_dr','importe_dr');
        $keys->selects = array();

        $init_data = array();
        $init_data['fc_retencion_dr'] = "gamboamartin\\facturacion";
        $init_data['cat_sat_factor'] = "gamboamartin\\cat_sat";
        $init_data['cat_sat_tipo_factor'] = "gamboamartin\\cat_sat";
        $init_data['cat_sat_tipo_impuesto'] = "gamboamartin\\cat_sat";
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

        $keys_selects = (new \base\controller\init())->key_select_txt(cols: 6,key: 'base_dr', keys_selects:$keys_selects, place_holder: 'Base dr');
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al maquetar key_selects',data:  $keys_selects);
        }

        $keys_selects = (new \base\controller\init())->key_select_txt(cols: 6,key: 'importe_dr', keys_selects:$keys_selects, place_holder: 'Importe dr');
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

        $keys_selects = $this->key_select(cols:6, con_registros: true,filtro:  array(), key: 'fc_retencion_dr_id',
            keys_selects: array(), id_selected: $this->registro['fc_retencion_dr_part_fc_retencion_dr_id'], label: 'Retencion dr');
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al maquetar key_selects',data:  $keys_selects, header: $header,ws:  $ws);
        }

        $keys_selects = $this->key_select(cols:6, con_registros: true,filtro:  array(), key: 'cat_sat_tipo_impuesto_id',
            keys_selects: $keys_selects, id_selected: $this->registro['fc_retencion_dr_part_cat_sat_tipo_impuesto_id'], label: 'Tipo de impuesto');
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al maquetar key_selects',data:  $keys_selects, header: $header,ws:  $ws);
        }

        $keys_selects = $this->key_select(cols:6, con_registros: true,filtro:  array(), key: 'cat_sat_tipo_factor_id',
            keys_selects: $keys_selects, id_selected: $this->registro['fc_retencion_dr_part_cat_sat_tipo_factor_id'], label: 'Tipo de factor');
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al maquetar key_selects',data:  $keys_selects, header: $header,ws:  $ws);
        }

        $keys_selects = $this->key_select(cols:6, con_registros: true,filtro:  array(), key: 'cat_sat_factor_id',
            keys_selects: $keys_selects, id_selected: $this->registro['fc_retencion_dr_part_cat_sat_factor_id'], label: 'Factor');
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
        $columns["fc_retencion_dr_part_id"]["titulo"] = "Id";
        $columns["fc_retencion_dr_part_codigo"]["titulo"] = "Codigo";
        $columns["fc_retencion_dr_part_descripcion_select"]["titulo"] = "Descripcion";
        $columns["fc_retencion_dr_part_base_dr"]["titulo"] = "Base dr";
        $columns["fc_retencion_dr_part_importe_dr"]["titulo"] = "Importe dr";

        $filtro = array("fc_retencion_dr_part.id","fc_retencion_dr_part.codigo",
            "fc_retencion_dr_part.descripcion_select","fc_retencion_dr_part.base_dr","fc_retencion_dr_part.importe_dr");

        $datatables = new stdClass();
        $datatables->columns = $columns;
        $datatables->filtro = $filtro;

        return $datatables;
    }


}
