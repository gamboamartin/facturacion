<?php
namespace gamboamartin\facturacion\controllers;

use gamboamartin\comercial\models\com_tmp_prod_cs;
use gamboamartin\errores\errores;
use PDO;

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

    public function data_producto(PDO $link, array $partida): string
    {
        $filtro['com_producto.id'] = $partida['com_producto_id'];
        $existe_tmp = (new com_tmp_prod_cs(link: $link))->existe(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar si existe existe_tmp', data: $existe_tmp);
        }
        if($existe_tmp){
            $r_com_tmp_prod_cs = (new com_tmp_prod_cs(link: $link))->filtro_and(filtro: $filtro);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al obtener producto', data: $r_com_tmp_prod_cs);
            }
            $partida['cat_sat_producto_codigo'] = $r_com_tmp_prod_cs->registros[0]['com_tmp_prod_cs_cat_sat_producto'];
        }

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
                <td>$partida[elimina_bd]</td>
            </tr>";
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
}
