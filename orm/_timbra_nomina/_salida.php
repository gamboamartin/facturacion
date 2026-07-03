<?php
namespace gamboamartin\facturacion\models\_timbra_nomina;


use config\generales;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\controllers\_n8n_request;
use gamboamartin\facturacion\controllers\_n8nrequest;
use gamboamartin\facturacion\models\fc_empleado_contacto;
use gamboamartin\facturacion\models\fc_row_layout;
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
            $codigos_error_datos_constancias = generales::$codigos_error_datos_constancias;
            $con_error = true;
            $JSON = json_decode($nomina_json,false);
            $extra_data = '';

            if (in_array($codigo, $codigos_error_datos_constancias)) {
                $fc_row_layout_modelo = new fc_row_layout(link: $link);
                $fc_row_layout_modelo->registro_id = $fc_row_layout_id;
                $rs_row_layout = $fc_row_layout_modelo->obten_data([
                    'fc_row_layout_id','fc_row_layout_fc_empleado_id',
                    'fc_row_layout_rfc','fc_row_layout_cp','fc_row_layout_nombre_completo'
                ]);

                if(!errores::$error){
                    $rfc = $rs_row_layout['fc_row_layout_rfc'];
                    $cp = $rs_row_layout['fc_row_layout_cp'];
                    $nombre_completo = $rs_row_layout['fc_row_layout_nombre_completo'];
                    $fc_empleado_id = $rs_row_layout['fc_row_layout_fc_empleado_id'];

                    $rs_empleado = (new fc_empleado_contacto($link))->filtro_and(
                        columnas: ['fc_empleado_contacto_estatus_telefono','fc_empleado_contacto_telefono'],
                        filtro: ['fc_empleado_contacto.fc_empleado_id' => $fc_empleado_id]
                    );

                    if($rs_empleado->n_registros == 1) {
                        $registro = $rs_empleado->registros[0];

                        $whatsapp = $registro['fc_empleado_contacto_telefono'];
                        $estatus_telefono = $registro['fc_empleado_contacto_estatus_telefono'];

                        if ($estatus_telefono !== 'no validado') {
                            $send_request = (new _n8n_request())->request_constancias(
                                fc_row_layout_id: $fc_row_layout_id,
                                rfc: $rfc,
                                cp: $cp,
                                nombre_completo: $nombre_completo,
                                whatsapp: $whatsapp
                            );

                            $status_request = (int)$send_request['status'];

                            $extra_data ="Empleado notificado";
                            if($status_request !== 200){
                                $extra_data ="Error al notificar al empleado";
                            }

                        }else{
                            $extra_data ="whatsapp: $whatsapp";
                            $extra_data .=" No validado";
                        }
                    }else{
                        $extra_data ="El empleado no tiene whatsapp registrado";
                    }
                }
            }

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
    final public function upd_error(string $codigo, stdClass $rs_timbre, PDO $link, int $fc_row_layout_id): true
    {
        errores::$error = false;
        $upd_err = addslashes("Codigo: $codigo Mensaje: $rs_timbre->mensaje");
        $sql = "UPDATE fc_row_layout SET fc_row_layout.error = '$upd_err' WHERE fc_row_layout.id = $fc_row_layout_id";
        modelo::ejecuta_transaccion($sql, $link);

        errores::$error = false;
        return true;

    }




}
