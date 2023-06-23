<?php

namespace gamboamartin\facturacion\models;

use base\orm\_modelo_parent;
use gamboamartin\errores\errores;
use PDO;
use stdClass;


class fc_impuesto_dr extends _modelo_parent{
    public function __construct(PDO $link)
    {
        $tabla = 'fc_impuesto_dr';
        $columnas = array($tabla=>false,'fc_docto_relacionado'=>$tabla,'fc_factura'=>'fc_docto_relacionado');
        $campos_obligatorios = array();


        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas);

        $this->NAMESPACE = __NAMESPACE__;
        $this->etiqueta = 'Impuesto Dr';
    }

    public function alta_bd(array $keys_integra_ds = array('descripcion')): array|stdClass
    {
        $keys = array('fc_docto_relacionado_id');
        $valida = $this->validacion->valida_existencia_keys(keys: $keys,registro:  $this->registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al valida',data:  $valida);
        }

        if(!isset($this->registro['codigo'])){
            $codigo = $this->registro['fc_docto_relacionado_id'];
            $codigo .= time();
            $codigo .= mt_rand(1000,9999);
            $this->registro['codigo'] = $codigo;
        }

        if(!isset($this->registro['descripcion'])){
            $descripcion = $this->registro['codigo'];
            $this->registro['descripcion'] = $descripcion;
        }

        $r_alta_bd = parent::alta_bd($keys_integra_ds); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar',data:  $r_alta_bd);
        }

        $fc_impuesto_dr = $r_alta_bd->registro_obj;


        $modelo_traslado = new fc_traslado(link: $this->link);

        $tiene_traslados = (new fc_factura(link: $this->link))->tiene_traslados(modelo_traslado: $modelo_traslado,
            registro_id: $fc_impuesto_dr->fc_factura_id);

        if($tiene_traslados){
            $fc_traslado_dr_ins['fc_impuesto_dr_id'] = $r_alta_bd->registro_id;
            $r_fc_traslado_dr = (new fc_traslado_dr(link: $this->link))->alta_registro(registro: $fc_traslado_dr_ins);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al insertar',data:  $r_fc_traslado_dr);
            }
        }

        $modelo_retencion = new fc_retenido(link: $this->link);

        $tiene_retenciones = (new fc_factura(link: $this->link))->tiene_retenciones(modelo_retencion: $modelo_retencion,
            registro_id: $fc_impuesto_dr->fc_factura_id);

        if($tiene_retenciones){

            $fc_retencion_dr_ins['fc_impuesto_dr_id'] = $r_alta_bd->registro_id;
            $r_fc_retencion_dr = (new fc_retencion_dr(link: $this->link))->alta_registro(registro: $fc_retencion_dr_ins);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al insertar',data:  $r_fc_retencion_dr);
            }
        }


        return $r_alta_bd;
    }

    public function alta_registro(array $registro): array|stdClass
    {
        $r_alta_bd = parent::alta_registro(registro: $registro); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar',data:  $r_alta_bd);
        }
        return $r_alta_bd;
    }

    public function elimina_bd(int $id): array|stdClass
    {

        $filtro['fc_impuesto_dr.id'] = $id;

        $del_fc_traslado_dr = (new fc_traslado_dr(link: $this->link))->elimina_con_filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al eliminar del_fc_traslado_dr',data:  $del_fc_traslado_dr);
        }
        $del_fc_retenido_dr = (new fc_retencion_dr(link: $this->link))->elimina_con_filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al eliminar del_fc_retenido_dr',data:  $del_fc_retenido_dr);
        }

        $r_elimina_bd =  parent::elimina_bd($id); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al eliminar',data:  $r_elimina_bd);
        }
        return $r_elimina_bd;

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
        return $r_modifica_bd;
    }

}