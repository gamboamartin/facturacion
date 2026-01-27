<?php
namespace gamboamartin\facturacion\models;

use config\generales;
use gamboamartin\errores\errores;

class com_agente extends \gamboamartin\comercial\models\com_agente {
     public function obtener_agente_operador_id(): int
     {
         $tipo_agente_id = 0;
         if (isset(generales::$tipo_agente_operador)){
             $tipo_agente_id = generales::$tipo_agente_operador;
         }

        $user_id = (int)$_SESSION['usuario_id'];

        $filtro = [
            'com_agente.adm_usuario_id' => $user_id,
            'com_tipo_agente.id' => $tipo_agente_id,
        ];
        $columnas = ['com_agente_id'];
        $rs_filtro_and = $this->filtro_and(columnas: $columnas,filtro: $filtro);
        if(errores::$error){
            return $this->error->error(
                mensaje: 'Error al buscar adm_user_id en com_agente ',
                data:  $rs_filtro_and
            );
        }

        if ($rs_filtro_and->n_registros === 0) {
            return -1;
        }

        return $rs_filtro_and->registros[0]['com_agente_id'];
     }

     public function obtener_operadores()
     {
         $tipo_agente_id = 0;
         if (isset(generales::$tipo_agente_operador)){
             $tipo_agente_id = generales::$tipo_agente_operador;
         }

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
}