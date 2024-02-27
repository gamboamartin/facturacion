<?php
namespace gamboamartin\facturacion\models;
use base\orm\_modelo_parent;
use gamboamartin\errores\errores;
use gamboamartin\proceso\models\pr_etapa_proceso;
use PDO;
use stdClass;


class fc_csd_etapa extends _modelo_parent {
    public function __construct(PDO $link){
        $tabla = 'fc_csd_etapa';
        $columnas = array($tabla=>false,'fc_csd'=>$tabla,'pr_etapa_proceso'=>$tabla,'pr_etapa'=>'pr_etapa_proceso');
        $campos_obligatorios = array('fc_csd_id','pr_etapa_proceso_id');

        $no_duplicados = array();


        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas, no_duplicados: $no_duplicados);

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'CSD Etapa';

    }

    public function alta_bd(array $keys_integra_ds = array('codigo', 'descripcion')): array|stdClass
    {
        if(!isset($this->registro['descripcion'])){
            $fc_csd = (new fc_csd(link: $this->link))->registro(registro_id: $this->registro['fc_csd_id']);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al obtener fc_csd',data:  $fc_csd);
            }
            $pr_etapa_proceso = (new pr_etapa_proceso(link: $this->link))->registro(registro_id: $this->registro['pr_etapa_proceso_id']);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al obtener pr_etapa_proceso',data:  $pr_etapa_proceso);
            }
            $descripcion = $fc_csd['fc_csd_descripcion'];
            $descripcion .= '-';
            $descripcion .= $fc_csd['fc_csd_id'];
            $descripcion .= '-';
            $descripcion .= $pr_etapa_proceso['pr_etapa_proceso_id'];
            $this->registro['descripcion'] = $descripcion;
        }
        $r_alta_bd = parent::alta_bd(keys_integra_ds: $keys_integra_ds);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar etapa',data:  $r_alta_bd);
        }

        $etapa = $r_alta_bd->registro['pr_etapa_descripcion'];
        $upd = (new fc_csd(link: $this->link))->modifica_etapa(etapa: $etapa,id: $r_alta_bd->registro['fc_csd_id']);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al actualizar etapa',data:  $upd);
        }

        return $r_alta_bd;
    }

}