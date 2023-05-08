<?php
namespace gamboamartin\facturacion\models;

use base\orm\_modelo_parent_sin_codigo;
use gamboamartin\errores\errores;
use PDO;
use stdClass;

class fc_cuenta_predial_nc extends _cuenta_predial {
    public function __construct(PDO $link){
        $tabla = 'fc_cuenta_predial_nc';
        $columnas = array($tabla=>false,'fc_partida_nc'=>$tabla,'fc_nota_credito'=>'fc_partida_nc');
        $campos_obligatorios = array();

        $modelo_partida = new fc_partida_nc(link: $link);
        parent::__construct(link: $link, tabla: $tabla, modelo_partida: $modelo_partida,
            campos_obligatorios: $campos_obligatorios, columnas: $columnas);

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'Cuentas prediales';

    }




}