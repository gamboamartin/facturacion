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
            $this->pdf = new Mpdf(['tempDir' => $temporales]);
        }
        catch (Throwable $e){
            $error = (new errores())->error('Error al generar objeto de pdf', $e);
            print_r($error);
            die('Error');
        }

        $this->pdf->SetFont('Arial');

        //$css = file_get_contents('assets/mpdfstyletables.css');
        //$mpdf->WriteHTML($stylesheet, 1);
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

        $table = "<table style='width: 100%;font-size: 12px'>$body</table>";

        $this->pdf->WriteHTML($table);

        $this->pdf->Output('ejemmplo'.'.pdf','D');

    }



}
