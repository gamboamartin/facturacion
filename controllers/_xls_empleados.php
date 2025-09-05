<?php
namespace gamboamartin\facturacion\controllers;

use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\fc_empleado;
use gamboamartin\facturacion\models\fc_layout_nom;
use gamboamartin\facturacion\models\fc_row_layout;
use PDO;
use stdClass;

class _xls_empleados{

    private function alta_empleado(array $row_empleado, PDO $link): array|stdClass
    {
        $fc_empleado_modelo = new fc_empleado($link);

        $fc_empleado_new = $this->fc_empleado_new($row_empleado);
        if(errores::$error){
            return (new errores())->error('Error al generar row', $fc_empleado_new);
        }
        $alta_em = $fc_empleado_modelo->alta_registro($fc_empleado_new);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al insertar $empleado', data: $alta_em);
        }
        return $alta_em;

    }

    private function asigna_value_row(string $key, array $row_empleado, mixed $value): array
    {
        if(is_null($value)){
            $value = '';
        }
        $value = trim($value);
        $value = strtoupper($value);
        $key_row_emp = _xls_dispersion::$letras[$key];
        $row_empleado[$key_row_emp] = trim($value);
        return $row_empleado;

    }

    final public function carga_empleados(PDO $link, int $fc_layout_nom_id): array
    {
        $fc_layout_nom = (new fc_layout_nom($link))->registro($fc_layout_nom_id,retorno_obj: true);
        if (errores::$error) {
            return (new errores())->error(mensaje: 'Error al obtener layout', data: $fc_layout_nom);
        }


        $rows_empleados = array();

        if($fc_layout_nom->fc_layout_nom_empleados_cargados === 'inactivo') {

            $rows_empleados = $this->verifica_empleados($fc_layout_nom, $link);
            if (errores::$error) {
                return (new errores())->error('Error al generar row', $rows_empleados);
            }

            foreach ($rows_empleados as $row_empleado) {

                if(!isset($row_empleado['TARJETA'])){
                    $row_empleado['TARJETA'] = '';
                }
                if(!isset($row_empleado['CUENTA'])){
                    $row_empleado['CUENTA'] = '';
                }
                if(!isset($row_empleado['CLABE INTERBANCARIA'])){
                    $row_empleado['CLABE INTERBANCARIA'] = '';
                }
                if(!isset($row_empleado['EMAIL'])){
                    $row_empleado['EMAIL'] = '';
                }

                $row_low_new['fc_empleado_id'] = $row_empleado['fc_empleado_id'];
                $row_low_new['fc_layout_nom_id'] = $fc_layout_nom_id;
                $row_low_new['esta_timbrado'] = 'inactivo';
                $row_low_new['neto_depositar'] = $row_empleado['NETO A DEPOSITAR'];
                $row_low_new['banco'] = $row_empleado['BANCO'];
                $row_low_new['cuenta'] = $row_empleado['CUENTA'];
                $row_low_new['clabe'] = $row_empleado['CLABE INTERBANCARIA'];
                $row_low_new['cp'] = $row_empleado['CODIGO POSTAL'];
                $row_low_new['cve_empleado'] = $row_empleado['CLAVE EMPLEADO'];
                $row_low_new['nss'] = $row_empleado['NSS'];
                $row_low_new['rfc'] = $row_empleado['RFC'];
                $row_low_new['curp'] = $row_empleado['CURP'];
                $row_low_new['nombre_completo'] = $row_empleado['NOMBRE COMPLETO'];
                $row_low_new['tarjeta'] = $row_empleado['TARJETA'];
                $row_low_new['email'] = $row_empleado['EMAIL'];
                $row_low_new['fecha_pago'] = $fc_layout_nom->fc_layout_nom_fecha_pago;
                $emision = date('H:i:s');
                $row_low_new['fecha_emision'] = "{$fc_layout_nom->fc_layout_nom_fecha_pago} $emision";

                $alta_row = (new fc_row_layout($link))->alta_registro($row_low_new);
                if (errores::$error) {
                    return (new errores())->error('Error al insertar row', $alta_row);
                }
            }
            $row_upd = array();
            $row_upd['empleados_cargados'] = 'activo';
            $upd_layout_nom = (new fc_layout_nom($link))->modifica_bd($row_upd, $fc_layout_nom_id);
            if (errores::$error) {
                return (new errores())->error(mensaje: 'Error al actualizar layout', data: $upd_layout_nom);
            }
        }

        return $rows_empleados;

    }

    private function fc_empleado_new(array $row_empleado): array
    {
        $fc_empleado_new = array();

        $fc_empleado_new['nombre_completo'] = $row_empleado['NOMBRE COMPLETO'];
        $fc_empleado_new['rfc'] = $row_empleado['RFC'];
        $fc_empleado_new['cp'] = $row_empleado['CODIGO POSTAL'];
        $fc_empleado_new['regimen_fiscal'] = '';
        $fc_empleado_new['clabe'] = $row_empleado['CLABE INTERBANCARIA'];
        $fc_empleado_new['nss'] = $row_empleado['NSS'];
        $fc_empleado_new['curp'] = $row_empleado['CURP'];
        $fc_empleado_new['validado_sat'] = 'inactivo';

        return $fc_empleado_new;

    }

    private function genera_row_emp_val(stdClass $datos, int $recorrido): array
    {
        $row_empleado = $this->row_empleado($datos, $recorrido);
        if(errores::$error){
            return (new errores())->error('Error al generar row', $row_empleado);
        }

        $row_emp_val = $this->row_emp_val($datos, $row_empleado);
        if(errores::$error){
            return (new errores())->error('Error al generar row', $row_emp_val);
        }

        return $row_emp_val;

    }

    private function get_empleados_by_rfc(array $row_empleado, PDO $link): array|stdClass
    {
        $fc_empleado_modelo = new fc_empleado($link);
        $rfc = trim($row_empleado['RFC']);
        $sql = "SELECT *FROM fc_empleado WHERE rfc = '$rfc'";
        $fc_empleados =  $fc_empleado_modelo->ejecuta_consulta($sql);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al obtener $datos', data: $fc_empleados);
        }
        return $fc_empleados;

    }

    private function init_row_cp(array$row_empleado): array
    {
        if(!isset($row_empleado['CODIGO POSTAL'])){
            $row_empleado['CODIGO POSTAL'] = '';
        }
        if(!isset($row_empleado['CP'])){
            $row_empleado['CP'] = '';
        }

        if($row_empleado['CODIGO POSTAL'] === ''){
            if($row_empleado['CP'] !== ''){
                $row_empleado['CODIGO POSTAL'] = $row_empleado['CP'];
            }
        }

        if($row_empleado['CODIGO POSTAL'] === ''){
            $row_empleado['CODIGO POSTAL'] = 'SIN CP';
        }
        return $row_empleado;

    }

    private function row_emp_val(stdClass $datos, array $row_empleado): array
    {
        $row_emp_val = array();
        foreach ($row_empleado as $letra=>$value){
            if(isset($datos->columnas[$letra])) {
                $tag = $datos->columnas[$letra];
                $row_emp_val[$tag] = trim($value);
            }
        }
        return $row_emp_val;

    }

    private function row_empleado(stdClass $datos, int $recorrido): array
    {
        $valores_fila = $datos->hoja->rangeToArray($datos->primer_columna.$recorrido.':'.$datos->ultima_columna.$recorrido, null, true, false);
        $row_xls = $valores_fila[0];

        $row_empleado = array();
        foreach ($row_xls as $key=>$value){
            $row_empleado = $this->asigna_value_row($key,$row_empleado,$value);
            if(errores::$error){
                return (new errores())->error('Error al generar row', $row_empleado);
            }
        }

        return $row_empleado;

    }

    private function row_empleados(stdClass $datos): array
    {
        $recorrido = $datos->fila_inicial;
        $rows_empleados = array();
        while ($recorrido <= $datos->ultima_fila) {
            $row_emp_val = $this->genera_row_emp_val($datos, $recorrido);
            if(errores::$error){
                return (new errores())->error('Error al generar row', $row_emp_val);
            }
            $rows_empleados[] = $row_emp_val;
            $recorrido++;
        }

        return $rows_empleados;

    }

    private function transacciona_empleado(array $row_empleado, PDO $link): array|stdClass
    {
        $row_empleado = $this->init_row_cp($row_empleado);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al obtener $datos', data: $row_empleado);
        }

        $fc_empleados =  $this->get_empleados_by_rfc($row_empleado,$link);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al obtener $datos', data: $fc_empleados);
        }

        if((int)$fc_empleados->n_registros === 0){
            $alta_em = $this->alta_empleado($row_empleado,$link);
            if(errores::$error){
                return (new errores())->error(mensaje: 'Error al insertar $empleado', data: $alta_em);
            }
            $row_empleado['fc_empleado_id'] = $alta_em->registro_id;
        }
        else{

            $row_empleado['fc_empleado_id'] = $fc_empleados->registros[0]['id'];
        }
        return $row_empleado;
    }

    private function verifica_empleados(stdClass $fc_layout_nom, PDO $link): array
    {
        $datos = (new _xls_dispersion())->lee_layout_base(fc_layout_nom: $fc_layout_nom);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al obtener $datos', data: $datos);
        }

        $rows_empleados = $this->row_empleados($datos);
        if(errores::$error){
            return (new errores())->error('Error al generar row', $rows_empleados);
        }

        foreach ($rows_empleados as $indice=>$row_empleado){
            $row_empleado = $this->transacciona_empleado($row_empleado, $link);
            if(errores::$error){
                return (new errores())->error(mensaje: 'Error al insertar $empleado', data: $row_empleado);
            }
            $rows_empleados[$indice] = $row_empleado;
        }

        return $rows_empleados;
    }



}
