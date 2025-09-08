<?php
namespace gamboamartin\facturacion\models;
use base\orm\modelo;
use gamboamartin\errores\errores;
use PDO;


class fc_row_nomina extends modelo{
    public function __construct(PDO $link){
        $tabla = 'fc_row_nomina';
        $columnas = array($tabla=>false, 'fc_row_layout'=>$tabla, 'doc_documento'=>$tabla,
            'doc_tipo_documento'=>'doc_documento','fc_layout_nom'=>'fc_row_layout');
        $campos_obligatorios = array();
        $campos_view = array();
        $no_duplicados = array();

        parent::__construct(link: $link,tabla:  $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas, campos_view: $campos_view, no_duplicados: $no_duplicados,tipo_campos: array());

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'Rows';
    }

}