<?php

namespace gamboamartin\facturacion\models;

use base\orm\_modelo_parent_sin_codigo;
use gamboamartin\errores\errores;
use PDO;
use stdClass;


class fc_email extends _data_mail
{
    public function __construct(PDO $link)
    {
        $tabla = 'fc_email';
        $columnas = array($tabla => false, 'fc_factura' => $tabla, 'com_email_cte' => $tabla,
            'com_sucursal' => 'fc_factura','com_cliente'=>'com_sucursal');
        $campos_obligatorios = array('fc_factura_id', 'com_email_cte_id');

        $columnas_extra = array();



        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas,  columnas_extra: $columnas_extra);

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'Factura Email';
    }

    public function alta_bd(array $keys_integra_ds = array('descripcion')): array|stdClass
    {
        $this->modelo_entidad = new fc_factura(link: $this->link);

        $r_alta_bd = parent::alta_bd($keys_integra_ds); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar',data:  $r_alta_bd);
        }
        return $r_alta_bd;
    }


}