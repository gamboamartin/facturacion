<?php
namespace html;

use gamboamartin\errores\errores;
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
        $controler->inputs->select->fc_cfd_id = $inputs->selects->fc_cfd_id;
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
        $controler->inputs->version = $inputs->texts->version;
        $controler->inputs->serie = $inputs->texts->serie;
        $controler->inputs->subtotal = $inputs->texts->subtotal;
        $controler->inputs->descuento = $inputs->texts->descuento;
        $controler->inputs->total = $inputs->texts->total;
        $controler->inputs->folio = $inputs->texts->folio;
        $controler->inputs->fecha = $inputs->texts->fecha;
        $controler->inputs->exportacion = $inputs->texts->exportacion;

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

        $texts = $this->texts_alta(row_upd: new stdClass(), value_vacio: true);
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


        $select = (new cat_sat_tipo_de_comprobante_html(html:$this->html_base))->select_cat_sat_tipo_de_comprobante_id(
            cols: 4, con_registros:true, id_selected:-1,link: $link);
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

        $select = (new fc_cfd_html(html:$this->html_base))->select_fc_cfd_id(
            cols: 12, con_registros:true, id_selected:-1,link: $link, label: 'Empresa');
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select',data:  $select);
        }
        $selects->fc_cfd_id = $select;

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
            cols: 6, con_registros:true, id_selected:-1,link: $link);
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

        $select = (new fc_cfd_html(html:$this->html_base))->select_fc_cfd_id(
            cols: 12, con_registros:true, id_selected:$row_upd->fc_cfd_id,link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select',data:  $select);
        }
        $selects->fc_cfd_id = $select;

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

    public function select_fc_factura_id(int $cols, bool $con_registros, int $id_selected, PDO $link): array|string
    {
        $modelo = new fc_factura(link: $link);

        $select = $this->select_catalogo(cols:$cols,con_registros:$con_registros,id_selected:$id_selected,
            modelo: $modelo,label: 'Factura',required: true);
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

        $in_version= $this->input_version(cols: 6,row_upd:  $row_upd,value_vacio:  $value_vacio);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar input',data:  $in_version);
        }
        $texts->version = $in_version;

        $in_serie = $this->input_serie(cols: 6,row_upd:  $row_upd,value_vacio:  $value_vacio);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar input',data:  $in_serie);
        }
        $texts->serie = $in_serie;

        $in_subtotal = $this->input_subtotal(cols: 4,row_upd:  $row_upd,value_vacio:  $value_vacio);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar input',data:  $in_subtotal);
        }
        $texts->subtotal = $in_subtotal;

        $in_descuento = $this->input_descuento(cols: 4,row_upd:  $row_upd,value_vacio:  $value_vacio);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar input',data:  $in_descuento);
        }
        $texts->descuento = $in_descuento;

        $in_total = $this->input_total(cols: 4,row_upd:  $row_upd,value_vacio:  $value_vacio);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar input',data:  $in_total);
        }
        $texts->total = $in_total;

        $in_folio = $this->input_folio(cols: 4,row_upd:  $row_upd,value_vacio:  $value_vacio);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar input',data:  $in_folio);
        }
        $texts->folio = $in_folio;

        $in_fecha= $this->input_fecha(cols: 4,row_upd:  $row_upd,value_vacio:  $value_vacio);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar input',data:  $in_fecha);
        }
        $texts->fecha = $in_fecha;

        $in_exportacion = $this->input_exportacion(cols: 4,row_upd:  $row_upd,value_vacio:  $value_vacio);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar input',data:  $in_exportacion);
        }
        $texts->exportacion = $in_exportacion;

        return $texts;
    }

}