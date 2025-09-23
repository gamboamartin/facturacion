<?php
namespace gamboamartin\facturacion\models;
use base\orm\modelo;
use gamboamartin\errores\errores;
use PDO;


class fc_row_nomina extends modelo{
    public function __construct(PDO $link){
        $tabla = 'fc_row_nomina';
        $columnas = array($tabla=>false, 'fc_row_layout'=>$tabla, 'doc_documento'=>$tabla,
            'doc_tipo_documento'=>'doc_documento','fc_layout_nom'=>'fc_row_layout');
        $campos_obligatorios = array();
        $campos_view = array();
        $no_duplicados = array();

        parent::__construct(link: $link,tabla:  $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas, campos_view: $campos_view, no_duplicados: $no_duplicados,tipo_campos: array());

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'Rows';
    }

    public function obtener_ruta_documento(int $fc_row_layout_id, int $doc_tipo_documento_id): array|string
    {

        $filtro['fc_row_layout.id'] = $fc_row_layout_id;
        $filtro['doc_tipo_documento.id'] = $doc_tipo_documento_id;
        $r_fc_row_nomina = $this->filtro_and(filtro: $filtro);
        if(errores::$error) {
            return (new errores())->error("Error al obtener datos del recibo", $r_fc_row_nomina);
        }
        if($r_fc_row_nomina->n_registros === 0){
            return (new errores())->error("No existe registro del documento", $r_fc_row_nomina);
        }

        $fc_row_nomina = $r_fc_row_nomina->registros[0];

        if (!is_file($fc_row_nomina['doc_documento_ruta_absoluta'])) {
            return (new errores())->error("No existe el documento", $r_fc_row_nomina);
        }

        return $fc_row_nomina['doc_documento_ruta_absoluta'];
    }

}