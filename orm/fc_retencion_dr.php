<?php

namespace gamboamartin\facturacion\models;

use base\orm\_modelo_parent;
use gamboamartin\errores\errores;
use PDO;
use stdClass;


class fc_retencion_dr extends _modelo_parent{
    public function __construct(PDO $link)
    {
        $tabla = 'fc_retencion_dr';
        $columnas = array($tabla=>false);
        $campos_obligatorios = array();


        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas);

        $this->NAMESPACE = __NAMESPACE__;
        $this->etiqueta = 'Retencion Dr';
    }

    public function alta_bd(array $keys_integra_ds = array('descripcion')): array|stdClass
    {

        if(!isset($this->registro['codigo'])){
            $codigo = $this->registro['fc_impuesto_dr_id'];
            $codigo .= time();
            $codigo .= mt_rand(1000,9999);
            $this->registro['codigo'] = $codigo;
        }

        if(!isset($this->registro['descripcion'])){
            $descripcion = $this->registro['codigo'];
            $this->registro['descripcion'] = $descripcion;
        }

        $r_alta_bd = parent::alta_bd(keys_integra_ds: $keys_integra_ds); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar',data:  $r_alta_bd);
        }
        return $r_alta_bd;
    }

    public function modifica_bd(array $registro, int $id, bool $reactiva = false, array $keys_integra_ds = array('codigo', 'descripcion')): array|stdClass
    {

        $registro_previo = $this->registro(registro_id: $id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener registro_previo',data:  $registro_previo);
        }


        if(!isset($registro['descripcion'])){

            $codigo = $registro_previo['fc_retencion_dr_codigo'];
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