<?php
namespace gamboamartin\facturacion\controllers;

use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\fc_partida;
use gamboamartin\system\html_controler;
use gamboamartin\template\directivas;
use PDO;
use stdClass;

class _partidas_html{

    private errores $error;
    public function __construct(){
        $this->error = new errores();
    }

    /**
     * @param string $tipo fc_traslado o fc_retenido
     * @param array $partida Partida de factura
     * @return bool
     */
    private function aplica_aplica_impuesto(string  $tipo, array $partida): bool
    {
        $aplica = false;
        if(count($partida[$tipo])>0) {
            $aplica = true;
        }
        return $aplica;
    }

    private function genera_impuesto(html_controler $html_controler, PDO $link, array $partida,
                                     string $tag_tipo_impuesto, string $tipo){
        $aplica = $this->aplica_aplica_impuesto(tipo: $tipo,partida:  $partida);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al verificar aplica impuesto', data: $aplica);
        }
        $impuesto_html = $this->integra_impuesto_html(aplica: $aplica, html_controler: $html_controler,
            link: $link, name_entidad: $tipo, partida: $partida, tag_tipo_impuesto: $tag_tipo_impuesto);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al generar html', data: $impuesto_html);
        }
        return $impuesto_html;
    }

    final function genera_partidas_html(int $fc_factura_id, html_controler $html, PDO $link): array|stdClass
    {
        $partidas  = (new fc_partida($link))->partidas(fc_factura_id: $fc_factura_id,html: $html);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener partidas', data: $partidas);
        }

        foreach ($partidas->registros as $indice=>$partida){
            $partidas = $this->partida_html(html_controler: $html, link: $link, indice: $indice,
                partida: $partida, partidas: $partidas);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al generar html', data: $partidas);
            }
        }
        return $partidas;
    }

    private function impuesto_html(bool $aplica, string $impuesto_html_completo, string $tag_tipo_impuesto){
        $impuesto_html = '';
        if($aplica) {
            $impuesto_html = (new _html_factura())->tr_impuestos_html(
                impuesto_html: $impuesto_html_completo, tag_tipo_impuesto: $tag_tipo_impuesto);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al generar html', data: $impuesto_html);
            }
        }
        return $impuesto_html;
    }

    /**
     * @param html_controler $html_controler
     * @param string $impuesto_html_completo
     * @param PDO $link
     * @param string $name_entidad fc_traslado o fc_traslado
     * @param array $partida
     * @return array|string
     */
    private function impuesto_html_completo(html_controler $html_controler, string $impuesto_html_completo, PDO $link,
                                            string $name_entidad, array $partida): array|string
    {
        $key_importe = $name_entidad.'_importe';

        foreach($partida[$name_entidad] as $impuesto){
            $key_registro_id = $name_entidad.'_id';
            $registro_id = $impuesto[$key_registro_id];

            $params = (new fc_partida(link: $link))->params_button_partida(fc_factura_id: $impuesto['fc_factura_id']);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al obtener params', data: $params);
            }

            $button = $html_controler->button_href(accion: 'elimina_bd', etiqueta: 'Elimina',
                registro_id:  $registro_id,seccion:  $name_entidad,style: 'danger',icon: 'bi bi-trash',
                muestra_icono_btn: true, muestra_titulo_btn: false, params: $params);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al generar button', data: $button);
            }

            $impuesto_html = (new _html_factura())->data_impuesto(button_del: $button, impuesto: $impuesto, key: $key_importe);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al generar html', data: $impuesto_html);
            }
            $impuesto_html_completo.=$impuesto_html;
        }
        return $impuesto_html_completo;
    }

    private function impuestos_html(html_controler $html_controler, PDO $link, array $partida): array|stdClass
    {
        $impuesto_traslado_html = $this->genera_impuesto(html_controler: $html_controler, link: $link,
            partida: $partida, tag_tipo_impuesto: 'Traslados', tipo: 'fc_traslado');
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al generar html', data: $impuesto_traslado_html);
        }
        $impuesto_retenido_html = $this->genera_impuesto(html_controler: $html_controler, link: $link,
            partida: $partida, tag_tipo_impuesto: 'Retenciones', tipo: 'fc_retenido');
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al generar html', data: $impuesto_retenido_html);
        }
        $data = new stdClass();
        $data->traslados = $impuesto_traslado_html;
        $data->retenidos = $impuesto_retenido_html;
        return $data;
    }

    private function integra_impuesto_html(bool $aplica, html_controler $html_controler, PDO $link,
                                           string $name_entidad, array $partida, string $tag_tipo_impuesto){
        $impuesto_html_completo = '';
        if($aplica){
            $impuesto_html_completo = $this->impuesto_html_completo(html_controler: $html_controler,
                impuesto_html_completo: $impuesto_html_completo, link: $link, name_entidad: $name_entidad,
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

    private function partida_html(html_controler $html_controler, PDO $link, int $indice, array $partida,
                                  stdClass $partidas): array|stdClass
    {
        $data_producto_html = (new _html_factura())->data_producto(link: $link, partida: $partida);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al generar html', data: $data_producto_html);
        }
        $partidas->registros[$indice]['data_producto_html'] = $data_producto_html;

        $fc_partida_descripcion = "<tbody><tr><td>$partida[fc_partida_descripcion]</td></tr></tbody>";
        $t_head_producto = "<thead><tr><th>Descripcion</th></tr></thead>";

        $descripcion_html = "<tr>
                                <td class='nested' colspan='9'>
                                    <table class='table table-striped' style='font-size: 14px;'>
                                        $fc_partida_descripcion
                                        $t_head_producto
                                    </table>
                                </td>
                            </tr>";


        $partidas->registros[$indice]['fc_partida_descripcion_html'] = $fc_partida_descripcion;
        $partidas->registros[$indice]['t_head_producto'] = $t_head_producto;
        $partidas->registros[$indice]['descripcion_html'] = $descripcion_html;


        $impuestos_html = $this->impuestos_html(html_controler: $html_controler, link: $link, partida: $partida);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al generar html', data: $impuestos_html);

        }

        $partidas->registros[$indice]['impuesto_traslado_html'] = $impuestos_html->traslados;
        $partidas->registros[$indice]['impuesto_retenido_html'] = $impuestos_html->retenidos;
        return $partidas;
    }
}
