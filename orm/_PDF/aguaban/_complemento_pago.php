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
use function PhpOffice\PhpSpreadsheet\Calculation\Statistical\Distributions\ChiSquared;

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

        $this->set_folio(pdf: $pdf);
        $this->set_fecha_hora_expedicion(pdf: $pdf);
        $this->set_emisor(pdf: $pdf);
        $this->set_receptor(pdf: $pdf);
        $this->set_detalle_pago(pdf: $pdf);
        $this->set_documento_pagado(pdf: $pdf);
        $this->set_informacion_impuestos(pdf: $pdf);
        $this->set_datos_fiscales(pdf: $pdf);
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

    private function set_folio(Fpdi $pdf): void
    {
        $folio = $this->datos['folio'];
        $pdf->SetFont('DejaVuSans', 'B', '10');
        $pdf->SetTextColor(18, 61, 112);
        $pdf->SetXY(166, 20);
        $pdf->cell(30,5, $folio, 0, 0, 'R');

    }

    private function set_fecha_hora_expedicion(__CUSTOM_FPDI $pdf): void
    {
        $border = 0;

        $x = 156;
        $y = 25;
        $w = 40;
        $h = 5;

        $pdf->ocultar_area($x, $y, $w, $h);

        $pdf->SetFont('DejaVuSans', '', '6');
        $pdf->SetTextColor(86, 117, 154);
        $pdf->SetXY($x, $y);
        $pdf->cell($w,$h, $this->fecha_certificacion(),$border, 0, 'R');
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

    private function set_receptor(__CUSTOM_FPDI $pdf): void
    {

        $border = 1;
        $x = 138;
        $h = 3.5;
        $y = 39;

        $razon_social = '';
        $cliente_rfc = 'RFC ';
        $regimen = 'Regimen Fiscal ';
        $uso_cfdi = 'Uso CFDI ';

        $razon_social = mb_convert_encoding($razon_social, 'ISO-8859-1', 'UTF-8');
        $cliente_rfc = mb_convert_encoding($cliente_rfc, 'ISO-8859-1', 'UTF-8');
        $regimen = mb_convert_encoding($regimen, 'ISO-8859-1', 'UTF-8');
        $uso_cfdi = mb_convert_encoding($uso_cfdi, 'ISO-8859-1', 'UTF-8');


        $pdf->SetFont('DejaVuSans', 'B', '8');
        $pdf->SetTextColor(18, 61, 112);
        $pdf->SetXY($x, $y);
        $pdf->multicell(60,$h, $razon_social,$border, 'L');
        $lineas_razon_social = $pdf->NbLines(60, $razon_social);
        $y += $h * $lineas_razon_social;

        $pdf->SetFont('DejaVuSans', '', '8');
        $pdf->SetTextColor(18, 61, 112);
        $pdf->SetXY($x, $y);
        $pdf->cell(60,$h, $cliente_rfc,$border, 0, 'L');
        $y += $h;

        $pdf->SetFont('DejaVuSans', '', '7');
        $pdf->SetTextColor(86, 117, 154);
        $pdf->SetXY($x, $y);
        $pdf->cell(60,$h, $regimen,$border, 0, 'L');
        $y += $h;

        $pdf->SetFont('DejaVuSans', '', '7');
        $pdf->SetTextColor(86, 117, 154);
        $pdf->SetXY($x, $y);
        $pdf->cell(60,$h, $uso_cfdi,$border, 0, 'L');

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

    private function set_documento_pagado(__CUSTOM_FPDI $pdf): void
    {// ToDo: algunos datos no vienen en el array de datos
        $border = 0;

        $uuid_documento_full_text = "UUID del Documento: ". strtoupper($this->datos['relacionados']['folio_fiscal']);
        $serie_folio = "A-0225";
        $moneda = strtoupper($this->datos['informacion_pago']['moneda']);
        $equivalencia = $this->datos['informacion_pago']['tipo_cambio'];
        $objeto_impuesto = "02 - Sí objeto de impuesto";
        $objeto_impuesto = mb_convert_encoding($objeto_impuesto, 'ISO-8859-1', 'UTF-8');
        $parcialidad = $this->datos['relacionados']['numero_parcialidad'];
        $saldo_anterior = $this->datos['relacionados']['saldo_anterior'];
        $importe_pagado = $this->datos['relacionados']['importe_pagado'];
        $saldo_insoluto = $this->datos['relacionados']['saldo_insoluto'];

        $pdf->ocultar_area(14.5, 109.3, 30, 5);

        $pdf->SetFont('DejaVuSans', '', 7);
        $pdf->SetTextColor(18, 61, 112);
        $pdf->SetXY(14.5, 109.3);
        $pdf->Cell(150, 5, $uuid_documento_full_text, $border, 0, 'L');

        $x = 15;
        $y = 126;
        $w = 21;
        $h = 8;
        $pdf->SetFont('DejaVuSans', '', 7);
        $pdf->SetTextColor(18, 61, 112);
        $pdf->SetXY($x, $y);
        $pdf->Cell($w, $h, $serie_folio, $border, 0, 'C');

        $x += $w;
        $w = 16;
        $pdf->SetXY($x, $y);
        $pdf->Cell($w, $h, $moneda, $border, 0, 'C');

        $x += $w;
        $w = 25;
        $pdf->SetXY($x, $y);
        $pdf->Cell($w, $h, $equivalencia, $border, 0, 'C');

        $x += $w;
        $w = 23;
        $pdf->SetXY($x, $y);
        $pdf->Multicell($w, (float)($h/2), $objeto_impuesto, $border, 'C');

        $x += $w;
        $w = 25;
        $pdf->SetXY($x, $y);
        $pdf->Cell($w, $h, $parcialidad, $border, 0, 'C');

        $x += $w;
        $w = 23;
        $pdf->SetXY($x, $y);
        $pdf->Cell($w, $h, $saldo_anterior, $border, 0, 'C');

        $x += $w;
        $pdf->SetXY($x, $y);
        $pdf->Cell($w, $h, $importe_pagado, $border, 0, 'C');

        $x += $w;
        $w = 24;
        $pdf->SetXY($x, $y);
        $pdf->Cell($w, $h, $saldo_insoluto, $border, 0, 'C');
    }

    private function set_informacion_impuestos(__CUSTOM_FPDI $pdf): void
    {// ToDo: algunos datos no vienen en el array de datos
        $border = 0;

        $tipo_impuesto = " --- ";
        $base = " --- ";
        $impuesto = " --- ";
        $tipo_factor = " --- ";
        $tasa_cuota = " --- ";
        $importe = " --- ";

        $x = 15;
        $y = 156;
        $h = 8;


        $w = 35;
        $pdf->SetFont('DejaVuSans', '', 7);
        $pdf->SetTextColor(18, 61, 112);
        $pdf->SetXY($x, $y);
        $pdf->Cell($w, $h, $tipo_impuesto, $border, 0, 'C');

        $x += $w;
        $w = 25;
        $pdf->SetXY($x, $y);
        $pdf->Cell($w, $h, $base, $border, 0, 'C');

        $x += $w;
        $w = 32;
        $pdf->SetXY($x, $y);
        $pdf->Cell($w, $h, $impuesto, $border, 0, 'C');

        $x += $w;
        $w = 30;
        $pdf->SetXY($x, $y);
        $pdf->Cell($w, $h, $tipo_factor, $border, 0, 'C');

        $x += $w;
        $pdf->SetXY($x, $y);
        $pdf->Cell($w, $h, $tasa_cuota, $border, 0, 'C');

        $x += $w;
        $w = 28;
        $pdf->SetXY($x, $y);
        $pdf->Cell($w, $h, $importe, $border, 0, 'C');
    }

    private function set_datos_fiscales(__CUSTOM_FPDI $pdf): void
    {
        $border = 0;

        $uuid_fiscal = "UUID / Folio fiscal: ";
        $uuid_fiscal .= strtoupper($this->datos['fiscales']['folio_sat']);

        $fecha_hora_certificacion = "Fecha y hora de certificación: ";
        $fecha_hora_certificacion .= $this->fecha_certificacion();

        $no_certificado_emisor = "No. certificado emisor: ";
        $no_certificado_emisor .= $this->datos['fiscales']['certificado_emisor'];

        $no_certificado_sat = "No. certificado SAT: ";
        $no_certificado_sat .= $this->datos['fiscales']['certificado_sat'];

        $uuid_fiscal = mb_convert_encoding($uuid_fiscal, 'ISO-8859-1', 'UTF-8');//
        $fecha_hora_certificacion = mb_convert_encoding($fecha_hora_certificacion, 'ISO-8859-1', 'UTF-8');
        $no_certificado_emisor = mb_convert_encoding($no_certificado_emisor, 'ISO-8859-1', 'UTF-8');
        $no_certificado_sat = mb_convert_encoding($no_certificado_sat, 'ISO-8859-1', 'UTF-8');

        $x = 14.5;
        $y = 192.2;
        $w = 135;
        $h = 4;

        $pdf->ocultar_area($x, $y, $w, $h*5);

        $pdf->SetFont('DejaVuSans', '', 7);
        $pdf->SetTextColor(18, 61, 112);
        $pdf->SetXY($x, $y);
        $pdf->Cell($w, $h, $uuid_fiscal, $border, 0, 'L');

        $y += $h;
        $pdf->SetFont('DejaVuSans', '', 7);
        $pdf->SetTextColor(86, 117, 154);
        $pdf->SetXY($x, $y);
        $pdf->Cell($w, $h, $fecha_hora_certificacion, $border, 0, 'L');

        $y += $h;
        $pdf->SetFont('DejaVuSans', '', 7);
        $pdf->SetTextColor(86, 117, 154);
        $pdf->SetXY($x, $y);
        $pdf->Cell($w, $h, $no_certificado_emisor, $border, 0, 'L');

        $y += $h;
        $pdf->SetFont('DejaVuSans', '', 7);
        $pdf->SetTextColor(86, 117, 154);
        $pdf->SetXY($x, $y);
        $pdf->Cell($w, $h, $no_certificado_sat, $border, 0, 'L');
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

    private function fecha_certificacion(): string
    {
        $fecha_certificacion = '';
        if (isset($this->datos['fechas']['fecha_certificacion'])) {
            $fecha_certificacion = $this->datos['fechas']['fecha_certificacion'];
            $fecha_certificacion = DateTime::createFromFormat('Y-m-d\TH:i:s', $fecha_certificacion);
            if ($fecha_certificacion) {
                return $fecha_certificacion->format('d/m/Y H:i:s');
            }

            return '';
        }
        return $fecha_certificacion;
    }




















}
