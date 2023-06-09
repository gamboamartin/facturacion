<?php

namespace gamboamartin\facturacion\models;

use base\orm\_modelo_parent;
use gamboamartin\errores\errores;
use PDO;
use stdClass;


class fc_traslado_dr extends _imp_dr {
    public function __construct(PDO $link)
    {
        $tabla = 'fc_traslado_dr';
        $columnas = array($tabla=>false,'fc_impuesto_dr'=>$tabla,
            'fc_docto_relacionado'=>'fc_impuesto_dr','fc_factura'=>'fc_docto_relacionado');
        $campos_obligatorios = array();


        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas);

        $this->NAMESPACE = __NAMESPACE__;
        $this->etiqueta = 'Traslado Dr';

        $this->modelo_dr_part = new fc_traslado_dr_part(link: $this->link);
    }




}