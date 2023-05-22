<?php

namespace gamboamartin\facturacion\models;

use base\orm\_modelo_parent;
use gamboamartin\errores\errores;
use PDO;
use stdClass;


class fc_pago extends _modelo_parent{
    public function __construct(PDO $link)
    {
        $tabla = 'fc_pago';
        $columnas = array($tabla=>false);
        $campos_obligatorios = array();


        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas);

        $this->NAMESPACE = __NAMESPACE__;
        $this->etiqueta = 'Pago';
    }

    public function alta_bd(array $keys_integra_ds = array('descripcion')): array|stdClass
    {

        $registro = $this->integra_descripcion(registro: $this->registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar descripcion',data:  $registro);
        }
        $this->registro = $registro;

        $r_alta_bd = parent::alta_bd(keys_integra_ds: $keys_integra_ds); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar',data:  $r_alta_bd);
        }
        return $r_alta_bd;
    }

    /**
     * Genera la descripcion basada en version e  identificador de complemento
     * @param array $registro Registro en proceso
     * @return string
     */
    private function descripcion(array $registro): string
    {
        $descripcion = $registro['version'];
        $descripcion .= ' '.$registro['fc_complemento_pago_id'];
        return $descripcion;
    }

    private function integra_descripcion(array $registro){
        if(!isset($registro['descripcion'])){

            $descripcion = $this->descripcion(registro: $registro);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al generar descripcion',data:  $descripcion);
            }

            $registro['descripcion'] = $descripcion;
        }
        return $registro;
    }

    public function modifica_bd(array $registro, int $id, bool $reactiva = false, array $keys_integra_ds = array('codigo', 'descripcion')): array|stdClass
    {

        $registro_previo = $this->registro(registro_id: $id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener registro_previo',data:  $registro_previo);
        }


        if(!isset($registro['descripcion'])){

            $codigo = $registro_previo['fc_pago_codigo'];
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