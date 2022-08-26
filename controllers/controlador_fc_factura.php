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
use gamboamartin\system\actions;
use gamboamartin\system\links_menu;
use gamboamartin\system\system;

use gamboamartin\template\html;
use html\fc_factura_html;
use links\secciones\link_fc_factura;
use models\fc_cfd_partida;
use models\fc_factura;
use PDO;
use stdClass;

class controlador_fc_factura extends system{
    public string $rfc = '';
    public string $razon_social = '';
    public string $link_fc_cfd_partida_alta_bd = '';
    public int $fc_factura_id = -1;
    public stdClass $partidas;

    public function __construct(PDO $link, html $html = new \gamboamartin\template_1\html(),
                                stdClass $paths_conf = new stdClass()){
        $modelo = new fc_factura(link: $link);
        $html_ = new fc_factura_html(html: $html);
        $obj_link = new link_fc_factura($this->registro_id);
        parent::__construct(html:$html_, link: $link,modelo:  $modelo, obj_link: $obj_link, paths_conf: $paths_conf);
        $this->titulo_lista = 'Facturas';

        $this->fc_factura_id = $this->registro_id;
        
        $link_fc_cfd_partida_alta_bd = $obj_link->link_fc_cfd_partida_alta_bd(fc_factura_id: $this->registro_id);
        if(errores::$error){
            $error = $this->errores->error(mensaje: 'Error al generar link sucursal alta',
                data:  $link_fc_cfd_partida_alta_bd);
            print_r($error);
            exit;
        }
        $this->link_fc_cfd_partida_alta_bd = $link_fc_cfd_partida_alta_bd;
    }

    public function alta(bool $header, bool $ws = false): array|string
    {
        $r_alta =  parent::alta(header: false, ws: false); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar template',data:  $r_alta, header: $header,ws:$ws);
        }

        $inputs = (new fc_factura_html(html: $this->html_base))->genera_inputs_alta(controler: $this, link: $this->link);
        if(errores::$error){
            $error = $this->errores->error(mensaje: 'Error al generar inputs',data:  $inputs);
            print_r($error);
            die('Error');
        }
        return $r_alta;
    }

    public function alta_partida_bd(bool $header, bool $ws = false){

        $this->link->beginTransaction();

        $siguiente_view = (new actions())->init_alta_bd();
        if(errores::$error){
            $this->link->rollBack();
            return $this->retorno_error(mensaje: 'Error al obtener siguiente view', data: $siguiente_view,
                header:  $header, ws: $ws);
        }


        if(isset($_POST['guarda'])){
            unset($_POST['guarda']);
        }
        if(isset($_POST['btn_action_next'])){
            unset($_POST['btn_action_next']);
        }


        $registro = $_POST;
        $registro['fc_factura_id'] = $this->registro_id;

        $r_alta_partida_bd = (new fc_cfd_partida($this->link))->alta_registro(registro:$registro); // TODO: Change the autogenerated stub
        if(errores::$error){
            $this->link->rollBack();
            return $this->retorno_error(mensaje: 'Error al dar de alta partida',data:  $r_alta_partida_bd,
                header: $header,ws:$ws);
        }

        $this->link->commit();

        if($header){

            $retorno = (new actions())->retorno_alta_bd(registro_id:$this->registro_id,seccion: $this->tabla,
                siguiente_view: $siguiente_view);
            if(errores::$error){
                return $this->retorno_error(mensaje: 'Error al dar de alta registro', data: $r_alta_sucursal_bd,
                    header:  true, ws: $ws);
            }
            header('Location:'.$retorno);
            exit;
        }
        if($ws){
            header('Content-Type: application/json');
            echo json_encode($r_alta_sucursal_bd, JSON_THROW_ON_ERROR);
            exit;
        }

        return $r_alta_sucursal_bd;

    }

    public function modifica(bool $header, bool $ws = false, string $breadcrumbs = '', bool $aplica_form = true,
                             bool $muestra_btn = true): array|string
    {
        $r_modifica =  parent::modifica(header: false,aplica_form:  false); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar template',data:  $r_modifica, header: $header,ws:$ws);
        }

        $inputs = (new fc_factura_html(html: $this->html_base))->inputs_fc_factura(controlador:$this);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al inicializar inputs',data:  $inputs, header: $header,ws:$ws);
        }

        return $r_modifica;
    }

    public function lista(bool $header, bool $ws = false): array
    {
        $r_lista = parent::lista($header, $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al maquetar datos',data:  $r_lista, header: $header,ws:$ws);
        }

        $registros = $this->maqueta_registros_lista(registros: $this->registros);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al maquetar registros',data:  $registros, header: $header,ws:$ws);
        }
        $this->registros = $registros;


        return $r_lista;
    }

    private function maqueta_registros_lista(array $registros): array
    {
        foreach ($registros as $indice=> $row){
            $row = $this->asigna_link_partida_row(row: $row);
            if(errores::$error){
                return $this->errores->error(mensaje: 'Error al maquetar row',data:  $row);
            }
            $registros[$indice] = $row;
        }
        return $registros;
    }

    private function asigna_link_partida_row(stdClass $row): array|stdClass
    {
        $keys = array('fc_factura_id');
        $valida = $this->validacion->valida_ids(keys: $keys,registro:  $row);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al validar row',data:  $valida);
        }

        $link_partidas = $this->obj_link->link_con_id(accion:'partidas',registro_id:  $row->fc_factura_id,
            seccion:  $this->tabla);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al genera link',data:  $link_partidas);
        }

        $row->link_partidas = $link_partidas;
        $row->link_partidas_style = 'info';

        return $row;
    }

    private function data_partida_btn(array $partida): array
    {

        $params['fc_cfd_partida_id'] = $partida['fc_cfd_partida_id'];

        $btn_elimina = $this->html_base->button_href(accion:'elimina_bd',etiqueta:  'Elimina',
            registro_id:  $partida['fc_cfd_partida_id'], seccion: 'fc_cfd_partida',style:  'danger');

        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al generar btn',data:  $btn_elimina);
        }
        $partida['link_elimina'] = $btn_elimina;


        $btn_modifica = $this->html_base->button_href(accion:'modifica_partida',etiqueta:  'Modifica',
            registro_id:  $partida['fc_factura_id'], seccion: 'fc_factura',style:  'warning', params: $params);

        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al generar btn',data:  $btn_elimina);
        }
        $partida['link_modifica'] = $btn_modifica;

        $btn_ve = $this->html_base->button_href(accion:'ve_partida',etiqueta:  'Ver',
            registro_id:  $partida['fc_factura_id'], seccion: 'fc_factura',style:  'info', params: $params);

        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al generar btn',data:  $btn_elimina);
        }
        $partida['link_ve'] = $btn_ve;
        return $partida;
    }

    public function partidas(bool $header, bool $ws = false): array|stdClass
    {

        $r_modifica =  parent::modifica(header: false,aplica_form:  false); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al generar template',data:  $r_modifica);
        }

        $inputs = (new fc_factura_html(html: $this->html_base))->genera_inputs_fc_cfd_partida(controler:$this,
            link: $this->link);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al inicializar inputs',data:  $inputs, header: $header,ws:$ws);
        }

        $select = $this->select_fc_factura_id();
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar select datos',data:  $select,
                header: $header,ws:$ws);
        }


        $partidas = (new fc_cfd_partida($this->link))->partidas(fc_factura_id: $this->fc_factura_id);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener sucursales',data:  $partidas, header: $header,ws:$ws);
        }

        foreach ($partidas->registros as $indice=>$partida){
            $partida = $this->data_partida_btn(partida:$partida);
            if(errores::$error){
                return $this->retorno_error(mensaje: 'Error al asignar botones',data:  $partida, header: $header,ws:$ws);
            }
            $partidas->registros[$indice] = $partida;

        }

        $this->partidas = $partidas;
        $this->number_active = 6;

        return $inputs;

    }

    private function select_fc_factura_id(): array|string
    {
        $select = (new fc_factura_html(html: $this->html_base))->select_fc_factura_id(cols:12,con_registros: true,
            id_selected: $this->registro_id,link:  $this->link, disabled: true);

        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al generar select datos',data:  $select);
        }
        $this->inputs->select->fc_factura_id = $select;

        return $select;
    }


}
