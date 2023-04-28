<?php
namespace gamboamartin\facturacion\models;
use base\orm\_modelo_parent;
use base\orm\modelo;
use gamboamartin\cat_sat\models\cat_sat_factor;
use gamboamartin\cat_sat\models\cat_sat_tipo_factor;
use gamboamartin\cat_sat\models\cat_sat_tipo_impuesto;
use gamboamartin\errores\errores;
use gamboamartin\organigrama\models\org_sucursal;
use PDO;
use stdClass;

class fc_retenido extends _data_impuestos {
    public function __construct(PDO $link, _partida|stdClass $modelo_partida = new stdClass()){
        $tabla = 'fc_retenido';


        $columnas = array($tabla=>false,'fc_partida'=>$tabla,'cat_sat_tipo_factor'=>$tabla,'cat_sat_factor'=>$tabla,
            'cat_sat_tipo_impuesto'=>$tabla,'com_producto'=>'fc_partida','fc_factura'=>'fc_partida');
        $campos_obligatorios = array('codigo','fc_partida_id');

        $no_duplicados = array('codigo','descripcion_select','alias','codigo_bis');

        $campos_view['fc_partida_id'] = array('type' => 'selects', 'model' =>$modelo_partida);
        $campos_view['cat_sat_tipo_factor_id'] = array('type' => 'selects', 'model' => new cat_sat_tipo_factor($link));
        $campos_view['cat_sat_factor_id'] = array('type' => 'selects', 'model' => new cat_sat_factor($link));
        $campos_view['org_sucursal_id'] = array('type' => 'selects', 'model' => new org_sucursal($link));
        $campos_view['cat_sat_tipo_impuesto_id'] = array('type' => 'selects', 'model' => new cat_sat_tipo_impuesto($link));
        $campos_view['codigo'] = array('type' => 'inputs');
        $campos_view['descripcion'] = array('type' => 'inputs');

        $sq_importes = (new _facturacion())->importes_base(name_entidad_partida: 'fc_partida');
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al generar sq_importes',data:  $sq_importes);
            print_r($error);
            exit;
        }
        $fc_impuesto_importe = (new _facturacion())->fc_impuesto_importe(
            fc_partida_importe_con_descuento: $sq_importes->fc_partida_entidad_importe_con_descuento);
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al generar fc_impuesto_importe',data:  $fc_impuesto_importe);
            print_r($error);
            exit;
        }

        $columnas_extra['fc_partida_importe'] = $sq_importes->fc_partida_entidad_importe;
        $columnas_extra['fc_partida_importe_con_descuento'] = $sq_importes->fc_partida_entidad_importe_con_descuento;
        $columnas_extra['fc_retenido_importe'] = $fc_impuesto_importe;

        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas, campos_view: $campos_view, columnas_extra: $columnas_extra,
            no_duplicados: $no_duplicados, tipo_campos: array());

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'Retencion';

    }







}