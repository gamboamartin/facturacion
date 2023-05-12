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
use gamboamartin\facturacion\html\fc_factura_html;
use gamboamartin\facturacion\html\fc_nc_rel_html;
use gamboamartin\facturacion\html\fc_relacion_nc_html;
use gamboamartin\facturacion\models\fc_nc_rel;
use gamboamartin\system\_ctl_parent_sin_codigo;
use gamboamartin\system\links_menu;
use gamboamartin\template\html;
use PDO;
use stdClass;


class controlador_fc_nc_rel extends _ctl_parent_sin_codigo {


    public function __construct(PDO $link, html $html = new \gamboamartin\template_1\html(), stdClass $paths_conf = new stdClass()){
        $modelo = new fc_nc_rel(link: $link);

        $html_ = new fc_nc_rel_html(html: $html);
        $obj_link = new links_menu(link: $link, registro_id: $this->registro_id);

        $datatables = new stdClass();
        $datatables->columns = array();
        $datatables->columns['fc_nc_rel_id']['titulo'] = 'Id';
        $datatables->columns['fc_nota_credito_folio']['titulo'] = 'Nota de Credito';
        $datatables->columns['fc_factura_folio']['titulo'] = 'Factura';

        $datatables->filtro = array();
        $datatables->filtro[] = 'fc_nc_rel.id';
        $datatables->filtro[] = 'fc_relacion_nc.folio';
        $datatables->filtro[] = 'fc_factura.folio';

        parent::__construct(html: $html_, link: $link, modelo: $modelo, obj_link: $obj_link, datatables: $datatables,
            paths_conf: $paths_conf);

        $this->titulo_lista = 'Facturas Relacionadas a NC';


    }


    public function alta(bool $header, bool $ws = false): array|string
    {
        $r_alta = parent::alta($header, $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return  $this->retorno_error(mensaje: 'Error al generar template',data:  $r_alta,header:  $header, ws: $ws);
        }

        $fc_relacion_nc_id = (new fc_relacion_nc_html(html: $this->html_base))->select_fc_relacion_nc_id(
            cols: 12,con_registros:  true, id_selected: -1,link:  $this->link);

        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar input',data:  $fc_relacion_nc_id,header:  $header,ws:  $ws);
        }

        $this->inputs->fc_relacion_nc_id = $fc_relacion_nc_id;

        $fc_factura_id = (new fc_factura_html(html: $this->html_base))->select_fc_factura_id(
            cols: 12,con_registros:  true, id_selected: -1,link:  $this->link);

        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar input',data:  $fc_factura_id,header:  $header,ws:  $ws);
        }

        $this->inputs->fc_factura_id = $fc_factura_id;

        return $r_alta;
    }

    public function modifica(bool $header, bool $ws = false, array $keys_selects = array()): array|stdClass
    {
        $r_modifica =  parent::modifica($header, $ws, $keys_selects); // TODO: Change the autogenerated stub
        if(errores::$error){
            return  $this->retorno_error(mensaje: 'Error al generar template',data:  $r_modifica,header:  $header, ws: $ws);
        }

        $fc_relacion_nc_id = (new fc_relacion_nc_html(html: $this->html_base))->select_fc_relacion_nc_id(
            cols: 12,con_registros:  true, id_selected: $this->row_upd->fc_relacion_nc_id,link:  $this->link);

        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar input',data:  $fc_relacion_nc_id,header:  $header,ws:  $ws);
        }

        $this->inputs->fc_relacion_nc_id = $fc_relacion_nc_id;

        $fc_factura_id = (new fc_factura_html(html: $this->html_base))->select_fc_factura_id(
            cols: 12,con_registros:  true, id_selected: $this->row_upd->fc_factura_id,link:  $this->link);

        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar input',data:  $fc_factura_id,header:  $header,ws:  $ws);
        }

        $this->inputs->fc_factura_id = $fc_factura_id;
        return $r_modifica;


    }


}