<?php

namespace gamboamartin\facturacion\controllers;

use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\com_agente;
use gamboamartin\facturacion\models\fc_layout_factura;
use gamboamartin\facturacion\models\fc_row_layout;
use PDO;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class _reporte_ventas{
    private array $registros;
    private array $operadores;
    private PDO $link ;
    private Spreadsheet $spreadsheet;

    public function __construct(array $registros, array $operadores, PDO $link){
        $this->registros = $registros;
        $this->operadores = $operadores;
        $this->link = $link;
        $this->spreadsheet = new Spreadsheet();
        try {
            $this->spreadsheet->removeSheetByIndex(0);
        } catch (Exception $e) {

        }

        return $this;
    }

    public function descarga_reporte(): Spreadsheet|array
    {


        foreach ($this->operadores as $operador) {
            $rs = $this->crear_sheet_operador(
                agente_operador_id: $operador['com_agente_id'],
                nombre: $operador['com_agente_descripcion']
            );
            if(errores::$error){
                return (new errores())->error(
                    mensaje: 'Error al obtener informacion adicional',
                    data:  $rs,
                );
            }
        }

        return $this->spreadsheet;
    }

    private function crear_sheet_operador(int $agente_operador_id, string $nombre): array
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

            $com_agente_asesor_id = $registro['com_cliente_com_agente_asesor_id'];
            $com_cliente_id = $registro['com_cliente_id'];

            $porcentaje_comision = $registro['fc_factura_porcentaje_comision_cliente'] / 100;
            $subtotal = $registro['fc_factura_sub_total'];
            $archivo = $subtotal / (1 + $porcentaje_comision);

            $sheet->setCellValue("A{$fila}", $nombre);
            $sheet->setCellValue("B{$fila}", $this->formatea_digitos($com_cliente_id));
            $sheet->setCellValue("C{$fila}", $registro['com_cliente_razon_social']);
            $sheet->setCellValue("D{$fila}", $registro['com_tipo_producto_descripcion']);
            $sheet->setCellValue("E{$fila}", $this->formatea_digitos($com_agente_asesor_id));
            $sheet->setCellValue("I{$fila}", $this->formatea_digitos($registro['fc_factura_id']));
            $sheet->setCellValue("J{$fila}",
                \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(
                    strtotime($registro['fc_factura_fecha'])
                )
            );
            $sheet->setCellValue("K{$fila}", $porcentaje_comision);
            $sheet->setCellValue("L{$fila}", $archivo);
            $sheet->setCellValue("M{$fila}", $registro['fc_factura_total_traslados']);
            $sheet->setCellValue("N{$fila}", $registro['fc_factura_total']);

            $informacion_adicional = $this->obtener_informacion_adicional(
                fc_factura_id: $registro['fc_factura_id'],
                com_agente_asesor_id: $com_agente_asesor_id,
            );
            if(errores::$error){
                return (new errores())->error(
                    mensaje: 'Error al obtener informacion adicional',
                    data:  $informacion_adicional,
                );
            }

            $sheet->setCellValue("F{$fila}", $informacion_adicional['nombre_asesor']);
            $sheet->setCellValue("G{$fila}", $informacion_adicional['periodo']);
            $sheet->setCellValue("H{$fila}", $informacion_adicional['numero_empleados']);

            $fila++;
        }

        $ultimaFila = $fila > 2 ? $fila - 1 : 1;
        $this->DarEstilos(sheet: $sheet, ultimaFila: $ultimaFila);
        return [];
    }

    private function obtener_informacion_adicional(int $fc_factura_id, int $com_agente_asesor_id)
    {
        $nombre_asesor = (new com_agente($this->link))->obtener_nombre_asesor($com_agente_asesor_id);
        if(errores::$error){
            return (new errores())->error(
                mensaje: 'Error al buscar el nombre del asesor',
                data:  $nombre_asesor
            );
        }

        $rs = (new fc_layout_factura(link: $this->link))
            ->filtro_and(filtro: ['fc_layout_factura.fc_factura_id' => $fc_factura_id]);
        if(errores::$error){
            return (new errores())->error(
                mensaje: 'Error al buscar la relacion de factura con layout',
                data:  $rs
            );
        }

        if ((int)$rs->n_registros < 1) {
            return [
                'numero_empleados' => 0,
                'periodo' => 'Fac sin Rel',
                'nombre_asesor' => $nombre_asesor,
            ];
        }

        $registro = $rs->registros[0];

        $periodo = 'Layout sin periodo';
        if ((int)$registro['fc_layout_periodo_id'] !== 0){
            $periodo = $registro['fc_layout_periodo_descripcion'];
        }

        $fc_layout_nom_id = (int)$registro['fc_layout_factura_fc_layout_nom_id'];


        $rs = (new fc_row_layout(link: $this->link))
            ->filtro_and(filtro: ['fc_row_layout.fc_layout_nom_id' => $fc_layout_nom_id]);
        if(errores::$error){
            return (new errores())->error(
                mensaje: 'Error al buscar el numero de empleados',
                data:  $rs
            );
        }

        $numero_empleados = (int)$rs->n_registros;

        return [
            'numero_empleados' => $numero_empleados,
            'periodo' => $periodo,
            'nombre_asesor' => $nombre_asesor,
        ];

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

        $sheet->getStyle("K2:K{$ultimaFila}")
            ->getNumberFormat()->setFormatCode('0.00%');

        $sheet->getStyle("J2:J{$ultimaFila}")
            ->getNumberFormat()->setFormatCode('yyyy-mm-dd');

        $sheet->getStyle("L2:N{$ultimaFila}")
            ->getNumberFormat()->setFormatCode('"$"#,##0.00');


        $sheet->getRowDimension(1)->setRowHeight(35);
        $sheet->getStyle('A1:N1')->getAlignment()
            ->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER)
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle("B2:B{$ultimaFila}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle("E2:E{$ultimaFila}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle("I2:I{$ultimaFila}")->getAlignment()
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

    private function formatea_digitos(int $numero): string {
        return str_pad((string)$numero, 7, '0', STR_PAD_LEFT);
    }


}