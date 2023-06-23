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
            'cat_sat_factor'=>$tabla,'fc_complemento_pago'=>'fc_pago','fc_factura'=>'fc_docto_relacionado');
        $campos_obligatorios = array();

        $renombres['com_tipo_cambio_pago']['nombre_original'] = 'com_tipo_cambio';
        $renombres['com_tipo_cambio_pago']['enlace'] = 'fc_pago_pago';
        $renombres['com_tipo_cambio_pago']['key'] = 'id';
        $renombres['com_tipo_cambio_pago']['key_enlace'] = 'com_tipo_cambio_id';

        $renombres['com_tipo_cambio_factura']['nombre_original'] = 'com_tipo_cambio';
        $renombres['com_tipo_cambio_factura']['enlace'] = 'fc_factura';
        $renombres['com_tipo_cambio_factura']['key'] = 'id';
        $renombres['com_tipo_cambio_factura']['key_enlace'] = 'com_tipo_cambio_id';

        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas, renombres: $renombres);

        $this->NAMESPACE = __NAMESPACE__;
        $this->etiqueta = 'Retencion Dr Part';

        $this->entidad_dr = 'fc_retencion_dr';
        $this->tipo_impuesto = 'retenciones';
    }














}