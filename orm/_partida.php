<?php
namespace gamboamartin\facturacion\models;

use gamboamartin\cat_sat\models\cat_sat_conf_imps;
use gamboamartin\comercial\models\com_precio_cliente;
use gamboamartin\errores\errores;
use gamboamartin\system\html_controler;

use gamboamartin\system\links_menu;
use stdClass;

class _partida extends  _base{

    public _data_impuestos $modelo_retencion;
    public _data_impuestos $modelo_traslado;

    protected _transacciones_fc $modelo_entidad;
    protected _cuenta_predial $modelo_predial;

    protected _etapa $modelo_etapa;

    public bool $valida_restriccion = true;


    private function acciones_conf_retenido(bool $aplica_cat_sat_conf_imps, int $cat_sat_conf_imps_id,
                                            stdClass $fc_registro_partida, _data_impuestos $modelo_retencion, array $registro): array|stdClass
    {
        $conf_retenidos = (new fc_conf_retenido($this->link))->get_configuraciones(
            com_producto_id: $registro["com_producto_id"]);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener conf. retenciones', data: $conf_retenidos);
        }
        $conf_descripcion = 'fc_conf_retenido_descripcion';
        if($aplica_cat_sat_conf_imps){
            $conf_retenidos->registros = (new cat_sat_conf_imps(link: $this->link))->get_retenciones(
                cat_sat_conf_imps_id: $cat_sat_conf_imps_id);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al obtener conf. retenciones', data: $conf_retenidos);
            }
            $conf_retenidos->registros = $conf_retenidos->registros;
            $conf_retenidos->n_registros = count($conf_retenidos->registros);
            $conf_descripcion = 'cat_sat_retencion_conf_descripcion';
        }

        if ($conf_retenidos->n_registros === 0) {
            return $conf_retenidos;
        }

        foreach ($conf_retenidos->registros as $configuracion) {
            $retenido = $this->maqueta_datos(configuracion: $configuracion,
                conf_descripcion: $conf_descripcion, fc_registro_partida: $fc_registro_partida, registro: $registro);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al maquetar datos retenidos', data: $retenido);
            }

            $alta_retenido = $modelo_retencion->alta_registro(registro: $retenido);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al dar de alta retenidos', data: $alta_retenido);
            }
        }

        return $alta_retenido;
    }

    private function acciones_conf_traslado(bool $aplica_cat_sat_conf_imps, int $cat_sat_conf_imps_id,
                                            stdClass $fc_registro_partida, _data_impuestos $modelo_traslado,
                                            array $registro): array|stdClass
    {

        $keys = array('com_producto_id');
        $valida = $this->validacion->valida_ids(keys: $keys,registro:  $registro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar $registro', data: $valida);
        }

        $conf_traslados = (new fc_conf_traslado($this->link))->get_configuraciones(
            com_producto_id: $registro["com_producto_id"]);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener conf. traslados', data: $conf_traslados);
        }
        $conf_descripcion = 'fc_conf_traslado_descripcion';
        if($aplica_cat_sat_conf_imps){
            $conf_traslados->registros = (new cat_sat_conf_imps(link: $this->link))->get_traslados(
                cat_sat_conf_imps_id: $cat_sat_conf_imps_id);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al obtener conf. traslados', data: $conf_traslados);
            }
            $conf_traslados->n_registros = count($conf_traslados->registros);
            $conf_descripcion = 'cat_sat_traslado_conf_descripcion';
        }

        if ($conf_traslados->n_registros === 0) {
            return $conf_traslados;
        }

        foreach ($conf_traslados->registros as $configuracion) {
            $traslado = $this->maqueta_datos(configuracion: $configuracion,
                conf_descripcion: $conf_descripcion, fc_registro_partida: $fc_registro_partida, registro: $registro);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al maquetar datos traslados', data: $traslado);
            }


            $alta_traslado = $modelo_traslado->alta_registro(registro: $traslado);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al dar de alta traslados', data: $alta_traslado);
            }
        }

        return $conf_traslados;
    }

    private function actualiza_descripcion_aut(stdClass $com_producto, array $fc_registro_partida, int $registro_id){
        $descripcion = $com_producto->descripcion;
        $descripcion = $this->descripcion_automatica(descripcion: $descripcion,fc_registro_partida:  $fc_registro_partida);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al ajustar descripcion', data: $descripcion);
        }

        $upd_desc['descripcion'] = $descripcion;

        $upd = $this->modifica_bd(registro: $upd_desc,id: $registro_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error actualizar descripcion', data: $upd);
        }
        return $upd;
    }

    private function ajusta_descripcion(string $campo, string $descripcion, mixed $value): array|string
    {
        if(is_null($value)){
            $value = '';
        }
        return str_replace("{{".$campo."}}", $value, $descripcion);
    }

    private function ajusta_descripcion_campos(string $descripcion, array $fc_registro_partida){
        foreach ($fc_registro_partida as $campo=>$value){
            $descripcion = $this->ajusta_descripcion(campo: $campo,descripcion:  $descripcion,value:  $value);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al ajustar descripcion', data: $descripcion);
            }
        }
        return $descripcion;
    }


    /**
     * SOBRRESCRIBIR
     * @param array $keys_integra_ds
     * @return array|stdClass
     */
    public function alta_bd(array $keys_integra_ds = array('codigo', 'descripcion')): array|stdClass
    {
        $modelo_entidad_tabla = trim($this->modelo_entidad->tabla);

        if($modelo_entidad_tabla === ''){
            return $this->error->error(mensaje: 'Error modelo_entidad_tabla esta vacia', data: $modelo_entidad_tabla);
        }

        $key_id_tabla = $modelo_entidad_tabla.'_id';

        $keys = array($key_id_tabla);
        $valida = $this->validacion->valida_ids(keys: $keys,registro:  $this->registro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error validar registro', data: $valida);
        }

        $permite_transaccion = $this->modelo_entidad->verifica_permite_transaccion(
            modelo_etapa: $this->modelo_etapa, registro_id: $this->registro[$key_id_tabla]);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error verificar transaccion', data: $permite_transaccion);
        }

        $registro = $this->init_alta_bd();
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al inicializar campos base', data: $registro);
        }

        $validacion = $this->validaciones(data: $this->registro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar datos', data: $validacion);
        }



        $this->registro = $this->limpia_campos(registro: $this->registro,
            campos_limpiar: array('cat_sat_tipo_factor_id', 'cat_sat_factor_id', 'cat_sat_tipo_impuesto_id'));
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al limpiar campos', data: $this->registro);
        }

        $aplica_cat_sat_conf_imps = false;
        $cat_sat_conf_imps_id = -1;
        if(isset($this->registro['cat_sat_conf_imps_id']) && $this->registro['cat_sat_conf_imps_id']>0){
            $cat_sat_conf_imps_id = $this->registro['cat_sat_conf_imps_id'];
            $aplica_cat_sat_conf_imps = true;
        }
        else{
            $row_entidad = $this->modelo_entidad->registro(registro_id: $registro[$this->modelo_entidad->key_id]);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error obtener entidad', data: $row_entidad);
            }
            $filtro = array();
            $filtro['com_producto.id'] = $registro['com_producto_id'];
            $filtro['com_cliente.id'] = $row_entidad['com_cliente_id'];

            $existe_com_precio_cliente = (new com_precio_cliente(link: $this->link))->existe(filtro: $filtro);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error validar si existe entidad',
                    data: $existe_com_precio_cliente);
            }
            if($existe_com_precio_cliente){
                $r_com_precio_cliente = (new com_precio_cliente(link: $this->link))->filtro_and(filtro: $filtro);
                if (errores::$error) {
                    return $this->error->error(mensaje: 'Error obtener configuracion',
                        data: $r_com_precio_cliente);
                }
                $cat_sat_conf_imps_id = $r_com_precio_cliente->registros[0]['cat_sat_conf_imps_id'];
                $aplica_cat_sat_conf_imps = true;
            }

        }

        $registro = $this->integra_calcula_subtotales(registro: $this->registro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error integrar subtotales', data: $registro);
        }

        $data_predial = array();
        if(isset($registro['cuenta_predial'])){
            $data_predial['cuenta_predial'] = $registro['cuenta_predial'];
            unset($registro['cuenta_predial']);
        }

        if(!isset($this->registro['cat_sat_obj_imp_id']) || (int)$this->registro['cat_sat_obj_imp_id'] <= 0) {
            $com_producto = (new com_producto($this->link))->registro(registro_id: $this->registro['com_producto_id']);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error obtener producto', data: $com_producto);
            }

            $this->registro['cat_sat_obj_imp_id'] = $com_producto['cat_sat_obj_imp_id'];
        }

        $this->registro = $registro;


        $r_alta_bd = parent::alta_bd();
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error registrar partida', data: $r_alta_bd);
        }

        $fc_registro_partida = $this->registro(registro_id: $r_alta_bd->registro_id, retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error obtener partida', data: $fc_registro_partida);
        }


        $traslado = $this->acciones_conf_traslado(aplica_cat_sat_conf_imps: $aplica_cat_sat_conf_imps,
            cat_sat_conf_imps_id: $cat_sat_conf_imps_id, fc_registro_partida: $fc_registro_partida,
            modelo_traslado: $this->modelo_traslado, registro: (array)$fc_registro_partida);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al realizar acciones de conf. traslado', data: $traslado);
        }

        $retenido = $this->acciones_conf_retenido(aplica_cat_sat_conf_imps: $aplica_cat_sat_conf_imps,
            cat_sat_conf_imps_id: $cat_sat_conf_imps_id,fc_registro_partida: $fc_registro_partida,
            modelo_retencion: $this->modelo_retencion, registro: (array)$fc_registro_partida);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al realizar acciones de conf. retenido', data: $retenido);
        }


        $r_fc_cuenta_predial = $this->integra_predial(data_predial: $data_predial,fc_registro_partida:  $fc_registro_partida);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al insertar predial', data: $r_fc_cuenta_predial);
        }

        $fc_registro_partida = $this->registro(registro_id: $r_alta_bd->registro_id, columnas_en_bruto: true,
            retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error obtener partida', data: $fc_registro_partida);
        }


        $upd = $this->upd_total_partida(row_partida_id: $r_alta_bd->registro_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al modificar', data: $upd);
        }

        $regenera = $this->regenera_totales(fc_registro_partida: $fc_registro_partida);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al modificar entidad base', data: $regenera);
        }


        $upd = $this->integra_descripcion_automatica(registro_id: $r_alta_bd->registro_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error actualizar descripcion', data: $upd);
        }

        $fc_registro_partida = $this->registro(registro_id: $r_alta_bd->registro_id, retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error obtener partida', data: $fc_registro_partida);
        }

        $fc_registro_partida_puro = $this->registro(registro_id: $r_alta_bd->registro_id, columnas_en_bruto: true,
            retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error obtener partida', data: $fc_registro_partida_puro);
        }

        $r_alta_bd->registro_obj = $fc_registro_partida;
        $r_alta_bd->registro_puro = $fc_registro_partida_puro;

        return $r_alta_bd;
    }

    private function data_descripcion_aut(int $registro_id){
        $fc_registro_partida = $this->registro(registro_id: $registro_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error obtener partida', data: $fc_registro_partida);
        }

        $com_producto = (new com_producto(link: $this->link))->registro(registro_id: $fc_registro_partida['com_producto_id'],
            columnas_en_bruto: true, retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error obtener producto', data: $com_producto);
        }
        $data = new stdClass();
        $data->fc_registro_partida = $fc_registro_partida;
        $data->com_producto = $com_producto;
        return $data;

    }

    private function descripcion_automatica(string $descripcion, array $fc_registro_partida){
        $descripcion = $this->descripcion_generales(descripcion: $descripcion);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al ajustar descripcion', data: $descripcion);
        }

        $descripcion = $this->ajusta_descripcion_campos(descripcion: $descripcion,fc_registro_partida:  $fc_registro_partida);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al ajustar descripcion', data: $descripcion);
        }
        return $descripcion;
    }

    private function descripcion_generales(string $descripcion){
        $descripcion = $this->descripcion_mes_letra(descripcion: $descripcion);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al ajustar descripcion', data: $descripcion);
        }
        $descripcion = $this->descripcion_year(descripcion: $descripcion);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al ajustar descripcion', data: $descripcion);
        }
        return $descripcion;
    }

    private function descripcion_mes_letra(string $descripcion): array|string
    {
        $descripcion = trim($descripcion);
        if($descripcion === ''){
            return $this->error->error(mensaje: 'Error descripcion esta vacio', data: $descripcion);
        }
        $mes = date('m');
        $mes_letra = $this->mes['espaniol'][$mes]['nombre'];

        $descripcion = str_replace("{{MES_LETRA}}", $mes_letra, $descripcion);
        return str_replace("{{mes_letra}}", $mes_letra, $descripcion);
    }

    private function descripcion_year(string $descripcion): array|string
    {
        $year = (int)date('Y');

        return str_replace("{{YEAR}}", $year, $descripcion);
    }

    private function integra_calcula_subtotales(array $registro): array
    {
        $sub_total_base = round(round($registro['valor_unitario'],4)
            * round($this->registro['cantidad'],4),4);

        $registro['sub_total_base'] = $sub_total_base;

        $descuento = 0.0;
        if(isset($registro['descuento'])){
            $descuento = round($registro['descuento'],2);
        }
        $registro['sub_total'] = $sub_total_base - $descuento;

        return $registro;
    }
    private function integra_descripcion_automatica(int $registro_id){
        $data_desc_aut = $this->data_descripcion_aut(registro_id: $registro_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error obtener datos', data: $data_desc_aut);
        }
        $upd = new stdClass();

        if($data_desc_aut->com_producto->es_automatico === 'activo'){

            $upd = $this->actualiza_descripcion_aut(com_producto: $data_desc_aut->com_producto,
                fc_registro_partida:  $data_desc_aut->fc_registro_partida,registro_id:  $registro_id);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error actualizar descripcion', data: $upd);
            }
        }
        return $upd;
    }
    private function inserta_predial(stdClass $fc_registro_partida){
        $key_id = $this->tabla.'_id';
        $data_predial[$key_id] = $fc_registro_partida->$key_id;

        $r_fc_cuenta_predial = $this->modelo_predial->alta_registro(registro: $data_predial);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al insertar predial', data: $r_fc_cuenta_predial);
        }
        return $r_fc_cuenta_predial;
    }
    private function integra_predial(array $data_predial, stdClass $fc_registro_partida){

        if(count($data_predial)>0){

            if($fc_registro_partida->com_producto_aplica_predial === 'activo'){

                $r_fc_cuenta_predial = $this->inserta_predial(fc_registro_partida: $fc_registro_partida);
                if (errores::$error) {
                    return $this->error->error(mensaje: 'Error al insertar predial', data: $r_fc_cuenta_predial);
                }

            }
        }
        return $data_predial;
    }

    /**
     * Refcatorizar para una sola transacion
     * @param stdClass $fc_registro_partida
     * @return array|stdClass
     */
    private function regenera_totales(stdClass $fc_registro_partida): array|stdClass
    {
        $key_entidad_id = $this->modelo_entidad->key_id;

        $registro_entidad_id = $fc_registro_partida->$key_entidad_id;

        $regenera_total_descuento = $this->regenera_fc_total_descuento(registro_entidad_id: $registro_entidad_id,
            key_filtro_entidad_id: $this->modelo_entidad->key_filtro_id, modelo_entidad: $this->modelo_entidad);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al modificar entidad base', data: $regenera_total_descuento);
        }

        $regenera_sub_total_base = $this->regenera_fc_sub_total_base(registro_entidad_id: $registro_entidad_id,
            key_filtro_entidad_id: $this->modelo_entidad->key_filtro_id, modelo_entidad: $this->modelo_entidad);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al modificar entidad base', data: $regenera_sub_total_base);
        }

        $regenera_sub_total = $this->regenera_fc_sub_total(registro_entidad_id: $registro_entidad_id,
            key_filtro_entidad_id: $this->modelo_entidad->key_filtro_id, modelo_entidad: $this->modelo_entidad);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al modificar entidad base', data: $regenera_sub_total);
        }

        $regenera_total_traslados = $this->regenera_fc_total_traslados(registro_entidad_id: $registro_entidad_id,
            key_filtro_entidad_id: $this->modelo_entidad->key_filtro_id, modelo_entidad: $this->modelo_entidad);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al modificar entidad base', data: $regenera_sub_total);
        }

        $regenera_total_retenciones = $this->regenera_fc_total_retenciones(registro_entidad_id: $registro_entidad_id,
            key_filtro_entidad_id: $this->modelo_entidad->key_filtro_id, modelo_entidad: $this->modelo_entidad);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al modificar entidad base', data: $regenera_total_retenciones);
        }

        $regenera_total = $this->regenera_fc_total(registro_entidad_id: $registro_entidad_id,
            key_filtro_entidad_id: $this->modelo_entidad->key_filtro_id, modelo_entidad: $this->modelo_entidad);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al modificar entidad base', data: $regenera_total);
        }

        $regenera_saldo = $this->regenera_fc_saldo(registro_entidad_id: $registro_entidad_id,
            key_filtro_entidad_id: $this->modelo_entidad->key_filtro_id, modelo_entidad: $this->modelo_entidad);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al modificar entidad base', data: $regenera_saldo);
        }


        $data = new stdClass();
        $data->regenera_total_descuento = $regenera_total_descuento;
        $data->regenera_sub_total_base = $regenera_sub_total_base;
        $data->regenera_sub_total = $regenera_sub_total;
        $data->regenera_total_traslados = $regenera_total_traslados;
        $data->regenera_total_retenciones = $regenera_total_retenciones;
        $data->regenera_total = $regenera_total;
        $data->regenera_saldo = $regenera_saldo;
        return $data;
    }
    private function upd_total_partida(int $row_partida_id){
        $fc_registro_partida = $this->registro(registro_id: $row_partida_id, columnas_en_bruto: true, retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error obtener partida', data: $fc_registro_partida);
        }

        $keys = array('sub_total','total_traslados','total_retenciones');
        $valida = $this->validacion->valida_existencia_keys(keys: $keys,registro:  $fc_registro_partida);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar fc_registro_partida', data: $valida);
        }

        $total = $fc_registro_partida->sub_total + $fc_registro_partida->total_traslados
            - $fc_registro_partida->total_retenciones;

        $row_partida_upd['total'] = $total;

        $upd = $this->modifica_bd(registro: $row_partida_upd, id: $fc_registro_partida->id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al modificar', data: $upd);
        }
        return $upd;
    }

    /**
     * Calcula el impuesto retenido de una partida
     * @param _data_impuestos $modelo_retencion Modelo de tipo retencion
     * @param int $registro_partida_id Registro de partida
     * @return array|float|int
     */
    final public function calculo_imp_retenido(_data_impuestos $modelo_retencion, int $registro_partida_id): float|array|int
    {
        $resultado = $this->calcula_impuesto(modelo_imp: $modelo_retencion, registro_partida_id: $registro_partida_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener los operacion', data: $resultado);
        }
        return $resultado;
    }

    /**
     * Calcula el impuesto de una partida
     * @param _data_impuestos $modelo_imp Modelo de tip impuesto
     * @param int $registro_partida_id Registro de partida
     * @return array|float
     */
    private function calcula_impuesto(_data_impuestos $modelo_imp, int $registro_partida_id): float|array
    {
        $filtro[$this->key_filtro_id] = $registro_partida_id;


        $params = $this->params_calculo(filtro: $filtro, modelo_imp: $modelo_imp,
            registro_partida_id:  $registro_partida_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener parametros', data: $params);
        }

        $resultado = $this->resultado_operacion_imp(params: $params);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener los operacion', data: $resultado);
        }
        return $resultado;
    }

    /**
     * Calcula los impuestos trasladados de una partida
     * @param _data_impuestos $modelo_traslado
     * @param int $registro_partida_id Partida a calcular
     * @return float
     */
    final public function calculo_imp_trasladado(_data_impuestos $modelo_traslado, int $registro_partida_id):float
    {
        $resultado = $this->calcula_impuesto(modelo_imp: $modelo_traslado, registro_partida_id: $registro_partida_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener los operacion', data: $resultado);
        }

        return $resultado;
    }

    final public function data_partida_obj(int $registro_partida_id): array|stdClass
    {
        $fc_partida = $this->registro(registro_id: $registro_partida_id, columnas_en_bruto: true, retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener partida', data: $fc_partida);
        }

        $data = new stdClass();
        $key = $this->tabla;
        $data->$key = $fc_partida;

        return $data;
    }

    /**
     * Elimina un registro con todas sus dependencias de una entidad de tipo partida
     * @param int $id Identificador a eliminar
     * @return array|stdClass
     */
    public function elimina_bd(int $id): array|stdClass
    {

        $init = $this->init_elimina_bd(id: $id,modelo_entidad:  $this->modelo_entidad,
            modelo_etapa:  $this->modelo_etapa,modelo_predial:  $this->modelo_predial,
            modelo_retencion:  $this->modelo_retencion,modelo_traslado:  $this->modelo_traslado);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al eliminar dependientes de partidas', data: $init);
        }


        $fc_registro_partida = $this->registro(registro_id: $id, retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error obtener partida', data: $fc_registro_partida);
        }

        $r_elimina_bd = parent::elimina_bd(id: $id); // TODO: Change the autogenerated stub
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al eliminar partida', data: $r_elimina_bd);
        }


        $regenera = $this->regenera_totales(fc_registro_partida: $fc_registro_partida);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al modificar entidad base', data: $regenera);
        }

        return $r_elimina_bd;
    }

    /**
     * Elimina los elementos dependientes de una partida
     * @param int $id Identificador de base partida
     * @param _cuenta_predial $modelo_predial Modelo de tipo predial
     * @param _data_impuestos $modelo_retencion Modelo de tipo retencion
     * @param _data_impuestos $modelo_traslado Modelo de tipo traslado
     * @return array|stdClass
     * @version 10.45.3
     */
    private function elimina_dependientes(int $id, _cuenta_predial $modelo_predial, _data_impuestos $modelo_retencion,
                                          _data_impuestos $modelo_traslado): array|stdClass
    {

        $filtro[$this->tabla.'.id'] = $id;
        $r_fc_retenido = $modelo_retencion->elimina_con_filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error('Error al eliminar r_fc_retenido', $r_fc_retenido);
        }
        $r_fc_traslado = $modelo_traslado->elimina_con_filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error('Error al eliminar r_fc_traslado', $r_fc_traslado);
        }
        $r_fc_cuenta_predial = $modelo_predial->elimina_con_filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al eliminar predial', data: $r_fc_cuenta_predial);
        }
        $data = new stdClass();
        $data->r_fc_retenido = $r_fc_retenido;
        $data->r_fc_traslado = $r_fc_traslado;
        $data->r_fc_cuenta_predial = $r_fc_cuenta_predial;

        return $data;
    }

    private function fc_entidad_sub_total(string $key_filtro_entidad_id, int $registro_entidad_id){
        $fc_partidas = $this->get_partidas(key_filtro_entidad_id: $key_filtro_entidad_id,
            registro_entidad_id:  $registro_entidad_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener r_fc_partida', data: $fc_partidas);
        }

        $sub_total = 0.0;
        foreach ($fc_partidas as $fc_partida){
            $sub_total += round($fc_partida[$this->tabla.'_sub_total_base'],2)- round($fc_partida[$this->tabla.'_descuento']);
        }
        return round($sub_total,2);

    }

    private function fc_entidad_total_traslados(string $key_filtro_entidad_id, int $registro_entidad_id){
        $fc_partidas = $this->get_partidas(key_filtro_entidad_id: $key_filtro_entidad_id,
            registro_entidad_id:  $registro_entidad_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener r_fc_partida', data: $fc_partidas);
        }

        $total_traslados = 0.0;
        foreach ($fc_partidas as $fc_partida){
            $total_traslados += round($fc_partida[$this->tabla.'_total_traslados'],2);
        }
        return round($total_traslados,2);

    }

    private function fc_entidad_total_retenciones(string $key_filtro_entidad_id, int $registro_entidad_id){
        $fc_partidas = $this->get_partidas(key_filtro_entidad_id: $key_filtro_entidad_id,
            registro_entidad_id:  $registro_entidad_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener r_fc_partida', data: $fc_partidas);
        }

        $total_retenciones = 0.0;
        foreach ($fc_partidas as $fc_partida){
            $total_retenciones += round($fc_partida[$this->tabla.'_total_retenciones'],2);
        }
        return round($total_retenciones,2);

    }

    private function fc_entidad_total(string $key_filtro_entidad_id, int $registro_entidad_id){
        $fc_partidas = $this->get_partidas(key_filtro_entidad_id: $key_filtro_entidad_id,
            registro_entidad_id:  $registro_entidad_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener r_fc_partida', data: $fc_partidas);
        }

        $total = 0.0;
        foreach ($fc_partidas as $fc_partida){
            $keys = array($this->tabla.'_total');
            $valida = $this->validacion->valida_double_mayores_igual_0(keys: $keys,registro:  $fc_partida);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al validar fc_partida', data: $valida);
            }

            $total += round($fc_partida[$this->tabla.'_total'],2);
        }
        return round($total,2);

    }
    private function fc_entidad_sub_total_base(string $key_filtro_entidad_id, int $registro_entidad_id){
        $fc_partidas = $this->get_partidas(key_filtro_entidad_id: $key_filtro_entidad_id,
            registro_entidad_id:  $registro_entidad_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener r_fc_partida', data: $fc_partidas);
        }

        $sub_total_base = 0.0;
        foreach ($fc_partidas as $fc_partida){
            $keys = array($this->tabla.'_sub_total_base');
            $valida = $this->validacion->valida_double_mayores_igual_0(keys: $keys,registro:  $fc_partida);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al validar fc_partida', data: $valida);
            }

            $sub_total_base += round($fc_partida[$this->tabla.'_sub_total_base'],2);
        }
        return round($sub_total_base,2);

    }

    /**
     * Obtiene los descuentos de una factura con datos de las partidas
     * @param string $key_filtro_entidad_id Jey de la entidad para uso de filtros
     * @param int $registro_entidad_id Registro en proceso
     * @return array|float
     * @version 10.145.5
     */
    private function fc_entidad_total_descuento(string $key_filtro_entidad_id, int $registro_entidad_id): float|array
    {

        if ($registro_entidad_id <= 0) {
            return $this->error->error(mensaje: 'Error registro_entidad_id debe ser mayor a 0',
                data: $registro_entidad_id);
        }
        $key_filtro_entidad_id = trim($key_filtro_entidad_id);
        if($key_filtro_entidad_id === ''){
            return $this->error->error(mensaje: 'Error key_filtro_entidad_id esta vacio', data: $key_filtro_entidad_id);
        }

        $fc_partidas = $this->get_partidas(key_filtro_entidad_id: $key_filtro_entidad_id,
            registro_entidad_id:  $registro_entidad_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener r_fc_partida', data: $fc_partidas);
        }

        $total_descuento = 0.0;
        foreach ($fc_partidas as $fc_partida){
            $total_descuento += round($fc_partida[$this->tabla.'_descuento'],2);
        }
        return round($total_descuento,2);

    }


    /**
     * Obtiene una partida
     * @param int $registro_partida_id Partida a validar
     * @return array
     * 10.44.3
     */
    final public function get_partida(int $registro_partida_id): array
    {
        if ($registro_partida_id <= 0) {
            return $this->error->error(mensaje: 'Error registro_partida_id debe ser mayor a 0', data: $registro_partida_id);
        }

        $registro = $this->registro(registro_id: $registro_partida_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener partida', data: $registro);
        }

        return $registro;
    }

    /**
     * Obtiene las partidas de una factura
     * @param string $key_filtro_entidad_id Jey id para filtro de factura complemento
     * @param int $registro_entidad_id Factura a validar
     * @param array $columnas_by_table
     * @param bool $columnas_en_bruto
     * @return array
     * @version 10.93.3
     */
    final public function get_partidas(string $key_filtro_entidad_id,int $registro_entidad_id,
                                       array $columnas_by_table = array(), bool $columnas_en_bruto = false): array
    {
        if ($registro_entidad_id <= 0) {
            return $this->error->error(mensaje: 'Error registro_entidad_id debe ser mayor a 0',
                data: $registro_entidad_id);
        }
        $key_filtro_entidad_id = trim($key_filtro_entidad_id);
        if($key_filtro_entidad_id === ''){
            return $this->error->error(mensaje: 'Error key_filtro_entidad_id esta vacio', data: $key_filtro_entidad_id);
        }

        $filtro[$key_filtro_entidad_id] = $registro_entidad_id;
        $r_fc_partida = $this->filtro_and(columnas_by_table: $columnas_by_table, columnas_en_bruto: $columnas_en_bruto, filtro: $filtro,);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener partidas', data: $r_fc_partida);
        }

        return $r_fc_partida->registros;
    }

    final public function get_retenciones(int $partida_id)
    {
        $filtro[$this->key_filtro_id] = $partida_id;
        $r_retenciones = $this->modelo_retencion->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener retenciones', data: $r_retenciones);
        }
        return $r_retenciones->registros;

    }

    final public function get_traslados(int $partida_id)
    {
        $filtro[$this->key_filtro_id] = $partida_id;
        $r_traslados = $this->modelo_traslado->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener traslados', data: $r_traslados);
        }
        return $r_traslados->registros;

    }


    /**
     * Genera un hijo de tipo impuesto
     * @param array $hijo configuracion de hijo
     * @param string $name_modelo_impuesto Nombre del modelo de tipo impuesto
     * @return array
     */
    private function hijo(array $hijo, string $name_modelo_impuesto): array
    {

        $tabla = trim($name_modelo_impuesto);
        if($tabla === ''){
            return $this->error->error(mensaje: 'Error name_modelo_impuesto esta vacia', data: $name_modelo_impuesto);
        }

        $hijo[$name_modelo_impuesto]['filtros'][$this->key_filtro_id] = $this->key_id;
        $hijo[$name_modelo_impuesto]['filtros_con_valor'] = array();
        $hijo[$name_modelo_impuesto]['nombre_estructura'] = $name_modelo_impuesto;
        $hijo[$name_modelo_impuesto]['namespace_model'] = 'gamboamartin\\facturacion\\models';
        return $hijo;
    }

    /**
     * Obtiene los childrens de un retenido
     * @param array $hijo Children
     * @param _data_impuestos $modelo_retencion Modelo de tipo impuesto
     * @return array
     */
    private function hijo_retenido(array $hijo, _data_impuestos $modelo_retencion): array
    {

        $tabla = $this->tabla_impuesto(modelo_impuesto: $modelo_retencion);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integra modelo', data: $tabla);
        }

        $hijo = $this->hijo(hijo: $hijo, name_modelo_impuesto: $tabla);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integrar hijo', data: $hijo);
        }

        return $hijo;
    }

    /**
     * Maqueta el elemento para un children de factura
     * @param array $hijo Hijo a maquetar
     * @param _data_impuestos $modelo_traslado Modelo de tipo traslado
     * @return array
     */
    private function hijo_traslado(array $hijo, _data_impuestos $modelo_traslado): array
    {
        $tabla = $this->tabla_impuesto(modelo_impuesto: $modelo_traslado);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integra modelo', data: $tabla);
        }

        $hijo = $this->hijo(hijo: $hijo, name_modelo_impuesto: $tabla);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integrar hijo', data: $hijo);
        }
        return $hijo;
    }

    /**
     * Integra los hijos de una partida
     * @param _data_impuestos $modelo_retencion Modelo de retenciones
     * @param _data_impuestos $modelo_traslado Modelo de traslados
     * @return array
     */
    private function hijos_partida(_data_impuestos $modelo_retencion, _data_impuestos $modelo_traslado): array
    {
        $hijo = array();

        $hijo = $this->hijo_traslado(hijo: $hijo,modelo_traslado: $modelo_traslado);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al integrar hijo', data: $hijo);
        }
        $hijo = $this->hijo_retenido(hijo: $hijo, modelo_retencion: $modelo_retencion);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al integrar hijo', data: $hijo);
        }
        return $hijo;
    }

    /**
     * Inicializa lo elementos para eliminaciones
     * @param int $id Identificador de partida
     * @param _transacciones_fc $modelo_entidad Entidad base
     * @param _etapa $modelo_etapa Modelo de la etapa
     * @param _cuenta_predial $modelo_predial Modelo del predial
     * @param _data_impuestos $modelo_retencion Modelo de la retencion
     * @param _data_impuestos $modelo_traslado Modelo del traslado
     * @return array|stdClass
     * @version 10.136.4
     */
    private function init_elimina_bd(int $id,_transacciones_fc $modelo_entidad, _etapa $modelo_etapa,
                                     _cuenta_predial $modelo_predial, _data_impuestos $modelo_retencion,
                                     _data_impuestos $modelo_traslado): array|stdClass
    {

        $modelo_traslado->valida_restriccion = $this->valida_restriccion;
        $modelo_retencion->valida_restriccion = $this->valida_restriccion;

        $permite_transaccion = $this->valida_restriccion_partida(id: $id,modelo_entidad:  $modelo_entidad,
            modelo_etapa:  $modelo_etapa);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error verificar transaccion', data: $permite_transaccion);
        }

        $del_dependientes = $this->elimina_dependientes(id: $id,modelo_predial:  $modelo_predial,
            modelo_retencion:  $modelo_retencion,modelo_traslado:  $modelo_traslado);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al eliminar dependientes de partidas', data: $del_dependientes);
        }
        return $del_dependientes;
    }

    /**
     * Integra un boton para partida
     * @param html_controler $html Html base de template
     * @param int $indice Indice de registro de partida
     * @param string $name_modelo_entidad Nombre base de la entidad
     * @param array $partida Partida a integrar datos
     * @param stdClass $r_fc_registro_partida Registros de partidas
     * @param int $registro_entidad_id Identificador de partida
     * @return array|stdClass
     * @version 10.37.1
     */
    private function integra_button_partida(html_controler $html, int $indice, string $name_modelo_entidad, array $partida,
                                            stdClass $r_fc_registro_partida, int $registro_entidad_id): array|stdClass
    {

        $name_modelo_entidad = trim($name_modelo_entidad);
        if($name_modelo_entidad === ''){
            return $this->error->error(mensaje: 'Error name_modelo_entidad esta vacio', data: $name_modelo_entidad);
        }
        if($registro_entidad_id <= 0){
            return $this->error->error(mensaje: 'Error registro_entidad_id debe ser mayor a 0',
                data: $registro_entidad_id);
        }
        if($indice < 0){
            return $this->error->error(mensaje: 'Error indice no puede ser negativo', data: $indice);
        }

        $key_id = $this->key_id;
        if($key_id === ''){
            return $this->error->error(mensaje: 'Error al partida controler key id esta vacio', data: $key_id);
        }

        $keys = array($key_id);
        $valida = $this->validacion->valida_ids(keys: $keys,registro:  $partida);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar partida', data: $valida);
        }


        $params = $this->params_button_partida(name_modelo_entidad: $name_modelo_entidad,
            registro_entidad_id: $registro_entidad_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener params', data: $params);
        }

        $link_elimina_partida = $html->button_href(accion: 'elimina_bd', etiqueta: 'Eliminar',
            registro_id: $partida[$key_id], seccion: $this->tabla, style: 'danger',icon: 'bi bi-trash',
            muestra_icono_btn: true, muestra_titulo_btn: false, params: $params);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al generar link elimina_bd para partida',
                data: $link_elimina_partida);
        }
        $r_fc_registro_partida->registros[$indice]['elimina_bd'] = $link_elimina_partida;

        $params = array();
        $params['registro_partida_id'] = $partida[$key_id];
        $link_modifica_partida_bd = (new links_menu(link: $this->link,registro_id: -1))->link_con_id(
            accion: 'modifica_partida_bd',link:  $this->link,registro_id:  $registro_entidad_id,
            seccion: $name_modelo_entidad,params: $params);

        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al generar link para modificar partida',
                data: $link_modifica_partida_bd);
        }

        $r_fc_registro_partida->registros[$indice]['link_modifica_partida_bd'] = $link_modifica_partida_bd;
        return $r_fc_registro_partida;
    }

    /**
     * Integra botones de eliminacion en las partidas de un elemento de tipo factura
     * @param array $filtro Filtro para obtener partidas
     * @param array $hijo Children para integrar partidas
     * @param html_controler $html Html
     * @param string $name_modelo_entidad Nombre de la entidad base
     * @param int $registro_entidad_id Identificador de la entidad base
     * @return array|stdClass
     */
    private function integra_buttons_partida( array $filtro, array $hijo, html_controler $html,
                                              string $name_modelo_entidad, int $registro_entidad_id): array|stdClass
    {

        $name_modelo_entidad = trim($name_modelo_entidad);
        if($name_modelo_entidad === ''){
            return $this->error->error(mensaje: 'Error name_modelo_entidad esta vacio', data: $name_modelo_entidad);
        }
        if($registro_entidad_id <= 0){
            return $this->error->error(mensaje: 'Error registro_entidad_id debe ser mayor a 0',
                data: $registro_entidad_id);
        }

        $r_fc_partida = $this->filtro_and(filtro: $filtro, hijo: $hijo);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener partidas', data: $r_fc_partida);
        }

        foreach ($r_fc_partida->registros as $indice => $partida) {
            $r_fc_partida = $this->integra_button_partida(html: $html, indice: $indice,
                name_modelo_entidad: $name_modelo_entidad, partida: $partida, r_fc_registro_partida: $r_fc_partida,
                registro_entidad_id: $registro_entidad_id);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al generar link elimina_bd para partida', data: $r_fc_partida);
            }
        }
        return $r_fc_partida;
    }

    /**
     * Por mover a base previos si existe algo asi
     * @param array $registro Registro en proceso
     * @param array $campos_limpiar Campos a limpiar
     * @return array
     */
    private function limpia_campos(array $registro, array $campos_limpiar): array
    {
        foreach ($campos_limpiar as $valor) {
            if (isset($registro[$valor])) {
                unset($registro[$valor]);
            }
        }
        return $registro;
    }
    private function maqueta_datos(array $configuracion, string $conf_descripcion, stdClass $fc_registro_partida,
                                   array $registro): array
    {
        $traslado = array();
        $traslado['descripcion'] = $configuracion[$conf_descripcion];
        $traslado['descripcion'] .= " " . $registro[$this->tabla.'_descripcion'];
        $traslado['cat_sat_tipo_factor_id'] = $configuracion['cat_sat_tipo_factor_id'];
        $traslado['cat_sat_factor_id'] = $configuracion['cat_sat_factor_id'];
        $traslado['cat_sat_tipo_impuesto_id'] = $configuracion['cat_sat_tipo_impuesto_id'];
        $key_id = $this->tabla.'_id';
        $traslado[$key_id] = $fc_registro_partida->$key_id;

        return $traslado;
    }

    public function modifica_bd(array $registro, int $id, bool $reactiva = false,
                                array $keys_integra_ds = array('codigo', 'descripcion')): array|stdClass
    {

        $partida = $this->get_partida(registro_partida_id: $id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener partida', data: $partida);
        }
/*
        $permite_transaccion = $this->modelo_entidad->verifica_permite_transaccion(
            modelo_etapa: $this->modelo_etapa, registro_id: $this->modelo_entidad->key_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error verificar transaccion', data: $permite_transaccion);
        }
*/
        if (!isset($registro['codigo'])) {
            $registro['codigo'] = $partida[$this->tabla."_codigo"];
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al obtener el codigo del registro', data: $registro);
            }
        }

        $registro = $this->campos_base(data: $registro, modelo: $this, id: $id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al inicializar campos base', data: $registro);
        }

        $registro = $this->limpia_campos(registro: $registro,
            campos_limpiar: array('cat_sat_tipo_factor_id', 'cat_sat_factor_id', 'cat_sat_tipo_impuesto_id'));
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al limpiar campos', data: $registro);
        }

        $r_modifica_bd = parent::modifica_bd(registro: $registro,id:  $id,reactiva:  $reactiva);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al modificar partida', data: $r_modifica_bd);
        }

        $partida = $this->get_partida(registro_partida_id: $id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener partida', data: $partida);
        }

        if(!isset($partida[$this->tabla.'_valor_unitario'])){
            return $this->error->error(mensaje: 'Error no existe campo valor unitario', data: $partida);
        }

        $sub_total_base = round(round($partida[$this->tabla.'_valor_unitario'],4)
            * round($partida[$this->tabla.'_cantidad'],4),4);

        $registro_stb_upd['sub_total_base'] = $sub_total_base;


        $descuento = 0.0;
        if(isset($partida[$this->tabla.'_descuento'])){
            $descuento = round($partida[$this->tabla.'_descuento'],2);
        }
        $registro_stb_upd['sub_total'] = $sub_total_base - $descuento;


        $upd = parent::modifica_bd(registro: $registro_stb_upd,id:  $id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al actualizar sub total base', data: $upd);
        }

        $this->modelo_traslado->modelo_entidad = $this->modelo_entidad;
        $this->modelo_traslado->modelo_etapa = $this->modelo_etapa;
        $this->modelo_traslado->modelo_partida = $this;

        $traslados = $this->get_traslados(partida_id: $id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener traslados', data: $traslados);
        }

        foreach ($traslados as $traslado){
           // print_r($traslado);exit;
            $trs_upd[$this->key_id] = $id;
            $trs_upd['cat_sat_tipo_factor_id'] = $traslado['cat_sat_tipo_factor_id'];
            $trs_upd['cat_sat_factor_id'] = $traslado['cat_sat_factor_id'];
            $trs_upd['cat_sat_tipo_impuesto_id'] = $traslado['cat_sat_tipo_impuesto_id'];
            $upd_traslado = $this->modelo_traslado->modifica_bd(registro: $trs_upd, id: $traslado[$this->modelo_traslado->key_id]);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al actualizar traslado', data: $upd_traslado);
            }
        }

        $this->modelo_retencion->modelo_entidad = $this->modelo_entidad;
        $this->modelo_retencion->modelo_etapa = $this->modelo_etapa;
        $this->modelo_retencion->modelo_partida = $this;

        $retenciones = $this->get_retenciones(partida_id: $id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener retenciones', data: $retenciones);
        }

        foreach ($retenciones as $retencion){
            $ret_upd[$this->key_id] = $id;
            $ret_upd['cat_sat_tipo_factor_id'] = $retencion['cat_sat_tipo_factor_id'];
            $ret_upd['cat_sat_factor_id'] = $retencion['cat_sat_factor_id'];
            $ret_upd['cat_sat_tipo_impuesto_id'] = $retencion['cat_sat_tipo_impuesto_id'];
            $upd_retencion = $this->modelo_retencion->modifica_bd(registro: $ret_upd, id: $retencion[$this->modelo_retencion->key_id]);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al actualizar retencion', data: $upd_retencion);
            }
        }

        //print_r($traslados);exit;

        $fc_registro_partida = $this->registro(registro_id: $id, columnas_en_bruto: true, retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error obtener partida', data: $fc_registro_partida);
        }



        $total = $fc_registro_partida->sub_total + $fc_registro_partida->total_traslados
            - $fc_registro_partida->total_retenciones;

        //echo $total;exit;

        $row_partida_upd['total'] = $total;
        $upd = parent::modifica_bd(registro: $row_partida_upd,id:  $id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al actualizar sub total base', data: $upd);
        }

        $fc_registro_partida = $this->registro(registro_id: $id, retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error obtener partida', data: $fc_registro_partida);
        }

        $regenera = $this->regenera_totales(fc_registro_partida: $fc_registro_partida);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al modificar entidad base', data: $regenera);
        }


        $registro_puro = $this->registro(registro_id: $id, columnas_en_bruto: true, retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error obtener registro_puro', data: $registro_puro);
        }

        $fc_registro_partida = $this->registro(registro_id: $id, retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error obtener partida', data: $fc_registro_partida);
        }

        $r_modifica_bd->registro_actualizado = (array)$fc_registro_partida;
        $r_modifica_bd->registro_puro = (array)$registro_puro;

        return $r_modifica_bd;
    }

    /**
     * Ejecuta multiplicacion de resultado
     * @param float $subtotal Subtotal de la partida
     * @param array $row Registro de impuesto
     * @return float|array
     * @version 12.13.2
     */
    private function operacion_factor(float $subtotal, array $row): float|array
    {
        $keys = array('cat_sat_factor_factor');
        $valida = $this->validacion->valida_existencia_keys(keys: $keys,registro:  $row);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar row',data:  $valida);
        }
        $valida = $this->validacion->valida_double_mayores_igual_0(keys: $keys,registro:  $row);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar row',data:  $valida);
        }
        if($subtotal<0.0){
            return $this->error->error(mensaje: 'Error subtotal debe ser mayor o igual a 0',data:  $subtotal);
        }

        return round($subtotal * round($row['cat_sat_factor_factor'],6),2);
    }

    /**
     * Integra los parametros por get para retorno
     * @param string $name_modelo_entidad Nombre del modelo o entidad de retorno
     * @param int $registro_entidad_id Registro id de la entidad de retorno
     * @return array
     * @version 10.10.0
     */
    final public function params_button_partida(string $name_modelo_entidad, int $registro_entidad_id): array
    {

        $name_modelo_entidad = trim($name_modelo_entidad);
        if($name_modelo_entidad === ''){
            return $this->error->error(mensaje: 'Error name_modelo_entidad esta vacio', data: $name_modelo_entidad);
        }

        if($registro_entidad_id <= 0){
            return $this->error->error(mensaje: 'Error registro_entidad_id debe ser mayor a 0',
                data: $registro_entidad_id);
        }

        $params = array();
        $params['seccion_retorno'] = $name_modelo_entidad;
        $params['accion_retorno'] = 'modifica';
        $params['id_retorno'] = $registro_entidad_id;
        return $params;
    }

    /**
     * Obtiene los parametros para calcular retenciones y traslados
     * @param array $filtro Filtro de datos
     * @param _data_impuestos $modelo_imp Modelo de tipo impuesto
     * @param int $registro_partida_id Partida identificador
     * @return array|stdClass
     * @version 12.13.2
     */
    private function params_calculo(array $filtro,_data_impuestos $modelo_imp, int $registro_partida_id): array|stdClass
    {
        if ($registro_partida_id <= 0) {
            return $this->error->error(mensaje: 'Error el id de la partida es incorrecto', data: $registro_partida_id);
        }

        $data_imp = $modelo_imp->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener los registros', data: $data_imp);
        }

        $subtotal = $this->subtotal_partida(registro_partida_id: $registro_partida_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener los registros', data: $subtotal);
        }
        $data = new stdClass();
        $data->data_imp = $data_imp;
        $data->subtotal = $subtotal;
        return $data;
    }

    /**
     * Obtiene las partidas de una entidad de tipo factura
     * @param html_controler $html Html de template
     * @param _transacciones_fc $modelo_entidad Modelo de tipo fc_factura, fc_complemento_pago
     * @param _data_impuestos $modelo_retencion Modelo de tipo retenido
     * @param _data_impuestos $modelo_traslado Modelo de tipo traslado
     * @param int $registro_entidad_id Identificador de factura o complemento
     * @param array $hijo  datos relacionados de partidas
     * @return array|stdClass
     */
    final public function partidas( html_controler $html, _transacciones_fc $modelo_entidad,_data_impuestos $modelo_retencion,
                              _data_impuestos $modelo_traslado, int $registro_entidad_id,array $hijo = array()): array|stdClass
    {
        if ($registro_entidad_id <= 0) {
            return $this->error->error(mensaje: 'Error registro_entidad_id debe ser mayor a 0', data: $registro_entidad_id);
        }

        $filtro[$modelo_entidad->key_filtro_id] = $registro_entidad_id;
        $hijo = $this->hijos_partida(modelo_retencion: $modelo_retencion, modelo_traslado: $modelo_traslado);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al integrar hijo', data: $hijo);
        }


        $r_fc_partida = $this->integra_buttons_partida(filtro: $filtro, hijo: $hijo,
            html: $html, name_modelo_entidad: $modelo_entidad->tabla, registro_entidad_id: $registro_entidad_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al generar link elimina_bd para partida', data: $r_fc_partida);
        }

        return $r_fc_partida;
    }

    private function regenera_fc_sub_total(int $registro_entidad_id, string $key_filtro_entidad_id,
                                                _transacciones_fc $modelo_entidad){
        $sub_total = $this->fc_entidad_sub_total(key_filtro_entidad_id: $key_filtro_entidad_id,
            registro_entidad_id:  $registro_entidad_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener sub_total_base', data: $sub_total);
        }

        $fc_entidad_upd['sub_total'] = $sub_total;
        //print_r($fc_entidad_upd);exit;
        $r_entidad_upd = $modelo_entidad->modifica_bd(registro: $fc_entidad_upd,id:  $registro_entidad_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al actualizar sub_total_base', data: $r_entidad_upd);
        }
        return $r_entidad_upd;
    }

    private function regenera_fc_total_traslados(int $registro_entidad_id, string $key_filtro_entidad_id,
                                           _transacciones_fc $modelo_entidad){
        $total_traslados = $this->fc_entidad_total_traslados(key_filtro_entidad_id: $key_filtro_entidad_id,
            registro_entidad_id:  $registro_entidad_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener sub_total_base', data: $total_traslados);
        }

        $fc_entidad_upd['total_traslados'] = $total_traslados;

        $r_entidad_upd = $modelo_entidad->modifica_bd(registro: $fc_entidad_upd,id:  $registro_entidad_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al actualizar sub_total_base', data: $r_entidad_upd);
        }
        return $r_entidad_upd;
    }

    private function regenera_fc_total_retenciones(int $registro_entidad_id, string $key_filtro_entidad_id,
                                                 _transacciones_fc $modelo_entidad){
        $total_retenciones = $this->fc_entidad_total_retenciones(key_filtro_entidad_id: $key_filtro_entidad_id,
            registro_entidad_id:  $registro_entidad_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener total_retenciones', data: $total_retenciones);
        }

        $fc_entidad_upd['total_retenciones'] = $total_retenciones;

        $r_entidad_upd = $modelo_entidad->modifica_bd(registro: $fc_entidad_upd,id:  $registro_entidad_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al actualizar sub_total_base', data: $r_entidad_upd);
        }
        return $r_entidad_upd;
    }

    private function regenera_fc_total(int $registro_entidad_id, string $key_filtro_entidad_id,
                                                   _transacciones_fc $modelo_entidad){
        $total_retenciones = $this->fc_entidad_total(key_filtro_entidad_id: $key_filtro_entidad_id,
            registro_entidad_id:  $registro_entidad_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener total_retenciones', data: $total_retenciones);
        }

        $fc_entidad_upd['total'] = $total_retenciones;

        $r_entidad_upd = $modelo_entidad->modifica_bd(registro: $fc_entidad_upd,id:  $registro_entidad_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al actualizar sub_total_base', data: $r_entidad_upd);
        }
        return $r_entidad_upd;
    }

    private function regenera_fc_saldo(int $registro_entidad_id, string $key_filtro_entidad_id,
                                       _transacciones_fc $modelo_entidad){
        $total = $this->fc_entidad_total(key_filtro_entidad_id: $key_filtro_entidad_id,
            registro_entidad_id:  $registro_entidad_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener total_retenciones', data: $total);
        }

        $fc_entidad_upd['saldo'] = $total;

        $r_entidad_upd = $modelo_entidad->modifica_bd(registro: $fc_entidad_upd,id:  $registro_entidad_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al actualizar sub_total_base', data: $r_entidad_upd);
        }
        return $r_entidad_upd;
    }


    private function regenera_fc_sub_total_base(int $registro_entidad_id, string $key_filtro_entidad_id,
                                                 _transacciones_fc $modelo_entidad){
        $sub_total_base = $this->fc_entidad_sub_total_base(key_filtro_entidad_id: $key_filtro_entidad_id,
            registro_entidad_id:  $registro_entidad_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener sub_total_base', data: $sub_total_base);
        }

        $fc_entidad_upd['sub_total_base'] = $sub_total_base;
        //print_r($fc_entidad_upd);exit;
        $r_entidad_upd = $modelo_entidad->modifica_bd(registro: $fc_entidad_upd,id:  $registro_entidad_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al actualizar sub_total_base', data: $r_entidad_upd);
        }
        return $r_entidad_upd;
    }

    private function regenera_fc_total_descuento(int $registro_entidad_id, string $key_filtro_entidad_id,
                                                 _transacciones_fc $modelo_entidad){

        if ($registro_entidad_id <= 0) {
            return $this->error->error(mensaje: 'Error registro_entidad_id debe ser mayor a 0',
                data: $registro_entidad_id);
        }
        $key_filtro_entidad_id = trim($key_filtro_entidad_id);
        if($key_filtro_entidad_id === ''){
            return $this->error->error(mensaje: 'Error key_filtro_entidad_id esta vacio', data: $key_filtro_entidad_id);
        }

        $total_descuento = $this->fc_entidad_total_descuento(key_filtro_entidad_id: $key_filtro_entidad_id,
            registro_entidad_id:  $registro_entidad_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener total_descuento', data: $total_descuento);
        }

        $fc_entidad_upd['total_descuento'] = $total_descuento;
        $r_entidad_upd = $modelo_entidad->modifica_bd(registro: $fc_entidad_upd,id:  $registro_entidad_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al actualizar total_descuento', data: $r_entidad_upd);
        }
        return $r_entidad_upd;
    }

    /**
     * Obtiene el resultado de una operacion de calculo de impuestos
     * @param stdClass $params parametros a integrar subtotal y registro de impuestos
     * @return array|float
     * @version 12.13.2
     */
    private function resultado_operacion_imp(stdClass $params): float|array
    {
        if(!isset($params->data_imp)){
            return $this->error->error(mensaje: 'Error $params->data_imp debe existir', data: $params);
        }
        if(!is_object($params->data_imp)){
            return $this->error->error(mensaje: 'Error $params->data_imp debe ser un objeto', data: $params);
        }
        if(!isset($params->data_imp->n_registros)){
            return $this->error->error(mensaje: 'Error $params->data_imp->n_registros debe existir', data: $params);
        }

        $resultado = 0.0;

        if ((int)$params->data_imp->n_registros > 0) {

            if(!isset($params->subtotal)){
                return $this->error->error(mensaje: 'Error $params->subtotal debe existir', data: $params);
            }
            if(!isset($params->data_imp->registros)){
                return $this->error->error(mensaje: 'Error $params->data_imp->registros debe existir', data: $params);
            }
            if(!is_array($params->data_imp->registros)){
                return $this->error->error(mensaje: 'Error $params->data_imp->registros debe ser un array',
                    data: $params);
            }
            if(!isset($params->data_imp->registros[0])){
                return $this->error->error(mensaje: 'Error $params->data_imp->registros[0] debe existir', data: $params);
            }
            if(!is_numeric($params->subtotal)){
                return $this->error->error(mensaje: 'Error $params->subtotal debe ser un numero', data: $params);
            }
            if(!is_array($params->data_imp->registros[0])){
                return $this->error->error(mensaje: 'Error $params->data_imp->registros[0] debe ser un array',
                    data: $params);
            }
            $keys = array('cat_sat_factor_factor');
            $valida = $this->validacion->valida_existencia_keys(keys: $keys,
                registro:  $params->data_imp->registros[0]);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al validar params',data:  $valida);
            }
            $valida = $this->validacion->valida_double_mayores_igual_0(keys: $keys,
                registro:  $params->data_imp->registros[0]);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al validar params',data:  $valida);
            }
            if($params->subtotal<0.0){
                return $this->error->error(mensaje: 'Error subtotal debe ser mayor o igual a 0',data:  $params);
            }

            $resultado = $this->operacion_factor(subtotal: $params->subtotal,row:  $params->data_imp->registros[0]);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al obtener los operacion', data: $resultado);
            }

        }
        return $resultado;
    }


    /**
     * Calcula el subtotal de una partida
     * @param int $registro_partida_id Partida a validar
     * @return float|array
     * @version 12.11.2
     */
    final public function subtotal_partida(int $registro_partida_id): float|array
    {
        if ($registro_partida_id <= 0) {
            return $this->error->error(mensaje: 'Error el id de la partida es incorrecto', data: $registro_partida_id);
        }

        $data = $this->registro(registro_id: $registro_partida_id, retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener los registros de la partida', data: $data);
        }

        $key_sub_total_base = $this->tabla.'_sub_total_base';
        $key_descuento = $this->tabla.'_descuento';

        $data->$key_sub_total_base = round($data->$key_sub_total_base,2);
        $data->$key_descuento = round($data->$key_descuento,2);

        return round($data->$key_sub_total_base - $data->$key_descuento, 2);
    }

    /**
     * POR DOCUMENTAR EN WIKI
     * La función tabla_impuesto valida y normaliza el nombre de la tabla de impuesto en
     * el modelo de impuestos brindado.
     *
     * @param _data_impuestos $modelo_impuesto Modelo de impuestos que contiene la tabla a validar.
     *
     * @return array|string Retorna el nombre de la tabla si la validación es exitosa.
     * Si se encuentra alguna inconsistencia con los datos de entrada,
     * retorna un array con la descripción del error.
     * @version 27.32.0
     */
    private function tabla_impuesto(_data_impuestos $modelo_impuesto): array|string
    {
        $modelo_impuesto->tabla = trim($modelo_impuesto->tabla);

        if(!isset($modelo_impuesto->tabla)){
            return $this->error->error(mensaje: 'Error no existe tabla definida de impuesto', data: $modelo_impuesto);
        }

        $tabla = trim($modelo_impuesto->tabla);
        if($tabla === ''){
            return $this->error->error(mensaje: 'Error la tabla esta vacia', data: $tabla);
        }
        return $tabla;
    }

    /**
     * Calcula el total de una partida
     * @param int $registro_partida_id Partida a validar
     * @return float|array
     */
    final public function total_partida(int $registro_partida_id): float|array
    {
        if ($registro_partida_id <= 0) {
            return $this->error->error(mensaje: 'Error el id de la partida es incorrecto', data: $registro_partida_id);
        }

        $subtotal = $this->subtotal_partida(registro_partida_id: $registro_partida_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al calcular el subtotal de la partida', data: $subtotal);
        }

        return round($subtotal , 2);
    }

    private function valida_cantidades(array $data): bool|array
    {
        $keys = array('cantidad');
        foreach ($keys as $key) {
            if (!isset($data[$key])) {
                return $this->error->error(mensaje: "Error debe de existir: $key", data: $data);
            }

            if ((int)$data[$key] <= 0) {
                return $this->error->error(mensaje: "Error $key no puede ser menor o igual a 0", data: $this->registro);
            }
        }
        return true;
    }

    /**
     * Valida si una transaccion es valida y proceder
     * @param _transacciones_fc $modelo_entidad Modelo de tipo factura complemento etc
     * @param _etapa $modelo_etapa Modelo de tipo etapa factura_etapa, complemento_etapa etc
     * @param stdClass $row_partida Registro de tipo partida
     * @return array|bool
     * @version 10.4.0
     */
    private function valida_restriccion(_transacciones_fc $modelo_entidad, _etapa $modelo_etapa,
                                        stdClass $row_partida): bool|array
    {
        $permite_transaccion = true;
        $key_entidad_id = $modelo_entidad->tabla.'_id';
        if($this->valida_restriccion){
            $keys = array($key_entidad_id);

            $valida = $this->validacion->valida_ids(keys: $keys,registro:  $row_partida);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error validar row_partida', data: $valida);
            }


            $permite_transaccion = $modelo_entidad->verifica_permite_transaccion(modelo_etapa: $modelo_etapa,
                registro_id: $row_partida->$key_entidad_id);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error verificar transaccion', data: $permite_transaccion);
            }
        }
        return $permite_transaccion;
    }

    /**
     * Verifica si una partida puede o no eliminarse
     * @param int $id Identificador de la partida
     * @param _transacciones_fc $modelo_entidad Modelo base
     * @param _etapa $modelo_etapa Modelo para identificar etapa
     * @return array|bool
     * @version 10.5.0
     */
    private function valida_restriccion_partida(int $id, _transacciones_fc $modelo_entidad,
                                                _etapa $modelo_etapa): bool|array
    {
        if($id <= 0){
            return $this->error->error(mensaje: 'Error id debe ser mayor a 0', data: $id);
        }
        $row_partida = $this->registro(registro_id: $id, retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error obtener row_partida', data: $row_partida);
        }

        $permite_transaccion = $this->valida_restriccion(modelo_entidad: $modelo_entidad,
            modelo_etapa:  $modelo_etapa,row_partida:  $row_partida);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error verificar transaccion', data: $permite_transaccion);
        }
        return $permite_transaccion;
    }

    private function validaciones(array $data): bool|array
    {
        $keys = array('descripcion', 'codigo');

        $valida = $this->validacion->valida_existencia_keys(keys: $keys, registro: $data);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar campos', data: $valida);
        }

        $keys = array('com_producto_id', $this->modelo_entidad->tabla.'_id');
        $valida = $this->validacion->valida_ids(keys: $keys, registro: $this->registro);
        if (errores::$error) {
            return $this->error->error(mensaje: "Error al validar foraneas", data: $valida);
        }

        $valida = $this->valida_cantidades($data);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error validar cantidades', data: $valida);
        }

        $com_producto = (new com_producto(link: $this->link))->registro(
            registro_id: $this->registro['com_producto_id'], retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error obtener com_producto', data: $com_producto);
        }

        $keys = array('com_producto_aplica_predial');
        $valida = $this->validacion->valida_statuses(keys: $keys,registro:  $com_producto);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar com_producto', data: $valida);
        }

        if($com_producto->com_producto_aplica_predial === 'activo'){
            $keys = array('cuenta_predial');

            $valida = $this->validacion->valida_existencia_keys(keys: $keys, registro: $data);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al validar campos', data: $valida);
            }
        }



        return true;
    }

}
