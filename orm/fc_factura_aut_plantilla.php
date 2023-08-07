<?php

namespace gamboamartin\facturacion\models;

use base\orm\_modelo_parent_sin_codigo;
use gamboamartin\errores\errores;
use PDO;
use stdClass;


class fc_factura_aut_plantilla extends _modelo_parent_sin_codigo
{
    public function __construct(PDO $link)
    {
        $tabla = 'fc_factura_aut_plantilla';
        $columnas = array($tabla => false, 'fc_factura' => $tabla,'fc_ejecucion_aut_plantilla'=>$tabla,
            'com_tipo_cliente'=>'fc_ejecucion_aut_plantilla','fc_csd'=>'fc_factura','org_sucursal'=>'fc_csd',
            'org_empresa'=>'org_sucursal');

        $campos_obligatorios = array('fc_factura_id','fc_ejecucion_aut_plantilla_id');

        $columnas_extra = array();

        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas,  columnas_extra: $columnas_extra);

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'Facturas automaticas por plantilla';
    }

    public function alta_bd(array $keys_integra_ds = array('descripcion')): array|stdClass
    {
        $keys = array('fc_factura_id','fc_ejecucion_aut_plantilla_id');
        $valida = $this->validacion->valida_ids(keys: $keys,registro:  $this->registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar registro',data:  $valida);
        }

        $fc_factura = (new fc_factura(link: $this->link))->registro(registro_id: $this->registro['fc_factura_id'],
            retorno_obj: true);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener factura',data:  $fc_factura);
        }

        if(!isset($this->registro['descripcion'])){
            $descripcion = $fc_factura->fc_factura_folio;
            $this->registro['descripcion'] = $descripcion;
        }

        $r_alta =  parent::alta_bd(keys_integra_ds: $keys_integra_ds); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar',data:  $r_alta);
        }
        return $r_alta;
    }


}