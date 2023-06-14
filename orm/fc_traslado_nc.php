<?php
namespace gamboamartin\facturacion\models;
use gamboamartin\cat_sat\models\cat_sat_factor;
use gamboamartin\cat_sat\models\cat_sat_tipo_factor;
use gamboamartin\cat_sat\models\cat_sat_tipo_impuesto;
use gamboamartin\errores\errores;
use gamboamartin\organigrama\models\org_sucursal;
use PDO;
use stdClass;

class fc_traslado_nc extends _data_impuestos {
    public function __construct(PDO $link){
        $tabla = 'fc_traslado_nc';

        $columnas = array($tabla=>false,'fc_partida_nc'=>$tabla,'cat_sat_tipo_factor'=>$tabla,'cat_sat_factor'=>$tabla,
            'cat_sat_tipo_impuesto'=>$tabla,'com_producto'=>'fc_partida_nc','fc_nota_credito'=>'fc_partida_nc');
        $campos_obligatorios = array('codigo','fc_partida_nc_id');

        $no_duplicados = array('codigo','descripcion_select','alias','codigo_bis');

        $campos_view['fc_partida_nc_id'] = array('type' => 'selects', 'model' => new fc_partida_nc(link: $link));
        $campos_view['cat_sat_tipo_factor_id'] = array('type' => 'selects', 'model' => new cat_sat_tipo_factor($link));
        $campos_view['cat_sat_factor_id'] = array('type' => 'selects', 'model' => new cat_sat_factor($link));
        $campos_view['org_sucursal_id'] = array('type' => 'selects', 'model' => new org_sucursal($link));
        $campos_view['cat_sat_tipo_impuesto_id'] = array('type' => 'selects', 'model' => new cat_sat_tipo_impuesto($link));
        $campos_view['codigo'] = array('type' => 'inputs');
        $campos_view['descripcion'] = array('type' => 'inputs');


        $sq_importes = (new _facturacion())->importes_base(name_entidad_partida: 'fc_partida_nc');
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al generar sq_importes',data:  $sq_importes);
            print_r($error);
            exit;
        }


        $columnas_extra['fc_partida_nc_importe'] = $sq_importes->fc_partida_entidad_importe;
        $columnas_extra['fc_partida_nc_importe_con_descuento'] = $sq_importes->fc_partida_entidad_importe_con_descuento;
        $columnas_extra['fc_traslado_nc_importe'] = "$tabla.total";

        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas, campos_view: $campos_view, columnas_extra: $columnas_extra,
            no_duplicados: $no_duplicados, tipo_campos: array());

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'Traslado';


    }

    public function alta_bd(array $keys_integra_ds = array('codigo', 'descripcion')): array|stdClass
    {
        $this->modelo_partida = new fc_partida_nc(link: $this->link);
        $this->modelo_entidad = new fc_nota_credito(link: $this->link);
        $this->modelo_etapa = new fc_nota_credito_etapa(link: $this->link);

        $r_alta_bd = parent::alta_bd($keys_integra_ds); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar',data:  $r_alta_bd);
        }
        return $r_alta_bd;
    }

    public function elimina_bd(int $id): array|stdClass
    {
        $this->modelo_entidad = new fc_nota_credito(link: $this->link);
        $this->modelo_etapa = new fc_nota_credito_etapa(link: $this->link);
        $this->modelo_partida = new fc_partida_nc(link: $this->link);

        $r_elimina_bd = parent::elimina_bd($id); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al eliminar',data:  $r_elimina_bd);
        }
        return $r_elimina_bd;
    }



}