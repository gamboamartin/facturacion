<?php
namespace gamboamartin\facturacion\models;
use gamboamartin\cat_sat\models\cat_sat_factor;
use gamboamartin\cat_sat\models\cat_sat_tipo_factor;
use gamboamartin\cat_sat\models\cat_sat_tipo_impuesto;
use gamboamartin\errores\errores;
use gamboamartin\organigrama\models\org_sucursal;
use PDO;
use stdClass;

class fc_traslado extends _data_impuestos {
    public function __construct(PDO $link){
        $tabla = 'fc_traslado';

        $columnas = array($tabla=>false,'fc_partida'=>$tabla,'cat_sat_tipo_factor'=>$tabla,'cat_sat_factor'=>$tabla,
            'cat_sat_tipo_impuesto'=>$tabla,'com_producto'=>'fc_partida','fc_factura'=>'fc_partida');
        $campos_obligatorios = array('codigo','fc_partida_id');

        $no_duplicados = array('codigo','descripcion_select','alias','codigo_bis');

        $campos_view['fc_partida_id'] = array('type' => 'selects', 'model' => new fc_partida(link: $link));
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



        $columnas_extra['fc_partida_importe'] = $sq_importes->fc_partida_entidad_importe;
        $columnas_extra['fc_partida_importe_con_descuento'] = $sq_importes->fc_partida_entidad_importe_con_descuento;
        $columnas_extra['fc_traslado_importe'] = "$tabla.total";

        $atributos_criticos = array('total');

        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas, campos_view: $campos_view, columnas_extra: $columnas_extra,
            no_duplicados: $no_duplicados,  atributos_criticos: $atributos_criticos);

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'Traslado';


    }

    public function alta_bd(array $keys_integra_ds = array('codigo', 'descripcion')): array|stdClass
    {
        $this->modelo_partida = new fc_partida(link: $this->link);
        $this->modelo_entidad = new fc_factura(link: $this->link);
        $this->modelo_etapa = new fc_factura_etapa(link: $this->link);

        $r_alta_bd = parent::alta_bd(keys_integra_ds: $keys_integra_ds); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar',data:  $r_alta_bd);
        }



        return $r_alta_bd;
    }

    public function elimina_bd(int $id): array|stdClass
    {
        $this->modelo_entidad = new fc_factura(link: $this->link);
        $this->modelo_etapa = new fc_factura_etapa(link: $this->link);
        $this->modelo_partida = new fc_partida(link: $this->link);

        $r_elimina_bd = parent::elimina_bd($id); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al eliminar',data:  $r_elimina_bd);
        }
        return $r_elimina_bd;
    }


}