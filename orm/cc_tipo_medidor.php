<?php

namespace gamboamartin\facturacion\models;

use base\orm\_modelo_parent;
use PDO;


class cc_tipo_medidor extends _modelo_parent
{
    public function __construct(PDO $link)
    {
        $tabla = 'cc_tipo_medidor';
        $columnas = array($tabla => false);

        $campos_obligatorios = array();

        parent::__construct(
            link: $link,
            tabla: $tabla,
            campos_obligatorios: $campos_obligatorios,
            columnas: $columnas
        );

        $this->NAMESPACE = __NAMESPACE__;
        $this->etiqueta = 'Tipo Medidor';
    }

}