<?php

namespace gamboamartin\facturacion\models;
use base\orm\_modelo_parent;
use gamboamartin\errores\errores;

class _base extends _modelo_parent{

    protected function init_alta_bd(){
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
        return $this->registros;
    }


}
