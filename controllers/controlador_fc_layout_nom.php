<?php
namespace gamboamartin\facturacion\controllers;

use gamboamartin\errores\errores;
use gamboamartin\facturacion\html\fc_layout_nom_html;
use gamboamartin\facturacion\models\fc_layout_nom;
use gamboamartin\system\links_menu;
use gamboamartin\system\system;
use gamboamartin\template_1\html;
use PDO;
use stdClass;

class controlador_fc_layout_nom extends system{

    public stdClass|array $keys_selects = array();

    public function __construct(PDO $link, html $html = new html(), stdClass $paths_conf = new stdClass()){
        $modelo = new fc_layout_nom(link: $link);
        $html_ = new fc_layout_nom_html(html: $html);
        $obj_link = new links_menu(link: $link, registro_id:  $this->registro_id);

        $this->rows_lista = array('id', 'codigo', 'descripcion');

        parent::__construct(html:$html_, link: $link,modelo:  $modelo, obj_link: $obj_link,
            paths_conf: $paths_conf);

        $this->lista_get_data = true;

    }

    public function alta(bool $header, bool $ws = false): array|string
    {

        $alta = parent::alta($header, $ws);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar input', data: $alta,header:  $header,ws:  $ws);
        }
        $this->inputs = new stdClass();

        $documento = $this->html->input_file(cols: 12, name: 'documento', row_upd: new stdClass(), value_vacio: false,
            place_holder: 'Layout', required: false);
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al obtener inputs', data: $documento, header: $header, ws: $ws);
        }

        $this->inputs->documento = $documento;


        $input_descripcion= $this->html->input_descripcion(cols: 12,row_upd: new stdClass(),value_vacio: false);

        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar $input_descripcion',
                data: $input_descripcion,header:  $header,ws:  $ws);
        }
        $this->inputs->descripcion = $input_descripcion;

        return $alta;
    }

    public function alta_bd(bool $header, bool $ws = false): array|stdClass
    {

        $r_alta = parent::alta_bd($header, $ws);
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al guardar', data: $r_alta, header: $header, ws: $ws);
        }
        return $r_alta;

    }



    public function carga_empleados(bool $header, bool $ws = false)
    {
        $rows_empleados = (new _xls_empleados())->carga_empleados($this->link,$this->registro_id);
        if(errores::$error){
            return (new errores())->error('Error al generar row', $rows_empleados);
        }
        exit;

    }

    public function genera_dispersion(bool $header, bool $ws = false)
    {
        $fc_layout_nom = (new fc_layout_nom($this->link))->registro($this->registro_id,retorno_obj: true);
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al obtener layout', data: $fc_layout_nom, header: $header, ws: $ws);
        }

        $verif_empleado = (new _xls_empleados())->carga_empleados($this->link,$this->registro_id);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al verificar empleado', data: $verif_empleado,
                header: $header, ws: $ws);
        }

        $datos = (new _xls_dispersion())->lee_layout_base(fc_layout_nom: $fc_layout_nom);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener $datos', data: $datos,
                header: $header, ws: $ws);
        }

        $xls = (new _xls_dispersion())->write_dispersion(hoja_base: $datos->hoja, layout_dispersion: $datos->layout_dispersion);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar $layout_dispersion', data: $xls,
                header: $header, ws: $ws);
        }

        exit;


    }



}
