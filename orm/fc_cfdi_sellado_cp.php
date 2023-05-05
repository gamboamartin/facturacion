<?php

namespace gamboamartin\facturacion\models;

use base\orm\_modelo_parent;
use gamboamartin\errores\errores;
use PDO;
use stdClass;

class fc_cfdi_sellado_cp extends _sellado
{
    public function __construct(PDO $link)
    {
        $tabla = 'fc_cfdi_sellado_cp';
        $columnas = array($tabla => false, 'fc_complemento_pago' => $tabla);

        $campos_obligatorios = array();

        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas);

        $this->NAMESPACE = __NAMESPACE__;
        $this->etiqueta = 'CFDI Sellado';
    }


}