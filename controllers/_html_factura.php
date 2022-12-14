<?php
namespace gamboamartin\facturacion\controllers;

use gamboamartin\errores\errores;

class _html_factura{
    private errores $error;
    public function __construct(){
        $this->error = new errores();
    }
    public function thead_producto(): string
    {
        return "<thead style='font-size: 14px; vertical-align: middle; background-color: #74569E; color: #ffffff'>
                    <tr>
                        <th>Clav Prod. Serv.</th>
                        <th>No Identificación</th>
                        <th>Cantidad</th>
                        <th>Unidad</th>
                        <th>Valor Unitario</th>
                        <th>Importe</th>
                        <th>Descuento</th>
                        <th>Objeto Impuesto</th>
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
</tr>";
    }

    public function data_producto(array $partida): string
    {

        return "
            <tr>
                <td>$partida[cat_sat_producto_codigo]</td>
                <td>$partida[com_producto_codigo]</td>
                <td>$partida[fc_partida_cantidad]</td>
                <td>$partida[cat_sat_unidad_descripcion]</td>
                <td>$partida[fc_partida_valor_unitario]</td>
                <td>$partida[fc_partida_importe]</td>
                <td>$partida[fc_partida_descuento]</td>
                <td>$partida[cat_sat_obj_imp_descripcion]</td>
            </tr>";
    }

    public function data_impuesto(array $impuesto): string
    {
        return "<tr style='font-size: 12px;'>
                    <td>$impuesto[cat_sat_tipo_impuesto_descripcion]</td>
                    <td>$impuesto[cat_sat_tipo_factor_descripcion]</td>
                    <td>$impuesto[cat_sat_factor_factor]</td>
                    <td>$impuesto[fc_traslado_importe]</td>
                    </tr>";
    }

    public function tr_impuestos_html(string $impuesto_traslado_html, string $tag_tipo_impuesto){
        $t_head_impuesto = $this->thead_impuesto();
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar t head', data: $t_head_impuesto);
        }
        $html = "
            <tr>
                <td class='nested' colspan='8' style='padding: 0;'>
                    <table class='table table-striped' style='font-size: 14px; vertical-align: middle; margin-bottom: 0;'>
                        <thead >
                            <tr style='background-color: #dfe7f6; color: #2c58a0;'>
                                <th colspan='4'>$tag_tipo_impuesto</th>
                            </tr>
                            $t_head_impuesto
                        </thead>
                        <tbody>
                            $impuesto_traslado_html
                        </tbody>
                    </table>
                </td>
            </tr>";

        return $html;
    }
}
