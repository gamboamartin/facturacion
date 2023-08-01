<?php
namespace gamboamartin\facturacion\controllers;

use gamboamartin\comercial\models\com_tmp_prod_cs;
use gamboamartin\errores\errores;
use gamboamartin\system\html_controler;
use gamboamartin\template\html;
use gamboamartin\validacion\validacion;
use PDO;
use stdClass;

class _html_factura{
    private errores $error;
    public function __construct(){
        $this->error = new errores();
    }
    public function thead_producto(): string
    {
        return "<thead class='head-principal' style='font-size: 14px; vertical-align: middle; background-color: #74569E; color: #ffffff'>
                    <tr>
                        <th>Clav Prod. Serv.</th>
                        <th>No Identificación</th>
                        <th>Cantidad</th>
                        <th>Unidad</th>
                        <th>Valor Unitario</th>
                        <th>Importe</th>
                        <th>Descuento</th>
                        <th>Objeto Impuesto</th>
                        <th>Acción</th>
                    </tr>
            </thead>";
    }

    /**
     * Genera el encabezado de impuestos a mostrar en las partidas
     * @return string
     * @version 11.7.0
     */
    private function thead_impuesto(): string
    {
        return "<tr><th>Tipo Impuesto</th><th>Tipo Factor</th><th>Factor</th><th>Importe</th><th>Elimina</th></tr>";

    }

    /**
     * Integra los datos de un producto para html views
     * @param html_controler $html_controler
     * @param PDO $link Conexion a la base de datos
     * @param string $name_entidad_partida Nombre de la entidad partida
     * @param array $partida Registro de tipo partida
     * @return string|array
     * @version 10.178.8
     *
     */
    final public function data_producto(html_controler $html_controler, PDO $link, string $name_entidad_partida,
                                        array $partida): string|array
    {

        $name_entidad_partida = trim($name_entidad_partida);
        if($name_entidad_partida === ''){
            return $this->error->error(mensaje: 'Error name_entidad_partida esta vacia', data: $name_entidad_partida);
        }
        $valida = (new _partidas_html())->valida_partida_html(partida: $partida);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar partida', data: $valida);
        }

        $partida = (new _tmps())->com_tmp_prod_cs(link: $link,partida:  $partida);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener partida tmp', data: $partida);
        }

        $keys = $this->keys_producto(name_entidad_partida: $name_entidad_partida);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener keys', data: $keys);
        }

        $inputs = $this->inputs_producto(html_controler: $html_controler,key_cantidad:  $keys->key_cantidad,
            key_valor_unitario:  $keys->key_valor_unitario,partida:  $partida);

        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar inputs', data: $inputs);
        }


        $tr_producto = $this->tr_producto(input_cantidad: $inputs->input_cantidad,
            input_valor_unitario:  $inputs->input_valor_unitario, key_descuento:  $keys->key_descuento,
            key_importe: $keys->key_importe,partida:  $partida);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar tr_producto', data: $tr_producto);
        }


        return $tr_producto;
    }

    /**
     * Integra un tr con los datos del impuesto
     * @param string $button_del Boton de eliminacion de partida
     * @param array $impuesto Datos del impuesto
     * @param string $key Key del tipo de impuesto
     * @return string|array
     * @version 11.2.0
     */
    final public function data_impuesto(string $button_del, array $impuesto, string $key): string|array
    {
        $key = trim($key);
        if($key === ''){
            return $this->error->error(mensaje: 'Error key esta vacio', data: $key);
        }
        $button_del = trim($button_del);
        if($button_del === ''){
            return $this->error->error(mensaje: 'Error button_del esta vacio', data: $button_del);
        }
        $keys = array('cat_sat_tipo_impuesto_descripcion','cat_sat_tipo_factor_descripcion',
            'cat_sat_factor_factor',$key);
        $valida = (new validacion())->valida_existencia_keys(keys: $keys,registro:  $impuesto);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar impuesto', data: $valida);
        }
        return "<tr style='font-size: 12px;'>
                    <td>$impuesto[cat_sat_tipo_impuesto_descripcion]</td>
                    <td>$impuesto[cat_sat_tipo_factor_descripcion]</td>
                    <td>$impuesto[cat_sat_factor_factor]</td>
                    <td>$impuesto[$key]</td>
                    <td>$button_del</td>
                    </tr>";
    }

    /**
     *
     * Genera los inputs de un producto para su alta
     * @param html_controler $html_controler Html
     * @param string $key_cantidad Key de partida
     * @param string $key_valor_unitario Key de valor unitario
     * @param array $partida Partida datos
     * @return array|stdClass
     * @version 10.172.6
     */
    private function inputs_producto(html_controler $html_controler, string $key_cantidad, string $key_valor_unitario,
                                     array $partida): array|stdClass
    {
        $key_cantidad = trim($key_cantidad);
        if($key_cantidad === ''){
            return $this->error->error(mensaje: 'Error key_cantidad esta vacia', data: $key_cantidad);
        }
        $key_valor_unitario = trim($key_valor_unitario);
        if($key_valor_unitario === ''){
            return $this->error->error(mensaje: 'Error key_valor_unitario esta vacia', data: $key_valor_unitario);
        }

        $keys = array($key_cantidad, $key_valor_unitario);
        foreach ($keys as $key){
            if(!isset($partida[$key])){
                $partida[$key] = 0;
            }
        }

        $input_cantidad = $html_controler->input_monto(cols: 12, row_upd: new stdClass(), value_vacio: false,
            con_label: false, name: 'cantidad', place_holder: 'Cantidad', value: $partida[$key_cantidad]);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar input', data: $input_cantidad);
        }

        $input_valor_unitario = $html_controler->input_monto(cols: 12, row_upd: new stdClass(), value_vacio: false,
            con_label: false, name: 'valor_unitario', place_holder: 'Valor Unitario',
            value: $partida[$key_valor_unitario]);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar input', data: $input_cantidad);
        }

        $data = new stdClass();
        $data->input_cantidad = $input_cantidad;
        $data->input_valor_unitario = $input_valor_unitario;

        return $data;

    }

    /**
     * Genera un name key para producto
     * @param string $campo Campo a integrar
     * @param string $name_entidad_partida Nombre de la entidad
     * @return string|array
     * @version 10.166.6
     */
    private function integra_key(string $campo, string $name_entidad_partida): string|array
    {
        $name_entidad_partida = trim($name_entidad_partida);
        if($name_entidad_partida === ''){
            return $this->error->error(mensaje: 'Error name_entidad_partida esta vacia', data: $name_entidad_partida);
        }
        $campo = trim($campo);
        if($campo === ''){
            return $this->error->error(mensaje: 'Error campo esta vacia', data: $campo);
        }
        return $name_entidad_partida.'_'.$campo;

    }


    /**
     * Integra un key del catalogo de productos para frontend
     * @param string $name_entidad_partida Nombre de la entidad base
     * @return stdClass|array
     * @version 10.168.6
     */
    private function keys_producto(string $name_entidad_partida): stdClass|array
    {
        $name_entidad_partida = trim($name_entidad_partida);
        if($name_entidad_partida === ''){
            return $this->error->error(mensaje: 'Error name_entidad_partida esta vacia', data: $name_entidad_partida);
        }

        $key_cantidad = $this->integra_key(campo: 'cantidad',name_entidad_partida:  $name_entidad_partida);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar key_cantidad', data: $key_cantidad);
        }
        $key_valor_unitario = $this->integra_key(campo: 'valor_unitario',name_entidad_partida:  $name_entidad_partida);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar key_valor_unitario', data: $key_valor_unitario);
        }
        $key_importe = $this->integra_key(campo: 'sub_total',name_entidad_partida:  $name_entidad_partida);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar key_valor_unitario', data: $key_importe);
        }
        $key_descuento = $this->integra_key(campo: 'descuento',name_entidad_partida:  $name_entidad_partida);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar key_descuento', data: $key_descuento);
        }


        $data = new stdClass();
        $data->key_cantidad = $key_cantidad;
        $data->key_valor_unitario = $key_valor_unitario;
        $data->key_importe = $key_importe;
        $data->key_descuento = $key_descuento;

        return $data;
    }

    /**
     * Integra los impuesto via html
     * @param string $impuesto_html Html de datos de impuesto
     * @param string $tag_tipo_impuesto Tag a mostrar
     * @return array|string
     * @version 11.9.0
     */
    final public function tr_impuestos_html(string $impuesto_html, string $tag_tipo_impuesto): array|string
    {

        $tag_tipo_impuesto = trim($tag_tipo_impuesto);
        if($tag_tipo_impuesto === ''){
            return $this->error->error(mensaje: 'Error tag_tipo_impuesto esta vacio', data: $tag_tipo_impuesto);
        }

        $t_head_impuesto = $this->thead_impuesto();
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar t head', data: $t_head_impuesto);
        }
        return "
            <tr>
                <td class='nested' colspan='9'>
                    <table class='table table-striped'>
                        <thead >
                            <tr>
                                <th colspan='5'>$tag_tipo_impuesto</th>
                            </tr>
                            $t_head_impuesto
                        </thead>
                        <tbody>
                            $impuesto_html
                        </tbody>
                    </table>
                </td>
            </tr>";
    }

    /**
     * Integra un tr de producto
     * @param string $input_cantidad Input de cantidad
     * @param string $input_valor_unitario Input de valor unitario
     * @param string $key_descuento Key de descuento basado en partida
     * @param string $key_importe Key importe basado en partida
     * @param array $partida Partida con datos
     * @return string|array
     * @version 10.178.8
     */
    private function tr_producto(string $input_cantidad, string $input_valor_unitario, string $key_descuento,
                                 string $key_importe, array $partida): string|array
    {

        $valida = $this->valida_tr(key_descuento: $key_descuento,key_importe:  $key_importe,
            input_cantidad:  $input_cantidad,input_valor_unitario:  $input_valor_unitario,partida:  $partida);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar partida', data: $valida);
        }

        return "<tr>
                <td>$partida[cat_sat_producto_codigo]</td>
                <td>$partida[com_producto_codigo]</td>
                <td>$input_cantidad</td>
                <td>$partida[cat_sat_unidad_descripcion]</td>
                <td>$input_valor_unitario</td>
                <td>$partida[$key_importe]</td>
                <td>$partida[$key_descuento]</td>
                <td>$partida[cat_sat_obj_imp_descripcion]</td>
                <td>$partida[elimina_bd]</td>
            </tr>";
    }

    /**
     * Valida los elementos de un tr para partida
     * @param string $key_descuento Key de partida descuento
     * @param string $key_importe Key de partida importe
     * @param string $input_cantidad Input de partida cantidad
     * @param string $input_valor_unitario Input de valor unitario de partida
     * @param array $partida Datos de la partida
     * @return array|true
     * @version 10.177.8
     */
    private function valida_tr(string $key_descuento, string $key_importe, string $input_cantidad,
                               string $input_valor_unitario, array $partida): bool|array
    {
        $key_descuento = trim($key_descuento);
        if($key_descuento === ''){
            return $this->error->error(mensaje: 'Error key_descuento esta vacio', data: $key_descuento);
        }
        $key_importe = trim($key_importe);
        if($key_importe === ''){
            return $this->error->error(mensaje: 'Error key_importe esta vacio', data: $key_importe);
        }
        $input_cantidad = trim($input_cantidad);
        if($input_cantidad === ''){
            return $this->error->error(mensaje: 'Error input_cantidad esta vacio', data: $input_cantidad);
        }
        $input_valor_unitario = trim($input_valor_unitario);
        if($input_valor_unitario === ''){
            return $this->error->error(mensaje: 'Error input_valor_unitario esta vacio', data: $input_valor_unitario);
        }


        $keys = array('cat_sat_producto_codigo','com_producto_codigo','cat_sat_unidad_descripcion',$key_importe,
            $key_descuento,'cat_sat_obj_imp_descripcion','elimina_bd');

        $valida = (new validacion())->valida_existencia_keys(keys: $keys,registro:  $partida);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar partida', data: $valida);
        }
        return true;
    }
}
