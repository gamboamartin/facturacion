<?php
namespace gamboamartin\facturacion\models;

use config\generales;
use gamboamartin\errores\errores;
use gamboamartin\proceso\models\pr_entidad;
use stdClass;

class com_agente extends \gamboamartin\comercial\models\com_agente {

    public function alta_bd(array $keys_integra_ds = array('descripcion')): array|stdClass
    {
        if (isset($_POST['num_asesor'])){
            $temporal_num = (int)$_POST['num_asesor'];
            if ($temporal_num !== -1 && $temporal_num !== 0) {
                $num_asesor = $temporal_num;
            }
            unset($_POST['num_asesor']);
        }

        $rs = parent::alta_bd($keys_integra_ds);
        if (errores::$error) {
            return $this->error->error( mensaje: 'Error en alta_bd de com_agente', data: $rs );
        }

        if (isset($num_asesor)) {
            $com_agente_id = $rs->registro_id;

            $result = $this->actualiza_num_asesor(
                com_agente_asesor_id: $com_agente_id,
                num_asesor: $num_asesor
            );

            if (errores::$error) {
                return $this->error->error( mensaje: 'Error al actualizar el num_asesor', data: $result );
            }
        }

        return $rs;

    }

    public function modifica_bd(array $registro, int $id, bool $reactiva = false, array $keys_integra_ds = array('descripcion')): array|stdClass
    {
        if (isset($_POST['num_asesor'])){
            $temporal_num = (int)$_POST['num_asesor'];
            if ($temporal_num !== -1 && $temporal_num !== 0) {
                $num_asesor = $temporal_num;
            }
            unset($_POST['num_asesor']);
        }

        $rs = parent::modifica_bd($registro, $id, $reactiva, $keys_integra_ds);
        if (errores::$error) {
            return $this->error->error( mensaje: 'Error en modifica_bd de com_agente', data: $rs );
        }

        if (isset($num_asesor)) {

            $result = $this->actualiza_num_asesor(
                com_agente_asesor_id: $id,
                num_asesor: $num_asesor
            );

            if (errores::$error) {
                return $this->error->error( mensaje: 'Error al actualizar el num_asesor', data: $result );
            }
        }

        return $rs;

    }

    public function obtener_agente_operador_id(): int
     {
         $tipo_agente_id = generales::$tipo_agente_operador ?? 0;

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
        $consulta = "UPDATE fc_factura SET fc_factura.agente_asesor_id = {$agente_asesor_id}
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

     private function actualiza_num_asesor(int $com_agente_asesor_id, int $num_asesor)
     {
         $consulta = "UPDATE com_agente SET com_agente.num_asesor = {$num_asesor}
                        WHERE com_agente.id = {$com_agente_asesor_id}";
         $rs = $this->ejecuta_sql($consulta);
         if(errores::$error){
             return (new errores())->error("Error al actualizar el num_asesor en com_agente", $rs);
         }

         return [];
     }

}