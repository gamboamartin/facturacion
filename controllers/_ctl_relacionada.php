<?php
namespace gamboamartin\facturacion\controllers;

use base\controller\controler;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\html\fc_factura_html;
use gamboamartin\facturacion\html\fc_relacion_html;
use stdClass;

class _ctl_relacionada extends _base {

    protected string $key_folio = '';

    public function alta(bool $header, bool $ws = false): array|string
    {
        $r_alta = parent::alta($header, $ws); // TODO: Change the autogenerated stub
        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al generar template', data: $r_alta);
            print_r($error);
            die('Error');
        }

        $columns_ds[] = $this->key_folio;
        $columns_ds[] = 'cat_sat_tipo_relacion_descripcion';

        $fc_relacion_id = (new fc_relacion_html(html: $this->html_base))->select_fc_relacion_id(cols: 12,
            con_registros: true, id_selected: -1, link: $this->link, columns_ds: $columns_ds);
        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al generar input', data: $fc_relacion_id);
            print_r($error);
            die('Error');
        }

        $columns_ds = array();
        $columns_ds[] = 'fc_factura_folio';
        $columns_ds[] = 'fc_factura_uuid';

        $fc_factura_id = (new fc_factura_html(html: $this->html_base))->select_fc_factura_id(cols: 12,
            con_registros: true, id_selected: -1, link: $this->link, columns_ds: $columns_ds);
        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al generar input', data: $fc_factura_id);
            print_r($error);
            die('Error');
        }



        $this->inputs = new stdClass();
        $this->inputs->fc_factura_id = $fc_factura_id;
        $this->inputs->fc_relacion_id = $fc_relacion_id;


        return $r_alta;

    }


    private function init_configuraciones(): controler
    {
        $this->seccion_titulo = 'Relaciones';
        $this->titulo_lista = 'Registro de Relaciones';

        return $this;
    }



    private function init_datatable(): stdClass
    {
        $columns["fc_factura_relacionada_id"]["titulo"] = "Id";
        $columns["fc_factura_relacionada_fc_factura_id"]["titulo"] = "Id Factura Origen";
        $columns["fc_factura_folio"]["titulo"] = "Folio Relacionado";
        $columns["cat_sat_tipo_relacion_descripcion"]["titulo"] = "Tipo Relacion";

        $filtro = array("fc_factura.id","fc_factura.folio","cat_sat_tipo_relacion.descripcion");

        $datatables = new stdClass();
        $datatables->columns = $columns;
        $datatables->filtro = $filtro;

        return $datatables;
    }

    public function modifica(bool $header, bool $ws = false): array|stdClass
    {
        $r_modifica = parent::modifica($header, $ws); // TODO: Change the autogenerated stub
        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al generar template', data: $r_modifica);
            print_r($error);
            die('Error');
        }



        $fc_relacion_id = (new fc_relacion_html(html: $this->html_base))->select_fc_relacion_id(cols: 12,
            con_registros: true, id_selected: $this->row_upd->fc_relacion_id, link: $this->link, disabled: true);
        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al generar input', data: $fc_relacion_id);
            print_r($error);
            die('Error');
        }


        $fc_factura_id = (new fc_factura_html(html: $this->html_base))->select_fc_factura_id(cols: 12,
            con_registros: true, id_selected: $this->row_upd->fc_factura_id, link: $this->link, disabled: true);
        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al generar input', data: $fc_factura_id);
            print_r($error);
            die('Error');
        }



        $this->inputs = new stdClass();
        $this->inputs->fc_factura_id = $fc_factura_id;
        $this->inputs->fc_relacion_id = $fc_relacion_id;
        return $r_modifica;

    }
}
