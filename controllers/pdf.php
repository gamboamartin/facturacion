<?php

namespace gamboamartin\facturacion\controllers;

use config\generales;
use gamboamartin\errores\errores;
use gamboamartin\validacion\validacion;
use Mpdf\Mpdf;
use NumberFormatter;
use Throwable;

final class pdf
{

    public Mpdf $pdf;
    private errores $error;
    private validacion $valida;

    public function __construct()
    {
        $this->error = new errores();
        $this->valida = new validacion();
        try {
            $temporales = (new generales())->path_base . "archivos/tmp/";
            $this->pdf = new Mpdf(['tempDir' => $temporales, 'mode' => 'utf-8', 'format' => [229, 279],
                'margin_left' => 8, 'margin_right' => 8]);
        } catch (Throwable $e) {
            $error = (new errores())->error('Error al generar objeto de pdf', $e);
            print_r($error);
            die('Error');
        }

        $this->pdf->simpleTables = false;

        $css = file_get_contents((new generales())->path_base . "css/pdf.css");
        $this->pdf->WriteHTML($css, 1);

    }

    public function header(string $rfc_emisor, string $folio_fiscal, string $nombre_emisor, string $csd,
                           string $rfc_receptor, string $cod_postal, string $fecha,
                           string $nombre_receptor, string $efecto,
                           string $cod_postal_receptor, string $regimen_fiscal, string $regimen_fiscal_receptor,
                           string $exportacion, string $cfdi): string|array
    {

        $body_td_1 = $this->html(etiqueta: "td", data: "RFC emisor:", class: "negrita");
        $body_td_2 = $this->html(etiqueta: "td", data: $rfc_emisor);
        $body_td_3 = $this->html(etiqueta: "td", data: "Folio fiscal:", class: "negrita");
        $body_td_4 = $this->html(etiqueta: "td", data: $folio_fiscal);
        $body_td_5 = $this->html(etiqueta: "td", data: "Nombre emisor:", class: "negrita");
        $body_td_6 = $this->html(etiqueta: "td", data: $nombre_emisor);
        $body_td_7 = $this->html(etiqueta: "td", data: "No. de serie del CSD:", class: "negrita");
        $body_td_8 = $this->html(etiqueta: "td", data: $csd);
        $body_td_9 = $this->html(etiqueta: "td", data: "RFC receptor:", class: "negrita");
        $body_td_10 = $this->html(etiqueta: "td", data: $rfc_receptor);
        $body_td_11 = $this->html(etiqueta: "td", data: "C??digo postal, fecha y hora de emisi??n:", class: "negrita");
        $body_td_12 = $this->html(etiqueta: "td", data: "$cod_postal $fecha");
        $body_td_13 = $this->html(etiqueta: "td", data: "Nombre receptor:", class: "negrita");
        $body_td_14 = $this->html(etiqueta: "td", data: $nombre_receptor);
        $body_td_15 = $this->html(etiqueta: "td", data: "Efecto de comprobante:", class: "negrita");
        $body_td_16 = $this->html(etiqueta: "td", data: $efecto);
        $body_td_17 = $this->html(etiqueta: "td", data: "C??digo postal del receptor:", class: "negrita");
        $body_td_18 = $this->html(etiqueta: "td", data: $cod_postal_receptor);
        $body_td_19 = $this->html(etiqueta: "td", data: "R??gimen fiscal:", class: "negrita");
        $body_td_20 = $this->html(etiqueta: "td", data: $regimen_fiscal);
        $body_td_21 = $this->html(etiqueta: "td", data: "R??gimen fiscal receptor:", class: "negrita");
        $body_td_22 = $this->html(etiqueta: "td", data: $regimen_fiscal_receptor);
        $body_td_23 = $this->html(etiqueta: "td", data: "Exportaci??n:", class: "negrita");
        $body_td_24 = $this->html(etiqueta: "td", data: $exportacion);
        $body_td_25 = $this->html(etiqueta: "td", data: "Uso CFDI:", class: "negrita");
        $body_td_26 = $this->html(etiqueta: "td", data: $cfdi);

        $body_tr_1 = $this->html(etiqueta: "tr", data: $body_td_1 . $body_td_2 . $body_td_3 . $body_td_4);
        $body_tr_2 = $this->html(etiqueta: "tr", data: $body_td_5 . $body_td_6 . $body_td_7 . $body_td_8);
        $body_tr_3 = $this->html(etiqueta: "tr", data: $body_td_9 . $body_td_10 . $body_td_11 . $body_td_12);
        $body_tr_4 = $this->html(etiqueta: "tr", data: $body_td_13 . $body_td_14 . $body_td_15 . $body_td_16);
        $body_tr_5 = $this->html(etiqueta: "tr", data: $body_td_17 . $body_td_18 . $body_td_19 . $body_td_20);
        $body_tr_6 = $this->html(etiqueta: "tr", data: $body_td_21 . $body_td_22 . $body_td_23 . $body_td_24);
        $body_tr_7 = $this->html(etiqueta: "tr", data: $body_td_25 . $body_td_26);

        $body = $this->html(etiqueta: "tbody", data: $body_tr_1 . $body_tr_2 . $body_tr_3 . $body_tr_4 . $body_tr_5 . $body_tr_6 .
            $body_tr_7);

        $table = $this->html(etiqueta: "table", data: $body);

        try {
            $this->pdf->WriteHTML($table);
        }
        catch (Throwable $e){
            return $this->error->error(mensaje: 'Error al generar pdf',data:  $e);
        }
        return $table;
    }

    private function concepto_datos(array $concepto): string|array
    {

        $keys_no_ob = array('fc_partida_descuento');
        foreach ($keys_no_ob as $key_no_ob){
            if(isset($concepto[$key_no_ob])){
                $concepto[$key_no_ob] = 0;
            }
        }

        $keys = array('fc_partida_descuento','fc_partida_valor_unitario','cat_sat_producto_codigo',
            'cat_sat_producto_id','fc_partida_cantidad','cat_sat_unidad_codigo','cat_sat_unidad_descripcion',
            'fc_partida_cantidad','cat_sat_obj_imp_descripcion');
        $valida = $this->valida->valida_existencia_keys(keys: $keys,registro:  $concepto);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar concepto',data:  $valida);
        }

        $class = "txt-center border";

        $fc_partida_valor_unitario = $this->limpia_monto(monto: $concepto['fc_partida_valor_unitario']);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al limpiar monto',data:  $fc_partida_valor_unitario);
        }

        $fc_partida_valor_unitario = (float)$fc_partida_valor_unitario;
        $fc_partida_valor_unitario = round($fc_partida_valor_unitario,2);


        $fc_partida_cantidad = $this->limpia_monto(monto: $concepto['fc_partida_cantidad']);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al limpiar monto',data:  $fc_partida_cantidad);
        }
        $fc_partida_cantidad = (float)$fc_partida_cantidad;
        $fc_partida_cantidad = round($fc_partida_cantidad,2);


        $fc_partida_descuento = $this->limpia_monto(monto: $concepto['fc_partida_descuento']);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al limpiar monto',data:  $fc_partida_descuento);
        }

        $fc_partida_descuento = (float)$fc_partida_descuento;
        $fc_partida_descuento = round($fc_partida_descuento,2);


        $fc_partida_importe = $fc_partida_valor_unitario * $fc_partida_cantidad;
        $fc_partida_importe = round($fc_partida_importe,2);


        $fc_partida_descuento = $this->monto_moneda(monto: $fc_partida_descuento);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al limpiar monto',data:  $fc_partida_descuento);
        }

        $fc_partida_valor_unitario = $this->monto_moneda(monto: $fc_partida_valor_unitario);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al limpiar monto',data:  $fc_partida_valor_unitario);
        }

        $fc_partida_importe = $this->monto_moneda(monto: $fc_partida_importe);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al limpiar monto',data:  $fc_partida_importe);
        }



        $body_td_1 = $this->html(etiqueta: "td", data: $concepto['cat_sat_producto_codigo'], class: $class, propiedades: "colspan='2'");
        $body_td_2 = $this->html(etiqueta: "td", data: $concepto['cat_sat_producto_id'], class: $class);
        $body_td_3 = $this->html(etiqueta: "td", data: $fc_partida_cantidad, class: $class);
        $body_td_4 = $this->html(etiqueta: "td", data: $concepto['cat_sat_unidad_codigo'], class: $class);
        $body_td_5 = $this->html(etiqueta: "td", data: $concepto['cat_sat_unidad_descripcion'], class: $class);
        $body_td_6 = $this->html(etiqueta: "td", data: $fc_partida_valor_unitario, class: $class);
        $body_td_7 = $this->html(etiqueta: "td", data: $fc_partida_importe, class: $class);
        $body_td_8 = $this->html(etiqueta: "td", data: $fc_partida_descuento, class: $class);
        $body_td_9 = $this->html(etiqueta: "td", data: $concepto['cat_sat_obj_imp_descripcion'], class: $class);

        return $this->html(etiqueta: "tr", data: $body_td_1 . $body_td_2 . $body_td_3 . $body_td_4 . $body_td_5 . $body_td_6 .
            $body_td_7 . $body_td_8 . $body_td_9);
    }

    private function columnas_impuestos(): string
    {
        $body_td_3 = $this->html(etiqueta: "td", data: "Impuesto", class: "txt-center negrita", propiedades: "colspan='2'");
        $body_td_4 = $this->html(etiqueta: "td", data: "Tipo", class: "txt-center negrita", propiedades: "colspan='2'");
        $body_td_5 = $this->html(etiqueta: "td", data: "Base", class: "txt-center negrita");
        $body_td_6 = $this->html(etiqueta: "td", data: "Tipo Factor", class: "txt-center negrita", propiedades: "colspan='2'");
        $body_td_7 = $this->html(etiqueta: "td", data: "Tasa o Cuota", class: "txt-center negrita", propiedades: "colspan='2'");
        $body_td_8 = $this->html(etiqueta: "td", data: "Importe", class: "txt-center negrita");

        return $this->html(etiqueta: "tr", data:  $body_td_3 . $body_td_4 . $body_td_5 .
            $body_td_6 . $body_td_7 . $body_td_8);
    }

    private function concepto_calculos(array $concepto): string
    {


        $body_tr = '';

        $impuesto = '';
        $tipo = '';
        $base = '';
        $tipo_factor = '';
        $tasa_cuota = '';
        $importe = '';
        $aplica_traslado = false;

        if(isset($concepto['traslados']) && count($concepto['traslados'])>0){
            $aplica_traslado = true;
            $impuesto = $concepto['traslados'][0]['cat_sat_tipo_impuesto_descripcion'];
            $tipo = 'Traslado';
            $fc_partida_valor_unitario = round($concepto['traslados'][0]['fc_partida_valor_unitario'],2);
            $fc_partida_cantidad = round($concepto['traslados'][0]['fc_partida_cantidad'],2);
            $fc_partida_descuento = round($concepto['traslados'][0]['fc_partida_descuento'],2);

            $tasa_cuota = round($concepto['traslados'][0]['cat_sat_factor_factor'],2);

            $base = $fc_partida_valor_unitario * $fc_partida_cantidad;
            $base = round($base,2);

            $base = $base - $fc_partida_descuento;
            $base = round($base,2);

            $importe = $base * $tasa_cuota;
            $importe = round($importe,2);
            $importe = $this->monto_moneda(monto: $importe);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al ajustar base',data:  $importe);
            }


            $base = $this->monto_moneda(monto: $base);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al ajustar base',data:  $base);
            }



            $tipo_factor = $concepto['traslados'][0]['cat_sat_tipo_factor_codigo'];


            //print_r($concepto);exit;
        }

        if($aplica_traslado) {
            $body_tr = $this->columnas_impuestos();
            $body_td_9 = $this->html(etiqueta: "td", data: $impuesto, class: "txt-center", propiedades: "colspan='2'");
            $body_td_10 = $this->html(etiqueta: "td", data: $tipo, class: "txt-center", propiedades: "colspan='2'");
            $body_td_11 = $this->html(etiqueta: "td", data: $base, class: "txt-center");
            $body_td_12 = $this->html(etiqueta: "td", data: $tipo_factor, class: "txt-center", propiedades: "colspan='2'");
            $body_td_13 = $this->html(etiqueta: "td", data: $tasa_cuota, class: "txt-center", propiedades: "colspan='2'");
            $body_td_14 = $this->html(etiqueta: "td", data: $importe, class: "txt-center");

            $body_tr .= $this->html(etiqueta: "tr", data: $body_td_9 . $body_td_10 . $body_td_11 . $body_td_12 . $body_td_13 .
                $body_td_14);
        }

        return $body_tr;
    }

    private function concepto_producto(array $concepto): string
    {

        $body_td_1 = $this->html(etiqueta: "td", data: "Descripci??n", class: "border color negrita", propiedades: "colspan='10'");
        $body_td_2 = $this->html(etiqueta: "td", data: $concepto['com_producto_descripcion'], class: "border",
            propiedades: "colspan='10'");

        $body_tr_1 = $this->html(etiqueta: "tr", data: $body_td_1);
        $body_tr_2 = $this->html(etiqueta: "tr", data: $body_td_2);

        return $body_tr_1.$body_tr_2;
    }

    private function concepto_numeros(array $concepto): string
    {
        $body_tr = '';
        $aplica_numero_pedimento = false;
        $aplica_cuenta_predial = false;
        $class = "color border negrita txt-center";

        if($aplica_numero_pedimento) {
            $body_td_1 = $this->html(etiqueta: "td", data: "N??mero de pedimento", class: $class, propiedades: "colspan='4'");
        }
        if($aplica_cuenta_predial) {
            $body_td_2 = $this->html(etiqueta: "td", data: "N??mero de cuenta predial", class: $class, propiedades: "colspan='6'");
        }
        if($aplica_numero_pedimento) {
            $body_td_3 = $this->html(etiqueta: "td", data: " ", class: "txt-center border", propiedades: "colspan='4'");
        }
        if($aplica_cuenta_predial) {
            $body_td_4 = $this->html(etiqueta: "td", data: "  ", class: "txt-center border", propiedades: "colspan='6'");
        }
        if($aplica_numero_pedimento) {
            $body_tr = $this->html(etiqueta: "tr", data: $body_td_1 . $body_td_2);
        }
        if($aplica_cuenta_predial) {
            $body_tr .= $this->html(etiqueta: "tr", data: $body_td_3 . $body_td_4);
        }

        return $body_tr;
    }

    public function conceptos(array $conceptos)
    {
        $titulo = $this->html(etiqueta: "h1", data: "Conceptos", class: "negrita titulo");
        $this->pdf->WriteHTML($titulo);

        $head_td_1 = $this->html(etiqueta: "th", data: "Clave del producto y/o servicio", class: "negrita border color", propiedades: "colspan='2'");;
        $head_td_2 = $this->html(etiqueta: "th", data: "No. identificaci??n", class: "negrita border color");
        $head_td_3 = $this->html(etiqueta: "th", data: "Cantidad", class: "negrita border color");
        $head_td_4 = $this->html(etiqueta: "th", data: "Clave de unidad", class: "negrita border color");
        $head_td_5 = $this->html(etiqueta: "th", data: "Unidad", class: "negrita border color");
        $head_td_6 = $this->html(etiqueta: "th", data: "Valor unitario", class: "negrita border color");
        $head_td_7 = $this->html(etiqueta: "th", data: "Importe", class: "negrita border color");
        $head_td_8 = $this->html(etiqueta: "th", data: "Descuento", class: "negrita border color");
        $head_td_9 = $this->html(etiqueta: "th", data: "Objeto impuesto", class: "negrita border color");

        $head_tr_1 = $this->html(etiqueta: "tr", data: $head_td_1 . $head_td_2 . $head_td_3 . $head_td_4 . $head_td_5 .
            $head_td_6 . $head_td_7 . $head_td_8 . $head_td_9);

        $body_tr = "";

        foreach ($conceptos as $concepto) {

            $concepto_pdf = $this->concepto_datos(concepto: $concepto);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al generar concepto',data:  $concepto_pdf);
            }

            $body_tr .= $concepto_pdf;


            $body_tr .= $this->concepto_producto(concepto: $concepto);
            if (errores::$error) {
                $error = (new errores())->error('Error al maquetar concepto', $body_tr);
                print_r($error);
                die('Error');
            }

            $body_tr .= $this->concepto_calculos(concepto: $concepto);
            if (errores::$error) {
                $error = (new errores())->error('Error al maquetar concepto', $body_tr);
                print_r($error);
                die('Error');
            }

            $body_tr .= $this->concepto_numeros(concepto: $concepto);
            if (errores::$error) {
                $error = (new errores())->error('Error al maquetar concepto', $body_tr);
                print_r($error);
                die('Error');
            }
        }

        $head = $this->html(etiqueta: "thead", data: $head_tr_1);
        $body = $this->html(etiqueta: "tbody", data: $body_tr);

        $table = $this->html(etiqueta: "table", data: $head . $body, class: "border");

        $this->pdf->WriteHTML($table);
    }

    private function html(string $etiqueta, string $data = "", string $class = "", string $propiedades = ""): string
    {
        return "<$etiqueta class='$class' $propiedades>$data</$etiqueta>";
    }

    /**
     * Limpia un monto para dejarlo como double
     * @param int|float|string $monto Monto a ajustar
     * @return array|string
     * @version 2.12.0
     */
    private function limpia_monto(int|float|string $monto): array|string
    {
        $monto = trim($monto);
        if($monto === ''){
            return $this->error->error(mensaje: 'Error monto esta vacio', data: $monto);
        }
        $monto = str_replace(' ', '', $monto);
        $monto = str_replace('$', '', $monto);
        $monto = str_replace(',', '', $monto);

        if(!is_numeric($monto)){
            return $this->error->error(mensaje: 'Error monto debe ser un numero', data: $monto);
        }

        return str_replace(',', '', $monto);
    }

    /**
     * Ajusta un monto a dor formato moneda mx
     * @param int|float|string $monto Monto a formatear
     * @return array|false|string
     * @version 2.18.0
     */
    private function monto_moneda(int|float|string $monto): bool|array|string
    {
        $monto = trim($monto);
        if($monto === ''){
            return $this->error->error(mensaje: 'Error monto esta vacio', data: $monto);
        }
        $monto = $this->limpia_monto(monto: $monto);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al limpiar monto',data:  $monto);
        }
        $formatter = new NumberFormatter('es_MX',  NumberFormatter::CURRENCY);
        return $formatter->formatCurrency($monto, 'MXN');
    }

    public function totales(string $moneda, string $subtotal, string $forma_pago, string $imp_trasladados,
                            string $imp_retenidos, string $metodo_pago, string $total)
    {

        $subtotal = $this->monto_moneda(monto: $subtotal);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al limpiar monto',data:  $subtotal);
        }

        $imp_trasladados = $this->monto_moneda(monto: $imp_trasladados);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al limpiar monto',data:  $imp_trasladados);
        }

        $imp_retenidos = $this->monto_moneda(monto: $imp_retenidos);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al limpiar monto',data:  $imp_retenidos);
        }

        $total = $this->monto_moneda(monto: $total);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al limpiar monto',data:  $total);
        }


        $body_td_1 = $this->html(etiqueta: "td", data: "Moneda:", class: "negrita");
        $body_td_2 = $this->html(etiqueta: "td", data: $moneda);
        $body_td_3 = $this->html(etiqueta: "td", data: "Subtotal:", class: "negrita");
        $body_td_4 = $this->html(etiqueta: "td", data: $subtotal);

        $body_td_5 = $this->html(etiqueta: "td", data: "Forma de pago:", class: "negrita", propiedades: "rowspan='2'");
        $body_td_6 = $this->html(etiqueta: "td", data: $forma_pago, propiedades: "rowspan='2'");
        $body_td_7 = $this->html(etiqueta: "td", data: "Impuestos trasladados:", class: "negrita");
        $body_td_8 = $this->html(etiqueta: "td", data: $imp_trasladados);
        $body_td_9 = $this->html(etiqueta: "td", data: "Impuestos retenidos", class: "negrita");
        $body_td_10 = $this->html(etiqueta: "td", data: $imp_retenidos);

        $body_td_11 = $this->html(etiqueta: "td", data: "M??todo de pago:", class: "negrita");
        $body_td_12 = $this->html(etiqueta: "td", data: $metodo_pago);
        $body_td_13 = $this->html(etiqueta: "td", data: "Total", class: "negrita");
        $body_td_14 = $this->html(etiqueta: "td", data: $total);

        $body_tr_1 = $this->html(etiqueta: "tr", data: $body_td_1 . $body_td_2 . $body_td_3 . $body_td_4);
        $body_tr_2 = $this->html(etiqueta: "tr", data: $body_td_5 . $body_td_6 . $body_td_7 . $body_td_8);
        $body_tr_3 = $this->html(etiqueta: "tr", data: $body_td_9 . $body_td_10);
        $body_tr_4 = $this->html(etiqueta: "tr", data: $body_td_11 . $body_td_12 . $body_td_13 . $body_td_14);

        $body = $this->html(etiqueta: "tbody", data: $body_tr_1 . $body_tr_2 . $body_tr_3 . $body_tr_4);

        $table = $this->html(etiqueta: "table", data: $body, class: "mt-2");

        $this->pdf->WriteHTML($table);
    }

    public function sellos(string $sello_cfdi, string $sello_sat)
    {

       // $this->pdf->WriteHTML("<br><br><br><br>");
        $sello = $this->html(etiqueta: "h2", data: "Sello digital del CFDI:", class: "negrita mt-5");
        $this->pdf->WriteHTML($sello);

        $firma = $this->html(etiqueta: "p", data: $sello_cfdi);
        $this->pdf->WriteHTML($firma);

        $sello = $this->html(etiqueta: "h2", data: "Sello digital del SAT:", class: "negrita");
        $this->pdf->WriteHTML($sello);

        $firma = $this->html(etiqueta: "p", data: $sello_sat);
        $this->pdf->WriteHTML($firma);
    }

    public function complementos(string $ruta_documento,string $complento, string $rfc_proveedor, string $fecha, string $no_certificado)
    {
        //$ruta_documento = '';

        $qr = $this->html(etiqueta: "img", data: "", propiedades: 'src = "' . $ruta_documento . '" width = "120"');

        if ($ruta_documento !== ""){
            $this->pdf->WriteHTML($qr);
        }

        $cadena_sat = $this->html(etiqueta: "h2", data: "Cadena Original del complemento de certificaci??n digital del SAT:", class: "negrita");
        $this->pdf->WriteFixedPosHTML($cadena_sat, 50, $this->pdf->y - 35,200,10);
        $complento = $this->html(etiqueta: "p", data: $complento);
        $this->pdf->WriteFixedPosHTML($complento, 50, $this->pdf->y - 30,170,20);

        $body_td_1 = $this->html(etiqueta: "td", data: "RFC del proveedor de certificaci??n:", class: "negrita");
        $body_td_2 = $this->html(etiqueta: "td", data: $rfc_proveedor);
        $body_td_3 = $this->html(etiqueta: "td", data: "Fecha y hora de certificaci??n:", class: "negrita");
        $body_td_4 = $this->html(etiqueta: "td", data: $fecha, class: "text-center");
        $body_td_5 = $this->html(etiqueta: "td", data: "No. de serie del certificado SAT", class: "negrita");
        $body_td_6 = $this->html(etiqueta: "td", data: $no_certificado);

        $body_tr_1 = $this->html(etiqueta: "tr", data: $body_td_1 . $body_td_2 . $body_td_3 . $body_td_4);
        $body_tr_2 = $this->html(etiqueta: "tr", data: $body_td_5 . $body_td_6);

        $body = $this->html(etiqueta: "tbody", data: $body_tr_1 . $body_tr_2 );
        $table = $this->html(etiqueta: "table", data: $body, class: "mt-2");

        $this->pdf->WriteFixedPosHTML($table, 50, $this->pdf->y - 15,170,20);
    }

    public function guardar(string $nombre_documento)
    {
        $this->pdf->Output($nombre_documento . '.pdf','I');
    }

    public function footer(string $descripcion)
    {
        $footer = $this->html(etiqueta: "h2", data: $descripcion, class: "footer negrita");

        $this->pdf->SetFooter($footer);
    }
}
