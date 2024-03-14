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
use gamboamartin\facturacion\models\fc_complemento_pago;
use gamboamartin\facturacion\models\fc_factura;
use gamboamartin\facturacion\models\fc_nota_credito;
use gamboamartin\plugins\exportador;
use html\adm_reporte_html;
use stdClass;

class controlador_adm_reporte extends \gamboamartin\acl\controllers\controlador_adm_reporte {
    public string $filtros = '';
    public string $link_ejecuta_reporte = '';
    public string $link_exportar_xls ='';

    private function asigna_data_fechas(): array|stdClass
    {
        $data = $this->data_fechas();
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error inicializar filtros',data:  $data);
        }

        $init = $this->init_data_post_fecha(data: $data);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error inicializar POST',data:  $init);
        }
        return $init;

    }
    private function data_fechas(): array|stdClass
    {
        $data = $this->init_filtro_fecha();
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error inicializar filtros',data:  $data);
        }

        $data = $this->init_fecha_inicial(data: $data);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error inicializar filtros',data:  $data);
        }
        $data = $this->init_fecha_final(data: $data);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error inicializar filtros',data:  $data);
        }
        return $data;

    }
    final public function ejecuta(bool $header, bool $ws = false){
        $adm_reporte_descripcion = $this->adm_reporte['adm_reporte_descripcion'];

        $link_ejecuta_reporte = $this->obj_link->link_con_id(accion: 'ejecuta_reporte',link: $this->link,
            registro_id:  $this->registro_id,seccion:  $this->tabla);

        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar link',data:  $link_ejecuta_reporte, header: $header, ws: $ws);
        }

        $this->link_ejecuta_reporte = $link_ejecuta_reporte;

        $descripciones_rpt = array('Facturas','Pagos','Egresos');

        if(in_array($adm_reporte_descripcion, $descripciones_rpt)){
            $filtros_fecha = $this->filtros_fecha();
            if(errores::$error){
                return $this->retorno_error(mensaje: 'Error al generar filtros fecha',
                    data:  $filtros_fecha, header: $header, ws: $ws);
            }
            $this->filtros = $filtros_fecha;
        }


        $btn_ejecuta = $this->html_base->submit(css: 'success',label: 'Ejecuta');
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar boton',data:  $btn_ejecuta, header: $header, ws: $ws);
        }

        $this->buttons['btn_ejecuta'] = $btn_ejecuta;



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

        $descripciones_rpt = array('Facturas','Pagos','Egresos');

        if(in_array($adm_reporte_descripcion, $descripciones_rpt)){
            $registros = $this->result_fc_rpt(adm_reporte_descripcion: $adm_reporte_descripcion);
            if(errores::$error){
                return $this->retorno_error(mensaje: 'Error al obtener fc_facturas',data:  $registros, header: $header, ws: $ws);
            }
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

        $btn_exporta = $this->html_base->submit(css: 'success',label: 'Exporta');
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar boton',data:  $btn_exporta, header: $header, ws: $ws);
        }

        $this->buttons['btn_exporta'] = $btn_exporta;

        $fecha_inicial = $this->html->hidden(name: 'fecha_inicial',value:  $_POST['fecha_inicial']);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar fecha_inicial',data:  $fecha_inicial, header: $header, ws: $ws);
        }

        $fecha_final = $this->html->hidden(name: 'fecha_final',value:  $_POST['fecha_final']);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar fecha_final',data:  $fecha_final, header: $header, ws: $ws);
        }

        $this->hiddens->fecha_inicial = $fecha_inicial;
        $this->hiddens->fecha_final = $fecha_final;


    }

    final public function exportar_xls(bool $header, bool $ws = false){
        $adm_reporte_descripcion = $this->adm_reporte['adm_reporte_descripcion'];

        $nombre_hojas = array();
        $keys_hojas = array();
        if($adm_reporte_descripcion === 'Facturas'){


            $registros = $this->result_fc_rpt(adm_reporte_descripcion: $adm_reporte_descripcion);
            if(errores::$error){
                return $this->retorno_error(mensaje: 'Error al obtener fc_facturas',data:  $registros, header: $header, ws: $ws);
            }

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

        $moneda = array();
        $xls = (new exportador())->genera_xls(header: $header,name:  'Facturas',nombre_hojas:  $nombre_hojas,
            keys_hojas: $keys_hojas, path_base: $this->path_base,moneda: $moneda);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener xls',data:  $xls, header: $header, ws: $ws);
        }

    }

    private function filtro_rango(string $table): array
    {
        $filtro_rango = array();
        if(isset($_POST['fecha_inicial'])){
            $filtro_rango = $this->filtro_rango_post(table: $table);
            if(errores::$error){
                return $this->errores->error(mensaje: 'Error al obtener filtro_rango',data:  $filtro_rango);
            }
        }
        return $filtro_rango;

    }

    private function filtro_rango_post(string $table): array
    {
        $table = trim($table);
        if($table === ''){
            return $this->errores->error(mensaje: 'Error table esta vacia',data:  $table);
        }

        $init = $this->asigna_data_fechas();
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error inicializar POST',data:  $init);
        }

        $filtro_rango[$table.'.fecha']['valor1'] = $_POST['fecha_inicial'];
        $filtro_rango[$table.'.fecha']['valor2'] = $_POST['fecha_final'];
        return $filtro_rango;

    }

    private function filtros_fecha(): array|string
    {

        $hoy = date('Y-m-d');

        $fecha_mes_inicial = date('Y-m-01');
        $fecha_inicial = (new adm_reporte_html(html: $this->html_base))->input_fecha(cols: 6,
            row_upd: new stdClass(), value_vacio: false, name: 'fecha_inicial', place_holder: 'Fecha Inicial',
            value: $fecha_mes_inicial);

        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al generar input',data:  $fecha_inicial);
        }

        $filtros = $fecha_inicial;

        $fecha_final = (new adm_reporte_html(html: $this->html_base))->input_fecha(cols: 6,
            row_upd: new stdClass(), value_vacio: false, name: 'fecha_final', place_holder: 'Fecha Final',
            value: $hoy);

        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al generar input',data:  $fecha_final);
        }

        $filtros .= $fecha_final;

        return $filtros;

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

    private function init_data_post_fecha(stdClass $data): array|stdClass
    {
        if(!$data->existe_alguna_fecha){
            $init = $this->init_post_fecha();
            if(errores::$error){
                return $this->errores->error(mensaje: 'Error inicializar POST',data:  $init);
            }
        }

        if(!$data->existe_fecha_inicial){
            $init = $this->init_post_fecha_inicial();
            if(errores::$error){
                return $this->errores->error(mensaje: 'Error inicializar POST',data:  $init);
            }
        }
        if(!$data->existe_fecha_final){
            $init = $this->init_post_fecha_final();
            if(errores::$error){
                return $this->errores->error(mensaje: 'Error inicializar POST',data:  $init);
            }
        }
        return $data;


    }

    private function init_fecha_final(stdClass $data): stdClass
    {
        if(isset($_POST['fecha_final'])){
            $data->existe_alguna_fecha = true;
            $data->existe_fecha_final = true;
        }
        return $data;

    }

    private function init_fecha_inicial(stdClass $data): stdClass
    {
        if(isset($_POST['fecha_inicial'])) {
            $data->existe_alguna_fecha = true;
            $data->existe_fecha_inicial = true;
        }
        return $data;

    }

    private function init_filtro_fecha(): stdClass
    {
        $data = new stdClass();
        $data->existe_alguna_fecha = false;
        $data->existe_fecha_inicial = false;
        $data->existe_fecha_final = false;
        return $data;

    }

    private function init_post_fecha(): array
    {
        $_POST['fecha_inicial'] = date('Y-m-01');
        $_POST['fecha_final'] = date('Y-m-d');
        return $_POST;

    }

    private function init_post_fecha_final(): array
    {
        $_POST['fecha_final'] = date('Y-m-d');
        return $_POST;

    }

    private function init_post_fecha_inicial(): array
    {
        $_POST['fecha_inicial'] = date('Y-m-01');
        return $_POST;

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

    private function result_fc_rpt(string $adm_reporte_descripcion): array
    {
        $result = new stdClass();
        $result->registros = array();

        $table = '';
        if($adm_reporte_descripcion === 'Facturas'){
            $table = 'fc_factura';
        }
        if($adm_reporte_descripcion === 'Pagos'){
            $table = 'fc_complemento_pago';
        }
        if($adm_reporte_descripcion === 'Egresos'){
            $table = 'fc_nota_credito';
        }

        $filtro_rango = $this->filtro_rango(table: $table);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener filtro_rango',data:  $filtro_rango);
        }
        if($adm_reporte_descripcion === 'Facturas'){
            $columnas_totales[] = 'fc_factura_sub_total_base';
            $columnas_totales[] = 'fc_factura_total_descuento';
            $columnas_totales[] = 'fc_factura_total_traslados';
            $columnas_totales[] = 'fc_factura_total_retenciones';
            $columnas_totales[] = 'fc_factura_total';
            $result = (new fc_factura(link: $this->link))->filtro_and(
                columnas_totales: $columnas_totales, filtro_rango: $filtro_rango);
            if(errores::$error){
                return $this->errores->error(mensaje: 'Error al obtener fc_facturas',data:  $result);
            }

        }
        if($adm_reporte_descripcion === 'Pagos'){
            $result = (new fc_complemento_pago(link: $this->link))->filtro_and(filtro_rango: $filtro_rango);
            if(errores::$error){
                return $this->errores->error(mensaje: 'Error al obtener fc_facturas',data:  $result);
            }
        }
        if($adm_reporte_descripcion === 'Egresos'){
            $result = (new fc_nota_credito(link: $this->link))->filtro_and(filtro_rango: $filtro_rango);
            if(errores::$error){
                return $this->errores->error(mensaje: 'Error al obtener fc_facturas',data:  $result);
            }
        }

        return $result->registros;

    }

    private function td(string $key_registro, array $registro): string|array
    {
        $key_registro = trim($key_registro);
        if($key_registro === ''){
            return $this->errores->error(mensaje: 'Error key_registro esta vacio',data:  $key_registro);
        }
        if(!isset($registro[$key_registro])){
            return $this->errores->error(mensaje: '$registro['.$key_registro.'] no existe',data:  $registro);
        }
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
        if($adm_reporte_descripcion === 'Pagos'){
            $ths[] = array('etiqueta'=>'Folio', 'campo'=>'fc_complemento_pago_folio');
            $ths[] = array('etiqueta'=>'UUID', 'campo'=>'fc_complemento_pago_uuid');
            $ths[] = array('etiqueta'=>'Cliente', 'campo'=>'com_cliente_razon_social');
            $ths[] = array('etiqueta'=>'Sub Total', 'campo'=>'fc_complemento_pago_sub_total_base');
            $ths[] = array('etiqueta'=>'Descuento', 'campo'=>'fc_complemento_pago_total_descuento');
            $ths[] = array('etiqueta'=>'Traslados', 'campo'=>'fc_complemento_pago_total_traslados');
            $ths[] = array('etiqueta'=>'Retenciones', 'campo'=>'fc_complemento_pago_total_retenciones');
            $ths[] = array('etiqueta'=>'Total', 'campo'=>'fc_complemento_pago_total');
            $ths[] = array('etiqueta'=>'Fecha', 'campo'=>'fc_complemento_pago_fecha');
            $ths[] = array('etiqueta'=>'Forma de Pago', 'campo'=>'cat_sat_forma_pago_descripcion');
            $ths[] = array('etiqueta'=>'Metodo de Pago', 'campo'=>'cat_sat_metodo_pago_descripcion');
            $ths[] = array('etiqueta'=>'Moneda', 'campo'=>'cat_sat_moneda_codigo');
            $ths[] = array('etiqueta'=>'Tipo Cambio', 'campo'=>'com_tipo_cambio_monto');
            $ths[] = array('etiqueta'=>'Uso CFDI', 'campo'=>'cat_sat_uso_cfdi_descripcion');
            $ths[] = array('etiqueta'=>'Exportacion', 'campo'=>'fc_complemento_pago_exportacion');
        }
        if($adm_reporte_descripcion === 'Egresos'){
            $ths[] = array('etiqueta'=>'Folio', 'campo'=>'fc_nota_credito_folio');
            $ths[] = array('etiqueta'=>'UUID', 'campo'=>'fc_nota_credito_uuid');
            $ths[] = array('etiqueta'=>'Cliente', 'campo'=>'com_cliente_razon_social');
            $ths[] = array('etiqueta'=>'Sub Total', 'campo'=>'fc_nota_credito_sub_total_base');
            $ths[] = array('etiqueta'=>'Descuento', 'campo'=>'fc_nota_credito_total_descuento');
            $ths[] = array('etiqueta'=>'Traslados', 'campo'=>'fc_nota_credito_total_traslados');
            $ths[] = array('etiqueta'=>'Retenciones', 'campo'=>'fc_nota_credito_total_retenciones');
            $ths[] = array('etiqueta'=>'Total', 'campo'=>'fc_nota_credito_total');
            $ths[] = array('etiqueta'=>'Fecha', 'campo'=>'fc_nota_credito_fecha');
            $ths[] = array('etiqueta'=>'Forma de Pago', 'campo'=>'cat_sat_forma_pago_descripcion');
            $ths[] = array('etiqueta'=>'Metodo de Pago', 'campo'=>'cat_sat_metodo_pago_descripcion');
            $ths[] = array('etiqueta'=>'Moneda', 'campo'=>'cat_sat_moneda_codigo');
            $ths[] = array('etiqueta'=>'Tipo Cambio', 'campo'=>'com_tipo_cambio_monto');
            $ths[] = array('etiqueta'=>'Uso CFDI', 'campo'=>'cat_sat_uso_cfdi_descripcion');
            $ths[] = array('etiqueta'=>'Exportacion', 'campo'=>'fc_nota_credito_exportacion');
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
