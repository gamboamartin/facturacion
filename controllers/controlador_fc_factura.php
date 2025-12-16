<?php
/**
 * @author Martin Gamboa Vazquez
 * @version 1.0.0
 * @created 2022-05-14
 * @final En proceso
 *
 */

namespace gamboamartin\facturacion\controllers;

use gamboamartin\controllers\_controlador_adm_reporte\_filtros;
use gamboamartin\controllers\_controlador_adm_reporte\_table;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\html\fc_factura_html;
use gamboamartin\facturacion\models\fc_cancelacion;
use gamboamartin\facturacion\models\fc_cfdi_sellado;
use gamboamartin\facturacion\models\fc_cuenta_predial;
use gamboamartin\facturacion\models\fc_email;
use gamboamartin\facturacion\models\fc_factura;
use gamboamartin\facturacion\models\fc_factura_documento;
use gamboamartin\facturacion\models\fc_factura_etapa;
use gamboamartin\facturacion\models\fc_factura_relacionada;
use gamboamartin\facturacion\models\fc_notificacion;
use gamboamartin\facturacion\models\fc_partida;
use gamboamartin\facturacion\models\fc_relacion;
use gamboamartin\facturacion\models\fc_retenido;
use gamboamartin\facturacion\models\fc_traslado;
use gamboamartin\facturacion\models\fc_uuid_fc;
use gamboamartin\plugins\exportador;
use gamboamartin\template\html;
use PDO;
use stdClass;

class controlador_fc_factura extends _base_system_fc
{

    public array|stdClass $keys_selects = array();
    public controlador_com_producto $controlador_com_producto;

    public string $rfc = '';
    public string $razon_social = '';

    public string $link_fc_factura_nueva_partida = '';

    public string $link_fc_email_alta_bd = '';

    public string $link_com_producto = '';

    public string $link_factura_cancela = '';
    public string $link_factura_genera_xml = '';
    public string $link_factura_timbra_xml = '';

    public string $link_exportar_xls ='';
    public string $button_fc_factura_correo = '';

    public int $fc_factura_id = -1;
    public int $fc_partida_id = -1;


    public array $facturas_cliente = array();

    public function __construct(PDO      $link, html $html = new \gamboamartin\template_1\html(),
                                stdClass $paths_conf = new stdClass())
    {
        $modelo = new fc_factura(link: $link);
        $this->modelo = $modelo;
        $this->cat_sat_tipo_de_comprobante = 'Ingreso';
        $html_ = new fc_factura_html(html: $html);
        $this->html_fc = $html_;

        parent::__construct(html_: $html_, link: $link, modelo: $modelo, paths_conf: $paths_conf);


        $this->data_selected_alta['cat_sat_forma_pago_id']['id'] = -1;
        $this->data_selected_alta['cat_sat_forma_pago_id']['filtro'] = array();


        $this->data_selected_alta['cat_sat_metodo_pago_id']['id'] = -1;
        $this->data_selected_alta['cat_sat_metodo_pago_id']['filtro'] = array();

        $this->data_selected_alta['cat_sat_moneda_id']['id'] = -1;
        $this->data_selected_alta['cat_sat_moneda_id']['filtro'] = array();

        $this->data_selected_alta['com_tipo_cambio_id']['id'] = -1;
        $this->data_selected_alta['com_tipo_cambio_id']['filtro'] = array();

        $this->data_selected_alta['cat_sat_uso_cfdi_id']['id'] = -1;
        $this->data_selected_alta['cat_sat_uso_cfdi_id']['filtro'] = array();

        $init_ctl = (new _fc_base())->init_base_fc(controler: $this, name_modelo_email: 'fc_email');
        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al inicializar', data: $init_ctl);
            print_r($error);
            die('Error');
        }


        if (isset($_GET['fc_partida_id'])) {
            $this->fc_partida_id = $_GET['fc_partida_id'];
        }

        $this->lista_get_data = true;


        $this->verifica_parents_alta = true;

        $thead_relacion = $this->thead_relacion();
        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al generar html', data: $thead_relacion);
            print_r($error);
            die('Error');
        }

        $link_exportar_xls = $this->obj_link->link_con_id(accion: 'exportar_xls',link: $this->link,
            registro_id:  $this->registro_id,seccion:  $this->tabla);
        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al generar link', data: $link_exportar_xls);
            print_r($error);
            die('Error');
        }

        $this->link_exportar_xls = $link_exportar_xls;
    }

    public function actualiza_porcentaje_comision(bool $header, bool $ws = false)
    {
        $fc_factura_id = $this->registro_id;
        $nuevo_porcentaje_comision = $this->modelo->actualiza_porcentaje_comision(fc_factura_id: $fc_factura_id);
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error en actualiza_porcentaje_comision',
                data: $nuevo_porcentaje_comision,
                header: $header,
                ws: $ws
            );
        }

        $_SESSION['exito'][]['mensaje'] = "fc_factura_id {$fc_factura_id}. porcentaje comision cliente ";
        $_SESSION['exito'][]['mensaje'] .= "actualizado correctamente al {$nuevo_porcentaje_comision}%";
        $link = "index.php?seccion=fc_factura&accion=lista&adm_menu_id=44";
        $link .= "&session_id={$_GET['session_id']}";
        header("Location: " . $link);
        exit;

    }

    public function adjunta(bool $header, bool $ws = false): array|stdClass
    {
        $this->modelo_entidad = new fc_factura(link: $this->link);
        $this->modelo_documento = new fc_factura_documento(link: $this->link);

        $r_template = parent::adjunta(header: $header, ws: $ws); // TODO: Change the autogenerated stub
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al generar template', data: $r_template, header: $header, ws: $ws);
        }

        return $r_template;
    }

    public function adjunta_bd(bool $header, bool $ws = false): array|stdClass
    {
        $this->modelo_entidad = new fc_factura(link: $this->link);
        $this->modelo_documento = new fc_factura_documento(link: $this->link);

        $r_template = parent::adjunta_bd(header: $header, ws: $ws); // TODO: Change the autogenerated stub
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al generar template', data: $r_template, header: $header, ws: $ws);
        }

        return $r_template;
    }

    public function ajusta_hora(bool $header, bool $ws = false): array|stdClass
    {
        $this->ctl_partida = new controlador_fc_partida(link: $this->link);
        $this->modelo_entidad = new fc_factura(link: $this->link);
        $this->modelo_partida = new fc_partida(link: $this->link);
        $this->modelo_retencion = new fc_retenido(link: $this->link);
        $this->modelo_traslado = new fc_traslado(link: $this->link);

        $result = parent::ajusta_hora(header: $header, ws: $ws); // TODO: Change the autogenerated stub
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al generar template', data: $result, header: $header, ws: $ws);
        }
        return $result;

    }

    public function alta(bool $header, bool $ws = false): array|string
    {

        $this->modelo_entidad = $this->modelo;

        $r_alta = parent::alta(header: $header, ws: $ws); // TODO: Change the autogenerated stub
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al generar template', data: $r_alta, header: $header, ws: $ws);
        }

        return $r_alta;
    }

    public function alta_partida_bd(bool $header, bool $ws = false)
    {
        $this->modelo_entidad = $this->modelo;
        $this->modelo_partida = new fc_partida(link: $this->link);
        $this->modelo_predial = new fc_cuenta_predial(link: $this->link);
        $this->modelo_relacion = new fc_relacion(link: $this->link);
        $this->modelo_relacionada = new fc_factura_relacionada(link: $this->link);
        $this->modelo_retencion = new fc_retenido(link: $this->link);
        $this->modelo_traslado = new fc_traslado(link: $this->link);
        $this->modelo_uuid_ext = new fc_uuid_fc(link: $this->link);


        $r_alta_partida = parent::alta_partida_bd($header, $ws); // TODO: Change the autogenerated stub
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al insertar', data: $r_alta_partida, header: $header, ws: $ws);
        }

        return $r_alta_partida;

    }

    public function cancela(bool $header, bool $ws = false)
    {
        $this->modelo_entidad = new fc_factura(link: $this->link);

        $r_template = parent::cancela($header, $ws); // TODO: Change the autogenerated stub
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al generar template', data: $r_template, header: $header, ws: $ws);
        }

        return $r_template;
    }

    public function cancela_bd(bool $header, bool $ws = false): array|stdClass
    {
        $this->modelo_entidad = new fc_factura(link: $this->link);
        $this->modelo_cancelacion = new fc_cancelacion(link: $this->link);
        $this->modelo_etapa = new fc_factura_etapa(link: $this->link);

        $r_cancela_bd = parent::cancela_bd($header, $ws); // TODO: Change the autogenerated stub
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al cancelar', data: $r_cancela_bd, header: $header, ws: $ws);
        }

        return $r_cancela_bd;
    }

    public function correo(bool $header, bool $ws = false): array|stdClass
    {
        $this->modelo_entidad = new fc_factura(link: $this->link);
        $this->modelo_email = new fc_email(link: $this->link);

        $r_template = parent::correo($header, $ws); // TODO: Change the autogenerated stub
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al generar template', data: $r_template, header: $header, ws: $ws);
        }

        return $r_template;
    }


    public function descarga_xml(bool $header, bool $ws = false)
    {
        $this->modelo_documento = new fc_factura_documento(link: $this->link);

        $r_template = parent::descarga_xml($header, $ws); // TODO: Change the autogenerated stub
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al generar template', data: $r_template, header: $header, ws: $ws);
        }

        return $r_template;
    }

    public function descargar_por_separado(bool $header, bool $ws = false): array|stdClass
    {
        $genera_pdf = $this->obj_link->link_con_id('genera_pdf', $this->link, $this->registro_id, $this->seccion);
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error $genera_pdf', data: $genera_pdf, header: $header, ws: $ws);
        }

        $descarga_xml = $this->obj_link->link_con_id('descarga_xml', $this->link, $this->registro_id, $this->seccion);
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error $descarga_xml', data: $descarga_xml, header: $header, ws: $ws);
        }

        $this->registro = new stdClass();
        $this->registro->genera_pdf = $genera_pdf;
        $this->registro->descarga_xml = $descarga_xml;

        return $this->registro;

    }


    public function duplica(bool $header, bool $ws = false)
    {
        $this->modelo_entidad = new fc_factura(link: $this->link);
        $this->modelo_partida = new fc_partida(link: $this->link);
        $this->modelo_retencion = new fc_retenido(link: $this->link);
        $this->modelo_traslado = new fc_traslado(link: $this->link);


        $r_duplica = parent::duplica(header: $header, ws: $ws); // TODO: Change the autogenerated stub
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al r_duplica', data: $r_duplica, header: $header, ws: $ws);
        }

        return $r_duplica;
    }

    public function elimina_sin_restriccion(bool $header, bool $ws = false)
    {

        $this->modelo_entidad = new fc_factura(link: $this->link);
        $this->modelo_entidad->valida_restriccion = false;


        $r_elimina_bd = parent::elimina_sin_restriccion(header: $header, ws: $ws); // TODO: Change the autogenerated stub
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al r_elimina_bd', data: $r_elimina_bd, header: $header, ws: $ws);
        }

        return $r_elimina_bd;

    }

    public function envia_cfdi(bool $header, bool $ws = false)
    {
        $this->modelo_email = new fc_email(link: $this->link);
        $this->modelo_notificacion = new fc_notificacion(link: $this->link);
        $this->modelo_etapa = new fc_factura_etapa(link: $this->link);
        $this->modelo_documento = new fc_factura_documento(link: $this->link);

        $r_envia = parent::envia_cfdi($header, $ws); // TODO: Change the autogenerated stub
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al enviar', data: $r_envia, header: $header, ws: $ws);
        }

        return $r_envia;

    }

    final public function es_plantilla(bool $header, bool $ws): array|stdClass
    {
        $this->modelo_entidad = new fc_factura(link: $this->link);

        $r_upd = parent::es_plantilla(header: $header, ws: $ws); // TODO: Change the autogenerated stub
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al actualizar', data: $r_upd, header: $header, ws: $ws);
        }
        return $r_upd;
    }

    public function exportar_documentos(bool $header, bool $ws = false)
    {

        $this->modelo_traslado = new fc_traslado(link: $this->link);
        $this->modelo_relacion = new fc_relacion(link: $this->link);
        $this->modelo_relacionada = new fc_factura_relacionada(link: $this->link);
        $this->modelo_retencion = new fc_retenido(link: $this->link);
        $this->modelo_predial = new fc_cuenta_predial(link: $this->link);
        $this->modelo_partida = new fc_partida(link: $this->link);
        $this->modelo_entidad = new fc_factura(link: $this->link);
        $this->modelo_documento = new fc_factura_documento(link: $this->link);
        $this->modelo_sello = new fc_cfdi_sellado(link: $this->link);
        $this->modelo_uuid_ext = new fc_uuid_fc(link: $this->link);

        $r_template = parent::exportar_documentos(header: $header, ws: $ws); // TODO: Change the autogenerated stub
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al insertar relacion', data: $r_template, header: $header, ws: $ws);
        }
        return $r_template;
    }

    public function fc_factura_relacionada_alta_bd(bool $header, bool $ws = false)
    {

        $this->modelo_relacion = new fc_relacion(link: $this->link);
        $this->modelo_entidad = new fc_factura(link: $this->link);
        $this->modelo_relacionada = new fc_factura_relacionada(link: $this->link);
        $this->modelo_uuid_ext = new fc_uuid_fc(link: $this->link);

        $r_alta = parent::fc_factura_relacionada_alta_bd($header, $ws); // TODO: Change the autogenerated stub
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al insertar relacion', data: $r_alta, header: $header, ws: $ws);
        }
        return $r_alta;

    }

    public function fc_relacion_alta_bd(bool $header, bool $ws = false)
    {
        $this->modelo_entidad = new fc_factura(link: $this->link);
        $this->modelo_relacion = new fc_relacion(link: $this->link);

        $r_alta = parent::fc_relacion_alta_bd($header, $ws); // TODO: Change the autogenerated stub
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al insertar relacion', data: $r_alta, header: $header, ws: $ws);
        }
        return $r_alta;
    }

    public function genera_pdf(bool $header, bool $ws = false,  bool $descarga = true, bool $guarda = false)
    {
        $this->modelo_documento = (new fc_factura_documento(link: $this->link));
        $this->modelo_entidad = $this->modelo;
        $this->modelo_partida = (new fc_partida(link: $this->link));
        $this->modelo_predial = (new fc_cuenta_predial(link: $this->link));
        $this->modelo_relacion = (new fc_relacion(link: $this->link));
        $this->modelo_relacionada = (new fc_factura_relacionada(link: $this->link));
        $this->modelo_retencion = (new fc_retenido(link: $this->link));
        $this->modelo_sello = (new fc_cfdi_sellado(link: $this->link));
        $this->modelo_traslado = (new fc_traslado(link: $this->link));
        $this->modelo_uuid_ext = (new fc_uuid_fc(link: $this->link));


        $r_genera = parent::genera_pdf($header, $ws, $descarga, $guarda); // TODO: Change the autogenerated stub
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al generar pdf', data: $r_genera, header: $header, ws: $ws);
        }
        return $r_genera;
    }

    public function genera_xml(bool $header, bool $ws = false)
    {
        $this->modelo_entidad = new fc_factura(link: $this->link);
        $this->modelo_documento = new fc_factura_documento(link: $this->link);
        $this->modelo_etapa = new fc_factura_etapa(link: $this->link);
        $this->modelo_partida = new fc_partida(link: $this->link);
        $this->modelo_predial = new fc_cuenta_predial(link: $this->link);
        $this->modelo_relacion = new fc_relacion(link: $this->link);
        $this->modelo_relacionada = new fc_factura_relacionada(link: $this->link);
        $this->modelo_retencion = new fc_retenido(link: $this->link);
        $this->modelo_traslado = new fc_traslado(link: $this->link);
        $this->modelo_uuid_ext = new fc_uuid_fc(link: $this->link);


        $r_xml = parent::genera_xml($header, $ws); // TODO: Change the autogenerated stub
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al generar xml', data: $r_xml, header: $header, ws: $ws);
        }
        return $r_xml;
    }

    public function inserta_factura_plantilla_bd(bool $header, bool $ws = false): array|string
    {

        $this->modelo_entidad = $this->modelo;
        $this->modelo_partida = (new fc_partida(link: $this->link));
        $this->modelo_traslado = (new fc_traslado(link: $this->link));
        $this->modelo_retencion = (new fc_retenido(link: $this->link));

        $r_alta = parent::inserta_factura_plantilla_bd(header: $header, ws: $ws); // TODO: Change the autogenerated stub
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al generar template', data: $r_alta, header: $header, ws: $ws);
        }

        return $r_alta;
    }

    public function modifica(bool $header, bool $ws = false): array|stdClass
    {
        $this->ctl_partida = new controlador_fc_partida(link: $this->link);
        $this->modelo_entidad = $this->modelo;
        $this->modelo_partida = (new fc_partida(link: $this->link));
        $this->modelo_retencion = (new fc_retenido(link: $this->link));
        $this->modelo_traslado = (new fc_traslado(link: $this->link));
        $this->modelo_email = (new fc_email(link: $this->link));


        $r_modifica = parent::modifica(header: $header, ws: $ws); // TODO: Change the autogenerated stub
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al generar template', data: $r_modifica, header: $header, ws: $ws);
        }

        return $r_modifica;
    }

    public function modifica_partida_bd(bool $header, bool $ws = false): array|stdClass
    {

        $this->modelo_partida = new fc_partida(link: $this->link);
        $this->modelo_entidad = new fc_factura(link: $this->link);
        $this->modelo_predial = new fc_cuenta_predial(link: $this->link);

        $r_modifica = parent::modifica_partida_bd(header: $header, ws: $ws); // TODO: Change the autogenerated stub
        if (errores::$error) {

            return $this->retorno_error(mensaje: 'Error al modificar', data: $r_modifica, header: $header, ws: $ws);
        }

        return $r_modifica;

    }

    public function relaciones(bool $header, bool $ws = false)
    {
        $this->modelo_partida = new fc_partida(link: $this->link);
        $this->modelo_entidad = new fc_factura(link: $this->link);
        $this->modelo_retencion = new fc_retenido(link: $this->link);
        $this->modelo_traslado = new fc_traslado(link: $this->link);
        $this->modelo_relacion = new fc_relacion(link: $this->link);
        $this->modelo_relacionada = new fc_factura_relacionada(link: $this->link);
        $this->modelo_etapa = new fc_factura_etapa(link: $this->link);
        $this->modelo_uuid_ext = new fc_uuid_fc(link: $this->link);

        $r_modifica = parent::relaciones($header, $ws); // TODO: Change the autogenerated stub
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al generar template', data: $r_modifica, header: $header, ws: $ws);
        }

        return $r_modifica;
    }

    public function timbra_xml(bool $header, bool $ws = false): array|stdClass
    {

        $this->modelo_entidad = $this->modelo;
        $this->modelo_documento = new fc_factura_documento(link: $this->link);
        $this->modelo_etapa = new fc_factura_etapa(link: $this->link);
        $this->modelo_partida = new fc_partida(link: $this->link);
        $this->modelo_predial = new fc_cuenta_predial(link: $this->link);
        $this->modelo_relacion = new fc_relacion(link: $this->link);
        $this->modelo_relacionada = new fc_factura_relacionada(link: $this->link);
        $this->modelo_retencion = new fc_retenido(link: $this->link);
        $this->modelo_sello = new fc_cfdi_sellado(link: $this->link);
        $this->modelo_traslado = new fc_traslado(link: $this->link);
        $this->modelo_uuid_ext = new fc_uuid_fc(link: $this->link);

        $r_timbra = parent::timbra_xml(header: $header, ws: $ws); // TODO: Change the autogenerated stub
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al timbrar', data: $r_timbra, header: $header,
                ws: $ws, class: __CLASS__, file: __FILE__, function: __FUNCTION__, line: __LINE__);
        }

        return $r_timbra;

    }

    public function verifica_cancelacion(bool $header, bool $ws = false)
    {

        $this->modelo_partida = new fc_partida(link: $this->link);
        $this->modelo_entidad = new fc_factura(link: $this->link);
        $this->modelo_retencion = new fc_retenido(link: $this->link);
        $this->modelo_traslado = new fc_traslado(link: $this->link);
        $this->modelo_etapa = new fc_factura_etapa(link: $this->link);


        $r_verifica = parent::verifica_cancelacion($header, $ws); // TODO: Change the autogenerated stub
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al timbrar', data: $r_verifica, header: $header, ws: $ws);
        }


        return $r_verifica;
    }

    public function exportar_xls(bool $header, bool $ws = false)
    {
        $nombre_hojas = array('Facturas');
        $keys_hojas = array();

        $registros = $this->result_fc_rpt();
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al obtener fc_facturas', data: $registros, header: $header, ws: $ws);
        }

        $ths = (new _table())->ths_array(adm_reporte_descripcion: 'Facturas');
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al obtener ths', data: $ths);
        }

        $keys = array();
        foreach ($ths as $data_th) {
            $keys[] = $data_th['campo'];
        }

        $keys_hojas['Facturas'] = new stdClass();
        $keys_hojas['Facturas']->keys = $keys;
        $keys_hojas['Facturas']->registros = $registros->registros;


        $moneda = array();
        $totales_hoja = new stdClass();
        $totales_hoja->Facturas = (array)$registros->totales;
        $xls = (new exportador())->genera_xls(header: $header, name: 'Facturas', nombre_hojas: $nombre_hojas,
            keys_hojas: $keys_hojas, path_base: $this->path_base, moneda: $moneda, totales_hoja: $totales_hoja);
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al obtener xls', data: $xls, header: $header, ws: $ws);
        }

    }

    private function result_fc_rpt(): array|stdClass
    {
        $result = new stdClass();
        $result->registros = array();
        $result->totales = array();

        $table = 'fc_factura';

        $filtro_rango = (new _filtros())->filtro_rango(table: $table);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al obtener filtro_rango', data: $filtro_rango);
        }

        $filtro_text = (new _filtros())->filtro_texto(table: $table);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al obtener filtro_texto', data: $filtro_text);
        }

        $columnas_totales[] = 'fc_factura_sub_total_base';
        $columnas_totales[] = 'fc_factura_total_descuento';
        $columnas_totales[] = 'fc_factura_total_traslados';
        $columnas_totales[] = 'fc_factura_total_retenciones';
        $columnas_totales[] = 'fc_factura_total';
        $result = (new fc_factura(link: $this->link))->filtro_and(
            columnas_totales: $columnas_totales, filtro: $filtro_text, filtro_rango: $filtro_rango);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al obtener fc_facturas', data: $result);
        }

        return $result;
    }

    public function valida_cep(bool $header, bool $ws = false)
    {
        $respuesta = $this->modelo->valida_cep(fecha: '07-01-2025',
            clave_rastreo: 'BNET01002501070038420534',
            insitucion_emisora: '40012', // CLIENTE
            insitucion_receptora: '40072', // EMPRESA
            cuenta: '072534012102254615',
            monto: '43389.21',
            receptor_participante: '1');
        if (errores::$error) {
            return $this->retorno_error(mensaje: 'Error al ejecutar la validación CEP', data: $respuesta, header: $header, ws: $ws);
        }

        return $respuesta;
    }


}
