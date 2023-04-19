<?php
namespace gamboamartin\facturacion\controllers;

use gamboamartin\errores\errores;
use stdClass;

class _base_system_csd extends _base_system_conf {

    final protected function init_inputs(): array
    {
        $identificador = "fc_csd_id";
        $propiedades = array("label" => "CSD", "cols" => 12);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "doc_documento_id";
        $propiedades = array("label" => "Documento", "cols" => 8);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "documento";
        $propiedades = array("place_holder" => "Documento", "cols" => 8);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "codigo";
        $propiedades = array("place_holder" => "Código", "cols" => 4);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);


        return $this->keys_selects;
    }
    final protected function init_modifica(): array|stdClass
    {
        $r_modifica =  parent::modifica(header: false);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al generar template',data:  $r_modifica);
        }

        $identificador = "fc_csd_id";
        $propiedades = array("id_selected" => $this->row_upd->fc_csd_id);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $identificador = "doc_documento_id";
        $propiedades = array("id_selected" => $this->row_upd->doc_documento_id);
        $this->asignar_propiedad(identificador:$identificador, propiedades: $propiedades);

        $inputs = $this->genera_inputs(keys_selects:  $this->keys_selects);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al generar inputs',data:  $inputs);
        }

        $data = new stdClass();
        $data->template = $r_modifica;
        $data->inputs = $inputs;

        return $data;
    }

    final public function modifica(bool $header, bool $ws = false): array|stdClass
    {
        $base = $this->init_modifica();
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al maquetar datos',data:  $base,
                header: $header,ws:$ws);
        }

        return $base->template;
    }

}
