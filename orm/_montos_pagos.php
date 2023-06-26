<?php
namespace gamboamartin\facturacion\models;


use gamboamartin\errores\errores;
use PDO;
use stdClass;

class _montos_pagos{

    private errores $error;

    public function __construct(){
        $this->error = new errores();
    }

    private function acumula_monto_mxn(string $key_monto, array $rows){
        $monto_mxn = 0.0;
        foreach ($rows as $row){
            $monto_mxn = $this->monto_mxn(key_monto: $key_monto, monto_total_mxn: $monto_mxn,row:  $row);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al obtener monto_mxn',data:  $monto_mxn);
            }
        }
        return $monto_mxn;
    }

    final public function fc_pago_total_upd(int $fc_pago_id, PDO $link){
        $monto_total_pagos_mxn = $this->monto_total_pagos_mxn(fc_pago_id: $fc_pago_id,link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener totales',data:  $monto_total_pagos_mxn);
        }


        $fc_pago_total_upd['monto_total_pagos'] = $monto_total_pagos_mxn;

        $total_traslados_base_iva_16_mxn = $this->total_traslados_base_iva_mxn(cat_sat_factor_factor:0.16,
            cat_sat_tipo_factor_descripcion: 'Tasa', fc_pago_id: $fc_pago_id,link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener totales',data:  $total_traslados_base_iva_16_mxn);
        }



        $fc_pago_total_upd['total_traslados_base_iva_16'] = $total_traslados_base_iva_16_mxn;

        $total_traslados_base_iva_08_mxn = $this->total_traslados_base_iva_mxn(cat_sat_factor_factor:0.08,
            cat_sat_tipo_factor_descripcion: 'Tasa', fc_pago_id: $fc_pago_id,link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener totales',data:  $total_traslados_base_iva_16_mxn);
        }

        $fc_pago_total_upd['total_traslados_base_iva_08'] = $total_traslados_base_iva_08_mxn;

        $total_traslados_base_iva_00_mxn = $this->total_traslados_base_iva_mxn(cat_sat_factor_factor:0.00,
            cat_sat_tipo_factor_descripcion: 'Tasa', fc_pago_id: $fc_pago_id,link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener totales',data:  $total_traslados_base_iva_16_mxn);
        }

        $fc_pago_total_upd['total_traslados_base_iva_00'] = $total_traslados_base_iva_00_mxn;

        $total_traslados_impuesto_iva_16_mxn = $this->total_traslados_impuesto_iva_mxn(cat_sat_factor_factor:0.16,
            cat_sat_tipo_factor_descripcion: 'Tasa', fc_pago_id: $fc_pago_id,link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener totales',data:  $total_traslados_impuesto_iva_16_mxn);
        }

        $fc_pago_total_upd['total_traslados_impuesto_iva_16'] = $total_traslados_impuesto_iva_16_mxn;

        $total_traslados_impuesto_iva_08_mxn = $this->total_traslados_impuesto_iva_mxn(cat_sat_factor_factor:0.08,
            cat_sat_tipo_factor_descripcion: 'Tasa', fc_pago_id: $fc_pago_id, link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener totales',data:  $total_traslados_impuesto_iva_08_mxn);
        }

        $fc_pago_total_upd['total_traslados_impuesto_iva_08'] = $total_traslados_impuesto_iva_08_mxn;

        $total_traslados_impuesto_iva_00_mxn = $this->total_traslados_impuesto_iva_mxn(cat_sat_factor_factor:0.00,
            cat_sat_tipo_factor_descripcion: 'Tasa', fc_pago_id: $fc_pago_id, link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener totales',data:  $total_traslados_impuesto_iva_00_mxn);
        }

        $fc_pago_total_upd['total_traslados_impuesto_iva_00'] = $total_traslados_impuesto_iva_00_mxn;


        $total_retenciones_iva_mxn = $this->total_retenciones_mxn(cat_sat_tipo_impuesto_codigo: 'IVA',
            fc_pago_id:  $fc_pago_id,link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener totales',data:  $total_retenciones_iva_mxn);
        }

        $fc_pago_total_upd['total_retenciones_iva'] = $total_retenciones_iva_mxn;

        $total_retenciones_ieps_mxn = $this->total_retenciones_mxn(cat_sat_tipo_impuesto_codigo: 'IEPS',
            fc_pago_id:  $fc_pago_id, link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener totales',data:  $total_retenciones_ieps_mxn);
        }

        $fc_pago_total_upd['total_retenciones_ieps'] = $total_retenciones_ieps_mxn;

        $total_retenciones_isr_mxn = $this->total_retenciones_mxn(cat_sat_tipo_impuesto_codigo: 'ISR',
            fc_pago_id:  $fc_pago_id,link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener totales',data:  $total_retenciones_isr_mxn);
        }

        $fc_pago_total_upd['total_retenciones_isr'] = $total_retenciones_isr_mxn;


        return $fc_pago_total_upd;
    }

    private function filtro_traslado_dr(float $cat_sat_factor_factor, string $cat_sat_tipo_factor_descripcion, int $fc_pago_id): array
    {
        $filtro['fc_pago.id'] = $fc_pago_id;
        $filtro['cat_sat_factor.factor'] = $cat_sat_factor_factor;
        $filtro['cat_sat_tipo_factor.descripcion'] = $cat_sat_tipo_factor_descripcion;
        return $filtro;
    }

    /**
     * Integra un monto basado con el tipo de cambio de operacion en pesos
     * @param string $key_monto Key a integrar
     * @param float $monto_total_mxn Monto total previo para acumular
     * @param array $row Registro en proceso
     * @return float
     */
    private function monto_mxn(string $key_monto, float $monto_total_mxn, array $row): float
    {
        $monto = round($row[$key_monto],2);
        $monto_mxn = round($monto * $row['com_tipo_cambio_monto'],2);
        $monto_total_mxn +=$monto_mxn;
        return $monto_total_mxn;
    }

    private function monto_mxn_traslado(float $cat_sat_factor_factor, string $cat_sat_tipo_factor_descripcion, int $fc_pago_id, string $key_monto, PDO $link){
        $fc_traslados_dr_part = $this->traslados_dr_part(cat_sat_factor_factor: $cat_sat_factor_factor,
            cat_sat_tipo_factor_descripcion:  $cat_sat_tipo_factor_descripcion, fc_pago_id: $fc_pago_id, link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener fc_traslados_dr_part',data:  $fc_traslados_dr_part);
        }

        $monto_mxn = $this->acumula_monto_mxn(key_monto: $key_monto, rows: $fc_traslados_dr_part);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener monto_mxn',data:  $monto_mxn);
        }

        return round($monto_mxn,2);
    }

    /**
     * Obtiene el monto total de pagos aplicados
     * @param int $fc_pago_id Pago Id
     * @param PDO $link
     * @return array|float
     */
    private function monto_total_pagos_mxn(int $fc_pago_id, PDO $link): float|array
    {
        $filtro['fc_pago_id'] = $fc_pago_id;

        $r_fc_pagos = (new fc_pago_pago(link: $link))->filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener r_fc_pagos',data:  $r_fc_pagos);
        }
        $fc_pagos = $r_fc_pagos->registros;


        $monto_mxn = $this->acumula_monto_mxn(key_monto: 'fc_pago_pago_monto', rows: $fc_pagos);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener monto_mxn',data:  $monto_mxn);
        }

        return round($monto_mxn,2);
    }

    private function total_retenciones_mxn(string $cat_sat_tipo_impuesto_codigo, int $fc_pago_id, PDO $link): float|array
    {
        $filtro['fc_pago.id'] = $fc_pago_id;
        $filtro['cat_sat_tipo_impuesto.codigo'] = $cat_sat_tipo_impuesto_codigo;

        $r_fc_retencion_dr_part = (new fc_retencion_dr_part(link: $link))->filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener r_fc_retencion_dr_part',data:  $r_fc_retencion_dr_part);
        }

        $fc_retenciones_dr_part = $r_fc_retencion_dr_part->registros;


        $monto_mxn = $this->acumula_monto_mxn(key_monto: 'fc_retencion_dr_part_importe_dr', rows: $fc_retenciones_dr_part);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener monto_mxn',data:  $monto_mxn);
        }


        return round($monto_mxn,2);
    }

    private function total_traslados_base_iva_mxn(float $cat_sat_factor_factor, string $cat_sat_tipo_factor_descripcion, int $fc_pago_id, PDO $link): float|array
    {

        $monto_mxn = $this->monto_mxn_traslado(cat_sat_factor_factor: $cat_sat_factor_factor,
            cat_sat_tipo_factor_descripcion: $cat_sat_tipo_factor_descripcion, fc_pago_id: $fc_pago_id,
            key_monto: 'fc_traslado_dr_part_base_dr', link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener monto_mxn',data:  $monto_mxn);
        }

        return round($monto_mxn,2);
    }

    private function total_traslados_impuesto_iva_mxn(float $cat_sat_factor_factor, string $cat_sat_tipo_factor_descripcion, int $fc_pago_id, PDO $link): float|array
    {


        $monto_mxn = $this->monto_mxn_traslado(cat_sat_factor_factor: $cat_sat_factor_factor,
            cat_sat_tipo_factor_descripcion: $cat_sat_tipo_factor_descripcion, fc_pago_id: $fc_pago_id,
            key_monto: 'fc_traslado_dr_part_importe_dr', link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener monto_mxn',data:  $monto_mxn);
        }


        return round($monto_mxn,2);
    }


    private function traslados_dr_part(float $cat_sat_factor_factor, string $cat_sat_tipo_factor_descripcion, int $fc_pago_id, PDO $link){
        $filtro = $this->filtro_traslado_dr(cat_sat_factor_factor: $cat_sat_factor_factor,
            cat_sat_tipo_factor_descripcion:  $cat_sat_tipo_factor_descripcion,fc_pago_id:  $fc_pago_id);

        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener filtro',data:  $filtro);
        }

        $r_fc_traslado_dr_part = (new fc_traslado_dr_part(link: $link))->filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener r_fc_traslado_dr_part',data:  $r_fc_traslado_dr_part);
        }

        return $r_fc_traslado_dr_part->registros;
    }

}
