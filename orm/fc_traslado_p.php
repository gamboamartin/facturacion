<?php

namespace gamboamartin\facturacion\models;


use gamboamartin\errores\errores;
use PDO;
use stdClass;


class fc_traslado_p extends _p {
    public function __construct(PDO $link)
    {
        $tabla = 'fc_traslado_p';
        $columnas = array($tabla=>false,'fc_impuesto_p'=>$tabla,'fc_pago_pago'=>'fc_impuesto_p',
            'com_tipo_cambio'=>'fc_pago_pago');
        $campos_obligatorios = array();

        $no_duplicados[] = 'fc_impuesto_p_id';

        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas, no_duplicados: $no_duplicados);

        $this->NAMESPACE = __NAMESPACE__;
        $this->etiqueta = 'Traslado P';

        $this->modelo_p_part = new fc_traslado_p_part(link: $link);
        $this->modelo_dr_part = new fc_traslado_dr_part(link: $this->link);
    }


}