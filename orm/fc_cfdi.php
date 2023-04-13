<?php
namespace gamboamartin\facturacion\models;
use base\orm\modelo;
use gamboamartin\errores\errores;
use PDO;
use stdClass;


class fc_cfdi extends modelo{
    public function __construct(PDO $link){
        $tabla = 'fc_cfdi';
        $columnas = array($tabla=>false,'fc_factura'=>$tabla);
        $campos_obligatorios = array('codigo');

        $campos_view['fc_factura_id'] = array('type' => 'selects', 'model' => new fc_csd($link));
        $campos_view['sello'] = array('type' => 'inputs');
        $campos_view['no_certificado'] = array('type' => 'inputs');
        $campos_view['fecha_timbrado'] = array('type' => 'dates');
        $campos_view['no_certificado_sat'] = array('type' => 'inputs');
        $campos_view['rfc_proveedor'] = array('type' => 'inputs');
        $campos_view['sello_cfd'] = array('type' => 'inputs');
        $campos_view['sello_sat'] = array('type' => 'inputs');
        $campos_view['uuid'] = array('type' => 'inputs');
        $campos_view['uuid_relacionado'] = array('type' => 'inputs');

        $no_duplicados = array('codigo','descripcion_select','alias','codigo_bis');

        parent::__construct(link: $link,tabla:  $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas, campos_view: $campos_view, no_duplicados: $no_duplicados,tipo_campos: array());

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'CFDI';
    }

}