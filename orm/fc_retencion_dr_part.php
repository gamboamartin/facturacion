<?php

namespace gamboamartin\facturacion\models;

use base\orm\_modelo_parent;
use gamboamartin\cat_sat\models\cat_sat_factor;
use gamboamartin\errores\errores;
use PDO;
use stdClass;


class fc_retencion_dr_part extends _dr_part {
    public function __construct(PDO $link)
    {
        $tabla = 'fc_retencion_dr_part';
        $columnas = array($tabla=>false,'fc_retencion_dr'=>$tabla,'fc_impuesto_dr'=>'fc_retencion_dr',
            'fc_docto_relacionado'=>'fc_impuesto_dr','fc_pago_pago'=>'fc_docto_relacionado',
            'fc_pago'=>'fc_pago_pago','cat_sat_tipo_impuesto'=>$tabla,'cat_sat_tipo_factor'=>$tabla,
            'cat_sat_factor'=>$tabla,'fc_complemento_pago'=>'fc_pago');
        $campos_obligatorios = array();


        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas);

        $this->NAMESPACE = __NAMESPACE__;
        $this->etiqueta = 'Retencion Dr Part';

        $this->entidad_dr = 'fc_retencion_dr';
        $this->tipo_impuesto = 'retenciones';
    }














}