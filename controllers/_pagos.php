<?php

namespace gamboamartin\facturacion\controllers;

use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\fc_complemento_pago;
use gamboamartin\facturacion\models\fc_docto_relacionado;
use gamboamartin\facturacion\models\fc_factura;
use gamboamartin\facturacion\models\fc_pago;
use gamboamartin\facturacion\models\fc_pago_pago;
use gamboamartin\facturacion\models\fc_pago_total;
use gamboamartin\system\html_controler;
use PDO;
use stdClass;

class _pagos{

    private errores $error;

    public function __construct(){
        $this->error = new errores();
    }

    private function data_docto_relacionado(array $fc_docto_relacionado, fc_factura $fc_factura_modelo, html_controler $html,
                                            int $registro_id, string $tabla): array|stdClass
    {
        $fc_factura = $fc_factura_modelo->registro(registro_id: $fc_docto_relacionado['fc_factura_id']);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener fc_factura',data:  $fc_factura);
        }

        $montos = $this->montos(fc_docto_relacionado: $fc_docto_relacionado,link: $fc_factura_modelo->link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener montos',data:  $montos);
        }

        $params = $this->params_link_elimina(registro_id: $registro_id,tabla:  $tabla);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener params', data:  $params);
        }

        $link_elimina_bd = $html->button_href(accion: 'elimina_bd',etiqueta: 'Elimina',
            registro_id:  $fc_docto_relacionado['fc_docto_relacionado_id'],seccion: 'fc_docto_relacionado',
            style: 'danger', params: $params);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener link_elimina_bd',data:  $link_elimina_bd);
        }

        $data = new stdClass();
        $data->fc_factura = $fc_factura;
        $data->montos = $montos;
        $data->params = $params;
        $data->link_elimina_bd = $link_elimina_bd;

        return $data;

    }

    final public function  data_pagos(controlador_fc_complemento_pago $controlador_fc_complemento_pago,
                                      fc_docto_relacionado $fc_docto_relacionado_modelo, fc_factura $fc_factura_modelo,
                                      array $fc_pago, fc_pago_pago $fc_pago_pago_modelo,
                                      fc_pago_total $fc_pago_total_modelo){
        $fc_pago_totales = $this->fc_pago_totales(fc_pago: $fc_pago,fc_pago_total_modelo:  $fc_pago_total_modelo);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener fc_pago_totales',data:  $fc_pago_totales);
        }

        $fc_pago_pagos = $this->fc_pago_pagos_genera(
            controlador_fc_complemento_pago: $controlador_fc_complemento_pago, fc_pago: $fc_pago,
            fc_docto_relacionado_modelo: $fc_docto_relacionado_modelo, fc_factura_modelo: $fc_factura_modelo,
            fc_pago_pago_modelo: $fc_pago_pago_modelo);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener r_fc_pago_pago',data:  $fc_pago_pagos);
        }

        $data = new stdClass();
        $data->fc_pago_totales = $fc_pago_totales;
        $data->fc_pago_pagos = $fc_pago_pagos;

        return $data;


    }

    final public function data_saldos_fc(int $com_tipo_cambio_pago_cat_sat_moneda_id, float $com_tipo_cambio_pago_monto,
                                         int $fc_complemento_pago_id,
                                         PDO $link, float $total_pagos): array|stdClass
    {

        $fc_complemento_pago = (new fc_complemento_pago(link: $link))->registro(registro_id: $fc_complemento_pago_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener fc_complemento_pago',data:  $fc_complemento_pago);
        }


        $fc_facturas = $this->fc_facturas(fc_complemento_pago: $fc_complemento_pago,link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener fc_facturas',data:  $fc_facturas);
        }

        $saldos = new stdClass();
        $saldos->saldo_total = 0.0;

        foreach ($fc_facturas as $indice_fc_factura=>$fc_factura){

            $saldos = $this->saldos_factura(
                com_tipo_cambio_factura_cat_sat_moneda_id: $fc_factura['cat_sat_moneda_id'],
                com_tipo_cambio_pago_cat_sat_moneda_id: $com_tipo_cambio_pago_cat_sat_moneda_id,
                com_tipo_cambio_factura_monto: $fc_factura['com_tipo_cambio_monto'],
                com_tipo_cambio_pago_monto: $com_tipo_cambio_pago_monto,
                fc_factura: $fc_factura, saldo_total: $saldos->saldo_total);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al obtener saldos',data:  $saldos);
            }


            $saldos->monto_pagado = $fc_factura['fc_factura_monto_saldo_aplicado'];
            $saldos->saldo = $fc_factura['fc_factura_saldo'];

            $fc_facturas[$indice_fc_factura]['fc_factura_monto_pagado'] = $saldos->monto_pagado;
            $fc_facturas[$indice_fc_factura]['fc_factura_saldo'] = $saldos->saldo;
            $fc_facturas[$indice_fc_factura]['total_factura_tc'] = $saldos->total_factura_tc;
            $fc_facturas[$indice_fc_factura]['imp_pagado_tc'] = $saldos->imp_pagado_tc;
            $fc_facturas[$indice_fc_factura]['saldo_factura_tc'] = $saldos->saldo_factura_tc;


            if($fc_factura['fc_factura_saldo']<=0.0){
                unset($fc_facturas[$indice_fc_factura]);
            }
        }


        $saldos->fc_facturas = $fc_facturas;
        return $saldos;
    }

    private function fc_doctos_relacionados(array $fc_doctos_relacionados, array $fc_factura, float $monto_pagado,
                                            int $indice_fc_doctos_relacionados, string $link_elimina_bd, float $saldo): array
    {
        $fc_doctos_relacionados[$indice_fc_doctos_relacionados]['fc_factura_total'] = $fc_factura['fc_factura_total'];
        $fc_doctos_relacionados[$indice_fc_doctos_relacionados]['fc_factura_monto_pagado'] = $monto_pagado;
        $fc_doctos_relacionados[$indice_fc_doctos_relacionados]['fc_factura_saldo'] = $saldo;
        $fc_doctos_relacionados[$indice_fc_doctos_relacionados]['elimina_bd'] = $link_elimina_bd;

        return $fc_doctos_relacionados;
    }

    private function fc_facturas(array $fc_complemento_pago, PDO $link){
        $filtro = array();
        $filtro['com_cliente.id'] = $fc_complemento_pago['com_cliente_id'];
        $filtro['org_empresa.id'] = $fc_complemento_pago['org_empresa_id'];
        $order = array('fc_factura.id'=>'ASC');

        $r_fc_facturas = (new fc_factura(link: $link))->filtro_and(filtro: $filtro, order: $order);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener r_fc_facturas',data:  $r_fc_facturas);
        }



        return $r_fc_facturas->registros;
    }
    private function fc_pago_pagos(array $fc_pago, fc_pago_pago $fc_pago_pago_modelo){
        $filtro = array();
        $filtro['fc_pago.id'] = $fc_pago['fc_pago_id'];
        $r_fc_pago_pago = $fc_pago_pago_modelo->filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener r_fc_pago_pago',data:  $r_fc_pago_pago);
        }
        return $r_fc_pago_pago->registros;
    }

    private function fc_pago_pagos_genera(controlador_fc_complemento_pago $controlador_fc_complemento_pago,
                                          array $fc_pago, fc_docto_relacionado $fc_docto_relacionado_modelo,
                                          fc_factura $fc_factura_modelo, fc_pago_pago $fc_pago_pago_modelo){
        $fc_pago_pagos = $this->fc_pago_pagos(fc_pago: $fc_pago, fc_pago_pago_modelo: $fc_pago_pago_modelo);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener fc_pago_pagos',data:  $fc_pago_pagos);
        }

        foreach ($fc_pago_pagos as $indice_pago_pago=>$fc_pago_pago){
            $controlador_fc_complemento_pago->tiene_pago = true;

            $fc_doctos_relacionados = $this->genera_fc_doctos_relacionados(
                fc_docto_relacionado_modelo: $fc_docto_relacionado_modelo,fc_factura_modelo:  $fc_factura_modelo,
                fc_pago_pago:  $fc_pago_pago,html: $controlador_fc_complemento_pago->html,
                registro_id: $controlador_fc_complemento_pago->registro_id,tabla: $controlador_fc_complemento_pago->tabla);

            if(errores::$error){
                return $this->error->error(mensaje: 'Error al obtener fc_doctos_relacionados',
                    data:  $fc_doctos_relacionados);
            }
            $fc_pago_pagos[$indice_pago_pago]['fc_doctos_relacionados'] = $fc_doctos_relacionados;
        }
        return $fc_pago_pagos;
    }

    final public function fc_pagos(int $fc_complemento_pago_id, fc_pago $fc_pago_modelo){
        $filtro['fc_complemento_pago.id'] = $fc_complemento_pago_id;
        $r_fc_pago = $fc_pago_modelo->filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener r_fc_pago',data:  $r_fc_pago);
        }

        return $r_fc_pago->registros;
    }

    private function fc_pago_totales(array $fc_pago, fc_pago_total $fc_pago_total_modelo){
        $filtro = array();
        $filtro['fc_pago.id'] = $fc_pago['fc_pago_id'];
        $r_fc_pago_total = $fc_pago_total_modelo->filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener r_fc_pago_total',data:  $r_fc_pago_total);
        }
        return $r_fc_pago_total->registros;
    }

    final public function fc_pago_totales_by_complemento(int $fc_complemento_pago_id, fc_pago_pago $fc_pago_pago_modelo){
        $filtro = array();
        $filtro['fc_complemento_pago.id'] = $fc_complemento_pago_id;
        $campos['total_pagos']='fc_pago_pago.monto';
        $r_fc_pago_pago = $fc_pago_pago_modelo->suma(campos: $campos, filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener r_fc_pago_pago',data:  $r_fc_pago_pago);
        }
        return $r_fc_pago_pago['total_pagos'];
    }

    private function genera_fc_doctos_relacionados(fc_docto_relacionado $fc_docto_relacionado_modelo,
                                                   fc_factura $fc_factura_modelo, array $fc_pago_pago,
                                                   html_controler $html, int $registro_id, string $tabla){
        $fc_doctos_relacionados = $this->get_fc_doctos_relacionados(
            fc_docto_relacionado_modelo: $fc_docto_relacionado_modelo,fc_pago_pago:  $fc_pago_pago);

        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener fc_doctos_relacionados',
                data:  $fc_doctos_relacionados);
        }

        foreach ($fc_doctos_relacionados as $indice_fc_doctos_relacionados=>$fc_docto_relacionado){

            $fc_doctos_relacionados = $this->integra_fc_docto_relacionado(
                fc_docto_relacionado: $fc_docto_relacionado, fc_doctos_relacionados: $fc_doctos_relacionados,
                fc_factura_modelo: $fc_factura_modelo, html: $html,
                indice_fc_doctos_relacionados: $indice_fc_doctos_relacionados, registro_id: $registro_id, tabla: $tabla);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al integrar fc_doctos_relacionados',
                    data:  $fc_doctos_relacionados);
            }
        }
        return $fc_doctos_relacionados;
    }

    private function get_fc_doctos_relacionados(fc_docto_relacionado $fc_docto_relacionado_modelo,array $fc_pago_pago){
        $filtro = array();
        $filtro['fc_pago_pago.id'] = $fc_pago_pago['fc_pago_pago_id'];
        $r_fc_docto_relacionado = $fc_docto_relacionado_modelo->filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(
                mensaje: 'Error al obtener r_fc_docto_relacionado',data:  $r_fc_docto_relacionado);
        }

        return $r_fc_docto_relacionado->registros;
    }

    private function integra_fc_docto_relacionado(array $fc_docto_relacionado, array $fc_doctos_relacionados,
                                                  fc_factura $fc_factura_modelo, html_controler $html,
                                                  int $indice_fc_doctos_relacionados, int $registro_id,
                                                  string $tabla): array
    {

        $data_docto = $this->data_docto_relacionado(fc_docto_relacionado: $fc_docto_relacionado,
            fc_factura_modelo: $fc_factura_modelo, html: $html, registro_id: $registro_id, tabla: $tabla);

        if(errores::$error){
            return $this->error->error(
                mensaje: 'Error al integrar fc_doctos_relacionados', data:  $fc_doctos_relacionados);
        }

        $fc_doctos_relacionados = $this->fc_doctos_relacionados(
            fc_doctos_relacionados: $fc_doctos_relacionados,fc_factura:  $data_docto->fc_factura,
            monto_pagado:  $data_docto->montos->monto_pagado,indice_fc_doctos_relacionados:  $indice_fc_doctos_relacionados,
            link_elimina_bd:  $data_docto->link_elimina_bd, saldo: $data_docto->montos->saldo);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integrar fc_doctos_relacionados',
                data:  $fc_doctos_relacionados);
        }
        return $fc_doctos_relacionados;
    }

    private function montos(array $fc_docto_relacionado, PDO $link): array|stdClass
    {
        $monto_pagado = $this->total_pagos(fc_factura_id: $fc_docto_relacionado['fc_factura_id'],link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener monto_pagado',data:  $monto_pagado);
        }

        $saldo = $this->saldo(fc_factura_id: $fc_docto_relacionado['fc_factura_id'], link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener monto_pagado',data:  $monto_pagado);
        }

        $data = new stdClass();
        $data->monto_pagado = $monto_pagado;
        $data->saldo = $saldo;
        return $data;
    }

    final public function n_parcialidades(int $fc_factura_id, PDO $link){
        $filtro['fc_factura.id'] = $fc_factura_id;
        $filtro['fc_docto_relacionado.status'] = 'activo';
        $filtro['fc_factura.status'] = 'activo';
        $filtro['fc_complemento_pago.status'] = 'activo';
        $n_parcialidades = (new fc_docto_relacionado(link: $link))->cuenta(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener parcialidades',data:  $n_parcialidades);
        }
        return $n_parcialidades;

    }

    private function params_link_elimina(int $registro_id, string $tabla): array
    {
        $params['seccion_retorno'] = $tabla;
        $params['accion_retorno'] = 'modifica';
        $params['id_retorno'] = $registro_id;

        return $params;
    }

    final public function saldo(int $fc_factura_id, PDO $link){

        $fc_factura = (new fc_factura(link: $link))->registro(registro_id: $fc_factura_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener factura',data:  $fc_factura);
        }

        $saldo = round($fc_factura['fc_factura_saldo'],2);
        return round($saldo,2);

    }

    private function saldos_factura(float $com_tipo_cambio_factura_cat_sat_moneda_id,
                                    int $com_tipo_cambio_pago_cat_sat_moneda_id, float $com_tipo_cambio_factura_monto,
                                    float $com_tipo_cambio_pago_monto,
                                    array $fc_factura, float $saldo_total): array|stdClass
    {

        $saldo = $fc_factura['fc_factura_saldo'];

        $saldo_total += $saldo;

        $data = new stdClass();
        $data->saldo = $saldo;
        $data->monto_pagado = $fc_factura['fc_factura_monto_saldo_aplicado'];
        $data->saldo_total = $saldo_total;

        $data->total_factura_tc = round($fc_factura['fc_factura_total'], 2);
        $data->imp_pagado_tc = round($fc_factura['fc_factura_monto_saldo_aplicado'] ,2);
        $data->saldo_factura_tc = round($fc_factura['fc_factura_saldo'] ,2);

        if((int)$com_tipo_cambio_factura_cat_sat_moneda_id !== $com_tipo_cambio_pago_cat_sat_moneda_id){

            if((int)$com_tipo_cambio_factura_cat_sat_moneda_id === 161) {
                $data->total_factura_tc = round($fc_factura['fc_factura_total'] / $com_tipo_cambio_pago_monto, 2);
                $data->imp_pagado_tc = round($fc_factura['fc_factura_monto_saldo_aplicado'] / $com_tipo_cambio_pago_monto, 2);
                $data->saldo_factura_tc = round($fc_factura['fc_factura_saldo'] / $com_tipo_cambio_pago_monto, 2);

            }
            if((int)$com_tipo_cambio_factura_cat_sat_moneda_id !== 161) {
                $data->total_factura_tc = round($fc_factura['fc_factura_total'] * $com_tipo_cambio_factura_monto, 2);
                $data->imp_pagado_tc = round($fc_factura['fc_factura_monto_saldo_aplicado'] * $com_tipo_cambio_factura_monto, 2);
                $data->saldo_factura_tc = round($fc_factura['fc_factura_saldo'] * $com_tipo_cambio_factura_monto, 2);
            }
        }


        return $data;
    }

    /**
     * Obtiene los pagos en sumatoria den base a los doctos relacionados
     * @param int $fc_factura_id Factura id
     * @param PDO $link Conexion a la base de datos
     * @return array|float
     */
    private function total_pagos(int $fc_factura_id, PDO $link): float|array
    {

        $filtro['fc_factura.id'] = $fc_factura_id;
        $filtro['fc_docto_relacionado.status'] = 'activo';
        $filtro['fc_factura.status'] = 'activo';
        $filtro['fc_complemento_pago.status'] = 'activo';
        $campos['total_pagos'] = 'fc_docto_relacionado.imp_pagado';
        $r_fc_docto_relacionado = (new fc_docto_relacionado(link: $link))->suma(campos: $campos, filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener pagos',data:  $r_fc_docto_relacionado);
        }
        return round($r_fc_docto_relacionado['total_pagos'],2);

    }

}
