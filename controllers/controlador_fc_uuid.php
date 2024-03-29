<?php
/**
 * @author Martin Gamboa Vazquez
 * @version 1.0.0
 * @created 2022-05-14
 * @final En proceso
 *
 */
namespace gamboamartin\facturacion\controllers;

use base\orm\modelo;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\html\fc_csd_html;
use gamboamartin\facturacion\html\fc_uuid_html;
use gamboamartin\facturacion\models\fc_uuid;
use gamboamartin\facturacion\models\fc_uuid_etapa;
use gamboamartin\system\_ctl_parent_sin_codigo;
use gamboamartin\system\actions;
use gamboamartin\system\links_menu;
use gamboamartin\template\html;
use gamboamartin\xml_cfdi_4\timbra;
use html\cat_sat_motivo_cancelacion_html;
use html\cat_sat_tipo_de_comprobante_html;
use html\com_sucursal_html;
use PDO;
use stdClass;


class controlador_fc_uuid extends _ctl_parent_sin_codigo {

    public string $link_fc_uuid_cancela_bd = '';
    private modelo $modelo_etapa;
    public function __construct(PDO $link, html $html = new \gamboamartin\template_1\html(), stdClass $paths_conf = new stdClass()){
        $modelo = new fc_uuid(link: $link);

        $html_ = new fc_uuid_html(html: $html);
        $obj_link = new links_menu(link: $link, registro_id: $this->registro_id);

        $datatables = new stdClass();
        $datatables->columns = array();
        $datatables->columns['cat_sat_tipo_de_comprobante_descripcion']['titulo'] = 'Tipo de comprobante';
        $datatables->columns['fc_uuid_id']['titulo'] = 'Id';
        $datatables->columns['fc_uuid_uuid']['titulo'] = 'UUID';
        $datatables->columns['com_cliente_rfc']['titulo'] = 'Cliente';
        $datatables->columns['org_empresa_rfc']['titulo'] = 'Empresa';
        $datatables->columns['fc_uuid_fecha']['titulo'] = 'Fecha';
        $datatables->columns['fc_uuid_folio']['titulo'] = 'Folio';
        $datatables->columns['fc_uuid_etapa']['titulo'] = 'Etapa';

        $datatables->filtro = array();
        $datatables->filtro[] = 'cat_sat_tipo_de_comprobante.descripcion';
        $datatables->filtro[] = 'fc_uuid.id';
        $datatables->filtro[] = 'fc_uuid.uuid';
        $datatables->filtro[] = 'com_cliente.rfc';
        $datatables->filtro[] = 'org_empresa.rfc';
        $datatables->filtro[] = 'fc_uuid.fecha';
        $datatables->filtro[] = 'fc_uuid.folio';

        parent::__construct(html: $html_, link: $link, modelo: $modelo, obj_link: $obj_link, datatables: $datatables,
            paths_conf: $paths_conf);

        $this->titulo_lista = 'Folio Externos';

        $this->modelo_etapa = new fc_uuid_etapa(link: $this->link);


    }

    public function alta(bool $header, bool $ws = false): array|string
    {

        $r_alta_bd = parent::alta($header, $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar template',data:  $r_alta_bd, header: $header,ws:  $ws);
        }

        $fc_csd_id = (new fc_csd_html(html: $this->html_base))->select_fc_csd_id(cols: 12,
            con_registros:  true,id_selected:  -1,link:  $this->link);

        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar input',data:  $fc_csd_id, header: $header,ws:  $ws);
        }

        $this->inputs->fc_csd_id = $fc_csd_id;

        $com_sucursal_id = (new com_sucursal_html(html: $this->html_base))->select_com_sucursal_id(cols: 12,
            con_registros: true, id_selected: -1, link: $this->link, disabled: false, label: 'Cliente');

        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar input',data:  $com_sucursal_id, header: $header,ws:  $ws);
        }

        $this->inputs->com_sucursal_id = $com_sucursal_id;


        $cat_sat_tipo_de_comprobante_id =
            (new cat_sat_tipo_de_comprobante_html(html: $this->html_base))->select_cat_sat_tipo_de_comprobante_id(
                cols: 12, con_registros: true, id_selected: -1, link: $this->link);

        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar input',data:  $cat_sat_tipo_de_comprobante_id, header: $header,ws:  $ws);
        }

        $this->inputs->cat_sat_tipo_de_comprobante_id = $cat_sat_tipo_de_comprobante_id;

        $uuid = (new fc_uuid_html(html: $this->html_base))->input_uuid(cols: 12, row_upd: new stdClass(),
                value_vacio: false);

        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar input',data:  $uuid, header: $header,ws:  $ws);
        }

        $this->inputs->uuid = $uuid;

        $fecha = (new fc_uuid_html(html: $this->html_base))->input_fecha(cols: 4, row_upd: new stdClass(),
                value_vacio: false, value_hora: true);

        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar input',data:  $fecha, header: $header,ws:  $ws);
        }

        $this->inputs->fecha = $fecha;

        $folio =
            (new fc_uuid_html(html: $this->html_base))->input_folio(cols: 4, row_upd: new stdClass(),
                value_vacio: false);

        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar input',data:  $folio, header: $header,ws:  $ws);
        }

        $this->inputs->folio = $folio;

        $total =
            (new fc_uuid_html(html: $this->html_base))->input_total(cols: 4,row_upd:  new stdClass(),value_vacio:  false);

        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar input',data:  $folio, header: $header,ws:  $ws);
        }

        $this->inputs->total = $total;

        return $r_alta_bd;
    }

    public function modifica(bool $header, bool $ws = false, array $keys_selects = array()): array|stdClass
    {
        $r_modifica = parent::modifica($header, $ws, $keys_selects); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar template',data:  $r_modifica, header: $header,ws:  $ws);
        }

        $fc_csd_id = (new fc_csd_html(html: $this->html_base))->select_fc_csd_id(cols: 12,
            con_registros:  true,id_selected:  $this->row_upd->fc_csd_id,link:  $this->link);

        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar input',data:  $fc_csd_id, header: $header,ws:  $ws);
        }

        $this->inputs->fc_csd_id = $fc_csd_id;

        $com_sucursal_id = (new com_sucursal_html(html: $this->html_base))->select_com_sucursal_id(cols: 12,
            con_registros: true, id_selected: $this->row_upd->com_sucursal_id, link: $this->link, disabled: false, label: 'Cliente');

        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar input',data:  $com_sucursal_id, header: $header,ws:  $ws);
        }

        $this->inputs->com_sucursal_id = $com_sucursal_id;


        $cat_sat_tipo_de_comprobante_id =
            (new cat_sat_tipo_de_comprobante_html(html: $this->html_base))->select_cat_sat_tipo_de_comprobante_id(
                cols: 12, con_registros: true, id_selected: $this->row_upd->cat_sat_tipo_de_comprobante_id, link: $this->link);

        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar input',data:  $cat_sat_tipo_de_comprobante_id, header: $header,ws:  $ws);
        }

        $this->inputs->cat_sat_tipo_de_comprobante_id = $cat_sat_tipo_de_comprobante_id;

        $uuid = (new fc_uuid_html(html: $this->html_base))->input_uuid(cols: 12, row_upd: $this->row_upd,
            value_vacio: false);

        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar input',data:  $uuid, header: $header,ws:  $ws);
        }

        $this->inputs->uuid = $uuid;


        $fecha = (new fc_uuid_html(html: $this->html_base))->input_fecha(cols: 4, row_upd: $this->row_upd,
            value_vacio: false, value: $this->row_upd->fecha, value_hora: true);

        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar input',data:  $fecha, header: $header,ws:  $ws);
        }

        $this->inputs->fecha = $fecha;

        $folio =
            (new fc_uuid_html(html: $this->html_base))->input_folio(cols: 4, row_upd:  $this->row_upd,
                value_vacio: false);

        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar input',data:  $folio, header: $header,ws:  $ws);
        }

        $this->inputs->folio = $folio;

        $total =
            (new fc_uuid_html(html: $this->html_base))->input_total(cols: 4,row_upd: $this->row_upd,value_vacio:  false);

        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar input',data:  $folio, header: $header,ws:  $ws);
        }

        $this->inputs->total = $total;

        return $r_modifica;

    }

    public function cancela(bool $header, bool $ws = false){
        $r_modifica = parent::modifica($header, $ws, array()); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar template',data:  $r_modifica, header: $header,ws:  $ws);
        }

        $fc_uuid_id = (new fc_uuid_html(html: $this->html_base))->select_fc_uuid_id(cols: 12,
            con_registros:  true,id_selected:  -1,link:  $this->link);

        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar input',data:  $fc_uuid_id, header: $header,ws:  $ws);
        }
        $this->inputs = new stdClass();
        $this->inputs->fc_uuid_id = $fc_uuid_id;

        $cat_sat_motivo_cancelacion_id = (new cat_sat_motivo_cancelacion_html(html: $this->html_base))->select_cat_sat_motivo_cancelacion_id(cols: 12,
            con_registros:  true,id_selected:  -1,link:  $this->link);

        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar input',data:  $cat_sat_motivo_cancelacion_id, header: $header,ws:  $ws);
        }

        $this->inputs->cat_sat_motivo_cancelacion_id = $cat_sat_motivo_cancelacion_id;


        $link = $this->obj_link->link_con_id(accion: 'cancela_bd',link:  $this->link,registro_id:  $this->registro_id, seccion: $this->seccion);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar link',data:  $link, header: $header,ws:  $ws);
        }
        $this->link_fc_uuid_cancela_bd = $link;


    }

    public function cancela_bd(bool $header, bool $ws = false): array|stdClass
    {

        $r_fc_cancelacion = (new fc_uuid(link: $this->link))->cancela_bd(
            cat_sat_motivo_cancelacion_id: $_POST['cat_sat_motivo_cancelacion_id'],
            registro_id: $this->registro_id);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al cancelar factura',data:  $r_fc_cancelacion, header: $header,ws:$ws);
        }

        if($header){

            $retorno = (new actions())->retorno_alta_bd(link: $this->link, registro_id: $this->registro_id,
                seccion: $this->tabla, siguiente_view: "lista");
            if(errores::$error){
                return $this->retorno_error(mensaje: 'Error al dar de alta registro', data: $r_fc_cancelacion,
                    header:  true, ws: $ws);
            }
            header('Location:'.$retorno);
            exit;
        }
        if($ws){
            header('Content-Type: application/json');
            echo json_encode($r_fc_cancelacion, JSON_THROW_ON_ERROR);
            exit;
        }

        return $r_fc_cancelacion;

    }

    public function verifica_cancelacion(bool $header, bool $ws = false){

        $this->link->beginTransaction();
        $fc_uuid = $this->modelo->registro(registro_id: $this->registro_id);
        if(errores::$error){
            $this->link->rollBack();
            return $this->retorno_error(mensaje: 'Error al obtener fc_uuid',data:  $fc_uuid,header:  $header, ws: $ws);
        }


        $verifica = (new timbra())->consulta_estado_sat($fc_uuid['org_empresa_rfc'], $fc_uuid['com_cliente_rfc'],
            $fc_uuid['fc_uuid_total'], $fc_uuid['fc_uuid_uuid']);
        if(errores::$error){
            $this->link->rollBack();
            return $this->retorno_error(mensaje: 'Error al consulta estado',data:  $verifica,header:  $header, ws: $ws);
        }


        $integra_etapa = (new _fc_base())->integra_etapa(key_factura_id_filter: 'fc_uuid.id',
            modelo: $this->modelo, modelo_etapa: $this->modelo_etapa, registro_id: $this->registro_id, verifica: $verifica);
        if(errores::$error){
            $this->link->rollBack();
            return $this->retorno_error(mensaje: 'Error al si aplica etapa',data:  $integra_etapa,header:  $header, ws: $ws);
        }

        $this->link->commit();


        $this->mensaje = $verifica->mensaje;

        print_r($this->mensaje);exit;

        return $verifica;



    }


}
