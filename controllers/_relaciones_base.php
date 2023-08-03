<?php
namespace gamboamartin\facturacion\controllers;

use gamboamartin\errores\errores;
use gamboamartin\facturacion\html\fc_nota_credito_html;
use gamboamartin\facturacion\models\_transacciones_fc;
use gamboamartin\facturacion\models\_uuid_ext;
use gamboamartin\facturacion\models\fc_factura;
use gamboamartin\facturacion\models\fc_nc_rel;
use gamboamartin\template\html;
use PDO;
use stdClass;

class _relaciones_base{

    private errores $error;

    public function __construct(){
        $this->error = new errores();
    }

    private function asigna_por_relacionar(array $class_css_chk, array $class_css_monto, bool $existe_factura_rel,
                                           array $factura_cliente, array $facturas_cliente_, html $html,
                                           string $key_entidad_id, string $key_entidad_saldo, string $key_entidad_total,
                                           string $key_relacion_id, array $relacion): array
    {
        if(!$existe_factura_rel){
            $factura_cliente = $this->integra_seleccion(class_css_chk: $class_css_chk,
                class_css_monto: $class_css_monto, factura_cliente: $factura_cliente, html: $html,
                key_entidad_id: $key_entidad_id, key_entidad_saldo: $key_entidad_saldo,
                key_entidad_total: $key_entidad_total, key_relacion_id: $key_relacion_id, relacion: $relacion);

            $facturas_cliente_[] = $factura_cliente;
        }
        return $facturas_cliente_;
    }

    private function checkbox_relaciona(array $class_css, array $extra_params, array $factura_cliente, string $key_entidad_id,
                                        string $key_relacion_id, array $relacion): string|array
    {
        $row_entidad_id = $factura_cliente[$key_entidad_id];
        $entidad_origen_key = $key_entidad_id;

        $chk = $this->input_chk_rel(clases_css: $class_css, entidad_origen_key: $entidad_origen_key,
            extra_params: $extra_params, relacion_id: $relacion[$key_relacion_id], row_entidad_id: $row_entidad_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar chk',data:  $chk);
        }

        return $chk;
    }

    private function existe_factura_rel(string $name_entidad_ejecucion, array $factura_cliente, string $key_entidad_id,
                                        PDO $link, array $relacion): bool|array
    {
        $existe_factura_rel = false;

        foreach ($relacion['fc_facturas_relacionadas'] as $fc_factura_relacionada){

            if(isset($factura_cliente[$key_entidad_id])) {
                if(isset($fc_factura_relacionada[$key_entidad_id])){
                    if ($factura_cliente[$key_entidad_id] === $fc_factura_relacionada[$key_entidad_id]) {
                        $existe_factura_rel = true;
                        break;
                    }
                }
                else{
                    if($name_entidad_ejecucion === 'fc_nota_credito') {

                        $filtro['fc_relacion_nc.id'] = $relacion['fc_relacion_nc_id'];
                        $filtro['fc_factura.id'] = $factura_cliente['fc_factura_id'];
                        $existe = (new fc_nc_rel(link: $link))->existe(filtro:$filtro);
                        if(errores::$error){
                            return $this->error->error(mensaje: 'Error al validar si existe relacion', data: $existe);
                        }
                        if($existe){
                            $existe_factura_rel = true;
                            break;
                        }

                    }
                }
            }
        }

        return $existe_factura_rel;
    }

    private function facturas_cliente_(int $com_cliente_id, _base_system_fc $controller, array $facturas_cliente,
                                       string $name_entidad, int $org_empresa_id, array $relacion): array
    {
        $facturas_cliente_ = array();
        $key_entidad_id = $name_entidad.'_id';
        $key_entidad_etapa = $name_entidad.'_etapa';
        foreach ($facturas_cliente as $factura_cliente){
            if($factura_cliente[$key_entidad_etapa] !== 'ALTA') {
                $factura_cliente['key_entidad_id'] = $controller->key_entidad_id;
                $factura_cliente['key_uuid'] = $controller->key_uuid;
                $factura_cliente['key_folio'] = $controller->key_folio;
                $factura_cliente['key_fecha'] = $controller->key_fecha;
                $factura_cliente['key_etapa'] = $controller->key_etapa;
                $factura_cliente['key_total'] = $controller->key_total;
                $factura_cliente['key_saldo'] = $controller->key_saldo;
                $facturas_cliente_ = $this->integra_facturas_cliente(factura_cliente: $factura_cliente,
                    facturas_cliente_: $facturas_cliente_, html: $controller->html_base,
                    key_entidad_id: $key_entidad_id, link: $controller->link,
                    name_entidad_ejecucion: $controller->tabla, key_entidad_saldo: $controller->key_saldo,
                    key_entidad_total: $controller->key_total, key_relacion_id: $controller->key_relacion_id,
                    relacion: $relacion);

                if (errores::$error) {
                    return $this->error->error(mensaje: 'Error al generar selecciones', data: $facturas_cliente_);
                }
            }
        }

        if($name_entidad === 'fc_nota_credito'){
            $filtro['com_cliente.id'] = $com_cliente_id;
            $filtro['org_empresa.id'] = $org_empresa_id;
            $r_fc_factura = (new fc_factura(link: $controller->link))->filtro_and(filtro: $filtro);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al obtener facturas', data: $r_fc_factura);
            }

            foreach ($r_fc_factura->registros as $fc_factura){
                //print_r($fc_factura);exit;
                $fc_factura['key_uuid'] = 'fc_factura_uuid';
                $fc_factura['key_folio'] = 'fc_factura_folio';
                $fc_factura['key_fecha'] = 'fc_factura_fecha';
                $fc_factura['key_etapa'] = 'fc_factura_etapa';
                $fc_factura['key_entidad_id'] = 'fc_factura_id';
                $fc_factura['key_total'] = 'fc_factura_total';
                $fc_factura['key_saldo'] = 'fc_factura_saldo';
                $facturas_cliente_ = $this->integra_facturas_cliente(factura_cliente: $fc_factura,
                    facturas_cliente_: $facturas_cliente_, html: $controller->html_base,
                    key_entidad_id: 'fc_factura_id', link: $controller->link,
                    name_entidad_ejecucion: $controller->tabla, key_entidad_saldo: 'fc_factura_saldo',
                    key_entidad_total: 'fc_factura_total', key_relacion_id: $controller->key_relacion_id,
                    relacion: $relacion);

                if (errores::$error) {
                    return $this->error->error(mensaje: 'Error al generar selecciones', data: $facturas_cliente_);
                }
            }

        }

        return $facturas_cliente_;
    }

    private function facturas_by_client(int $com_cliente_id, _transacciones_fc $modelo_entidad, int $org_empresa_id){
        $filtro = array();
        $filtro['com_cliente.id'] = $com_cliente_id;
        $filtro['org_empresa.id'] = $org_empresa_id;
        $r_fc_factura = $modelo_entidad->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener facturas', data: $r_fc_factura);

        }
        return $r_fc_factura->registros;
    }

    private function facturas_relacion(int $com_cliente_id, _base_system_fc $controller, array $facturas_cliente,
                                       string $name_entidad, int $org_empresa_id, array $relaciones): array
    {
        foreach ($relaciones as $indice=>$relacion){

            $facturas_cliente_ = $this->facturas_cliente_(com_cliente_id: $com_cliente_id,
                controller: $controller, facturas_cliente: $facturas_cliente, name_entidad: $name_entidad,
                org_empresa_id: $org_empresa_id, relacion: $relacion);

            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al generar selecciones', data: $facturas_cliente_);
            }

            $relaciones[$indice]['fc_facturas'] = $facturas_cliente_;
        }
        return $relaciones;
    }

    final public function genera_relaciones(int $com_cliente_id, _base_system_fc $controller, _uuid_ext $modelo_uuid_ext,
                                       string $name_entidad,  int $org_empresa_id): array
    {
        $relaciones = $controller->modelo_entidad->get_data_relaciones(modelo_relacion: $controller->modelo_relacion,
            modelo_relacionada: $controller->modelo_relacionada, modelo_uuid_ext: $modelo_uuid_ext,
            registro_entidad_id: $controller->registro_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener relaciones', data: $relaciones);

        }

        $facturas_cliente = $this->facturas_by_client(com_cliente_id: $com_cliente_id,
            modelo_entidad: $controller->modelo_entidad, org_empresa_id: $org_empresa_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener facturas', data: $facturas_cliente);
        }

        $relaciones = $this->facturas_relacion(com_cliente_id: $com_cliente_id, controller: $controller,
            facturas_cliente: $facturas_cliente, name_entidad: $name_entidad, org_empresa_id: $org_empresa_id,
            relaciones: $relaciones);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al integrar facturas', data: $relaciones);
        }

        return $relaciones;
    }

    /**
     * Genera input de tipo checkbox array para con variable facturas_id
     * @param array $clases_css Clases css
     * @param string $entidad_origen_key key de base ej fc_factura_id
     * @param array $extra_params Parametros para data extra
     * @param int $relacion_id Identificador de relacion
     * @param int $row_entidad_id Registro id de entidad base
     * @return string
     */
    final public function input_chk_rel(array $clases_css, string $entidad_origen_key, array $extra_params,
                                   int $relacion_id, int $row_entidad_id): string
    {
        $class_css_html = '';
        foreach ($clases_css as $class_css){
            $class_css_html.= " $class_css ";
        }
        if($class_css_html!==''){
            $class_css_html = "class='$class_css_html'";
        }

        $extra_params_html = '';
        foreach ($extra_params as $key=>$value){
            $extra_params_html.=" data-$key='$value' ";
        }

        return "<input type='checkbox' $class_css_html $extra_params_html name='fc_facturas_id[$row_entidad_id][$entidad_origen_key]' value='$relacion_id'>";
    }

    private function integra_facturas_cliente(array $factura_cliente, array $facturas_cliente_, html $html,
                                              string $key_entidad_id, PDO $link, string $name_entidad_ejecucion,
                                              string $key_entidad_saldo, string $key_entidad_total,
                                              string $key_relacion_id, array $relacion): array
    {
        $existe_factura_rel = $this->existe_factura_rel(name_entidad_ejecucion: $name_entidad_ejecucion,
            factura_cliente: $factura_cliente, key_entidad_id: $key_entidad_id, link: $link, relacion: $relacion);

        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al verificar si existe relacion', data: $existe_factura_rel);
        }
        $class_css_chk[] = 'chk_relacion';
        $class_css_monto[] = 'inp_monto';
        $class_css_monto[] = 'form-control';

        $facturas_cliente_ = $this->asigna_por_relacionar(class_css_chk: $class_css_chk,
            class_css_monto: $class_css_monto, existe_factura_rel: $existe_factura_rel,
            factura_cliente: $factura_cliente, facturas_cliente_: $facturas_cliente_, html: $html,
            key_entidad_id: $key_entidad_id, key_entidad_saldo: $key_entidad_saldo,
            key_entidad_total: $key_entidad_total, key_relacion_id: $key_relacion_id, relacion: $relacion);

        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al generar selecciones', data: $facturas_cliente_);
        }

        return $facturas_cliente_;
    }

    private function integra_seleccion(array $class_css_chk, array $class_css_monto, array $factura_cliente, html $html,
                                       string $key_entidad_id, string $key_entidad_saldo, string $key_entidad_total,
                                       string $key_relacion_id, array $relacion): array
    {
        $extra_params['total'] = $factura_cliente[$key_entidad_total];
        $extra_params['saldo'] = $factura_cliente[$key_entidad_saldo];

        $checkbox = $this->checkbox_relaciona(class_css: $class_css_chk, extra_params: $extra_params,
            factura_cliente: $factura_cliente, key_entidad_id: $key_entidad_id, key_relacion_id: $key_relacion_id,
            relacion: $relacion);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al generar checkbox', data: $checkbox);
        }
        $factura_cliente['seleccion'] = $checkbox;
        $relacion_id = $relacion[$key_relacion_id];

        $row_entidad_id = $factura_cliente[$key_entidad_id];
        $name = "fc_facturas_id_monto[$row_entidad_id][fc_relacion_id][$relacion_id]";

        $input_monto = (new fc_nota_credito_html(html: $html))->input_monto_aplicado_factura(
            class_css: $class_css_monto, cols: 12, row_upd: new stdClass(), value_vacio: false, name: $name);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al generar monto', data: $input_monto);
        }

        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al generar input_monto', data: $input_monto);
        }
        $factura_cliente['input_monto'] = $input_monto;

        return $factura_cliente;
    }
}
