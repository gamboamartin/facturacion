<?php

namespace gamboamartin\facturacion\models;

use base\orm\_modelo_parent_sin_codigo;
use gamboamartin\errores\errores;
use PDO;
use stdClass;


class fc_email_cp extends _modelo_parent_sin_codigo
{
    public function __construct(PDO $link)
    {
        $tabla = 'fc_email_cp';
        $columnas = array($tabla => false, 'fc_complemento_pago' => $tabla, 'com_email_cte' => $tabla,
            'com_sucursal' => 'fc_complemento_pago','com_cliente'=>'com_sucursal');
        $campos_obligatorios = array('fc_complemento_pago_id', 'com_email_cte_id');

        $columnas_extra = array();



        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas,  columnas_extra: $columnas_extra);

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'Factura Email';
    }

    public function alta_bd(array $keys_integra_ds = array('descripcion')): array|stdClass
    {

        $keys = array('fc_complemento_pago_id');
        $valida = $this->validacion->valida_ids(keys: $keys,registro:  $this->registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar registro',data:  $valida);
        }



        $fc_complemento_pago = (new fc_complemento_pago(link: $this->link))->registro(registro_id: $this->registro['fc_complemento_pago_id']);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener fc_complemento_pago',data:  $fc_complemento_pago);
        }


        if(!isset($this->registro['com_email_cte_id'])){
            if(isset($this->registro['descripcion'])){
                $com_email_cte_ins['descripcion'] = $this->registro['descripcion'];
                $com_email_cte_ins['com_cliente_id'] = $fc_complemento_pago['com_cliente_id'];
                $r_alta_com_email_cte = (new com_email_cte(link: $this->link))->alta_registro(registro: $com_email_cte_ins);
                if(errores::$error){
                    return $this->error->error(mensaje: 'Error al insertar email',data:  $r_alta_com_email_cte);
                }
                $com_email_cte_id = $r_alta_com_email_cte->registro_id;
                $this->registro['com_email_cte_id'] = $com_email_cte_id;
                unset($this->registro['descripcion']);
            }

        }

        $com_email_cte = (new com_email_cte(link: $this->link))->registro(registro_id: $this->registro['com_email_cte_id']);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener factura',data:  $com_email_cte);
        }

        if(!isset($this->registro['descripcion'])){
            $descripcion = $fc_complemento_pago['fc_complemento_pago_folio'].' '.$com_email_cte['com_email_cte_descripcion'];
            $this->registro['descripcion'] = $descripcion;
        }
        $r_alta_bd = parent::alta_bd(keys_integra_ds: $keys_integra_ds); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar',data:  $r_alta_bd);
        }
        return $r_alta_bd;

    }

    final public function status(string $campo, int $registro_id): array|stdClass
    {
        $r_status = parent::status(campo: $campo,registro_id:  $registro_id); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al transaccionar status',data:  $r_status);
        }
        $fc_email_cp = $this->registro(registro_id: $registro_id, retorno_obj: true);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener fc_email',data:  $fc_email_cp);
        }
        $com_email_cte['status'] = $fc_email_cp->fc_email_cp_status;
        $r_com_email_cte = (new com_email_cte(link: $this->link))->modifica_bd(registro: $com_email_cte,
            id: $fc_email_cp->com_email_cte_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al modificar com_email_cte',data:  $r_com_email_cte);
        }

        return $r_status;
    }


}