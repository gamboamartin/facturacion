<?php

namespace gamboamartin\facturacion\models;


use gamboamartin\errores\errores;
use PDO;
use stdClass;

class fc_partida_cp extends _partida
{
    public function __construct(PDO $link, _transacciones_fc|stdClass $modelo_entidad = new stdClass(),
                                _cuenta_predial|stdClass $modelo_predial = new stdClass(),
                                _data_impuestos|stdClass $modelo_retencion = new stdClass(),
                                _data_impuestos|stdClass $modelo_traslado = new stdClass())
    {
        $tabla = 'fc_partida_cp';
        $columnas = array($tabla => false, 'fc_complemento_pago' => $tabla, 'com_producto' => $tabla,
            'cat_sat_producto' => 'com_producto', 'cat_sat_unidad' => 'com_producto', 'cat_sat_obj_imp' => 'com_producto');
        $campos_obligatorios = array('codigo', 'com_producto_id');

        $columnas_extra = array();

        $sq_importes = (new _facturacion())->importes_base(name_entidad_partida: 'fc_partida_cp');
        if (errores::$error) {
            $error = (new errores())->error(mensaje: 'Error al generar sq_importes', data: $sq_importes);
            print_r($error);
            exit;
        }

        $sq_importe_total_traslado = (new _facturacion())->impuesto_partida(
            name_entidad_partida: 'fc_partida_cp', tabla_impuesto: 'fc_traslado_cp');
        if (errores::$error) {
            $error = (new errores())->error(mensaje: 'Error al generar sq_importe_total_traslado', data: $sq_importe_total_traslado);
            print_r($error);
            exit;
        }
        $sq_importe_total_retenido = (new _facturacion())->impuesto_partida(
            name_entidad_partida: 'fc_partida_cp', tabla_impuesto: 'fc_retenido_cp');
        if (errores::$error) {
            $error = (new errores())->error(mensaje: 'Error al generar sq_importe_total_retenido', data: $sq_importe_total_retenido);
            print_r($error);
            exit;
        }

        // print_r($sq_importes);exit;
        $columnas_extra['fc_partida_cp_importe'] = $sq_importes->fc_partida_entidad_importe;
        $columnas_extra['fc_partida_cp_importe_con_descuento'] = $sq_importes->fc_partida_entidad_importe_con_descuento;
        $columnas_extra['fc_partida_cp_importe_total_traslado'] = $sq_importe_total_traslado;
        $columnas_extra['fc_partida_cp_importe_total_retenido'] = $sq_importe_total_retenido;
        $columnas_extra['fc_partida_cp_importe_total'] = "$sq_importes->fc_partida_entidad_importe_con_descuento 
        + $sq_importe_total_traslado - $sq_importe_total_retenido";

        $columnas_extra['fc_partida_cp_n_traslados'] = "(SELECT COUNT(*) FROM fc_traslado_cp 
        WHERE fc_traslado_cp.fc_partida_cp_id = fc_partida_cp.id)";
        $columnas_extra['fc_partida_cp_n_retenidos'] = "(SELECT COUNT(*) FROM fc_retenido_cp 
        WHERE fc_retenido_cp.fc_partida_cp_id = fc_partida_cp.id)";

        $no_duplicados = array('codigo', 'descripcion_select', 'alias', 'codigo_bis');


        parent::__construct(link: $link, tabla: $tabla, modelo_entidad: $modelo_entidad,
            modelo_predial: $modelo_predial, modelo_retencion: $modelo_retencion,
            modelo_traslado: $modelo_traslado, campos_obligatorios: $campos_obligatorios, columnas: $columnas,
            columnas_extra: $columnas_extra, no_duplicados: $no_duplicados, tipo_campos: array());

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'Partida';

    }


}