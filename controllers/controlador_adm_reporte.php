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
use gamboamartin\facturacion\models\fc_factura;
use gamboamartin\plugins\exportador;
use html\adm_reporte_html;
use stdClass;

class controlador_adm_reporte extends \gamboamartin\acl\controllers\controlador_adm_reporte {
    public string $filtros = '';
    public string $link_ejecuta_reporte = '';
    public string $link_exportar_xls ='';
    final public function ejecuta(bool $header, bool $ws = false){
        $descripcion = $this->adm_reporte['adm_reporte_descripcion'];

        $link_ejecuta_reporte = $this->obj_link->link_con_id(accion: 'ejecuta_reporte',link: $this->link,
            registro_id:  $this->registro_id,seccion:  $this->tabla);

        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar link',data:  $link_ejecuta_reporte, header: $header, ws: $ws);
        }

        $this->link_ejecuta_reporte = $link_ejecuta_reporte;



        if($descripcion === 'Facturas'){
            $hoy = date('Y-m-d');
            $fecha_mes_inicial = date('Y-m-01');
            $fecha_inicial = (new adm_reporte_html(html: $this->html_base))->input_fecha(cols: 6,
                row_upd: new stdClass(), value_vacio: false, name: 'fecha_inicial', place_holder: 'Fecha Inicial',
                value: $fecha_mes_inicial);

            if(errores::$error){
                return $this->retorno_error(mensaje: 'Error al generar input',data:  $fecha_inicial, header: $header, ws: $ws);
            }

            $this->filtros = $fecha_inicial;

            $fecha_final = (new adm_reporte_html(html: $this->html_base))->input_fecha(cols: 6,
                row_upd: new stdClass(), value_vacio: false, name: 'fecha_final', place_holder: 'Fecha Final',
                value: $hoy);

            if(errores::$error){
                return $this->retorno_error(mensaje: 'Error al generar input',data:  $fecha_final, header: $header, ws: $ws);
            }

            $this->filtros .= $fecha_final;
        }

    }

    final public function ejecuta_reporte(bool $header, bool $ws = false){
        $adm_reporte_descripcion = $this->adm_reporte['adm_reporte_descripcion'];

        $link_exportar_xls = $this->obj_link->link_con_id(accion: 'exportar_xls',link: $this->link,
            registro_id:  $this->registro_id,seccion:  $this->tabla);

        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar link',data:  $link_exportar_xls, header: $header, ws: $ws);
        }

        $this->link_exportar_xls = $link_exportar_xls;

        $registros = array();

        if($adm_reporte_descripcion === 'Facturas'){
            $filtro_rango = array();
            if(isset($_POST['fecha_inicial'])){
                $filtro_rango['fc_factura.fecha']['valor1'] = $_POST['fecha_inicial'];
                $filtro_rango['fc_factura.fecha']['valor2'] = $_POST['fecha_final'];
            }

            $r_fc_factura = (new fc_factura(link: $this->link))->filtro_and(filtro_rango: $filtro_rango);
            if(errores::$error){
                return $this->retorno_error(mensaje: 'Error al obtener fc_facturas',data:  $r_fc_factura, header: $header, ws: $ws);
            }

            $registros = $r_fc_factura->registros;

        }

        $ths_html = $this->genera_ths_html(adm_reporte_descripcion: $adm_reporte_descripcion);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener ths_html',data:  $ths_html, header: $header, ws: $ws);
        }

        $trs_html = $this->genera_trs_html(adm_reporte_descripcion: $adm_reporte_descripcion,registros:  $registros);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener trs_html',data:  $trs_html, header: $header, ws: $ws);
        }

        $this->ths = $ths_html;
        $this->trs = $trs_html;

    }

    final public function exportar_xls(bool $header, bool $ws = false){
        $adm_reporte_descripcion = $this->adm_reporte['adm_reporte_descripcion'];

        $nombre_hojas = array();
        $keys_hojas = array();
        if($adm_reporte_descripcion === 'Facturas'){
            $filtro_rango = array();
            if(isset($_POST['fecha_inicial'])){
                $filtro_rango['fc_factura.fecha']['valor1'] = $_POST['fecha_inicial'];
                $filtro_rango['fc_factura.fecha']['valor2'] = $_POST['fecha_final'];
            }

            $r_fc_factura = (new fc_factura(link: $this->link))->filtro_and(filtro_rango: $filtro_rango);
            if(errores::$error){
                return $this->retorno_error(mensaje: 'Error al obtener fc_facturas',data:  $r_fc_factura, header: $header, ws: $ws);
            }

            $registros = $r_fc_factura->registros;


            $ths = $this->ths_array(adm_reporte_descripcion: $adm_reporte_descripcion);
            if(errores::$error){
                return $this->errores->error(mensaje: 'Error al obtener ths',data:  $ths);
            }
            $keys = array();

            foreach ($ths as $data_th){
                $keys[] = $data_th['campo'];
            }

            $nombre_hojas[] = 'Facturas';
            $keys_hojas['Facturas'] = new stdClass();
            $keys_hojas['Facturas']->keys = $keys;
            $keys_hojas['Facturas']->registros = $registros;

        }

        $xls = (new exportador())->genera_xls(header: $header,name:  'Facturas',nombre_hojas:  $nombre_hojas,
            keys_hojas: $keys_hojas, path_base: $this->path_base);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener xls',data:  $xls, header: $header, ws: $ws);
        }

    }

    private function genera_ths_html(string $adm_reporte_descripcion): array|string
    {
        $ths = $this->ths_array(adm_reporte_descripcion: $adm_reporte_descripcion);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener ths',data:  $ths);
        }

        $ths_html = $this->ths_html(ths: $ths);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener ths_html',data:  $ths_html);
        }
        return $ths_html;
    }

    private function genera_trs_html(string $adm_reporte_descripcion, array $registros): array|string
    {
        $ths = $this->ths_array(adm_reporte_descripcion: $adm_reporte_descripcion);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener ths',data:  $ths);
        }

        $trs_html = '';
        foreach ($registros as $registro){
            $trs_html = $this->integra_trs_html(registro: $registro,ths:  $ths,trs_html:  $trs_html);
            if(errores::$error){
                return $this->errores->error(mensaje: 'Error al obtener trs_html',data:  $trs_html);
            }
        }
        return $trs_html;
    }

    private function integra_td(array $data_ths, array $registro, string $tds_html): array|string
    {
        $key_registro = $data_ths['campo'];
        $td = $this->td(key_registro: $key_registro,registro:  $registro);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener td',data:  $td);
        }
        $tds_html.="$td";
        return $tds_html;
    }



    private function td(string $key_registro, array $registro): string
    {
        return "<td>$registro[$key_registro]</td>";
    }

    private function tds_html(array $registro, array $ths): array|string
    {
        $tds_html = '';
        foreach ($ths as $data_ths){
            $tds_html = $this->integra_td(data_ths: $data_ths,registro:  $registro,tds_html:  $tds_html);
            if(errores::$error){
                return $this->errores->error(mensaje: 'Error al obtener td',data:  $tds_html);
            }
        }
        return $tds_html;
    }

    private function integra_trs_html(array $registro, array $ths, string $trs_html): array|string
    {
        $tds_html = $this->tds_html(registro: $registro,ths:  $ths);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener tds_html',data:  $tds_html);
        }
        $trs_html.="<tr>$tds_html</tr>";
        return $trs_html;
    }

    private function th(string $etiqueta): string
    {
        return "<th>$etiqueta</th>";
    }

    private function ths_array(string $adm_reporte_descripcion): array
    {
        $ths = array();

        if($adm_reporte_descripcion === 'Facturas'){
            $ths[] = array('etiqueta'=>'Folio', 'campo'=>'fc_factura_folio');
            $ths[] = array('etiqueta'=>'UUID', 'campo'=>'fc_factura_uuid');
            $ths[] = array('etiqueta'=>'Cliente', 'campo'=>'com_cliente_razon_social');
            $ths[] = array('etiqueta'=>'Sub Total', 'campo'=>'fc_factura_sub_total_base');
            $ths[] = array('etiqueta'=>'Descuento', 'campo'=>'fc_factura_total_descuento');
            $ths[] = array('etiqueta'=>'Traslados', 'campo'=>'fc_factura_total_traslados');
            $ths[] = array('etiqueta'=>'Retenciones', 'campo'=>'fc_factura_total_retenciones');
            $ths[] = array('etiqueta'=>'Total', 'campo'=>'fc_factura_total');
            $ths[] = array('etiqueta'=>'Fecha', 'campo'=>'fc_factura_fecha');
            $ths[] = array('etiqueta'=>'Forma de Pago', 'campo'=>'cat_sat_forma_pago_descripcion');
            $ths[] = array('etiqueta'=>'Metodo de Pago', 'campo'=>'cat_sat_metodo_pago_descripcion');
            $ths[] = array('etiqueta'=>'Moneda', 'campo'=>'cat_sat_moneda_codigo');
            $ths[] = array('etiqueta'=>'Tipo Cambio', 'campo'=>'com_tipo_cambio_monto');
            $ths[] = array('etiqueta'=>'Uso CFDI', 'campo'=>'cat_sat_uso_cfdi_descripcion');
            $ths[] = array('etiqueta'=>'Exportacion', 'campo'=>'fc_factura_exportacion');
        }
        return $ths;
    }

    private function ths_html(array $ths): array|string
    {
        $ths_html = '';
        foreach ($ths as $th_data){
            $etiqueta = $th_data['etiqueta'];

            $th = $this->th(etiqueta: $etiqueta);
            if(errores::$error){
                return $this->errores->error(mensaje: 'Error al obtener th',data:  $th);
            }
            $ths_html.=$th;
        }
        return $ths_html;
    }
}
