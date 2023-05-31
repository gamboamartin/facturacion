<?php
namespace gamboamartin\facturacion\tests;
use base\orm\modelo_base;

use gamboamartin\cat_sat\models\cat_sat_factor;
use gamboamartin\cat_sat\models\cat_sat_forma_pago;
use gamboamartin\cat_sat\models\cat_sat_metodo_pago;
use gamboamartin\cat_sat\models\cat_sat_moneda;
use gamboamartin\cat_sat\models\cat_sat_tipo_factor;
use gamboamartin\cat_sat\models\cat_sat_tipo_relacion;
use gamboamartin\comercial\models\com_producto;
use gamboamartin\comercial\models\com_sucursal;
use gamboamartin\comercial\models\com_tipo_cambio;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\fc_complemento_pago;
use gamboamartin\facturacion\models\fc_conf_retenido;
use gamboamartin\facturacion\models\fc_conf_traslado;
use gamboamartin\facturacion\models\fc_csd;
use gamboamartin\facturacion\models\fc_factura;
use gamboamartin\facturacion\models\fc_factura_relacionada;
use gamboamartin\facturacion\models\fc_pago;
use gamboamartin\facturacion\models\fc_pago_pago;
use gamboamartin\facturacion\models\fc_partida;


use gamboamartin\facturacion\models\fc_partida_cp;
use gamboamartin\facturacion\models\fc_relacion;
use gamboamartin\organigrama\models\org_sucursal;
use PDO;
use stdClass;


class base_test{

    public function alta_adm_seccion(PDO $link, string $descripcion = 'fc_factura', $id = 1): array|\stdClass
    {
        $alta = (new \gamboamartin\administrador\tests\base_test())->alta_adm_seccion(link: $link,
            descripcion: $descripcion, id: $id);
        if(errores::$error){
            return (new errores())->error('Error al insertar', $alta);

        }
        return $alta;
    }

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

    public function alta_cat_sat_tipo_relacion(PDO $link, int $id = 1): array|\stdClass
    {
        $alta = (new \gamboamartin\cat_sat\tests\base_test())->alta_cat_sat_tipo_relacion(link: $link, id: $id);
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

    public function alta_com_producto(PDO $link, int $codigo = 1, int $id = 1): array|\stdClass
    {
        $alta = (new \gamboamartin\comercial\test\base_test())->alta_com_producto(link: $link, codigo: $codigo, id: $id);
        if(errores::$error){
            return (new errores())->error('Error al insertar', $alta);

        }
        return $alta;
    }

    public function alta_com_sucursal(PDO $link, int $cat_sat_moneda_id = 161, int $id = 1): array|\stdClass
    {

        $alta = (new \gamboamartin\comercial\test\base_test())->alta_com_sucursal(link: $link, id: $id);
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
        $registro['no_certificado'] = 010101;


        $alta = (new fc_csd($link))->alta_registro($registro);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al insertar', data: $alta);

        }
        return $alta;
    }

    public function alta_fc_factura(PDO $link, int $cat_sat_forma_pago_id = 1, int $cat_sat_metodo_pago_id = 2,
                                    int $cat_sat_moneda_id = 999, string $codigo = '1', int $com_sucursal_id = 1,
                                    int $com_tipo_cambio_id = 1, int $fc_csd_id = 1, string $folio = 'A-000001',
                                    int $id = 1): array|\stdClass
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
        $registro['folio'] = $folio;
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


    public function alta_fc_complemento_pago(PDO $link, int $cat_sat_forma_pago_id = 1, int $cat_sat_metodo_pago_id = 2,
                                    int $cat_sat_moneda_id = 999, string $codigo = '1', int $com_sucursal_id = 1,
                                    int $com_tipo_cambio_id = 1, int $fc_csd_id = 1, string $folio = 'A-000001',
                                    int $id = 1): array|\stdClass
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

        $existe = (new com_producto($link))->existe_by_id(registro_id: '84111506');
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al verificar si existe factura', data: $existe);
        }
        if(!$existe) {
            $alta = $this->alta_com_producto(link: $link, codigo: '84111506', id: '84111506');
            if (errores::$error) {
                return (new errores())->error(mensaje: 'Error al insertar sucursal', data: $alta);
            }
        }

        $registro = array();
        $registro['id'] = $id;
        $registro['codigo'] = $codigo;
        $registro['descripcion'] = 1;
        $registro['fc_csd_id'] = $fc_csd_id;
        $registro['com_sucursal_id'] = $com_sucursal_id;
        $registro['serie'] = 1;
        $registro['folio'] = $folio;
        $registro['exportacion'] = 1;
        $registro['cat_sat_forma_pago_id'] = $cat_sat_forma_pago_id;
        $registro['cat_sat_metodo_pago_id'] = $cat_sat_metodo_pago_id;
        $registro['cat_sat_moneda_id'] = $cat_sat_moneda_id;
        $registro['com_tipo_cambio_id'] = $com_tipo_cambio_id;
        $registro['cat_sat_uso_cfdi_id'] = 1;
        $registro['cat_sat_tipo_de_comprobante_id'] = 1;

        $alta = (new fc_complemento_pago($link))->alta_registro($registro);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al insertar', data: $alta);

        }
        return $alta;
    }

    public function alta_fc_factura_relacionada(PDO $link, int $fc_factura_id = 1,int $fc_relacion_id = 1, int $id = 1): array|\stdClass
    {

        $existe = (new fc_relacion($link))->existe_by_id(registro_id: $fc_relacion_id);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al verificar si existe ', data: $existe);
        }
        if(!$existe) {
            $alta = $this->alta_fc_relacion(link: $link, id: $fc_relacion_id);
            if (errores::$error) {
                return (new errores())->error(mensaje: 'Error al insertar ', data: $alta);
            }
        }

        $existe = (new fc_factura($link))->existe_by_id(registro_id: $fc_factura_id);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al verificar si existe ', data: $existe);
        }
        if(!$existe) {
            $alta = $this->alta_fc_factura(link: $link, id: $fc_factura_id);
            if (errores::$error) {
                return (new errores())->error(mensaje: 'Error al insertar ', data: $alta);
            }
        }

        $registro = array();
        $registro['id'] = $id;
        $registro['fc_relacion_id'] = $fc_relacion_id;
        $registro['fc_factura_id'] = $fc_factura_id;

        $alta = (new fc_factura_relacionada($link))->alta_registro($registro);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al insertar', data: $alta);

        }
        return $alta;
    }

    public function alta_fc_pago(PDO $link, int $fc_complemento_pago_id = 1, int $id = 1,
                                 string $version = '2.0'): array|\stdClass
    {


        $existe = (new fc_complemento_pago($link))->existe_by_id(registro_id: $fc_complemento_pago_id);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al verificar si existe ', data: $existe);
        }
        if(!$existe) {
            $alta = $this->alta_fc_complemento_pago(link: $link, id: $fc_complemento_pago_id);
            if (errores::$error) {
                return (new errores())->error(mensaje: 'Error al insertar ', data: $alta);
            }
            $filtro['fc_complemento_pago.id'] = $fc_complemento_pago_id;
            $del = (new fc_pago(link: $link))->elimina_con_filtro_and(filtro: $filtro);
            if (errores::$error) {
                return (new errores())->error(mensaje: 'Error al eliminar ', data: $del);
            }

        }


        $registro = array();
        $registro['id'] = $id;
        $registro['fc_complemento_pago_id'] = $fc_complemento_pago_id;
        $registro['version'] = $version;


        $alta = (new fc_pago($link))->alta_registro($registro);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al insertar', data: $alta);

        }
        return $alta;
    }

    public function alta_fc_pago_pago(PDO $link, int $cat_sat_forma_pago_id = 1, int $cat_sat_moneda_id = 1,
                                      int $com_tipo_cambio_id = 1, int $fc_pago_id = 1,
                                      string $fecha_pago = '2020-01-01', int $id = 1, float $monto = 10): array|\stdClass
    {


        $existe = (new fc_pago($link))->existe_by_id(registro_id: $fc_pago_id);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al verificar si existe ', data: $existe);
        }
        if(!$existe) {


            $alta = $this->alta_fc_pago(link: $link, id: $fc_pago_id);
            if (errores::$error) {
                return (new errores())->error(mensaje: 'Error al insertar ', data: $alta);
            }
        }


        $registro = array();
        $registro['id'] = $id;
        $registro['fc_pago_id'] = $fc_pago_id;
        $registro['fecha_pago'] = $fecha_pago;
        $registro['cat_sat_forma_pago_id'] = $cat_sat_forma_pago_id;
        $registro['cat_sat_moneda_id'] = $cat_sat_moneda_id;
        $registro['com_tipo_cambio_id'] = $com_tipo_cambio_id;
        $registro['monto'] = $monto;


        $alta = (new fc_pago_pago($link))->alta_registro($registro);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al insertar', data: $alta);

        }
        return $alta;
    }

    public function alta_fc_partida(PDO $link, string $codigo = '1', float $cantidad = 1, int $com_producto_id = 1,
                                    string $descripcion = '1', float $descuento = 0,
                                    string $fc_factura_folio = 'A-000001', int $fc_factura_id = 1,
                                    int $id = 1, float $valor_unitario = 1): array|\stdClass
    {

        $existe = (new fc_factura($link))->existe_by_id(registro_id: $fc_factura_id);
        if(errores::$error){
            return (new errores())->error('Error al validar si existe', $existe);
        }
        if(!$existe) {
            $alta = $this->alta_fc_factura(link: $link, folio: $fc_factura_folio);
            if (errores::$error) {
                return (new errores())->error('Error al insertar factura', $alta);
            }
        }

        $existe = (new com_producto($link))->existe_by_id(registro_id: $com_producto_id);
        if(errores::$error){
            return (new errores())->error('Error al validar si existe', $existe);
        }
        if(!$existe) {
            $alta = $this->alta_com_producto(link: $link,id: $com_producto_id);
            if (errores::$error) {
                return (new errores())->error('Error al insertar com_producto', $alta);
            }
        }

        $registro = array();
        $registro['id'] = $id;
        $registro['codigo'] = $codigo;
        $registro['descripcion'] = $descripcion;
        $registro['cantidad'] = $cantidad;
        $registro['valor_unitario'] = $valor_unitario;
        $registro['fc_factura_id'] = $fc_factura_id;
        $registro['com_producto_id'] = $com_producto_id;
        $registro['codigo_bis'] = $codigo;
        $registro['descuento'] = $descuento;


        $alta = (new fc_partida($link))->alta_registro($registro);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al insertar', data: $alta);

        }
        return $alta;
    }

    public function alta_fc_partida_cp(PDO $link, string $codigo = '1', float $cantidad = 1, int $com_producto_id = 1,
                                    string $descripcion = '1', float $descuento = 0,
                                    string $fc_complemento_pago_folio = 'A-000001', int $fc_complemento_pago_id = 1,
                                    int $id = 1, float $valor_unitario = 1): array|\stdClass
    {

        $existe = (new fc_complemento_pago($link))->existe_by_id(registro_id: $fc_complemento_pago_id);
        if(errores::$error){
            return (new errores())->error('Error al validar si existe', $existe);
        }
        if(!$existe) {
            $alta = $this->alta_fc_complemento_pago(link: $link, folio: $fc_complemento_pago_folio);
            if (errores::$error) {
                return (new errores())->error('Error al insertar factura', $alta);
            }
        }

        $existe = (new com_producto($link))->existe_by_id(registro_id: $com_producto_id);
        if(errores::$error){
            return (new errores())->error('Error al validar si existe', $existe);
        }
        if(!$existe) {
            $alta = $this->alta_com_producto(link: $link,id: $com_producto_id);
            if (errores::$error) {
                return (new errores())->error('Error al insertar com_producto', $alta);
            }
        }

        $registro = array();
        $registro['id'] = $id;
        $registro['codigo'] = $codigo;
        $registro['descripcion'] = $descripcion;
        $registro['cantidad'] = $cantidad;
        $registro['valor_unitario'] = $valor_unitario;
        $registro['fc_complemento_pago_id'] = $fc_complemento_pago_id;
        $registro['com_producto_id'] = $com_producto_id;
        $registro['codigo_bis'] = $codigo;
        $registro['descuento'] = $descuento;


        $alta = (new fc_partida_cp($link))->alta_registro($registro);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al insertar', data: $alta);

        }
        return $alta;
    }

    public function alta_fc_relacion(PDO $link, int $cat_sat_tipo_relacion_id = 1, int $fc_factura_id = 1,
                                    int $id = 1): array|\stdClass
    {

        $existe = (new cat_sat_tipo_relacion($link))->existe_by_id(registro_id: $cat_sat_tipo_relacion_id);
        if(errores::$error){
            return (new errores())->error('Error al validar si existe', $existe);
        }
        if(!$existe) {
            $alta = $this->alta_cat_sat_tipo_relacion(link: $link, id: $cat_sat_tipo_relacion_id);
            if (errores::$error) {
                return (new errores())->error('Error al insertar factura', $alta);
            }
        }

        $existe = (new fc_factura($link))->existe_by_id(registro_id: $fc_factura_id);
        if(errores::$error){
            return (new errores())->error('Error al validar si existe', $existe);
        }
        if(!$existe) {
            $alta = $this->alta_com_producto(link: $link,id: $fc_factura_id);
            if (errores::$error) {
                return (new errores())->error('Error al insertar com_producto', $alta);
            }
        }

        $registro = array();
        $registro['id'] = $id;
        $registro['cat_sat_tipo_relacion_id'] = $cat_sat_tipo_relacion_id;
        $registro['fc_factura_id'] = $fc_factura_id;


        $alta = (new fc_relacion($link))->alta_registro($registro);
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

    public function alta_pr_etapa_proceso(PDO $link, string $adm_accion_descripcion = 'alta_bd', int $adm_accion_id = 1,
                                          string $adm_seccion_descripcion = 'fc_factura', int $id = 1): array|stdClass
    {
        $alta = (new \gamboamartin\proceso\tests\base_test())->alta_pr_etapa_proceso(link: $link,
            adm_accion_id: $adm_accion_id, id: $id, adm_accion_descripcion: $adm_accion_descripcion,
            adm_seccion_descripcion: $adm_seccion_descripcion);
        if(errores::$error){
            return (new errores())->error('Error al insertar', $alta);

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

    public function del_adm_accion(PDO $link): array|\stdClass
    {


        $del = (new base_test())->del_pr_etapa_proceso($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }

        $del = (new \gamboamartin\administrador\tests\base_test())->del_adm_accion($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);

        }
        return $del;
    }

    public function del_adm_seccion(PDO $link): array|\stdClass
    {

        $del = (new base_test())->del_pr_etapa_proceso($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }

        $del = (new \gamboamartin\administrador\tests\base_test())->del_adm_seccion($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);

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
        $del = (new base_test())->del_fc_conf_retenido($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);

        }

        $del = (new base_test())->del_fc_retenido($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);

        }
        $del = (new base_test())->del_fc_traslado($link);
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

        $del = (new base_test())->del_fc_complemento_pago($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);

        }

        $del = (new base_test())->del_fc_nota_credito($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);

        }

        $del = (new base_test())->del_fc_uuid($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);

        }

        $del = (new base_test())->del_fc_receptor_email($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }

        $del = (new \gamboamartin\comercial\test\base_test())->del_com_cliente($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);

        }
        return $del;
    }





    public function del_com_producto(PDO $link): array|\stdClass
    {
        $del = (new base_test())->del_fc_conf_retenido($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);

        }
        $del = (new base_test())->del_fc_conf_traslado($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);

        }

        $del = (new base_test())->del_fc_partida_cp($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);

        }


        $del = (new \gamboamartin\comercial\test\base_test())->del_com_producto($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);

        }
        return $del;
    }

    public function del_pr_etapa_proceso(PDO $link): array|\stdClass
    {

        $del = (new base_test())->del_fc_factura_etapa($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        $del = (new base_test())->del_not_mensaje_etapa($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);

        }
        $del = (new base_test())->del_fc_conf_etapa_rel($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);

        }
        $del = (new base_test())->del_fc_complemento_pago_etapa($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);

        }
        $del = (new base_test())->del_fc_nota_credito_etapa($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);

        }
        $del = (new base_test())->del_fc_uuid_etapa($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);

        }


        $del = (new \gamboamartin\proceso\tests\base_test())->del_pr_etapa_proceso($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);

        }
        return $del;
    }

    public function del_not_mensaje_etapa(PDO $link): array|\stdClass
    {
        $del = (new \gamboamartin\notificaciones\tests\base_test())->del_not_mensaje_etapa($link);
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

    public function del_fc_cancelacion(PDO $link): array
    {
        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_cancelacion');
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        return $del;
    }

    public function del_fc_conf_etapa_rel(PDO $link): array
    {
        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_conf_etapa_rel');
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        return $del;
    }

    public function del_fc_cer_csd(PDO $link): array
    {
        $del = (new base_test())->del_fc_cer_pem($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);

        }

        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_cer_csd');
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        return $del;
    }

    public function del_fc_cer_pem(PDO $link): array
    {

        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_cer_pem');
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        return $del;
    }

    public function del_fc_cfdi_sellado_nc(PDO $link): array
    {

        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_cfdi_sellado_nc');
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

    public function del_fc_complemento_pago(PDO $link): array
    {

        $del = (new base_test())->del_fc_complemento_pago_etapa($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);

        }
        $del = (new base_test())->del_fc_email_cp($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);

        }

        $del = (new base_test())->del_fc_partida_cp($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);

        }

        $del = (new base_test())->del_fc_pago($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);

        }

        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_complemento_pago');
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        return $del;
    }

    public function del_fc_complemento_pago_etapa(PDO $link): array
    {


        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_complemento_pago_etapa');
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

        $del = (new base_test())->del_fc_complemento_pago($link);
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

        $del = (new base_test())->del_fc_nota_credito($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);

        }

        $del = (new base_test())->del_fc_uuid($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);

        }

        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_csd');
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        return $del;
    }

    public function del_fc_email(PDO $link): array
    {
        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_email');
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        return $del;
    }

    public function del_fc_email_cp(PDO $link): array
    {
        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_email_cp');
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        return $del;
    }

    public function del_fc_email_nc(PDO $link): array
    {
        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_email_nc');
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

        $del = $this->del_fc_factura_etapa($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }

        $del = $this->del_fc_cancelacion($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }


        $del = $this->del_fc_factura_relacionada($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }

        $del = $this->del_fc_relacion($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        $del = $this->del_fc_email($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        $del = $this->del_fc_notificacion($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        $del = $this->del_fc_nc_rel($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        $del = $this->del_fc_docto_relacionado($link);
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
        $del = $this->del_fc_key_pem($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }

        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_key_csd');
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        return $del;
    }

    public function del_fc_key_pem(PDO $link): array
    {

        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_key_pem');
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        return $del;
    }

    public function del_fc_pago_pago(PDO $link): array
    {
        $del = $this->del_fc_impuesto_p($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }

        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_pago_pago');
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        return $del;
    }

    public function del_fc_impuesto_p(PDO $link): array
    {

        $del = $this->del_fc_traslado_p($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }

        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_impuesto_p');
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        return $del;
    }
    public function del_fc_traslado_p(PDO $link): array
    {

        $del = $this->del_fc_traslado_p_part($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }

        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_traslado_p');
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        return $del;
    }

    public function del_fc_traslado_p_part(PDO $link): array
    {


        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_traslado_p_part');
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        return $del;
    }

    public function del_fc_uuid_nc(PDO $link): array
    {

        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_uuid_nc');
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        return $del;
    }

    public function del_fc_nota_credito_relacionada(PDO $link): array
    {

        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_nota_credito_relacionada');
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        return $del;
    }
    public function del_fc_relacion_nc(PDO $link): array
    {

        $del = $this->del_fc_uuid_nc($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }

        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_relacion_nc');
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        return $del;
    }

    public function del_fc_cancelacion_nc(PDO $link): array
    {

        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_cancelacion_nc');
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        return $del;
    }

    public function del_fc_notificacion_nc(PDO $link): array
    {

        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_notificacion_nc');
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        return $del;
    }

    public function del_fc_nota_credito(PDO $link): array
    {

        $del = $this->del_fc_email_nc($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }

        $del = $this->del_fc_nota_credito_etapa($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }

        $del = $this->del_fc_partida_nc($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }

        $del = $this->del_fc_nota_credito_documento($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }

        $del = $this->del_fc_cfdi_sellado_nc($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }

        $del = $this->del_fc_nota_credito_relacionada($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        $del = $this->del_fc_relacion_nc($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        $del = $this->del_fc_cancelacion_nc($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        $del = $this->del_fc_notificacion_nc($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }


        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_nota_credito');
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        return $del;
    }

    public function del_fc_nota_credito_etapa(PDO $link): array
    {
        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_nota_credito_etapa');
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        return $del;
    }

    public function del_fc_nota_credito_documento(PDO $link): array
    {
        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_nota_credito_documento');
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        return $del;
    }




    public function del_fc_factura_etapa(PDO $link): array
    {
        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_factura_etapa');
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        return $del;
    }

    public function del_fc_factura_relacionada(PDO $link): array
    {
        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_factura_relacionada');
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        return $del;
    }


    public function del_fc_notificacion(PDO $link): array
    {
        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_notificacion');
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        return $del;
    }

    public function del_fc_nc_rel(PDO $link): array
    {
        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_nc_rel');
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        return $del;
    }

    public function del_fc_docto_relacionado(PDO $link): array
    {

        $del = $this->del_fc_impuesto_dr($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }

        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_docto_relacionado');
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        return $del;
    }

    public function del_fc_traslado_dr(PDO $link): array
    {

        $del = $this->del_fc_traslado_dr_part($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }

        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_traslado_dr');
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        return $del;
    }

    public function del_fc_traslado_dr_part(PDO $link): array
    {


        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_traslado_dr_part');
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        return $del;
    }

    public function del_fc_impuesto_dr(PDO $link): array
    {
        $del = $this->del_fc_traslado_dr($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }


        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_impuesto_dr');
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
        $del = $this->del_fc_cuenta_predial($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }

        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_partida');
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        return $del;
    }

    public function del_fc_partida_cp(PDO $link): array
    {

        $del = $this->del_fc_traslado_cp($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }

        $del = $this->del_fc_retenido_cp($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }

        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_partida_cp');
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        return $del;
    }

    public function del_fc_pago(PDO $link): array
    {
        $del = $this->del_fc_pago_pago($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        $del = $this->del_fc_pago_total($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }

        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_pago');
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        return $del;
    }

    public function del_fc_pago_total(PDO $link): array
    {


        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_pago_total');
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        return $del;
    }

    public function del_fc_partida_nc(PDO $link): array
    {

        $del = $this->del_fc_traslado_nc($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_partida_nc');
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        return $del;
    }

    public function del_fc_traslado_nc(PDO $link): array
    {

        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_traslado_nc');
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        return $del;
    }

    public function del_fc_receptor_email(PDO $link): array
    {

        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_receptor_email');
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        return $del;
    }

    public function del_fc_uuid_fc(PDO $link): array
    {

        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_uuid_fc');
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        return $del;
    }

    public function del_fc_relacion(PDO $link): array
    {

        $del = $this->del_fc_uuid_fc($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }

        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_relacion');
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

    public function del_fc_traslado_cp(PDO $link): array
    {
        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_traslado_cp');
        if(errores::$error){
            return (new errores())->error('Error al eliminar traslado', $del);
        }
        return $del;
    }

    public function del_fc_retenido_cp(PDO $link): array
    {
        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_retenido_cp');
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

    public function del_fc_cuenta_predial(PDO $link): array
    {
        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_cuenta_predial');
        if(errores::$error){
            return (new errores())->error('Error al eliminar retenido', $del);
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

    public function del_fc_uuid(PDO $link): array
    {

        $del = $this->del_fc_uuid_cancela($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        $del = $this->del_fc_uuid_etapa($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }

        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_uuid');
        if(errores::$error){
            return (new errores())->error('Error al eliminar retenido', $del);
        }
        return $del;
    }

    public function del_fc_uuid_cancela(PDO $link): array
    {


        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_uuid_cancela');
        if(errores::$error){
            return (new errores())->error('Error al eliminar retenido', $del);
        }
        return $del;
    }

    public function del_fc_uuid_etapa(PDO $link): array
    {


        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_uuid_etapa');
        if(errores::$error){
            return (new errores())->error('Error al eliminar retenido', $del);
        }
        return $del;
    }

    public function del_org_empresa(PDO $link): array|\stdClass
    {
        $del = $this->del_fc_csd($link);
        if (errores::$error) {
            return (new errores())->error('Error al eliminar', $del);
        }

        $del = $this->del_fc_uuid($link);
        if (errores::$error) {
            return (new errores())->error('Error al eliminar', $del);
        }



        $del = (new \gamboamartin\organigrama\tests\base_test())->del_org_empresa($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);

        }
        return $del;
    }

    public function del_org_sucursal(PDO $link): array|\stdClass
    {
        $del = $this->del_fc_csd($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }

        $del = (new \gamboamartin\organigrama\tests\base_test())->del_org_sucursal($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);

        }
        return $del;
    }


}
