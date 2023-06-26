<?php

namespace gamboamartin\facturacion\models;

use base\orm\_modelo_parent;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\controllers\_pagos;
use PDO;
use stdClass;


class fc_docto_relacionado extends _modelo_parent{
    public function __construct(PDO $link)
    {
        $tabla = 'fc_docto_relacionado';
        $columnas = array($tabla=>false,'fc_pago_pago'=>$tabla,'fc_factura'=>$tabla,'fc_pago'=>'fc_pago_pago',
            'fc_complemento_pago'=>'fc_pago','cat_sat_moneda'=>'fc_factura','cat_sat_obj_imp'=>$tabla);

        $campos_obligatorios = array();

        $fc_factura_uuid = "(SELECT IFNULL(fc_cfdi_sellado.uuid,'') FROM fc_cfdi_sellado WHERE fc_cfdi_sellado.fc_factura_id = fc_factura.id)";

        $columnas_extra['fc_factura_uuid'] = "IFNULL($fc_factura_uuid,'SIN UUID')";

        $atributos_criticos = array('fc_factura_id','equivalencia_dr','num_parcialidad','imp_saldo_ant',
            'imp_pagado','imp_saldo_insoluto','cat_sat_obj_imp_id','fc_pago_pago_id','total_factura','total_factura_tc',
            'imp_pagado_tc','saldo_factura_tc');


        $renombres['com_tipo_cambio_pago']['nombre_original'] = 'com_tipo_cambio';
        $renombres['com_tipo_cambio_pago']['enlace'] = 'fc_pago_pago';
        $renombres['com_tipo_cambio_pago']['key'] = 'id';
        $renombres['com_tipo_cambio_pago']['key_enlace'] = 'com_tipo_cambio_id';

        $renombres['com_tipo_cambio_factura']['nombre_original'] = 'com_tipo_cambio';
        $renombres['com_tipo_cambio_factura']['enlace'] = 'fc_factura';
        $renombres['com_tipo_cambio_factura']['key'] = 'id';
        $renombres['com_tipo_cambio_factura']['key_enlace'] = 'com_tipo_cambio_id';

        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas, columnas_extra: $columnas_extra, renombres: $renombres,
            atributos_criticos: $atributos_criticos);

        $this->NAMESPACE = __NAMESPACE__;
        $this->etiqueta = 'Docto Relacionado';
    }

    public function alta_bd(array $keys_integra_ds = array('descripcion')): array|stdClass
    {

        $valida = $this->valida_alta_docto();
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar',data:  $valida);
        }

        $fc_pago_pago = (new fc_pago_pago(link: $this->link))->registro($this->registro['fc_pago_pago_id']);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener fc_pago_pago',data:  $fc_pago_pago);
        }

        $fc_factura = (new fc_factura(link: $this->link))->registro(registro_id: $this->registro['fc_factura_id']);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener fc_factura',data:  $fc_factura);
        }


        $registro = $this->init_alta(cat_sat_moneda_factura_id: $fc_factura['cat_sat_moneda_id'],
            cat_sat_moneda_pago_id: $fc_pago_pago['cat_sat_moneda_id'],
            com_tipo_cambio_factura_monto: $fc_factura['com_tipo_cambio_monto'],
            com_tipo_cambio_pago_monto: $fc_pago_pago['com_tipo_cambio_monto'],
            fc_factura_id: $this->registro['fc_factura_id']);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integrar registro',data:  $registro);
        }


        $this->registro = $registro;


        $r_alta_bd = parent::alta_bd(keys_integra_ds: $keys_integra_ds); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar',data:  $r_alta_bd);
        }

        $regenera_monto = $this->regenera_pago_pago_monto(fc_pago_pago_id: $registro['fc_pago_pago_id']);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al actualizar monto de pago',data:  $regenera_monto);
        }


        $transacciones = $this->genera_impuestos(fc_docto_relacionado_id: $r_alta_bd->registro_id,
            fc_factura_id:  $registro['fc_factura_id'],fc_pago_pago_id:  $registro['fc_pago_pago_id']);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al transaccionar', data: $transacciones);
        }


        $modelo_traslado = new fc_traslado(link: $this->link);

        $tiene_traslado = (new fc_factura(link: $this->link))->tiene_traslados(modelo_traslado: $modelo_traslado,
            registro_id:  $r_alta_bd->registro['fc_factura_id']);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar si existe traslado',data:  $tiene_traslado);
        }
        if($tiene_traslado){
            $fc_traslado_p_ins['fc_impuesto_p_id'] = $transacciones->fc_impuesto_p_id;
            $r_alta_fc_traslado_p = (new fc_traslado_p(link: $this->link))->alta_registro(registro: $fc_traslado_p_ins);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al insertar impuesto traslado',data:  $r_alta_fc_traslado_p);
            }
        }

        $modelo_retencion = new fc_retenido(link: $this->link);
        $tiene_retencion = (new fc_factura(link: $this->link))->tiene_retenciones(modelo_retencion: $modelo_retencion,
            registro_id:  $r_alta_bd->registro['fc_factura_id']);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar si existe traslado',data:  $tiene_traslado);
        }
        if($tiene_retencion){
            $fc_retencion_p_ins['fc_impuesto_p_id'] = $transacciones->fc_impuesto_p_id;
            $r_alta_fc_retencion_p = (new fc_retencion_p(link: $this->link))->alta_registro(registro: $fc_retencion_p_ins);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al insertar impuesto retenido',data:  $r_alta_fc_retencion_p);
            }
        }

        $regenera = (new _saldos_fc())->regenera_saldos(fc_factura_id:  $this->registro['fc_factura_id'],link: $this->link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al actualizar monto regenera',data:  $regenera);
        }

        return $r_alta_bd;
    }

    private function alta_fc_impuesto_p(int $fc_pago_pago_id){
        $r_alta_impuesto_p = new stdClass();
        $filtro = array();
        $filtro['fc_pago_pago.id'] = $fc_pago_pago_id;

        $existe = (new fc_impuesto_p(link: $this->link))->existe(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar si existe',data:  $existe);
        }
        if(!$existe) {
            $r_alta_impuesto_p = $this->inserta_fc_impuesto_p(fc_pago_pago_id: $fc_pago_pago_id);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al insertar r_alta_impuesto_p', data: $r_alta_impuesto_p);
            }
            $fc_impuesto_p = $r_alta_impuesto_p->registro;
        }
        else{
            $r_fc_impuesto_p = (new fc_impuesto_p(link: $this->link))->filtro_and(filtro: $filtro);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al obtener fc_impuesto_p', data: $r_alta_impuesto_p);
            }
            $fc_impuesto_p = $r_fc_impuesto_p->registros[0];
        }

        return $fc_impuesto_p;
    }

    private function cat_sat_obj_imp_id(array $fc_partidas){
        $cat_sat_obj_imp_id = -1;
        foreach ($fc_partidas as $fc_partida){
            $cat_sat_obj_imp_id = $fc_partida['cat_sat_obj_imp_id'];
        }
        return $cat_sat_obj_imp_id;
    }

    private function cat_sat_obj_imp_id_alta(array $fc_partidas, array $registro){
        $tiene_un_solo_obj_imp = $this->tiene_un_solo_obj_imp(fc_partidas: $fc_partidas);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener tiene_un_solo_obj_imp',data:  $tiene_un_solo_obj_imp);
        }
        $cat_sat_obj_imp_id = $this->cat_sat_obj_imp_id(fc_partidas: $fc_partidas);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener tiene_un_solo_obj_imp',data:  $cat_sat_obj_imp_id);
        }

        if($tiene_un_solo_obj_imp){
            $registro['cat_sat_obj_imp_id'] = $cat_sat_obj_imp_id;
        }
        return $registro;
    }

    private function codigo_alta(array $registro): array
    {
        if(!isset($registro['codigo'])){
            $codigo = $registro['fc_factura_id'];
            $codigo .= $registro['imp_pagado'];
            $codigo .= $registro['fc_pago_pago_id'];
            $codigo .= mt_rand(1000,9999);
            $registro['codigo'] = $codigo;
        }
        return $registro;
    }

    private function descripcion_alta(array $registro): array
    {
        if(!isset($registro['descripcion'])){
            $descripcion = $registro['codigo'];
            $registro['descripcion'] = $descripcion;
        }
        return $registro;
    }

    public function elimina_bd(int $id): array|stdClass
    {

        $registro = $this->registro(registro_id: $id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener registro',data:  $registro);
        }

        $filtro['fc_docto_relacionado.id'] = $id;

        $del_fc_impuesto_dr = (new fc_impuesto_dr(link: $this->link))->elimina_con_filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al eliminar fc_impuesto_dr',data:  $del_fc_impuesto_dr);
        }

        $r_elimina_bd= parent::elimina_bd(id: $id); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al eliminar',data:  $r_elimina_bd);
        }

        $regenera_monto = $this->regenera_pago_pago_monto(fc_pago_pago_id: $registro['fc_pago_pago_id']);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al actualizar monto de pago',data:  $regenera_monto);
        }


        $regenera = (new _saldos_fc())->regenera_saldos(fc_factura_id:  $registro['fc_factura_id'],link: $this->link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al actualizar monto regenera',data:  $regenera);
        }

        $regenera = (new fc_pago_pago(link: $this->link))->regenera_totales(fc_pago_id: $registro['fc_pago_pago_id']);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al actualizar totales',data:  $regenera);
        }

        return $r_elimina_bd;

    }

    /**
     * @param array $fc_factura
     * @param array $fc_pago_pago
     * @return float
     */
    private function equivalencia_dr(array $fc_factura, array $fc_pago_pago): float
    {
        $equivalencia_dr = 1.0;
        if($fc_pago_pago['com_tipo_cambio_cat_sat_moneda_id'] !== $fc_factura['com_tipo_cambio_cat_sat_moneda_id']) {
            $equivalencia_dr = round($fc_pago_pago['com_tipo_cambio_monto'], 6)
                / round($fc_factura['com_tipo_cambio_monto'], 6);
        }

        return round($equivalencia_dr,6);
    }

    private function genera_impuestos(int $fc_docto_relacionado_id, int $fc_factura_id, int $fc_pago_pago_id){
        $modelo_traslado = new fc_traslado(link: $this->link);
        $modelo_retencion = new fc_retenido(link: $this->link);

        $tiene_impuestos = (new fc_factura(link: $this->link))->tiene_impuestos(modelo_traslado: $modelo_traslado,
            modelo_retencion:  $modelo_retencion, registro_id: $fc_factura_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar si existen impuestos',data:  $tiene_impuestos);
        }
        $transacciones = new stdClass();
        if($tiene_impuestos){

            $transacciones = $this->transacciona_impuestos(fc_docto_relacionado_id: $fc_docto_relacionado_id,
                fc_pago_pago_id: $fc_pago_pago_id);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al transaccionar', data: $transacciones);
            }
        }

        $transacciones->tiene_impuestos = $tiene_impuestos;
        return $transacciones;
    }

    private function init_alta(int $cat_sat_moneda_factura_id, int $cat_sat_moneda_pago_id,
                               float $com_tipo_cambio_factura_monto, float $com_tipo_cambio_pago_monto, int $fc_factura_id){

        $modelo_partida = new fc_partida(link: $this->link);
        $fc_partidas = (new fc_factura(link: $this->link))->partidas_base(modelo_partida: $modelo_partida,
            registro_id:  $fc_factura_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener factura',data:  $fc_partidas);
        }

        $registro = $this->codigo_alta(registro: $this->registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integrar codigo',data:  $registro);
        }

        $registro = $this->descripcion_alta(registro: $registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integrar codigo',data:  $registro);
        }

        $importe_pagado = $this->importe_pagado_documento(cat_sat_moneda_factura_id: $cat_sat_moneda_factura_id,
            cat_sat_moneda_pago_id:  $cat_sat_moneda_pago_id,
            com_tipo_cambio_factura_monto:  $com_tipo_cambio_factura_monto,
            com_tipo_cambio_pago_monto:  $com_tipo_cambio_pago_monto,registro:  $registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integrar importe pagado',data:  $importe_pagado);
        }


        $registro['imp_pagado'] = $importe_pagado;

        $registro = $this->cat_sat_obj_imp_id_alta(fc_partidas: $fc_partidas,registro:  $registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener tiene_un_solo_obj_imp',data:  $registro);
        }

        $registro = $this->integra_equivalencia_dr(registro:  $registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integrar equivalencia_dr',data:  $registro);
        }

        $registro = $this->num_parcialidad_alta(registro: $registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener n_parcialidades',data:  $registro);
        }

        $registro = $this->saldos_alta(registro: $registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integrar registro',data:  $registro);
        }


        return $registro;
    }

    private function importe_pagado_documento(int $cat_sat_moneda_factura_id, int $cat_sat_moneda_pago_id, float $com_tipo_cambio_factura_monto,
                                              float $com_tipo_cambio_pago_monto, array $registro){
        $importe_pagado = round($registro['imp_pagado'],2);

        if($cat_sat_moneda_factura_id === 161){

            if($cat_sat_moneda_pago_id !== 161){

                $importe_pagado = round($importe_pagado * $com_tipo_cambio_pago_monto,2);
            }
        }
        if($cat_sat_moneda_factura_id !== 161){
            if($cat_sat_moneda_pago_id === 161){
                $importe_pagado = round($importe_pagado / $com_tipo_cambio_factura_monto,2);
            }
        }
        return $importe_pagado;
    }

    private function inserta_fc_impuesto_dr(int $fc_docto_relacionado_id){
        $fc_impuesto_dr_ins['fc_docto_relacionado_id'] = $fc_docto_relacionado_id;
        $r_fc_impuesto_dr = (new fc_impuesto_dr(link: $this->link))->alta_registro(registro: $fc_impuesto_dr_ins);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar r_fc_impuesto_dr',data:  $r_fc_impuesto_dr);
        }
        return $r_fc_impuesto_dr;
    }

    private function inserta_fc_impuesto_p(int $fc_pago_pago_id){
        $fc_impuesto_p_ins['fc_pago_pago_id'] = $fc_pago_pago_id;
        $r_alta_impuesto_p = (new fc_impuesto_p(link: $this->link))->alta_registro(registro: $fc_impuesto_p_ins);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al insertar r_alta_impuesto_p', data: $r_alta_impuesto_p);
        }
        return $r_alta_impuesto_p;
    }

    private function integra_equivalencia_dr(array $registro){
        $fc_factura = (new fc_factura(link: $this->link))->registro(registro_id: $registro['fc_factura_id']);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener factura',data:  $fc_factura);
        }

        $fc_pago_pago = (new fc_pago_pago(link: $this->link))->registro(registro_id: $registro['fc_pago_pago_id']);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener fc_pago_pago',data:  $fc_pago_pago);
        }

        $equivalencia_dr = $this->equivalencia_dr(fc_factura: $fc_factura,fc_pago_pago:  $fc_pago_pago);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener equivalencia_dr',data:  $equivalencia_dr);
        }

        $registro['equivalencia_dr'] = round($equivalencia_dr,6);
        return $registro;
    }

    public function modifica_bd(array $registro, int $id, bool $reactiva = false, array $keys_integra_ds = array('codigo', 'descripcion')): array|stdClass
    {

        $registro_previo = $this->registro(registro_id: $id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener registro_previo',data:  $registro_previo);
        }


        if(!isset($registro['descripcion'])){

            $codigo = $registro_previo['fc_impuesto_p_codigo'];
            if(isset($registro['codigo'])){
                $codigo = $registro['codigo'];
            }

            $descripcion = $codigo;
            $registro['descripcion'] = $descripcion;
        }
        $r_modifica_bd  = parent::modifica_bd(registro: $registro,id:  $id,reactiva:  $reactiva,
            keys_integra_ds:  $keys_integra_ds); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al modificar',data:  $r_modifica_bd);
        }

        $registro = $this->registro(registro_id: $id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener registro',data:  $registro);
        }

        $regenera_monto = $this->regenera_pago_pago_monto(fc_pago_pago_id: $registro['fc_pago_pago_id']);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al actualizar monto de pago',data:  $regenera_monto);
        }

        return $r_modifica_bd;
    }

    private function num_parcialidad_alta(array $registro){
        $n_parcialidades = (new _pagos())->n_parcialidades(fc_factura_id: $registro['fc_factura_id'],link: $this->link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener n_parcialidades',data:  $n_parcialidades);
        }

        $registro['num_parcialidad'] = $n_parcialidades + 1;
        return $registro;
    }

    private function regenera_pago_pago_monto(int $fc_pago_pago_id){

        $filtro['fc_pago_pago.id'] = $fc_pago_pago_id;

        $r_fc_docto_relacionado = $this->filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener documentos',data:  $r_fc_docto_relacionado);
        }

        $fc_doctos_relacionados = $r_fc_docto_relacionado->registros;

        $monto_pagado_tc = $this->monto_pagado_tc(fc_doctos_relacionados: $fc_doctos_relacionados);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener monto_pagado_tc',data:  $monto_pagado_tc);
        }


        $fc_pago_pago['monto'] = round($monto_pagado_tc,2) ;

        $r_pago_pago = (new fc_pago_pago(link: $this->link))->modifica_bd(registro: $fc_pago_pago,id: $fc_pago_pago_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al actualizar pago pago',data:  $r_pago_pago);
        }
        return $r_pago_pago;
    }

    private function monto_pagado_tc(array $fc_doctos_relacionados){
        $monto_pagado_tc = 0.0;
        foreach ($fc_doctos_relacionados as $fc_docto_relacionado){
            $monto_pagado_tc = $this->acumula_monto_tipo_cambio(fc_docto_relacionado: $fc_docto_relacionado,monto_pagado_tc:  $monto_pagado_tc);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al obtener monto_pagado_tc',data:  $monto_pagado_tc);
            }

        }
        return $monto_pagado_tc;
    }

    private function acumula_monto_tipo_cambio(array $fc_docto_relacionado, float $monto_pagado_tc){
        $tipo_cambio_diferente = $this->tipo_cambio_diferente(fc_docto_relacionado: $fc_docto_relacionado);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener tipo_cambio_diferente',data:  $tipo_cambio_diferente);
        }


        $monto_pagado_tc = $this->integra_monto_pagado_tc(fc_docto_relacionado: $fc_docto_relacionado,
            monto_pagado_tc:  $monto_pagado_tc,tipo_cambio_diferente:  $tipo_cambio_diferente);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener monto_pagado_tc',data:  $monto_pagado_tc);
        }
        return $monto_pagado_tc;
    }

    private function integra_monto_pagado_tc(array $fc_docto_relacionado, float $monto_pagado_tc, bool $tipo_cambio_diferente){
        if($tipo_cambio_diferente){

            $monto_pagado_tc = $this->monto_pagado_tc_dif(fc_docto_relacionado: $fc_docto_relacionado,monto_pagado_tc:  $monto_pagado_tc);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al obtener monto_pagado_tc',data:  $monto_pagado_tc);
            }
        }
        else{
            $monto_pagado_tc += round($fc_docto_relacionado['fc_docto_relacionado_imp_pagado'], 2);
        }


        return $monto_pagado_tc;
    }

    /**
     * Integra el fc_pago_pago_monto
     * @param array $fc_docto_relacionado Documento relacionado
     * @param float $monto_pagado_tc Monto de pago previo
     * @return float|int|array
     * @version 10.119.4
     */
    private function monto_pagado_tc_dif(array $fc_docto_relacionado, float $monto_pagado_tc): float|int|array
    {
        $keys = array('fc_docto_relacionado_imp_pagado','com_tipo_cambio_pago_monto','com_tipo_cambio_factura_monto');
        $valida = $this->validacion->valida_double_mayores_0(keys: $keys,registro:  $fc_docto_relacionado);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar fc_docto_relacionado', data: $valida);
        }

        $keys = array('com_tipo_cambio_factura_cat_sat_moneda_id','com_tipo_cambio_pago_cat_sat_moneda_id');
        $valida = $this->validacion->valida_ids(keys: $keys,registro:  $fc_docto_relacionado);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar fc_docto_relacionado', data: $valida);
        }

        $monto_pago_docto = $this->monto_pago_docto(fc_docto_relacionado: $fc_docto_relacionado);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener monto_pago_docto', data: $monto_pago_docto);
        }

        $monto_pagado_tc+= $monto_pago_docto;

        return $monto_pagado_tc;
    }

    /**
     * PROBAR MONTO CRITICA
     * @param array $fc_docto_relacionado
     * @return array|float
     */
    private function monto_pago_docto(array $fc_docto_relacionado): float|array
    {
        $monto_pago_docto = round($fc_docto_relacionado['fc_docto_relacionado_imp_pagado'], 2);

        $monto_pago_docto = $this->monto_pago_mon_diferentes(fc_docto_relacionado: $fc_docto_relacionado,monto_pago_docto:  $monto_pago_docto);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener monto_pago_docto', data: $monto_pago_docto);
        }
        return $monto_pago_docto;
    }

    /**
     * PROBAR MONTO CRITICA
     * @param array $fc_docto_relacionado
     * @param float $monto_pago_docto
     * @return array|float
     */
    private function monto_pago_mon_diferentes(array $fc_docto_relacionado, float $monto_pago_docto): float|array
    {
        $monto_pago_docto = $this->get_monto_pago_mxn_otro(fc_docto_relacionado: $fc_docto_relacionado,monto_pago_docto:  $monto_pago_docto);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener monto_pago_docto', data: $monto_pago_docto);
        }

        $monto_pago_docto = $this->get_monto_pago_otro_mxn(fc_docto_relacionado: $fc_docto_relacionado,monto_pago_docto:  $monto_pago_docto);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener monto_pago_docto', data: $monto_pago_docto);
        }
        return $monto_pago_docto;
    }

    /**
     * PROBAR MONTO CRITICA
     * @param array $fc_docto_relacionado
     * @param float $monto_pago_docto
     * @return array|float
     */
    private function get_monto_pago_mxn_otro(array $fc_docto_relacionado, float $monto_pago_docto): float|array
    {
        if((int)$fc_docto_relacionado['com_tipo_cambio_factura_cat_sat_moneda_id'] === 161){

            if((int)$fc_docto_relacionado['com_tipo_cambio_pago_cat_sat_moneda_id'] !== 161) {
                $monto_pago_docto = $this->monto_pago_docto_mxn_otro(fc_docto_relacionado: $fc_docto_relacionado,monto_pago_docto:  $monto_pago_docto);
                if(errores::$error){
                    return $this->error->error(mensaje: 'Error al obtener monto_pago_docto', data: $monto_pago_docto);
                }
            }
        }
        return $monto_pago_docto;
    }

    private function get_monto_pago_otro_mxn(array $fc_docto_relacionado, float $monto_pago_docto){
        if((int)$fc_docto_relacionado['com_tipo_cambio_factura_cat_sat_moneda_id'] !== 161){
            if((int)$fc_docto_relacionado['com_tipo_cambio_pago_cat_sat_moneda_id'] === 161) {
                $monto_pago_docto = $this->monto_pago_docto_otro_mxn(fc_docto_relacionado: $fc_docto_relacionado,monto_pago_docto:  $monto_pago_docto);
                if(errores::$error){
                    return $this->error->error(mensaje: 'Error al obtener monto_pago_docto', data: $monto_pago_docto);
                }
            }
        }
        return $monto_pago_docto;
    }

    /**
     * Obtiene el monto del pago de documento relacionado para fc_pago_pago
     * PROBAR MONTO CRITICA
     * @param array $fc_docto_relacionado Documento relacionado registro
     * @param float $monto_pago_docto Monto previamente acumulado
     * @return float
     */
    private function monto_pago_docto_mxn_otro(array $fc_docto_relacionado, float $monto_pago_docto): float
    {
        return round(round($monto_pago_docto, 2) / round($fc_docto_relacionado['com_tipo_cambio_pago_monto'],2),2);
    }

    /**
     * PROBAR MONTO CRITICA
     * @param array $fc_docto_relacionado
     * @param float $monto_pago_docto
     * @return float
     */
    private function monto_pago_docto_otro_mxn(array $fc_docto_relacionado, float $monto_pago_docto): float
    {
        return round(round($monto_pago_docto, 2) * round($fc_docto_relacionado['com_tipo_cambio_factura_monto'],2),2);
    }

    private function saldos_alta(array $registro){

        $imp_saldo_ant = (new _pagos())->saldo(fc_factura_id: $registro['fc_factura_id'],link: $this->link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener imp_saldo_ant',data:  $imp_saldo_ant);
        }
        $registro['imp_saldo_ant'] = $imp_saldo_ant;
        $registro['imp_saldo_insoluto'] = round($imp_saldo_ant,2) - round($registro['imp_pagado'],2);

        if($registro['imp_saldo_insoluto'] < 0.0){
            return $this->error->error(mensaje: 'Error imp_saldo_insoluto es menor a 0',data:  $registro);
        }
        return $registro;
    }

    /**
     * Verifica si el tipo de cambio de pago es diferente al de la factura emitida
     * @param array $fc_docto_relacionado documento a verificar
     * @return bool
     */
    private function tipo_cambio_diferente(array $fc_docto_relacionado): bool
    {
        return (int)$fc_docto_relacionado['com_tipo_cambio_pago_cat_sat_moneda_id'] !==
            (int)$fc_docto_relacionado['com_tipo_cambio_factura_cat_sat_moneda_id'];
    }

    private function tiene_un_solo_obj_imp(array $fc_partidas): bool
    {
        $tiene_un_solo_obj_imp = true;
        $cat_sat_obj_imp_id_anterior = -1;
        foreach ($fc_partidas as $fc_partida){
            $cat_sat_obj_imp_id_actual = $fc_partida['cat_sat_obj_imp_id'];
            if($cat_sat_obj_imp_id_anterior > 0){

                if($cat_sat_obj_imp_id_actual !== $cat_sat_obj_imp_id_anterior){
                    $tiene_un_solo_obj_imp = false;
                    break;
                }
                $cat_sat_obj_imp_id_anterior = $cat_sat_obj_imp_id_actual;

            }
        }
        return $tiene_un_solo_obj_imp;
    }

    private function transacciona_impuestos(int $fc_docto_relacionado_id, int $fc_pago_pago_id){
        $r_fc_impuesto_dr = $this->inserta_fc_impuesto_dr(fc_docto_relacionado_id: $fc_docto_relacionado_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar r_fc_impuesto_dr',data:  $r_fc_impuesto_dr);
        }


        $fc_impuesto_p = $this->alta_fc_impuesto_p(fc_pago_pago_id: $fc_pago_pago_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al insertar r_alta_impuesto_p', data: $fc_impuesto_p);
        }
        $data = new stdClass();
        $data->r_fc_impuesto_dr = $r_fc_impuesto_dr;
        $data->fc_impuesto_p = $fc_impuesto_p;
        $data->fc_impuesto_p_id = $fc_impuesto_p['fc_impuesto_p_id'];
        return $data;
    }

    /**
     * Valida la entrada de datos de un alta
     * @return array|true
     * @version 10.124.4
     */
    private function valida_alta_docto(): bool|array
    {
        $keys = array('fc_factura_id','fc_pago_pago_id');
        $valida = $this->validacion->valida_ids(keys: $keys,registro:  $this->registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar',data:  $valida);
        }

        $keys = array('imp_pagado');
        $valida = $this->validacion->valida_numerics(keys: $keys,row:  $this->registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar',data:  $valida);
        }
        return true;
    }

}