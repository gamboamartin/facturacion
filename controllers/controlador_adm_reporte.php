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

        $result = new stdClass();

        $descripciones_rpt = array('Facturas','Pagos','Egresos');

        if(in_array($adm_reporte_descripcion, $descripciones_rpt)){
            $result = $this->result_fc_rpt(adm_reporte_descripcion: $adm_reporte_descripcion);
            if(errores::$error){
                return $this->retorno_error(mensaje: 'Error al obtener fc_facturas',data:  $result, header: $header, ws: $ws);
            }
        }


        $table = (new _table())->contenido_table(adm_reporte_descripcion: $adm_reporte_descripcion,result:  $result);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener contenido',data:  $table, header: $header, ws: $ws);
        }
        $this->ths = $table->ths_html;
        $this->trs = $table->trs_html;


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

            $ths = (new _table())->ths_array(adm_reporte_descripcion: $adm_reporte_descripcion);
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
            $keys_hojas['Facturas']->registros = $registros->registros;

        }

        $moneda = array();
        $totales_hoja = new stdClass();
        $totales_hoja->Facturas = (array)$registros->totales;
        $xls = (new exportador())->genera_xls(header: $header,name:  'Facturas',nombre_hojas:  $nombre_hojas,
            keys_hojas: $keys_hojas, path_base: $this->path_base,moneda: $moneda,totales_hoja: $totales_hoja);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener xls',data:  $xls, header: $header, ws: $ws);
        }

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


    private function result_fc_rpt(string $adm_reporte_descripcion): array|stdClass
    {
        $result = new stdClass();
        $result->registros = array();
        $result->totales = array();

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

        $filtro_rango = (new _filtros())->filtro_rango(table: $table);
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

        return $result;

    }

}
