<?php

namespace gamboamartin\facturacion\models;

use base\orm\_modelo_parent_sin_codigo;
use PDO;



class fc_ejecucion_aut_plantilla extends _modelo_parent_sin_codigo
{
    public function __construct(PDO $link)
    {
        $tabla = 'fc_ejecucion_aut_plantilla';
        $columnas = array($tabla => false, 'com_tipo_cliente'=>$tabla);

        $campos_obligatorios = array('com_tipo_cliente_id');

        $columnas_extra = array();

        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas,  columnas_extra: $columnas_extra);

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'Ejecuciones de facturacion automatica';
    }



}