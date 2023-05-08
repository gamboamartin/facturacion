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
use gamboamartin\facturacion\models\fc_relacion_cp;
use gamboamartin\facturacion\html\fc_uuid_rel_cp_html;
use gamboamartin\facturacion\models\fc_uuid_rel_cp;
use gamboamartin\facturacion\models\fc_cfdi;
use gamboamartin\system\_ctl_base;
use gamboamartin\system\links_menu;
use gamboamartin\system\system;
use gamboamartin\template\html;

use PDO;
use stdClass;

class controlador_fc_uuid_rel_cp extends _ctl_base {

    public array|stdClass $keys_selects = array();

    public function __construct(PDO $link, html $html = new \gamboamartin\template_1\html(),
                                stdClass $paths_conf = new stdClass()){
        $modelo = new fc_uuid_rel_cp(link: $link);
        $html_ = new fc_uuid_rel_cp_html(html: $html);
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




        $this->parents_verifica[] = (new fc_relacion_cp(link: $this->link));
        $this->parents_verifica[] = (new fc_cfdi(link: $this->link));


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
        $init_data['fc_cfdi'] = "gamboamartin\\facturacion";
        $init_data['fc_relacion_cp'] = "gamboamartin\\facturacion";

        $campos_view = $this->campos_view_base(init_data: $init_data, keys: $keys);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al inicializar campo view', data: $campos_view);
        }

        return $campos_view;
    }

    private function init_configuraciones(): controler
    {
        $this->seccion_titulo = 'Relacion UUID ';
        $this->titulo_lista = 'Registro de Relacion UUID';

        return $this;
    }

    public function init_datatable(): stdClass
    {

        $columns["fc_uuid_rel_cp_id"]["titulo"] = "Id";
        $columns["fc_uuid_rel_cp_codigo"]["titulo"] = "Código";
        $columns["fc_uuid_rel_cp_descripcion"]["titulo"] = "Relacion UUID";
        $columns["fc_cfdi_descripcion"]["titulo"] = "CFDI";
        $columns["fc_relacion_cp_descripcion"]["titulo"] = "Relacion cp";


        $filtro = array("fc_uuid_rel_cp.id", "fc_uuid_rel_cp.codigo", "fc_uuid_rel_cp.descripcion",
            "fc_relacion_cp.descripcion", "fc_cfdi.descripcion"
        );

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
        $keys_selects = $this->init_selects(keys_selects: array(), key: "fc_cfdi_id", label: "CFDI");
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al maquetar key_selects', data: $keys_selects);
        }


        $keys_selects = $this->init_selects(keys_selects: $keys_selects, key: "fc_relacion_cp_id", label: "Relacion cp");
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

        $keys_selects['fc_cfdi_id']->id_selected = $this->registro['fc_cfdi_id'];
        $keys_selects['fc_relacion_cp_id']->id_selected = $this->registro['fc_relacion_cp_id'];



        $base = $this->base_upd(keys_selects: $keys_selects, params: array(), params_ajustados: array());
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al integrar base', data: $base, header: $header, ws: $ws);
        }

        return $r_modifica;
    }


}