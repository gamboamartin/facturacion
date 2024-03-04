<?php
namespace gamboamartin\facturacion\controllers;
use base\orm\modelo;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\_etapa;
use gamboamartin\proceso\models\pr_proceso;
use stdClass;

class _fc_base{

    private errores $error;
    public function __construct(){
        $this->error = new errores();
    }

    private function aplica_etapa(string $key_factura_id_filter, modelo $modelo_etapa, int $registro_id, stdClass $verifica): bool|array
    {
        $aplica_etapa = false;
        if($verifica->mensaje === 'Cancelado'){

            $filtro['pr_etapa.descripcion'] = 'cancelado_sat';
            $filtro[$key_factura_id_filter] = $registro_id;
            $existe = $modelo_etapa->existe(filtro: $filtro);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al validar etapa',data:  $existe);
            }
            if(!$existe){
                $aplica_etapa = true;
            }
        }
        return $aplica_etapa;
    }

    final public function init_base_fc(_base_system_fc $controler, string $name_modelo_email){
        $links = $controler->init_links(name_modelo_email: $name_modelo_email);
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

    final public function integra_etapa(string $key_factura_id_filter, modelo $modelo, modelo $modelo_etapa,
                                   int $registro_id, stdClass $verifica, bool $valida_existencia_etapa = true): bool|array
    {
        $aplica_etapa = $this->aplica_etapa(key_factura_id_filter: $key_factura_id_filter,
            modelo_etapa: $modelo_etapa, registro_id: $registro_id, verifica: $verifica);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al si aplica etapa',data:  $aplica_etapa);
        }

        if($aplica_etapa) {
            $r_alta_factura_etapa = (new pr_proceso(link: $modelo_etapa->link))->inserta_etapa(
                adm_accion: 'cancelado_sat', fecha: '', modelo: $modelo, modelo_etapa: $modelo_etapa,
                registro_id: $registro_id, valida_existencia_etapa: $valida_existencia_etapa);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al insertar etapa', data: $r_alta_factura_etapa);
            }
        }
        return $aplica_etapa;
    }






}
