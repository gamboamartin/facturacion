<?php
namespace gamboamartin\facturacion\models;
use base\orm\modelo;
use gamboamartin\organigrama\models\org_sucursal;
use PDO;


class fc_csd extends modelo{
    public function __construct(PDO $link){
        $tabla = 'fc_csd';
        $columnas = array($tabla=>false,'org_sucursal'=>$tabla,'org_empresa'=>'org_sucursal',
            'dp_calle_pertenece'=>'org_sucursal','cat_sat_regimen_fiscal'=>'org_empresa');
        $campos_obligatorios = array('codigo','serie','org_sucursal_id','descripcion_select','alias','codigo_bis');

        $no_duplicados = array('codigo','descripcion_select','alias','codigo_bis','serie');

        $campos_view = array();
        $campos_view['org_sucursal_id']['type'] = 'selects';
        $campos_view['org_sucursal_id']['model'] = (new org_sucursal($link));

        $campos_view['serie']['type'] = 'inputs';

        parent::__construct(link: $link,tabla:  $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas,no_duplicados: $no_duplicados,tipo_campos: array(), campos_view: $campos_view);
    }

}