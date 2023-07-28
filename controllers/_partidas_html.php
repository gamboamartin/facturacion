<?php
namespace gamboamartin\facturacion\controllers;

use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\_data_impuestos;
use gamboamartin\facturacion\models\_partida;
use gamboamartin\facturacion\models\_transacciones_fc;
use gamboamartin\facturacion\models\fc_cuenta_predial;
use gamboamartin\facturacion\models\fc_factura;
use gamboamartin\facturacion\models\fc_partida;
use gamboamartin\facturacion\models\fc_retenido;
use gamboamartin\facturacion\models\fc_traslado;
use gamboamartin\system\html_controler;
use gamboamartin\template\directivas;
use gamboamartin\validacion\validacion;
use PDO;
use stdClass;

class _partidas_html{

    private errores $error;
    public function __construct(){
        $this->error = new errores();
    }

    /**
     * Valida si la partida contiene impuestos
     * @param string $tipo fc_traslado o fc_retenido
     * @param array $partida Partida de factura
     * @return bool|array
     * @version 11.0.0
     */
    private function aplica_aplica_impuesto(string  $tipo, array $partida): bool|array
    {
        $tipo = trim($tipo);
        if($tipo === ''){
            return $this->error->error(mensaje: 'Error tipo no existe o esta vacio', data: $tipo);
        }
        $valida = (new validacion())->valida_existencia_keys(keys: array($tipo),registro:  $partida);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar partida', data: $valida);
        }
        if(!is_array($partida[$tipo])){
            return $this->error->error(mensaje: 'Error partida[tipo] debe ser un array', data: $partida);
        }
        $aplica = false;
        if(count($partida[$tipo])>0) {
            $aplica = true;
        }
        return $aplica;
    }

    /**
     * @param html_controler $html_controler
     * @param _partida $modelo_partida
     * @param string $name_modelo_entidad
     * @param array $partida
     * @param string $tag_tipo_impuesto
     * @param string $tipo
     * @return array|string
     */
    private function genera_impuesto(html_controler $html_controler, _partida $modelo_partida,
                                     string $name_modelo_entidad, array $partida, string $tag_tipo_impuesto,
                                     string $tipo): array|string
    {
        $aplica = $this->aplica_aplica_impuesto(tipo: $tipo,partida:  $partida);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al verificar aplica impuesto', data: $aplica);
        }
        $impuesto_html = $this->integra_impuesto_html(aplica: $aplica, html_controler: $html_controler,
            modelo_partida: $modelo_partida, name_entidad_impuesto: $tipo,
            name_modelo_entidad: $name_modelo_entidad, partida: $partida, tag_tipo_impuesto: $tag_tipo_impuesto);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al generar html', data: $impuesto_html);
        }
        return $impuesto_html;
    }

    /**
     * Genera un impuesto en forma de html
     * @param html_controler $html_controler
     * @param array $impuesto
     * @param string $key_importe
     * @param _partida $modelo_partida
     * @param string $name_entidad_impuesto
     * @param string $name_modelo_entidad
     * @return array|string
     * @version 11.3.0
     */
    private function genera_impuesto_html(html_controler $html_controler, array $impuesto, string $key_importe,
                                          _partida $modelo_partida, string $name_entidad_impuesto,
                                          string $name_modelo_entidad): array|string
    {
        $name_entidad_impuesto = trim($name_entidad_impuesto);
        if($name_entidad_impuesto === ''){
            return $this->error->error(mensaje: 'Error name_entidad_impuesto esta vacio', data: $name_entidad_impuesto);
        }
        $name_modelo_entidad = trim($name_modelo_entidad);
        if($name_modelo_entidad === ''){
            return $this->error->error(mensaje: 'Error name_modelo_entidad esta vacio', data: $name_modelo_entidad);
        }
        $key_importe = trim($key_importe);
        if($key_importe === ''){
            return $this->error->error(mensaje: 'Error key_importe esta vacio', data: $key_importe);
        }

        $key_registro_id = $name_entidad_impuesto.'_id';
        $key_modelo_entidad_id = $name_modelo_entidad.'_id';

        $keys = array($key_registro_id,$key_modelo_entidad_id);
        $valida = (new validacion())->valida_ids(keys: $keys, registro: $impuesto);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar impuesto', data: $valida);
        }

        $keys = array($key_registro_id,$key_modelo_entidad_id,$key_importe,'cat_sat_tipo_impuesto_descripcion',
            'cat_sat_tipo_factor_descripcion','cat_sat_factor_factor');
        $valida = (new validacion())->valida_existencia_keys(keys: $keys, registro: $impuesto);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar impuesto', data: $valida);
        }

        $registro_id = $impuesto[$key_registro_id];

        $params = $modelo_partida->params_button_partida(name_modelo_entidad: $name_modelo_entidad,
            registro_entidad_id: $impuesto[$key_modelo_entidad_id]);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener params', data: $params);
        }

        $button = $html_controler->button_href(accion: 'elimina_bd', etiqueta: 'Elimina',
            registro_id:  $registro_id,seccion:  $name_entidad_impuesto,style: 'danger',icon: 'bi bi-trash',
            muestra_icono_btn: true, muestra_titulo_btn: false, params: $params);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al generar button', data: $button);
        }

        $impuesto_html = (new _html_factura())->data_impuesto(button_del: $button, impuesto: $impuesto,
            key: $key_importe);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al generar html', data: $impuesto_html);
        }
        return $impuesto_html;
    }


    /**
     * @param html_controler $html
     * @param PDO $link
     * @param _transacciones_fc $modelo_entidad
     * @param _partida $modelo_partida
     * @param _data_impuestos $modelo_retencion
     * @param _data_impuestos $modelo_traslado
     * @param int $registro_entidad_id
     * @return array|stdClass
     */
    final function genera_partidas_html(html_controler $html, PDO $link, _transacciones_fc $modelo_entidad,
                                        _partida $modelo_partida,_data_impuestos $modelo_retencion,
                                        _data_impuestos $modelo_traslado, int $registro_entidad_id): array|stdClass
    {


        $partidas  = $modelo_partida->partidas(html: $html, modelo_entidad: $modelo_entidad,
            modelo_retencion: $modelo_retencion, modelo_traslado: $modelo_traslado, registro_entidad_id: $registro_entidad_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener partidas', data: $partidas);
        }

        foreach ($partidas->registros as $indice=>$partida){
            $partidas = $this->partida_html(html_controler: $html, indice: $indice, link: $link,
                modelo_partida: $modelo_partida, name_entidad_retenido: $modelo_retencion->tabla,
                name_entidad_traslado: $modelo_traslado->tabla, name_modelo_entidad: $modelo_entidad->tabla,
                partida: $partida, partidas: $partidas);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al generar html', data: $partidas);
            }
        }
        return $partidas;
    }

    /**
     * Integra un impuesto html si aplica o existe en la partida
     * @param bool $aplica Si aplica integra el html del impuesto
     * @param string $impuesto_html_completo Html del contenedor
     * @param string $tag_tipo_impuesto Tipo de impuesto
     * @return array|string
     * @version 11.10.0
     */
    private function impuesto_html(bool $aplica, string $impuesto_html_completo, string $tag_tipo_impuesto): array|string
    {
        $impuesto_html = '';
        if($aplica) {

            $tag_tipo_impuesto = trim($tag_tipo_impuesto);
            if($tag_tipo_impuesto === ''){
                return $this->error->error(mensaje: 'Error tag_tipo_impuesto esta vacio', data: $tag_tipo_impuesto);
            }

            $impuesto_html = (new _html_factura())->tr_impuestos_html(
                impuesto_html: $impuesto_html_completo, tag_tipo_impuesto: $tag_tipo_impuesto);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al generar html', data: $impuesto_html);
            }
        }
        return $impuesto_html;
    }

    /**
     * Integra el html de un impuesto completo
     * @param html_controler $html_controler html base
     * @param string $impuesto_html_completo impuesto previo cargado
     * @param _partida $modelo_partida Modelo de la partida
     * @param string $name_entidad_impuesto fc_traslado o fc_retenido
     * @param string $name_modelo_entidad Nombre de la tabla base
     * @param array $partida Partida a integrar
     * @return array|string
     * @version 11.5.0
     */
    private function impuesto_html_completo(html_controler $html_controler, string $impuesto_html_completo,
                                            _partida $modelo_partida, string $name_entidad_impuesto,
                                            string $name_modelo_entidad, array $partida): array|string
    {

        $name_entidad_impuesto = trim($name_entidad_impuesto);
        if($name_entidad_impuesto === ''){
            return $this->error->error(mensaje: 'Error name_entidad_impuesto esta vacio', data: $name_entidad_impuesto);
        }
        $name_modelo_entidad = trim($name_modelo_entidad);
        if($name_modelo_entidad === ''){
            return $this->error->error(mensaje: 'Error name_modelo_entidad esta vacio', data: $name_modelo_entidad);
        }

        $keys = array($name_entidad_impuesto);
        $valida = (new validacion())->valida_existencia_keys(keys: $keys,registro:  $partida);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar partida', data: $valida);
        }
        if(!is_iterable($partida[$name_entidad_impuesto])){
            return $this->error->error(mensaje: 'Error partida[name_entidad_impuesto] no es iterable',
                data: $valida);
        }

        $key_importe = $name_entidad_impuesto.'_importe';

        foreach($partida[$name_entidad_impuesto] as $impuesto){
            if(!is_array($impuesto)){
                return $this->error->error(mensaje: 'Error impuesto debe ser un array', data: $impuesto);
            }
            $impuesto_html = $this->genera_impuesto_html(html_controler: $html_controler,impuesto:  $impuesto,
                key_importe:  $key_importe, modelo_partida: $modelo_partida,
                name_entidad_impuesto:  $name_entidad_impuesto,name_modelo_entidad:  $name_modelo_entidad);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al generar html', data: $impuesto_html);
            }
            $impuesto_html_completo.=$impuesto_html;
        }

        return $impuesto_html_completo;
    }

    /**
     * @param html_controler $html_controler
     * @param _partida $modelo_partida
     * @param string $name_entidad_retenido
     * @param string $name_entidad_traslado
     * @param string $name_modelo_entidad
     * @param array $partida
     * @return array|stdClass
     */
    private function impuestos_html(html_controler $html_controler, _partida $modelo_partida,
                                    string $name_entidad_retenido, string $name_entidad_traslado,
                                    string $name_modelo_entidad, array $partida): array|stdClass
    {
        $impuesto_traslado_html = $this->genera_impuesto(html_controler: $html_controler,
            modelo_partida: $modelo_partida, name_modelo_entidad: $name_modelo_entidad, partida: $partida,
            tag_tipo_impuesto: 'Traslados', tipo: $name_entidad_traslado);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al generar html', data: $impuesto_traslado_html);
        }
        $impuesto_retenido_html = $this->genera_impuesto(html_controler: $html_controler,
            modelo_partida: $modelo_partida, name_modelo_entidad: $name_modelo_entidad, partida: $partida,
            tag_tipo_impuesto: 'Retenciones', tipo: $name_entidad_retenido);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al generar html', data: $impuesto_retenido_html);
        }
        $data = new stdClass();
        $data->traslados = $impuesto_traslado_html;
        $data->retenidos = $impuesto_retenido_html;
        return $data;
    }

    /**
     * @param bool $aplica
     * @param html_controler $html_controler
     * @param _partida $modelo_partida
     * @param string $name_entidad_impuesto
     * @param string $name_modelo_entidad
     * @param array $partida
     * @param string $tag_tipo_impuesto
     * @return array|string
     */
    private function integra_impuesto_html(bool $aplica, html_controler $html_controler,
                                           _partida $modelo_partida, string $name_entidad_impuesto,
                                           string $name_modelo_entidad, array $partida, string $tag_tipo_impuesto): array|string
    {

        $name_entidad_impuesto = trim($name_entidad_impuesto);
        if($name_entidad_impuesto === ''){
            return $this->error->error(mensaje: 'Error name_entidad_impuesto esta vacio', data: $name_entidad_impuesto);
        }
        $name_modelo_entidad = trim($name_modelo_entidad);
        if($name_modelo_entidad === ''){
            return $this->error->error(mensaje: 'Error name_modelo_entidad esta vacio', data: $name_modelo_entidad);
        }

        $impuesto_html_completo = '';
        if($aplica){
            $impuesto_html_completo = $this->impuesto_html_completo(html_controler: $html_controler,
                impuesto_html_completo: $impuesto_html_completo, modelo_partida: $modelo_partida,
                name_entidad_impuesto: $name_entidad_impuesto, name_modelo_entidad: $name_modelo_entidad,
                partida: $partida);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al generar html', data: $impuesto_html_completo);
            }
        }

        $impuesto_html = $this->impuesto_html(aplica: $aplica,
            impuesto_html_completo: $impuesto_html_completo, tag_tipo_impuesto: $tag_tipo_impuesto);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al generar html', data: $impuesto_html);
        }
        return $impuesto_html;
    }

    /**
     * @param html_controler $html_controler
     * @param int $indice
     * @param PDO $link
     * @param _partida $modelo_partida
     * @param string $name_entidad_retenido
     * @param string $name_entidad_traslado
     * @param string $name_modelo_entidad
     * @param array $partida
     * @param stdClass $partidas
     * @return array|stdClass
     */
    private function partida_html(html_controler $html_controler, int $indice, PDO $link, _partida $modelo_partida,
                                  string $name_entidad_retenido, string $name_entidad_traslado,
                                  string $name_modelo_entidad, array $partida, stdClass $partidas): array|stdClass
    {

        $data_producto_html = (new _html_factura())->data_producto(html_controler:$html_controler, link: $link,
            name_entidad_partida: $modelo_partida->tabla, partida: $partida);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al generar html', data: $data_producto_html);
        }
        $partidas->registros[$indice]['data_producto_html'] = $data_producto_html;

        $key_descripcion = $modelo_partida->tabla.'_descripcion';
        $key_descripcion_html = $modelo_partida->tabla.'descripcion_html';

        $row_upd = new stdClass();
        $row_upd->descripcion = $partida[$key_descripcion];
        $input_descripcion = $html_controler->input_descripcion(cols: 12,row_upd: $row_upd,value_vacio:  false,
            con_label: false);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al generar input', data: $input_descripcion);
        }

        $fc_partida_descripcion = "<tbody><tr><td>$input_descripcion</td></tr></tbody>";

        $t_head_producto = "<thead><tr><th>Descripcion</th></tr></thead>";

        $descripcion_html = "<tr>
                                <td class='nested' colspan='9'>
                                    <table class='table table-striped' style='font-size: 14px;'>
                                        $fc_partida_descripcion
                                        $t_head_producto
                                    </table>
                                </td>
                            </tr>";


        $partidas->registros[$indice][$key_descripcion_html] = $fc_partida_descripcion;
        $partidas->registros[$indice]['t_head_producto'] = $t_head_producto;
        $partidas->registros[$indice]['descripcion_html'] = $descripcion_html;


        $impuestos_html = $this->impuestos_html(html_controler: $html_controler,
            modelo_partida: $modelo_partida, name_entidad_retenido: $name_entidad_retenido,
            name_entidad_traslado: $name_entidad_traslado,
            name_modelo_entidad: $name_modelo_entidad, partida: $partida);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al generar html', data: $impuestos_html);

        }

        $partidas->registros[$indice]['impuesto_traslado_html'] = $impuestos_html->traslados;
        $partidas->registros[$indice]['impuesto_retenido_html'] = $impuestos_html->retenidos;
        return $partidas;
    }
}
