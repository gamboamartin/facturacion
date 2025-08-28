<?php
namespace gamboamartin\facturacion\controllers;

use gamboamartin\errores\errores;
use gamboamartin\facturacion\html\fc_layout_nom_html;
use gamboamartin\facturacion\models\fc_layout_nom;
use gamboamartin\system\links_menu;
use gamboamartin\system\system;

use gamboamartin\template_1\html;
use PDO;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Xml\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use stdClass;

class controlador_fc_layout_nom extends system{

    public stdClass|array $keys_selects = array();

    public function __construct(PDO $link, html $html = new html(), stdClass $paths_conf = new stdClass()){
        $modelo = new fc_layout_nom(link: $link);
        $html_ = new fc_layout_nom_html(html: $html);
        $obj_link = new links_menu(link: $link, registro_id:  $this->registro_id);

        $this->rows_lista = array('id', 'codigo', 'descripcion');

        parent::__construct(html:$html_, link: $link,modelo:  $modelo, obj_link: $obj_link,
            paths_conf: $paths_conf);

    }

    public function alta(bool $header, bool $ws = false): array|string
    {

        $alta = parent::alta($header, $ws);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar input', data: $alta,header:  $header,ws:  $ws);
        }
        $this->inputs = new stdClass();

        $documento = $this->html->input_file(cols: 12, name: 'documento', row_upd: new stdClass(), value_vacio: false,
            place_holder: 'Layout', required: false);
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al obtener inputs', data: $documento, header: $header, ws: $ws);
        }

        $this->inputs->documento = $documento;


        $input_descripcion= $this->html->input_descripcion(cols: 12,row_upd: new stdClass(),value_vacio: false);

        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar $input_descripcion', data: $input_descripcion,header:  $header,ws:  $ws);
        }
        $this->inputs->descripcion = $input_descripcion;

        return $alta;
    }

    public function alta_bd(bool $header, bool $ws = false): array|stdClass
    {

        $r_alta = parent::alta_bd($header, $ws);
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al guardar', data: $r_alta, header: $header, ws: $ws);
        }
        return $r_alta;

    }

    public function genera_dispersion(bool $header, bool $ws = false)
    {
        $fc_layout_nom = (new fc_layout_nom($this->link))->registro($this->registro_id,retorno_obj: true);
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al obtener layout', data: $fc_layout_nom, header: $header, ws: $ws);
        }

        $archivo = $fc_layout_nom->doc_documento_ruta_absoluta;
        $spreadsheet = IOFactory::load($archivo);
        $hoja = $spreadsheet->getActiveSheet();

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
                return $this->retorno_error(
                    mensaje: 'Error revise layout', data: $n_fila, header: $header, ws: $ws);
            }
        }

        $fila_inicial = $fila_encabezado + 1;
        $ultima_fila = null;
        foreach ($hoja->getRowIterator() as $fila) {
            $valor = $hoja->getCell('A' . $fila->getRowIndex())->getValue();
            if (!empty($valor)) {
                $ultima_fila = $fila->getRowIndex();
            }
        }

        $layout_dispersion = array();

        $recorrido = $fila_inicial;

        while ($recorrido <= $ultima_fila) {
            $valores_fila = $hoja->rangeToArray("A$recorrido:L$recorrido", null, true, false);
            $row = new stdClass();
            if(is_null($valores_fila[0]['5'])){
                $valores_fila[0]['5'] = '';
            }
            if(is_null($valores_fila[0]['9'])){
                $valores_fila[0]['9'] = '';
            }
            if(is_null($valores_fila[0]['6'])){
                $valores_fila[0]['6'] = '';
            }
            $nombre = strtoupper(trim($valores_fila[0]['5']));
            $clabe = strtoupper(trim($valores_fila[0]['9']));
            $monto = strtoupper(trim($valores_fila[0]['6']));

            $monto = round($monto,2);

            if($clabe === ''){
                if(is_null($valores_fila[0]['10'])){
                    $valores_fila[0]['10'] = '';
                }
                $clabe = strtoupper(trim($valores_fila[0]['10']));
            }

            $row->nombre = $nombre;
            $row->clabe = $clabe;
            $row->monto = $monto;
            $row->concepto = 'PENSION POR RENTA VITALICIA';

            $layout_dispersion[] = $row;

            $recorrido++;

        }

        $cliente = $hoja->rangeToArray("D2:D6", null, true, false);

        if(is_null($cliente[0][0])){
            $cliente[0][0] = '';
        }
        if(is_null($cliente[1][0])){
            $cliente[1][0] = '';
        }

        $name_cliente = strtoupper(trim($cliente[0][0]));
        $periodo = strtoupper(trim($cliente[1][0]));
        $fecha = date('YmdHis');
        $title = "$name_cliente.$periodo.$fecha.xlsx";

        $keys = array('NOMBRE','CLABE','MONTO','CONCEPTO');

        $xls = new Spreadsheet();
        $hoja = $xls->getActiveSheet();

        $hoja->getStyle('B:B')
            ->getNumberFormat()
            ->setFormatCode('@');

        $hoja->getStyle('C:C')
            ->getNumberFormat()
            ->setFormatCode('0.00');

        $hoja->fromArray($keys);

        $row_ini = 2;
        foreach ($layout_dispersion as $row){

            $hoja->setCellValueExplicit("A$row_ini", $row->nombre, DataType::TYPE_STRING);
            $hoja->setCellValueExplicit("B$row_ini", $row->clabe, DataType::TYPE_STRING);
            $hoja->setCellValueExplicit("C$row_ini", $row->monto, DataType::TYPE_NUMERIC);
            $hoja->setCellValueExplicit("D$row_ini", $row->concepto, DataType::TYPE_STRING);

            $row_ini++;
        }

        $hoja->getStyle('A1:Z1')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        $hoja->getColumnDimension('A')->setAutoSize(true);
        $hoja->getColumnDimension('B')->setAutoSize(true);
        $hoja->getColumnDimension('C')->setAutoSize(true);
        $hoja->getColumnDimension('D')->setAutoSize(true);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'.$title.'"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($xls);
        $writer->save('php://output');
        exit;


    }


}
