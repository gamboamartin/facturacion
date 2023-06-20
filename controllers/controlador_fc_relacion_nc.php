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
use gamboamartin\facturacion\html\fc_relacion_nc_html;
use gamboamartin\facturacion\models\fc_nota_credito;
use gamboamartin\facturacion\models\fc_relacion_nc;
use gamboamartin\cat_sat\models\cat_sat_tipo_relacion;
use gamboamartin\system\links_menu;
use gamboamartin\system\_ctl_base;
use gamboamartin\template\html;

use PDO;
use stdClass;

class controlador_fc_relacion_nc extends _ctl_base {

    public array|stdClass $keys_selects = array();

    public function __construct(PDO $link, html $html = new \gamboamartin\template_1\html(),
                                stdClass $paths_conf = new stdClass()){
        $modelo = new fc_relacion_nc(link: $link);
        $html_ = new fc_relacion_nc_html(html: $html);
        $obj_link = new links_menu(link: $link, registro_id:  $this->registro_id);

        $datatables = $this->init_datatable();
        if(errores::$error){
            $error = $this->errores->error(mensaje: 'Error al inicializar datatable',data: $datatables);
            print_r($error);
            die('Error');
        }

        parent::__construct(html:$html_, link: $link,modelo:  $modelo, obj_link: $obj_link, datatables: $datatables,
            paths_conf: $paths_conf);

        $configuraciones = $this->init_configuraciones();
        if(errores::$error){
            $error = $this->errores->error(mensaje: 'Error al inicializar configuraciones',data: $configuraciones);
            print_r($error);
            die('Error');
        }

        $this->parents_verifica[] = (new fc_nota_credito(link: $this->link));
        $this->parents_verifica[] = (new cat_sat_tipo_relacion(link: $this->link));

        $this->verifica_parents_alta = true;
    }
    public function alta(bool $header, bool $ws = false): array|string
    {


        $r_alta =  parent::alta(header: false);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar template',data:  $r_alta, header: $header,ws:$ws);
        }
        $keys_selects = $this->init_selects_inputs();
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al inicializar selects', data: $keys_selects, header: $header,
                ws: $ws);
        }
        $inputs = $this->inputs(keys_selects: $keys_selects);
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al obtener inputs', data: $inputs, header: $header, ws: $ws);
        }

        return $r_alta;
    }
    protected function campos_view(): array
    {
        $keys = new stdClass();
        $keys->inputs = array('codigo', 'descripcion');
        $keys->selects = array();

        $init_data = array();
        $init_data['cat_sat_tipo_relacion'] = "gamboamartin\\cat_sat";
        $init_data['fc_nota_credito'] = "gamboamartin\\facturacion";

        $campos_view = $this->campos_view_base(init_data: $init_data, keys: $keys);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al inicializar campo view', data: $campos_view);
        }

        return $campos_view;
    }

    private function init_configuraciones(): controler
    {
        $this->seccion_titulo = 'Relacion notas de credito';
        $this->titulo_lista = 'Registro de relacion de las Notas de Créditos';

        return $this;
    }

    public function init_datatable(): stdClass
    {

        $columns["fc_relacion_nc_id"]["titulo"] = "Id";
        $columns["fc_relacion_nc_codigo"]["titulo"] = "Código";
        $columns["fc_relacion_nc_descripcion"]["titulo"] = "Relacion NC";

        $columns["cat_sat_tipo_relacion_descripcion"]["titulo"] = "Tipo de Comprobante";
        $columns["fc_nota_credito_descripcion"]["titulo"] = "Regimen Fiscal";


        $filtro = array("fc_relacion_nc.id", "fc_relacion_nc.codigo", "fc_relacion_nc.descripcion",
            "cat_sat_tipo_relacion.descripcion", "fc_nota_credito.descripcion");

        $datatables = new stdClass();
        $datatables->columns = $columns;
        $datatables->filtro = $filtro;

        return $datatables;
    }

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
        $keys_selects = $this->init_selects(keys_selects: array(), key: "cat_sat_tipo_relacion_id", label: "Producto");
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al maquetar key_selects', data: $keys_selects);
        }


        $keys_selects = $this->init_selects(keys_selects: $keys_selects, key: "fc_nota_credito_id", label: "Nota credito");
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al maquetar key_selects', data: $keys_selects);
        }

        return $keys_selects;
    }
    protected function key_selects_txt(array $keys_selects): array
    {
        $keys_selects = (new \base\controller\init())->key_select_txt(cols: 12, key: 'descripcion',
            keys_selects: $keys_selects, place_holder: 'Descripción');
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al maquetar key_selects', data: $keys_selects);
        }

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

        $keys_selects['cat_sat_tipo_relacion_id']->id_selected = $this->registro['cat_sat_tipo_relacion_id'];
        $keys_selects['fc_nota_credito_id']->id_selected = $this->registro['fc_nota_credito_id'];

        $base = $this->base_upd(keys_selects: $keys_selects, params: array(), params_ajustados: array());
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al integrar base', data: $base, header: $header, ws: $ws);
        }

        return $r_modifica;
    }
}