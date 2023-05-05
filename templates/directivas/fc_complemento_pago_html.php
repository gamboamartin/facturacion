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


class fc_complemento_pago_html extends _base_fc_html {



}