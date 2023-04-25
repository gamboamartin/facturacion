<?php
namespace gamboamartin\facturacion\controllers;
use gamboamartin\errores\errores;
use stdClass;

class _fc_base{

    private errores $error;
    public function __construct(){
        $this->error = new errores();
    }

    final public function init_base_fc(controlador_fc_factura|controlador_fc_csd|controlador_fc_complemento_pago $controler){
        $links = $controler->init_links();
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al inicializar links',data:  $links);
        }

        $inputs = $controler->init_inputs();
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al inicializar inputs',data:  $inputs);
        }

        $data = new stdClass();
        $data->links = $links;
        $data->inputs = $inputs;
        return $data;
    }

}
