<?php
namespace gamboamartin\facturacion\controllers;

use gamboamartin\errores\errores;
use gamboamartin\facturacion\html\fc_layout_nom_html;
use gamboamartin\facturacion\models\fc_empleado;
use gamboamartin\facturacion\models\fc_layout_nom;
use gamboamartin\src\sql;
use gamboamartin\system\links_menu;
use gamboamartin\system\system;
use gamboamartin\template_1\html;
use PDO;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPUnit\Runner\Baseline\Writer;
use stdClass;
use Throwable;

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
            return $this->retorno_error(mensaje: 'Error al generar $input_descripcion',
                data: $input_descripcion,header:  $header,ws:  $ws);
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

    public function carga_empleados(bool $header, bool $ws = false)
    {
        $fc_layout_nom = (new fc_layout_nom($this->link))->registro($this->registro_id,retorno_obj: true);
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al obtener layout', data: $fc_layout_nom, header: $header, ws: $ws);
        }

        $datos = (new _xls_dispersion())->lee_layout_base(fc_layout_nom: $fc_layout_nom);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener $datos', data: $datos,
                header: $header, ws: $ws);
        }


        $recorrido = $datos->fila_inicial;
        $rows_empleados = array();
        while ($recorrido <= $datos->ultima_fila) {
            $valores_fila = $datos->hoja->rangeToArray($datos->primer_columna.$recorrido.':'.$datos->ultima_columna.$recorrido, null, true, false);
            $row_xls = $valores_fila[0];
            $row_empleado = array();
            foreach ($row_xls as $key=>$value){
                if(is_null($value)){
                    $value = '';
                }
                $value = trim($value);
                $value = strtoupper($value);
                $row_empleado[_xls_dispersion::$letras[$key]] = trim($value);
            }

            $row_emp_val = array();
            foreach ($row_empleado as $letra=>$value){
                if(isset($datos->columnas[$letra])) {
                    $tag = $datos->columnas[$letra];
                    $row_emp_val[$tag] = trim($value);
                }
            }
            $rows_empleados[] = $row_emp_val;
            $recorrido++;
        }


        foreach ($rows_empleados as $row_empleado){
           $rfc = trim($row_empleado['RFC']);

            if(!isset($row_empleado['CODIGO POSTAL'])){
                $row_empleado['CODIGO POSTAL'] = '';
            }

           if($row_empleado['CODIGO POSTAL'] === ''){
               $row_empleado['CODIGO POSTAL'] = 'SIN CP';
           }

           $sql = "SELECT *FROM fc_empleado WHERE rfc = '$rfc'";
           $fc_empleados =  $this->modelo->ejecuta_consulta($sql);
           if(errores::$error){
               return $this->retorno_error(mensaje: 'Error al obtener $datos', data: $fc_empleados,
                   header: $header, ws: $ws);
           }
           $fc_empleado_modelo = new fc_empleado($this->link);
           if((int)$fc_empleados->n_registros === 0){

               $fc_empleado_new = array();

               $fc_empleado_new['nombre_completo'] = $row_empleado['NOMBRE COMPLETO'];
               $fc_empleado_new['rfc'] = $row_empleado['RFC'];
               $fc_empleado_new['cp'] = $row_empleado['CODIGO POSTAL'];
               $fc_empleado_new['regimen_fiscal'] = '';
               $fc_empleado_new['clabe'] = $row_empleado['CLABE INTERBANCARIA'];
               $fc_empleado_new['nss'] = $row_empleado['NSS'];
               $fc_empleado_new['curp'] = $row_empleado['CURP'];
               $fc_empleado_new['validado_sat'] = 'inactivo';

               $alta_em = $fc_empleado_modelo->alta_registro($fc_empleado_new);
               if(errores::$error){
                   return $this->retorno_error(mensaje: 'Error al insertar $empleado', data: $alta_em,
                       header: $header, ws: $ws);
               }
           }

        }


        print_r($rows_empleados);exit;

        exit;


    }

    public function genera_dispersion(bool $header, bool $ws = false)
    {
        $fc_layout_nom = (new fc_layout_nom($this->link))->registro($this->registro_id,retorno_obj: true);
        if (errores::$error) {
            return $this->retorno_error(
                mensaje: 'Error al obtener layout', data: $fc_layout_nom, header: $header, ws: $ws);
        }

        $datos = (new _xls_dispersion())->lee_layout_base(fc_layout_nom: $fc_layout_nom);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener $datos', data: $datos,
                header: $header, ws: $ws);
        }

        $xls = (new _xls_dispersion())->write_dispersion(hoja_base: $datos->hoja, layout_dispersion: $datos->layout_dispersion);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar $layout_dispersion', data: $xls,
                header: $header, ws: $ws);
        }
        exit;


    }



}
