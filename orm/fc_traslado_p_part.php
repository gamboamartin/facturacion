<?php

namespace gamboamartin\facturacion\models;

use base\orm\_modelo_parent;
use gamboamartin\errores\errores;
use PDO;
use stdClass;


class fc_traslado_p_part extends _p_part {
    public function __construct(PDO $link)
    {
        $tabla = 'fc_traslado_p_part';
        $columnas = array($tabla=>false,'fc_traslado_p'=>$tabla,'cat_sat_tipo_factor'=>$tabla,
            'cat_sat_tipo_impuesto'=>$tabla,'cat_sat_factor'=>$tabla,'fc_impuesto_p'=>'fc_traslado_p',
            'fc_pago_pago'=>'fc_impuesto_p');
        $campos_obligatorios = array();


        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas);

        $this->NAMESPACE = __NAMESPACE__;
        $this->etiqueta = 'Traslado P Part';
        $this->key_p_id = 'fc_traslado_p_id';
    }


}