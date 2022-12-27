<?php

namespace gamboamartin\facturacion\controllers;

use config\generales;
use gamboamartin\errores\errores;
use Mpdf\Mpdf;
use Throwable;

final class pdf {

    public Mpdf $pdf;

    public function __construct(){
        try {
            $temporales = (new generales())->path_base . "archivos/tmp/";
            $this->pdf = new Mpdf(['tempDir' => $temporales,'mode' => 'utf-8', 'format' => [229, 279]]);
        }
        catch (Throwable $e){
            $error = (new errores())->error('Error al generar objeto de pdf', $e);
            print_r($error);
            die('Error');
        }

        $this->pdf->SetFont('Arial');
        $this->pdf->simpleTables = false;

        $css = file_get_contents((new generales())->path_base."css/pdf.css");
        $this->pdf->WriteHTML($css, 1);
    }

    public function header(string $rfc_emisor,string $folio_fiscal, string $nombre_emisor,string $csd,
                           string $rfc_receptor, string $cod_posta_fecha,string $nombre_receptor, string $efecto,
                           string $cod_posta_receptor, string $regimen_fiscal,string $regimen_fiscal_receptor,
                           string $exportacion,string $cfdi){

        $rfc_emisor = "<td style='font-weight: bold'>RFC emisor:</td><td style='text-align: left; padding-left: 5px'>$rfc_emisor</td>";
        $folio_fiscal = "<td style='font-weight: bold'>Folio fiscal:</td><td>$folio_fiscal</td>";
        $nombre_emisor = "<td style='font-weight: bold'>Nombre emisor:</td><td style='text-align: left; padding-left: 5px'>$nombre_emisor</td>";
        $csd = "<td style='font-weight: bold'>No. de serie del CSD:</td><td>$csd</td>";
        $rfc_receptor = "<td style='font-weight: bold'>RFC receptor:</td><td style='text-align: left; padding-left: 5px'>$rfc_receptor</td>";
        $cod_posta_fecha = "<td style='font-weight: bold'>Código postal, fecha y hora de emisión:</td><td>$cod_posta_fecha</td>";
        $nombre_receptor = "<td style='font-weight: bold'>Nombre receptor:</td><td style='text-align: left; padding-left: 5px'>$nombre_receptor</td>";
        $efecto = "<td style='font-weight: bold'>Efecto de comprobante:</td><td>$efecto</td>";
        $cod_posta_receptor = "<td style='font-weight: bold'>Código postal del receptor:</td><td style='text-align: left; padding-left: 5px'>$cod_posta_receptor</td>";
        $regimen_fiscal = "<td style='font-weight: bold'>Régimen fiscal:</td><td>$regimen_fiscal</td>";
        $regimen_fiscal_receptor = "<td style='font-weight: bold'>Régimen fiscal receptor:</td><td style='text-align: left; padding-left: 5px'>$regimen_fiscal_receptor</td>";
        $exportacion = "<td style='font-weight: bold'>Exportación:</td><td>$exportacion</td>";
        $cfdi = "<td style='font-weight: bold'>Uso CFDI:</td><td style='text-align: left; padding-left: 5px'>$cfdi</td>";

        $fila_1 = "<tr>$rfc_emisor $folio_fiscal</tr>";
        $fila_2 = "<tr>$nombre_emisor $csd</tr>";
        $fila_3 = "<tr>$rfc_receptor $cod_posta_fecha</tr>";
        $fila_4 = "<tr>$nombre_receptor $efecto</tr>";
        $fila_5 = "<tr>$cod_posta_receptor $regimen_fiscal</tr>";
        $fila_6 = "<tr>$regimen_fiscal_receptor $exportacion</tr>";
        $fila_7 = "<tr>$cfdi</tr>";

        $body = "<tbody>$fila_1$fila_2$fila_3,$fila_4,$fila_5,$fila_6,$fila_7</tbody>";

        $table = "<table style='width: 100%;font-size: 12px;'>$body</table>";

        $this->pdf->WriteHTML($table,2);
    }



    private function concepto_datos(array $concepto):string{

        $body_td_1 = $this->html(etiqueta: "td", data: $concepto['cat_sat_producto_id']);
        $body_td_2 = $this->html(etiqueta: "td", data: $concepto['cat_sat_producto_id']);
        $body_td_3 = $this->html(etiqueta: "td", data: $concepto['fc_partida_cantidad']);
        $body_td_4 = $this->html(etiqueta: "td", data: $concepto['cat_sat_unidad_codigo']);
        $body_td_5 = $this->html(etiqueta: "td", data: $concepto['fc_partida_cantidad']);
        $body_td_6 = $this->html(etiqueta: "td", data: $concepto['cat_sat_unidad_descripcion'],propiedades: "colspan='2'");
        $body_td_7 = $this->html(etiqueta: "td", data: $concepto['fc_partida_valor_unitario'],propiedades: "colspan='2'");
        $body_td_8 = $this->html(etiqueta: "td", data: $concepto['fc_partida_descuento'],propiedades: "colspan='2'");
        $body_td_9 = $this->html(etiqueta: "td", data: $concepto['cat_sat_obj_imp_descripcion']);

        return $this->html(etiqueta: "tr", data: $body_td_1.$body_td_2.$body_td_3.$body_td_4.$body_td_5. $body_td_6.
            $body_td_7.$body_td_8.$body_td_9);
    }

    private function concepto_calculos(array $concepto):string{

        $body_td_1 = $this->html(etiqueta: "td", data: "Descripción",class: "color",propiedades: "rowspan='2'");
        $body_td_2 = $this->html(etiqueta: "td", data: "Descripción",propiedades: "colspan='4' rowspan='2'");
        $body_td_3 = $this->html(etiqueta: "td", data: "Impuesto");
        $body_td_4 = $this->html(etiqueta: "td", data: "Tipo",propiedades: "colspan='2'");
        $body_td_5 = $this->html(etiqueta: "td", data: "Base");
        $body_td_6 = $this->html(etiqueta: "td", data: "Tipo Factor");
        $body_td_7 = $this->html(etiqueta: "td", data: "Tasa o Cuota");
        $body_td_8 = $this->html(etiqueta: "td", data: "Importe");


        $body_td_9 = $this->html(etiqueta: "td", data: "222222");
        $body_td_10 = $this->html(etiqueta: "td", data: "222222");
        $body_td_11 = $this->html(etiqueta: "td", data: "222222");
        $body_td_12 = $this->html(etiqueta: "td", data: "222222");
        $body_td_13 = $this->html(etiqueta: "td", data: "222222");
        $body_td_14 = $this->html(etiqueta: "td", data: "222222");
        $body_td_15 = $this->html(etiqueta: "td", data: "222222");

        $body_tr = $this->html(etiqueta: "tr", data: $body_td_1.$body_td_2.$body_td_3.$body_td_4.$body_td_5.
            $body_td_6.$body_td_7.$body_td_8);

        $body_tr .= $this->html(etiqueta: "tr", data: $body_td_9.$body_td_10.$body_td_11.$body_td_12.$body_td_13.
            $body_td_14. $body_td_15);

        return $body_tr;
    }

    private function concepto_numeros(array $concepto):string{

        $body_td_1 = $this->html(etiqueta: "td", data: "Número de pedimento",class: "color",propiedades: "colspan='2'");
        $body_td_2 = $this->html(etiqueta: "td", data: "Número de cuenta predial",class: "color",propiedades: "colspan='2'");
        $body_td_3 = $this->html(etiqueta: "td", data: "  ",propiedades: "colspan='2'");
        $body_td_4 = $this->html(etiqueta: "td", data: "  ",propiedades: "colspan='2'");

        $body_tr = $this->html(etiqueta: "tr", data: $body_td_1.$body_td_2);
        $body_tr .= $this->html(etiqueta: "tr", data: $body_td_3.$body_td_4);

        return $body_tr;
    }

    public function conceptos(array $conceptos){
        $titulo = $this->html(etiqueta: "h1", data: "Conceptos");
        $this->pdf->WriteHTML($titulo);

        $head_td_1 = $this->html(etiqueta: "th", data: "Clave del producto y/o servicio");
        $head_td_2 = $this->html(etiqueta: "th", data: "No. identificación",);
        $head_td_3 = $this->html(etiqueta: "th", data: "Cantidad");
        $head_td_4 = $this->html(etiqueta: "th", data: "Clave de unidad");
        $head_td_5 = $this->html(etiqueta: "th", data: "Unidad");
        $head_td_6 = $this->html(etiqueta: "th", data: "Valor unitario",propiedades: "colspan='2'");
        $head_td_7 = $this->html(etiqueta: "th", data: "Importe",propiedades: "colspan='2'");
        $head_td_8 = $this->html(etiqueta: "th", data: "Descuento",propiedades: "colspan='2'");
        $head_td_9 = $this->html(etiqueta: "th", data: "Objeto impuesto");

        $head_tr_1 = $this->html(etiqueta: "tr", data: $head_td_1.$head_td_2.$head_td_3.$head_td_4.$head_td_5.
            $head_td_6.$head_td_7.$head_td_8.$head_td_9);

        $body_tr = "";

        foreach ($conceptos as $concepto){
            $body_tr .= $this->concepto_datos(concepto: $concepto);
            if(errores::$error){
                $error = (new errores())->error('Error al maquetar concepto', $body_tr);
                print_r($error);
                die('Error');
            }

            $body_tr .= $this->concepto_calculos(concepto: $concepto);
            if(errores::$error){
                $error = (new errores())->error('Error al maquetar concepto', $body_tr);
                print_r($error);
                die('Error');
            }

            $body_tr .= $this->concepto_numeros(concepto: $concepto);
            if(errores::$error){
                $error = (new errores())->error('Error al maquetar concepto', $body_tr);
                print_r($error);
                die('Error');
            }
        }

        $head = $this->html(etiqueta: "thead", data: $head_tr_1);
        $body = $this->html(etiqueta: "tbody", data: $body_tr);

        $table = $this->html(etiqueta: "table", data: $head.$body,class: "conceptos");

        $this->pdf->WriteHTML($table);

        $this->pdf->Output('ejemmplo'.'.pdf','D');
    }

    private function html(string $etiqueta, string $data, string $class = "", string $propiedades = ""): string{
        return "<$etiqueta class='$class' $propiedades>$data</$etiqueta>";
    }

}
