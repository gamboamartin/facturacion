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
use config\generales;
use gamboamartin\cat_sat\models\cat_sat_tipo_de_comprobante;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\fc_conf_retenido;
use gamboamartin\facturacion\models\fc_conf_traslado;
use gamboamartin\facturacion\models\fc_factura;
use gamboamartin\facturacion\models\fc_partida;
use gamboamartin\facturacion\models\fc_traslado;
use gamboamartin\system\actions;
use gamboamartin\system\links_menu;
use gamboamartin\system\system;

use gamboamartin\template\html;
use gamboamartin\xml_cfdi_4\cfdis;
use html\fc_partida_html;
use html\fc_factura_html;
use models\doc_documento;
use PDO;
use stdClass;

class controlador_fc_factura extends system{

    public array $keys_selects = array();
    public controlador_fc_partida $controlador_fc_partida;
    public controlador_com_producto $controlador_com_producto;

    public string $rfc = '';
    public string $razon_social = '';
    public string $link_fc_partida_alta_bd = '';
    public string $link_fc_partida_modifica_bd = '';
    public string $link_fc_factura_partidas = '';
    public string $link_fc_factura_nueva_partida = '';
    public string $link_com_producto = '';
    public string $link_factura_genera_xml = '';
    public int $fc_factura_id = -1;
    public int $fc_partida_id = -1;
    public stdClass $partidas;

    public function __construct(PDO $link, html $html = new \gamboamartin\template_1\html(),
                                stdClass $paths_conf = new stdClass()){
        $modelo = new fc_factura(link: $link);
        $html_ = new fc_factura_html(html: $html);
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

        $controladores = $this->init_controladores(paths_conf: $paths_conf);
        if(errores::$error){
            $error = $this->errores->error(mensaje: 'Error al inicializar controladores',data:  $controladores);
            print_r($error);
            die('Error');
        }

        $links = $this->init_links();
        if(errores::$error){
            $error = $this->errores->error(mensaje: 'Error al inicializar links',data:  $links);
            print_r($error);
            die('Error');
        }

        $inputs = $this->init_inputs();
        if(errores::$error){
            $error = $this->errores->error(mensaje: 'Error al inicializar inputs',data:  $inputs);
            print_r($error);
            die('Error');
        }

        if(isset($_GET['fc_partida_id'])){
            $this->fc_partida_id = $_GET['fc_partida_id'];
        }

    }

    public function alta(bool $header, bool $ws = false): array|string
    {
        $r_alta =  parent::alta(header: false);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar template',data:  $r_alta, header: $header,ws:$ws);
        }

        $tipo_comprobante = $this->get_tipo_comprobante();
        if(errores::$error){
            $error = $this->errores->error(mensaje: 'Error al obtener tipo de comprobante',data:  $tipo_comprobante);
            print_r($error);
            die('Error');
        }

        $this->asignar_propiedad(identificador: 'cat_sat_tipo_de_comprobante_id',
            propiedades: ["id_selected" => $tipo_comprobante,
                "filtro" => array('cat_sat_tipo_de_comprobante.id' => $tipo_comprobante)]);

        $this->row_upd->fecha = date('Y-m-d');
        $this->row_upd->subtotal = 0;
        $this->row_upd->descuento = 0;
        $this->row_upd->impuestos_trasladados = 0;
        $this->row_upd->impuestos_retenidos = 0;
        $this->row_upd->total = 0;

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

    private function ruta_archivos(): array|string
    {
        $ruta_archivos = (new generales())->path_base.'archivos';
        if(!file_exists($ruta_archivos)){
            mkdir($ruta_archivos,0777,true);
        }
        if(!file_exists($ruta_archivos)){
            return $this->errores->error(mensaje: 'Error no existe '.$ruta_archivos, data: $ruta_archivos);
        }
        return $ruta_archivos;
    }

    private function ruta_archivos_tmp(string $ruta_archivos): array|string
    {
        $ruta_archivos_tmp = $ruta_archivos.'/tmp';

        if(!file_exists($ruta_archivos_tmp)){
            mkdir($ruta_archivos_tmp,0777,true);
        }
        if(!file_exists($ruta_archivos_tmp)){
            return $this->errores->error(mensaje: 'Error no existe '.$ruta_archivos_tmp, data: $ruta_archivos_tmp);
        }
        return $ruta_archivos_tmp;
    }

    private function genera_ruta_archivo_tmp(): array|string
    {
        $ruta_archivos = $this->ruta_archivos();
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al generar ruta de archivos', data: $ruta_archivos);
        }

        $ruta_archivos_tmp = $this->ruta_archivos_tmp(ruta_archivos: $ruta_archivos);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al generar ruta de archivos', data: $ruta_archivos_tmp);
        }
        return $ruta_archivos_tmp;
    }

    public function genera_xml(bool $header, bool $ws = false){

        $factura = $this->modelo->get_factura(fc_factura_id: $this->registro_id);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener fc_factura',data:  $factura, header: $header,ws:$ws);
        }

        $comprobante = array();
        $comprobante['lugar_expedicion'] = $factura['dp_cp_descripcion'];
        $comprobante['tipo_de_comprobante'] = $factura['cat_sat_tipo_de_comprobante_codigo'];
        $comprobante['moneda'] = $factura['cat_sat_moneda_codigo'];
        $comprobante['sub_total'] = $factura['fc_factura_sub_total'];
        $comprobante['total'] = $factura['fc_factura_total'];
        $comprobante['exportacion'] = $factura['fc_factura_exportacion'];
        $comprobante['folio'] = $factura['fc_factura_folio'];

        $emisor = array();
        $emisor['rfc'] = $factura['org_empresa_rfc'];

        $receptor = array();
        $receptor['rfc'] = $factura['com_cliente_rfc'];
        $receptor['nombre'] = $factura['org_empresa_rfc'];
        $receptor['domicilio_fiscal_receptor'] = $factura['org_empresa_rfc'];
        $receptor['regimen_fiscal_receptor'] = $factura['org_empresa_rfc'];
        $receptor['uso_cfdi'] = $factura['org_empresa_rfc'];

        $conceptos = $factura['conceptos'];

        $impuestos = new stdClass();
        $impuestos->total_impuestos_trasladados = $factura['total_impuestos_trasladados'];
        $impuestos->total_impuestos_retenidos = 'x';
        $impuestos->traslados = $factura['traslados'];
        $impuestos->retenciones = $factura['retenidos'];

        $ingreso = (new cfdis())->ingreso(comprobante: $comprobante,conceptos:  $conceptos, emisor: $emisor,
            impuestos: $impuestos,receptor:  $receptor);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener xml',data:  $ingreso, header: $header,ws:$ws);
        }

        $ruta_archivos_tmp = $this->genera_ruta_archivo_tmp();
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al generar ruta de archivos', data: $ruta_archivos_tmp, header: $header, ws: $ws);
        }

        $documento = array();
        $file = array();
        $file_xml_st = $ruta_archivos_tmp.'/'.$this->registro_id.'.st.xml';
        file_put_contents($file_xml_st, $ingreso);

        $file['name'] = $file_xml_st;
        $file['tmp_name'] = $file_xml_st;
        $documento['doc_tipo_documento_id'] = 1;
        $documento['descripcion'] = 1;

        $documento = (new doc_documento(link: $this->link))->alta_registro(registro: $documento, file: $file);
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al guardar xml', data: $documento, header: $header, ws: $ws);
        }
        unlink($file_xml_st);
        ob_clean();
        echo trim(file_get_contents($documento->registro['doc_documento_ruta_absoluta']));
        header('Content-Type: text/xml');
        exit;
    }

    private function get_tipo_comprobante(): array|int
    {
        if (generales::CAT_SAT_TIPO_DE_COMPROBANTE === ""){
            return $this->errores->error(mensaje: 'Error CAT_SAT_TIPO_DE_COMPROBANTE no puede estar vacio',data:  $this);
        }

        $filtro['cat_sat_tipo_de_comprobante.descripcion'] = generales::CAT_SAT_TIPO_DE_COMPROBANTE;
        $tipo_comprobante = (new cat_sat_tipo_de_comprobante($this->link))->filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener tipo de comprobante',data:  $this);
        }

        if ($tipo_comprobante->n_registros === 0){
            $tipo_comprobante = (new cat_sat_tipo_de_comprobante($this->link))->get_tipo_comprobante_predeterminado();
            if(errores::$error){
                return $this->errores->error(mensaje: 'Error al obtener tipo de comprobante predeterminado',data:  $this);
            }
        }

        return $tipo_comprobante->registros[0]['cat_sat_tipo_de_comprobante_id'];
    }

    private function init_configuraciones(): controler
    {
        $this->seccion_titulo = 'Facturas';
        $this->titulo_lista = 'Registro de Facturas';

        return $this;
    }

    private function init_controladores(stdClass $paths_conf): controler
    {
        $this->controlador_fc_partida= new controlador_fc_partida(link:$this->link, paths_conf: $paths_conf);
        $this->controlador_com_producto = new controlador_com_producto(link:$this->link, paths_conf: $paths_conf);

        return $this;
    }

    public function init_datatable(): stdClass
    {
        $columns["fc_factura_id"]["titulo"] = "Id";
        $columns["fc_factura_codigo"]["titulo"] = "Código";
        $columns["fc_factura_descripcion"]["titulo"] = "Factura";
        $columns["fc_factura_folio"]["titulo"] = "Folio";
        $columns["fc_factura_serie"]["titulo"] = "Serie";

        $filtro = array("fc_factura.id","fc_factura.codigo","fc_factura.descripcion","fc_factura.folio",
            "fc_factura.serie");

        $datatables = new stdClass();
        $datatables->columns = $columns;
        $datatables->filtro = $filtro;

        return $datatables;
    }

    private function init_links(): array|string
    {
        $this->obj_link->genera_links($this);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al generar links para partida',data:  $this->obj_link);
        }

        $link = $this->obj_link->get_link($this->seccion,"nueva_partida");
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener link nueva_partida',data:  $link);
        }
        $this->link_fc_factura_nueva_partida = $link;

        $link = $this->obj_link->get_link($this->seccion,"alta_partida_bd");
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener link alta_partida_bd',data:  $link);
        }
        $this->link_fc_partida_alta_bd = $link;

        $link = $this->obj_link->get_link($this->seccion,"genera_xml");
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener link genera_xml',data:  $link);
        }
        $this->link_factura_genera_xml = $link;

        $this->link_com_producto = $this->controlador_com_producto->link_com_producto;

        return $link;
    }

    private function init_inputs(): array
    {
        $identificador = "fc_csd_id";
        $propiedades = array("label" => "Empresa", "cols" => 12,"extra_params_keys"=>array("fc_csd_serie"));
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "com_sucursal_id";
        $propiedades = array("label" => "Cliente", "cols" => 12,"extra_params_keys" => array("com_cliente_cat_sat_forma_pago_id",
            "com_cliente_cat_sat_metodo_pago_id","com_cliente_cat_sat_moneda_id","com_cliente_cat_sat_uso_cfdi_id"));
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "cat_sat_forma_pago_id";
        $propiedades = array("label" => "Forma Pago");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "cat_sat_metodo_pago_id";
        $propiedades = array("label" => "Metodo Pago");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "cat_sat_moneda_id";
        $propiedades = array("label" => "Moneda");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "com_tipo_cambio_id";
        $propiedades = array("label" => "Tipo Cambio");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "cat_sat_uso_cfdi_id";
        $propiedades = array("label" => "Uso CFDI");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "cat_sat_tipo_de_comprobante_id";
        $propiedades = array("label" => "Tipo Comprobante");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "dp_calle_pertenece_id";
        $propiedades = array("label" => "Calle");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "cat_sat_regimen_fiscal_id";
        $propiedades = array("label" => "Regimen Fiscal");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "folio";
        $propiedades = array("place_holder" => "Folio");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "exportacion";
        $propiedades = array("place_holder" => "Exportación");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "serie";
        $propiedades = array("place_holder" => "Serie");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "subtotal";
        $propiedades = array("place_holder" => "Subtotal", "cols" => 4,"disabled" => true);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "descuento";
        $propiedades = array("place_holder" => "Descuento", "cols" => 4,"disabled" => true);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "impuestos_trasladados";
        $propiedades = array("place_holder" => "Imp. Trasladados", "disabled" => true);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "impuestos_retenidos";
        $propiedades = array("place_holder" => "Imp. Retenidos", "disabled" => true);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "total";
        $propiedades = array("place_holder" => "Total", "cols" => 4, "disabled" => true);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "fecha";
        $propiedades = array("place_holder" => "Fecha");
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        return $this->keys_selects;
    }

    private function init_modifica(): array|stdClass
    {
        $r_modifica =  parent::modifica(header: false);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al generar template',data:  $r_modifica);
        }

        $identificador = "fc_csd_id";
        $propiedades = array("id_selected" => $this->row_upd->fc_csd_id);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "com_sucursal_id";
        $propiedades = array("id_selected" => $this->row_upd->com_sucursal_id);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "cat_sat_forma_pago_id";
        $propiedades = array("id_selected" => $this->row_upd->cat_sat_forma_pago_id);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "cat_sat_metodo_pago_id";
        $propiedades = array("id_selected" => $this->row_upd->cat_sat_metodo_pago_id);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "cat_sat_moneda_id";
        $propiedades = array("id_selected" => $this->row_upd->cat_sat_moneda_id);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "com_tipo_cambio_id";
        $propiedades = array("id_selected" => $this->row_upd->com_tipo_cambio_id);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "cat_sat_uso_cfdi_id";
        $propiedades = array("id_selected" => $this->row_upd->cat_sat_uso_cfdi_id);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "cat_sat_tipo_de_comprobante_id";
        $propiedades = array("id_selected" => $this->row_upd->cat_sat_tipo_de_comprobante_id,
            "filtro" => array('cat_sat_tipo_de_comprobante.id' => $this->row_upd->cat_sat_uso_cfdi_id));
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $sub_total = (new fc_factura($this->link))->get_factura_sub_total(fc_factura_id: $this->registro_id);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener sub_total',data:  $sub_total);
        }

        $descuento = (new fc_factura($this->link))->get_factura_descuento(fc_factura_id: $this->registro_id);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener descuento',data:  $descuento);
        }

        $imp_trasladados = (new fc_factura($this->link))->get_factura_imp_trasladados(fc_factura_id:
            $this->registro_id);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener imp_trasladados',data:  $imp_trasladados);
        }

        $imp_retenidos = (new fc_factura($this->link))->get_factura_imp_retenidos(fc_factura_id:
            $this->registro_id);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener imp_retenidos',data:  $imp_retenidos);
        }

        $total = (new fc_factura($this->link))->get_factura_total(fc_factura_id: $this->registro_id);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener total factura',data:  $total);
        }

        $this->row_upd->subtotal = $sub_total;
        $this->row_upd->descuento = $descuento;
        $this->row_upd->impuestos_trasladados = $imp_trasladados;
        $this->row_upd->impuestos_retenidos = $imp_retenidos;
        $this->row_upd->total = $total;

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
        $partidas  = (new fc_partida($this->link))->partidas(fc_factura_id: $this->registro_id);
        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al obtener partidas', data: $partidas);
            print_r($error);
            die('Error');
        }

        foreach ($partidas->registros as $indice=>$partida){

            $data_producto_html = (new _html_factura())->data_producto(partida: $partida);
            if (errores::$error) {
                $error = $this->errores->error(mensaje: 'Error al generar html', data: $data_producto_html);
                print_r($error);
                die('Error');
            }
            $partidas->registros[$indice]['data_producto_html'] = $data_producto_html;

            $impuesto_traslado_html_completo = '';
            $aplica_traslado = false;
            foreach($partida['fc_traslado'] as $impuesto){
                $aplica_traslado = true;
                $impuesto_traslado_html = (new _html_factura())->data_impuesto(impuesto: $impuesto);
                if (errores::$error) {
                    $error = $this->errores->error(mensaje: 'Error al generar html', data: $impuesto_traslado_html);
                    print_r($error);
                    die('Error');
                }
                $impuesto_traslado_html_completo.=$impuesto_traslado_html;

            }
            $impuesto_traslado_html = '';
            if($aplica_traslado) {
                $impuesto_traslado_html = (new _html_factura())->tr_impuestos_html(
                    impuesto_traslado_html: $impuesto_traslado_html_completo, tag_tipo_impuesto: 'Traslados');
                if (errores::$error) {
                    $error = $this->errores->error(mensaje: 'Error al generar html', data: $impuesto_traslado_html);
                    print_r($error);
                    die('Error');
                }
            }


            $partidas->registros[$indice]['impuesto_traslado_html'] = $impuesto_traslado_html;

            $impuesto_retenido_html_completo = '';
            $aplica_retenido = false;
            foreach($partida['fc_retenido'] as $impuesto){
                $aplica_retenido = true;
                $impuesto_retenidos_html = (new _html_factura())->data_impuesto(impuesto: $impuesto);
                if (errores::$error) {
                    $error = $this->errores->error(mensaje: 'Error al generar html', data: $impuesto_retenidos_html);
                    print_r($error);
                    die('Error');
                }
                $impuesto_retenido_html_completo.=$impuesto_retenidos_html;

            }
            $impuesto_retenido_html = '';
            if($aplica_retenido) {
                $impuesto_retenido_html = (new _html_factura())->tr_impuestos_html(
                    impuesto_traslado_html: $impuesto_retenido_html_completo, tag_tipo_impuesto: 'Retenciones');
                if (errores::$error) {
                    $error = $this->errores->error(mensaje: 'Error al generar html', data: $impuesto_retenido_html);
                    print_r($error);
                    die('Error');
                }
            }


            $partidas->registros[$indice]['impuesto_retenido_html'] = $impuesto_retenido_html;
        }

        $this->partidas = $partidas;

        $base = $this->init_modifica();
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al maquetar datos',data:  $base,
                header: $header,ws:$ws);
        }

        $identificador = "com_producto_id";
        $propiedades = array("cols" => 12);
        $this->controlador_fc_partida->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $inputs = $this->nueva_partida_inicializa();
        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al inicializar partida', data: $inputs);
            print_r($error);
            die('Error');
        }

        $this->inputs->partidas = $inputs;

        //$this->inputs = (object) array_merge((array)$this->inputs, (array)$inputs);

        $t_head_producto = (new _html_factura())->thead_producto();
        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al generar html', data: $t_head_producto);
            print_r($error);
            die('Error');
        }
        $this->t_head_producto = $t_head_producto;




        return $base->template;
    }

    public function nueva_partida(bool $header, bool $ws = false): array|stdClass
    {
        $this->inputs = $this->nueva_partida_inicializa();
        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al inicializar partida', data: $this->inputs);
            print_r($error);
            die('Error');
        }

        return $this->inputs;
    }

    private function nueva_partida_inicializa(): array|stdClass{
        $this->controlador_fc_partida->modelo->campos_view['unidad'] = array('type' => 'inputs');
        $this->controlador_fc_partida->modelo->campos_view['impuesto'] = array('type' => 'inputs');

        $identificador = "unidad";
        $propiedades = array("place_holder" => "Unidad", "disabled" => true);
        $this->controlador_fc_partida->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "impuesto";
        $propiedades = array("place_holder" => "Objeto del Impuesto", "disabled" => true);
        $this->controlador_fc_partida->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $alta = $this->controlador_fc_partida->alta(header: false);
        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al generar template', data: $this->inputs);
            print_r($error);
            die('Error');
        }

        $identificador = "fc_factura_id";
        $propiedades = array("id_selected" => $this->registro_id, "disabled" => true,
            "filtro" => array('fc_factura.id' => $this->registro_id));
        $this->controlador_fc_partida->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "descripcion";
        $propiedades = array("cols" => 12);
        $this->controlador_fc_partida->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $inputs = $this->controlador_fc_partida->genera_inputs(
            keys_selects:  $this->controlador_fc_partida->keys_selects);
        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al generar inputs', data: $this->inputs);
            print_r($error);
            die('Error');
        }

        return $inputs;
    }

    public function productos(bool $header, bool $ws = false): array|stdClass
    {
        $datatables = $this->controlador_com_producto->init_datatable();
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al inicializar datatable', data: $datatables, header: $header, ws: $ws);
        }

        $datatables->columns["nueva_conf_traslado"]["titulo"] = "Acciones";
        $datatables->columns["nueva_conf_traslado"]["type"] = "button";
        $datatables->columns["nueva_conf_traslado"]["campos"] = array("nueva_conf_retenido");

        $table = $this->datatable_init(columns: $datatables->columns, filtro: $datatables->filtro,
            identificador: "#com_producto");
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al generar datatable', data: $table, header: $header, ws: $ws);
        }

        $alta = $this->controlador_com_producto->alta(header: false);
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al generar template', data: $alta, header: $header, ws: $ws);
        }

        $this->inputs = $this->controlador_com_producto->genera_inputs(
            keys_selects:  $this->controlador_com_producto->keys_selects);
        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al generar inputs', data: $this->inputs);
            print_r($error);
            die('Error');
        }

        return $this->inputs;
    }

    public function alta_partida_bd(bool $header, bool $ws = false){

        $this->link->beginTransaction();

        $siguiente_view = (new actions())->init_alta_bd(siguiente_view: "modifica");
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

        $factura = (new fc_factura($this->link))->get_factura(fc_factura_id: $this->registro_id);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener factura', data: $factura, header: $header, ws: $ws);
        }

        $registro = $_POST;
        $registro['fc_factura_id'] = $this->registro_id;

        $r_alta_partida_bd = (new fc_partida($this->link))->alta_registro(registro:$registro); // TODO: Change the autogenerated stub
        if(errores::$error){
            $this->link->rollBack();
            return $this->retorno_error(mensaje: 'Error al dar de alta partida',data:  $r_alta_partida_bd,
                header: $header,ws:$ws);
        }

        $this->link->commit();

        if($header){

            $retorno = (new actions())->retorno_alta_bd(link: $this->link, registro_id: $this->registro_id,
                seccion: $this->tabla, siguiente_view: "modifica");
            if(errores::$error){
                return $this->retorno_error(mensaje: 'Error al dar de alta registro', data: $r_alta_partida_bd,
                    header:  true, ws: $ws);
            }
            header('Location:'.$retorno);
            exit;
        }
        if($ws){
            header('Content-Type: application/json');
            echo json_encode($r_alta_partida_bd, JSON_THROW_ON_ERROR);
            exit;
        }

        return $r_alta_partida_bd;

    }

    /*
     * POR REVISAR
     */



    public function partidas(bool $header, bool $ws = false): array|stdClass
    {

        $r_modifica =  parent::modifica(header: false,aplica_form:  false); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al generar template',data:  $r_modifica);
        }

        $inputs = (new fc_factura_html(html: $this->html_base))->genera_inputs_fc_partida(controler:$this,
            link: $this->link);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al inicializar inputs',data:  $inputs, header: $header,ws:$ws);
        }

        $select = $this->select_fc_factura_id();
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar select datos',data:  $select,
                header: $header,ws:$ws);
        }


        $partidas = (new fc_partida($this->link))->partidas(fc_factura_id: $this->fc_factura_id);
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

    public function modifica_partida(bool $header, bool $ws = false): array|stdClass
    {

        $r_modifica =  parent::modifica(header: false,aplica_form:  false); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al generar template',data:  $r_modifica);
        }

        $registro = (new fc_partida($this->link))->registro(registro_id: $this->fc_partida_id,retorno_obj: true);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al generar template',data:  $registro);
        }

        $this->row_upd = $registro;

        $inputs = (new fc_factura_html(html: $this->html_base))->genera_inputs_fc_partida_modifica(controler:$this,
            link: $this->link);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al inicializar inputs',data:  $inputs, header: $header,ws:$ws);
        }

        $select = $this->select_fc_factura_id();
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar select datos',data:  $select,
                header: $header,ws:$ws);
        }

        $partidas = (new fc_partida($this->link))->partidas(fc_factura_id: $this->fc_factura_id);
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

    public function modifica_partida_bd(bool $header, bool $ws = false): array|stdClass
    {
        $siguiente_view = (new actions())->siguiente_view();
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener siguiente view sucursal',data:  $siguiente_view,
                header: $header,ws:$ws);
        }
        if(isset($_POST['guarda'])){
            unset($_POST['guarda']);
        }
        if(isset($_POST['btn_action_next'])){
            unset($_POST['btn_action_next']);
        }

        $fc_partida_modelo = new fc_partida($this->link);

        $fc_partida_ins = $_POST;
        $fc_partida_ins['fc_factura_id'] = $this->registro_id;

        $r_modifica_bd = $fc_partida_modelo->modifica_bd(registro:$fc_partida_ins, id: $this->fc_partida_id);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al modificar partida',data:  $r_modifica_bd,
                header: $header,ws:$ws);
        }

        if($header){
            $params = array('fc_partida_id'=>$this->fc_partida_id);
            $retorno = (new actions())->retorno_alta_bd(registro_id:$this->registro_id,seccion: $this->tabla,
                siguiente_view: $siguiente_view, params:$params );
            if(errores::$error){
                return $this->retorno_error(mensaje: 'Error al dar de alta registro', data: $r_modifica_bd,
                    header:  true, ws: $ws);
            }
            header('Location:'.$retorno);
            exit;
        }
        if($ws){
            header('Content-Type: application/json');
            echo json_encode($r_modifica_bd, JSON_THROW_ON_ERROR);
            exit;
        }
        return $r_modifica_bd;

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

    public function ve_partida(bool $header, bool $ws = false): array|stdClass
    {

        $keys = array('fc_partida_id','registro_id');
        $valida = $this->validacion->valida_ids(keys: $keys, registro: $_GET);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al validar GET',data:  $valida, header: $header,ws:$ws);
        }

        $r_modifica =  parent::modifica(header: false,aplica_form:  false); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al generar template',data:  $r_modifica);
        }

        $inputs = (new fc_factura_html(html: $this->html_base))->genera_inputs_fc_partida(controler:$this,
            link: $this->link);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al inicializar inputs',data:  $inputs, header: $header,ws:$ws);
        }

        $data_base = $this->base_data_partida(fc_partida_id: $_GET['fc_partida_id']);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar base',data:  $data_base->htmls,
                header: $header,ws:$ws);
        }

        $inputs_partida = $this->inputs_partida(html: $data_base->htmls->fc_partida,
            fc_partida: $data_base->data->data_partida->fc_partida);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar inputs partida',
                data:  $inputs_partida, header: $header,ws:$ws);
        }

        return $inputs_partida;
    }

    private function inputs_partida(fc_partida_html $html, stdClass $fc_partida,
                                    stdClass         $params = new stdClass()): array|stdClass{
        $partida_codigo_disabled = $params->partida_codigo->disabled ?? true;
        $fc_partida_codigo = $html->input_codigo(cols: 4,row_upd:  $fc_partida, value_vacio: false,
            disabled: $partida_codigo_disabled);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener partida_codigo select',data:  $fc_partida_codigo);
        }

        $partida_codigo_bis_disabled = $params->partida_codigo_bis->disabled ?? true;
        $fc_partida_codigo_bis = $html->input_codigo_bis(cols: 4,row_upd:  $fc_partida, value_vacio: false,
            disabled: $partida_codigo_bis_disabled);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener partida_codigo_bis',
                data:  $fc_partida_codigo_bis);
        }

        $partida_descripcion_disabled = $params->partida_descripcion->disabled ?? true;
        $fc_partida_descripcion = $html->input_descripcion(cols: 12,row_upd:  $fc_partida, value_vacio: false,
            disabled: $partida_descripcion_disabled);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener descripcion',data:  $fc_partida_descripcion);
        }

        $partida_cantidad_disabled = $params->partida_cantidad->disabled ?? true;
        $fc_partida_cantidad = $html->input_cantidad(cols: 4, row_upd:  $fc_partida,
            value_vacio: false, disabled: $partida_cantidad_disabled);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener $fc_partida_cantidad',data:  $fc_partida_cantidad);
        }

        $partida_valor_unitario_disabled = $params->partida_valor_unitario->disabled ?? true;
        $fc_partida_valor_unitario = $html->input_valor_unitario(cols: 4, row_upd:  $fc_partida,
            value_vacio: false, disabled: $partida_valor_unitario_disabled);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener $fc_partida_valor_unitario',data:  $fc_partida_valor_unitario);
        }

        $partida_descuento_disabled = $params->partida_descuento->disabled ?? true;
        $fc_partida_descuento = $html->input_descuento(cols: 4, row_upd:  $fc_partida,
            value_vacio: false, disabled: $partida_descuento_disabled);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener $fc_partida_descuento',data:  $fc_partida_descuento);
        }

        $factura_id_disabled = $params->fc_factura_id->disabled ?? true;
        $fc_factura_id = $html->input_id(cols: 12,row_upd:  $fc_partida, value_vacio: false,
            disabled: $factura_id_disabled,place_holder: 'ID factura');
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener sucursal_id select',data:  $fc_factura_id);
        }
        
        $com_producto_id_disabled = $params->com_producto_id->disabled ?? true;
        $com_producto_id = $html->input_id(cols: 4,row_upd:  $fc_partida, value_vacio: false,
            disabled: $com_producto_id_disabled,place_holder: 'ID Producto');
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener sucursal_id select',data:  $com_producto_id);
        }

        $this->inputs->fc_factura_id = $fc_factura_id;
        $this->inputs->com_producto_id = $com_producto_id;
        $this->inputs->codigo = $fc_partida_codigo;
        $this->inputs->codigo_bis = $fc_partida_codigo_bis;
        $this->inputs->descripcion = $fc_partida_descripcion;
        $this->inputs->cantidad = $fc_partida_cantidad;
        $this->inputs->valor_unitario = $fc_partida_valor_unitario;
        $this->inputs->descuento = $fc_partida_descuento;

        return $this->inputs;
    }

    private function base_data_partida(int $fc_partida_id): array|stdClass
    {
        $data = $this->data_partida(fc_partida_id: $fc_partida_id);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al cargar datos de partida', data: $data);
        }

        $htmls = $this->htmls_partida();
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener htmls',data:  $htmls);
        }
        $data_return = new stdClass();
        $data_return->data = $data;
        $data_return->htmls = $htmls;
        return $data_return;
    }


    private function htmls_partida(): stdClass
    {
        $fc_partida_html = (new fc_partida_html(html: $this->html_base));

        $data = new stdClass();
        $data->fc_partida = $fc_partida_html;
        
        return $data;
    }

    private function data_partida(int $fc_partida_id): array|stdClass
    {
        $data_partida = (new fc_partida($this->link))->data_partida_obj(fc_partida_id: $fc_partida_id);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener partida',data:  $data_partida);
        }

        $data = new stdClass();
        $data->data_partida = $data_partida;

        return $data;
    }


}
