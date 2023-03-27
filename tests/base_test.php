<?php
namespace gamboamartin\facturacion\tests;
use base\orm\modelo_base;

use gamboamartin\cat_sat\models\cat_sat_factor;
use gamboamartin\cat_sat\models\cat_sat_forma_pago;
use gamboamartin\cat_sat\models\cat_sat_metodo_pago;
use gamboamartin\cat_sat\models\cat_sat_moneda;
use gamboamartin\cat_sat\models\cat_sat_tipo_factor;
use gamboamartin\comercial\models\com_sucursal;
use gamboamartin\comercial\models\com_tipo_cambio;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\fc_conf_retenido;
use gamboamartin\facturacion\models\fc_conf_traslado;
use gamboamartin\facturacion\models\fc_csd;
use gamboamartin\facturacion\models\fc_factura;
use gamboamartin\facturacion\models\fc_partida;


use gamboamartin\organigrama\models\org_sucursal;
use PDO;


class base_test{

    public function alta_cat_sat_factor(PDO $link, string $codigo = '16', float $factor = .16, int $id = 1): array|\stdClass
    {
        $alta = (new \gamboamartin\cat_sat\tests\base_test())->alta_cat_sat_factor(link: $link, codigo: $codigo, factor: $factor, id: $id);
        if(errores::$error){
            return (new errores())->error('Error al insertar', $alta);

        }
        return $alta;
    }

    public function alta_cat_sat_forma_pago(PDO $link, int $id): array|\stdClass
    {
        $alta = (new \gamboamartin\cat_sat\tests\base_test())->alta_cat_sat_forma_pago(link: $link, id: $id);
        if(errores::$error){
            return (new errores())->error('Error al insertar', $alta);

        }
        return $alta;
    }

    public function alta_cat_sat_metodo_pago(PDO $link, int $id): array|\stdClass
    {
        $alta = (new \gamboamartin\cat_sat\tests\base_test())->alta_cat_sat_metodo_pago(link: $link, id: $id);
        if(errores::$error){
            return (new errores())->error('Error al insertar', $alta);

        }
        return $alta;
    }

    public function alta_cat_sat_moneda(PDO $link, int $id): array|\stdClass
    {
        $alta = (new \gamboamartin\cat_sat\tests\base_test())->alta_cat_sat_moneda(link: $link, codigo: 'MXN', id: $id);
        if(errores::$error){
            return (new errores())->error('Error al insertar moneda', $alta);

        }
        return $alta;
    }

    public function alta_cat_sat_tipo_factor(PDO $link, string $descripcion = 'Tasa', int $id = 1): array|\stdClass
    {
        $alta = (new \gamboamartin\cat_sat\tests\base_test())->alta_cat_sat_tipo_factor(link: $link,
            descripcion: $descripcion, id: $id);
        if(errores::$error){
            return (new errores())->error('Error al insertar', $alta);

        }
        return $alta;
    }


    public function alta_com_cliente(PDO $link): array|\stdClass
    {
        $alta = (new \gamboamartin\comercial\test\base_test())->alta_com_cliente($link);
        if(errores::$error){
            return (new errores())->error('Error al insertar', $alta);

        }
        return $alta;
    }

    public function alta_com_sucursal(PDO $link, int $id = 1): array|\stdClass
    {

        $alta = (new \gamboamartin\comercial\test\base_test())->alta_com_sucursal(link: $link,id: $id);
        if(errores::$error){
            return (new errores())->error('Error al insertar', $alta);

        }
        return $alta;
    }

    public function alta_com_tipo_cambio(PDO $link, int $id): array|\stdClass
    {
        $alta = (new \gamboamartin\comercial\test\base_test())->alta_com_tipo_cambio(link: $link, id: $id);
        if(errores::$error){
            return (new errores())->error('Error al insertar', $alta);

        }
        return $alta;
    }

    public function alta_fc_conf_retenido(PDO $link, string $cat_sat_factor_codigo = '1.25',
                                          float $cat_sat_factor_factor = .0125, int $cat_sat_factor_id = 2,
                                          int $cat_sat_tipo_factor_id = 1, int $cat_sat_tipo_impuesto_id = 1,
                                          int $com_producto_id = 1, int $id = 1): array|\stdClass
    {


        $existe = (new cat_sat_tipo_factor($link))->existe_by_id(registro_id: $cat_sat_tipo_factor_id);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al verificar si existe ', data: $existe);
        }
        if(!$existe) {
            $alta = $this->alta_cat_sat_tipo_factor(link: $link, id: $cat_sat_tipo_factor_id);
            if (errores::$error) {
                return (new errores())->error(mensaje: 'Error al insertar', data: $alta);
            }
        }

        $existe = (new cat_sat_factor($link))->existe_by_id(registro_id: $cat_sat_factor_id);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al verificar si existe ', data: $existe);
        }
        if(!$existe) {
            $alta = $this->alta_cat_sat_factor(link: $link, codigo: $cat_sat_factor_codigo,
                factor: $cat_sat_factor_factor, id: $cat_sat_factor_id);
            if (errores::$error) {
                return (new errores())->error(mensaje: 'Error al insertar', data: $alta);
            }
        }


        $registro = array();
        $registro['id'] = $id;
        $registro['com_producto_id'] = $com_producto_id;
        $registro['cat_sat_tipo_factor_id'] = $cat_sat_tipo_factor_id;
        $registro['cat_sat_factor_id'] = $cat_sat_factor_id;
        $registro['cat_sat_tipo_impuesto_id'] = $cat_sat_tipo_impuesto_id;


        $alta = (new fc_conf_retenido($link))->alta_registro($registro);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al insertar', data: $alta);

        }
        return $alta;
    }

    public function alta_fc_conf_traslado(PDO $link, string $cat_sat_factor_codigo = '16',
                                          float $cat_sat_factor_factor = .16, int $cat_sat_factor_id = 1,
                                          int $cat_sat_tipo_factor_id = 1, int $cat_sat_tipo_impuesto_id = 1,
                                          int $com_producto_id = 1, int $id = 1): array|\stdClass
    {


        $existe = (new cat_sat_tipo_factor($link))->existe_by_id(registro_id: $cat_sat_tipo_factor_id);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al verificar si existe ', data: $existe);
        }
        if(!$existe) {
            $alta = $this->alta_cat_sat_tipo_factor(link: $link, id: $cat_sat_tipo_factor_id);
            if (errores::$error) {
                return (new errores())->error(mensaje: 'Error al insertar', data: $alta);
            }
        }

        $existe = (new cat_sat_factor($link))->existe_by_id(registro_id: $cat_sat_factor_id);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al verificar si existe ', data: $existe);
        }
        if(!$existe) {
            $alta = $this->alta_cat_sat_factor(link: $link, codigo: $cat_sat_factor_codigo, factor: $cat_sat_factor_factor, id: $cat_sat_factor_id);
            if (errores::$error) {
                return (new errores())->error(mensaje: 'Error al insertar', data: $alta);
            }
        }


        $registro = array();
        $registro['id'] = $id;
        $registro['com_producto_id'] = $com_producto_id;
        $registro['cat_sat_tipo_factor_id'] = $cat_sat_tipo_factor_id;
        $registro['cat_sat_factor_id'] = $cat_sat_factor_id;
        $registro['cat_sat_tipo_impuesto_id'] = $cat_sat_tipo_impuesto_id;


        $alta = (new fc_conf_traslado($link))->alta_registro($registro);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al insertar', data: $alta);

        }
        return $alta;
    }


    public function alta_fc_csd(PDO $link, int $id = 1, int $org_sucursal_id = 1): array|\stdClass
    {

        $existe = (new org_sucursal($link))->existe_by_id(registro_id: $org_sucursal_id);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al verificar si existe ', data: $existe);
        }
        if(!$existe) {
            $alta = $this->alta_org_sucursal(link: $link, id: $org_sucursal_id);
            if (errores::$error) {
                return (new errores())->error(mensaje: 'Error al insertar sucursal', data: $alta);
            }
        }


        $registro = array();
        $registro['id'] = $id;
        $registro['codigo'] = 1;
        $registro['descripcion'] = 1;
        $registro['serie'] = 1;
        $registro['org_sucursal_id'] = $org_sucursal_id;
        $registro['descripcion_select'] = 1;
        $registro['alias'] = 1;
        $registro['codigo_bis'] = 1;


        $alta = (new fc_csd($link))->alta_registro($registro);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al insertar', data: $alta);

        }
        return $alta;
    }

    public function alta_fc_factura(PDO $link, int $cat_sat_forma_pago_id = 1, int $cat_sat_metodo_pago_id = 2,
                                    int $cat_sat_moneda_id = 999, string $codigo = '1', int $com_sucursal_id = 1,
                                    int $com_tipo_cambio_id = 1, int $fc_csd_id = 1, int $id = 1): array|\stdClass
    {


        $existe = (new com_sucursal($link))->existe_by_id(registro_id: $com_sucursal_id);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al verificar si existe factura', data: $existe);
        }
        if(!$existe) {
            $alta = $this->alta_com_sucursal(link: $link, id: $com_sucursal_id);
            if (errores::$error) {
                return (new errores())->error(mensaje: 'Error al insertar sucursal', data: $alta);
            }
        }

        $existe = (new com_tipo_cambio($link))->existe_by_id(registro_id: $com_tipo_cambio_id);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al verificar si existe ', data: $existe);
        }
        if(!$existe) {
            $alta = $this->alta_com_tipo_cambio(link: $link, id: $com_tipo_cambio_id);
            if (errores::$error) {
                return (new errores())->error(mensaje: 'Error al insertar ', data: $alta);
            }
        }

        $existe = (new fc_csd($link))->existe_by_id(registro_id: $fc_csd_id);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al verificar si existe ', data: $existe);
        }
        if(!$existe) {
            $alta = $this->alta_fc_csd(link: $link, id: $fc_csd_id);
            if (errores::$error) {
                return (new errores())->error(mensaje: 'Error al insertar csd', data: $alta);
            }
        }

        $existe = (new cat_sat_forma_pago($link))->existe_by_id(registro_id: $cat_sat_forma_pago_id);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al verificar si existe ', data: $existe);
        }
        if(!$existe) {
            $alta = $this->alta_cat_sat_forma_pago(link: $link, id: $cat_sat_forma_pago_id);
            if (errores::$error) {
                return (new errores())->error(mensaje: 'Error al insertar ', data: $alta);
            }
        }

        $existe = (new cat_sat_metodo_pago($link))->existe_by_id(registro_id: $cat_sat_metodo_pago_id);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al verificar si existe ', data: $existe);
        }
        if(!$existe) {
            $alta = $this->alta_cat_sat_metodo_pago(link: $link, id: $cat_sat_metodo_pago_id);
            if (errores::$error) {
                return (new errores())->error(mensaje: 'Error al insertar ', data: $alta);
            }
        }

        $existe = (new cat_sat_moneda($link))->filtro_and(filtro: array("cat_sat_moneda.codigo" => "MXN"));
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al verificar si existe ', data: $existe);
        }

        if($existe->n_registros == 0) {
            $alta = $this->alta_cat_sat_moneda(link: $link, id: $cat_sat_moneda_id);
            if (errores::$error) {
                return (new errores())->error(mensaje: 'Error al insertar moneda', data: $alta);
            }

            $cat_sat_moneda_id = $alta->registro_id;
        } else {
            $cat_sat_moneda_id = $existe->registros[0]['cat_sat_moneda_id'];
        }

        $registro = array();
        $registro['id'] = $id;
        $registro['codigo'] = $codigo;
        $registro['descripcion'] = 1;
        $registro['fc_csd_id'] = $fc_csd_id;
        $registro['com_sucursal_id'] = $com_sucursal_id;
        $registro['serie'] = 1;
        $registro['folio'] = 1;
        $registro['exportacion'] = 1;
        $registro['cat_sat_forma_pago_id'] = $cat_sat_forma_pago_id;
        $registro['cat_sat_metodo_pago_id'] = $cat_sat_metodo_pago_id;
        $registro['cat_sat_moneda_id'] = $cat_sat_moneda_id;
        $registro['com_tipo_cambio_id'] = $com_tipo_cambio_id;
        $registro['cat_sat_uso_cfdi_id'] = 1;
        $registro['cat_sat_tipo_de_comprobante_id'] = 1;



        $alta = (new fc_factura($link))->alta_registro($registro);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al insertar', data: $alta);

        }
        return $alta;
    }

    public function alta_fc_partida(PDO $link, string $codigo = '1', float $cantidad = 1, string $descripcion = '1',
                                    float $descuento = 0, int $fc_factura_id = 1, int $id = 1,
                                    float $valor_unitario = 1): array|\stdClass
    {

        $existe = (new fc_factura($link))->existe_by_id(registro_id: $fc_factura_id);
        if(errores::$error){
            return (new errores())->error('Error al validar si existe', $existe);
        }
        if(!$existe) {
            $alta = $this->alta_fc_factura($link);
            if (errores::$error) {
                return (new errores())->error('Error al insertar factura', $alta);
            }
        }

        $registro = array();
        $registro['id'] = $id;
        $registro['codigo'] = $codigo;
        $registro['descripcion'] = $descripcion;
        $registro['cantidad'] = $cantidad;
        $registro['valor_unitario'] = $valor_unitario;
        $registro['fc_factura_id'] = $fc_factura_id;
        $registro['com_producto_id'] = 1;
        $registro['codigo_bis'] = 1;
        $registro['descuento'] = $descuento;


        $alta = (new fc_partida($link))->alta_registro($registro);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al insertar', data: $alta);

        }
        return $alta;
    }

    public function alta_org_sucursal(PDO $link, int $id = 1): array|\stdClass
    {


        $alta = (new \gamboamartin\organigrama\tests\base_test())->alta_org_sucursal(link: $link, id: $id);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al insertar sucursal', data: $alta);

        }
        return $alta;
    }
    


    public function del(PDO $link, string $name_model): array
    {

        $model = (new modelo_base($link))->genera_modelo(modelo: $name_model);
        $del = $model->elimina_todo();
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al eliminar '.$name_model, data: $del);
        }
        return $del;
    }

    public function del_cat_sat_factor(PDO $link): array|\stdClass
    {

        $del = (new base_test())->del_fc_conf_traslado($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);

        }

        $del = (new base_test())->del_fc_conf_retenido($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);

        }

        $del = (new \gamboamartin\cat_sat\tests\base_test())->del_cat_sat_factor($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);

        }
        return $del;
    }

    public function del_cat_sat_forma_pago(PDO $link): array|\stdClass
    {


        $del = (new \gamboamartin\cat_sat\tests\base_test())->del_cat_sat_forma_pago($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);

        }
        return $del;
    }

    public function del_cat_sat_metodo_pago(PDO $link): array|\stdClass
    {

        $del = $this->del_com_cliente($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);

        }

        $del = (new \gamboamartin\cat_sat\tests\base_test())->del_cat_sat_metodo_pago($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);

        }
        return $del;
    }

    public function del_cat_sat_moneda(PDO $link): array|\stdClass
    {

        $del = (new base_test())->del_fc_factura($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);

        }

        $del = (new base_test())->del_com_cliente($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);

        }

        $del = (new base_test())->del_com_tipo_cambio($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);

        }

        $del = (new \gamboamartin\cat_sat\tests\base_test())->del_cat_sat_moneda($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);

        }
        return $del;
    }

    public function del_cat_sat_tipo_factor(PDO $link): array|\stdClass
    {

        $del = (new base_test())->del_fc_conf_traslado($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);

        }

        $del = (new \gamboamartin\cat_sat\tests\base_test())->del_cat_sat_tipo_factor($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);

        }
        return $del;
    }

    public function del_com_cliente(PDO $link): array|\stdClass
    {


        $del = (new base_test())->del_fc_factura($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);

        }

        $del = (new \gamboamartin\comercial\test\base_test())->del_com_cliente($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);

        }
        return $del;
    }

    public function del_com_tipo_cambio(PDO $link): array|\stdClass
    {


        $del = (new \gamboamartin\comercial\test\base_test())->del_com_tipo_cambio($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);

        }
        return $del;
    }

    public function del_fc_cer_csd(PDO $link): array
    {

        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_cer_csd');
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        return $del;
    }

    public function del_fc_cfdi_sellado(PDO $link): array
    {
        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_cfdi_sellado');
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        return $del;
    }

    public function del_fc_csd(PDO $link): array
    {


        $del = (new base_test())->del_fc_factura($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);

        }

        $del = (new base_test())->del_fc_cer_csd($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);

        }

        $del = (new base_test())->del_fc_key_csd($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);

        }

        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_csd');
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        return $del;
    }

    public function del_fc_factura(PDO $link): array
    {

        $del = $this->del_fc_factura_documento($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }

        $del = $this->del_fc_partida($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }

        $del = $this->del_fc_cfdi_sellado($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }

        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_factura');
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        return $del;
    }

    public function del_fc_factura_documento(PDO $link): array
    {
        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_factura_documento');
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        return $del;
    }

    public function del_fc_key_csd(PDO $link): array
    {

        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_key_csd');
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        return $del;
    }


    public function del_fc_partida(PDO $link): array
    {

        $del = $this->del_fc_traslado($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        $del = $this->del_fc_retenido($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }

        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_partida');
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        return $del;
    }

    public function del_fc_traslado(PDO $link): array
    {
        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_traslado');
        if(errores::$error){
            return (new errores())->error('Error al eliminar traslado', $del);
        }
        return $del;
    }

    public function del_fc_conf_traslado(PDO $link): array
    {
        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_conf_traslado');
        if(errores::$error){
            return (new errores())->error('Error al eliminar fc_conf_traslado', $del);
        }
        return $del;
    }

    public function del_fc_conf_retenido(PDO $link): array
    {
        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_conf_retenido');
        if(errores::$error){
            return (new errores())->error('Error al eliminar fc_conf_retenido', $del);
        }
        return $del;
    }

    public function del_fc_producto(PDO $link): array
    {
        $del = $this->del($link, 'gamboamartin\\comercial\\models\\com_producto');
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        return $del;
    }

    public function del_fc_tipo_producto(PDO $link): array
    {
        $del = $this->del($link, 'gamboamartin\\comercial\\models\\com_tipo_producto');
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        return $del;
    }

    public function del_fc_retenido(PDO $link): array
    {
        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_retenido');
        if(errores::$error){
            return (new errores())->error('Error al eliminar retenido', $del);
        }
        return $del;
    }

    public function del_org_empresa(PDO $link): array|\stdClass
    {
        $del = $this->del_fc_csd($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }

        $del = (new \gamboamartin\organigrama\tests\base_test())->del_org_empresa($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);

        }
        return $del;
    }


}
