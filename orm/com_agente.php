<?php
namespace gamboamartin\facturacion\models;

use config\generales;
use gamboamartin\errores\errores;

class com_agente extends \gamboamartin\comercial\models\com_agente {
    public function obtener_agente_operador_id(): int
     {
         $tipo_agente_id = generales::$tipo_agente_operador ?? 0;
         return $this->obtener_agente_id( tipo_agente_id: $tipo_agente_id );
     }

    public function obtener_agente_asesor_id(): int
    {
        $tipo_agente_id = generales::$tipo_agente_asesor ?? 0;
        return $this->obtener_agente_id( tipo_agente_id: $tipo_agente_id );
    }

    public function asigna_agente_operador_a_factura(int $agente_operador_id, int $factura_id): array|stdClass
    {
        $consulta = "UPDATE fc_factura SET fc_factura.agente_operacion_alta_id = {$agente_operador_id}
                        WHERE fc_factura.id = {$factura_id}";
        $rs = $this->ejecuta_sql($consulta);
        if(errores::$error){
            return (new errores())->error("Error al asignar agente operador en fc_factura", $rs);
        }

        return [];
    }

    public function asigna_agente_asesor_a_factura(int $agente_asesor_id, int $factura_id): array|stdClass
    {
        $consulta = "UPDATE fc_factura SET fc_factura.agente_operacion_alta_id = {$agente_asesor_id}
                        WHERE fc_factura.id = {$factura_id}";
        $rs = $this->ejecuta_sql($consulta);
        if(errores::$error){
            return (new errores())->error("Error al asignar agente asesor en fc_factura", $rs);
        }

        return [];
    }

    public function obtener_operadores()
     {
         $tipo_agente_id = generales::$tipo_agente_operador ?? 0;

         $filtro = [
             'com_tipo_agente.id' => $tipo_agente_id,
         ];

         $rs_filtro_and = $this->filtro_and(filtro: $filtro);
         if(errores::$error){
             return $this->error->error(
                 mensaje: 'Error al obtener operadores',
                 data:  $rs_filtro_and
             );
         }

         return $rs_filtro_and->registros;
     }

    public function obtener_nombre_asesor(int $com_agente_asesor_id)
     {
        if ($com_agente_asesor_id === -1){
            return 'El cliente no tiene asesor';
        }

         $tipo_agente_id = 0;
         if (isset(generales::$tipo_agente_asesor)){
             $tipo_agente_id = generales::$tipo_agente_asesor;
         }

         $filtro = [
             'com_agente.id' => $com_agente_asesor_id,
             'com_tipo_agente.id' => $tipo_agente_id,
         ];

         $columnas = [];
         $rs_filtro_and = $this->filtro_and(columnas: $columnas,filtro: $filtro);
         if(errores::$error){
             return $this->error->error(
                 mensaje: 'Error al buscar asesor en com_agente ',
                 data:  $rs_filtro_and
             );
         }

         if ((int)$rs_filtro_and->n_registros === 0) {
             return 'El Id de asesor no existe';
         }

         return $rs_filtro_and->registros[0]['com_agente_descripcion'];

     }

    private function obtener_agente_id(int $tipo_agente_id): int
    {
         $user_id = (int) $_SESSION['usuario_id'];
         $filtro = [ 'com_agente.adm_usuario_id' => $user_id, 'com_tipo_agente.id' => $tipo_agente_id, ];

         $rs_filtro_and = $this->filtro_and( columnas: ['com_agente_id'], filtro: $filtro );

         if (errores::$error) {
             return $this->error->error( mensaje: 'Error al buscar usuario en com_agente', data: $rs_filtro_and );
         }

         if ($rs_filtro_and->n_registros === 0) {
             return -1;
         }

         return (int) $rs_filtro_and->registros[0]['com_agente_id'];
     }
}