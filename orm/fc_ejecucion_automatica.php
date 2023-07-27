<?php

namespace gamboamartin\facturacion\models;

use base\orm\_modelo_parent_sin_codigo;
use gamboamartin\cat_sat\models\cat_sat_moneda;
use gamboamartin\comercial\models\com_cliente;
use gamboamartin\comercial\models\com_sucursal;
use gamboamartin\comercial\models\com_tipo_cambio;
use gamboamartin\errores\errores;
use PDO;
use stdClass;


class fc_ejecucion_automatica extends _modelo_parent_sin_codigo
{
    public function __construct(PDO $link)
    {
        $tabla = 'fc_ejecucion_automatica';
        $columnas = array($tabla => false, 'fc_conf_automatico' => $tabla,'com_tipo_cliente'=>'fc_conf_automatico',
            'fc_csd'=>'fc_conf_automatico','org_sucursal'=>'fc_csd','org_empresa'=>'org_sucursal');

        $campos_obligatorios = array('fc_conf_automatico_id');

        $columnas_extra = array();

        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas,  columnas_extra: $columnas_extra);

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'Ejecuciones de facturacion automatica';
    }

    public function alta_bd(array $keys_integra_ds = array('descripcion')): array|stdClass
    {

        $data = $this->automatiza_facturas(fc_conf_automatico_id: $this->registro['fc_conf_automatico_id']);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar facturas',data:  $data);
        }
        $r_alta = parent::alta_bd($keys_integra_ds); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar registro',data:  $r_alta);
        }

        return $r_alta;

    }

    private function automatiza_facturas(int $fc_conf_automatico_id){
        $data = $this->data_auto(fc_conf_automatico_id: $fc_conf_automatico_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener datos',data:  $data);
        }

        $facturas = $this->inserta_facturas(data: $data);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar facturas',data:  $facturas);
        }

        $partidas = $this->inserta_partidas_factura(data: $data,facturas:  $facturas);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar partidas',data:  $partidas);
        }

        $data->facturas = $facturas;
        $data->partidas = $partidas;
        return $data;
    }

    private function cat_sat_conf_imps_id(stdClass $fc_factura): int
    {
        $cat_sat_conf_imps_id = 1;

        if((int)$fc_factura->org_empresa_cat_sat_tipo_persona_id === 5){
            if((int)$fc_factura->cat_sat_regimen_fiscal_id === 626){
                $cat_sat_conf_imps_id = 4;
            }
        }
        return $cat_sat_conf_imps_id;
    }

    private function data_auto(int $fc_conf_automatico_id){

        $fc_conf_automatico = (new fc_conf_automatico(link: $this->link))->registro(
            registro_id: $fc_conf_automatico_id,retorno_obj: true);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener configuracion',data:  $fc_conf_automatico);
        }

        $com_sucursales = (new com_sucursal(link: $this->link))->sucursales_by_tipo_cliente(
            com_tipo_cliente_id: $fc_conf_automatico->com_tipo_cliente_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener sucursales',data:  $com_sucursales);
        }

        $productos = (new fc_conf_aut_producto(link: $this->link))->productos_by_conf(
            fc_conf_automatico_id: $fc_conf_automatico->fc_conf_automatico_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener productos',data:  $productos);
        }

        $data = new stdClass();
        $data->fc_conf_automatico = $fc_conf_automatico;
        $data->com_sucursales = $com_sucursales;
        $data->productos = $productos;

        return $data;
    }

    private function fc_factura_insert(array $com_sucursal, stdClass $data, string $hoy){
        $fc_factura_insert = array();

        $fc_factura_insert['fc_csd_id'] = $data->fc_conf_automatico->fc_csd_id;
        $fc_factura_insert['com_sucursal_id'] = $com_sucursal['com_sucursal_id'];
        $fc_factura_insert['cat_sat_metodo_pago_id'] = $com_sucursal['com_cliente_cat_sat_metodo_pago_id'];
        $fc_factura_insert['cat_sat_forma_pago_id'] = $com_sucursal['com_cliente_cat_sat_forma_pago_id'];
        $fc_factura_insert['cat_sat_moneda_id'] = $com_sucursal['com_cliente_cat_sat_moneda_id'];
        $fc_factura_insert['cat_sat_uso_cfdi_id'] = $com_sucursal['com_cliente_cat_sat_uso_cfdi_id'];
        $fc_factura_insert['cat_sat_tipo_de_comprobante_id'] = $com_sucursal['com_cliente_cat_sat_tipo_de_comprobante_id'];
        $fc_factura_insert['exportacion'] = '01';
        $fc_factura_insert['fecha'] = $hoy;

        $fc_factura_insert = $this->integra_tipo_cambio(com_sucursal: $com_sucursal,fc_factura_insert:  $fc_factura_insert,hoy:  $hoy);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener tipo de cambio',data:  $fc_factura_insert);
        }
        return $fc_factura_insert;
    }

    private function fc_partida_ins(stdClass $fc_factura, array $producto){
        $fc_partida_ins = array();
        $fc_partida_ins['fc_factura_id'] = $fc_factura->fc_factura_id;
        $fc_partida_ins['descripcion'] = $producto['com_producto_descripcion'];
        $fc_partida_ins['com_producto_id'] = $producto['com_producto_id'];
        $fc_partida_ins['cantidad'] = $producto['fc_conf_aut_producto_cantidad'];


        $fc_partida_ins = $this->integra_conf_imp(fc_factura: $fc_factura,fc_partida_ins:  $fc_partida_ins);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener cat_sat_conf_imps_id',data:  $fc_partida_ins);
        }

        $fc_partida_ins = $this->integra_precio(fc_factura: $fc_factura,fc_partida_ins:  $fc_partida_ins,producto:  $producto);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener precio',data:  $fc_partida_ins);
        }
        return $fc_partida_ins;
    }

    private function inserta_factura(array $com_sucursal, stdClass $data, string $hoy){
        $fc_factura_insert = $this->fc_factura_insert(com_sucursal: $com_sucursal,data:  $data,hoy:  $hoy);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener tipo de cambio',data:  $fc_factura_insert);
        }
        $r_alta_fc_factura = (new fc_factura(link: $this->link))->alta_registro(registro: $fc_factura_insert);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar factura',data:  $r_alta_fc_factura);
        }
        return $r_alta_fc_factura;

    }

    private function inserta_facturas(stdClass $data){
        $hoy = date('Y-m-d');
        $facturas = array();
        foreach ($data->com_sucursales as $com_sucursal){
            $r_alta_fc_factura = $this->inserta_factura(com_sucursal: $com_sucursal,data:  $data,hoy:  $hoy);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al insertar factura',data:  $r_alta_fc_factura);
            }
            $facturas[] = $r_alta_fc_factura->registro_obj;
        }
        return $facturas;
    }

    private function inserta_partida(stdClass $fc_factura, array $producto){
        $fc_partida_ins = $this->fc_partida_ins(fc_factura: $fc_factura,producto:  $producto);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener cat_sat_conf_imps_id',data:  $fc_partida_ins);
        }

        $r_alta_fc_partida = (new fc_partida(link: $this->link))->alta_registro(registro: $fc_partida_ins);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar r_alta_fc_partida',data:  $r_alta_fc_partida);
        }
        return $r_alta_fc_partida;
    }

    private function inserta_partidas(stdClass $data, stdClass $fc_factura){
        $altas = array();
        foreach ($data->productos as $producto){
            $r_alta_fc_partida = $this->inserta_partida(fc_factura: $fc_factura,producto:  $producto);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al insertar r_alta_fc_partida',data:  $r_alta_fc_partida);
            }
            $altas[] = $r_alta_fc_partida->registro_obj;
        }
        return $altas;
    }

    private function inserta_partidas_factura(stdClass $data, array $facturas){
        $partidas = array();
        foreach ($facturas as $fc_factura){
            $partidas = $this->inserta_partidas(data: $data, fc_factura: $fc_factura);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al insertar r_alta_fc_partida',data:  $partidas);
            }
        }
        return $partidas;
    }

    private function integra_conf_imp(stdClass $fc_factura, array $fc_partida_ins){
        $cat_sat_conf_imps_id = $this->cat_sat_conf_imps_id(fc_factura: $fc_factura);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener cat_sat_conf_imps_id',data:  $cat_sat_conf_imps_id);
        }

        $fc_partida_ins['cat_sat_conf_imps_id'] = $cat_sat_conf_imps_id;
        return $fc_partida_ins;
    }

    private function integra_precio(stdClass $fc_factura, array $fc_partida_ins, array $producto){
        $precio = $this->valor_unitario(fc_factura: $fc_factura,producto:  $producto);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener precio',data:  $precio);
        }

        $fc_partida_ins['valor_unitario'] = $precio;
        return $fc_partida_ins;
    }

    private function integra_tipo_cambio(array $com_sucursal, array $fc_factura_insert, string $hoy){
        $com_tipo_cambio = (new com_tipo_cambio(link: $this->link))->tipo_cambio(
            cat_sat_moneda_id: $com_sucursal['com_cliente_cat_sat_moneda_id'],fecha:  $hoy);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener tipo de cambio',data:  $com_tipo_cambio);
        }

        $fc_factura_insert['com_tipo_cambio_id'] = $com_tipo_cambio['com_tipo_cambio_id'];
        return $fc_factura_insert;
    }

    private function valor_unitario(stdClass $fc_factura, array $producto){
        $precio = (new com_producto(link: $this->link))->precio(com_cliente_id: $fc_factura->com_cliente_id,
            com_producto_id:  $producto['com_producto_id']);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener precio',data:  $precio);
        }
        if($precio <=0.0){
            return $this->error->error(mensaje: 'Error precio no configurado',data:  $precio);
        }
        return $precio;
    }


}