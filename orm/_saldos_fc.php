<?php

namespace gamboamartin\facturacion\models;

use gamboamartin\errores\errores;
use PDO;

class _saldos_fc{

    private errores $error;

    public function __construct(){
        $this->error = new errores();
    }

    private function fc_doctos_relacionados(int $fc_factura_id, PDO $link){
        if($fc_factura_id <= 0){
            return $this->error->error(mensaje: 'Error fc_factura_id debe ser mayor a 0',data:  $fc_factura_id);
        }
        $filtro['fc_factura.id'] = $fc_factura_id;
        $r_fc_docto_relacionado = (new fc_docto_relacionado(link: $link))->filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener relaciones de r_fc_docto_relacionado',data:  $r_fc_docto_relacionado);
        }
        return $r_fc_docto_relacionado->registros;
    }

    /**
     * Obtiene los pagos aplicados basados en una relacion de nota de credito
     * @param int $fc_factura_id Factura a obtener notas de creditos aplicadas
     * @param PDO $link Conexion a la base de datos
     * @return array
     */
    private function fc_nc_rels(int $fc_factura_id, PDO $link): array
    {
        if($fc_factura_id <= 0){
            return $this->error->error(mensaje: 'Error fc_factura_id debe ser mayor a 0',data:  $fc_factura_id);
        }
        $filtro['fc_factura.id'] = $fc_factura_id;
        $filtro['fc_nota_credito.aplica_saldo'] = 'activo';
        $r_fc_nc_rel = (new fc_nc_rel(link: $link))->filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener relaciones de notas de credito',data:  $r_fc_nc_rel);
        }
        return $r_fc_nc_rel->registros;
    }

    private function get_pagos_cp(int $fc_factura_id, PDO $link){
        if($fc_factura_id <= 0){
            return $this->error->error(mensaje: 'Error fc_factura_id debe ser mayor a 0',data:  $fc_factura_id);
        }

        $fc_doctos_relacionados = $this->fc_doctos_relacionados(fc_factura_id: $fc_factura_id, link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener relaciones de fc_doctos_relacionados',
                data:  $fc_doctos_relacionados);
        }

        $total_pagos = $this->total_pagos(key_aplica_saldo: 'fc_complemento_pago_aplica_saldo',
            key_monto:  'fc_docto_relacionado_imp_pagado',rows:  $fc_doctos_relacionados);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener total_pagos',data:  $total_pagos);
        }

        return $total_pagos;

    }

    private function get_pagos_nc(int $fc_factura_id, PDO $link){
        if($fc_factura_id <= 0){
            return $this->error->error(mensaje: 'Error fc_factura_id debe ser mayor a 0',data:  $fc_factura_id);
        }
        $fc_nc_rels = $this->fc_nc_rels(fc_factura_id: $fc_factura_id,link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener fc_nc_rels',data:  $fc_nc_rels);
        }

        $total_pagos = $this->total_pagos(key_aplica_saldo: 'fc_nota_credito_aplica_saldo',
            key_monto:  'fc_nc_rel_monto_aplicado_factura',rows:  $fc_nc_rels);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener total_pagos',data:  $total_pagos);
        }

        return $total_pagos;

    }

    public function regenera_saldos(int $fc_factura_id, PDO $link){

        $fc_factura_modelo = new fc_factura(link: $link);
        $fc_factura_modelo->modelo_etapa = new fc_factura_etapa(link: $link);

        $fc_factura = $fc_factura_modelo->registro(registro_id: $fc_factura_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener factura',data:  $fc_factura);
        }

        $total_pagos_nc = $this->get_pagos_nc(fc_factura_id: $fc_factura_id, link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener total pagos nc',data:  $total_pagos_nc);
        }

        $total_pagos_cp = $this->get_pagos_cp(fc_factura_id: $fc_factura_id, link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener total pagos cp',data:  $total_pagos_cp);
        }

        $monto_saldo_aplicado = $total_pagos_cp + $total_pagos_nc;

        $fc_factura_upd['monto_pago_nc'] = $total_pagos_nc;
        $fc_factura_upd['monto_pago_cp'] = $total_pagos_cp;
        $fc_factura_upd['monto_saldo_aplicado'] = $monto_saldo_aplicado;
        $fc_factura_upd['saldo'] = $fc_factura['fc_factura_total'] - $monto_saldo_aplicado;

        $upd = $fc_factura_modelo->modifica_bd(registro: $fc_factura_upd,id:  $fc_factura_id,
            verifica_permite_transaccion: false);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al actualizar saldos',data:  $upd);
        }
        return $upd;
    }

    /**
     * Acumula el total de pagos basados en un conjunto de entidades de relacion como doctos relacionados o nc rels
     * @param string $key_aplica_saldo key de entidad de aplicacion de saldo
     * @param string $key_monto  Key del monto en la relacion
     * @param array $rows Registros
     * @return float
     */
    private function total_pagos(string $key_aplica_saldo, string $key_monto, array $rows): float
    {
        $total_pagos = 0.0;
        foreach ($rows as $row){
            if($row[$key_aplica_saldo] === 'activo') {
                $total_pagos += round($row[$key_monto], 2);
            }
        }
        return $total_pagos;
    }

}
