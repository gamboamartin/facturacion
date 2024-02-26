<?php
namespace gamboamartin\facturacion\models;
use base\orm\_modelo_parent;
use PDO;


class fc_csd_etapa extends _modelo_parent {
    public function __construct(PDO $link){
        $tabla = 'fc_csd_etapa';
        $columnas = array($tabla=>false,'fc_csd'=>$tabla,'pr_etapa_proceso'=>$tabla);
        $campos_obligatorios = array('fc_csd_id','pr_etapa_proceso_id');

        $no_duplicados = array();


        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas, no_duplicados: $no_duplicados);

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'CSD Etapa';

    }

}