<?php

namespace gamboamartin\facturacion\models;

use gamboamartin\errores\errores;
use stdClass;

class _facturacion
{
    private errores $error;

    public function __construct()
    {
        $this->error = new errores();
    }

    /**
     * Genera SQL para calcular el importe de una partida
     * @param string $name_entidad_partida Nombre de la entidad de la partida fc_partida, fc_partida_nc, fc_partida_xxx
     * @return string|array
     */
    private function fc_partida_importe(string $name_entidad_partida): string|array
    {
        $name_entidad_partida = trim($name_entidad_partida);
        if ($name_entidad_partida === '') {
            return $this->error->error(mensaje: 'Error al name_entidad_partida esta vacio', data: $name_entidad_partida);
        }

        return "($name_entidad_partida.sub_total_base)";
    }

    /**
     * Genera SQL para calcular el importe con descuento de una partida
     * @param string $name_entidad_partida Nombre de la entidad de la partida fc_partida, fc_partida_nc, fc_partida_xxx
     * @return string|array
     */
    private function fc_partida_importe_con_descuento(string $name_entidad_partida): string|array
    {
        $name_entidad_partida = trim($name_entidad_partida);
        if ($name_entidad_partida === '') {
            return $this->error->error(mensaje: 'Error al name_entidad_partida esta vacio', data: $name_entidad_partida);
        }


        return "($name_entidad_partida.sub_total)";
    }

    /**
     * Obtiene SQL para calcular el impuesto de una partida
     * @param string $fc_partida_importe_con_descuento Partida a validar
     * @return string
     * @version 1.37.0
     */
    public function fc_impuesto_importe(string $fc_partida_importe_con_descuento): string
    {
        return "ROUND($fc_partida_importe_con_descuento * ROUND(IFNULL(cat_sat_factor.factor,0),4),2)";
    }

    /**
     * Obtiene SQL para calcular el importe e importe con descuento de una partida
     * @param string $name_entidad_partida Nombre de la entidad de la partida fc_partida, fc_partida_nc, fc_partida_xxx
     * @return array|stdClass
     * @version 1.36.0
     */
    public function importes_base(string $name_entidad_partida): array|stdClass
    {
        $name_entidad_partida = trim($name_entidad_partida);
        if ($name_entidad_partida === '') {
            return $this->error->error(mensaje: 'Error al name_entidad_partida esta vacio', data: $name_entidad_partida);
        }
        $fc_partida_entidad_importe = $this->fc_partida_importe(name_entidad_partida: $name_entidad_partida);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al generar fc_partida_entidad_importe',
                data: $fc_partida_entidad_importe);
        }

        $fc_partida_entidad_importe_con_descuento = $this->fc_partida_importe_con_descuento(
            name_entidad_partida: $name_entidad_partida);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al generar fc_partida_entidad_importe_con_descuento',
                data: $fc_partida_entidad_importe_con_descuento);
        }

        $data = new stdClass();
        $data->fc_partida_entidad_importe = $fc_partida_entidad_importe;
        $data->fc_partida_entidad_importe_con_descuento = $fc_partida_entidad_importe_con_descuento;


        return $data;
    }

    /**
     * Obtiene SQL para calcular los impuestos de una partida en base al tipo de impuesto
     * @param string $name_entidad_partida
     * @param string $tabla_impuesto Tipo de impuesto a evaluar
     * @return array|string
     */
    public function impuesto_partida(string $name_entidad_partida, string $tabla_impuesto): array|string
    {
        $name_entidad_partida = trim($name_entidad_partida);
        if ($name_entidad_partida === '') {
            return $this->error->error(mensaje: 'Error al name_entidad_partida esta vacio', data: $name_entidad_partida);
        }
        $tabla_impuesto = trim($tabla_impuesto);
        if ($tabla_impuesto === '') {
            return $this->error->error(mensaje: 'Error al tabla_impuesto esta vacio', data: $tabla_impuesto);
        }
        $fc_partida_importe_con_descuento = $this->fc_partida_importe_con_descuento(
            name_entidad_partida: $name_entidad_partida);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al generar fc_partida_importe_con_descuento',
                data: $fc_partida_importe_con_descuento);
        }

        $fc_impuesto_importe = $this->fc_impuesto_importe($fc_partida_importe_con_descuento);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al generar fc_partida_importe_con_descuento',
                data: $fc_partida_importe_con_descuento);
        }

        $id_partida = $name_entidad_partida.'_id';

        $inner_join_cat_sat_factor = "INNER JOIN cat_sat_factor ON cat_sat_factor.id = $tabla_impuesto.cat_sat_factor_id";
        $where = "WHERE $tabla_impuesto.$id_partida = $name_entidad_partida.id";


        return "(SELECT ROUND(SUM($fc_impuesto_importe),2) FROM $tabla_impuesto $inner_join_cat_sat_factor $where)";
    }

}
