<?php

namespace gamboamartin\facturacion\models;

use base\orm\_modelo_parent_sin_codigo;
use PDO;


class fc_conf_automatico extends _modelo_parent_sin_codigo
{
    public function __construct(PDO $link)
    {
        $tabla = 'fc_conf_automatico';
        $columnas = array($tabla => false, 'com_tipo_cliente' => $tabla,'fc_csd'=>$tabla,'org_sucursal'=>'fc_csd',
            'org_empresa'=>'org_sucursal');
        $campos_obligatorios = array('com_tipo_cliente_id','fc_csd_id');

        $columnas_extra = array();

        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas,  columnas_extra: $columnas_extra);

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'Configuraciones de facturacion automatica';
    }


}