<?php

namespace gamboamartin\facturacion\models;

use base\orm\_modelo_parent;
use PDO;


class fc_layout_periodo extends _modelo_parent
{
    public function __construct(PDO $link)
    {
        $tabla = 'fc_layout_periodo';
        $columnas = [$tabla => false];
        $campos_obligatorios = [];
        $columnas_extra = [];

        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas,  columnas_extra: $columnas_extra);

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'Periodo';
    }

}