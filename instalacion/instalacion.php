<?php
namespace gamboamartin\facturacion\instalacion;

use gamboamartin\administrador\models\_instalacion;
use gamboamartin\errores\errores;
use PDO;
use stdClass;

class instalacion
{

    private function fc_complemento_pago(PDO $link): array|stdClass
    {
        $init = (new _instalacion(link: $link));
        $foraneas = array();
        $foraneas[] = 'fc_csd_id';
        $foraneas[] = 'cat_sat_forma_pago_id';
        $foraneas[] = 'cat_sat_metodo_pago_id';
        $foraneas[] = 'cat_sat_moneda_id';
        $foraneas[] = 'com_tipo_cambio_id';
        $foraneas[] = 'cat_sat_uso_cfdi_id';
        $foraneas[] = 'cat_sat_tipo_de_comprobante_id';
        $foraneas[] = 'dp_calle_pertenece_id';
        $foraneas[] = 'cat_sat_regimen_fiscal_id';
        $foraneas[] = 'com_sucursal_id';

        $foraneas_r = $init->foraneas(foraneas: $foraneas,table:  'fc_complemento_pago');

        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar foranea', data:  $foraneas_r);
        }

        $campos = new stdClass();

        $campos->cantidad = new stdClass();
        $campos->cantidad->tipo_dato = 'double';
        $campos->cantidad->default = '0';
        $campos->cantidad->longitud = '100,2';

        $campos->valor_unitario = new stdClass();
        $campos->valor_unitario->tipo_dato = 'double';
        $campos->valor_unitario->default = '0';
        $campos->valor_unitario->longitud = '100,2';

        $campos->descuento = new stdClass();
        $campos->descuento->tipo_dato = 'double';
        $campos->descuento->default = '0';
        $campos->descuento->longitud = '100,2';

        $campos->total_traslados = new stdClass();
        $campos->total_traslados->tipo_dato = 'double';
        $campos->total_traslados->default = '0';
        $campos->total_traslados->longitud = '100,2';

        $campos->total_retenciones = new stdClass();
        $campos->total_retenciones->tipo_dato = 'double';
        $campos->total_retenciones->default = '0';
        $campos->total_retenciones->longitud = '100,2';

        $campos->aplica_saldo = new stdClass();
        $campos->aplica_saldo->default = 'inactivo';

        $campos->total = new stdClass();
        $campos->total->tipo_dato = 'double';
        $campos->total->default = '0';
        $campos->total->longitud = '100,2';

        $campos->monto_pago_nc = new stdClass();
        $campos->monto_pago_nc->tipo_dato = 'double';
        $campos->monto_pago_nc->default = '0';
        $campos->monto_pago_nc->longitud = '100,2';

        $campos->monto_pago_cp = new stdClass();
        $campos->monto_pago_cp->tipo_dato = 'double';
        $campos->monto_pago_cp->default = '0';
        $campos->monto_pago_cp->longitud = '100,2';

        $campos->saldo = new stdClass();
        $campos->saldo->tipo_dato = 'double';
        $campos->saldo->default = '0';
        $campos->saldo->longitud = '100,2';

        $campos->monto_saldo_aplicado = new stdClass();
        $campos->monto_saldo_aplicado->tipo_dato = 'double';
        $campos->monto_saldo_aplicado->default = '0';
        $campos->monto_saldo_aplicado->longitud = '100,2';

        $campos->folio_fiscal = new stdClass();
        $campos->folio_fiscal->default = 'SIN ASIGNAR';

        $campos->etapa = new stdClass();
        $campos->etapa->default = 'ALTA';

        $campos->es_plantilla = new stdClass();
        $campos->es_plantilla->default = 'inactivo';

        $campos->total_descuento = new stdClass();
        $campos->total_descuento->tipo_dato = 'double';
        $campos->total_descuento->default = '0';
        $campos->total_descuento->longitud = '100,2';

        $campos->sub_total_base = new stdClass();
        $campos->sub_total_base->tipo_dato = 'double';
        $campos->sub_total_base->default = '0';
        $campos->sub_total_base->longitud = '100,2';

        $campos->sub_total = new stdClass();
        $campos->sub_total->tipo_dato = 'double';
        $campos->sub_total->default = '0';
        $campos->sub_total->longitud = '100,2';

        $campos_r = $init->add_columns(campos: $campos,table:  'fc_complemento_pago');

        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar foranea', data:  $campos_r);
        }

        $result = new stdClass();
        $result->foranenas = $foraneas_r;
        $result->campos = $campos_r;

        return $result;

    }

    private function fc_factura(PDO $link): array|stdClass
    {
        $init = (new _instalacion(link: $link));
        $foraneas = array();
        $foraneas[] = 'fc_csd_id';
        $foraneas[] = 'cat_sat_forma_pago_id';
        $foraneas[] = 'cat_sat_metodo_pago_id';
        $foraneas[] = 'cat_sat_moneda_id';
        $foraneas[] = 'com_tipo_cambio_id';
        $foraneas[] = 'cat_sat_uso_cfdi_id';
        $foraneas[] = 'cat_sat_tipo_de_comprobante_id';
        $foraneas[] = 'dp_calle_pertenece_id';
        $foraneas[] = 'cat_sat_regimen_fiscal_id';
        $foraneas[] = 'com_sucursal_id';

        $foraneas_r = $init->foraneas(foraneas: $foraneas,table:  'fc_factura');

        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar foranea', data:  $foraneas_r);
        }

        $campos = new stdClass();

        $campos->cantidad = new stdClass();
        $campos->cantidad->tipo_dato = 'double';
        $campos->cantidad->default = '0';
        $campos->cantidad->longitud = '100,2';

        $campos->valor_unitario = new stdClass();
        $campos->valor_unitario->tipo_dato = 'double';
        $campos->valor_unitario->default = '0';
        $campos->valor_unitario->longitud = '100,2';

        $campos->descuento = new stdClass();
        $campos->descuento->tipo_dato = 'double';
        $campos->descuento->default = '0';
        $campos->descuento->longitud = '100,2';

        $campos->total_traslados = new stdClass();
        $campos->total_traslados->tipo_dato = 'double';
        $campos->total_traslados->default = '0';
        $campos->total_traslados->longitud = '100,2';

        $campos->total_retenciones = new stdClass();
        $campos->total_retenciones->tipo_dato = 'double';
        $campos->total_retenciones->default = '0';
        $campos->total_retenciones->longitud = '100,2';

        $campos->aplica_saldo = new stdClass();
        $campos->aplica_saldo->default = 'inactivo';

        $campos->total = new stdClass();
        $campos->total->tipo_dato = 'double';
        $campos->total->default = '0';
        $campos->total->longitud = '100,2';

        $campos->monto_pago_nc = new stdClass();
        $campos->monto_pago_nc->tipo_dato = 'double';
        $campos->monto_pago_nc->default = '0';
        $campos->monto_pago_nc->longitud = '100,2';

        $campos->monto_pago_cp = new stdClass();
        $campos->monto_pago_cp->tipo_dato = 'double';
        $campos->monto_pago_cp->default = '0';
        $campos->monto_pago_cp->longitud = '100,2';

        $campos->saldo = new stdClass();
        $campos->saldo->tipo_dato = 'double';
        $campos->saldo->default = '0';
        $campos->saldo->longitud = '100,2';

        $campos->monto_saldo_aplicado = new stdClass();
        $campos->monto_saldo_aplicado->tipo_dato = 'double';
        $campos->monto_saldo_aplicado->default = '0';
        $campos->monto_saldo_aplicado->longitud = '100,2';

        $campos->folio_fiscal = new stdClass();
        $campos->folio_fiscal->default = 'SIN ASIGNAR';

        $campos->etapa = new stdClass();
        $campos->etapa->default = 'ALTA';

        $campos->es_plantilla = new stdClass();
        $campos->es_plantilla->default = 'inactivo';

        $campos_r = $init->add_columns(campos: $campos,table:  'fc_factura');

        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar foranea', data:  $campos_r);
        }

        $result = new stdClass();
        $result->foraneas = $foraneas_r;
        $result->campos_r = $campos_r;

        return $result;


    }

    private function fc_nota_credito(PDO $link): array|stdClass
    {
        $init = (new _instalacion(link: $link));
        $foraneas = array();
        $foraneas[] = 'fc_csd_id';
        $foraneas[] = 'cat_sat_forma_pago_id';
        $foraneas[] = 'cat_sat_metodo_pago_id';
        $foraneas[] = 'cat_sat_moneda_id';
        $foraneas[] = 'com_tipo_cambio_id';
        $foraneas[] = 'cat_sat_uso_cfdi_id';
        $foraneas[] = 'cat_sat_tipo_de_comprobante_id';
        $foraneas[] = 'dp_calle_pertenece_id';
        $foraneas[] = 'cat_sat_regimen_fiscal_id';
        $foraneas[] = 'com_sucursal_id';

        $foraneas_r = $init->foraneas(foraneas: $foraneas,table:  'fc_nota_credito');

        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar foranea', data:  $foraneas_r);
        }

        $campos = new stdClass();

        $campos->cantidad = new stdClass();
        $campos->cantidad->tipo_dato = 'double';
        $campos->cantidad->default = '0';
        $campos->cantidad->longitud = '100,2';

        $campos->valor_unitario = new stdClass();
        $campos->valor_unitario->tipo_dato = 'double';
        $campos->valor_unitario->default = '0';
        $campos->valor_unitario->longitud = '100,2';

        $campos->descuento = new stdClass();
        $campos->descuento->tipo_dato = 'double';
        $campos->descuento->default = '0';
        $campos->descuento->longitud = '100,2';

        $campos->total_traslados = new stdClass();
        $campos->total_traslados->tipo_dato = 'double';
        $campos->total_traslados->default = '0';
        $campos->total_traslados->longitud = '100,2';

        $campos->total_retenciones = new stdClass();
        $campos->total_retenciones->tipo_dato = 'double';
        $campos->total_retenciones->default = '0';
        $campos->total_retenciones->longitud = '100,2';

        $campos->aplica_saldo = new stdClass();
        $campos->aplica_saldo->default = 'inactivo';

        $campos->total = new stdClass();
        $campos->total->tipo_dato = 'double';
        $campos->total->default = '0';
        $campos->total->longitud = '100,2';

        $campos->monto_pago_nc = new stdClass();
        $campos->monto_pago_nc->tipo_dato = 'double';
        $campos->monto_pago_nc->default = '0';
        $campos->monto_pago_nc->longitud = '100,2';

        $campos->monto_pago_cp = new stdClass();
        $campos->monto_pago_cp->tipo_dato = 'double';
        $campos->monto_pago_cp->default = '0';
        $campos->monto_pago_cp->longitud = '100,2';

        $campos->saldo = new stdClass();
        $campos->saldo->tipo_dato = 'double';
        $campos->saldo->default = '0';
        $campos->saldo->longitud = '100,2';

        $campos->monto_saldo_aplicado = new stdClass();
        $campos->monto_saldo_aplicado->tipo_dato = 'double';
        $campos->monto_saldo_aplicado->default = '0';
        $campos->monto_saldo_aplicado->longitud = '100,2';

        $campos->folio_fiscal = new stdClass();
        $campos->folio_fiscal->default = 'SIN ASIGNAR';

        $campos->etapa = new stdClass();
        $campos->etapa->default = 'ALTA';

        $campos->es_plantilla = new stdClass();
        $campos->es_plantilla->default = 'inactivo';

        $campos_r = $init->add_columns(campos: $campos,table:  'fc_nota_credito');

        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar foranea', data:  $campos_r);
        }

        $result = new stdClass();
        $result->foraneas = $foraneas_r;
        $result->campos_r = $campos_r;

        return $result;


    }

    private function fc_partida(PDO $link): array|stdClass
    {
        $init = (new _instalacion(link: $link));
        $foraneas = array();
        $foraneas[] = 'com_producto_id';
        $foraneas[] = 'fc_factura_id';


        $foraneas_r = $init->foraneas(foraneas: $foraneas,table:  'fc_partida');

        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar foranea', data:  $foraneas_r);
        }


        $campos = new stdClass();

        $campos->cantidad = new stdClass();
        $campos->cantidad->tipo_dato = 'double';
        $campos->cantidad->default = '0';
        $campos->cantidad->longitud = '100,2';

        $campos->valor_unitario = new stdClass();
        $campos->valor_unitario->tipo_dato = 'double';
        $campos->valor_unitario->default = '0';
        $campos->valor_unitario->longitud = '100,2';

        $campos->descuento = new stdClass();
        $campos->descuento->tipo_dato = 'double';
        $campos->descuento->default = '0';
        $campos->descuento->longitud = '100,2';

        $campos->sub_total = new stdClass();
        $campos->sub_total->tipo_dato = 'double';
        $campos->sub_total->default = '0';
        $campos->sub_total->longitud = '100,2';

        $campos->total_traslados = new stdClass();
        $campos->total_traslados->tipo_dato = 'double';
        $campos->total_traslados->default = '0';
        $campos->total_traslados->longitud = '100,2';

        $campos->total_retenciones = new stdClass();
        $campos->total_retenciones->tipo_dato = 'double';
        $campos->total_retenciones->default = '0';
        $campos->total_retenciones->longitud = '100,2';

        $campos->sub_total_base = new stdClass();
        $campos->sub_total_base->tipo_dato = 'double';
        $campos->sub_total_base->default = '0';
        $campos->sub_total_base->longitud = '100,2';

        $campos->total = new stdClass();
        $campos->total->tipo_dato = 'double';
        $campos->total->default = '0';
        $campos->total->longitud = '100,2';


        $campos_r = $init->add_columns(campos: $campos,table:  'fc_partida');

        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar foranea', data:  $campos_r);
        }


        $result = new stdClass();
        $result->foranenas = $foraneas_r;
        $result->campos = $campos_r;

        return $result;

    }

    private function fc_partida_cp(PDO $link): array|stdClass
    {
        $init = (new _instalacion(link: $link));
        $foraneas = array();
        $foraneas[] = 'com_producto_id';
        $foraneas[] = 'fc_complemento_pago_id';


        $foraneas_r = $init->foraneas(foraneas: $foraneas,table:  'fc_partida_cp');

        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar foranea', data:  $foraneas_r);
        }


        $campos = new stdClass();

        $campos->sub_total = new stdClass();
        $campos->sub_total->tipo_dato = 'double';
        $campos->sub_total->default = '0';
        $campos->sub_total->longitud = '100,2';

        $campos->total_traslados = new stdClass();
        $campos->total_traslados->tipo_dato = 'double';
        $campos->total_traslados->default = '0';
        $campos->total_traslados->longitud = '100,2';

        $campos->total_retenciones = new stdClass();
        $campos->total_retenciones->tipo_dato = 'double';
        $campos->total_retenciones->default = '0';
        $campos->total_retenciones->longitud = '100,2';

        $campos->sub_total_base = new stdClass();
        $campos->sub_total_base->tipo_dato = 'double';
        $campos->sub_total_base->default = '0';
        $campos->sub_total_base->longitud = '100,2';

        $campos->total = new stdClass();
        $campos->total->tipo_dato = 'double';
        $campos->total->default = '0';
        $campos->total->longitud = '100,2';

        $campos_r = $init->add_columns(campos: $campos,table:  'fc_partida_cp');

        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar foranea', data:  $campos_r);
        }

        $result = new stdClass();
        $result->foranenas = $foraneas_r;
        $result->campos = $campos_r;

        return $result;

    }

    private function fc_partida_nc(PDO $link): array|stdClass
    {
        $init = (new _instalacion(link: $link));
        $foraneas = array();
        $foraneas[] = 'com_producto_id';
        $foraneas[] = 'fc_nota_credito_id';

        $foraneas_r = $init->foraneas(foraneas: $foraneas,table:  'fc_partida_nc');

        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar foranea', data:  $foraneas_r);
        }


        $campos = new stdClass();

        $campos->cantidad = new stdClass();
        $campos->cantidad->tipo_dato = 'double';
        $campos->cantidad->default = '0';
        $campos->cantidad->longitud = '100,2';

        $campos->valor_unitario = new stdClass();
        $campos->valor_unitario->tipo_dato = 'double';
        $campos->valor_unitario->default = '0';
        $campos->valor_unitario->longitud = '100,2';

        $campos->descuento = new stdClass();
        $campos->descuento->tipo_dato = 'double';
        $campos->descuento->default = '0';
        $campos->descuento->longitud = '100,2';

        $campos->sub_total = new stdClass();
        $campos->sub_total->tipo_dato = 'double';
        $campos->sub_total->default = '0';
        $campos->sub_total->longitud = '100,2';

        $campos->total_traslados = new stdClass();
        $campos->total_traslados->tipo_dato = 'double';
        $campos->total_traslados->default = '0';
        $campos->total_traslados->longitud = '100,2';

        $campos->total_retenciones = new stdClass();
        $campos->total_retenciones->tipo_dato = 'double';
        $campos->total_retenciones->default = '0';
        $campos->total_retenciones->longitud = '100,2';

        $campos->sub_total_base = new stdClass();
        $campos->sub_total_base->tipo_dato = 'double';
        $campos->sub_total_base->default = '0';
        $campos->sub_total_base->longitud = '100,2';

        $campos->total = new stdClass();
        $campos->total->tipo_dato = 'double';
        $campos->total->default = '0';
        $campos->total->longitud = '100,2';


        $campos_r = $init->add_columns(campos: $campos,table:  'fc_partida_nc');

        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar foranea', data:  $campos_r);
        }

        $result = new stdClass();
        $result->foranenas = $foraneas_r;
        $result->campos = $campos_r;

        return $result;

    }

    private function fc_retenido(PDO $link): array|stdClass
    {
        $init = (new _instalacion(link: $link));

        $foraneas = array();
        $foraneas[] = 'fc_partida_id';
        $foraneas[] = 'cat_sat_tipo_factor_id';
        $foraneas[] = 'cat_sat_factor_id';
        $foraneas[] = 'cat_sat_tipo_impuesto_id';


        $foraneas_r = $init->foraneas(foraneas: $foraneas,table:  'fc_retenido');

        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar foranea', data:  $foraneas_r);
        }


        $campos = new stdClass();

        $campos->total = new stdClass();
        $campos->total->tipo_dato = 'double';
        $campos->total->default = '0';
        $campos->total->longitud = '100,2';

        $campos_r = $init->add_columns(campos: $campos,table:  'fc_retenido');

        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar foranea', data:  $campos_r);
        }

        $result = new stdClass();
        $result->foranenas = $foraneas_r;
        $result->campos = $campos_r;

        return $result;

    }

    private function fc_retenido_nc(PDO $link): array|stdClass
    {
        $init = (new _instalacion(link: $link));

        $foraneas = array();
        $foraneas[] = 'fc_partida_nc_id';
        $foraneas[] = 'cat_sat_tipo_factor_id';
        $foraneas[] = 'cat_sat_factor_id';
        $foraneas[] = 'cat_sat_tipo_impuesto_id';


        $foraneas_r = $init->foraneas(foraneas: $foraneas,table:  'fc_retenido_nc');

        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar foranea', data:  $foraneas_r);
        }


        $campos = new stdClass();

        $campos->total = new stdClass();
        $campos->total->tipo_dato = 'double';
        $campos->total->default = '0';
        $campos->total->longitud = '100,2';

        $campos_r = $init->add_columns(campos: $campos,table:  'fc_retenido_nc');

        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar foranea', data:  $campos_r);
        }

        $result = new stdClass();
        $result->foranenas = $foraneas_r;
        $result->campos = $campos_r;

        return $result;

    }

    private function fc_traslado(PDO $link): array|stdClass
    {
        $init = (new _instalacion(link: $link));
        $foraneas = array();
        $foraneas[] = 'fc_partida_id';
        $foraneas[] = 'cat_sat_tipo_factor_id';
        $foraneas[] = 'cat_sat_factor_id';
        $foraneas[] = 'cat_sat_tipo_impuesto_id';


        $foraneas_r = $init->foraneas(foraneas: $foraneas,table:  'fc_traslado');

        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar foranea', data:  $foraneas_r);
        }


        $campos = new stdClass();

        $campos->total = new stdClass();
        $campos->total->tipo_dato = 'double';
        $campos->total->default = '0';
        $campos->total->longitud = '100,2';


        $campos_r = $init->add_columns(campos: $campos,table:  'fc_traslado');

        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar foranea', data:  $campos_r);
        }

        $result = new stdClass();
        $result->foranenas = $foraneas_r;
        $result->campos = $campos_r;

        return $result;

    }
    final public function instala(PDO $link)
    {

        $result = new stdClass();

        $fc_factura = $this->fc_factura(link: $link);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar fc_factura', data:  $fc_factura);
        }
        $result->fc_factura = $fc_factura;

        $fc_complemento_pago = $this->fc_complemento_pago(link: $link);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar fc_complemento_pago', data:  $fc_complemento_pago);
        }
        $result->fc_complemento_pago = $fc_complemento_pago;

        $fc_partida = $this->fc_partida(link: $link);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar fc_partida', data:  $fc_partida);
        }
        $result->fc_partida = $fc_partida;

        $fc_traslado = $this->fc_traslado(link: $link);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar fc_traslado', data:  $fc_traslado);
        }
        $result->fc_traslado = $fc_traslado;


        $fc_partida_nc = $this->fc_partida_nc(link: $link);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar fc_partida_nc', data:  $fc_partida_nc);
        }
        $result->fc_partida_nc = $fc_partida_nc;

        $fc_partida_cp = $this->fc_partida_cp(link: $link);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar fc_partida_nc', data:  $fc_partida_cp);
        }
        $result->fc_partida_cp = $fc_partida_cp;

        $fc_retenido = $this->fc_retenido(link: $link);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar fc_retenido', data:  $fc_retenido);
        }
        $result->fc_retenido = $fc_retenido;

        $fc_nota_credito = $this->fc_nota_credito(link: $link);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar fc_retenido', data:  $fc_nota_credito);
        }
        $result->fc_nota_credito = $fc_nota_credito;

        $fc_retenido_nc = $this->fc_retenido_nc(link: $link);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al ajustar fc_retenido_nc', data:  $fc_retenido_nc);
        }
        $result->fc_retenido_nc = $fc_retenido_nc;

        return $result;

    }

}
