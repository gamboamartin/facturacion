<?php
namespace gamboamartin\facturacion\models;
use base\orm\modelo;
use gamboamartin\errores\errores;
use PDO;


class fc_empleado extends modelo{
    public function __construct(PDO $link){
        $tabla = 'fc_empleado';
        $columnas = array($tabla=>false);

        $campos_view = array();
        $campos_obligatorios = array();
        $no_duplicados = array();


        parent::__construct(link: $link,tabla:  $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas, campos_view: $campos_view, no_duplicados: $no_duplicados);

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'Empleados';
    }

    public function alta_registro(array $registro): array|\stdClass
    {
        if(!isset($registro['codigo'])){
            $registro['codigo'] = trim($registro['rfc'].$registro['curp']);
        }
        if(!isset($registro['descripcion'])){
            $registro['descripcion'] = trim($registro['codigo'].$registro['nombre_completo']);
        }
        if(!isset($registro['codigo_bis'])){
            $registro['codigo_bis'] = trim($registro['descripcion']);
        }
        $r_alta = parent::alta_registro(registro: $registro);
        if(errores::$error){
            return (new errores())->error('Error al insertar', $r_alta);
        }
        return $r_alta;


    }


}