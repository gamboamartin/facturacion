<?php
namespace gamboamartin\facturacion\html;

use gamboamartin\errores\errores;
use gamboamartin\facturacion\controllers\controlador_fc_factura;
use gamboamartin\facturacion\models\fc_factura;
use gamboamartin\system\html_controler;

use gamboamartin\validacion\validacion;
use html\cat_sat_factor_html;
use html\cat_sat_forma_pago_html;
use html\cat_sat_metodo_pago_html;
use html\cat_sat_moneda_html;
use html\cat_sat_regimen_fiscal_html;
use html\cat_sat_tipo_de_comprobante_html;
use html\cat_sat_tipo_factor_html;
use html\cat_sat_tipo_impuesto_html;
use html\cat_sat_uso_cfdi_html;
use html\com_producto_html;
use html\com_sucursal_html;
use html\com_tipo_cambio_html;
use html\dp_calle_pertenece_html;
use html\dp_colonia_postal_html;
use html\dp_cp_html;
use html\dp_estado_html;
use html\dp_municipio_html;
use html\dp_pais_html;
use models\base\limpieza;
use PDO;
use stdClass;


class fc_nota_credito_html extends _base_fc_html {

    public function input_monto_aplicado_factura(array $class_css, int $cols, stdClass $row_upd, bool $value_vacio, bool $disabled = false,
                                string $name = 'monto_aplicado_factura', string $place_holder = 'Monto'): array|string
    {

        if($cols<=0){
            return $this->error->error(mensaje: 'Error cold debe ser mayor a 0', data: $cols);
        }
        if($cols>=13){
            return $this->error->error(mensaje: 'Error cold debe ser menor o igual a  12', data: $cols);
        }

        $html =$this->directivas->input_text_sin_label(class_css: $class_css, cols: $cols, disabled: $disabled,
            name: $name, place_holder: $place_holder, required: false, row_upd: $row_upd, value_vacio: $value_vacio);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar input', data: $html);
        }


        return $html;
    }

}