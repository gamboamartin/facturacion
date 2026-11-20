<?php

namespace gamboamartin\facturacion\models\_PDF\aguaban;

use config\generales;
use DateTime;
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

        $this->set_detalle_pago(pdf: $pdf);
        $this->documento_pagado(pdf: $pdf);
        $this->set_sellos(pdf: $pdf);
        $this->set_qrcode(pdf: $pdf);


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

    private function documento_pagado(__CUSTOM_FPDI $pdf): void
    {// ToDo: terminarlo
        $border = 1;

        $uuid_documento_full_text = "UUID del Documento: ". strtoupper($this->datos['relacionados']['folio_fiscal']);
        $serie_folio = "A-0225";
        $moneda = "MXN";
        $equivalencia = "";
        $objeto_impuesto = "";
        $parcialidad = "";
        $saldo_anterior = "";
        $importe_pagado = "";
        $saldo_insoluto = "";

        $pdf->ocultar_area(14.5, 109.3, 30, 5);
        $pdf->SetDrawColor(0, 0, 0);

        $pdf->SetFont('DejaVuSans', '', 7);
        $pdf->SetTextColor(18, 61, 112);
        $pdf->SetXY(14.5, 109.3);
        $pdf->Cell(150, 5, $uuid_documento_full_text, $border, 0, 'L');

        $x = 15;
        $w = 21;
        $pdf->SetFont('DejaVuSans', '', 7);
        $pdf->SetTextColor(18, 61, 112);
        $pdf->SetXY($x, 126);
        $pdf->Cell($w, 5, $serie_folio, $border, 0, 'C');

        $x += $w;
        $w = 16;
        $pdf->SetXY($x, 126);
        $pdf->Cell($w, 5, $moneda, $border, 0, 'C');

        $x += $w;
        $w = 25;
        $pdf->SetXY($x, 126);
        $pdf->Cell($w, 5, $equivalencia, $border, 0, 'C');

        $x += $w;
        $w = 23;
        $pdf->SetXY($x, 126);
        $pdf->Cell($w, 5, $objeto_impuesto, $border, 0, 'C');

        $x += $w;
        $w = 25;
        $pdf->SetXY($x, 126);
        $pdf->Cell($w, 5, $parcialidad, $border, 0, 'C');

        $x += $w;
        $w = 23;
        $pdf->SetXY($x, 126);
        $pdf->Cell($w, 5, $saldo_anterior, $border, 0, 'C');

        $x += $w;
        $pdf->SetXY($x, 126);
        $pdf->Cell($w, 5, $importe_pagado, $border, 0, 'C');

        $x += $w;
        $w = 24;
        $pdf->SetXY($x, 126);
        $pdf->Cell($w, 5, $saldo_insoluto, $border, 0, 'C');
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

    private function set_detalle_pago(__CUSTOM_FPDI $pdf): void
    {
        $x = 14.5;


        $y = 73;
        $h_title = 3;
        $h_text = 5;

        $lw = 71; //ancho del lado izquierdo
        $rw = 50; //ancho del lado derecho

        $lx = 14.5; // posición x del lado izquierdo
        $rx = $lx + $lw; // posición x del lado derecho

        $border = 0; // 0 oculta el border de los cell y 1 los muestra

        $monto_total = $this->mx_currency($this->datos['informacion_pago']['monto']);
        $monto_total .= ' MXN';

        $fecha_pago = $this->datos['informacion_pago']['fecha_pago'];
        $forma_pago = $this->datos['informacion_pago']['forma_pago_v2'];
        $moneda = $this->datos['informacion_pago']['moneda'] .' - ';
        $moneda .= $this->datos['informacion_pago']['moneda_descripcion'];
        $tipo_cambio = $this->datos['informacion_pago']['tipo_cambio'];
        $rfc_cuenta_bancaria = '---';
        $cuenta_bancaria = '---';

        $pdf->ocultar_area($x, 70, 120, 34);
        $pdf->SetDrawColor(0, 0, 0);

        $pdf->SetFont('DejaVuSans', 'B', 12);
        $pdf->SetTextColor(18, 61, 112);
        $pdf->SetXY(142, $y);
        $pdf->Cell(50, 9, $monto_total, $border, 0, 'R');

        // start row1
        $pdf->SetFont('DejaVuSans', '', 6); // Fecha Pago
        $pdf->SetTextColor(86, 117, 154);
        $pdf->SetXY($lx, $y );
        $pdf->Cell($lw, $h_title, 'Fecha de pago', $border, 0, 'L');

        $pdf->SetFont('DejaVuSans', '', 8);
        $pdf->SetTextColor(18, 61, 112);
        $pdf->SetXY($lx, $y+$h_title);
        $pdf->Cell($lw, $h_text, $fecha_pago, $border, 0, 'L');


        $pdf->SetFont('DejaVuSans', '', 6); // Moneda
        $pdf->SetTextColor(86, 117, 154);
        $pdf->SetXY($rx, $y );
        $pdf->Cell($rw, $h_title, 'Moneda', $border, 0, 'L');

        $pdf->SetFont('DejaVuSans', '', 8);
        $pdf->SetTextColor(18, 61, 112);
        $pdf->SetXY($rx, $y+$h_title );
        $pdf->Cell($rw, $h_text, $moneda, $border, 0, 'L');
        // end row1

        // start row2
        $y += $h_title + $h_text + 2;
        $pdf->SetFont('DejaVuSans', '', 6); // Forma de pago
        $pdf->SetTextColor(86, 117, 154);
        $pdf->SetXY($lx, $y );
        $pdf->Cell($lw, $h_title, 'Forma de pago', $border, 0, 'L');

        $pdf->SetFont('DejaVuSans', '', 8);
        $pdf->SetTextColor(18, 61, 112);
        $pdf->SetXY($lx, $y+$h_title);
        $pdf->Cell($lw, $h_text, $forma_pago, $border, 0, 'L');

        $pdf->SetFont('DejaVuSans', '', 6); // Tipo de cambio
        $pdf->SetTextColor(86, 117, 154);
        $pdf->SetXY($rx, $y );
        $pdf->Cell($rw, $h_title, 'Tipo de cambio', $border, 0, 'L');

        $pdf->SetFont('DejaVuSans', '', 8);
        $pdf->SetTextColor(18, 61, 112);
        $pdf->SetXY($rx, $y+$h_title );
        $pdf->Cell($rw, $h_text, $tipo_cambio, $border, 0, 'L');
        // end row2

        // start row3
        $y += $h_title + $h_text + 2;
        $pdf->SetFont('DejaVuSans', '', 6); // RFC del banco de la cuenta beneficiaria
        $pdf->SetTextColor(86, 117, 154);
        $pdf->SetXY($lx, $y );
        $pdf->Cell($lw, $h_title, 'RFC del banco de la cuenta beneficiaria', $border, 0, 'L');

        $pdf->SetFont('DejaVuSans', '', 8);
        $pdf->SetTextColor(18, 61, 112);
        $pdf->SetXY($lx, $y+$h_title);
        $pdf->Cell($lw, $h_text, $rfc_cuenta_bancaria, $border, 0, 'L');

        $pdf->SetFont('DejaVuSans', '', 6); // Cuenta beneficiaria
        $pdf->SetTextColor(86, 117, 154);
        $pdf->SetXY($rx, $y );
        $pdf->Cell($rw, $h_title, 'Cuenta beneficiaria', $border, 0, 'L');

        $pdf->SetFont('DejaVuSans', '', 8);
        $pdf->SetTextColor(18, 61, 112);
        $pdf->SetXY($rx, $y+$h_title );
        $pdf->Cell($rw, $h_text, $rfc_cuenta_bancaria, $border, 0, 'L');
        // end row3

    }

    private function set_sellos(Fpdi $pdf): void
    {
        $x = 14.5;
        $pdf->SetFont('DejaVuSans', '', '7');
        $pdf->SetTextColor(0, 0, 0);

        $pdf->SetXY($x, 219.5);
        $pdf->MultiCell(140, 2.6, $this->datos['sellos']['sello_cfdi'], 0);

        $pdf->SetXY($x, 239.5);
        $pdf->MultiCell(140, 2.6, $this->datos['sellos']['sello_sat'], 0);

        $pdf->SetAutoPageBreak(false);
        $pdf->SetXY($x, 259.5);
        $pdf->MultiCell(140, 2.6, $this->datos['sellos']['cadena_original']);
        $pdf->SetAutoPageBreak(true);

    }

    private function set_qrcode(Fpdi $pdf): void
    {
        if ($this->datos['qr'] !== '') {
            if (file_exists($this->datos['qr'])) {
                $pdf->Image($this->datos['qr'], 159.5, 222, 31, 31, 'png');
            }
        }
    }

    private function mx_currency(float|int|string|null $cantidad): string
    {
        if ($cantidad === null || !is_numeric($cantidad)) {
            return '';
        }

        $cantidad = round((float)$cantidad, 2);

        $resultado = $this->fmt->formatCurrency($cantidad, 'MXN');

        if ($resultado === false) {
            return '';
        }

        return $resultado;
    }

    private function formato_fecha(string $fecha): string
    {
        $fecha = DateTime::createFromFormat('Y-m-d\TH:i:s', $fecha);
        if ($fecha) {
            return $fecha->format('d/m/Y H:i:s');
        }

        return '';
    }




















}
