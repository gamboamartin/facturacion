<?php
namespace gamboamartin\facturacion\models;
use base\controller\controler;
use gamboamartin\errores\errores;
use stdClass;

class limpieza{
    private errores $error;
    public function __construct(){
        $this->error = new errores();
    }



    private function init_data_fc_factura(controler $controler, stdClass $registro): stdClass
    {
        $controler->row_upd->dp_pais_id = $registro->dp_pais_id;
        $controler->row_upd->dp_estado_id = $registro->dp_estado_id;
        $controler->row_upd->dp_municipio_id = $registro->dp_municipio_id;
        $controler->row_upd->dp_cp_id = $registro->dp_cp_id;
        $controler->row_upd->dp_colonia_postal_id = $registro->dp_colonia_postal_id;
        $controler->row_upd->dp_calle_pertenece_id = $registro->dp_calle_pertenece_id;

        return $controler->row_upd;
    }

    private function init_foraneas(array $keys_foraneas, stdClass $org_empresa): stdClass
    {
        foreach ($keys_foraneas as $campo){
            if(is_null($org_empresa->$campo)){
                $org_empresa->$campo = '-1';
            }
        }
        return $org_empresa;
    }


    public function init_modifica_fc_factura(controler $controler): array|stdClass
    {
        if(!isset($controler->row_upd)){
            $controler->row_upd = new stdClass();
        }


        $registro = $controler->modelo->registro(registro_id: $controler->registro_id,retorno_obj: true);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener registro',data:  $registro);
        }


        $init = $this->init_upd_fc_factura(controler: $controler,registro:  $registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al inicializa datos',data:  $init);
        }
        return $init;
    }
    private function init_upd_fc_factura(controler $controler, stdClass $registro): array|stdClass
    {
        $keys_foraneas = array('dp_pais_id','dp_estado_id','dp_municipio_id','dp_cp_id','dp_colonia_postal_id',
            'dp_calle_pertenece_id');


        $init = $this->init_foraneas(keys_foraneas: $keys_foraneas,org_empresa:  $registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al inicializa datos',data:  $init);
        }

        $init = $this->init_data_fc_factura(controler: $controler,registro:  $registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al inicializa datos',data:  $init);
        }
        return $init;
    }
}
