<?php

namespace gamboamartin\facturacion\controllers;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class _reporte_facturacion{
    private array $registros;
    private int $fila;
    private Spreadsheet $spreadsheet;
    private Worksheet $sheet;

    public function __construct(array $registros){
        $this->registros = $registros;
        $this->spreadsheet = new Spreadsheet();
        $this->sheet = $this->spreadsheet->getActiveSheet();

        foreach ($this->headers() as $cell => $text) {
            $this->sheet->setCellValue($cell, $text);
        }

        return $this;
    }

    public function descarga_reporte(): Spreadsheet
    {

        $this->fila = 2;

        $total_archivo  = 0;
        $total_comision = 0;
        $total_subtotal = 0;
        $total_iva      = 0;
        $total_total    = 0;

        foreach ($this->registros as $registro) {

            $porcentaje_comision = $registro['fc_factura_porcentaje_comision_cliente'] / 100;
            $subtotal = $registro['fc_factura_sub_total'];
            $archivo = $subtotal / (1 + $porcentaje_comision);
            $comision = $subtotal - $archivo;

            /* Acumuladores */
            $total_archivo  += (float)$archivo;
            $total_comision += (float)$comision;
            $total_subtotal += (float)$subtotal;
            $total_iva      += (float)$registro['fc_factura_total_traslados'];
            $total_total    += (float)$registro['fc_factura_total'];

            $this->sheet->setCellValue("A{$this->fila}", $registro['org_empresa_razon_social']);
            $this->sheet->setCellValue("B{$this->fila}", $registro['com_cliente_razon_social']);
            $this->sheet->setCellValue("C{$this->fila}", $porcentaje_comision);
            $this->sheet->setCellValue("D{$this->fila}", $archivo);
            $this->sheet->setCellValue("E{$this->fila}", $comision);
            $this->sheet->setCellValue("F{$this->fila}", $registro['fc_factura_sub_total']);
            $this->sheet->setCellValue("G{$this->fila}", $registro['fc_factura_total_traslados']);
            $this->sheet->setCellValue("H{$this->fila}", $registro['fc_factura_total']);
            $this->sheet->setCellValue("I{$this->fila}", $registro['cat_sat_metodo_pago_codigo']);
            $this->sheet->setCellValue(
                "J{$this->fila}",
                \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(
                    strtotime($registro['fc_factura_fecha'])
                )
            );
            $this->sheet->setCellValue("K{$this->fila}", $registro['fc_factura_folio']);

            $this->fila++;
        }

        $this->sheet->setCellValue("C{$this->fila}", 'TOTAL');
        $this->sheet->setCellValue("D{$this->fila}", round($total_archivo, 2));
        $this->sheet->setCellValue("E{$this->fila}", round($total_comision, 2));
        $this->sheet->setCellValue("F{$this->fila}", round($total_subtotal, 2));
        $this->sheet->setCellValue("G{$this->fila}", round($total_iva, 2));
        $this->sheet->setCellValue("H{$this->fila}", round($total_total, 2));

        $this->formatos();

        return $this->spreadsheet;

    }

    private function headers(): array
    {
        return [
            'A1' => 'Pagadora',
            'B1' => 'CLIENTE',
            'C1' => 'Comisión %',
            'D1' => 'Archivo',
            'E1' => 'Comisión Total',
            'F1' => 'Importe Unitario',
            'G1' => 'IVA',
            'H1' => 'Monto Total',
            'I1' => 'Metodo de pago FI',
            'J1' => 'Fecha de factura',
            'K1' => 'Folio CFDI'
        ];
    }

    private function formatos(): void
    {
        /* Formatos */
        $this->sheet->getStyle('A1:K1')->getFont()->setBold(true);
        $this->sheet->getStyle("C{$this->fila}:H{$this->fila}")->getFont()->setBold(true);

        $this->sheet->getStyle("D{$this->fila}:H{$this->fila}")
            ->getNumberFormat()
            ->setFormatCode('"$"#,##0.00');

        $this->sheet->getStyle("C2:C{$this->fila}")
            ->getNumberFormat()->setFormatCode('0.00%');

        $this->sheet->getStyle("D2:H{$this->fila}")
            ->getNumberFormat()->setFormatCode('"$"#,##0.00');

        $this->sheet->getStyle("J2:J{$this->fila}")
            ->getNumberFormat()->setFormatCode('yyyy-mm-dd');
    }
}