<?php
namespace gamboamartin\facturacion\models;
use base\orm\modelo;
use PDO;


class fc_retenido extends modelo{
    public function __construct(PDO $link){
        $tabla = 'fc_retenido';
        $columnas = array($tabla=>false,'fc_partida'=>$tabla,'cat_sat_tipo_factor'=>$tabla,'cat_sat_factor'=>$tabla,
            'cat_sat_tipo_impuesto'=>$tabla,'com_producto'=>'fc_partida');
        $campos_obligatorios = array('codigo','serie','fc_partida_id');

        $no_duplicados = array('codigo','descripcion_select','alias','codigo_bis','serie');

        parent::__construct(link: $link,tabla:  $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas,no_duplicados: $no_duplicados,tipo_campos: array());
    }

}