<?php
namespace models;
use base\orm\modelo;
use gamboamartin\errores\errores;
use gamboamartin\organigrama\controllers\controlador_org_empresa;
use models\base\limpieza;
use PDO;
use stdClass;

class fc_factura extends modelo{
    public function __construct(PDO $link){
        $tabla = __CLASS__;
        $columnas = array($tabla=>false,'fc_cfd'=>$tabla, 'cat_sat_forma_pago'=>$tabla,'cat_sat_metodo_pago'=>$tabla,
            'cat_sat_moneda'=>$tabla, 'com_tipo_cambio'=>$tabla, 'cat_sat_uso_cfdi'=>$tabla,
            'cat_sat_tipo_de_comprobante'=>$tabla, 'dp_calle_pertenece'=>$tabla, 'cat_sat_regimen_fiscal'=>$tabla,
            'com_sucursal'=>$tabla);
        $campos_obligatorios = array('codigo', 'cat_sat_forma_pago_id','cat_sat_metodo_pago_id', 'cat_sat_moneda_id',
            'com_tipo_cambio_id', 'cat_sat_uso_cfdi_id', 'cat_sat_tipo_de_comprobante_id',
            'dp_calle_pertenece_id', 'cat_sat_regimen_fiscal_id', 'com_sucursal_id');

        $no_duplicados = array('codigo','descripcion_select','alias','codigo_bis','serie');

        parent::__construct(link: $link,tabla:  $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas,no_duplicados: $no_duplicados,tipo_campos: array());
    }

}