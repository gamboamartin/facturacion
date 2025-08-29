<?php
namespace gamboamartin\facturacion\controllers;

use gamboamartin\errores\errores;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use stdClass;
use Throwable;

class _xls_dispersion{

    private array $letras = array();

    public function __construct()
    {
        $letras = array('A','B','D','E','F','G','H','I','J','K','L','M','N','O','P','Q',
            'R','S','T','U','V','W','X','X','Z');

        $letras_b = $letras;

        $letras_bin = $letras;
        foreach ($letras as $letra) {
            foreach ($letras_b as $letra_b) {
                $letras_bin[] = $letra.$letra_b;
            }
        }

        $this->letras = $letras_bin;

    }

    private function autosize(Worksheet $hoja): Worksheet
    {
        $hoja->getColumnDimension('A')->setAutoSize(true);
        $hoja->getColumnDimension('B')->setAutoSize(true);
        $hoja->getColumnDimension('C')->setAutoSize(true);
        $hoja->getColumnDimension('D')->setAutoSize(true);

        return $hoja;

    }

    private function carga_row_layout(Worksheet $hoja, array $layout_dispersion, int $recorrido): array
    {
        $valores_fila = $this->init_valores_fila(hoja: $hoja,recorrido:  $recorrido);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al obtener $valores_fila', data: $valores_fila);
        }

        $row = $this->genera_row_fila($valores_fila);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al obtener $row', data: $row);
        }
        $layout_dispersion[] = $row;

        return $layout_dispersion;

    }



    private function columnas(Worksheet $hoja): array
    {
        $fila_encabezado = $this->file_encabezado($hoja);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al obtener $fila_encabezado', data: $fila_encabezado);
        }
        $valores_fila = $hoja->rangeToArray("A$fila_encabezado:Z$fila_encabezado", null,
            true, false);
        if(!isset($valores_fila[0])){
            return (new errores())->error(mensaje: 'Error al obtener datos de encabezados', data: $valores_fila);
        }


        $row_xls = $valores_fila[0];
        $columnas = array();
        foreach ($row_xls as $key => $value) {
            if(is_null($value)){
                $value = '';
            }
            $value = trim($value);
            $value = strtoupper($value);
            $value = str_replace('á','A',$value);
            $value = str_replace('é','E',$value);
            $value = str_replace('í','I',$value);
            $value = str_replace('ó','O',$value);
            $value = str_replace('ú','U',$value);

            $value = str_replace('Á','A',$value);
            $value = str_replace('É','E',$value);
            $value = str_replace('Í','I',$value);
            $value = str_replace('Ó','O',$value);
            $value = str_replace('Ú','U',$value);

            $value = str_replace('  ',' ',$value);
            $value = str_replace('  ',' ',$value);
            $value = str_replace('  ',' ',$value);
            $value = str_replace('  ',' ',$value);


            if($value !== ''){
                $columnas[$this->letras[$key]] = $value;
            }

        }


        return $columnas;


    }

    private function datos_iniciales(Worksheet $hoja): array|stdClass
    {
        $es_valido = $this->es_valido(hoja: $hoja);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al obtener $es_valido', data: $es_valido);
        }
        if(!$es_valido){
            return (new errores())->error(mensaje: 'Error revise layout', data: $es_valido);
        }

        $fila_inicial = $this->fila_inicial(hoja: $hoja);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al obtener fila_inicial', data: $fila_inicial);
        }

        $ultima_fila = $this->ultima_fila(fila_inicial: $fila_inicial, hoja:  $hoja);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al obtener $ultima_fila', data: $ultima_fila);
        }

        $columnas = $this->columnas($hoja);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al obtener $columnas', data: $columnas);
        }

        $data = new stdClass();
        $data->fila_inicial = $fila_inicial;
        $data->ultima_fila = $ultima_fila;
        $data->columnas = $columnas;

        return $data;

    }

    private function datos_layout(Worksheet $hoja): stdClass
    {
        $cliente = $hoja->rangeToArray("D2:D6", null, true, false);

        if(is_null($cliente[0][0])){
            $cliente[0][0] = '';
        }
        if(is_null($cliente[1][0])){
            $cliente[1][0] = '';
        }
        $name_cliente = strtoupper(trim($cliente[0][0]));
        $periodo = strtoupper(trim($cliente[1][0]));

        $datos = new stdClass();
        $datos->name_cliente = $name_cliente;
        $datos->periodo = $periodo;

        return $datos;

    }
    private function es_valido(Worksheet $hoja): bool|array
    {
        $n_fila = 1;
        $es_valido = false;

        foreach ($hoja->getRowIterator() as $fila) {
            $celda = $hoja->getCell('A' . $fila->getRowIndex());
            $value = $celda->getValue();
            if(is_null($value)){
                $value = '';
            }
            $value = trim($value);
            $value = strtoupper($value);
            if($value === 'CLAVE EMPLEADO'){
                $es_valido = true;
                break;
            }
            $n_fila++;
            if($n_fila >= 200){
                return (new errores())->error(mensaje: 'Error revise layout', data: $n_fila);
            }
        }
        return $es_valido;

    }

    private function estilos_wr(Worksheet $hoja): Worksheet
    {
        $hoja->getStyle('B:B')
            ->getNumberFormat()
            ->setFormatCode('@');

        $hoja->getStyle('C:C')
            ->getNumberFormat()
            ->setFormatCode('0.00');

        return $hoja;

    }

    private function fila_inicial(Worksheet $hoja): array|int
    {
        $fila_encabezado = $this->file_encabezado(hoja: $hoja);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al obtener $fila_encabezado', data: $fila_encabezado);
        }
        return $fila_encabezado + 1;

    }

    private function file_encabezado(Worksheet $hoja): array|int
    {
        $fila_encabezado = 1;
        $n_fila = 1;
        foreach ($hoja->getRowIterator() as $fila) {
            $celda = $hoja->getCell('A' . $fila->getRowIndex());
            $value = $celda->getValue();
            if(is_null($value)){
                $value = '';
            }
            $value = trim($value);
            $value = strtoupper($value);
            if($value === 'CLAVE EMPLEADO'){
                $fila_encabezado = $n_fila;
                break;
            }
            $n_fila++;
            if($n_fila >= 200){
                return (new errores())->error(mensaje: 'Error revise layout', data: $n_fila);
            }
        }
        return $fila_encabezado;

    }

    private function genera_row_fila(array $valores_fila): stdClass
    {
        $nombre = strtoupper(trim($valores_fila[0]['5']));
        $clabe = strtoupper(trim($valores_fila[0]['9']));
        if($clabe === ''){
            $clabe = strtoupper(trim($valores_fila[0]['10']));
        }
        $monto = strtoupper(trim($valores_fila[0]['6']));
        if(is_numeric($monto)){
            $monto = round($monto,2);
        }

        $row = new stdClass();
        $row->nombre = $nombre;
        $row->clabe = $clabe;
        $row->monto = $monto;
        $row->concepto = 'PENSION POR RENTA VITALICIA';

        return $row;

    }

    private function init_valores_fila(Worksheet $hoja, int $recorrido): array
    {
        $valores_fila = $hoja->rangeToArray("A$recorrido:L$recorrido", null, true, false);

        if(is_null($valores_fila[0]['5'])){
            $valores_fila[0]['5'] = '';
        }
        if(is_null($valores_fila[0]['6'])){
            $valores_fila[0]['6'] = '';
        }
        if(is_null($valores_fila[0]['9'])){
            $valores_fila[0]['9'] = '';
        }
        if(is_null($valores_fila[0]['10'])){
            $valores_fila[0]['10'] = '';
        }

        return $valores_fila;

    }

    private function layout_dispersion(Worksheet $hoja, stdClass $ini): array
    {
        $layout_dispersion = array();
        $recorrido = $ini->fila_inicial;

        while ($recorrido <= $ini->ultima_fila) {
            $layout_dispersion = $this->carga_row_layout(hoja: $hoja,layout_dispersion:  $layout_dispersion,recorrido: $recorrido);
            if(errores::$error){
                return (new errores())->error(mensaje: 'Error al obtener $layout_dispersion', data: $layout_dispersion);
            }
            $recorrido++;
        }

        return $layout_dispersion;

    }

    final public function lee_layout_base(stdClass$fc_layout_nom): array|stdClass
    {
        $archivo = $fc_layout_nom->doc_documento_ruta_absoluta;
        $spreadsheet = IOFactory::load($archivo);
        $hoja = $spreadsheet->getActiveSheet();

        $es_valido = $this->es_valido(hoja: $hoja);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al obtener $es_valido', data: $es_valido);
        }
        if(!$es_valido){
            return (new errores())->error(mensaje: 'Error revise layout', data: $es_valido);
        }

        $ini = $this->datos_iniciales(hoja: $hoja);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al obtener $ini', data: $ini);
        }

        $layout_dispersion = $this->layout_dispersion(hoja: $hoja,ini: $ini);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al obtener $layout_dispersion', data: $layout_dispersion);
        }

        $data = new stdClass();
        $data->layout_dispersion = $layout_dispersion;
        $data->hoja = $hoja;
        $data->columnas = $ini->columnas;

        return $data;

    }

    private function title_doc(Worksheet $hoja): array|string
    {
        $datos_ly = $this->datos_layout(hoja: $hoja);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al obtener $datos_ly', data: $datos_ly);
        }


        $fecha = date('YmdHis');
        return "$datos_ly->name_cliente.$datos_ly->periodo.$fecha.xlsx";

    }

    private function ultima_fila(int $fila_inicial, Worksheet $hoja): array|int
    {
        $ultima_fila = -1;
        foreach ($hoja->getRowIterator() as $fila) {
            $valor = $hoja->getCell('A' . $fila->getRowIndex())->getValue();
            if (!empty($valor)) {
                $ultima_fila = $fila->getRowIndex();
            }
        }
        if($ultima_fila <= 0){
            return (new errores())->error(mensaje: 'Error al obtener ultima fila', data: $ultima_fila);
        }

        if($ultima_fila <= $fila_inicial){
            foreach ($hoja->getRowIterator() as $fila) {
                $valor = $hoja->getCell('B' . $fila->getRowIndex())->getValue();
                if (!empty($valor)) {
                    $ultima_fila = $fila->getRowIndex();
                }
            }
        }

        if($ultima_fila <= $fila_inicial){
            foreach ($hoja->getRowIterator() as $fila) {
                $valor = $hoja->getCell('C' . $fila->getRowIndex())->getValue();
                if (!empty($valor)) {
                    $ultima_fila = $fila->getRowIndex();
                }
            }
        }

        if($ultima_fila <= $fila_inicial){
            foreach ($hoja->getRowIterator() as $fila) {
                $valor = $hoja->getCell('D' . $fila->getRowIndex())->getValue();
                if (!empty($valor)) {
                    $ultima_fila = $fila->getRowIndex();
                }
            }
        }
        if($ultima_fila <= $fila_inicial){
            foreach ($hoja->getRowIterator() as $fila) {
                $valor = $hoja->getCell('E' . $fila->getRowIndex())->getValue();
                if (!empty($valor)) {
                    $ultima_fila = $fila->getRowIndex();
                }
            }
        }

        return $ultima_fila;

    }

    private function write_data(Worksheet $hoja, array $layout_dispersion): Worksheet|array
    {
        $hoja = $this->write_keys($hoja);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al obtener $hoja_wr', data: $hoja);
        }

        $hoja = $this->write_rows($hoja,$layout_dispersion);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al obtener $hoja_wr', data: $hoja);
        }
        return $hoja;

    }

    final public function write_dispersion(Worksheet $hoja_base, array $layout_dispersion)
    {
        $xls = new Spreadsheet();
        $hoja_wr = $xls->getActiveSheet();

        $hoja_wr = $this->estilos_wr($hoja_wr);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al obtener $hoja_wr', data: $hoja_wr);
        }

        $hoja_wr = $this->write_data(hoja: $hoja_wr,layout_dispersion: $layout_dispersion);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al obtener $hoja_wr', data: $hoja_wr);
        }

        $hoja_wr = $this->autosize($hoja_wr);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al obtener $hoja_wr', data: $hoja_wr);
        }

        $title = $this->title_doc($hoja_base);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al obtener $title', data: $title);
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'.$title.'"');
        header('Cache-Control: max-age=0');

        try {
            $writer = new Xlsx($xls);
            $writer->save('php://output');
            exit;
        }
        catch (Throwable $e) {
            return (new errores())->error(mensaje: 'Error al guardar file', data: $e);
        }


    }

    private function write_keys(Worksheet $hoja): Worksheet|array
    {
        $keys = array('Nombre','Clabe','Monto','Concepto');
        $hoja->fromArray($keys);


        try {
            $hoja->getStyle('A1:Z1')->applyFromArray([
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN]],
            ]);
        }
        catch (Throwable $e){
            return (new errores())->error(mensaje: 'Error al cargar estilos', data: $e);
        }

        return $hoja;

    }

    private function write_row(Worksheet $hoja_wr, stdClass $row, int $row_ini): Worksheet
    {
        $hoja_wr->setCellValueExplicit("A$row_ini", $row->nombre, DataType::TYPE_STRING);
        $hoja_wr->setCellValueExplicit("B$row_ini", $row->clabe, DataType::TYPE_STRING);
        $hoja_wr->setCellValueExplicit("C$row_ini", $row->monto, DataType::TYPE_NUMERIC);
        $hoja_wr->setCellValueExplicit("D$row_ini", $row->concepto, DataType::TYPE_STRING);

        return $hoja_wr;

    }

    private function write_rows(Worksheet $hoja_wr, array $layout_dispersion): Worksheet|array
    {
        $row_ini = 2;
        foreach ($layout_dispersion as $row){
            $hoja_wr = $this->write_row($hoja_wr, $row, $row_ini);
            if(errores::$error){
                return (new errores())->error(mensaje: 'Error al obtener $hoja_wr', data: $hoja_wr);
            }
            $row_ini++;
        }

        return $hoja_wr;

    }

}
