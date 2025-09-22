<?php
namespace gamboamartin\facturacion\models\_timbra_nomina;


use gamboamartin\errores\errores;
use gamboamartin\modelo\modelo;
use PDO;
use stdClass;

class _salida{

    /**
     * out
     * @param string $codigo
     * @param stdClass $rs
     * @param string $nomina_json
     * @param PDO $link
     * @return array|string
     */
    final public function code_error(string $codigo, stdClass $rs, string $nomina_json, PDO $link, int $fc_row_layout_id)
    {
        if($codigo !== '200'){
            $con_error = true;
            $JSON = json_decode($nomina_json,false);
            $extra_data = '';
            if($codigo === 'CFDI40145'){
                $extra_data ="RFC: {$JSON->Comprobante->Receptor->Rfc}";
                $extra_data .=" Nombre: {$JSON->Comprobante->Receptor->Nombre}";
            }

            if($codigo === 'CFDI40999'){
                $extra_data ="RFC: {$JSON->Comprobante->Receptor->Rfc}";
                $extra_data .=" Nombre: {$JSON->Comprobante->Receptor->Nombre}";
                $extra_data .=" DomicilioFiscalReceptor: CON ERROR";
            }

            if($codigo === '307'){
                $con_error = false;
                errores::$error = false;
            }

            if($con_error){

                $upd = $this->upd_error($codigo, $rs, $link,$fc_row_layout_id);
                return (new errores())->error("Error al timbrar $rs->mensaje Code: $rs->codigo $extra_data", $upd);
            }
        }
        return $nomina_json;

    }

    /**
     * OUT
     * @param string $codigo
     * @param stdClass $rs_timbre
     * @param PDO $link
     * @return true
     */
    private function upd_error(string $codigo, stdClass $rs_timbre, PDO $link, int $fc_row_layout_id): true
    {
        errores::$error = false;
        $sql = "UPDATE fc_row_layout SET fc_row_layout.error = 'Codigo: $codigo Mensaje: $rs_timbre->mensaje' WHERE fc_row_layout.id = $fc_row_layout_id";
        $rs = modelo::ejecuta_transaccion($sql, $link);
        print_r($rs);

        return true;

    }




}
