<?php

namespace gamboamartin\facturacion\controllers;

use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class _reporte_ventas{
    private array $registros;
    private array $operadores;
    private Spreadsheet $spreadsheet;

    public function __construct(array $registros, array $operadores){
        $this->registros = $registros;
        $this->operadores = $operadores;
        $this->spreadsheet = new Spreadsheet();
        try {
            $this->spreadsheet->removeSheetByIndex(0);
        } catch (Exception $e) {

        }

        return $this;
    }

    public function descarga_reporte(): Spreadsheet
    {


        foreach ($this->operadores as $operador) {
            $this->crear_sheet_operador(
                agente_operador_id: $operador['com_agente_id'],
                nombre: $operador['com_agente_descripcion']
            );
        }

        return $this->spreadsheet;
    }

    private function crear_sheet_operador(int $agente_operador_id, string $nombre)
    {
        $sheet = $this->spreadsheet->createSheet();
        $sheet->setTitle(substr($nombre, 0, 31));

        foreach ($this->headers() as $cell => $text) {
            $sheet->setCellValue($cell, $text);
        }

        $fila = 2;

        foreach ($this->registros as $registro) {

            if ((int)$registro['fc_factura_agente_operacion_alta_id'] !== $agente_operador_id) {
                continue;
            }

            $sheet->setCellValue("A{$fila}", $nombre);
            $sheet->setCellValue("B{$fila}", $registro['com_cliente_id']);
            $sheet->setCellValue("C{$fila}", $registro['com_cliente_razon_social']);

            $fila++;
        }

        $ultimaFila = $fila > 2 ? $fila - 1 : 1;
        $this->DarEstilos(sheet: $sheet, ultimaFila: $ultimaFila);

    }

    private function headers(): array
    {
        return [
            'A1' => 'Nombre Operador',
            'B1' => 'No DE CLIENTE',
            'C1' => 'CLIENTE',
            'D1' => 'PRODUCTO',
            'E1' => 'No DE ASESOR',
            'F1' => 'ASESOR',
            'G1' => 'PERIODO',
            'H1' => 'NUMERO DE EMPLEADOS',
            'I1' => 'FAC.',
            'J1' => 'FECHA DE OPERACIÓN',
            'K1' => 'COMISION CLIENTE',
            'L1' => 'MONTO DE DISPERSION',
            'M1' => 'IVA',
            'N1' => 'TOTAL FACTURA',
        ];
    }

    private function DarEstilos(Worksheet $sheet, int $ultimaFila): void
    {
        try {
            $sheet->getStyle('A1:N1')->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '5983b0'],
                ],
                'borders' => [
                    'outline' => [ // bordes exteriores
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['rgb' => '000000'],
                    ],
                    'inside' => [ // bordes internos
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['rgb' => '000000'],
                    ],
                ],
            ]);
        } catch (Exception $e) {

        }

        $sheet->getRowDimension(1)->setRowHeight(35);
        $sheet->getStyle('A1:N1')->getAlignment()
            ->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER)
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getColumnDimension('A')->setWidth(16);
        $sheet->getColumnDimension('B')->setWidth(10);
        $sheet->getColumnDimension('C')->setWidth(45);
        $sheet->getColumnDimension('D')->setWidth(12);
        $sheet->getColumnDimension('E')->setWidth(12);
        $sheet->getColumnDimension('F')->setWidth(30);
        $sheet->getColumnDimension('G')->setWidth(18);
        $sheet->getColumnDimension('H')->setWidth(15);
        $sheet->getColumnDimension('I')->setWidth(15);
        $sheet->getColumnDimension('J')->setWidth(18);
        $sheet->getColumnDimension('K')->setWidth(18);
        $sheet->getColumnDimension('L')->setWidth(18);
        $sheet->getColumnDimension('M')->setWidth(18);
        $sheet->getColumnDimension('N')->setWidth(18);
    }


}