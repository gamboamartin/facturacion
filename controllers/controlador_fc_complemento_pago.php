<?php
/**
 * @author Martin Gamboa Vazquez
 * @version 1.0.0
 * @created 2022-05-14
 * @final En proceso
 *
 */
namespace gamboamartin\facturacion\controllers;

use gamboamartin\errores\errores;
use gamboamartin\facturacion\html\fc_complemento_pago_html;
use gamboamartin\facturacion\models\fc_cancelacion_cp;
use gamboamartin\facturacion\models\fc_cfdi_sellado_cp;
use gamboamartin\facturacion\models\fc_complemento_pago;
use gamboamartin\facturacion\models\fc_complemento_pago_documento;
use gamboamartin\facturacion\models\fc_complemento_pago_etapa;
use gamboamartin\facturacion\models\fc_complemento_pago_relacionada;
use gamboamartin\facturacion\models\fc_cuenta_predial_cp;
use gamboamartin\facturacion\models\fc_docto_relacionado;
use gamboamartin\facturacion\models\fc_email_cp;
use gamboamartin\facturacion\models\fc_factura;
use gamboamartin\facturacion\models\fc_notificacion_cp;
use gamboamartin\facturacion\models\fc_pago;
use gamboamartin\facturacion\models\fc_pago_pago;
use gamboamartin\facturacion\models\fc_pago_total;
use gamboamartin\facturacion\models\fc_partida_cp;
use gamboamartin\facturacion\models\fc_relacion_cp;
use gamboamartin\facturacion\models\fc_retenido_cp;
use gamboamartin\facturacion\models\fc_traslado_cp;
use gamboamartin\facturacion\models\fc_uuid_cp;
use gamboamartin\system\actions;
use gamboamartin\template\html;

use html\cat_sat_forma_pago_html;
use PDO;
use stdClass;

class controlador_fc_complemento_pago extends _base_system_fc {

    public array|stdClass $keys_selects = array();
    public controlador_fc_partida_cp $controlador_fc_partida_cp;
    public controlador_com_producto $controlador_com_producto;

    public string $button_fc_complemento_pago_modifica = '';
    public string $button_fc_complemento_pago_relaciones = '';
    public string $button_fc_complemento_pago_timbra = '';

    public string $rfc = '';
    public string $razon_social = '';
    public string $link_fc_partida_cp_alta_bd = '';
    public string $link_fc_partida_cp_modifica_bd = '';
    public string $link_fc_factura_cp_partidas = '';
    public string $link_fc_complemento_pago_nueva_partida = '';

    public string $link_fc_email_cp_alta_bd = '';
    public string $link_fc_relacion_cp_alta_bd = '';
    public string $link_com_producto = '';

    public string $link_complemento_pago_cancela = '';
    public string $link_complemento_pago_xml = '';
    public string $link_complemento_pago_timbra_xml = '';
    public string $button_fc_complemento_pago_correo = '';
    public string $link_fc_complemento_pago_relacionada_alta_bd = '';
    public string $link_fc_docto_relacionado_alta_bd = '';
    public int $fc_complemento_pago_id = -1;
    public int $fc_partida_cp_id = -1;
    public stdClass $partidas;

    public array $fc_pagos = array();
    public array $fc_facturas = array();

    public string $link_fc_pago_pago_alta_bd = '';


    public array $relaciones = array();
    public array$complementos_pago_cliente = array();
    public function __construct(PDO $link, html $html = new \gamboamartin\template_1\html(),
                                stdClass $paths_conf = new stdClass()){
        $modelo = new fc_complemento_pago(link: $link);
        $this->modelo = $modelo;
        $this->cat_sat_tipo_de_comprobante = 'Pago';
        $html_ = new fc_complemento_pago_html(html: $html);
        $this->html_fc = $html_;

        parent::__construct(html_: $html_, link: $link,modelo:  $modelo, paths_conf: $paths_conf);


        $this->data_selected_alta['cat_sat_forma_pago_id']['id'] = 99;
        $this->data_selected_alta['cat_sat_forma_pago_id']['filtro'] = array('cat_sat_forma_pago.id'=>99);

        $this->data_selected_alta['cat_sat_metodo_pago_id']['id'] = 2;
        $this->data_selected_alta['cat_sat_metodo_pago_id']['filtro'] = array('cat_sat_metodo_pago.id'=>2);

        $this->data_selected_alta['cat_sat_moneda_id']['id'] = 163;
        $this->data_selected_alta['cat_sat_moneda_id']['filtro'] = array('cat_sat_moneda.id'=>163);

        $this->data_selected_alta['com_tipo_cambio_id']['id'] = -1;
        $this->data_selected_alta['com_tipo_cambio_id']['filtro'] = array();

        $this->data_selected_alta['cat_sat_uso_cfdi_id']['id'] = 23;
        $this->data_selected_alta['cat_sat_uso_cfdi_id']['filtro'] = array('cat_sat_uso_cfdi.id'=>23);


        $init_ctl = (new _fc_base())->init_base_fc(controler: $this,name_modelo_email: 'fc_email_cp');
        if(errores::$error){
            $error = $this->errores->error(mensaje: 'Error al inicializar',data:  $init_ctl);
            print_r($error);
            die('Error');
        }


        if(isset($_GET['fc_partida_id'])){
            $this->fc_partida_cp_id = $_GET['fc_partida_cp_id'];
        }

        $this->lista_get_data = true;


        $this->verifica_parents_alta = true;


    }

    public function alta_partida_bd(bool $header, bool $ws = false)
    {
        $this->modelo_entidad = $this->modelo;
        $this->modelo_partida = new fc_partida_cp(link: $this->link);
        $this->modelo_predial = new fc_cuenta_predial_cp(link: $this->link);
        $this->modelo_relacion = new fc_relacion_cp(link: $this->link);
        $this->modelo_relacionada = new fc_complemento_pago_relacionada(link: $this->link);
        $this->modelo_retencion = new fc_retenido_cp(link: $this->link);
        $this->modelo_traslado = new fc_traslado_cp(link: $this->link);


        $r_alta_partida =  parent::alta_partida_bd($header, $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al insertar',data:  $r_alta_partida,header:  $header, ws: $ws);
        }

        return $r_alta_partida;

    }

    public function cancela(bool $header, bool $ws = false)
    {
        $this->modelo_entidad = new fc_complemento_pago(link: $this->link);

        $r_template = parent::cancela($header, $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar template',data:  $r_template,header:  $header, ws: $ws);
        }

        return $r_template;
    }

    public function cancela_bd(bool $header, bool $ws = false): array|stdClass
    {
        $this->modelo_entidad = new fc_complemento_pago(link: $this->link);
        $this->modelo_cancelacion = new fc_cancelacion_cp(link: $this->link);
        $this->modelo_etapa = new fc_complemento_pago_etapa(link: $this->link);

        $r_cancela_bd = parent::cancela_bd($header, $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al cancelar',data:  $r_cancela_bd,header:  $header, ws: $ws);
        }

        return $r_cancela_bd;
    }

    public function correo(bool $header, bool $ws = false): array|stdClass
    {
        $this->modelo_entidad = new fc_complemento_pago(link: $this->link);
        $this->modelo_email = new fc_email_cp(link: $this->link);

        $r_template = parent::correo($header, $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar template',data:  $r_template,header:  $header, ws: $ws);
        }

        return $r_template;
    }

    private function data_modifica(cat_sat_forma_pago_html $cat_sat_forma_pago_html,
                                   fc_complemento_pago_html $fc_complemento_pago_html): array|stdClass
    {
        $value_fecha_pago = date('Y-m-d H:i:s');
        $fecha_pago = $fc_complemento_pago_html->input_fecha(cols: 6,
            row_upd: new stdClass(), value_vacio: false, name: 'fecha_pago', value: $value_fecha_pago,
            value_hora: true);

        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al generar fecha_pago',data:  $fecha_pago);
        }

        $this->inputs->fecha_pago = $fecha_pago;

        $monto = $fc_complemento_pago_html->input_monto(cols: 6,row_upd: new stdClass(),value_vacio: false);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al generar monto',data:  $monto);
        }

        $this->inputs->monto = $monto;

        $cat_sat_forma_pago_id_full = $cat_sat_forma_pago_html->select_cat_sat_forma_pago_id(
            cols: 6,con_registros: true,id_selected: -1,link: $this->link);

        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al generar cat_sat_forma_pago_id_full',data:  $cat_sat_forma_pago_id_full);
        }

        $this->inputs->cat_sat_forma_pago_id_full = $cat_sat_forma_pago_id_full;

        $link_fc_pago_pago_alta_bd = $this->obj_link->link_alta_bd(link:  $this->link,seccion: 'fc_pago_pago');
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al generar link_fc_pago_pago_alta_bd',data:  $link_fc_pago_pago_alta_bd);
        }

        $this->link_fc_pago_pago_alta_bd = $link_fc_pago_pago_alta_bd;

        $link_fc_docto_relacionado_alta_bd = $this->obj_link->link_con_id(accion: 'fc_docto_rel_alta_bd',
            link: $this->link,registro_id: $this->registro_id,seccion: $this->tabla);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al generar link_fc_docto_relacionado_alta_bd',
                data:  $link_fc_docto_relacionado_alta_bd);
        }

        $this->link_fc_docto_relacionado_alta_bd = $link_fc_docto_relacionado_alta_bd;

        $filtro['fc_complemento_pago.id'] = $this->registro_id;
        $r_fc_pago = (new fc_pago(link: $this->link))->filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener r_fc_pago',data:  $r_fc_pago);
        }

        $fc_pago_id = $fc_complemento_pago_html->hidden(name: 'fc_pago_id',value:  $r_fc_pago->registros[0]['fc_pago_id']);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener fc_pago_id',data:  $fc_pago_id);
        }
        $this->inputs->fc_pago_id = $fc_pago_id;

        $seccion_retorno = $fc_complemento_pago_html->hidden(name: 'seccion_retorno',value:  $this->tabla);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener seccion_retorno',data:  $seccion_retorno);
        }
        $this->inputs->seccion_retorno = $seccion_retorno;

        $id_retorno = $fc_complemento_pago_html->hidden(name: 'id_retorno',value:  $this->registro_id);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener id_retorno',data:  $id_retorno);
        }
        $this->inputs->id_retorno = $id_retorno;

        return $this->inputs;
    }

    public function descarga_xml(bool $header, bool $ws = false)
    {
        $this->modelo_documento = new fc_complemento_pago_documento(link: $this->link);

        $r_template = parent::descarga_xml($header, $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar template',data:  $r_template,header:  $header, ws: $ws);
        }

        return $r_template;
    }

    public function envia_cfdi(bool $header, bool $ws = false)
    {
        $this->modelo_email = new fc_email_cp(link: $this->link);
        $this->modelo_notificacion = new fc_notificacion_cp(link: $this->link);
        $this->modelo_etapa = new fc_complemento_pago_etapa(link: $this->link);

        $r_envia = parent::envia_cfdi($header, $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al enviar',data:  $r_envia,header:  $header, ws: $ws);
        }

        return $r_envia;

    }

    public function exportar_documentos(bool $header, bool $ws = false)
    {

        $this->modelo_traslado = new fc_traslado_cp(link: $this->link);
        $this->modelo_relacion = new fc_relacion_cp(link: $this->link);
        $this->modelo_relacionada = new fc_complemento_pago_relacionada(link: $this->link);
        $this->modelo_retencion = new fc_retenido_cp(link: $this->link);
        $this->modelo_predial = new fc_cuenta_predial_cp(link: $this->link);
        $this->modelo_partida = new fc_partida_cp(link: $this->link);
        $this->modelo_entidad = new fc_complemento_pago(link: $this->link);
        $this->modelo_documento = new fc_complemento_pago_documento(link: $this->link);
        $this->modelo_sello = new fc_cfdi_sellado_cp(link: $this->link);
        $this->modelo_uuid_ext = new fc_uuid_cp(link: $this->link);

        $r_template = parent::exportar_documentos($header, $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al insertar relacion',data:  $r_template,header:  $header, ws: $ws);
        }
        return $r_template;
    }

    public function fc_docto_rel_alta_bd(bool $header, bool $ws = false){
        $montos = $_POST['monto'];
        $altas = array();

        //print_r($montos);exit;

        foreach ($montos as $fc_factura){
            $fc_factura_id = key($fc_factura);
            $fc_pago_pago_id = key($fc_factura[$fc_factura_id]);

            $existe_monto = false;
            $monto = 0;
            if(isset($fc_factura[$fc_factura_id])){
                if(isset($fc_factura[$fc_factura_id][$fc_pago_pago_id])){
                    if(trim($fc_factura[$fc_factura_id][$fc_pago_pago_id])!==''){
                        if((float)trim($fc_factura[$fc_factura_id][$fc_pago_pago_id]) > 0.0){
                            $monto = $fc_factura[$fc_factura_id][$fc_pago_pago_id];
                            $existe_monto = true;
                        }
                    }
                }
            }
            if($existe_monto) {

                $fc_docto_relacionado_ins['fc_factura_id'] = $fc_factura_id;
                $fc_docto_relacionado_ins['imp_pagado'] = $monto;
                $fc_docto_relacionado_ins['fc_pago_pago_id'] = $fc_pago_pago_id;

                $alta_bd = (new fc_docto_relacionado(link: $this->link))->alta_registro(registro: $fc_docto_relacionado_ins);
                if (errores::$error) {
                    return $this->retorno_error(mensaje: 'Error al insertar', data: $alta_bd, header: $header, ws: $ws);
                }
                $altas[] = $alta_bd;
            }

        }

        $siguiente_view = (new actions())->init_alta_bd();
        if(errores::$error){
            $this->link->rollBack();
            return $this->retorno_error(mensaje: 'Error al obtener siguiente view', data: $siguiente_view,
                header:  $header, ws: $ws);
        }

        if($header){

            $retorno = (new actions())->retorno_alta_bd(link: $this->link, registro_id: $this->registro_id,
                seccion: $this->tabla, siguiente_view: "modifica");
            if(errores::$error){
                return $this->retorno_error(mensaje: 'Error cambiar de view', data: $retorno,
                    header:  true, ws: $ws);
            }
            header('Location:'.$retorno);
            exit;
        }
        if($ws){
            header('Content-Type: application/json');
            echo json_encode($altas, JSON_THROW_ON_ERROR);
            exit;
        }
        return $altas;


    }

    public function fc_factura_relacionada_alta_bd(bool $header, bool $ws = false)
    {

        $this->modelo_relacion = new fc_relacion_cp(link: $this->link);
        $this->modelo_entidad = new fc_complemento_pago(link: $this->link);
        $this->modelo_relacionada = new fc_complemento_pago_relacionada(link: $this->link);

        $r_alta = parent::fc_factura_relacionada_alta_bd($header, $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al insertar relacion',data:  $r_alta,header:  $header, ws: $ws);
        }
        return $r_alta;

    }

    public function genera_pdf(bool $header, bool $ws = false)
    {
        $this->modelo_documento = (new fc_complemento_pago_documento(link: $this->link));
        $this->modelo_entidad = $this->modelo;
        $this->modelo_partida = (new fc_partida_cp(link: $this->link));
        $this->modelo_predial = (new fc_cuenta_predial_cp(link: $this->link));
        $this->modelo_relacion = (new fc_relacion_cp(link: $this->link));
        $this->modelo_relacionada = (new fc_complemento_pago_relacionada(link: $this->link));
        $this->modelo_retencion = (new fc_retenido_cp(link: $this->link));
        $this->modelo_sello = (new fc_cfdi_sellado_cp(link: $this->link));
        $this->modelo_traslado = (new fc_traslado_cp(link: $this->link));

        $r_genera = parent::genera_pdf($header, $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar pdf',data:  $r_genera,header:  $header, ws: $ws);
        }
        return $r_genera;
    }

    public function modifica(bool $header, bool $ws = false): array|stdClass
    {
        $this->ctl_partida = new controlador_fc_partida_cp(link: $this->link);
        $this->modelo_entidad = $this->modelo;
        $this->modelo_partida = (new fc_partida_cp(link: $this->link));
        $this->modelo_retencion = (new fc_retenido_cp(link: $this->link));
        $this->modelo_traslado = (new fc_traslado_cp(link: $this->link));
        $this->modelo_email = (new fc_email_cp(link: $this->link));


        $r_modifica = parent::modifica($header, $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar template',data:  $r_modifica,header:  $header, ws: $ws);
        }

        $cat_sat_forma_pago_html = (new cat_sat_forma_pago_html(html: $this->html_base));
        $fc_complemento_pago_html = (new fc_complemento_pago_html(html: $this->html_base));

        $data = $this->data_modifica(cat_sat_forma_pago_html: $cat_sat_forma_pago_html,
            fc_complemento_pago_html:  $fc_complemento_pago_html);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar template',data:  $data,header:  $header, ws: $ws);
        }

        $fc_pago_modelo = new fc_pago(link: $this->link);
        $fc_pago_total_modelo = new fc_pago_total(link: $this->link);
        $fc_pago_pago_modelo = new fc_pago_pago(link: $this->link);
        $fc_docto_relacionado_modelo = new fc_docto_relacionado(link: $this->link);
        $fc_factura_modelo = new fc_factura(link: $this->link);

        $filtro['fc_complemento_pago.id'] = $this->registro_id;
        $r_fc_pago = $fc_pago_modelo->filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener r_fc_pago',data:  $r_fc_pago,header:  $header, ws: $ws);
        }

        $fc_pagos = $r_fc_pago->registros;


        foreach ($fc_pagos as $indice_fc_pagos=>$fc_pago){
            $filtro = array();
            $filtro['fc_pago.id'] = $fc_pago['fc_pago_id'];
            $r_fc_pago_total = $fc_pago_total_modelo->filtro_and(filtro: $filtro);
            if(errores::$error){
                return $this->retorno_error(mensaje: 'Error al obtener r_fc_pago_total',data:  $r_fc_pago_total,header:  $header, ws: $ws);
            }
            $fc_pago_totales = $r_fc_pago_total->registros;

            $filtro = array();
            $filtro['fc_pago.id'] = $fc_pago['fc_pago_id'];
            $r_fc_pago_pago = $fc_pago_pago_modelo->filtro_and(filtro: $filtro);
            if(errores::$error){
                return $this->retorno_error(mensaje: 'Error al obtener r_fc_pago_pago',data:  $r_fc_pago_pago,header:  $header, ws: $ws);
            }
            $fc_pago_pagos = $r_fc_pago_pago->registros;

            foreach ($fc_pago_pagos as $indice_pago_pago=>$fc_pago_pago){
                $filtro = array();
                $filtro['fc_pago_pago.id'] = $fc_pago_pago['fc_pago_pago_id'];
                $r_fc_docto_relacionado = $fc_docto_relacionado_modelo->filtro_and(filtro: $filtro);
                if(errores::$error){
                    return $this->retorno_error(mensaje: 'Error al obtener r_fc_docto_relacionado',data:  $r_fc_docto_relacionado,header:  $header, ws: $ws);
                }
                $fc_doctos_relacionados = $r_fc_docto_relacionado->registros;

                foreach ($fc_doctos_relacionados as $indice_fc_doctos_relacionados=>$fc_docto_relacionado){
                    $fc_factura = $fc_factura_modelo->registro(registro_id: $fc_docto_relacionado['fc_factura_id']);
                    if(errores::$error){
                        return $this->retorno_error(mensaje: 'Error al obtener fc_factura',data:  $fc_factura,header:  $header, ws: $ws);
                    }

                    $monto_pagado = $fc_factura_modelo->total_pagos(fc_factura_id: $fc_docto_relacionado['fc_factura_id']);
                    if(errores::$error){
                        return $this->retorno_error(mensaje: 'Error al obtener monto_pagado',data:  $monto_pagado,header:  $header, ws: $ws);
                    }

                    $saldo = $fc_factura_modelo->saldo(fc_factura_id: $fc_docto_relacionado['fc_factura_id']);
                    if(errores::$error){
                        return $this->retorno_error(mensaje: 'Error al obtener monto_pagado',data:  $monto_pagado,header:  $header, ws: $ws);
                    }

                    $params['seccion_retorno'] = $this->tabla;
                    $params['accion_retorno'] = 'modifica';
                    $params['id_retorno'] = $this->registro_id;

                    $link_elimina_bd = $this->html->button_href(accion: 'elimina_bd',etiqueta: 'Elimina',
                        registro_id:  $fc_docto_relacionado['fc_docto_relacionado_id'],seccion: 'fc_docto_relacionado',style: 'danger', params: $params);
                    if(errores::$error){
                        return $this->retorno_error(mensaje: 'Error al obtener link_elimina_bd',data:  $link_elimina_bd,header:  $header, ws: $ws);
                    }

                    $fc_doctos_relacionados[$indice_fc_doctos_relacionados]['fc_factura_total'] = $fc_factura['fc_factura_total'];
                    $fc_doctos_relacionados[$indice_fc_doctos_relacionados]['fc_factura_monto_pagado'] = $monto_pagado;
                    $fc_doctos_relacionados[$indice_fc_doctos_relacionados]['fc_factura_saldo'] = $saldo;
                    $fc_doctos_relacionados[$indice_fc_doctos_relacionados]['elimina_bd'] = $link_elimina_bd;


                }

                $fc_pago_pagos[$indice_pago_pago]['fc_doctos_relacionados'] = $fc_doctos_relacionados;


            }


            $fc_pagos[$indice_fc_pagos]['fc_pago_totales'] = $fc_pago_totales;
            $fc_pagos[$indice_fc_pagos]['fc_pago_pagos'] = $fc_pago_pagos;

        }

        $fc_complemento_pago = (new fc_complemento_pago(link: $this->link))->registro(registro_id: $this->registro_id);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener fc_complemento_pago',data:  $fc_complemento_pago,header:  $header, ws: $ws);
        }

        $filtro = array();
        $filtro['com_cliente.id'] = $fc_complemento_pago['com_cliente_id'];
        $filtro['org_empresa.id'] = $fc_complemento_pago['org_empresa_id'];
        $r_fc_facturas = (new fc_factura(link: $this->link))->filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener r_fc_facturas',data:  $r_fc_facturas,header:  $header, ws: $ws);
        }
        $fc_facturas = $r_fc_facturas->registros;

        foreach ($fc_facturas as $indice_fc_factura=>$fc_factura){
            $monto_pagado = (new fc_factura(link: $this->link))->total_pagos(fc_factura_id: $fc_factura['fc_factura_id']);
            if(errores::$error){
                return $this->retorno_error(mensaje: 'Error al obtener monto_pagado',data:  $monto_pagado,header:  $header, ws: $ws);
            }

            $saldo = $fc_factura['fc_factura_total'] - $monto_pagado;


            $fc_facturas[$indice_fc_factura]['fc_factura_monto_pagado'] = $monto_pagado;
            $fc_facturas[$indice_fc_factura]['fc_factura_saldo'] = $saldo;

            if($saldo <= 0.0) {
                unset($fc_facturas[$indice_fc_factura]);
            }


        }

        $this->fc_facturas = $fc_facturas;


        $this->fc_pagos = $fc_pagos;


        return $r_modifica;
    }

    public function relaciones(bool $header, bool $ws = false)
    {
        $this->modelo_partida = new fc_partida_cp(link: $this->link);
        $this->modelo_entidad = new fc_complemento_pago(link: $this->link);
        $this->modelo_retencion = new fc_retenido_cp(link: $this->link);
        $this->modelo_traslado = new fc_traslado_cp(link: $this->link);
        $this->modelo_relacion = new fc_relacion_cp(link: $this->link);
        $this->modelo_relacionada = new fc_complemento_pago_relacionada(link: $this->link);
        $this->modelo_etapa = new fc_complemento_pago_etapa(link: $this->link);

        $r_modifica = parent::relaciones($header, $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al generar template',data:  $r_modifica,header:  $header, ws: $ws);
        }

        return $r_modifica;
    }

    public function timbra_xml(bool $header, bool $ws = false): array|stdClass
    {

        $this->modelo_entidad = $this->modelo;
        $this->modelo_documento = new fc_complemento_pago_documento(link: $this->link);
        $this->modelo_etapa = new fc_complemento_pago_etapa(link: $this->link);
        $this->modelo_partida = new fc_partida_cp(link: $this->link);
        $this->modelo_predial = new fc_cuenta_predial_cp(link: $this->link);
        $this->modelo_relacion = new fc_relacion_cp(link: $this->link);
        $this->modelo_relacionada = new fc_complemento_pago_relacionada(link: $this->link);
        $this->modelo_retencion = new fc_retenido_cp(link: $this->link);
        $this->modelo_sello = new fc_cfdi_sellado_cp(link: $this->link);
        $this->modelo_traslado = new fc_traslado_cp(link: $this->link);
        $this->modelo_uuid_ext = new fc_uuid_cp(link: $this->link);

        $r_timbra = parent::timbra_xml($header, $ws); // TODO: Change the autogenerated stub
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al timbrar',data:  $r_timbra,header:  $header, ws: $ws);
        }



        return $r_timbra;

    }






}
