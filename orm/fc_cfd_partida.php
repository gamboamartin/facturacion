<?php
namespace models;
use base\orm\modelo;
use gamboamartin\errores\errores;
use gamboamartin\organigrama\controllers\controlador_org_empresa;
use models\base\limpieza;
use PDO;
use stdClass;

class fc_cfd_partida extends modelo{
    public function __construct(PDO $link){
        $tabla = __CLASS__;
        $columnas = array($tabla=>false,'com_producto'=>$tabla);
        $campos_obligatorios = array('codigo','serie','com_producto_id');

        $no_duplicados = array('codigo','descripcion_select','alias','codigo_bis','serie');

        parent::__construct(link: $link,tabla:  $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas,no_duplicados: $no_duplicados,tipo_campos: array());
    }

}