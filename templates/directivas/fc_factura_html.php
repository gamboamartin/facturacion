<?php
namespace html;

use config\generales;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\controllers\controlador_fc__partida;
use gamboamartin\facturacion\controllers\controlador_fc_factura;
use gamboamartin\system\html_controler;

use models\base\limpieza;
use models\fc_factura;
use PDO;
use stdClass;


class fc_factura_html extends html_controler {

    /**
     * Asigna diferentes inputs de paquetes cat_sat, comercial, dirección postal, organigrama
     * e inputs declarados localmente.
     * @param controlador_fc_factura $controler Controlador en ejecución
     * @param stdClass $inputs Inputs precargados
     * @return array|stdClass
     */
    private function asigna_inputs(controlador_fc_factura $controler, stdClass $inputs): array|stdClass
    {
        $controler->inputs->select = new stdClass();
        $controler->inputs->select->fc_csd_id = $inputs->selects->fc_csd_id;
        $controler->inputs->select->cat_sat_forma_pago_id = $inputs->selects->cat_sat_forma_pago_id;
        $controler->inputs->select->cat_sat_metodo_pago_id = $inputs->selects->cat_sat_metodo_pago_id;
        $controler->inputs->select->cat_sat_moneda_id = $inputs->selects->cat_sat_moneda_id;
        $controler->inputs->select->com_tipo_cambio_id = $inputs->selects->com_tipo_cambio_id;
        $controler->inputs->select->cat_sat_tipo_de_comprobante_id = $inputs->selects->cat_sat_tipo_de_comprobante_id;
        $controler->inputs->select->dp_calle_pertenece_id = $inputs->selects->dp_calle_pertenece_id;
        $controler->inputs->select->cat_sat_regimen_fiscal_id = $inputs->selects->cat_sat_regimen_fiscal_id;
        $controler->inputs->select->com_sucursal_id = $inputs->selects->com_sucursal_id;
        $controler->inputs->select->cat_sat_uso_cfdi_id = $inputs->selects->cat_sat_uso_cfdi_id;

        $controler->inputs->select->dp_pais_id = $inputs->selects->dp_pais_id;
        $controler->inputs->select->dp_estado_id = $inputs->selects->dp_estado_id;
        $controler->inputs->select->dp_municipio_id = $inputs->selects->dp_municipio_id;
        $controler->inputs->select->dp_cp_id = $inputs->selects->dp_cp_id;
        $controler->inputs->select->dp_colonia_postal_id = $inputs->selects->dp_colonia_postal_id;
        $controler->inputs->serie = $inputs->texts->serie;
        $controler->inputs->subtotal = $inputs->texts->subtotal;
        $controler->inputs->descuento = $inputs->texts->descuento;
        $controler->inputs->impuestos_trasladados = $inputs->texts->impuestos_trasladados;
        $controler->inputs->impuestos_retenidos = $inputs->texts->impuestos_retenidos;
        $controler->inputs->total = $inputs->texts->total;
        $controler->inputs->folio = $inputs->texts->folio;
        $controler->inputs->fecha = $inputs->texts->fecha;
        $controler->inputs->select->exportacion = $inputs->selects->exportacion;

        return $controler->inputs;
    }

    private function asigna_inputs_fc_partida(controlador_fc_factura $controler, stdClass $inputs): array|stdClass
    {
        $controler->inputs->select = new stdClass();
        $controler->inputs->select->fc_factura_id = $inputs->selects->fc_factura_id;
        $controler->inputs->select->com_producto_id = $inputs->selects->com_producto_id;

        $controler->inputs->cantidad = $inputs->texts->cantidad;
        $controler->inputs->valor_unitario = $inputs->texts->valor_unitario;
        $controler->inputs->descuento = $inputs->texts->descuento;
        $controler->inputs->codigo = $inputs->texts->codigo;

        return $controler->inputs;
    }


    public function genera_inputs_alta(controlador_fc_factura $controler, PDO $link): array|stdClass
    {
        $inputs = $this->init_alta(link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar inputs',data:  $inputs);

        }
        $inputs_asignados = $this->asigna_inputs(controler:$controler, inputs: $inputs);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al asignar inputs',data:  $inputs_asignados);
        }

        return $inputs_asignados;
    }

    private function genera_inputs_modifica(controlador_fc_factura $controler, PDO $link): array|stdClass
    {
        $inputs = $this->init_modifica(link: $link, row_upd: $controler->row_upd);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar inputs',data:  $inputs);
        }

        $inputs_asignados = $this->asigna_inputs(controler:$controler, inputs: $inputs);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al asignar inputs',data:  $inputs_asignados);
        }

        return $inputs_asignados;
    }

    public function genera_inputs_fc_partida(controlador_fc_factura $controler, PDO $link): array|stdClass
    {
        $inputs = $this->init_alta_fc_partida(link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar inputs',data:  $inputs);

        }
        $inputs_asignados = $this->asigna_inputs_fc_partida(controler:$controler, inputs: $inputs);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al asignar inputs',data:  $inputs_asignados);
        }

        return $inputs_asignados;
    }

    public function genera_inputs_fc_partida_modifica(controlador_fc_factura $controler, PDO $link): array|stdClass
    {
        $inputs = $this->init_modifica_fc_partida(link: $link, row_upd: $controler->row_upd);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar inputs',data:  $inputs);

        }
        $inputs_asignados = $this->asigna_inputs_fc_partida(controler:$controler, inputs: $inputs);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al asignar inputs',data:  $inputs_asignados);
        }

        return $inputs_asignados;
    }

    private function init_alta_fc_partida(PDO $link): array|stdClass
    {
        $selects = $this->selects_alta_fc_partida(link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar selects',data:  $selects);
        }

        $texts = $this->texts_alta_fc_partida(row_upd: new stdClass(), value_vacio: true);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar texts',data:  $texts);
        }

        $alta_inputs = new stdClass();
        $alta_inputs->selects = $selects;
        $alta_inputs->texts = $texts;

        return $alta_inputs;
    }

    private function init_modifica_fc_partida(PDO $link, stdClass $row_upd): array|stdClass
    {
        $selects = $this->selects_modifica_fc_partida(link: $link, row_upd: $row_upd);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar selects',data:  $selects);
        }

        $texts = $this->texts_alta_fc_partida(row_upd: $row_upd, value_vacio: false);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar texts',data:  $texts);
        }

        $alta_inputs = new stdClass();
        $alta_inputs->selects = $selects;
        $alta_inputs->texts = $texts;

        return $alta_inputs;
    }

    private function texts_alta_fc_partida(stdClass $row_upd, bool $value_vacio, stdClass $params = new stdClass()): array|stdClass
    {
        $texts = new stdClass();

        $cols_codigo = $params->codigo->cols ?? 4   ;
        $disabled_codigo = $params->codigo->disabled ?? false;

        $in_codigo = $this->input_codigo(cols: $cols_codigo,row_upd:  $row_upd,value_vacio:  $value_vacio,
            disabled: $disabled_codigo);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar input',data:  $in_codigo);
        }
        $texts->codigo = $in_codigo;

        if(!isset($row_upd->cantidad)){
            $row_upd->cantidad = 0;
        }
        if(!isset($row_upd->descuento)){
            $row_upd->descuento = 0;
        }
        if(!isset($row_upd->valor_unitario)){
            $row_upd->valor_unitario = 0;
        }

        if($row_upd->cantidad === 0 && isset($row_upd->fc_partida_cantidad)){
            $row_upd->cantidad = $row_upd->fc_partida_cantidad;
        }
        if($row_upd->descuento === 0 && isset($row_upd->fc_partida_descuento)){
            $row_upd->descuento = $row_upd->fc_partida_descuento;
        }
        if($row_upd->valor_unitario === 0 && isset($row_upd->fc_partida_valor_unitario)){
            $row_upd->valor_unitario = $row_upd->fc_partida_valor_unitario;
        }
        $in_cantidad= $this->input_cantidad(cols: 4,row_upd:  $row_upd,value_vacio:  false);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar input',data:  $in_cantidad);
        }
        $texts->cantidad = $in_cantidad;

        $in_descuento= $this->input_descuento(cols: 4,row_upd:  $row_upd,value_vacio:  false);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar input',data:  $in_descuento);
        }
        $texts->descuento = $in_descuento;

        $in_valor_unitario= $this->input_valor_unitario(cols: 4,row_upd:  $row_upd,value_vacio:  false);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar input',data:  $in_valor_unitario);
        }
        $texts->valor_unitario = $in_valor_unitario;


        return $texts;
    }

    public function input_cantidad(int $cols, stdClass $row_upd, bool $value_vacio, bool $disabled = false): array|string
    {
        $valida = $this->directivas->valida_cols(cols: $cols);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar columnas', data: $valida);
        }

        $html =$this->directivas->input_text_required(disable: $disabled,name: 'cantidad',place_holder: 'Cantidad',
            row_upd: $row_upd, value_vacio: $value_vacio);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar input', data: $html);
        }

        $div = $this->directivas->html->div_group(cols: $cols,html:  $html);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integrar div', data: $div);
        }

        return $div;
    }

    public function input_valor_unitario(int $cols, stdClass $row_upd, bool $value_vacio, bool $disabled = false): array|string
    {
        $valida = $this->directivas->valida_cols(cols: $cols);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar columnas', data: $valida);
        }

        $html =$this->directivas->input_text_required(disable: $disabled,name: 'valor_unitario',place_holder: 'Valor Unitario',
            row_upd: $row_upd, value_vacio: $value_vacio);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar input', data: $html);
        }

        $div = $this->directivas->html->div_group(cols: $cols,html:  $html);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integrar div', data: $div);
        }

        return $div;
    }

    private function selects_alta_fc_partida(PDO $link): array|stdClass
    {
        $selects = new stdClass();

        $select = (new com_producto_html(html:$this->html_base))->select_com_producto_id(
            cols: 12, con_registros:true, id_selected:-1,link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select',data:  $select);
        }
        $selects->com_producto_id = $select;

        $select = (new fc_factura_html(html:$this->html_base))->select_fc_factura_id(
            cols: 12, con_registros:true, id_selected:-1,link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select',data:  $select);
        }
        $selects->fc_factura_id = $select;

        return $selects;
    }

    private function selects_modifica_fc_partida(PDO $link, stdClass $row_upd): array|stdClass
    {
        $selects = new stdClass();

        $select = (new com_producto_html(html:$this->html_base))->select_com_producto_id(
            cols: 12, con_registros:true, id_selected:$row_upd->com_producto_id,link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select',data:  $select);
        }
        $selects->com_producto_id = $select;

        $select = (new fc_factura_html(html:$this->html_base))->select_fc_factura_id(
            cols: 12, con_registros:true, id_selected:$row_upd->fc_factura_id,link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select',data:  $select);
        }
        $selects->fc_factura_id = $select;

        return $selects;
    }

    /** Inicializa los datos para una accion de tipo alta bd
     * @param PDO $link Conexion a la base de datos
     * @return array|stdClass
     */
    private function init_alta(PDO $link): array|stdClass
    {
        $selects = $this->selects_alta(link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar selects',data:  $selects);
        }

        $row_upd = new stdClass();
        $row_upd->exportacion = -1;
        $in_exportacion = $this->select_exportacion(cols: 6,row_upd: $row_upd);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar input',data:  $in_exportacion);
        }
        $selects->exportacion = $in_exportacion;

        $texts = $this->texts_alta(row_upd: new stdClass(), value_vacio: true);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar texts',data:  $texts);
        }

        $alta_inputs = new stdClass();
        $alta_inputs->selects = $selects;
        $alta_inputs->texts = $texts;

        return $alta_inputs;
    }

    /** Inicializa los datos para una accion de tipo modifica bd
     * @param PDO $link Conexion a la base de datos
     * @return array|stdClass
     */
    private function init_modifica(PDO $link, stdClass $row_upd): array|stdClass
    {

        $selects = $this->selects_modifica(link: $link, row_upd: $row_upd);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar selects',data:  $selects);
        }

        $in_exportacion = $this->select_exportacion(cols: 6,row_upd: $row_upd);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar input',data:  $in_exportacion);
        }
        $selects->exportacion = $in_exportacion;

        $texts = $this->texts_alta(row_upd: $row_upd, value_vacio: false);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar texts',data:  $texts);
        }

        $alta_inputs = new stdClass();
        $alta_inputs->selects = $selects;
        $alta_inputs->texts = $texts;

        return $alta_inputs;
    }

    public function inputs_fc_factura(controlador_fc_factura $controlador): array|stdClass
    {
        $init = (new limpieza())->init_modifica_fc_factura(controler: $controlador);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al inicializa datos',data:  $init);
        }

        $inputs = $this->genera_inputs_modifica(controler: $controlador, link: $controlador->link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar inputs',data:  $inputs);
        }
        return $inputs;
    }

    public function input_version(int $cols, stdClass $row_upd, bool $value_vacio, bool $disabled = false): array|string
    {
        $valida = $this->directivas->valida_cols(cols: $cols);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar columnas', data: $valida);
        }

        $html =$this->directivas->input_text_required(disable: $disabled,name: 'version',place_holder: 'Version',
            row_upd: $row_upd, value_vacio: $value_vacio);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar input', data: $html);
        }

        $div = $this->directivas->html->div_group(cols: $cols,html:  $html);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integrar div', data: $div);
        }

        return $div;
    }

    public function input_serie(int $cols, stdClass $row_upd, bool $value_vacio, bool $disabled = false): array|string
    {
        $valida = $this->directivas->valida_cols(cols: $cols);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar columnas', data: $valida);
        }

        $html =$this->directivas->input_text_required(disable: $disabled,name: 'serie',place_holder: 'Serie',
            row_upd: $row_upd, value_vacio: $value_vacio);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar input', data: $html);
        }

        $div = $this->directivas->html->div_group(cols: $cols,html:  $html);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integrar div', data: $div);
        }

        return $div;
    }

    public function input_subtotal(int $cols, stdClass $row_upd, bool $value_vacio, bool $disabled = false): array|string
    {
        $valida = $this->directivas->valida_cols(cols: $cols);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar columnas', data: $valida);
        }

        $html =$this->directivas->input_text_required(disable: $disabled,name: 'subtotal',place_holder: 'Subtotal',
            row_upd: $row_upd, value_vacio: $value_vacio);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar input', data: $html);
        }

        $div = $this->directivas->html->div_group(cols: $cols,html:  $html);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integrar div', data: $div);
        }

        return $div;
    }

    public function input_descuento(int $cols, stdClass $row_upd, bool $value_vacio, bool $disabled = false): array|string
    {
        $valida = $this->directivas->valida_cols(cols: $cols);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar columnas', data: $valida);
        }

        $html =$this->directivas->input_text_required(disable: $disabled,name: 'descuento',place_holder: 'Descuento',
            row_upd: $row_upd, value_vacio: $value_vacio);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar input', data: $html);
        }

        $div = $this->directivas->html->div_group(cols: $cols,html:  $html);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integrar div', data: $div);
        }

        return $div;
    }

    public function input_impuestos_trasladados(int $cols, stdClass $row_upd, bool $value_vacio, bool $disabled = false): array|string
    {
        $valida = $this->directivas->valida_cols(cols: $cols);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar columnas', data: $valida);
        }

        $html =$this->directivas->input_text_required(disable: $disabled,name: 'impuestos_trasladados',
            place_holder: 'Imp Trasladados',
            row_upd: $row_upd, value_vacio: $value_vacio);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar input', data: $html);
        }

        $div = $this->directivas->html->div_group(cols: $cols,html:  $html);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integrar div', data: $div);
        }

        return $div;
    }

    public function input_impuestos_retenidos(int $cols, stdClass $row_upd, bool $value_vacio, bool $disabled = false): array|string
    {
        $valida = $this->directivas->valida_cols(cols: $cols);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar columnas', data: $valida);
        }

        $html =$this->directivas->input_text_required(disable: $disabled,name: 'impuestos_retenidos',
            place_holder: 'Imp Retenidos',
            row_upd: $row_upd, value_vacio: $value_vacio);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar input', data: $html);
        }

        $div = $this->directivas->html->div_group(cols: $cols,html:  $html);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integrar div', data: $div);
        }

        return $div;
    }

    public function input_total(int $cols, stdClass $row_upd, bool $value_vacio, bool $disabled = false): array|string
    {
        $valida = $this->directivas->valida_cols(cols: $cols);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar columnas', data: $valida);
        }

        $html =$this->directivas->input_text_required(disable: $disabled,name: 'total',place_holder: 'Total',
            row_upd: $row_upd, value_vacio: $value_vacio);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar input', data: $html);
        }

        $div = $this->directivas->html->div_group(cols: $cols,html:  $html);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integrar div', data: $div);
        }

        return $div;
    }

    public function input_folio(int $cols, stdClass $row_upd, bool $value_vacio, bool $disabled = false): array|string
    {
        $valida = $this->directivas->valida_cols(cols: $cols);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar columnas', data: $valida);
        }

        $html =$this->directivas->input_text_required(disable: $disabled,name: 'folio',place_holder: 'Folio',
            row_upd: $row_upd, value_vacio: $value_vacio);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar input', data: $html);
        }

        $div = $this->directivas->html->div_group(cols: $cols,html:  $html);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integrar div', data: $div);
        }

        return $div;
    }

    public function input_fecha(int $cols, stdClass $row_upd, bool $value_vacio, bool $disabled = false): array|string
    {
        $valida = $this->directivas->valida_cols(cols: $cols);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar columnas', data: $valida);
        }

        $html =$this->directivas->fecha_required(disable: $disabled,name: 'fecha',place_holder: 'Fecha',
            row_upd: $row_upd, value_vacio: $value_vacio);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar input', data: $html);
        }

        $div = $this->directivas->html->div_group(cols: $cols,html:  $html);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integrar div', data: $div);
        }

        return $div;
    }

    public function input_exportacion(int $cols, stdClass $row_upd, bool $value_vacio, bool $disabled = false): array|string
    {
        $valida = $this->directivas->valida_cols(cols: $cols);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar columnas', data: $valida);
        }

        $html =$this->directivas->input_text_required(disable: $disabled,name: 'exportacion',place_holder: 'Exportacion',
            row_upd: $row_upd, value_vacio: $value_vacio);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar input', data: $html);
        }

        $div = $this->directivas->html->div_group(cols: $cols,html:  $html);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al integrar div', data: $div);
        }

        return $div;
    }

    /**
     * Genera los selects a mostrar con sus respectivos parámetros
     * @param PDO $link Conexion a la base de datos
     * @return array|stdClass
     */
    private function selects_alta(PDO $link): array|stdClass
    {
        $selects = new stdClass();

        $select = (new cat_sat_metodo_pago_html(html:$this->html_base))->select_cat_sat_metodo_pago_id(
            cols: 4, con_registros:true, id_selected:-1,link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select',data:  $select);
        }
        $selects->cat_sat_metodo_pago_id = $select;

        $select = (new cat_sat_moneda_html(html:$this->html_base))->select_cat_sat_moneda_id(
            cols: 4, con_registros:true, id_selected:-1,link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select',data:  $select);
        }
        $selects->cat_sat_moneda_id = $select;

        $generales = new generales();
        $cat_sat_tipo_de_comprobante_id = -1;
        if(isset($generales->tipo_de_comprobante_id)){
            $cat_sat_tipo_de_comprobante_id = $generales->tipo_de_comprobante_id;
        }
        $select = (new cat_sat_tipo_de_comprobante_html(html:$this->html_base))->select_cat_sat_tipo_de_comprobante_id(
            cols: 4, con_registros:true, id_selected:$cat_sat_tipo_de_comprobante_id,link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select',data:  $select);
        }
        $selects->cat_sat_tipo_de_comprobante_id = $select;

        $select = (new com_sucursal_html(html:$this->html_base))->select_com_sucursal_id(
            cols: 12, con_registros:true, id_selected:-1,link: $link, label: 'Cliente');
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select',data:  $select);
        }
        $selects->com_sucursal_id = $select;

        $select = (new dp_calle_pertenece_html(html:$this->html_base))->select_dp_calle_pertenece_id(
            cols: 6, con_registros:true, id_selected:-1,link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select',data:  $select);
        }
        $selects->dp_calle_pertenece_id = $select;

        $select = (new fc_csd_html(html:$this->html_base))->select_fc_csd_id(
            cols: 12, con_registros:true, id_selected:-1,link: $link, label: 'Empresa');
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select',data:  $select);
        }
        $selects->fc_csd_id = $select;

        $select = (new cat_sat_forma_pago_html(html:$this->html_base))->select_cat_sat_forma_pago_id(
            cols: 4, con_registros:true, id_selected:-1,link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select',data:  $select);
        }
        $selects->cat_sat_forma_pago_id = $select;

        $select = (new com_tipo_cambio_html(html:$this->html_base))->select_com_tipo_cambio_id(
            cols: 4, con_registros:true, id_selected:-1,link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select',data:  $select);
        }
        $selects->com_tipo_cambio_id = $select;

        $select = (new cat_sat_regimen_fiscal_html(html:$this->html_base))->select_cat_sat_regimen_fiscal_id(
            cols: 12, con_registros:true, id_selected:-1,link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select',data:  $select);
        }
        $selects->cat_sat_regimen_fiscal_id = $select;

        $select = (new cat_sat_uso_cfdi_html(html:$this->html_base))->select_cat_sat_uso_cfdi_id(
            cols: 4, con_registros:true, id_selected:-1,link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select',data:  $select);
        }
        $selects->cat_sat_uso_cfdi_id = $select;

        $select = (new dp_pais_html(html:$this->html_base))->select_dp_pais_id(
            cols: 6, con_registros:true, id_selected:-1,link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select',data:  $select);
        }
        $selects->dp_pais_id = $select;

        $select = (new dp_estado_html(html:$this->html_base))->select_dp_estado_id(
            cols: 6, con_registros:true, id_selected:-1,link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select',data:  $select);
        }
        $selects->dp_estado_id = $select;

        $select = (new dp_municipio_html(html:$this->html_base))->select_dp_municipio_id(
            cols: 6, con_registros:true, id_selected:-1,link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select',data:  $select);
        }
        $selects->dp_municipio_id = $select;

        $select = (new dp_cp_html(html:$this->html_base))->select_dp_cp_id(
            cols: 6, con_registros:true, id_selected:-1,link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select',data:  $select);
        }
        $selects->dp_cp_id = $select;

        $select = (new dp_colonia_postal_html(html:$this->html_base))->select_dp_colonia_postal_id(
            cols: 6, con_registros:true, id_selected:-1,link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select',data:  $select);
        }
        $selects->dp_colonia_postal_id = $select;

        return $selects;
    }

    public function select_exportacion(int $cols, stdClass $row_upd){
        $exportacion = (int)$row_upd->exportacion;

        $values['01']['descripcion_select'] = '01';
        $values['02']['descripcion_select'] = '02';

        $select = $this->html_base->select(cols:$cols, id_selected: $exportacion, label: 'Exportacion',
            name:'exportacion', values: $values, extra_params_key: array(),required: true);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select', data: $select);
        }

        return $select;
    }

    /**
     * Genera los selects a mostrar para modificar con sus respectivos parámetros
     * @param PDO $link Conexion a la base de datos
     * @param stdClass $row_upd Registro obtenido para actualizar
     * @return array|stdClass
     */
    private function selects_modifica(PDO $link, stdClass $row_upd): array|stdClass
    {
        $selects = new stdClass();

        $select = (new cat_sat_moneda_html(html:$this->html_base))->select_cat_sat_moneda_id(
            cols: 4, con_registros:true, id_selected:$row_upd->cat_sat_moneda_id,link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select',data:  $select);
        }
        $selects->cat_sat_moneda_id = $select;

        $select = (new cat_sat_metodo_pago_html(html:$this->html_base))->select_cat_sat_metodo_pago_id(
            cols: 4, con_registros:true, id_selected:$row_upd->cat_sat_metodo_pago_id,link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select',data:  $select);
        }
        $selects->cat_sat_metodo_pago_id = $select;

        $select = (new cat_sat_tipo_de_comprobante_html(html:$this->html_base))->select_cat_sat_tipo_de_comprobante_id(
            cols: 4, con_registros:true, id_selected:$row_upd->cat_sat_tipo_de_comprobante_id,link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select',data:  $select);
        }
        $selects->cat_sat_tipo_de_comprobante_id = $select;

        $select = (new com_sucursal_html(html:$this->html_base))->select_com_sucursal_id(
            cols: 12, con_registros:true, id_selected:$row_upd->com_sucursal_id,link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select',data:  $select);
        }
        $selects->com_sucursal_id = $select;

        $select = (new dp_calle_pertenece_html(html:$this->html_base))->select_dp_calle_pertenece_id(
            cols: 6, con_registros:true, id_selected:$row_upd->dp_calle_pertenece_id,link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select',data:  $select);
        }
        $selects->dp_calle_pertenece_id = $select;

        $select = (new fc_csd_html(html:$this->html_base))->select_fc_csd_id(
            cols: 12, con_registros:true, id_selected:$row_upd->fc_csd_id,link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select',data:  $select);
        }
        $selects->fc_csd_id = $select;

        $select = (new cat_sat_forma_pago_html(html:$this->html_base))->select_cat_sat_forma_pago_id(
            cols: 4, con_registros:true, id_selected:$row_upd->cat_sat_forma_pago_id,link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select',data:  $select);
        }
        $selects->cat_sat_forma_pago_id = $select;

        $select = (new com_tipo_cambio_html(html:$this->html_base))->select_com_tipo_cambio_id(
            cols: 4, con_registros:true, id_selected:$row_upd->com_tipo_cambio_id,link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select',data:  $select);
        }
        $selects->com_tipo_cambio_id = $select;

        $select = (new cat_sat_regimen_fiscal_html(html:$this->html_base))->select_cat_sat_regimen_fiscal_id(
            cols: 6, con_registros:true, id_selected:$row_upd->cat_sat_regimen_fiscal_id,link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select',data:  $select);
        }
        $selects->cat_sat_regimen_fiscal_id = $select;

        $select = (new cat_sat_uso_cfdi_html(html:$this->html_base))->select_cat_sat_uso_cfdi_id(
            cols: 4, con_registros:true, id_selected:$row_upd->cat_sat_uso_cfdi_id,link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select',data:  $select);
        }
        $selects->cat_sat_uso_cfdi_id = $select;

        $select = (new dp_pais_html(html:$this->html_base))->select_dp_pais_id(
            cols: 6, con_registros:true, id_selected:$row_upd->dp_pais_id,link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select',data:  $select);
        }
        $selects->dp_pais_id = $select;

        $select = (new dp_estado_html(html:$this->html_base))->select_dp_estado_id(
            cols: 6, con_registros:true, id_selected:$row_upd->dp_estado_id,link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select',data:  $select);
        }
        $selects->dp_estado_id = $select;

        $select = (new dp_municipio_html(html:$this->html_base))->select_dp_municipio_id(
            cols: 6, con_registros:true, id_selected:$row_upd->dp_municipio_id,link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select',data:  $select);
        }
        $selects->dp_municipio_id = $select;

        $select = (new dp_cp_html(html:$this->html_base))->select_dp_cp_id(
            cols: 6, con_registros:true, id_selected:$row_upd->dp_cp_id,link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select',data:  $select);
        }
        $selects->dp_cp_id = $select;

        $select = (new dp_colonia_postal_html(html:$this->html_base))->select_dp_colonia_postal_id(
            cols: 6, con_registros:true, id_selected:$row_upd->dp_colonia_postal_id,link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select',data:  $select);
        }
        $selects->dp_colonia_postal_id = $select;



        return $selects;
    }

    public function select_fc_factura_id(int $cols, bool $con_registros, int $id_selected, PDO $link,
    bool $disabled = false): array|string
    {
        $modelo = new fc_factura(link: $link);

        $select = $this->select_catalogo(cols:$cols,con_registros:$con_registros,id_selected:$id_selected,
            modelo: $modelo, disabled: $disabled,label: 'Factura',required: true);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select', data: $select);
        }
        return $select;
    }

    /**
     * Genera los inputs text a mostrar con sus respectivos parámetros
     * @param stdClass $row_upd Registro obtenido para actualizar
     * @param bool $value_vacio Si vacio no muestra datos
     * @return array|stdClass
     */
    private function texts_alta(stdClass $row_upd, bool $value_vacio): array|stdClass
    {
        $texts = new stdClass();

        $in_serie = $this->input_serie(cols: 6,row_upd:  $row_upd,value_vacio:  $value_vacio);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar input',data:  $in_serie);
        }
        $texts->serie = $in_serie;

        if(!isset($row_upd->subtotal)){
            $row_upd->subtotal = 0;
        }
        if(!isset($row_upd->descuento)){
            $row_upd->descuento = 0;
        }
        if(!isset($row_upd->total)){
            $row_upd->total = 0;
        }
        if(!isset($row_upd->impuestos_trasladados)){
            $row_upd->impuestos_trasladados = 0;
        }
        if(!isset($row_upd->impuestos_retenidos)){
            $row_upd->impuestos_retenidos = 0;
        }


        $in_impuestos_trasladados = $this->input_impuestos_trasladados(cols: 4,row_upd: $row_upd,value_vacio:  false,
            disabled: true);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar input',data:  $in_impuestos_trasladados);
        }
        $texts->impuestos_trasladados = $in_impuestos_trasladados;

        $in_impuestos_retenidos = $this->input_impuestos_retenidos(cols: 4,row_upd:  $row_upd,value_vacio:  false,
            disabled: true);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar input',data:  $in_impuestos_retenidos);
        }
        $texts->impuestos_retenidos = $in_impuestos_retenidos;

        $in_subtotal = $this->input_subtotal(cols: 4,row_upd:  $row_upd,value_vacio:  false,  disabled: true);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar input',data:  $in_subtotal);
        }
        $texts->subtotal = $in_subtotal;

        $in_descuento = $this->input_descuento(cols: 4,row_upd:  $row_upd,value_vacio:  false, disabled: true);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar input',data:  $in_descuento);
        }
        $texts->descuento = $in_descuento;

        $in_total = $this->input_total(cols: 8,row_upd:  $row_upd,value_vacio:  false, disabled: true);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar input',data:  $in_total);
        }
        $texts->total = $in_total;

        $in_folio = $this->input_folio(cols: 6,row_upd:  $row_upd,value_vacio:  $value_vacio);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar input',data:  $in_folio);
        }
        $texts->folio = $in_folio;

        if(!isset($row_upd->fecha) || $row_upd->fecha === '0000-00-00'){
            $row_upd->fecha = date('Y-m-d');
        }
        $row_upd->fecha = date('Y-m-d',strtotime($row_upd->fecha));

        $in_fecha= $this->input_fecha(cols: 6,row_upd:  $row_upd,value_vacio:  false);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar input',data:  $in_fecha);
        }
        $texts->fecha = $in_fecha;

        return $texts;
    }

}