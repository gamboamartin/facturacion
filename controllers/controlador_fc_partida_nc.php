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
use gamboamartin\facturacion\models\fc_nota_credito;
use gamboamartin\facturacion\html\fc_partida_nc_html;
use gamboamartin\facturacion\models\fc_partida_nc;
use gamboamartin\comercial\models\com_producto;
use gamboamartin\system\links_menu;
use gamboamartin\system\system;
use gamboamartin\template\html;

use PDO;
use stdClass;

class controlador_fc_partida_nc extends system{

    public array|stdClass $keys_selects = array();

    public function __construct(PDO $link, html $html = new \gamboamartin\template_1\html(),
                                stdClass $paths_conf = new stdClass()){
        $modelo = new fc_partida_nc(link: $link);
        $html_ = new fc_partida_nc_html(html: $html);
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

        $inputs = $this->init_inputs();
        if(errores::$error){
            $error = $this->errores->error(mensaje: 'Error al inicializar inputs',data:  $inputs);
            print_r($error);
            die('Error');
        }


        $this->parents_verifica[] = (new fc_nota_credito(link: $this->link));
        $this->parents_verifica[] = (new com_producto(link: $this->link));


        $this->verifica_parents_alta = true;


    }

    public function alta(bool $header, bool $ws = false): array|string
    {
        $r_alta =  parent::alta(header: false);
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


    private function init_configuraciones(): controler
    {
        $this->seccion_titulo = 'Partida notas de Créditos';
        $this->titulo_lista = 'Registro de Partidas de notas de Créditos';

        return $this;
    }

    public function init_datatable(): stdClass
    {

        $columns["fc_partida_nc_id"]["titulo"] = "Id";
        $columns["fc_partida_nc_codigo"]["titulo"] = "Código";
        $columns["fc_partida_nc_descripcion"]["titulo"] = "Partidas de notas de credito";
        $columns["com_producto_descripcion"]["titulo"] = "Producto de notas de credito";
        $columns["fc_nota_credito_descripcion"]["titulo"] = "Nota de Créditos";


        $filtro = array("fc_partida_nc.id", "fc_partida_nc.codigo", "fc_partida_nc.descripcion",
            "fc_nota_credito.descripcion", "com_producto.descripcion"
        );

        $datatables = new stdClass();
        $datatables->columns = $columns;
        $datatables->filtro = $filtro;

        return $datatables;
    }

    private function init_inputs(): array
    {
        $identificador = "fc_nota_credito_id";
        $propiedades = array("label" => "Notas de credito");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        return $this->keys_selects;
    }

    private function init_modifica(): array|stdClass
    {
        $r_modifica =  parent::modifica(header: false);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al generar template',data:  $r_modifica);
        }
        $identificador = "fc_partida_nc_id";

        $nota_credito = (new fc_nota_credito($this->link))->get_nota_credito($this->row_upd->fc_nota_credito_id);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener csd',data:  $nota_credito);
        }


        $identificador = "com_producto_id";
        $propiedades = array("id_selected" => $nota_credito['com_producto_id']);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $inputs = $this->genera_inputs(keys_selects:  $this->keys_selects);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al generar inputs',data:  $inputs);
        }

        $data = new stdClass();
        $data->template = $r_modifica;
        $data->inputs = $inputs;

        return $data;
    }

    public function modifica(bool $header, bool $ws = false): array|stdClass
    {
        $base = $this->init_modifica();
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al maquetar datos',data:  $base,
                header: $header,ws:$ws);
        }

        return $base->template;
    }
}
