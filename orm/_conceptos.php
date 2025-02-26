<?php

namespace gamboamartin\facturacion\models;
use gamboamartin\errores\errores;
use stdClass;

class _conceptos{

    private errores $error;
    public function __construct()
    {
        $this->error = new errores();

    }

    private function acumula_totales(stdClass $data_partida, _partida $modelo_partida, float $total_impuestos_retenidos,
                                     float $total_impuestos_trasladados): stdClass
    {
        $key_importe_total_traslado = $modelo_partida->tabla.'_total_traslados';
        $key_importe_total_retenido = $modelo_partida->tabla.'_total_retenciones';

        $total_impuestos_trasladados += ($data_partida->partida[$key_importe_total_traslado]);
        $total_impuestos_retenidos += ($data_partida->partida[$key_importe_total_retenido]);

        $totales = new stdClass();
        $totales->total_impuestos_retenidos = $total_impuestos_retenidos;
        $totales->total_impuestos_trasladados = $total_impuestos_trasladados;

        return $totales;

    }

    private function carga_totales(stdClass $data_partida, _partida $modelo_partida,
                                   _data_impuestos $modelo_retencion, _data_impuestos $modelo_traslado, array $ret_global,
                                   float $total_impuestos_retenidos, float $total_impuestos_trasladados, array $trs_global)
    {
        $totales = $this->acumula_totales(data_partida: $data_partida,modelo_partida: $modelo_partida,
            total_impuestos_retenidos: $total_impuestos_retenidos,total_impuestos_trasladados: $total_impuestos_trasladados);

        if(errores::$error){
            return $this->error->error(mensaje: 'Error al cargar $totales', data: $totales);
        }

        $total_impuestos_trasladados = $totales->total_impuestos_trasladados;
        $total_impuestos_retenidos = $totales->total_impuestos_retenidos;

        $globales_imps = $this->impuestos_globales(data_partida: $data_partida,modelo_partida:  $modelo_partida,
            modelo_retencion:  $modelo_retencion,
            modelo_traslado: $modelo_traslado, ret_global: $ret_global,trs_global:  $trs_global);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al cargar globales_imps', data: $globales_imps);
        }

        $ret_global = $globales_imps->ret_global;
        $trs_global = $globales_imps->trs_global;

        $totales_fin = new stdClass();
        $totales_fin->total_impuestos_trasladados = $total_impuestos_trasladados;
        $totales_fin->total_impuestos_retenidos = $total_impuestos_retenidos;
        $totales_fin->ret_global = $ret_global;
        $totales_fin->trs_global = $trs_global;

        return $totales_fin;

    }

    private function concepto(stdClass $data_partida, stdClass $keys_part): stdClass
    {
        $concepto = new stdClass();
        $concepto->clave_prod_serv = $data_partida->partida['cat_sat_producto_codigo'];
        $concepto->cantidad = $data_partida->partida[$keys_part->cantidad];
        $concepto->clave_unidad = $data_partida->partida['cat_sat_unidad_codigo'];
        $concepto->descripcion = $data_partida->partida[$keys_part->descripcion];
        $concepto->valor_unitario = number_format($data_partida->partida[$keys_part->valor_unitario], 2);
        $concepto->importe = number_format($data_partida->partida[$keys_part->importe], 2);
        $concepto->objeto_imp = $data_partida->partida['cat_sat_obj_imp_codigo'];
        $concepto->no_identificacion = $data_partida->partida['com_producto_codigo'];
        $concepto->unidad = $data_partida->partida['cat_sat_unidad_descripcion'];

        return $concepto;

    }

    private function cuenta_predial(
        stdClass $concepto, string $key_filtro_id, _cuenta_predial $modelo_predial, array $partida, int $registro_id): array|stdClass
    {

        // Validar que el ID del registro sea mayor a 0
        if ($registro_id <= 0) {
            return $this->error->error(
                mensaje: "Error: el registro_id debe ser mayor a 0",
                data: $registro_id,
                es_final: true
            );
        }

        $key_filtro_id = trim($key_filtro_id);
        if($key_filtro_id === ''){
            return $this->error->error(
                mensaje: "Error: el $key_filtro_id esta vacio", data: $registro_id, es_final: true
            );
        }

        // Verificar si el producto en la partida requiere cuenta predial
        if(isset($partida['com_producto_aplica_predial']) && $partida['com_producto_aplica_predial'] === 'activo'){

            // Obtener el número de cuenta predial del registro
            $fc_cuenta_predial_numero = $this->fc_cuenta_predial_numero(key_filtro_id: $key_filtro_id,
                modelo_predial: $modelo_predial,
                registro_id:  $registro_id
            );

            // Manejo de errores si la cuenta predial no pudo obtenerse
            if (errores::$error) {
                return $this->error->error(
                    mensaje: 'Error al obtener cuenta predial',
                    data: $fc_cuenta_predial_numero
                );
            }

            // Asignar la cuenta predial al concepto
            $concepto->cuenta_predial = $fc_cuenta_predial_numero;
        }

        return $concepto;
    }

    private function data_partida(
        _partida $modelo_partida,
        _data_impuestos $modelo_retencion,
        _data_impuestos $modelo_traslado,
        array $partida
    ): array|stdClass
    {
        // Verifica que la clave identificadora de la partida exista en el array $partida
        if (!isset($partida[$modelo_partida->key_id])) {
            return $this->error->error(
                mensaje: 'Error $partida[$modelo_partida->key_id] no existe '.$modelo_partida->key_id,
                data: $partida,
                es_final: true
            );
        }

        // Obtiene los impuestos trasladados y retenidos de la partida
        $imp_partida = $this->get_impuestos_partida(
            modelo_partida: $modelo_partida,
            modelo_retencion:  $modelo_retencion,
            modelo_traslado:  $modelo_traslado,
            partida:  $partida
        );

        // Manejo de error en la obtención de impuestos
        if (errores::$error) {
            return $this->error->error(
                mensaje: 'Error al obtener los impuestos de la partida',
                data: $imp_partida
            );
        }

        // Integra los datos temporales del producto a la partida
        $partida = $this->integra_producto_tmp(partida: $partida);

        // Manejo de error en la integración del producto temporal
        if(errores::$error){
            return $this->error->error(
                mensaje: 'Error al integrar producto temporal',
                data: $partida
            );
        }

        // Agrega la información de la partida al objeto de impuestos
        $imp_partida->partida = $partida;

        return $imp_partida;
    }

    private function descuento(stdClass $data_partida, string $key_descuento): float|array
    {
        // Elimina espacios en blanco en la clave del descuento
        $key_descuento = trim($key_descuento);

        // Validación: La clave del descuento no debe estar vacía
        if ($key_descuento === '') {
            return $this->error->error(
                mensaje: 'Error $key_descuento está vacío',
                data: $key_descuento,
                es_final: true
            );
        }

        // Valor por defecto del descuento
        $descuento = 0.0;

        // Verifica si la clave del descuento está definida en la partida
        if (isset($data_partida->partida[$key_descuento])) {
            $descuento = $data_partida->partida[$key_descuento];
        }

        // Formatear el monto del descuento con dos decimales
        $descuento = (new _comprobante())->monto_dos_dec(monto: $descuento);

        // Validación de errores después del formateo
        if (errores::$error) {
            return $this->error->error(
                mensaje: 'Error al maquetar descuento',
                data: $descuento
            );
        }

        return $descuento;
    }

    private function fc_cuenta_predial_numero(string $key_filtro_id, _cuenta_predial $modelo_predial, int $registro_id): array|string
    {

        // Validar que el ID del registro sea mayor a 0
        if ($registro_id <= 0) {
            return $this->error->error(
                mensaje: "Error: el registro_id debe ser mayor a 0",
                data: $registro_id,
                es_final: true
            );
        }
        $key_filtro_id = trim($key_filtro_id);
        if($key_filtro_id === ''){
            return $this->error->error(
                mensaje: 'Error: el $key_filtro_id esta vacio', data: $registro_id, es_final: true
            );
        }

        // Obtener la cuenta predial asociada al registro
        $r_fc_cuenta_predial = $this->r_fc_cuenta_predial(key_filtro_id: $key_filtro_id,
            modelo_predial: $modelo_predial,
            registro_id: $registro_id
        );

        // Verificar si hubo un error en la consulta
        if (errores::$error) {
            return $this->error->error(
                mensaje: 'Error al obtener cuenta predial',
                data: $r_fc_cuenta_predial
            );
        }

        // Obtener la clave de la cuenta predial en el resultado
        $key_cuenta_predial_descripcion = $modelo_predial->tabla . '_descripcion';

        // Retornar el número de cuenta predial
        return $r_fc_cuenta_predial->registros[0][$key_cuenta_predial_descripcion];
    }

    private function genera_concepto(stdClass $data_partida, string $key_filtro_id, _partida $modelo_partida, _cuenta_predial $modelo_predial,
                                     _data_impuestos $modelo_retencion, _data_impuestos $modelo_traslado, int $registro_id)
    {

        $key_filtro_id = trim($key_filtro_id);
        if($key_filtro_id === ''){
            return $this->error->error(
                mensaje: "Error: el $key_filtro_id esta vacio", data: $registro_id, es_final: true
            );
        }

        $concepto = $this->integra_descuento(data_partida: $data_partida,modelo_partida: $modelo_partida);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al maquetar impuestos', data: $concepto);
        }

        $concepto = $this->inicializa_impuestos_de_concepto(concepto: $concepto);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al maquetar impuestos', data: $concepto);
        }

        $concepto = $this->integra_traslados(concepto: $concepto, data_partida: $data_partida,
            modelo_partida: $modelo_partida, modelo_traslado: $modelo_traslado);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al maquetar traslados', data: $concepto);
        }

        $concepto = $this->integra_retenciones(concepto: $concepto, data_partida: $data_partida,
            modelo_partida:  $modelo_partida,modelo_retencion:  $modelo_retencion);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al maquetar traslados', data: $concepto);
        }

        $concepto = $this->cuenta_predial(concepto: $concepto, key_filtro_id: $key_filtro_id,
            modelo_predial: $modelo_predial, partida: $data_partida->partida, registro_id: $registro_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al integrar cuenta predial', data: $concepto);
        }

        return $concepto;

    }
    private function get_impuestos_partida(
        _partida $modelo_partida,
        _data_impuestos $modelo_retencion,
        _data_impuestos $modelo_traslado,
        array $partida
    ): array|stdClass
    {
        // Verifica que la clave identificadora de la partida exista en el array $partida
        if (!isset($partida[$modelo_partida->key_id])) {
            return $this->error->error(
                mensaje: 'Error $partida[$modelo_partida->key_id] no existe '.$modelo_partida->key_id,
                data: $partida,
                es_final: true
            );
        }

        // Obtiene los impuestos trasladados asociados a la partida
        $traslados = $modelo_traslado->get_data_rows(
            name_modelo_partida: $modelo_partida->tabla,
            registro_partida_id: $partida[$modelo_partida->key_id]
        );

        // Manejo de error en la obtención de impuestos trasladados
        if (errores::$error) {
            return $this->error->error(
                mensaje: 'Error al obtener el traslados de la partida',
                data: $traslados
            );
        }

        // Obtiene los impuestos retenidos asociados a la partida
        $retenidos = $modelo_retencion->get_data_rows(
            name_modelo_partida: $modelo_partida->tabla,
            registro_partida_id: $partida[$modelo_partida->key_id]
        );

        // Manejo de error en la obtención de impuestos retenidos
        if (errores::$error) {
            return $this->error->error(
                mensaje: 'Error al obtener el retenidos de la partida',
                data: $retenidos
            );
        }

        // Retorna los impuestos de la partida en un objeto estándar
        $data = new stdClass();
        $data->traslados = $traslados;
        $data->retenidos = $retenidos;

        return $data;
    }

    private function impuestos_globales(
        stdClass $data_partida, _partida $modelo_partida, _data_impuestos $modelo_retencion,
        _data_impuestos $modelo_traslado, array $ret_global, array $trs_global)
    {
        $key_traslado_importe = $modelo_traslado->tabla.'_importe';
        $trs_global = (new _impuestos())->impuestos_globales(
            impuestos: $data_partida->traslados, global_imp: $trs_global, key_importe: $key_traslado_importe,
            name_tabla_partida: $modelo_partida->tabla);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al inicializar acumulado', data: $trs_global);
        }

        $key_retenido_importe = $modelo_retencion->tabla.'_importe';
        $ret_global = (new _impuestos())->impuestos_globales(
            impuestos: $data_partida->retenidos, global_imp: $ret_global, key_importe: $key_retenido_importe,
            name_tabla_partida: $modelo_partida->tabla);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al inicializar acumulado', data: $ret_global);
        }

        $impuestos_glb = new stdClass();
        $impuestos_glb->ret_global = $ret_global;
        $impuestos_glb->trs_global = $trs_global;

        return $impuestos_glb;

    }

    private function inicializa_impuestos_de_concepto(stdClass $concepto): stdClass
    {
        // Inicializa la propiedad impuestos como un array vacío en el concepto
        $concepto->impuestos = array();

        // Agrega una estructura de impuestos en el primer índice del array
        $concepto->impuestos[0] = new stdClass();

        // Inicializa las propiedades traslados y retenciones como arrays vacíos
        $concepto->impuestos[0]->traslados = array();
        $concepto->impuestos[0]->retenciones = array();

        // Retorna el objeto concepto con la estructura de impuestos inicializada
        return $concepto;
    }

    private function integra_descuento(stdClass $data_partida, _partida $modelo_partida)
    {

        $keys_part = $this->keys_partida(modelo_partida: $modelo_partida);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener $keys_part', data: $keys_part);
        }

        $concepto = $this->concepto(data_partida: $data_partida,keys_part: $keys_part);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al maquetar $concepto', data: $concepto);
        }

        $descuento = $this->descuento(data_partida: $data_partida,key_descuento:  $keys_part->descuento);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al maquetar descuento', data: $descuento);
        }

        $concepto->descuento = $descuento;

        return $concepto;

    }

    final public function integra_partidas(array $conceptos, string $key, string $key_filtro_id, _partida $modelo_partida, _cuenta_predial $modelo_predial,
                                      _data_impuestos $modelo_retencion, _data_impuestos $modelo_traslado,
                                      array $partida, array $registro, int $registro_id, array $ret_global,
                                      float $total_impuestos_retenidos, float $total_impuestos_trasladados, array $trs_global)
    {

        $key_filtro_id = trim($key_filtro_id);
        if($key_filtro_id === ''){
            return $this->error->error(
                mensaje: "Error: el $key_filtro_id esta vacio", data: $registro_id, es_final: true
            );
        }

        $data_partida = $this->data_partida(modelo_partida: $modelo_partida,modelo_retencion:  $modelo_retencion,
            modelo_traslado:  $modelo_traslado,partida:  $partida);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integrar producto temporal', data: $partida);
        }

        $registro['partidas'][$key]['traslados'] = $data_partida->traslados->registros;
        $registro['partidas'][$key]['retenidos'] = $data_partida->retenidos->registros;


        $concepto = $this->genera_concepto(data_partida: $data_partida, key_filtro_id: $key_filtro_id,
            modelo_partida: $modelo_partida, modelo_predial: $modelo_predial,
            modelo_retencion: $modelo_retencion, modelo_traslado: $modelo_traslado, registro_id: $registro_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al integrar concepto', data: $concepto);
        }


        $conceptos[] = $concepto;



        $totales = $this->carga_totales(data_partida: $data_partida, modelo_partida: $modelo_partida,
            modelo_retencion: $modelo_retencion, modelo_traslado: $modelo_traslado, ret_global: $ret_global,
            total_impuestos_retenidos: $total_impuestos_retenidos,
            total_impuestos_trasladados: $total_impuestos_trasladados, trs_global: $trs_global);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al cargar $totales', data: $totales);
        }

        $datos = new stdClass();


        $datos->ret_global = $totales->ret_global;
        $datos->trs_global = $totales->trs_global;
        $datos->total_impuestos_trasladados = $totales->total_impuestos_trasladados;
        $datos->total_impuestos_retenidos = $totales->total_impuestos_retenidos;
        $datos->conceptos = $conceptos;
        $datos->registro = $registro;

        return $datos;

    }

    /**
     * POR ELIMINAR FUNCION Y OBTENER DE COM PRODUCTO
     * @param array $partida
     * @return array
     */
    private function integra_producto_tmp(array $partida): array
    {
        $partida['cat_sat_producto_codigo'] = $partida['com_producto_codigo_sat'];
        return $partida;

    }

    private function integra_retenciones(stdClass $concepto, stdClass $data_partida, _partida $modelo_partida,
                                         _data_impuestos $modelo_retencion)
    {
        $key_retenido_importe = $modelo_retencion->tabla.'_importe';
        $impuestos = (new _impuestos())->maqueta_impuesto(impuestos: $data_partida->retenidos,
            key_importe_impuesto: $key_retenido_importe, name_tabla_partida: $modelo_partida->tabla);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al maquetar retenciones', data: $impuestos);
        }

        $concepto->impuestos[0]->retenciones = $impuestos;

        return $concepto;

    }

    private function integra_traslados(stdClass $concepto, stdClass $data_partida, _partida $modelo_partida,
                                       _data_impuestos $modelo_traslado){
        $key_traslado_importe = $modelo_traslado->tabla.'_importe';
        $impuestos = (new _impuestos())->maqueta_impuesto(impuestos: $data_partida->traslados,
            key_importe_impuesto: $key_traslado_importe,name_tabla_partida: $modelo_partida->tabla);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al maquetar traslados', data: $impuestos);
        }

        $concepto->impuestos[0]->traslados = $impuestos;

        return $concepto;
    }

    private function keys_partida(_partida $modelo_partida): stdClass
    {
        $key_cantidad = $modelo_partida->tabla.'_cantidad';
        $key_descripcion = $modelo_partida->tabla.'_descripcion';
        $key_valor_unitario = $modelo_partida->tabla.'_valor_unitario';
        $key_importe = $modelo_partida->tabla.'_sub_total_base';
        $key_descuento = $modelo_partida->tabla.'_descuento';

        $keys = new stdClass();
        $keys->cantidad = $key_cantidad;
        $keys->descripcion = $key_descripcion;
        $keys->valor_unitario = $key_valor_unitario;
        $keys->importe = $key_importe;
        $keys->descuento = $key_descuento;

        return $keys;

    }

    private function r_fc_cuenta_predial(string $key_filtro_id, _cuenta_predial $modelo_predial, int $registro_id): array|stdClass
    {
        // Validar que el ID del registro sea mayor a 0
        if ($registro_id <= 0) {
            return $this->error->error(
                mensaje: "Error: el registro_id debe ser mayor a 0",
                data: $registro_id,
                es_final: true
            );
        }

        $key_filtro_id = trim($key_filtro_id);
        if($key_filtro_id === ''){
            return $this->error->error(
                mensaje: "Error: el $key_filtro_id esta vacio", data: $registro_id, es_final: true
            );
        }

        // Consultar la cuenta predial asociada al registro
        $r_fc_cuenta_predial = $modelo_predial->filtro_and(filtro: array($key_filtro_id => $registro_id));

        // Verificar si hubo un error en la consulta
        if (errores::$error) {
            return $this->error->error(
                mensaje: "Error al obtener cuenta predial",
                data: $r_fc_cuenta_predial
            );
        }

        // Validar que haya exactamente un registro de cuenta predial
        if ($r_fc_cuenta_predial->n_registros === 0) {
            return $this->error->error(
                mensaje: "Error: no existe cuenta predial asignada",
                data: $r_fc_cuenta_predial,
                es_final: true
            );
        }

        if ($r_fc_cuenta_predial->n_registros > 1) {
            return $this->error->error(
                mensaje: "Error de integridad: más de una cuenta predial asignada",
                data: $r_fc_cuenta_predial,
                es_final: true
            );
        }

        // Retornar la cuenta predial encontrada
        return $r_fc_cuenta_predial;
    }


}
