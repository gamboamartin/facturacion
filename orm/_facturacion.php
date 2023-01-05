<?php
namespace gamboamartin\facturacion\models;
use gamboamartin\errores\errores;
use stdClass;


class _facturacion {
    private errores $error;

    public function __construct(){
        $this->error = new errores();
    }

    /**
     * Genera SQL para calcular el importe de una partida
     * @return string
     * @version 1.34.0
     */
    private function fc_partida_importe(): string
    {
        return "ROUND((ROUND(IFNULL(fc_partida.cantidad,0),2) * ROUND(IFNULL(fc_partida.valor_unitario,0),2)),2)";
    }

    /**
     * Genera SQL para calcular el importe con descuento de una partida
     * @return string
     * @version 1.35.0
     */
    private function fc_partida_importe_con_descuento(): string
    {
        $fc_partida_importe = $this->fc_partida_importe();
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar fc_partida_importe',data:  $fc_partida_importe);
        }

        return "ROUND(($fc_partida_importe - ROUND(IFNULL(fc_partida.descuento,0),2)),2)";
    }


    public function fc_impuesto_importe(string $fc_partida_importe_con_descuento): string
    {
        return "ROUND($fc_partida_importe_con_descuento * ROUND(IFNULL(cat_sat_factor.factor,0),2),2)";
    }


    /**
     * Obtiene SQL para calcular el importe e importe con descuento de una partida
     * @return array|stdClass
     * @version 1.36.0
     */
    public function importes_base(): array|stdClass
    {
        $fc_partida_importe = $this->fc_partida_importe();
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar fc_partida_importe',data:  $fc_partida_importe);
        }

        $fc_partida_importe_con_descuento = $this->fc_partida_importe_con_descuento();
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar fc_partida_importe_con_descuento',data:  $fc_partida_importe_con_descuento);
        }

        $data = new stdClass();
        $data->fc_partida_importe = $fc_partida_importe;
        $data->fc_partida_importe_con_descuento = $fc_partida_importe_con_descuento;

        return $data;
    }

    public function impuesto_partida(string $tabla_impuesto): array|string
    {
        $fc_partida_importe_con_descuento = $this->fc_partida_importe_con_descuento();
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar fc_partida_importe_con_descuento',data:  $fc_partida_importe_con_descuento);
        }
        $fc_impuesto_importe = $this->fc_impuesto_importe($fc_partida_importe_con_descuento);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar fc_partida_importe_con_descuento',data:  $fc_partida_importe_con_descuento);
        }

        $inner_join_cat_sat_factor = "INNER JOIN cat_sat_factor ON cat_sat_factor.id = $tabla_impuesto.cat_sat_factor_id";
        $where = "WHERE $tabla_impuesto.fc_partida_id = fc_partida.id";

        /**
        (SELECT SUM(((fc_partida.cantidad * fc_partida.valor_unitario) - fc_partida.descuento) * cat_sat_factor.factor)
         * FROM fc_traslado INNER JOIN cat_sat_factor ON cat_sat_factor.id = fc_traslado.cat_sat_factor_id
         * WHERE fc_traslado.fc_partida_id = fc_partida.id)
         */

        return "(SELECT ROUND(SUM($fc_impuesto_importe),2) FROM $tabla_impuesto $inner_join_cat_sat_factor $where)";


    }





}
