<?php

namespace gamboamartin\facturacion\models;

use base\orm\_modelo_parent_sin_codigo;
use gamboamartin\errores\errores;
use PDO;
use stdClass;


class fc_conf_aut_producto extends _modelo_parent_sin_codigo
{
    public function __construct(PDO $link)
    {
        $tabla = 'fc_conf_aut_producto';
        $columnas = array($tabla => false, 'com_producto' => $tabla,'fc_conf_automatico'=>$tabla);
        $campos_obligatorios = array('com_producto_id','fc_conf_automatico_id','cantidad');

        $columnas_extra = array();

        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas,  columnas_extra: $columnas_extra);

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'Configuraciones de productos automaticos';
    }

    public function alta_bd(array $keys_integra_ds = array('descripcion')): array|stdClass
    {
        $com_producto = (new com_producto(link: $this->link))->registro(registro_id: $this->registro['com_producto_id']);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener producto',data:  $com_producto);
        }
        $fc_conf_automatico = (new fc_conf_automatico(link: $this->link))->registro(registro_id: $this->registro['fc_conf_automatico_id']);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener producto',data:  $com_producto);
        }

        if(!isset($this->registro['descripcion'])){
            $descripcion = $com_producto['com_producto_descripcion'].' '.$fc_conf_automatico['fc_conf_automatico_descripcion'];
            $descripcion .= $this->registro['cantidad'].' '.date('Y-m-d H:i:s');
            $this->registro['descripcion'] = $descripcion;
        }
        return parent::alta_bd(keys_integra_ds: $keys_integra_ds); // TODO: Change the autogenerated stub
    }

    final public function productos_by_conf(int $fc_conf_automatico_id){
        $filtro_prods['fc_conf_automatico.id'] = $fc_conf_automatico_id;

        $r_conf_aut_producto = (new fc_conf_aut_producto(link: $this->link))->filtro_and(filtro: $filtro_prods);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener productos',data:  $r_conf_aut_producto);
        }
        return $r_conf_aut_producto->registros;
    }


}