<?php

namespace gamboamartin\facturacion\models;

use base\orm\_modelo_parent;
use gamboamartin\errores\errores;
use PDO;
use stdClass;


class fc_retencion_p_part extends _p_part {
    public function __construct(PDO $link)
    {
        $tabla = 'fc_retencion_p_part';
        $columnas = array($tabla=>false,'fc_retencion_p'=>$tabla,'cat_sat_tipo_factor'=>$tabla,
            'cat_sat_tipo_impuesto'=>$tabla,'cat_sat_factor'=>$tabla,'fc_impuesto_p'=>'fc_retencion_p',
            'fc_pago_pago'=>'fc_impuesto_p');
        $campos_obligatorios = array();


        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas);

        $this->NAMESPACE = __NAMESPACE__;
        $this->etiqueta = 'Retencion P Part';
        $this->key_p_id = 'fc_retencion_p_id';
    }


}