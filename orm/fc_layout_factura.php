<?php
namespace gamboamartin\facturacion\models;
use base\orm\modelo;
use gamboamartin\errores\errores;
use PDO;


class fc_layout_factura extends modelo{
    public function __construct(PDO $link){
        $tabla = 'fc_layout_factura';
        $columnas = [$tabla=>false, 'fc_layout_nom_id'=>$tabla, 'fc_factura_id'=>$tabla ];
        $campos_obligatorios = [];
        $campos_view = [];
        $no_duplicados = [];

        parent::__construct(link: $link,tabla:  $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas, campos_view: $campos_view, no_duplicados: $no_duplicados,tipo_campos: array());

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'Layouts y Facturas';
    }

}