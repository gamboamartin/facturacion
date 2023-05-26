<?php

namespace gamboamartin\facturacion\models;

use base\orm\_modelo_parent;
use gamboamartin\errores\errores;
use PDO;
use stdClass;


class fc_docto_relacionado extends _modelo_parent{
    public function __construct(PDO $link)
    {
        $tabla = 'fc_docto_relacionado';
        $columnas = array($tabla=>false,'fc_pago_pago'=>$tabla,'fc_factura'=>$tabla,'fc_pago'=>'fc_pago_pago',
            'fc_complemento_pago'=>'fc_pago');

        $campos_obligatorios = array();

        $fc_factura_uuid = "(SELECT IFNULL(fc_cfdi_sellado.uuid,'') FROM fc_cfdi_sellado WHERE fc_cfdi_sellado.fc_factura_id = fc_factura.id)";

        $columnas_extra['fc_factura_uuid'] = "IFNULL($fc_factura_uuid,'SIN UUID')";

        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas, columnas_extra: $columnas_extra);

        $this->NAMESPACE = __NAMESPACE__;
        $this->etiqueta = 'Docto Relacionado';
    }

    public function alta_bd(array $keys_integra_ds = array('descripcion')): array|stdClass
    {

        $valida = $this->valida_alta_docto();
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar',data:  $valida);
        }
        $registro = $this->init_alta(fc_factura_id: $this->registro['fc_factura_id']);
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

        return $r_alta_bd;
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

        $r_elimina_bd= parent::elimina_bd(id: $id); // TODO: Change the autogenerated stub

        $regenera_monto = $this->regenera_pago_pago_monto(fc_pago_pago_id: $registro['fc_pago_pago_id']);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al actualizar monto de pago',data:  $regenera_monto);
        }
        return $r_elimina_bd;

    }

    private function equivalencia_dr(array $fc_factura, array $fc_pago_pago): float
    {
        $equivalencia_dr = round($fc_factura['com_tipo_cambio_monto'],2)
            / round($fc_pago_pago['com_tipo_cambio_monto'],2) ;

        return round($equivalencia_dr,2);
    }

    private function init_alta(int $fc_factura_id){
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

        $registro['equivalencia_dr'] = round($equivalencia_dr,2);
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
        $n_parcialidades = (new fc_factura(link: $this->link))->n_parcialidades(fc_factura_id: $registro['fc_factura_id']);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener n_parcialidades',data:  $n_parcialidades);
        }

        $registro['num_parcialidad'] = $n_parcialidades + 1;
        return $registro;
    }

    private function regenera_pago_pago_monto(int $fc_pago_pago_id){
        $filtro['fc_pago_pago.id'] = $fc_pago_pago_id;
        $campos['monto'] = 'fc_docto_relacionado.imp_pagado';

        $r_fc_docto_relacionados = $this->suma(campos: $campos, filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener pagos',data:  $r_fc_docto_relacionados);
        }

        $monto = round($r_fc_docto_relacionados['monto'],2);

        $fc_pago_pago['monto'] = $monto;
        $r_pago_pago = (new fc_pago_pago(link: $this->link))->modifica_bd(registro: $fc_pago_pago,id: $fc_pago_pago_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al actualizar pago pago',data:  $r_pago_pago);
        }
        return $r_pago_pago;
    }

    private function saldos_alta(array $registro){
        $imp_saldo_ant = (new fc_factura(link: $this->link))->saldo(fc_factura_id: $registro['fc_factura_id']);
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

    /**
     * Valida la entrada de datos de un alta
     * @return array|true
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