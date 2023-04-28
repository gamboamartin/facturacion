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
use gamboamartin\errores\errores;
use gamboamartin\facturacion\html\fc_partida_html;
use gamboamartin\facturacion\models\fc_partida;
use gamboamartin\facturacion\models\fc_traslado;
use gamboamartin\system\_ctl_base;
use gamboamartin\system\actions;
use gamboamartin\system\links_menu;

use gamboamartin\template\html;
use PDO;
use stdClass;

class controlador_fc_partida_cp extends _base {

    public controlador_fc_traslado $controlador_fc_traslado;
    public string $link_fc_traslado_alta_bd = '';

    public function __construct(PDO      $link, html $html = new \gamboamartin\template_1\html(),
                                stdClass $paths_conf = new stdClass())
    {
        $modelo = new fc_partida(link: $link);
        $html_ = new fc_partida_html(html: $html);

        parent::__construct(html_: $html_,link:  $link,modelo:  $modelo, paths_conf: $paths_conf);

        $init_controladores = $this->init_controladores(paths_conf: $paths_conf);
        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al inicializar controladores', data: $init_controladores);
            print_r($error);
            die('Error');
        }

        $init_links = $this->init_links();
        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al inicializar links', data: $init_links);
            print_r($error);
            die('Error');
        }
    }

    public function alta(bool $header, bool $ws = false): array|string
    {
        $r_alta = $this->init_alta();
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al inicializar alta', data: $r_alta, header: $header, ws: $ws);
        }

        $keys_selects = $this->init_selects_inputs();
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al inicializar selects', data: $keys_selects, header: $header,
                ws: $ws);
        }

        $this->row_upd->cantidad = 0;
        $this->row_upd->valor_unitario = 0;
        $this->row_upd->descuento = 0;
        $this->row_upd->subtotal = 0;
        $this->row_upd->total = 0;

        $inputs = $this->inputs(keys_selects: $keys_selects);
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al obtener inputs', data: $inputs, header: $header, ws: $ws);
        }

        return $r_alta;
    }

    public function alta_bd(bool $header, bool $ws = false): array|stdClass
    {


        $r_alta_bd = parent::alta_bd(header: $header,ws: $ws); // TODO: Change the autogenerated stub
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al insertar',data:  $r_alta_bd,header:  $header,ws:  $ws);
        }
        return $r_alta_bd;
    }

    protected function campos_view(): array
    {
        $keys = new stdClass();
        $keys->inputs = array('codigo', 'descripcion', 'cantidad', 'valor_unitario', 'descuento', 'subtotal', 'total',
            'unidad', 'impuesto','cuenta_predial');
        $keys->selects = array();

        $init_data = array();
        $init_data['com_producto'] = "gamboamartin\\comercial";
        $init_data['fc_factura'] = "gamboamartin\\facturacion";

        $campos_view = $this->campos_view_base(init_data: $init_data, keys: $keys);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al inicializar campo view', data: $campos_view);
        }

        return $campos_view;
    }

    private function init_configuraciones(): controler
    {
        $this->seccion_titulo = 'Partidas';
        $this->titulo_lista = 'Registro de Partidas';

        return $this;
    }

    private function init_controladores(stdClass $paths_conf): controler
    {
        $this->controlador_fc_traslado = new controlador_fc_traslado(link: $this->link,
            paths_conf: $paths_conf);

        return $this;
    }

    private function init_datatable(): stdClass
    {
        $columns["fc_partida_id"]["titulo"] = "Id";
        $columns["com_producto_codigo"]["titulo"] = "Cod Producto";
        $columns["com_producto_descripcion"]["titulo"] = "Producto";
        $columns["fc_factura_descripcion"]["titulo"] = "Factura";
        $columns["fc_partida_cantidad"]["titulo"] = "Cantidad";
        $columns["fc_partida_valor_unitario"]["titulo"] = "Valor Unitario";
        $columns["fc_partida_descuento"]["titulo"] = "Descuento";
        $columns["fc_partida_n_traslados"]["titulo"] = "# Traslados";
        $columns["fc_partida_n_retenidos"]["titulo"] = "# Retenidos";

        $filtro = array("fc_partida.id","fc_partida.codigo","fc_factura.descripcion","com_producto.descripcion",
            "fc_partida.cantidad","fc_partida.valor_unitario","fc_partida.descuento");

        $datatables = new stdClass();
        $datatables->columns = $columns;
        $datatables->filtro = $filtro;

        return $datatables;
    }

    public function init_links(): array|string
    {
        $this->link_fc_traslado_alta_bd = $this->obj_link->link_con_id(accion: 'nuevo_traslado_bd', link: $this->link,
            registro_id: $this->registro_id, seccion: "fc_partida");
        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al obtener link',
                data: $this->link_fc_traslado_alta_bd);
            print_r($error);
            exit;
        }

        return $this->link_fc_traslado_alta_bd;
    }

    /**
     * Integra los selects
     * @param array $keys_selects Key de selcta integrar
     * @param string $key key a validar
     * @param string $label Etiqueta a mostrar
     * @param int $id_selected  selected
     * @param int $cols cols css
     * @param bool $con_registros Intrega valores
     * @param array $filtro Filtro de datos
     * @return array
     */
    private function init_selects(array $keys_selects, string $key, string $label, int $id_selected = -1, int $cols = 6,
                                  bool  $con_registros = true, array $filtro = array()): array
    {
        $keys_selects = $this->key_select(cols: $cols, con_registros: $con_registros, filtro: $filtro, key: $key,
            keys_selects: $keys_selects, id_selected: $id_selected, label: $label);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al maquetar key_selects', data: $keys_selects);
        }

        return $keys_selects;
    }

    public function init_selects_inputs(): array
    {
        $keys_selects = $this->init_selects(keys_selects: array(), key: "com_producto_id", label: "Producto");
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al maquetar key_selects', data: $keys_selects);
        }
        $keys_selects['com_producto_id']->extra_params_keys = array("com_producto_codigo", "com_producto_descripcion",
            "cat_sat_unidad_descripcion","cat_sat_obj_imp_descripcion",'com_producto_aplica_predial',
            'cat_sat_conf_imps_id');

        $keys_selects = $this->init_selects(keys_selects: $keys_selects, key: "fc_factura_id", label: "Factura");
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al maquetar key_selects', data: $keys_selects);
        }

        $keys_selects = $this->init_selects(keys_selects: $keys_selects, key: "cat_sat_conf_imps_id", label: "Conf Imps");
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al maquetar key_selects', data: $keys_selects);
        }



        return $keys_selects;
    }

    protected function key_selects_txt(array $keys_selects): array
    {
        $keys_selects = (new \base\controller\init())->key_select_txt(cols: 4, key: 'codigo',
            keys_selects: $keys_selects, place_holder: 'Código');
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al maquetar key_selects', data: $keys_selects);
        }

        $keys_selects = (new \base\controller\init())->key_select_txt(cols: 12, key: 'descripcion',
            keys_selects: $keys_selects, place_holder: 'Descripción');
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al maquetar key_selects', data: $keys_selects);
        }

        $keys_selects = (new \base\controller\init())->key_select_txt(cols: 6, key: 'cantidad',
            keys_selects: $keys_selects, place_holder: 'Cantidad');
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al maquetar key_selects', data: $keys_selects);
        }

        $keys_selects = (new \base\controller\init())->key_select_txt(cols: 6, key: 'valor_unitario',
            keys_selects: $keys_selects, place_holder: 'Valor Unitario');
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al maquetar key_selects', data: $keys_selects);
        }

        $keys_selects = (new \base\controller\init())->key_select_txt(cols: 4, key: 'descuento',
            keys_selects: $keys_selects, place_holder: 'Descuento');
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al maquetar key_selects', data: $keys_selects);
        }

        $keys_selects = (new \base\controller\init())->key_select_txt(cols: 4, key: 'subtotal',
            keys_selects: $keys_selects, place_holder: 'Subtotal');
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al maquetar key_selects', data: $keys_selects);
        }
        $keys_selects['subtotal']->disabled = true;

        $keys_selects = (new \base\controller\init())->key_select_txt(cols: 4, key: 'total',
            keys_selects: $keys_selects, place_holder: 'Total');
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al maquetar key_selects', data: $keys_selects);
        }
        $keys_selects['total']->disabled = true;

        $keys_selects = (new \base\controller\init())->key_select_txt(cols: 4, key: 'unidad',
            keys_selects: $keys_selects, place_holder: 'Unidad');
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al maquetar key_selects', data: $keys_selects);
        }
        $keys_selects['unidad']->disabled = true;

        $keys_selects = (new \base\controller\init())->key_select_txt(cols: 4, key: 'impuesto',
            keys_selects: $keys_selects, place_holder: 'Objeto del Impuesto');
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al maquetar key_selects', data: $keys_selects);
        }
        $keys_selects['impuesto']->disabled = true;

        $keys_selects = (new \base\controller\init())->key_select_txt(cols: 4, key: 'cuenta_predial',
            keys_selects: $keys_selects, place_holder: 'Predial', required: true);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al maquetar key_selects', data: $keys_selects);
        }
        $keys_selects['cuenta_predial']->disabled = true;

        return $keys_selects;
    }

    public function modifica(bool $header, bool $ws = false): array|stdClass
    {
        $r_modifica = $this->init_modifica();
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al generar salida de template', data: $r_modifica, header: $header, ws: $ws);
        }

        $keys_selects = $this->init_selects_inputs();
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al inicializar selects', data: $keys_selects, header: $header,
                ws: $ws);
        }

        $keys_selects['com_producto_id']->id_selected = $this->registro['com_producto_id'];
        $keys_selects['fc_factura_id']->id_selected = $this->registro['fc_factura_id'];
        $keys_selects['cat_sat_conf_imps_id']->id_selected = -1;

        $this->row_upd->subtotal = $this->row_upd->cantidad * $this->row_upd->valor_unitario;
        $this->row_upd->total = $this->row_upd->subtotal - $this->row_upd->descuento;

        $base = $this->base_upd(keys_selects: $keys_selects, params: array(), params_ajustados: array());
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al integrar base', data: $base, header: $header, ws: $ws);
        }

        return $r_modifica;
    }



    public function nuevo_traslado(bool $header, bool $ws = false): array|stdClass
    {
        $datatables = $this->controlador_fc_traslado->init_datatable();
        if(errores::$error){
            $error = $this->errores->error(mensaje: 'Error al inicializar datatable',data: $datatables);
            print_r($error);
            die('Error');
        }

        $datatables->columns["modifica"]["titulo"] = "Acciones";
        $datatables->columns["modifica"]["type"] = "button";
        $datatables->columns["modifica"]["campos"] = array("elimina_bd");

        $table = $this->datatable_init(columns: $datatables->columns, filtro: $datatables->filtro,
            identificador: "#fc_traslado", data: array("fc_partida.id" => $this->registro_id));
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al generar datatable', data: $table, header: $header, ws: $ws);
        }

        $alta = $this->controlador_fc_traslado->alta(header: false);
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al generar template', data: $alta, header: $header, ws: $ws);
        }

        $partida = (new fc_partida($this->link))->get_partida(fc_partida_id: $this->registro_id);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener partida', data: $alta, header: $header, ws: $ws);
        }

        $this->controlador_fc_traslado->row_upd->codigo = $partida["fc_partida_codigo"];
        $this->controlador_fc_traslado->row_upd->descripcion = $partida["fc_partida_descripcion"];

        $identificador = "fc_partida_id";
        $propiedades = array("id_selected" => $this->registro_id, "disabled" => true,
            "filtro" => array('fc_partida.id' => $this->registro_id));
        $this->controlador_fc_traslado->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "descripcion";
        $propiedades = array("cols" => 12);
        $this->controlador_fc_traslado->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $this->inputs = $this->controlador_fc_traslado->genera_inputs(
            keys_selects:  $this->controlador_fc_traslado->keys_selects);
        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al generar inputs', data: $this->inputs);
            print_r($error);
            die('Error');
        }

        return $this->inputs;
    }

    public function nuevo_traslado_bd(bool $header, bool $ws = false): array|stdClass
    {
        $this->link->beginTransaction();

        $siguiente_view = (new actions())->init_alta_bd();
        if(errores::$error){
            $this->link->rollBack();
            return $this->retorno_error(mensaje: 'Error al obtener siguiente view', data: $siguiente_view,
                header:  $header, ws: $ws);
        }

        if(isset($_POST['btn_action_next'])){
            unset($_POST['btn_action_next']);
        }

        $partida = (new fc_partida($this->link))->get_partida(fc_partida_id: $this->registro_id);
        if(errores::$error){
            $this->link->rollBack();
            return $this->retorno_error(mensaje: 'Error al obtener partida', data: $siguiente_view,
                header:  $header, ws: $ws);
        }

        $registro = $_POST;
        $registro['fc_partida_id'] = $this->registro_id;
        $registro['codigo'] = $partida['fc_partida_id'].$registro['descripcion'];

        $alta = (new fc_traslado($this->link))->alta_registro(registro:$registro);
        if(errores::$error){
            $this->link->rollBack();
            return $this->retorno_error(mensaje: 'Error al dar de alta traslado',data:  $alta,
                header: $header,ws:$ws);
        }

        $this->link->commit();

        if($header){

            $retorno = (new actions())->retorno_alta_bd(link: $this->link, registro_id: $this->registro_id,
                seccion: $this->tabla, siguiente_view: $siguiente_view);
            if(errores::$error){
                return $this->retorno_error(mensaje: 'Error al dar de alta registro', data: $retorno,
                    header:  true, ws: $ws);
            }
            header('Location:'.$retorno);
            exit;
        }
        if($ws){
            header('Content-Type: application/json');
            echo json_encode($alta, JSON_THROW_ON_ERROR);
            exit;
        }

        return $alta;
    }
}