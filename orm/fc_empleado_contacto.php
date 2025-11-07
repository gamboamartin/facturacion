<?php
namespace gamboamartin\facturacion\models;
use base\orm\modelo;
use gamboamartin\errores\errores;
use PDO;


class fc_empleado_contacto extends modelo{
    public function __construct(PDO $link){
        $tabla = 'fc_empleado_contacto';
        $columnas = array( $tabla=>false );

        $campos_view = array();
        $campos_obligatorios = array();
        $no_duplicados = array();


        parent::__construct(link: $link,tabla:  $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas, campos_view: $campos_view, no_duplicados: $no_duplicados);

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'Empleado Contacto';
    }

}