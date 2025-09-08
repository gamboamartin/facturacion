<?php

namespace gamboamartin\facturacion\models;

use base\orm\modelo;
use PDO;

class fc_cfdi_sellado_nomina extends modelo
{
    public function __construct(PDO $link)
    {
        $tabla = 'fc_cfdi_sellado_nomina';
        $columnas = array($tabla => false, 'fc_row_layout' => $tabla);

        $campos_obligatorios = array();

        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas);

        $this->NAMESPACE = __NAMESPACE__;
        $this->etiqueta = 'CFDI Sellado Nomina';
    }




}