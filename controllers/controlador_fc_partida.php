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
use gamboamartin\facturacion\models\fc_partida;
use gamboamartin\system\links_menu;
use gamboamartin\system\system;

use gamboamartin\template\html;
use html\fc_partida_html;
use PDO;
use stdClass;

class controlador_fc_partida extends system{

    public array $keys_selects = array();
    public string $rfc = '';
    public string $razon_social = '';

    public function __construct(PDO $link, html $html = new \gamboamartin\template_1\html(),
                                stdClass $paths_conf = new stdClass()){
        $modelo = new fc_partida(link: $link);
        $html_ = new fc_partida_html(html: $html);
        $obj_link = new links_menu(link: $link, registro_id:  $this->registro_id);

        $columns["fc_partida_id"]["titulo"] = "Id";
        $columns["fc_partida_codigo"]["titulo"] = "Codigo";
        $columns["fc_partida_descripcion"]["titulo"] = "Factura";
        $columns["com_producto_descripcion"]["titulo"] = "Producto";
        $columns["fc_partida_cantidad"]["titulo"] = "Cantidad";
        $columns["fc_partida_valor_unitario"]["titulo"] = "Valor Unitario";
        $columns["fc_partida_descuento"]["titulo"] = "Descuento";

        $filtro = array("fc_partida.id","fc_partida.codigo","fc_partida.descripcion","com_producto.descripcion");

        $datatables = new stdClass();
        $datatables->columns = $columns;
        $datatables->filtro = $filtro;


        parent::__construct(html:$html_, link: $link,modelo:  $modelo, obj_link: $obj_link, datatables: $datatables,
            paths_conf: $paths_conf);

        $this->titulo_lista = 'Partidas';

        $this->asignar_propiedad(identificador:'com_producto_id', propiedades: ["label" => "Producto"]);
        $this->asignar_propiedad(identificador:'fc_factura_id', propiedades: ["label" => "Factura","cols" => 12]);
        $this->asignar_propiedad(identificador: 'codigo', propiedades: ['place_holder'=> 'Codigo']);
        $this->asignar_propiedad(identificador: 'cantidad', propiedades: ['place_holder'=> 'Cantidad']);
        $this->asignar_propiedad(identificador: 'valor_unitario', propiedades: ['place_holder'=> 'Valor Unitario']);
        $this->asignar_propiedad(identificador: 'descuento', propiedades: ['place_holder'=> 'Descuento']);

    }

    public function alta(bool $header, bool $ws = false): array|string
    {
        $r_alta =  parent::alta(header: false, ws: false);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar template',data:  $r_alta, header: $header,ws:$ws);
        }

        $inputs = $this->genera_inputs(keys_selects:  $this->keys_selects);
        if(errores::$error){
            $error = $this->errores->error(mensaje: 'Error al generar inputs',data:  $inputs);
            print_r($error);
            die('Error');
        }

        return $r_alta;
    }

    public function asignar_propiedad(string $identificador, mixed $propiedades)
    {
        if (!array_key_exists($identificador,$this->keys_selects)){
            $this->keys_selects[$identificador] = new stdClass();
        }

        foreach ($propiedades as $key => $value){
            $this->keys_selects[$identificador]->$key = $value;
        }
    }

    private function asigna_link_partida_row(stdClass $row): array|stdClass
    {
        $keys = array('fc_partida_id');
        $valida = $this->validacion->valida_ids(keys: $keys,registro:  $row);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al validar row',data:  $valida);
        }
        $link_modifica = $this->obj_link->link_con_id(accion:'modifica',registro_id:  $row->fc_partida_id,
            seccion:  $this->tabla);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al genera link',data:  $link_modifica);
        }

        $link_elimina_bd = $this->obj_link->link_con_id(accion:'elimina_bd',registro_id:  $row->fc_partida_id,
            seccion:  $this->tabla);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al genera link',data:  $link_elimina_bd);
        }

        $row->link_modifica = $link_modifica;

        $row->link_elimina_bd = $link_elimina_bd;

        return $row;
    }

    private function base(): array|stdClass
    {
        $r_modifica =  parent::modifica(header: false,aplica_form:  false);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al generar template',data:  $r_modifica);
        }

        $this->asignar_propiedad(identificador:'fc_factura_id',
            propiedades: ["id_selected"=>$this->row_upd->fc_factura_id]);
        $this->asignar_propiedad(identificador:'com_producto_id',
            propiedades: ["id_selected"=>$this->row_upd->com_producto_id]);

        $inputs = $this->genera_inputs(keys_selects:  $this->keys_selects);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al inicializar inputs',data:  $inputs);
        }

        $data = new stdClass();
        $data->template = $r_modifica;
        $data->inputs = $inputs;

        return $data;
    }

    public function lista(bool $header, bool $ws = false): array
    {
        $r_lista = parent::lista($header, $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al maquetar datos',data:  $r_lista, header: $header,ws:$ws);
        }

        $registros = (new fc_partida($this->link))->registros(return_obj: true);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al maquetar registros',data:  $registros,
                header: $header,ws:$ws);
        }

        $registros = $this->maqueta_registros_lista(registros: $registros);
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

    public function modifica(bool $header, bool $ws = false, string $breadcrumbs = '', bool $aplica_form = true,
                             bool $muestra_btn = true): array|string
    {
        $base = $this->base();
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al maquetar datos',data:  $base,
                header: $header,ws:$ws);
        }

        return $base->template;
    }
}
