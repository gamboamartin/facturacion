<?php
/**
 * @author Martin Gamboa Vazquez
 * @version 1.0.0
 * @created 2022-05-14
 * @final En proceso
 *
 */
namespace gamboamartin\facturacion\controllers;

use gamboamartin\banco\models\bn_sucursal_cuenta;
use gamboamartin\errores\errores;
use gamboamartin\organigrama\html\org_empresa_html;
use gamboamartin\organigrama\html\org_sucursal_html;
use gamboamartin\organigrama\models\org_empresa;
use gamboamartin\template\html;
use html\bn_banco_html;
use html\com_cliente_html;
use PDO;
use stdClass;

class controlador_org_sucursal extends \gamboamartin\organigrama\controllers\controlador_org_sucursal {

    public string $link_asigna_cuenta_bd = '';

    public string $button_bn_sucursal_cuenta = '';
    public function __construct(PDO $link, html $html = new \gamboamartin\template_1\html(), stdClass $paths_conf = new stdClass())
    {
        parent::__construct(link: $link,html:  $html, paths_conf: $paths_conf);
        $this->childrens_data['fc_csd']['title'] = 'CSD';

        $link_asigna_cuenta_bd = $this->obj_link->link_alta_bd(link: $this->link, seccion: 'bn_sucursal_cuenta');
        if (errores::$error) {
            $error = $this->errores->error(mensaje: 'Error al obtener link', data: $link_asigna_cuenta_bd);
            print_r($error);
            exit;
        }
        $this->link_asigna_cuenta_bd = $link_asigna_cuenta_bd;
    }

    public function asigna_cuenta(bool $header, bool $ws = false): array|stdClass
    {
        $row_upd = $this->modelo->registro(registro_id: $this->registro_id, columnas_en_bruto: true, retorno_obj: true);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al obtener registro', data: $row_upd);
        }

        $empresa = (new org_empresa($this->link))->registro(registro_id: $row_upd->org_empresa_id);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al obtener empresa', data: $row_upd);
        }

        $this->inputs = new stdClass();
        $org_sucursal_id = (new org_sucursal_html(html: $this->html_base))->select_org_sucursal_id(cols: 12,
            con_registros: true, id_selected: $this->registro_id, link: $this->link,
            disabled: true, filtro: array('org_sucursal.id' => $this->registro_id));
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al maquetar input', data: $org_sucursal_id);
        }
        $this->inputs->org_sucursal_id = $org_sucursal_id;

        $bn_banco_id = (new bn_banco_html(html: $this->html_base))->select_bn_banco_id(cols: 12,
            con_registros: true, id_selected: -1, link: $this->link);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al maquetar input', data: $bn_banco_id);
        }
        $this->inputs->bn_banco_id = $bn_banco_id;

        $row_upd->rfc = $empresa['org_empresa_rfc'];
        $row_upd->razon_social = $empresa['org_empresa_razon_social'];

        $org_empresa_rfc = (new com_cliente_html(html: $this->html_base))->input_rfc(cols: 4, row_upd: $row_upd,
            value_vacio: false,disabled: true);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al maquetar input', data: $org_empresa_rfc);
        }
        $this->inputs->org_empresa_rfc = $org_empresa_rfc;

        $org_empresa_razon_social = (new com_cliente_html(html: $this->html_base))->input_razon_social(cols: 8,
            row_upd: $row_upd, value_vacio: false, disabled: true);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al maquetar input', data: $org_empresa_razon_social);
        }
        $this->inputs->org_empresa_razon_social = $org_empresa_razon_social;

        $num_cuenta = $this->html->input_text(cols: 12, disabled: false, name: 'num_cuenta',
            place_holder: "Número de cuenta", row_upd: new stdClass(), value_vacio: false);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al maquetar input', data: $num_cuenta);
        }
        $this->inputs->num_cuenta = $num_cuenta;

        $clabe = $this->html->input_text(cols: 12, disabled: false, name: 'clabe',
            place_holder: "Clabe", row_upd: new stdClass(), value_vacio: false);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al maquetar input', data: $clabe);
        }
        $this->inputs->clabe = $clabe;

        $hidden_row_id = $this->html->hidden(name: 'org_sucursal_id', value: $this->registro_id);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al maquetar input', data: $hidden_row_id);
        }

        $hidden_seccion_retorno = $this->html->hidden(name: 'seccion_retorno', value: $this->tabla);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al maquetar input', data: $hidden_seccion_retorno);
        }

        $hidden_id_retorno = $this->html->hidden(name: 'id_retorno', value: $this->registro_id);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al maquetar input', data: $hidden_id_retorno);
        }

        $this->inputs->hidden_row_id = $hidden_row_id;
        $this->inputs->hidden_seccion_retorno = $hidden_seccion_retorno;
        $this->inputs->hidden_id_retorno = $hidden_id_retorno;

        $filtro['org_sucursal.id'] = $this->registro_id;
        $bn_sucursal_cuenta = (new bn_sucursal_cuenta(link: $this->link))->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al obtener cuentas', data: $bn_sucursal_cuenta);
        }

        $cuentas_empresa = $bn_sucursal_cuenta->registros;

        foreach ($cuentas_empresa as $indice => $cuenta_empresa) {
            $params = $this->params_button_partida(org_sucursal_id: $this->registro_id);
            if (errores::$error) {
                return $this->errores->error(mensaje: 'Error al generar params', data: $params);
            }

            $link_elimina = $this->html->button_href(accion: 'elimina_bd', etiqueta: 'Eliminar',
                registro_id: $cuenta_empresa['bn_sucursal_cuenta_id'],
                seccion: 'bn_sucursal_cuenta', style: 'danger', icon: 'bi bi-trash',
                muestra_icono_btn: true, muestra_titulo_btn: false, params: $params);
            if (errores::$error) {
                return $this->errores->error(mensaje: 'Error al generar link elimina_bd', data: $link_elimina);
            }
            $cuentas_empresa[$indice]['elimina_bd'] = $link_elimina;
        }
        $this->registros['cuentas_empresa'] = $cuentas_empresa;

        $button_bn_sucursal_cuenta = $this->html->button_href(accion: 'modifica', etiqueta: 'Ir a Sucursal',
            registro_id: $this->registro_id,
            seccion: 'org_sucursal', style: 'warning', params: array());
        if (errores::$error) {
            return $this->errores->error(mensaje: 'Error al generar link', data: $button_bn_sucursal_cuenta);
        }

        $this->button_bn_sucursal_cuenta = $button_bn_sucursal_cuenta;

        return $this->inputs;
    }

    private function params_button_partida(int $org_sucursal_id): array
    {
        $params = array();
        $params['seccion_retorno'] = 'org_sucursal';
        $params['accion_retorno'] = 'asigna_cuenta';
        $params['id_retorno'] = $org_sucursal_id;
        return $params;
    }


}
