<?php
namespace gamboamartin\facturacion\tests;
use base\orm\modelo_base;

use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\fc_csd;
use gamboamartin\facturacion\models\fc_factura;
use gamboamartin\facturacion\models\fc_partida;


use PDO;


class base_test{





    public function alta_fc_csd(PDO $link): array|\stdClass
    {

        $alta = $this->alta_org_sucursal($link);
        if(errores::$error){
            return (new errores())->error('Error al insertar', $alta);

        }

        $registro = array();
        $registro['id'] = 1;
        $registro['codigo'] = 1;
        $registro['descripcion'] = 1;
        $registro['serie'] = 1;
        $registro['org_sucursal_id'] = 1;
        $registro['descripcion_select'] = 1;
        $registro['alias'] = 1;
        $registro['codigo_bis'] = 1;


        $alta = (new fc_csd($link))->alta_registro($registro);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al insertar', data: $alta);

        }
        return $alta;
    }

    public function alta_fc_factura(PDO $link): array|\stdClass
    {

        $registro = array();
        $registro['id'] = 1;
        $registro['codigo'] = 1;
        $registro['descripcion'] = 1;
        $registro['fc_csd_id'] = 1;
        $registro['com_sucursal_id'] = 1;
        $registro['serie'] = 1;
        $registro['folio'] = 1;
        $registro['cat_sat_forma_pago_id'] = 1;
        $registro['cat_sat_metodo_pago_id'] = 1;
        $registro['cat_sat_moneda_id'] = 1;
        $registro['com_tipo_cambio_id'] = 1;
        $registro['cat_sat_uso_cfdi_id'] = 1;
        $registro['cat_sat_tipo_de_comprobante_id'] = 1;



        $alta = (new fc_factura($link))->alta_registro($registro);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al insertar', data: $alta);

        }
        return $alta;
    }

    public function alta_fc_partida(PDO $link, string $codigo = '1', string $descripcion = '1',
                                    float $descuento = 0, int $id = 1): array|\stdClass
    {
        $alta = $this->alta_fc_factura($link);
        if(errores::$error){
            return (new errores())->error('Error al insertar', $alta);

        }

        $registro = array();
        $registro['id'] = $id;
        $registro['codigo'] = $codigo;
        $registro['descripcion'] = $descripcion;
        $registro['cantidad'] = 1;
        $registro['valor_unitario'] = 1;
        $registro['fc_factura_id'] = 1;
        $registro['com_producto_id'] = 1;
        $registro['codigo_bis'] = 1;
        $registro['descuento'] = $descuento;


        $alta = (new fc_partida($link))->alta_registro($registro);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al insertar', data: $alta);

        }
        return $alta;
    }

    public function alta_org_sucursal(PDO $link): array|\stdClass
    {


        $alta = (new \gamboamartin\organigrama\tests\base_test())->alta_org_sucursal($link);
        if(errores::$error){
            return (new errores())->error(mensaje: 'Error al insertar', data: $alta);

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



    public function del_fc_csd(PDO $link): array
    {


        $del = (new base_test())->del_fc_factura($link);
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
        $del = $this->del_fc_partida($link);
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_factura');
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        return $del;
    }

    public function del_fc_partida(PDO $link): array
    {
        $del = $this->del($link, 'gamboamartin\\facturacion\\models\\fc_partida');
        if(errores::$error){
            return (new errores())->error('Error al eliminar', $del);
        }
        return $del;
    }


}
