<?php
namespace gamboamartin\facturacion\models;
use base\orm\_modelo_parent;
use gamboamartin\cat_sat\models\cat_sat_factor;
use gamboamartin\cat_sat\models\cat_sat_tipo_factor;
use gamboamartin\cat_sat\models\cat_sat_tipo_impuesto;
use gamboamartin\errores\errores;
use gamboamartin\organigrama\models\org_sucursal;
use PDO;
use stdClass;

class fc_traslado extends _base {
    public function __construct(PDO $link){
        $tabla = 'fc_traslado';
        $columnas = array($tabla=>false,'fc_partida'=>$tabla,'cat_sat_tipo_factor'=>$tabla,'cat_sat_factor'=>$tabla,
            'cat_sat_tipo_impuesto'=>$tabla,'com_producto'=>'fc_partida','fc_factura'=>'fc_partida');
        $campos_obligatorios = array('codigo','fc_partida_id');

        $no_duplicados = array('codigo','descripcion_select','alias','codigo_bis');

        $campos_view['fc_partida_id'] = array('type' => 'selects', 'model' => new fc_partida($link));
        $campos_view['cat_sat_tipo_factor_id'] = array('type' => 'selects', 'model' => new cat_sat_tipo_factor($link));
        $campos_view['cat_sat_factor_id'] = array('type' => 'selects', 'model' => new cat_sat_factor($link));
        $campos_view['org_sucursal_id'] = array('type' => 'selects', 'model' => new org_sucursal($link));
        $campos_view['cat_sat_tipo_impuesto_id'] = array('type' => 'selects', 'model' => new cat_sat_tipo_impuesto($link));
        $campos_view['codigo'] = array('type' => 'inputs');
        $campos_view['descripcion'] = array('type' => 'inputs');


        $sq_importes = (new _facturacion())->importes_base();
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al generar sq_importes',data:  $sq_importes);
            print_r($error);
            exit;
        }

        $fc_impuesto_importe = (new _facturacion())->fc_impuesto_importe(fc_partida_importe_con_descuento: $sq_importes->fc_partida_importe_con_descuento);
        if(errores::$error){
            $error = (new errores())->error(mensaje: 'Error al generar fc_impuesto_importe',data:  $fc_impuesto_importe);
            print_r($error);
            exit;
        }


        $columnas_extra['fc_partida_importe'] = $sq_importes->fc_partida_importe;
        $columnas_extra['fc_partida_importe_con_descuento'] = $sq_importes->fc_partida_importe_con_descuento;
        $columnas_extra['fc_traslado_importe'] = $fc_impuesto_importe;

        parent::__construct(link: $link, tabla: $tabla, campos_obligatorios: $campos_obligatorios,
            columnas: $columnas, campos_view: $campos_view, columnas_extra: $columnas_extra,
            no_duplicados: $no_duplicados, tipo_campos: array());

        $this->NAMESPACE = __NAMESPACE__;

        $this->etiqueta = 'Traslado';
    }

    public function alta_bd(array $keys_integra_ds = array('codigo', 'descripcion')): array|stdClass
    {
        $registro = $this->init_alta_bd();
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al inicializar campos base', data: $registro);
        }

        $this->registro = $this->validaciones(data: $this->registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar foraneas',data: $this->registro);
        }

        $fc_partida =(new fc_partida(link: $this->link))->registro(registro_id: $this->registro['fc_partida_id'], retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener fc_partida', data: $fc_partida);
        }

        $permite_transaccion = (new fc_factura(link: $this->link))->verifica_permite_transaccion(fc_factura_id: $fc_partida->fc_factura_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error verificar transaccion', data: $permite_transaccion);
        }

        $r_alta_bd =  parent::alta_bd();
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error registrar traslados', data: $r_alta_bd);
        }

        return $r_alta_bd;
    }

    final public function elimina_bd(int $id): array|stdClass
    {
        $traslado = $this->get_traslado(fc_traslado_id: $id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener retenido',data: $traslado);
        }
        $permite_transaccion = (new fc_factura(link: $this->link))->verifica_permite_transaccion(fc_factura_id: $traslado['fc_factura_id']);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error verificar transaccion', data: $permite_transaccion);
        }
        $r_elimina_bd = parent::elimina_bd($id); // TODO: Change the autogenerated stub
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al eliminar', data: $r_elimina_bd);
        }
        return $r_elimina_bd;

    }

    public function get_traslado(int $fc_traslado_id): array|stdClass|int
    {
        $registro = $this->registro(registro_id: $fc_traslado_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener traslado',data:  $registro);
        }

        return $registro;
    }

    /**
     * Obtiene los impuestos trasladados de una partida
     * @param int $fc_partida_id Partida
     * @return array|stdClass|int
     * @version 6.18.0
     */
    final public function get_traslados(int $fc_partida_id): array|stdClass|int
    {
        $filtro['fc_partida.id']  = $fc_partida_id;
        $registro = $this->filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener traslados',data:  $registro);
        }

        return $registro;
    }

    public function modifica_bd(array $registro, int $id, bool $reactiva = false,
                                array $keys_integra_ds = array('codigo', 'descripcion')): array|stdClass
    {
        $traslado = $this->get_traslado(fc_traslado_id: $id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener traslado',data: $traslado);
        }
        $permite_transaccion = (new fc_factura(link: $this->link))->verifica_permite_transaccion(fc_factura_id: $traslado->fc_factura_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error verificar transaccion', data: $permite_transaccion);
        }

        if(!isset($registro['codigo'])){
            $registro['codigo'] =  $traslado["fc_traslado_codigo"];
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al obtener el codigo del registro',data: $registro);
            }
        }

        $registro = $this->campos_base(data: $registro,modelo: $this,id: $id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al inicializar campos base',data: $registro);
        }

        $registro = $this->validaciones(data: $registro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar foraneas',data: $registro);
        }

        $r_modifica_bd = parent::modifica_bd($registro, $id, $reactiva);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al modificar traslados',data:  $r_modifica_bd);
        }

        return $r_modifica_bd;
    }

    private function validaciones(array $data): array
    {
        $keys = array('descripcion','codigo');
        $valida = $this->validacion->valida_existencia_keys(keys:$keys,registro:  $data);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar campos', data: $valida);
        }

        $keys = array('fc_partida_id', 'cat_sat_tipo_factor_id', 'cat_sat_factor_id', 'cat_sat_tipo_impuesto_id');
        $valida = $this->validacion->valida_ids(keys: $keys, registro: $data);
        if(errores::$error){
            return $this->error->error(mensaje: "Error al validar foraneas",data:  $valida);
        }

        return $data;
    }
}