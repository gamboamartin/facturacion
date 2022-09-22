<?php
namespace gamboamartin\facturacion\models;
use base\orm\modelo;
use PDO;


class fc_csd extends modelo{
    public function __construct(PDO $link){
        $tabla = 'fc_csd';
        $columnas = array($tabla=>false,'org_sucursal'=>$tabla,'org_empresa'=>'org_sucursal',
            'dp_calle_pertenece'=>'org_empresa','cat_sat_regimen_fiscal'=>'org_empresa');
        $campos_obligatorios = array('codigo','serie','org_sucursal_id','descripcion_select','alias');

        $no_duplicados = array('codigo','descripcion_select','alias','codigo_bis','serie');

        parent::__construct(link: $link,tabla:  $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas,no_duplicados: $no_duplicados,tipo_campos: array());
    }

}