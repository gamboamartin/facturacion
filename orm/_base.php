<?php

namespace gamboamartin\facturacion\models;
use base\orm\_modelo_parent;
use gamboamartin\errores\errores;

class _base extends _modelo_parent{

    /**
     * Integra los elementos base de un modelo para alta
     * @return array
     */
    protected function init_alta_bd(): array
    {

        if(!isset($this->registro['descripcion'])){
            $descripcion = time();
            $descripcion .= mt_rand(10,99).mt_rand(10,99).mt_rand(10,99).mt_rand(10,99).mt_rand(10,99).mt_rand(10,99);
            $descripcion .= mt_rand(10,99).mt_rand(10,99).mt_rand(10,99).mt_rand(10,99).mt_rand(10,99).mt_rand(10,99);
            $this->registro['descripcion'] = $descripcion;
        }

        if (!isset($this->registro['codigo'])) {
            $this->registro['codigo'] = $this->get_codigo_aleatorio();
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al generar codigo aleatorio', data: $this->registro);
            }
        }

        $this->registro = $this->campos_base(data: $this->registro, modelo: $this);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al inicializar campos base', data: $this->registro);
        }
        return $this->registro;
    }


}
