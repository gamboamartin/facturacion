<?php

namespace gamboamartin\facturacion\models;

use base\orm\_modelo_parent;
use gamboamartin\errores\errores;
use PDO;
use stdClass;

class fc_cfdi_sellado extends _sellado
{
    public function __construct(PDO $link)
    {
        $tabla = 'fc_cfdi_sellado';
        $columnas = array($tabla => false, 'fc_factura' => $tabla);

        $campos_obligatorios = array();

        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas);

        $this->NAMESPACE = __NAMESPACE__;
        $this->etiqueta = 'CFDI Sellado';
    }


}