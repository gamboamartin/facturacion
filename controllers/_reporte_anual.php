<?php

namespace gamboamartin\facturacion\controllers;

use gamboamartin\errores\errores;
use PDO;
use PDOException;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class _reporte_anual{
    // Este reporte se compone de 3 parte para cuales existirá un header y sus estilos
    // fmt1, fmt2, fmt3
    private PDO $link ;
    private int $year;
    private Spreadsheet $spreadsheet;
    private array $datos_fmt3 = [];

    public function __construct(int $year, PDO $link){

        $this->link = $link;
        $this->year = $year;
        $this->spreadsheet = new Spreadsheet();
        try {
            $this->spreadsheet->removeSheetByIndex(0);
        } catch (Exception $e) {

        }

        return $this;
    }

    public function descarga_reporte(): Spreadsheet|array
    {
        foreach ($this->meses() as $mes => $nombre_mes) {
            $rs_sheet_fmt1 = $this->crear_sheet_mes_fmt1(mes: $mes, nombre_mes: $nombre_mes);
            if(errores::$error){
                return (new errores())->error(
                    mensaje: 'Error al crear_sheet_mes_fmt1',
                    data:  $rs_sheet_fmt1
                );
            }

            $rs_sheet_fmt2 = $this->crear_sheet_mes_fmt2(mes: $mes, nombre_mes: $nombre_mes);
            if(errores::$error){
                return (new errores())->error(
                    mensaje: 'Error al crear_sheet_mes_fmt2',
                    data:  $rs_sheet_fmt2
                );
            }
        }

        $rs_sheet_fmt3 = $this->crear_sheet_fmt3();
        if(errores::$error){
            return (new errores())->error(
                mensaje: 'Error al crear_sheet_fmt3',
                data:  $rs_sheet_fmt3
            );
        }

        return $this->spreadsheet;
    }

    private function crear_sheet_fmt3(): array
    {
        $sheet = $this->spreadsheet->createSheet();
        $sheet->setTitle(substr("CONCENTRADO DE VENTAS {$this->year}", 0, 31));

        foreach ($this->headers_fmt3() as $cell => $text) {
            $sheet->setCellValue($cell, $text);
        }
        $sheet->setCellValue('S1', "TOTAL {$this->year}");

        $fila = 2;

        $total_enero = 0.00;
        $total_febrero = 0.00;
        $total_marzo = 0.00;
        $total_abril = 0.00;
        $total_mayo = 0.00;
        $total_junio = 0.00;
        $total_julio = 0.00;
        $total_agosto = 0.00;
        $total_septiembre = 0.00;
        $total_octubre = 0.00;
        $total_noviembre = 0.00;
        $total_diciembre = 0.00;

        foreach ($this->datos_fmt3 as $registro) {
            $total_actual = 0.00;
            $total_actual += (float)($registro['01'] ?? 0.00);
            $total_actual += (float)($registro['02'] ?? 0.00);
            $total_actual += (float)($registro['03'] ?? 0.00);
            $total_actual += (float)($registro['04'] ?? 0.00);
            $total_actual += (float)($registro['05'] ?? 0.00);
            $total_actual += (float)($registro['06'] ?? 0.00);
            $total_actual += (float)($registro['07'] ?? 0.00);
            $total_actual += (float)($registro['08'] ?? 0.00);
            $total_actual += (float)($registro['09'] ?? 0.00);
            $total_actual += (float)($registro['10'] ?? 0.00);
            $total_actual += (float)($registro['11'] ?? 0.00);
            $total_actual += (float)($registro['12'] ?? 0.00);

            $total_enero +=      (float)($registro['01'] ?? 0.00);
            $total_febrero +=    (float)($registro['02'] ?? 0.00);
            $total_marzo +=      (float)($registro['03'] ?? 0.00);
            $total_abril +=      (float)($registro['04'] ?? 0.00);
            $total_mayo +=       (float)($registro['05'] ?? 0.00);
            $total_junio +=      (float)($registro['06'] ?? 0.00);
            $total_julio +=      (float)($registro['07'] ?? 0.00);
            $total_agosto +=     (float)($registro['08'] ?? 0.00);
            $total_septiembre += (float)($registro['09'] ?? 0.00);
            $total_octubre +=    (float)($registro['10'] ?? 0.00);
            $total_noviembre +=  (float)($registro['11'] ?? 0.00);
            $total_diciembre +=  (float)($registro['12'] ?? 0.00);

            $enero =      $registro['01'] ?? '-';
            $febrero =    $registro['02'] ?? '-';
            $marzo =      $registro['03'] ?? '-';
            $abril =      $registro['04'] ?? '-';
            $mayo =       $registro['05'] ?? '-';
            $junio =      $registro['06'] ?? '-';
            $julio =      $registro['07'] ?? '-';
            $agosto =     $registro['08'] ?? '-';
            $septiembre = $registro['09'] ?? '-';
            $octubre =    $registro['10'] ?? '-';
            $noviembre =  $registro['11'] ?? '-';
            $diciembre =  $registro['12'] ?? '-';

            $sheet->setCellValue("A{$fila}", $registro['cliente']);
            $sheet->setCellValue("B{$fila}", $registro['asesor']);
            $sheet->setCellValue("C{$fila}", ($registro['comision']/100));
            $sheet->setCellValue("D{$fila}", $this->year);

            $sheet->setCellValue("G{$fila}", $enero);
            $sheet->setCellValue("H{$fila}", $febrero);
            $sheet->setCellValue("I{$fila}", $marzo);
            $sheet->setCellValue("J{$fila}", $abril);
            $sheet->setCellValue("K{$fila}", $mayo);
            $sheet->setCellValue("L{$fila}", $junio);
            $sheet->setCellValue("M{$fila}", $julio);
            $sheet->setCellValue("N{$fila}", $agosto);
            $sheet->setCellValue("O{$fila}", $septiembre);
            $sheet->setCellValue("P{$fila}", $octubre);
            $sheet->setCellValue("Q{$fila}", $noviembre);
            $sheet->setCellValue("R{$fila}", $diciembre);
            $sheet->setCellValue("S{$fila}", $total_actual);

            $fila++;
        }

        $sheet->setCellValue("G{$fila}", $total_enero);
        $sheet->setCellValue("H{$fila}", $total_febrero);
        $sheet->setCellValue("I{$fila}", $total_marzo);
        $sheet->setCellValue("J{$fila}", $total_abril);
        $sheet->setCellValue("K{$fila}", $total_mayo);
        $sheet->setCellValue("L{$fila}", $total_junio);
        $sheet->setCellValue("M{$fila}", $total_julio);
        $sheet->setCellValue("N{$fila}", $total_agosto);
        $sheet->setCellValue("O{$fila}", $total_septiembre);
        $sheet->setCellValue("P{$fila}", $total_octubre);
        $sheet->setCellValue("Q{$fila}", $total_noviembre);
        $sheet->setCellValue("R{$fila}", $total_diciembre);
        $fila++;

        $ultimaFila = $fila > 2 ? $fila - 1 : 1;
        $this->fmt3_styles(sheet: $sheet, ultimaFila: $ultimaFila);

        return [];
    }

    private function crear_sheet_mes_fmt1(string $mes, string $nombre_mes): array
    {
        $sheet = $this->spreadsheet->createSheet();
        $sheet->setTitle(substr($nombre_mes, 0, 31));

        $registros = $this->obtener_data_fmt1(mes: $mes);
        if(errores::$error){
            return (new errores())->error(
                mensaje: 'Error al obtener_data_fmt1',
                data:  $registros
            );
        }

        $this->recorre_registros(registros: $registros,sheet:  $sheet);

        return [];
    }

    private function crear_sheet_mes_fmt2(string $mes, string $nombre_mes): array
    {
        $sheet = $this->spreadsheet->createSheet();
        $sheet->setTitle(substr("{$nombre_mes}(2)", 0, 31));

        $registros = $this->obtener_data_fmt2(mes: $mes);
        if(errores::$error){
            return (new errores())->error(
                mensaje: 'Error al obtener_data_fmt2',
                data:  $registros
            );
        }

        $this->procesa_registros_fmt3(registros: $registros, mes: $mes);

        $this->recorre_registros(registros: $registros,sheet:  $sheet);

        return [];
    }

    private function recorre_registros(array $registros,Worksheet $sheet): void
    {
        foreach ($this->headers_fmt1_fmt2() as $cell => $text) {
            $sheet->setCellValue($cell, $text);
        }

        $fila = 2;

        foreach ($registros as $registro) {

            $porcentaje_comision = $registro['porcentaje_comision'] / 100;
            $subtotal = $registro['sub_total'];
            $monto_dispersion = $subtotal / (1 + $porcentaje_comision);

            $sheet->setCellValue("A{$fila}", $registro['operador']);
            $sheet->setCellValue("B{$fila}", $this->formatea_digitos((int)$registro['numero_cliente']));
            $sheet->setCellValue("C{$fila}", $registro['cliente']);
            $sheet->setCellValue("D{$fila}", ""); // EMPRESA PAGADORA
            $sheet->setCellValue("E{$fila}", $this->formatea_digitos((int)$registro['numero_asesor']));
            $sheet->setCellValue("F{$fila}", $registro['asesor']);
            $sheet->setCellValue("G{$fila}", $registro['periodo']);
            $sheet->setCellValue("H{$fila}", $registro['numero_empleados']); // NUMERO DE EMPLEADOS
            $sheet->setCellValue("I{$fila}", $this->formatea_digitos((int)$registro['numero_factura']));
            $sheet->setCellValue("J{$fila}",
                \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(
                    strtotime($registro['fecha_operacion'])
                )
            );
            $sheet->setCellValue("K{$fila}", $registro['producto']);
            $sheet->setCellValue("L{$fila}", $porcentaje_comision);
            $sheet->setCellValue("M{$fila}", $monto_dispersion); //MONTO DE DISPERSION
            $sheet->setCellValue("N{$fila}", $registro['iva']);
            $sheet->setCellValue("O{$fila}", $registro['total']);

            $fila++;

        }// end foreach ($registros as $registro)

        $ultimaFila = $fila > 2 ? $fila - 1 : 1;
        $this->fmt1_fmt2_styles(sheet: $sheet, ultimaFila: $ultimaFila);
    }

    private function procesa_registros_fmt3(array $registros, string $mes): void
    {

        foreach ($registros as $registro) {
            $numero_cliente = $registro['numero_cliente'];
            $cliente = $registro['cliente'];
            $asesor = $registro['asesor'];
            $porcentaje_comision = $registro['porcentaje_comision'];
            $total = (float)$registro['total'];

            if (!isset($this->datos_fmt3[$numero_cliente])) {
                $this->datos_fmt3[$numero_cliente] = [
                    'cliente' => $cliente,
                    'asesor' => $asesor,
                    'comision' => $porcentaje_comision,
                ];
            }

            if (!isset($this->datos_fmt3[$numero_cliente][$mes])) {
                $this->datos_fmt3[$numero_cliente][$mes] = $total;
            }else{
                $this->datos_fmt3[$numero_cliente][$mes] += $total;
            }

        }
    }

    private function headers_fmt1_fmt2(): array
    {
        return [
            'A1' => 'Nombre Operador',
            'B1' => 'No DE CLIENTE',
            'C1' => 'CLIENTE',
            'D1' => 'EMPRESA PAGADORA',
            'E1' => 'No DE ASESOR',
            'F1' => 'ASESOR',
            'G1' => 'PERIODO',
            'H1' => 'NUMERO DE EMPLEADOS',
            'I1' => 'FAC.',
            'J1' => 'FECHA DE OPERACIÓN',
            'K1' => 'PRODUCTO',
            'L1' => 'COMISION CLIENTE',
            'M1' => 'MONTO DE DISPERSION',
            'N1' => 'IVA',
            'O1' => 'TOTAL FACTURA',
        ];
    }

    private function headers_fmt3(): array
    {
        return [
            'A1' => 'CLIENTE',
            'B1' => 'ASESOR',
            'C1' => 'COMISION',
            'D1' => 'AÑO',
            'E1' => 'BNI',
            'F1' => '',
            'G1' => 'TOTAL FACTURADO ENERO',
            'H1' => 'TOTAL FACTURADO FEBRERO',
            'I1' => 'TOTAL FACTURADO MARZO',
            'J1' => 'TOTAL FACTURADO ABRIL',
            'K1' => 'TOTAL FACTURADO MAYO',
            'L1' => 'TOTAL FACTURADO JUNIO',
            'M1' => 'TOTAL FACTURADO JULIO',
            'N1' => 'TOTAL FACTURADO AGOSTO',
            'O1' => 'TOTAL FACTURADO SEPTIEMBRE',
            'P1' => 'TOTAL FACTURADO OCTUBRE',
            'Q1' => 'TOTAL FACTURADO NOVIEMBRE',
            'R1' => 'TOTAL FACTURADO DICIEMBRE',
        ];
    }

    private function meses(): array
    {
        return [
            '01' => 'ENERO',
            '02' => 'FEBRERO',
            '03' => 'MARZO',
            '04' => 'ABRIL',
            '05' => 'MAYO',
            '06' => 'JUNIO',
            '07' => 'JULIO',
            '08' => 'AGOSTO',
            '09' => 'SEPTIEMBRE',
            '10' => 'OCTUBRE',
            '11' => 'NOVIEMBRE',
            '12' => 'DICIEMBRE',
        ];
    }

    private function fmt1_fmt2_styles(Worksheet $sheet, int $ultimaFila): void
    {
        try {
            $sheet->getStyle('A1:O1')->applyFromArray([
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

        $sheet->getStyle("L2:L{$ultimaFila}")
            ->getNumberFormat()->setFormatCode('0.00%');

        $sheet->getStyle("J2:J{$ultimaFila}")
            ->getNumberFormat()->setFormatCode('yyyy-mm-dd');

        $sheet->getStyle("M2:O{$ultimaFila}")
            ->getNumberFormat()->setFormatCode('"$"#,##0.00');


        $sheet->getRowDimension(1)->setRowHeight(35);
        $sheet->getStyle('A1:O1')->getAlignment()
            ->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER)
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle("B2:B{$ultimaFila}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle("E2:E{$ultimaFila}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle("H2:H{$ultimaFila}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle("J2:J{$ultimaFila}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle("I2:I{$ultimaFila}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getColumnDimension('A')->setWidth(35);
        $sheet->getColumnDimension('B')->setWidth(10);
        $sheet->getColumnDimension('C')->setWidth(35);
        $sheet->getColumnDimension('D')->setWidth(12);
        $sheet->getColumnDimension('E')->setWidth(12);
        $sheet->getColumnDimension('F')->setWidth(25);
        $sheet->getColumnDimension('G')->setWidth(18);
        $sheet->getColumnDimension('H')->setWidth(15);
        $sheet->getColumnDimension('I')->setWidth(15);
        $sheet->getColumnDimension('J')->setWidth(18);
        $sheet->getColumnDimension('K')->setWidth(18);
        $sheet->getColumnDimension('L')->setWidth(18);
        $sheet->getColumnDimension('M')->setWidth(18);
        $sheet->getColumnDimension('N')->setWidth(18);
        $sheet->getColumnDimension('O')->setWidth(18);
    }
    private function fmt3_styles(Worksheet $sheet, int $ultimaFila): void
    {
        try {
            $sheet->getStyle('A1:S1')->applyFromArray([
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

        $sheet->getStyle("C2:C{$ultimaFila}")
            ->getNumberFormat()->setFormatCode('0.00%');

        $sheet->getStyle("G2:S{$ultimaFila}")
            ->getNumberFormat()->setFormatCode('"$"#,##0.00');


        $sheet->getRowDimension(1)->setRowHeight(45);
        $sheet->getStyle('A1:S1')->getAlignment()
            ->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER)
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle("C2:D{$ultimaFila}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle("G2:S{$ultimaFila}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle("G{$ultimaFila}:S{$ultimaFila}")
            ->getFont()
            ->setBold(true);

        $sheet->getStyle("S2:S{$ultimaFila}")
            ->getFont()
            ->setBold(true);


        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getColumnDimension('C')->setWidth(10);
        $sheet->getColumnDimension('D')->setWidth(10);
        $sheet->getColumnDimension('E')->setWidth(10);
        $sheet->getColumnDimension('F')->setWidth(10);

        $sheet->getColumnDimension('G')->setWidth(14);
        $sheet->getColumnDimension('H')->setWidth(14);
        $sheet->getColumnDimension('I')->setWidth(14);
        $sheet->getColumnDimension('J')->setWidth(14);
        $sheet->getColumnDimension('K')->setWidth(14);
        $sheet->getColumnDimension('L')->setWidth(14);
        $sheet->getColumnDimension('M')->setWidth(14);
        $sheet->getColumnDimension('N')->setWidth(14);
        $sheet->getColumnDimension('O')->setWidth(14);
        $sheet->getColumnDimension('P')->setWidth(14);
        $sheet->getColumnDimension('Q')->setWidth(14);
        $sheet->getColumnDimension('R')->setWidth(14);
        $sheet->getColumnDimension('S')->setWidth(14);
    }

    private function formatea_digitos(int $numero): string {
        return str_pad((string)$numero, 7, '0', STR_PAD_LEFT);
    }

    private function obtener_data_fmt1(string $mes): array
    {
        $mes = str_pad($mes, 2, '0', STR_PAD_LEFT);

        $fecha_inicio = "{$this->year}-{$mes}-01";
        $fecha_fin = date("Y-m-t", strtotime($fecha_inicio));
        // t = último día real del mes (28,29,30,31)

        $query = "SELECT
                        COALESCE(operador.descripcion, 'NO ASIGNADO') AS operador,
                        com_cliente.id AS numero_cliente,
                        com_cliente.razon_social AS cliente,
                        asesor.id AS numero_asesor,
                        COALESCE(asesor.descripcion, 'NO ASIGNADO') AS asesor,
                        periodo.descripcion AS periodo,
                        (SELECT COUNT(*) FROM fc_row_layout WHERE fc_row_layout.fc_layout_nom_id = fc_layout_nom.id) AS numero_empleados,
                        fc_factura.id AS numero_factura,
                        fc_factura.fecha AS fecha_operacion,
                        producto.descripcion AS producto,
                        fc_factura.porcentaje_comision_cliente AS porcentaje_comision,
                        fc_factura.total_traslados AS iva,
                        fc_factura.sub_total,
                        fc_factura.total AS total 
                    FROM
                        fc_factura
                        LEFT JOIN com_agente AS operador ON fc_factura.agente_operacion_alta_id = operador.id
                        LEFT JOIN com_sucursal ON fc_factura.com_sucursal_id = com_sucursal.id
                        LEFT JOIN com_cliente ON com_sucursal.com_cliente_id = com_cliente.id
                        LEFT JOIN com_agente AS asesor ON com_cliente.com_agente_asesor_id = asesor.id
                        LEFT JOIN fc_layout_factura ON fc_factura.id = fc_layout_factura.fc_factura_id
                        LEFT JOIN fc_layout_nom ON fc_layout_factura.fc_layout_nom_id = fc_layout_nom.id
                        LEFT JOIN fc_layout_periodo AS periodo ON fc_layout_nom.fc_layout_periodo_id = periodo.id
                        LEFT JOIN com_tipo_producto AS producto ON fc_factura.com_tipo_producto_id = producto.id 
                    WHERE
                    fc_factura.fecha BETWEEN :fecha_inicio
                    AND :fecha_fin
                ORDER BY
                    periodo.id,
                    operador.descripcion ASC";

        try {
            $stmt = $this->link->prepare($query);
            $stmt->execute([
                ':fecha_inicio' => $fecha_inicio,
                ':fecha_fin'    => $fecha_fin
            ]);
            $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return (new errores())->error(
                mensaje: $e->getMessage(),
                data:  $e
            );
        }

        return $rs;
    }

    private function obtener_data_fmt2(string $mes): array
    {
        $mes = str_pad($mes, 2, '0', STR_PAD_LEFT);

        $fecha_inicio = "{$this->year}-{$mes}-01";
        $fecha_fin = date("Y-m-t", strtotime($fecha_inicio));
        // t = último día real del mes (28,29,30,31)

        $query = "{$this->base_query()}
                ORDER BY
                    com_cliente.razon_social ASC";

        try {
            $stmt = $this->link->prepare($query);
            $stmt->execute([
                ':fecha_inicio' => $fecha_inicio,
                ':fecha_fin'    => $fecha_fin
            ]);
            $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return (new errores())->error(
                mensaje: $e->getMessage(),
                data:  $e
            );
        }

        return $rs;
    }

    private function base_query(): string
    {
        return "SELECT
                        COALESCE(operador.descripcion, 'NO ASIGNADO') AS operador,
                        com_cliente.id AS numero_cliente,
                        com_cliente.razon_social AS cliente,
                        asesor.id AS numero_asesor,
                        COALESCE(asesor.descripcion, 'NO ASIGNADO') AS asesor,
                        periodo.descripcion AS periodo,
                        (SELECT COUNT(*) FROM fc_row_layout WHERE fc_row_layout.fc_layout_nom_id = fc_layout_nom.id) AS numero_empleados,
                        fc_factura.id AS numero_factura,
                        fc_factura.fecha AS fecha_operacion,
                        producto.descripcion AS producto,
                        fc_factura.porcentaje_comision_cliente AS porcentaje_comision,
                        fc_factura.total_traslados AS iva,
                        fc_factura.sub_total,
                        fc_factura.total AS total 
                    FROM
                        fc_factura
                        LEFT JOIN com_agente AS operador ON fc_factura.agente_operacion_alta_id = operador.id
                        LEFT JOIN com_sucursal ON fc_factura.com_sucursal_id = com_sucursal.id
                        LEFT JOIN com_cliente ON com_sucursal.com_cliente_id = com_cliente.id
                        LEFT JOIN com_agente AS asesor ON com_cliente.com_agente_asesor_id = asesor.id
                        LEFT JOIN fc_layout_factura ON fc_factura.id = fc_layout_factura.fc_factura_id
                        LEFT JOIN fc_layout_nom ON fc_layout_factura.fc_layout_nom_id = fc_layout_nom.id
                        LEFT JOIN fc_layout_periodo AS periodo ON fc_layout_nom.fc_layout_periodo_id = periodo.id
                        LEFT JOIN com_tipo_producto AS producto ON fc_factura.com_tipo_producto_id = producto.id 
                    WHERE
                    fc_factura.fecha BETWEEN :fecha_inicio
                    AND :fecha_fin";
    }

}