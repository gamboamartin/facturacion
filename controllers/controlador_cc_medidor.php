<?php
namespace gamboamartin\facturacion\controllers;

use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\cc_medidor;
use gamboamartin\facturacion\models\cc_tipo_medidor;
use gamboamartin\system\html_controler;
use gamboamartin\system\links_menu;
use gamboamartin\system\system;
use gamboamartin\template_1\html;
use PDO;
use stdClass;

class controlador_cc_medidor extends system{

    public string $url_submit = '#';

    public function __construct(PDO $link, html $html = new html(), stdClass $paths_conf = new stdClass())
    {
        $html = new html_controler(html: $html);
        $modelo = new cc_medidor($link);
        $obj_link = new links_menu(link: $link, registro_id:  $this->registro_id);

        $datatables = $this->init_datatable();

        parent::__construct(
            html: $html,
            link:  $link,
            modelo:  $modelo,
            obj_link:  $obj_link,
            datatables:  $datatables
        );
    }

    public function init_datatable(): stdClass
    {
        $datatables = new stdClass();
        $datatables->columns = array();
        $datatables->columns['cc_medidor_id']['titulo'] = 'Id';
        $datatables->columns['cc_medidor_descripcion']['titulo'] = 'Medidor';
        $datatables->columns['cc_tipo_medidor_descripcion']['titulo'] = 'Tipo Medidor';

        $datatables->filtro = array();
        $datatables->filtro[] = 'cc_medidor.id';
        $datatables->filtro[] = 'cc_medidor.descripcion';
        $datatables->filtro[] = 'cc_tipo_medidor.descripcion';

        return $datatables;
    }

    public function alta(bool $header, bool $ws = false): array|string
    {
        $link = "index.php?seccion=cc_medidor&accion=alta_bd&registro_id={$this->registro_id}&session_id={$_GET['session_id']}";
        $this->url_submit = $link;
        $parent_alta = parent::alta($header, $ws);

        $modelo_cc_tipo_medidor = new cc_tipo_medidor(link: $this->link);

        $input_select_tipo_medidor = $this->html->select_catalogo(cols: 12, con_registros: true, id_selected: -1,
            modelo: $modelo_cc_tipo_medidor, columns_ds: ['cc_tipo_medidor_descripcion'],
            label: 'Tipo Medidor', name: 'cc_tipo_medidor_id', required: true
        );
        if(errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al generar $input_select_tipo_medidor',
                data: $input_select_tipo_medidor,
                header: $header, ws: $ws
            );
        }

        $this->inputs->input_select_tipo_medidor = $input_select_tipo_medidor;

        return $parent_alta;
    }

    public function modifica(bool $header, bool $ws = false, array $keys_selects = array()): array|stdClass
    {
        $this->modelo->registro_id = $this->registro_id;
        $link = "index.php?seccion=cc_medidor&accion=modifica_bd&registro_id={$this->registro_id}&session_id={$_GET['session_id']}";
        $this->url_submit = $link;
        return parent::modifica($header, $ws, $keys_selects);
    }



}