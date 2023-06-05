<?php

namespace gamboamartin\facturacion\models;

use base\orm\_modelo_parent;
use gamboamartin\errores\errores;
use PDO;
use stdClass;


class fc_retencion_p extends _p {
    public function __construct(PDO $link)
    {
        $tabla = 'fc_retencion_p';
        $columnas = array($tabla=>false,'fc_impuesto_p'=>$tabla,'fc_pago_pago'=>'fc_impuesto_p');
        $campos_obligatorios = array();


        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas);

        $this->NAMESPACE = __NAMESPACE__;
        $this->etiqueta = 'Retencion P';

        $this->modelo_p_part = new fc_retencion_p_part(link: $link);
        $this->modelo_dr_part = new fc_retencion_dr_part(link: $link);
    }




}