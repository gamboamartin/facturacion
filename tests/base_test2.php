<?php

namespace gamboamartin\facturacion\tests;

use base\orm\modelo_base;

use gamboamartin\comercial\models\com_sucursal;
use gamboamartin\comercial\models\com_tipo_cambio;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\fc_csd;
use gamboamartin\facturacion\models\fc_factura;
use gamboamartin\facturacion\models\fc_partida;


use gamboamartin\organigrama\models\org_sucursal;
use PDO;
use stdClass;


class base_test2
{
    public function alta_com_producto(PDO $link, int $id): array|int
    {
        $del = $this->elimina_registro($link, 'gamboamartin\\comercial\\models\\com_producto', id: $id);
        if (errores::$error) {
            return (new errores())->error('Error al eliminar producto', $del);
        }

        $alta = (new \gamboamartin\comercial\test\base_test())->alta_com_producto($link, id: $id);
        if (errores::$error) {
            return (new errores())->error('Error al insertar producto', $alta);
        }

        return $alta->registro_id;
    }

    public function alta_com_sucursal(PDO $link, int $id): array|int
    {
        $existe = (new com_sucursal($link))->existe_by_id(registro_id: 1);
        if (errores::$error) {
            return (new errores())->error(mensaje: 'Error al verificar si existe sucursal', data: $existe);
        }

        if ($existe) {
            return 1;
        }

        $alta = (new \gamboamartin\comercial\test\base_test())->alta_com_sucursal($link, id: $id);
        if (errores::$error) {
            return (new errores())->error('Error al insertar sucursal', $alta);
        }

        return $alta->registro_id;
    }

    public function alta_com_tipo_cambio(PDO $link, int $id): array|int
    {
        $existe = (new com_tipo_cambio($link))->existe_by_id(registro_id: $id);
        if (errores::$error) {
            return (new errores())->error(mensaje: 'Error al verificar si existe tipo cambio', data: $existe);
        }

        if ($existe) {
            return $id;
        }

        $alta = (new \gamboamartin\comercial\test\base_test())->alta_com_tipo_cambio(link: $link, id: $id);
        if (errores::$error) {
            return (new errores())->error('Error al insertar tipo cambio', $alta);
        }

        return $alta->registro_id;
    }


    public function alta_org_sucursal(PDO $link, int $id): array|int
    {
        $existe = (new org_sucursal($link))->existe_by_id(registro_id: $id);
        if (errores::$error) {
            return (new errores())->error(mensaje: 'Error al verificar si existe sucursal', data: $existe);
        }

        if ($existe) {
            return $id;
        }

        $alta = (new \gamboamartin\organigrama\tests\base_test())->alta_org_sucursal(link: $link, id: $id);
        if (errores::$error) {
            return (new errores())->error(mensaje: 'Error al insertar', data: $alta);
        }

        return $alta->registro_id;
    }

    public function alta_fc_csd(PDO $link, int $id = 1, string $codigo = '1', string $descripcion = '1'): array|int
    {
        $del = $this->elimina_fc_csd(link: $link, id: $id, factura_id: 999);
        if (errores::$error) {
            return (new errores())->error('Error al eliminar csd', $del);
        }

        $sucursal = $this->alta_org_sucursal(link: $link, id: 999);
        if (errores::$error) {
            return (new errores())->error('Error al insertar csd', $sucursal);
        }

        $registro = array();
        $registro['id'] = $id;
        $registro['codigo'] = $codigo;
        $registro['descripcion'] = $descripcion;
        $registro['serie'] = $id;
        $registro['org_sucursal_id'] = $sucursal;
        $registro['descripcion_select'] = $descripcion;
        $registro['alias'] = $codigo;
        $registro['codigo_bis'] = $codigo;

        $alta = (new fc_csd($link))->alta_registro($registro);
        if (errores::$error) {
            return (new errores())->error(mensaje: 'Error al insertar csd', data: $alta);
        }

        return $alta->registro_id;
    }

    public function alta_fc_factura(PDO $link, int $id = 1): array|int
    {
        $csd = $this->alta_fc_csd(link: $link, id: 999);
        if (errores::$error) {
            return (new errores())->error('Error al insertar csd', $csd);
        }

        $sucursal = $this->alta_com_sucursal(link: $link, id: 999);
        if (errores::$error) {
            return (new errores())->error('Error al insertar sucursal', $sucursal);
        }

        $tipo_cambio = $this->alta_com_tipo_cambio(link: $link, id: 1);
        if (errores::$error) {
            return (new errores())->error('Error al insertar tipo cambio', $tipo_cambio);
        }

        $registro = array();
        $registro['id'] = $id;
        $registro['codigo'] = $id;
        $registro['descripcion'] = $id;
        $registro['fc_csd_id'] = $csd;
        $registro['com_sucursal_id'] = $sucursal;
        $registro['serie'] = $id;
        $registro['folio'] = $id;
        $registro['exportacion'] = $id;
        $registro['cat_sat_forma_pago_id'] = 1;
        $registro['cat_sat_metodo_pago_id'] = 1;
        $registro['cat_sat_moneda_id'] = 1;
        $registro['com_tipo_cambio_id'] = $tipo_cambio;
        $registro['cat_sat_uso_cfdi_id'] = 1;
        $registro['cat_sat_tipo_de_comprobante_id'] = 1;
        $alta = (new fc_factura($link))->alta_registro($registro);
        if (errores::$error) {
            return (new errores())->error(mensaje: 'Error al insertar factura', data: $alta);
        }

        return $alta->registro_id;
    }

    public function alta_fc_partida(PDO   $link, int $id = 1, int $cantidad = 1, float $valor_unitario = 1,
                                    float $descuento = 0): array|int
    {
        $producto = $this->alta_com_producto(link: $link, id: 999);
        if (errores::$error) {
            return (new errores())->error('Error al insertar producto', $producto);
        }

        $factura = $this->alta_fc_factura(link: $link, id: 999);
        if (errores::$error) {
            return (new errores())->error('Error al insertar factura', $factura);
        }

        $registro = array();
        $registro['id'] = $id;
        $registro['codigo'] = $id;
        $registro['descripcion'] = $id;
        $registro['codigo_bis'] = $id;
        $registro['cantidad'] = $cantidad;
        $registro['valor_unitario'] = $valor_unitario;
        $registro['fc_factura_id'] = $factura;
        $registro['com_producto_id'] = $producto;
        $registro['descuento'] = $descuento;
        $alta = (new fc_partida($link))->alta_registro($registro);
        if (errores::$error) {
            return (new errores())->error(mensaje: 'Error al insertar partida', data: $alta);
        }

        return $alta->registro_id;
    }

    public function elimina_fc_csd(PDO $link, int $id, int $factura_id): array
    {
        $del = $this->elimina_registro($link, 'gamboamartin\facturacion\models\fc_factura', id: $factura_id);
        if (errores::$error) {
            return (new errores())->error('Error al eliminar factura', $del);
        }

        $del = $this->elimina_registro($link, 'gamboamartin\\facturacion\\models\\fc_csd', id: $id);
        if (errores::$error) {
            return (new errores())->error('Error al eliminar csd', $del);
        }

        return $del;
    }

    public function elimina_registro(PDO $link, string $name_model, int $id): array
    {
        $entidad = explode("\\", $name_model)[3];

        $model = (new modelo_base($link))->genera_modelo(modelo: $name_model);
        $del = $model->elimina_con_filtro_and(filtro: array($entidad . ".id" => $id));
        if (errores::$error) {
            return (new errores())->error(mensaje: 'Error al eliminar ' . $name_model, data: $del);
        }

        return $del;
    }
}
