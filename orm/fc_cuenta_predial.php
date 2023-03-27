<?php
namespace gamboamartin\facturacion\models;

use base\orm\_modelo_parent_sin_codigo;
use gamboamartin\errores\errores;
use PDO;
use stdClass;

class fc_cuenta_predial extends _modelo_parent_sin_codigo {
    public function __construct(PDO $link){
        $tabla = 'fc_cuenta_predial';
        $columnas = array($tabla=>false,'fc_partida'=>$tabla,'fc_factura'=>'fc_partida');
        $campos_obligatorios = array();

        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios, columnas: $columnas);

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'Cuentas prediales';
    }

    public function alta_bd(array $keys_integra_ds = array('descripcion')): array|stdClass
    {
        if(!isset($this->registro['descripcion'])){
            $this->registro['descripcion'] = $this->registro['fc_partida_id'];
            $this->registro['descripcion'] .= $this->registro['cuenta_predial'];
        }
        $r_alta_bd = parent::alta_bd($keys_integra_ds); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar',data:  $r_alta_bd);
        }
        return $r_alta_bd;
    }

}