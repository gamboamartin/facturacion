<?php

namespace gamboamartin\facturacion\models;

use PDO;


class fc_factura_etapa extends _base
{
    public function __construct(PDO $link)
    {
        $tabla = 'fc_factura_etapa';
        $columnas = array($tabla => false, 'fc_factura' => $tabla, 'pr_etapa_proceso' => $tabla,
            'pr_etapa' => 'pr_etapa_proceso', 'pr_proceso' => 'pr_etapa_proceso', 'pr_tipo_proceso' => 'pr_proceso');
        $campos_obligatorios = array('fc_factura_id', 'pr_etapa_proceso_id');

        $columnas_extra = array();



        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas,  columnas_extra: $columnas_extra);

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'Factura Etapa';
    }


}