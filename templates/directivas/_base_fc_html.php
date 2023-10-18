<?php
namespace gamboamartin\facturacion\html;

use base\orm\modelo;
use gamboamartin\cat_sat\models\cat_sat_conf_imps;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\controllers\_base_system_fc;
use gamboamartin\facturacion\controllers\controlador_fc_factura;
use gamboamartin\facturacion\models\_transacciones_fc;
use gamboamartin\facturacion\models\fc_factura;
use gamboamartin\facturacion\models\limpieza;
use gamboamartin\system\html_controler;
use gamboamartin\validacion\validacion;
use html\cat_sat_conf_imps_html;
use html\cat_sat_factor_html;
use html\cat_sat_forma_pago_html;
use html\cat_sat_metodo_pago_html;
use html\cat_sat_moneda_html;
use html\cat_sat_regimen_fiscal_html;
use html\cat_sat_tipo_de_comprobante_html;
use html\cat_sat_tipo_factor_html;
use html\cat_sat_tipo_impuesto_html;
use html\cat_sat_uso_cfdi_html;
use html\com_producto_html;
use html\com_sucursal_html;
use html\com_tipo_cambio_html;
use html\dp_calle_pertenece_html;
use html\dp_colonia_postal_html;
use html\dp_cp_html;
use html\dp_estado_html;
use html\dp_municipio_html;
use html\dp_pais_html;
use PDO;
use stdClass;

class _base_fc_html extends html_controler{

    private function cat_sat_conf_imps(array $configuraciones_impuestos, _transacciones_fc $modelo_entidad,
                                       int $registro_entidad_id): array|stdClass
    {
        $params = $this->params_imps(configuraciones_impuestos: $configuraciones_impuestos,
            modelo_entidad: $modelo_entidad, registro_entidad_id: $registro_entidad_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener parametros', data: $params);
        }

        $r_cat_sat_conf_imps = (new cat_sat_conf_imps(link: $modelo_entidad->link))->filtro_and(in: $params->in);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener configuraciones', data: $r_cat_sat_conf_imps);
        }
        $params->cat_sat_conf_imps = $r_cat_sat_conf_imps->registros;


        return $params;

    }

    /**
     * Obtiene los codigos de regimen fiscal y tipo de persona del cliente y empresa
     * @param _transacciones_fc $modelo_entidad Modelo base de ejecucion
     * @param int $registro_id Registro en proceso
     * @return array|stdClass
     */
    private function data_param_imp(_transacciones_fc $modelo_entidad, int $registro_id): array|stdClass
    {
        $fc_entidad = $modelo_entidad->registro(registro_id: $registro_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener entidad', data: $fc_entidad);
        }

        $keys_codes = array('cat_sat_regimen_fiscal_empresa_codigo','cat_sat_tipo_persona_cliente_codigo');
        $data = $this->normaliza_codigos(keys: $keys_codes,row:  $fc_entidad);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al normalizar', data: $data);
        }

        return $data;
    }

    private function data_params_imps(array $configuraciones_impuestos, _transacciones_fc $modelo_entidad,
                                      int $registro_entidad_id): array|stdClass
    {
        $data_param = $this->data_param_imp(modelo_entidad: $modelo_entidad, registro_id: $registro_entidad_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener param', data: $data_param);
        }

        $existe_conf_imps = $this->existe_conf_imps(
            cat_sat_regimen_fiscal_empresa_codigo: $data_param->cat_sat_regimen_fiscal_empresa_codigo,
            cat_sat_tipo_persona_cliente_codigo:  $data_param->cat_sat_tipo_persona_cliente_codigo,
            configuraciones_impuestos: $configuraciones_impuestos);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar si existe conf impuestos', data: $existe_conf_imps);
        }
        $data_param->existe_conf_imps = $existe_conf_imps;
        return $data_param;
    }

    /**
     * Obtiene el elemento default de una configuracion de impuestos forzosa la existencia de los keys
     * @param string $cat_sat_regimen_fiscal_empresa_codigo
     * @param string $cat_sat_tipo_persona_cliente_codigo
     * @param array $configuraciones_impuestos
     * @return int
     */
    private function default_conf_imps(string $cat_sat_regimen_fiscal_empresa_codigo,
                                       string $cat_sat_tipo_persona_cliente_codigo, array $configuraciones_impuestos): int
    {
        return $configuraciones_impuestos[$cat_sat_regimen_fiscal_empresa_codigo][$cat_sat_tipo_persona_cliente_codigo]['default'];
    }

    private function existe_conf_imps(string $cat_sat_regimen_fiscal_empresa_codigo,
                                      string $cat_sat_tipo_persona_cliente_codigo,
                                      array $configuraciones_impuestos): bool
    {
        $existe = false;
        if(isset($configuraciones_impuestos[$cat_sat_regimen_fiscal_empresa_codigo])) {
            if (isset($configuraciones_impuestos[$cat_sat_regimen_fiscal_empresa_codigo][$cat_sat_tipo_persona_cliente_codigo])) {
                $existe = true;
            }
        }
        return $existe;

    }

    /**
     * Obtiene el parametro in para obtencion de configuraciones de impuestos, forza existencia de keys
     * @param string $cat_sat_regimen_fiscal_empresa_codigo
     * @param string $cat_sat_tipo_persona_cliente_codigo
     * @param array $configuraciones_impuestos
     * @return array
     */
    private function in_configuracion_imp(string $cat_sat_regimen_fiscal_empresa_codigo,
                                          string $cat_sat_tipo_persona_cliente_codigo, array $configuraciones_impuestos): array
    {
        $in['llave'] = 'cat_sat_conf_imps.id';
        $in['values'] = $configuraciones_impuestos[$cat_sat_regimen_fiscal_empresa_codigo][$cat_sat_tipo_persona_cliente_codigo]['permitidos'];
        return $in;
    }


    public function input_observaciones(int $cols, stdClass $row_upd, bool $value_vacio): array|string
    {
        if($cols<=0){
            return $this->error->error(mensaje: 'Error cold debe ser mayor a 0', data: $cols);
        }
        if($cols>=13){
            return $this->error->error(mensaje: 'Error cold debe ser menor o igual a  12', data: $cols);
        }

        $html =$this->directivas->input_text(disabled: false,name: 'observaciones',
            place_holder: 'Observaciones',required: false,row_upd: $row_upd, value_vacio: $value_vacio);
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

    private function asigna_inputs_fc_partida(_base_system_fc $controler, stdClass $inputs): array|stdClass
    {
        $controler->inputs->select = new stdClass();
        $controler->inputs->select->fc_factura_id = $inputs->selects->fc_factura_id;
        $controler->inputs->select->com_producto_id = $inputs->selects->com_producto_id;
        $controler->inputs->select->cat_sat_tipo_factor_id = $inputs->selects->cat_sat_tipo_factor_id;
        $controler->inputs->select->cat_sat_factor_id = $inputs->selects->cat_sat_factor_id;
        $controler->inputs->select->cat_sat_tipo_impuesto_id = $inputs->selects->cat_sat_tipo_impuesto_id;

        $controler->inputs->cantidad = $inputs->texts->cantidad;
        $controler->inputs->valor_unitario = $inputs->texts->valor_unitario;
        $controler->inputs->descuento = $inputs->texts->descuento;
        $controler->inputs->codigo = $inputs->texts->codigo;

        return $controler->inputs;
    }


    public function genera_inputs_alta(controlador_fc_factura $controler, array $keys_selects, PDO $link): array|stdClass
    {
        $inputs = $this->init_alta(keys_selects: $keys_selects, modelo: $link);
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
        $inputs = $this->init_modifica(link: $link, modelo: $controler->modelo, row_upd: $controler->row_upd);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar inputs',data:  $inputs);
        }

        $inputs_asignados = $this->asigna_inputs(controler:$controler, inputs: $inputs);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al asignar inputs',data:  $inputs_asignados);
        }

        return $inputs_asignados;
    }

    public function genera_inputs_fc_partida(_base_system_fc $controler, PDO $link): array|stdClass
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

    public function genera_inputs_fc_partida_modifica(_base_system_fc $controler, PDO $link): array|stdClass
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

    private function normaliza_codigos(array $keys, array $row){
        $result = new stdClass();
        foreach ($keys as $key){

            $key = trim($key);
            if($key === ''){
                return $this->error->error(mensaje: 'Error key esta vacio',data:  $key);
            }

            $txt = $this->txt_null_normalizado(key: $key, row: $row);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al normalizar', data: $txt);
            }
            $result->$key = $txt;

        }
        return $result;
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

        $html =$this->directivas->input_text_required(disabled: $disabled,name: 'cantidad',place_holder: 'Cantidad',
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

        $html =$this->directivas->input_text_required(disabled: $disabled,name: 'valor_unitario',place_holder: 'Valor Unitario',
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

    private function params_conf_imps(string $cat_sat_regimen_fiscal_empresa_codigo,
                                      string $cat_sat_tipo_persona_cliente_codigo,
                                      array $configuraciones_impuestos): array|stdClass
    {
        $params = new stdClass();
        $in = $this->in_configuracion_imp(
            cat_sat_regimen_fiscal_empresa_codigo: $cat_sat_regimen_fiscal_empresa_codigo,
            cat_sat_tipo_persona_cliente_codigo:  $cat_sat_tipo_persona_cliente_codigo,configuraciones_impuestos: $configuraciones_impuestos);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener parametro in', data: $in);

        }

        $default = $this->default_conf_imps(
            cat_sat_regimen_fiscal_empresa_codigo: $cat_sat_regimen_fiscal_empresa_codigo,
            cat_sat_tipo_persona_cliente_codigo:  $cat_sat_tipo_persona_cliente_codigo,
            configuraciones_impuestos: $configuraciones_impuestos);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener parametro default', data: $default);

        }

        $params->in = $in;
        $params->default = $default;

        return $params;

    }

    private function params_conf_impuestos(string $cat_sat_regimen_fiscal_empresa_codigo,
                                           string $cat_sat_tipo_persona_cliente_codigo,
                                           array $configuraciones_impuestos, bool $existe_conf_imps): array|stdClass
    {
        $params = new stdClass();
        $params->in = array();
        $params->default = -1;

        if($existe_conf_imps){
            $params = $this->params_conf_imps(
                cat_sat_regimen_fiscal_empresa_codigo: $cat_sat_regimen_fiscal_empresa_codigo,
                cat_sat_tipo_persona_cliente_codigo: $cat_sat_tipo_persona_cliente_codigo,configuraciones_impuestos: $configuraciones_impuestos);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al obtener parametros', data: $params);

            }
        }
        return $params;

    }

    private function params_imps(array $configuraciones_impuestos, _transacciones_fc $modelo_entidad, int $registro_entidad_id): array|stdClass
    {
        $data_param = $this->data_params_imps(configuraciones_impuestos: $configuraciones_impuestos,
            modelo_entidad: $modelo_entidad, registro_entidad_id: $registro_entidad_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener param', data: $data_param);
        }



        $params = $this->params_conf_impuestos(
            cat_sat_regimen_fiscal_empresa_codigo: $data_param->cat_sat_regimen_fiscal_empresa_codigo,
            cat_sat_tipo_persona_cliente_codigo: $data_param->cat_sat_tipo_persona_cliente_codigo,
            configuraciones_impuestos: $configuraciones_impuestos, existe_conf_imps: $data_param->existe_conf_imps);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener parametros', data: $params);

        }
        return $params;
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

        $select = (new cat_sat_tipo_factor_html(html:$this->html_base))->select_cat_sat_tipo_factor_id(
            cols: 4, con_registros:true, id_selected:-1,link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select',data:  $select);
        }
        $selects->cat_sat_tipo_factor_id = $select;

        $select = (new cat_sat_factor_html(html:$this->html_base))->select_cat_sat_factor_id(
            cols: 4, con_registros:true, id_selected:-1,link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select',data:  $select);
        }
        $selects->cat_sat_factor_id = $select;

        $select = (new cat_sat_tipo_impuesto_html(html:$this->html_base))->select_cat_sat_tipo_impuesto_id(
            cols: 4, con_registros:true, id_selected:-1,link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select',data:  $select);
        }
        $selects->cat_sat_tipo_impuesto_id = $select;

        return $selects;
    }

    final public function select_cat_sat_imp_id(array $configuraciones_impuestos, _transacciones_fc $modelo_entidad,
                                                int $registro_entidad_id){
        $params = $this->cat_sat_conf_imps(configuraciones_impuestos: $configuraciones_impuestos,
            modelo_entidad: $modelo_entidad, registro_entidad_id: $registro_entidad_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener params', data: $params);
        }

        $cat_sat_conf_imps_id = (new cat_sat_conf_imps_html(html: $this->html_base))->select_cat_sat_conf_imps_id(
            cols: 12,con_registros:  true,id_selected: $params->default,link: $modelo_entidad->link,registros: $params->cat_sat_conf_imps);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al inicializar input', data: $cat_sat_conf_imps_id);
        }
        return $cat_sat_conf_imps_id;
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
     * @param array $keys_selects
     * @param PDO $link
     * @param modelo|false $modelo
     * @return array|stdClass
     */
    protected function init_alta(array $keys_selects, PDO $link, modelo|false $modelo = false): array|stdClass
    {
        $selects = $this->selects_alta(keys_selects: $keys_selects, link: $link);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar selects',data:  $selects);
        }

        $row_upd = new stdClass();
        $row_upd->exportacion = -1;
        $in_exportacion = $this->select_exportacion(cols: 6, link: $link, modelo: $modelo, row_upd: $row_upd);
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
     * @param modelo $modelo
     * @param stdClass $row_upd
     * @return array|stdClass
     */
    private function init_modifica(PDO $link, modelo $modelo, stdClass $row_upd): array|stdClass
    {

        $selects = $this->selects_modifica(link: $link, row_upd: $row_upd);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar selects',data:  $selects);
        }

        $in_exportacion = $this->select_exportacion(cols: 6, link: $link, modelo: $modelo, row_upd: $row_upd);
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

        $html =$this->directivas->input_text_required(disabled: $disabled,name: 'version',place_holder: 'Version',
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

        $html =$this->directivas->input_text_required(disabled: $disabled,name: 'serie',place_holder: 'Serie',
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

        $html =$this->directivas->input_text_required(disabled: $disabled,name: 'subtotal',place_holder: 'Subtotal',
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

        $html =$this->directivas->input_text_required(disabled: $disabled,name: 'descuento',place_holder: 'Descuento',
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

        $html =$this->directivas->input_text_required(disabled: $disabled,name: 'impuestos_trasladados',
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

        $html =$this->directivas->input_text_required(disabled: $disabled,name: 'impuestos_retenidos',
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

        $html =$this->directivas->input_text_required(disabled: $disabled,name: 'total',place_holder: 'Total',
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

        $html =$this->directivas->input_text_required(disabled: $disabled,name: 'folio',place_holder: 'Folio',
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

        $html =$this->directivas->input_text_required(disabled: $disabled,name: 'exportacion',place_holder: 'Exportacion',
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
     * Genera un select de tipo exportacion
     * @param int $cols N columnas css
     * @param PDO $link
     * @param modelo|bool $modelo $modelo
     * @param stdClass $row_upd Registro en proceso
     * @return array|string
     * @version 0.113.26
     */
    public function select_exportacion(int $cols, PDO $link, modelo|bool $modelo, stdClass $row_upd): array|string
    {
        $keys = array('exportacion');
        $valida = (new validacion())->valida_existencia_keys(keys: $keys, registro: $row_upd);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar',data:  $valida);
        }

        $valida = (new validacion())->valida_cols_css(cols: $cols);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar cols',data:  $valida);
        }

        $exportacion = (int)$row_upd->exportacion;

        $values['01']['descripcion_select'] = '01';
        $values['02']['descripcion_select'] = '02';

        if(!$modelo){
            $modelo = new fc_factura(link: $link);
        }

        $select = $this->html_base->select(cols: $cols, id_selected: $exportacion, label: 'Exportacion',
            name: 'exportacion', values: $values, required: true);
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
            cols: 12, con_registros:true, id_selected:$row_upd->com_sucursal_id,link: $link,disabled: false);
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



    public function select_fc_entidad_id(int $cols,array $columns_ds, bool $con_registros, bool $disabled,
                                         array $filtro, int|null $id_selected, string $label,
                                         _transacciones_fc $modelo_entidad, array $registros){

        $select = $this->select_catalogo(cols: $cols, con_registros: $con_registros, id_selected: $id_selected,
            modelo: $modelo_entidad, columns_ds: $columns_ds, disabled: $disabled, filtro: $filtro, label: $label,
            registros: $registros, required: true);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar select', data: $select);
        }
        return $select;
    }

    /**
     * Genera los inputs text a mostrar con sus respectivos parámetros
     * @param stdClass $row_upd Registro obtenido para actualizar
     * @param bool $value_vacio Si vacio no muestra datos
     * @param stdClass $params
     * @return array|stdClass
     */
    protected function texts_alta(stdClass $row_upd, bool $value_vacio, stdClass $params = new stdClass()): array|stdClass
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

    /**
     * Normaliza un texto de un array si este viene nulo lo integra como vacio
     * @param string $key Key a verificar
     * @param array $row registro a verificar
     * @return string|array
     * @version 10.155.6
     */
    private function txt_null_normalizado(string $key, array $row): string|array
    {
        $key = trim($key);
        if($key === ''){
            return $this->error->error(mensaje: 'Error key esta vacio',data:  $key);
        }

        $txt = '';
        if(isset($row[$key])){
            $txt = trim($row[$key]);
        }
        return $txt;
    }

}
