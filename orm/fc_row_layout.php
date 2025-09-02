<?php
namespace gamboamartin\facturacion\models;
use base\orm\modelo;
use gamboamartin\errores\errores;
use PDO;


class fc_row_layout extends modelo{
    public function __construct(PDO $link){
        $tabla = 'fc_row_layout';
        $columnas = array($tabla=>false,'fc_layout_nom'=>$tabla);
        $campos_obligatorios = array();
        $campos_view = array();
        $no_duplicados = array();

        parent::__construct(link: $link,tabla:  $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas, campos_view: $campos_view, no_duplicados: $no_duplicados,tipo_campos: array());

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'Rows';
    }

    public function alta_registro(array $registro): array|\stdClass
    {
        if(!isset($registro['codigo'])){
            $registro['codigo'] = $registro['fc_empleado_id'];
            $registro['codigo'] .= $registro['fc_layout_nom_id'];
            $registro['codigo'] .= $registro['nss'];
            $registro['codigo'] .= $registro['rfc'];
            $registro['codigo'] .= $registro['curp'];
        }
        if(!isset($registro['codigo_bis'])){
            $registro['codigo_bis'] = $registro['codigo'];
        }
        if(!isset($registro['descripcion'])){
            $registro['descripcion'] = $registro['fc_empleado_id'];
            $registro['descripcion'] .= ' '.$registro['fc_layout_nom_id'];
            $registro['descripcion'] .= ' '.$registro['nss'];
            $registro['descripcion'] .= ' '.$registro['rfc'];
            $registro['descripcion'] .= ' '.$registro['curp'];
        }
        $r_alta = parent::alta_registro($registro);
        if(errores::$error){
            return (new errores())->error('Error al insertar row', $r_alta);
        }
        return $r_alta;

    }

}