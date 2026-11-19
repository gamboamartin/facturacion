<?php

namespace gamboamartin\facturacion\models\_PDF\aguaban;

use config\generales;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\_PDF\__CUSTOM_FPDI;
use NumberFormatter;
use PDO;
use setasign\Fpdi\Fpdi;
use stdClass;
use Throwable;

class _complemento_pago
{
    private errores $error;
    private generales $config_generales;

    private array $datos;

    private NumberFormatter $fmt;

    public function __construct()
    {
        $this->config_generales = new generales();
        $this->error = new errores();
        $this->fmt = new NumberFormatter('es_MX', NumberFormatter::CURRENCY);
    }

    final public function pdf(
        bool $descarga,
        bool $guarda,
        PDO $link,
        array $datos,
    ):
    array|string|bool|stdClass {

        $this->datos = $datos;

        $pdf = new __CUSTOM_FPDI();

        $pdf->AddFont('DejaVuSans', '', 'DejaVuSans.php');
        $pdf->AddFont('DejaVuSans', 'B', 'DejaVuSans-Bold.php');
        $pdf->SetFont('DejaVuSans', '', '9');
        $pdf->SetTextColor(0, 0, 0);

        $init = $this->init($pdf);
        if (errores::$error) {
            return (new errores())->error(mensaje: 'Error al cargar template', data: $init);
        }

        $this->set_emisor(pdf: $pdf);


        $nombre_documento = 'nombre_temporal';


        if ($descarga) {
            $pdf->Output($nombre_documento . '.pdf', 'I');
            return $nombre_documento;
        }
        if ($guarda) {
            $path_base = $this->config_generales->path_base;
            $path_base_archivos = $path_base . 'archivos';
            if (!file_exists($path_base_archivos)) {
                mkdir($path_base_archivos);
            }
            $nombre_documento = $path_base_archivos . '/' . $nombre_documento . '.pdf';

            $pdf->Output('F', $nombre_documento);
            return $nombre_documento;
        }

        return [];

    }



    private function init(Fpdi $pdf): true|array
    {
        $plantilla = 'complemento_aguaban.pdf';
        try {
            $pdf->addPage();
            $pdf->setSourceFile($this->config_generales->path_base . '/plantillas/' . $plantilla);
            $tplIdx = $pdf->importPage(1);
            $pdf->useTemplate($tplIdx, 0, 0, null, null, true);
        } catch (Throwable $e) {
            return (new errores())->error(mensaje: 'Error al cargar template', data: $e);
        }
        return true;
    }

    private function set_emisor(__CUSTOM_FPDI $pdf): void
    {

        $exportacion = "EXPORTACION - " . $this->datos['fiscales']['exportacion'];
        $exportacion = mb_convert_encoding($exportacion, 'ISO-8859-1', 'UTF-8');
        $exportacion = strtoupper($exportacion);


        $border = 0;
        $x = 14.8;
        $h = 4.5;
        $y = 39;

        $pdf->ocultar_area($x, $y, 70, 20);

        $pdf->SetFont('DejaVuSans', 'B', '8');
        $pdf->SetTextColor(18, 61, 112);
        $pdf->SetXY($x, $y);
        $pdf->cell(60,$h, mb_convert_encoding("AGUABAN · RFC AGU000614BI8", 'ISO-8859-1', 'UTF-8'),$border, 0, 'L');
        $y += $h;

        $pdf->SetFont('DejaVuSans', '', '8');
        $pdf->SetTextColor(86, 117, 154);
        $pdf->SetXY($x, $y);
        $pdf->cell(60,$h, mb_convert_encoding("Régimen fiscal 601 - General de Ley Personas Morales", 'ISO-8859-1', 'UTF-8'),$border, 0, 'L');
        $y += $h;

        $pdf->SetFont('DejaVuSans', '', '7');
        $pdf->SetTextColor(86, 117, 154);
        $pdf->SetXY($x, $y);
        $pdf->cell(60,$h, mb_convert_encoding("Lugar de expedición: CP 63734", 'ISO-8859-1', 'UTF-8'),$border, 0, 'L');
        $y += $h;

        $pdf->SetFont('DejaVuSans', '', '7');
        $pdf->SetTextColor(86, 117, 154);
        $pdf->SetXY($x, $y);
        $pdf->cell(60,$h, $exportacion, $border, 0, 'L');


    }




















}
