<?php

namespace gamboamartin\facturacion\models;

use base\orm\_modelo_parent;
use gamboamartin\errores\errores;
use PDO;
use stdClass;

class fc_pago_total extends _modelo_parent{
    public function __construct(PDO $link)
    {
        $tabla = 'fc_pago_total';
        $columnas = array($tabla=>false,'fc_pago'=>$tabla);
        $campos_obligatorios = array();


        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas);

        $this->NAMESPACE = __NAMESPACE__;
        $this->etiqueta = 'Pago Total';
    }

    public function alta_bd(array $keys_integra_ds = array('descripcion')): array|\stdClass
    {


        $registro = $this->init(registro: $this->registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integrar codigo',data:  $registro);
        }

        $this->registro = $registro;


        $r_alta_bd = parent::alta_bd($keys_integra_ds); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar',data:  $r_alta_bd);
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

    /**
     * INtegra un codigo para alta
     * @param array $registro Registro en proceso
     * @return array
     */
    private function codigo(array $registro): array
    {
        if(!isset($registro['codigo'])){
            $codigo = $registro['fc_pago_id'];
            $registro['codigo'] = $codigo;
        }
        return $registro;
    }

    private function descripcion(array $registro): array
    {
        if(!isset($registro['descripcion'])){
            $descripcion = $registro['codigo'];
            $registro['descripcion'] = $descripcion;
        }
        return $registro;
    }

    private function init(array $registro){
        $registro = $this->codigo(registro: $registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integrar codigo',data:  $registro);
        }

        $registro = $this->descripcion(registro: $registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integrar codigo',data:  $registro);
        }
        return $registro;
    }

    /**
     * Inicializa un registro previo a su modificacion
     * @param int $id Identificador
     * @param array $registro Registro en proceso
     * @return array
     *
     */
    private function init_row_modifica(int $id,array $registro): array
    {
        $registro_previo = $this->registro(registro_id: $id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener registro_previo',data:  $registro_previo);
        }

        if(!isset($registro['descripcion'])){

            $codigo = $registro_previo['fc_pago_total_codigo'];
            if(isset($registro['codigo'])){
                $codigo = $registro['codigo'];
            }

            $descripcion = $codigo;
            $registro['descripcion'] = $descripcion;
        }
        return $registro;
    }

    public function modifica_bd(array $registro, int $id, bool $reactiva = false,
                                array $keys_integra_ds = array('codigo', 'descripcion')): array|\stdClass
    {


        $registro = $this->init_row_modifica(id: $id, registro: $registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al inicializa registro',data:  $registro);
        }

        $r_modifica_bd  = parent::modifica_bd(registro: $registro,id:  $id,reactiva:  $reactiva,
            keys_integra_ds:  $keys_integra_ds); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al modificar',data:  $r_modifica_bd);
        }
        return $r_modifica_bd;
    }


}