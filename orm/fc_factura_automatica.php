<?php

namespace gamboamartin\facturacion\models;

use base\orm\_modelo_parent_sin_codigo;
use gamboamartin\errores\errores;
use PDO;
use stdClass;


class fc_factura_automatica extends _modelo_parent_sin_codigo
{
    public function __construct(PDO $link)
    {
        $tabla = 'fc_factura_automatica';
        $columnas = array($tabla => false, 'fc_factura' => $tabla,'fc_ejecucion_automatica'=>$tabla,
            'fc_conf_automatico'=>'fc_ejecucion_automatica','fc_csd'=>'fc_factura','org_sucursal'=>'fc_csd',
            'org_empresa'=>'org_sucursal');

        $campos_obligatorios = array('fc_factura_id','fc_ejecucion_automatica_id');

        $fc_factura_etapa = "(SELECT pr_etapa.descripcion FROM pr_etapa 
            LEFT JOIN pr_etapa_proceso ON pr_etapa_proceso.pr_etapa_id = pr_etapa.id 
            LEFT JOIN fc_factura_etapa ON fc_factura_etapa.pr_etapa_proceso_id = pr_etapa_proceso.id
            WHERE fc_factura_etapa.fc_factura_id = fc_factura.id ORDER BY fc_factura_etapa.id DESC LIMIT 1)";

        $columnas_extra['fc_factura_etapa'] = $fc_factura_etapa;

        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas,  columnas_extra: $columnas_extra);

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'Facturas automaticas';
    }

    public function alta_bd(array $keys_integra_ds = array('descripcion')): array|stdClass
    {
        $keys = array('fc_factura_id','fc_ejecucion_automatica_id');
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