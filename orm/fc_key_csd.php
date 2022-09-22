<?php
namespace gamboamartin\facturacion\models;
use base\orm\modelo;
use PDO;


class fc_key_csd extends modelo{
    public function __construct(PDO $link){
        $tabla = 'fc_key_csd';
        $columnas = array($tabla=>false,'fc_csd'=>$tabla);
        $campos_obligatorios = array('codigo');

        $no_duplicados = array('codigo','descripcion_select','alias','codigo_bis','serie');

        parent::__construct(link: $link,tabla:  $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas,no_duplicados: $no_duplicados,tipo_campos: array());
    }

}