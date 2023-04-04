<?php

namespace gamboamartin\facturacion\models;

use base\orm\_modelo_parent_sin_codigo;
use PDO;



class fc_relacion extends _modelo_parent_sin_codigo
{
    public function __construct(PDO $link)
    {
        $tabla = 'fc_relacion';
        $columnas = array($tabla => false, 'fc_factura' => $tabla, 'cat_sat_tipo_relacion' => $tabla);
        $campos_obligatorios = array('fc_factura_id', 'cat_sat_tipo_relacion_id');

        $columnas_extra = array();


        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas,  columnas_extra: $columnas_extra);

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'Facturas Relacionadas';
    }




}