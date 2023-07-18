<?php
namespace gamboamartin\facturacion\controllers;

use gamboamartin\comercial\models\com_tmp_prod_cs;
use gamboamartin\errores\errores;
use gamboamartin\system\html_controler;
use gamboamartin\template\html;
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

    private function thead_impuesto(): string
    {
        return "<tr>
    <th>Tipo Impuesto</th>
    <th>Tipo Factor</th>
    <th>Factor</th>
    <th>Importe</th>
    <th>Elimina</th>
</tr>";
    }

    /**
     * Integra los datos de un producto para html views
     * @param html_controler $html_controler
     * @param PDO $link Conexion a la base de datos
     * @param string $name_entidad_partida Nombre de la entidad partida
     * @param array $partida Registro de tipo partida
     * @return string
     */
    final public function data_producto(html_controler $html_controler, PDO $link, string $name_entidad_partida, array $partida): string
    {


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

    public function data_impuesto(string $button_del, array $impuesto, string $key): string
    {
        return "<tr style='font-size: 12px;'>
                    <td>$impuesto[cat_sat_tipo_impuesto_descripcion]</td>
                    <td>$impuesto[cat_sat_tipo_factor_descripcion]</td>
                    <td>$impuesto[cat_sat_factor_factor]</td>
                    <td>$impuesto[$key]</td>
                    <td>$button_del</td>
                    </tr>";
    }

    private function inputs_producto(html_controler $html_controler, string $key_cantidad, string $key_valor_unitario,
                                     array $partida){
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
     * @return string
     */
    private function integra_key(string $campo, string $name_entidad_partida): string
    {

        return $name_entidad_partida.'_'.$campo;

    }


    private function keys_producto(string $name_entidad_partida): stdClass
    {

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

    public function tr_impuestos_html(string $impuesto_html, string $tag_tipo_impuesto){
        $t_head_impuesto = $this->thead_impuesto();
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar t head', data: $t_head_impuesto);
        }
        $html = "
            <tr>
                <td class='nested' colspan='9' style='padding: 0;'>
                    <table class='table table-striped' style='font-size: 14px;'>
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

        return $html;
    }

    private function tr_producto(string $input_cantidad, string $input_valor_unitario, string $key_descuento,
                                 string $key_importe, array $partida): string
    {
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
}
