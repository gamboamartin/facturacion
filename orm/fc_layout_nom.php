<?php
namespace gamboamartin\facturacion\models;
use base\orm\modelo;
use gamboamartin\documento\models\doc_documento;
use gamboamartin\errores\errores;
use PDO;
use stdClass;


class fc_layout_nom extends modelo{
    public function __construct(PDO $link){
        $tabla = 'fc_layout_nom';
        $columnas = array($tabla=>false,'doc_documento'=>$tabla);

        $campos_view = array();
        $campos_obligatorios = array();
        $no_duplicados = array();


        parent::__construct(link: $link,tabla:  $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas, campos_view: $campos_view, no_duplicados: $no_duplicados);

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'Layout Nomina';
    }

    public function alta_bd(): array|stdClass
    {

        $file = $_FILES['documento'];

        $doc_documento_modelo = new doc_documento(link: $this->link);
        $doc_documento_ins['doc_tipo_documento_id'] = 11;
        $doc_documento_ins['name_out'] = $_FILES['documento']['name'];


        $doc_documento_alta = $doc_documento_modelo->alta_documento(registro:$doc_documento_ins,file: $file);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar doc', data: $doc_documento_alta);
        }

        $doc_documento_id = $doc_documento_alta->registro_id;

        $this->registro['codigo'] = 'LDN.'.date('YmdHis').'.'.mt_rand(10,99);
        $this->registro['codigo_bis'] = 'LDN.'.date('YmdHis').'.'.mt_rand(10,99);
        $this->registro['status'] = 'activo';
        $this->registro['descripcion'] = $this->registro['descripcion'].' '.$doc_documento_ins['name_out'];
        $this->registro['doc_documento_id'] = $doc_documento_id;

        $r_alta = parent::alta_bd();
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al dar de alta registro',data: $r_alta);
        }
        return $r_alta;

    }



}