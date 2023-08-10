<?php

namespace gamboamartin\facturacion\models;
use gamboamartin\comercial\models\com_tipo_cambio;
use gamboamartin\errores\errores;
use gamboamartin\validacion\validacion;
use PDO;
use stdClass;

class _plantilla{
    private _transacciones_fc $modelo_entidad;
    private _partida $modelo_partida;
    private _data_impuestos $modelo_traslado;
    private _data_impuestos $modelo_retenido;
    private errores $error;
    private int $row_entidad_id = -1;

    public function __construct(_transacciones_fc $modelo_entidad,_partida $modelo_partida,
                                _data_impuestos $modelo_retenido, _data_impuestos $modelo_traslado,
                                int $row_entidad_id){
        $this->error = new errores();
        $this->modelo_entidad = $modelo_entidad;
        $this->modelo_partida = $modelo_partida;
        $this->row_entidad_id = $row_entidad_id;
        $this->modelo_traslado = $modelo_traslado;
        $this->modelo_retenido = $modelo_retenido;

    }

    /**
     * Inserta una factura basada en una plantilla
     * @return array|stdClass
     */
    final public function aplica_plantilla(): array|stdClass
    {
        $row_entidad_new = $this->inserta_row_entidad();
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar factura',data:  $row_entidad_new);
        }

        $rows_imp_ins = $this->inserta_partidas_full(row_entidad_new_id: $row_entidad_new->registro_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integrar rows_imp_ins',data:  $rows_imp_ins);
        }
        return $row_entidad_new;
    }

    /**
     * Ejecuta la insercion de impuestos de una partida
     * @param _data_impuestos $modelo_imp
     * @param int $partida_id_new
     * @param int $registro_partida_id
     * @return array|stdClass
     */
    private function ejecuta_imp_ins(_data_impuestos $modelo_imp, int $partida_id_new, int $registro_partida_id): array|stdClass
    {
        $del = $this->limpia_impuestos(modelo_imp: $modelo_imp, partida_id_new: $partida_id_new);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al eliminar traslados',data:  $del);
        }

        $r_impuestos = $this->r_impuestos(modelo_imp: $modelo_imp,
            name_modelo_partida:  $this->modelo_partida->tabla,registro_partida_id:  $registro_partida_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener impuestos',data:  $r_impuestos);
        }

        $rows_imp_ins = $this->inserta_impuestos(impuestos: $r_impuestos->registros,
            modelo_imp:  $modelo_imp,row_partida_id:  $partida_id_new);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integrar rows_imp_ins',data:  $rows_imp_ins);
        }

        $data = new stdClass();
        $data->del = $del;
        $data->r_impuestos = $r_impuestos;
        $data->rows_imp_ins = $rows_imp_ins;
        return $data;
    }

    /**
     * Inserta el registro de partida basado en una plantilla
     * @param int $row_entidad_new_id
     * @param array $row_partida_origen
     * @return array|stdClass
     */
    private function ejecuta_partida(int $row_entidad_new_id, array $row_partida_origen): array|stdClass
    {
        $r_alta_row_partida = $this->inserta_row_partida(row_entidad_new_id: $row_entidad_new_id,
            row_partida_origen: $row_partida_origen);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar partida',data:  $r_alta_row_partida);
        }

        $rows_imp_ins = $this->inserta_impuestos_completos(partida_id_new: $r_alta_row_partida->registro_id,
            registro_partida_id:  $row_partida_origen['id']);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integrar rows_imp_ins',data:  $rows_imp_ins);
        }
        return $rows_imp_ins;
    }

    /**
     * Integra un registro listo para insertar como factura basado en una plantilla
     * @return array
     * @version 13.4.0
     */
    private function genera_row_entidad_ins(): array
    {

        $row_entidad = $this->row_entidad();
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener registro',data:  $row_entidad);
        }

        $com_tipo_cambio = (new com_tipo_cambio(link: $this->modelo_entidad->link))->tipo_cambio(
            cat_sat_moneda_id: $row_entidad->cat_sat_moneda_id,fecha:  date('Y-m-d'));

        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integrar com_tipo_cambio',data:  $com_tipo_cambio);
        }

        $row_entidad_ins = $this->row_entidad_ins(com_tipo_cambio: $com_tipo_cambio,row_entidad:  $row_entidad);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al maquetar factura',data:  $row_entidad_ins);
        }
        return $row_entidad_ins;
    }

    /**
     * Obtiene los impuestos de la plantilla de una partida
     * @param _data_impuestos $modelo_imp
     * @param string $name_modelo_partida
     * @param int $registro_partida_id
     * @return array|int|stdClass
     */
    private function r_impuestos(_data_impuestos $modelo_imp, string $name_modelo_partida,
                                 int $registro_partida_id): int|array|stdClass
    {
        $r_impuestos = $modelo_imp->get_data_rows(name_modelo_partida: $name_modelo_partida,
            registro_partida_id:  $registro_partida_id, columnas_en_bruto: true);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener impuestos',data:  $r_impuestos);
        }
        return $r_impuestos;
    }

    /**
     * Inserta un registro de impuestos basado en una plantilla
     * @param _data_impuestos $modelo_imp
     * @param array $row_imp
     * @param int $row_partida_id
     * @return array|stdClass
     */
    private function inserta_impuesto(_data_impuestos $modelo_imp, array $row_imp, int $row_partida_id): array|stdClass
    {
        $row_imp_ins = $this->row_imp_ins(name_modelo_imp: $modelo_imp->tabla,
            row_imp: $row_imp, row_partida_id: $row_partida_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integrar row_imp_ins',data:  $row_imp_ins);
        }

        $r_alta_imp = $modelo_imp->alta_registro(registro: $row_imp_ins);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar r_alta_fc_traslado',data:  $r_alta_imp);
        }
        return $r_alta_imp;
    }

    /**
     * Inserta todos los impuestos basados en una plantilla
     * @param array $impuestos
     * @param _data_impuestos $modelo_imp
     * @param int $row_partida_id
     * @return array
     */
    private function inserta_impuestos(array $impuestos, _data_impuestos $modelo_imp, int $row_partida_id): array
    {
        $data_imp = array();
        foreach ($impuestos as $row_imp){
            $row_imp_ins = $this->inserta_impuesto(modelo_imp: $modelo_imp,row_imp:  $row_imp,
                row_partida_id: $row_partida_id);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al integrar row_imp_ins',data:  $row_imp_ins);
            }
            $data_imp[] = $row_imp_ins;
        }
        return $data_imp;
    }

    /**
     * Inserta todos los impuestos de una partida basado en una plantilla
     * @param int $partida_id_new
     * @param int $registro_partida_id
     * @return array|stdClass
     */
    private function inserta_impuestos_completos(int $partida_id_new, int $registro_partida_id): array|stdClass
    {

        $datos = new stdClass();
        $rows_imp_ins = $this->ejecuta_imp_ins(modelo_imp: $this->modelo_traslado,
            partida_id_new: $partida_id_new, registro_partida_id: $registro_partida_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integrar rows_imp_ins',data:  $rows_imp_ins);
        }
        $datos->traslados = $rows_imp_ins;

        $rows_imp_ins = $this->ejecuta_imp_ins(modelo_imp: $this->modelo_retenido,
            partida_id_new: $partida_id_new, registro_partida_id: $registro_partida_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integrar rows_imp_ins',data:  $rows_imp_ins);
        }
        $datos->retenciones = $rows_imp_ins;

        return $datos;

    }

    /**
     * Inserta las partidas basados en una plantilla
     * @param int $row_entidad_new_id
     * @param array $rows_partidas
     * @return array
     */
    private function inserta_partidas(int $row_entidad_new_id, array $rows_partidas): array
    {
        $rows_imps_ins = array();
        foreach ($rows_partidas as $row_partida_origen){

            $rows_imp_ins = $this->ejecuta_partida(row_entidad_new_id: $row_entidad_new_id,
                row_partida_origen: $row_partida_origen);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al integrar rows_imp_ins',data:  $rows_imp_ins);
            }
            $rows_imps_ins[] = $rows_imp_ins;
        }
        return $rows_imps_ins;
    }

    /**
     * Inserta todas las partidas de una plantilla
     * @param int $row_entidad_new_id
     * @return array
     */
    private function inserta_partidas_full(int $row_entidad_new_id): array
    {
        $rows_partidas = $this->rows_partidas();
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener fc_partidas',data:  $rows_partidas);
        }

        $rows_imp_ins = $this->inserta_partidas(row_entidad_new_id: $row_entidad_new_id,rows_partidas:  $rows_partidas);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integrar rows_imp_ins',data:  $rows_imp_ins);
        }
        return $rows_imp_ins;
    }

    /**
     * Inserta un registro de tipo factura basado en una plantilla
     * @return array|stdClass
     * @version 13.5.0
     */
    private function inserta_row_entidad(): array|stdClass
    {
        $row_entidad_ins = $this->genera_row_entidad_ins();
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al maquetar factura',data:  $row_entidad_ins);
        }

        $r_alta_fc = $this->modelo_entidad->alta_registro(registro: $row_entidad_ins);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar factura',data:  $r_alta_fc);
        }
        return $r_alta_fc;
    }

    /**
     * Inserta un registro de tipo partida basado en la plantilla de origen
     * @param int $row_entidad_new_id
     * @param array $row_partida_origen
     * @return array|stdClass
     */
    private function inserta_row_partida(int $row_entidad_new_id, array  $row_partida_origen): array|stdClass
    {
        $row_partida_ins = $this->row_partida_ins(row_entidad_new_id: $row_entidad_new_id,
            row_partida_origen: $row_partida_origen);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al maquetar partida',data:  $row_partida_ins);
        }

        $r_alta_fc_partida = $this->modelo_partida->alta_registro(registro: $row_partida_ins);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al insertar partida',data:  $r_alta_fc_partida);
        }
        return $r_alta_fc_partida;
    }

    /**
     * Integra los key de los impuestos a integrar
     * @return stdClass
     * @version 13.9.0
     */
    private function keys_impuestos(): stdClass
    {
        $key_n_traslados = $this->modelo_partida->tabla.'_n_traslados';
        $key_n_retenidos = $this->modelo_partida->tabla.'_n_retenidos';
        $data = new stdClass();
        $data->key_n_traslados = $key_n_traslados;
        $data->key_n_retenidos = $key_n_retenidos;
        return $data;
    }

    /**
     * Limpia los datos de impuestos precargados en la partida destino para posteriormente integralos con seguridad
     * @param _data_impuestos $modelo_imp
     * @param int $partida_id_new
     * @return array|string[]
     */
    private function limpia_impuestos(_data_impuestos $modelo_imp, int $partida_id_new): array
    {
        $filtro = array();
        $filtro[$this->modelo_partida->key_filtro_id] = $partida_id_new;

        $del = $modelo_imp->elimina_con_filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al eliminar traslados',data:  $del);
        }
        return $del;
    }

    /**
     * Maqueta un registro para insertar un factura
     * @param array $com_tipo_cambio Tipo de cambio
     * @param stdClass $row_entidad Registro precargado
     * @return array
     * @version 13.3.0
     */
    private function row_entidad_ins(array $com_tipo_cambio, stdClass $row_entidad): array
    {

        if(!isset($row_entidad->observaciones)){
            $row_entidad->observaciones = '';
        }

        $valida = $this->valida_row_entidad(com_tipo_cambio: $com_tipo_cambio,row_entidad:  $row_entidad);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar row_entidad',data:  $valida);
        }

        $row_entidad_ins['fc_csd_id'] = $row_entidad->fc_csd_id;
        $row_entidad_ins['cat_sat_forma_pago_id'] = $row_entidad->cat_sat_forma_pago_id;
        $row_entidad_ins['cat_sat_metodo_pago_id'] = $row_entidad->cat_sat_metodo_pago_id;
        $row_entidad_ins['cat_sat_moneda_id'] = $row_entidad->cat_sat_moneda_id;
        $row_entidad_ins['com_tipo_cambio_id'] = $com_tipo_cambio['com_tipo_cambio_id'];
        $row_entidad_ins['cat_sat_uso_cfdi_id'] = $row_entidad->cat_sat_uso_cfdi_id;
        $row_entidad_ins['fecha'] = date('Y-m-d');
        $row_entidad_ins['cat_sat_tipo_de_comprobante_id'] = $row_entidad->cat_sat_tipo_de_comprobante_id;
        $row_entidad_ins['dp_calle_pertenece_id'] = $row_entidad->dp_calle_pertenece_id;
        $row_entidad_ins['exportacion'] = $row_entidad->exportacion;
        $row_entidad_ins['cat_sat_regimen_fiscal_id'] = $row_entidad->cat_sat_regimen_fiscal_id;
        $row_entidad_ins['com_sucursal_id'] = $row_entidad->com_sucursal_id;
        $row_entidad_ins['observaciones'] = $row_entidad->observaciones;
        $row_entidad_ins['total_descuento'] = $row_entidad->total_descuento;
        $row_entidad_ins['sub_total_base'] = $row_entidad->sub_total_base;
        $row_entidad_ins['sub_total'] = $row_entidad->sub_total;
        $row_entidad_ins['total_traslados'] = $row_entidad->total_traslados;
        $row_entidad_ins['total_retenciones'] = $row_entidad->total_retenciones;
        $row_entidad_ins['total'] = $row_entidad->total;
        return $row_entidad_ins;
    }


    /**
     * Obtiene un registro de tipo fc factura nota de credito o complemento de pago
     * @return array|stdClass
     * @version 12.20.2
     */
    private function row_entidad(): array|stdClass
    {
        if($this->row_entidad_id <= 0){
            return $this->error->error(mensaje: 'Error $this->row_entidad_i debe ser mayor a 0',
                data:  $this->row_entidad_id);
        }
        $row_entidad = $this->modelo_entidad->registro(registro_id: $this->row_entidad_id, columnas_en_bruto: true,
            retorno_obj: true);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener registro',data:  $row_entidad);
        }
        return $row_entidad;
    }

    /**
     * Obtiene el registro a insertar de impuestos
     * @param string $name_modelo_imp
     * @param array $row_imp
     * @param int $row_partida_id
     * @return array
     */
    private function row_imp_ins(string $name_modelo_imp,array $row_imp, int $row_partida_id): array
    {
        $key_partida_importe = $this->modelo_partida->tabla.'_importe';
        $key_partida_id = $this->modelo_partida->key_id;
        $key_partida_importe_con_descuento = $this->modelo_partida->tabla."_importe_con_descuento";
        $key_importe = $name_modelo_imp."_importe";

        $row_imp_ins[$key_partida_id] = $row_partida_id;
        $row_imp_ins['cat_sat_tipo_factor_id'] = $row_imp['cat_sat_tipo_factor_id'];
        $row_imp_ins['cat_sat_factor_id'] = $row_imp['cat_sat_factor_id'];
        $row_imp_ins['cat_sat_tipo_impuesto_id'] = $row_imp['cat_sat_tipo_impuesto_id'];
        $row_imp_ins['total'] = $row_imp['total'];
        $row_imp_ins[$key_partida_importe] = $row_imp[$key_partida_importe];
        $row_imp_ins[$key_partida_importe_con_descuento] = $row_imp[$key_partida_importe_con_descuento];
        $row_imp_ins[$key_importe] = $row_imp[$key_importe];
        $row_imp_ins['descripcion'] = $row_imp['descripcion'];
        return $row_imp_ins;
    }

    /**
     * Genera un registro de tip partida para insercion
     * @param int $row_entidad_new_id Entidad base id
     * @param array $row_partida_origen Registro de partida origen de plantilla
     * @return array
     */
    private function row_partida_ins(int $row_entidad_new_id, array $row_partida_origen): array
    {

        $keys_imps = $this->keys_impuestos();
        if(errores::$error){
            return  $this->error->error(mensaje: 'Error obtener keys_imps',data:  $keys_imps);
        }

        $valida = $this->valida_row_partida_insert(keys_imps: $keys_imps,row_entidad_new_id:  $row_entidad_new_id,
            row_partida_origen:  $row_partida_origen);
        if(errores::$error){
            return  $this->error->error(mensaje: 'Error al validar row_partida_origen',data:  $valida);
        }


        $fc_row_ins['com_producto_id'] = $row_partida_origen['com_producto_id'];
        $fc_row_ins['cantidad'] = $row_partida_origen['cantidad'];
        $fc_row_ins['descripcion'] = $row_partida_origen['descripcion'];
        $fc_row_ins['valor_unitario'] = $row_partida_origen['valor_unitario'];
        $fc_row_ins['descuento'] = $row_partida_origen['descuento'];
        $fc_row_ins[$this->modelo_entidad->key_id] = $row_entidad_new_id;
        $fc_row_ins['sub_total_base'] = $row_partida_origen['sub_total_base'];
        $fc_row_ins['sub_total'] = $row_partida_origen['sub_total'];
        $fc_row_ins['total'] = $row_partida_origen['total'];
        $fc_row_ins['total_traslados'] = $row_partida_origen['total_traslados'];
        $fc_row_ins['total_retenciones'] = $row_partida_origen['total_retenciones'];
        $fc_row_ins[$keys_imps->key_n_traslados] = $row_partida_origen[$keys_imps->key_n_traslados];
        $fc_row_ins[$keys_imps->key_n_retenidos] = $row_partida_origen[$keys_imps->key_n_retenidos];

        return $fc_row_ins;
    }

    /**
     * Obtiene las partidas de una plantilla
     * @return array
     * @version 13.8.0
     */
    private function rows_partidas(): array
    {
        $fc_partidas = $this->modelo_partida->get_partidas(key_filtro_entidad_id: $this->modelo_entidad->key_filtro_id,
            registro_entidad_id:  $this->row_entidad_id,columnas_en_bruto: true);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener fc_partidas',data:  $fc_partidas);
        }
        return $fc_partidas;
    }


    /**
     * Valida que existan los keys necesarios para integrar una factura
     * @param stdClass $keys_imps Keys de impuestos
     * @param array $row_partida_origen Partida de plantilla
     * @return array|true
     * @version 13.10.0
     */
    private function valida_existe_key_partida(stdClass $keys_imps, array $row_partida_origen): bool|array
    {
        $keys = array('key_n_traslados','key_n_retenidos');
        $valida = (new validacion())->valida_existencia_keys(keys: $keys,registro:  $keys_imps);
        if(errores::$error){
            return  $this->error->error(mensaje: 'Error al validar keys_imps',data:  $valida);
        }

        $keys = array('com_producto_id','cantidad','descripcion','valor_unitario','descuento','sub_total_base',
            'sub_total','total','total_traslados','total_retenciones',$keys_imps->key_n_traslados, $keys_imps->key_n_retenidos);

        $valida = (new validacion())->valida_existencia_keys(keys: $keys,registro:  $row_partida_origen);
        if(errores::$error){
            return  $this->error->error(mensaje: 'Error al validar row_partida_origen',data:  $valida);
        }
        return true;
    }

    /**
     * Valida los identificadores necesario externos de una partida de plantilla
     * @param array $row_partida_origen Registro de plantilla de partida
     * @return array|true
     * @version 13.11.1
     */
    private function valida_ids_partida(array $row_partida_origen): bool|array
    {
        $keys = array('com_producto_id');

        $valida = (new validacion())->valida_ids(keys: $keys,registro:  $row_partida_origen);
        if(errores::$error){
            return  $this->error->error(mensaje: 'Error al validar row_partida_origen',data:  $valida);
        }
        return true;

    }

    /**
     * Valida que los montos sean validos
     * @param array $row_partida_origen Registro de partida
     * @return array|true
     * @version 13.13.1
     */
    private function valida_monto_mayor_0(array $row_partida_origen): bool|array
    {
        $keys = array('cantidad','valor_unitario','sub_total_base', 'sub_total','total');

        $valida = (new validacion())->valida_double_mayores_0(keys: $keys,registro:  $row_partida_origen);
        if(errores::$error){
            return  $this->error->error(mensaje: 'Error al validar row_partida_origen',data:  $valida);
        }
        return true;
    }

    /**
     * Valida que los montos sean validos
     * @param stdClass $keys_imps Keys de impuestos
     * @param array $row_partida_origen Registro de partida de plantilla
     * @return array|bool
     * @version 13.14.1
     */
    private function valida_monto_mayor_igual_0(stdClass $keys_imps, array $row_partida_origen): bool|array
    {
        $keys = array('key_n_traslados','key_n_retenidos');
        $valida = (new validacion())->valida_existencia_keys(keys: $keys,registro:  $keys_imps);
        if(errores::$error){
            return  $this->error->error(mensaje: 'Error al validar keys_imps',data:  $valida);
        }

        $keys = array('descuento','total_traslados','total_retenciones',
            $keys_imps->key_n_traslados, $keys_imps->key_n_retenidos);

        $valida = (new validacion())->valida_double_mayores_igual_0(keys: $keys,registro:  $row_partida_origen);
        if(errores::$error){
            return  $this->error->error(mensaje: 'Error al validar row_partida_origen',data:  $valida);
        }
        return true;
    }

    /**
     * Valida que los montos de una partida de plantilla sean validos
     * @param stdClass $keys_imps Keys de impuestos
     * @param array $row_partida_origen Registro de partida
     * @return array|true
     * @version 13.14.1
     */
    private function valida_montos(stdClass $keys_imps,array $row_partida_origen): bool|array
    {
        $valida = $this->valida_monto_mayor_0(row_partida_origen: $row_partida_origen);
        if(errores::$error){
            return  $this->error->error(mensaje: 'Error al validar row_partida_origen',data:  $valida);
        }

        $valida = $this->valida_monto_mayor_igual_0(keys_imps: $keys_imps,row_partida_origen:  $row_partida_origen);
        if(errores::$error){
            return  $this->error->error(mensaje: 'Error al validar row_partida_origen',data:  $valida);
        }
        return true;
    }

    /**
     * Valida la entrada de datos de una plantilla
     * @param array $com_tipo_cambio Tipo de cambio
     * @param stdClass $row_entidad Registro de tipo plantilla
     * @return array|true
     * @version 13.3.0
     */
    private function valida_row_entidad(array $com_tipo_cambio, stdClass $row_entidad): bool|array
    {
        $keys = array('fc_csd_id','cat_sat_forma_pago_id','cat_sat_metodo_pago_id','cat_sat_moneda_id',
            'cat_sat_uso_cfdi_id','cat_sat_tipo_de_comprobante_id','dp_calle_pertenece_id','exportacion',
            'cat_sat_regimen_fiscal_id','com_sucursal_id','observaciones','total_descuento','sub_total_base',
            'sub_total','total_traslados','total_retenciones','total');

        $valida = (new validacion())->valida_existencia_keys(keys: $keys,registro:  $row_entidad,valida_vacio: false);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar row_entidad',data:  $valida);
        }

        $keys = array('com_tipo_cambio_id');

        $valida = (new validacion())->valida_ids(keys: $keys,registro:  $com_tipo_cambio);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar com_tipo_cambio',data:  $valida);
        }

        $keys = array('fc_csd_id','cat_sat_forma_pago_id','cat_sat_metodo_pago_id','cat_sat_moneda_id',
            'cat_sat_uso_cfdi_id','cat_sat_tipo_de_comprobante_id','dp_calle_pertenece_id',
            'cat_sat_regimen_fiscal_id','com_sucursal_id');

        $valida = (new validacion())->valida_ids(keys: $keys,registro:  $row_entidad);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar row_entidad',data:  $valida);
        }

        $valida = (new validacion())->valida_cod_int_0_2_numbers(key: 'exportacion',registro: $row_entidad);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar row_entidad',data:  $valida);
        }

        $keys = array('total_descuento','sub_total_base', 'sub_total','total_traslados','total_retenciones','total');

        $valida = (new validacion())->valida_double_mayores_igual_0(keys: $keys,registro:  $row_entidad);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar row_entidad',data:  $valida);
        }
        return true;
    }

    /**
     * Valida los datos de una partida de plantilla
     * @param stdClass $keys_imps Keys de impuestos
     * @param array $row_partida_origen Registro de partida de plantilla
     * @return array|true
     */
    private function valida_row_partida(stdClass $keys_imps, array $row_partida_origen): bool|array
    {
        $valida = $this->valida_existe_key_partida(keys_imps: $keys_imps,row_partida_origen:  $row_partida_origen);
        if(errores::$error){
            return  $this->error->error(mensaje: 'Error al validar row_partida_origen',data:  $valida);
        }

        $valida = $this->valida_ids_partida(row_partida_origen: $row_partida_origen);
        if(errores::$error){
            return  $this->error->error(mensaje: 'Error al validar row_partida_origen',data:  $valida);
        }


        $valida = $this->valida_montos(keys_imps: $keys_imps,row_partida_origen:  $row_partida_origen);
        if(errores::$error){
            return  $this->error->error(mensaje: 'Error al validar row_partida_origen',data:  $valida);
        }
        return true;
    }

    /**
     * Valida los datos de una partida de plantilla
     * @param stdClass $keys_imps
     * @param int $row_entidad_new_id
     * @param array $row_partida_origen
     * @return array|true
     */
    private function valida_row_partida_insert(stdClass $keys_imps, int $row_entidad_new_id, array $row_partida_origen): bool|array
    {
        $valida = $this->valida_row_partida(keys_imps: $keys_imps,row_partida_origen:  $row_partida_origen);
        if(errores::$error){
            return  $this->error->error(mensaje: 'Error al validar row_partida_origen',data:  $valida);
        }

        if($row_entidad_new_id <= 0 ){
            return  $this->error->error(mensaje: 'Error row_entidad_new_id debe ser mayor a 0',
                data:  $row_entidad_new_id);
        }
        return true;
    }
}
