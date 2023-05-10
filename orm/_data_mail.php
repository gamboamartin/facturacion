<?php
namespace gamboamartin\facturacion\models;

use base\orm\_modelo_parent_sin_codigo;
use gamboamartin\errores\errores;

use stdClass;


class _data_mail extends _modelo_parent_sin_codigo{


    protected _transacciones_fc $modelo_entidad;

    public function alta_bd(array $keys_integra_ds = array('descripcion')): array|stdClass
    {


        $keys = array($this->modelo_entidad->key_id);
        $valida = $this->validacion->valida_ids(keys: $keys,registro:  $this->registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar registro',data:  $valida);
        }

        $row_entidad = $this->modelo_entidad->registro(registro_id: $this->registro[$this->modelo_entidad->key_id]);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener row_entidad',data:  $row_entidad);
        }

        if(!isset($this->registro['com_email_cte_id'])){
            if(isset($this->registro['descripcion'])){
                $com_email_cte_ins['descripcion'] = $this->registro['descripcion'];
                $com_email_cte_ins['com_cliente_id'] = $row_entidad['com_cliente_id'];
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
            $descripcion = $row_entidad[$this->modelo_entidad->tabla.'_folio'].' '.$com_email_cte['com_email_cte_descripcion'];
            $this->registro['descripcion'] = $descripcion;
        }


        $filtro[$this->modelo_entidad->key_filtro_id] = $this->registro[$this->modelo_entidad->key_id];
        $filtro['com_email_cte.id'] = $this->registro['com_email_cte_id'];

        $existe = $this->existe(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar',data:  $existe);
        }


        if($existe){
            $r_fc_email = $this->filtro_and(filtro: $filtro);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al obtener registro',data:  $r_fc_email);
            }
            if($r_fc_email->n_registros === 0){
                return $this->error->error(mensaje: 'Error no existe registro',data:  $r_fc_email);
            }
            if($r_fc_email->n_registros > 1){
                return $this->error->error(mensaje: 'Error existe mas de un registro',data:  $r_fc_email);
            }

            $this->registro_id = $r_fc_email->registros[0][$this->key_id];


            $r_alta_bd = new stdClass();
            $r_alta_bd->mensaje = "Registro existente previamente";
            $r_alta_bd->registro_id = $this->registro_id;
            $r_alta_bd->sql = 'NO SE EJECUTO TRANSACCION YA EXISTIA EL REGISTRO';
            $r_alta_bd->registro = $r_fc_email->registros[0];
            $r_alta_bd->registro_obj = (object)$r_fc_email->registros[0];
            $r_alta_bd->registro_ins = $this->registro;
            $r_alta_bd->campos = $this->campos_tabla;
        }

        else{
            $r_alta_bd = parent::alta_bd(keys_integra_ds: $keys_integra_ds); // TODO: Change the autogenerated stub
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al insertar',data:  $r_alta_bd);
            }
        }
        return $r_alta_bd;

    }

    final public function status(string $campo, int $registro_id): array|stdClass
    {
        $r_status = parent::status(campo: $campo,registro_id:  $registro_id); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al transaccionar status',data:  $r_status);
        }
        $fc_email = $this->registro(registro_id: $registro_id, retorno_obj: true);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener fc_email',data:  $fc_email);
        }
        $key_email_status = $this->tabla.'_status';
        $com_email_cte['status'] = $fc_email->$key_email_status;
        $r_com_email_cte = (new com_email_cte(link: $this->link))->modifica_bd(registro: $com_email_cte,
            id: $fc_email->com_email_cte_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al modificar com_email_cte',data:  $r_com_email_cte);
        }

        return $r_status;
    }

}