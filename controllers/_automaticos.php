<?php
namespace gamboamartin\facturacion\controllers;
use base\orm\_modelo_parent_sin_codigo;
use config\generales;
use gamboamartin\compresor\compresor;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\_pdf;
use gamboamartin\facturacion\models\fc_cfdi_sellado;
use gamboamartin\facturacion\models\fc_cuenta_predial;
use gamboamartin\facturacion\models\fc_email;
use gamboamartin\facturacion\models\fc_factura;
use gamboamartin\facturacion\models\fc_factura_documento;
use gamboamartin\facturacion\models\fc_factura_etapa;
use gamboamartin\facturacion\models\fc_factura_relacionada;
use gamboamartin\facturacion\models\fc_notificacion;
use gamboamartin\facturacion\models\fc_partida;
use gamboamartin\facturacion\models\fc_relacion;
use gamboamartin\facturacion\models\fc_retenido;
use gamboamartin\facturacion\models\fc_traslado;
use gamboamartin\facturacion\models\fc_uuid_fc;
use gamboamartin\system\actions;
use gamboamartin\system\out_permisos;
use gamboamartin\system\system;
use stdClass;
use Throwable;

class _automaticos extends system{
    public string $link_timbra = '';
    protected _modelo_parent_sin_codigo $modelo_automatico;


    private function buttons_html(controlador_fc_factura $controlador_fc_factura): array|string
    {
        if(count($controlador_fc_factura->registro) === 0){
            return $this->errores->error(mensaje: 'Error controler->registro esta vacio',
                data:  $controlador_fc_factura->registro);
        }
        $buttons = (new out_permisos())->buttons_view(controler:$controlador_fc_factura,
            not_actions: array(), params: array());
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener botones', data: $buttons);
        }
        return implode('', $buttons);
    }


    final protected function data_form(): array|stdClass
    {
        $clases_css[] = 'btn_timbra';
        $button_timbra = $this->html->directivas->btn(ids_css: array(), clases_css: $clases_css, extra_params: array(),
            label: 'Timbra', name: 'btn_timbra', value: 'Timbra', cols: 2, style: 'danger',type: 'submit' );
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener boton', data: $button_timbra);
        }
        $link_timbra = $this->obj_link->link_con_id(accion: 'timbra',link:  $this->link,
            registro_id: $this->registro_id,seccion: $this->seccion);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener boton link_timbra', data: $link_timbra);
        }

        $clases_css = array();
        $clases_css[] = 'btn_descarga';
        $button_descarga = $this->html->directivas->btn(ids_css: array(), clases_css: $clases_css, extra_params: array(),
            label: 'Descarga', name: 'btn_descarga', value: 'Descarga', cols: 2, style: 'warning',type: 'submit' );
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener boton', data: $button_descarga);
        }
        $link_descarga = $this->obj_link->link_con_id(accion: 'descarga',link:  $this->link,registro_id: $this->registro_id,seccion: $this->seccion);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener boton link_descarga', data: $link_descarga);
        }

        $clases_css = array();
        $clases_css[] = 'btn_envia_cfdi';
        $button_envia_cfdi = $this->html->directivas->btn(ids_css: array(), clases_css: $clases_css, extra_params: array(),
            label: 'Envia CFDI', name: 'btn_envia', value: 'Envia CFDI', cols: 2, style: 'success',type: 'submit' );
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener boton', data: $button_envia_cfdi);
        }
        $link_envia_cfdi = $this->obj_link->link_con_id(accion: 'envia_cfdi',link:  $this->link,
            registro_id: $this->registro_id,seccion: $this->seccion);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener boton link_descarga', data: $link_envia_cfdi);
        }

        $data = new stdClass();
        $data->button_timbra = $button_timbra;
        $data->button_descarga = $button_descarga;
        $data->button_envia_cfdi = $button_envia_cfdi;
        $data->link_timbra = $link_timbra;
        $data->link_descarga = $link_descarga;
        $data->link_envia_cfdi = $link_envia_cfdi;

        return $data;
    }

    private function data_rows_fc(controlador_fc_factura $controlador_fc_factura, array $fc_factura): array|stdClass
    {
        $controlador_fc_factura->registro_id = $fc_factura['fc_factura_id'];

        $buttons_html = $this->buttons_html(controlador_fc_factura: $controlador_fc_factura);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener botones', data: $buttons_html);
        }
        $input_chk = $this->input_chk(fc_factura: $fc_factura);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener checkbox', data: $input_chk);
        }
        $data = new stdClass();
        $data->buttons_html = $buttons_html;
        $data->input_chk = $input_chk;
        return $data;
    }

    public function descarga(bool $header, bool $ws = false){

        if(!isset($_POST['fc_facturas_id'])){
            $_POST['fc_facturas_id'] = array();
        }
        $fc_facturas = $_POST['fc_facturas_id'];
        if(count($fc_facturas) === 0){
            $facturas = $this->facturas_automaticas();

            foreach ($facturas as $fc_factura){
                $fc_facturas[] = $fc_factura['fc_factura_id'];
            }

        }

        $row_aut = $this->modelo->registro(registro_id: $this->registro_id, columnas_en_bruto: true, retorno_obj: true);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener registro',data:  $row_aut,header:  $header,ws:  $ws);
        }

        $modelo_documento = new fc_factura_documento(link: $this->link);
        $modelo_entidad = new fc_factura(link: $this->link);
        $modelo_partida = new fc_partida(link: $this->link);
        $modelo_predial = new fc_cuenta_predial(link: $this->link);
        $modelo_relacion = new fc_relacion(link: $this->link);
        $modelo_relacionada = new fc_factura_relacionada(link: $this->link);
        $modelo_retencion = new fc_retenido(link: $this->link);
        $modelo_sello = new fc_cfdi_sellado(link: $this->link);
        $modelo_traslado = new fc_traslado(link: $this->link);
        $modelo_uuid_ext = new fc_uuid_fc(link: $this->link);


        $documentos_fc = array();

        foreach ($fc_facturas as $fc_factura_id){

            $fc_factura = (new fc_factura(link: $this->link))->registro(registro_id: $fc_factura_id);
            if(errores::$error){
                return $this->retorno_error(mensaje: 'Error al obtener factura',data:  $fc_factura,header:  $header,ws:  $ws);
            }
            if($fc_factura['fc_factura_etapa'] ==='TIMBRADO'){

                $ruta_xml = $modelo_documento->get_factura_documento(
                    key_entidad_filter_id: $modelo_entidad->key_filtro_id, registro_id: $fc_factura_id,
                    tipo_documento: "xml_sin_timbrar");
                if(errores::$error){
                    return $this->retorno_error(mensaje: 'Error al obtener XML',data:  $ruta_xml, header: $header,ws:$ws);
                }

                if(!file_exists($ruta_xml)){
                    return $this->retorno_error(mensaje: 'Error al no existe xml',data:  $ruta_xml, header: $header,ws:$ws);
                }

                $docto_xml = array();
                $docto_xml['ruta'] = $ruta_xml;
                $docto_xml['doc_extension_codigo'] ='xml';
                $docto_xml['fc_factura_folio'] =$fc_factura['fc_factura_folio'];


                $ruta_pdf = (new _pdf())->pdf(descarga: false, guarda: true, link: $this->link,
                    modelo_documento: $modelo_documento, modelo_entidad: $modelo_entidad,
                    modelo_partida: $modelo_partida, modelo_predial: $modelo_predial,
                    modelo_relacion: $modelo_relacion, modelo_relacionada: $modelo_relacionada,
                    modelo_retencion: $modelo_retencion, modelo_sellado: $modelo_sello,
                    modelo_traslado: $modelo_traslado, modelo_uuid_ext: $modelo_uuid_ext, registro_id: $fc_factura['fc_factura_id']);
                if(errores::$error){
                    return $this->retorno_error(mensaje: 'Error al generar PDF',data:  $ruta_pdf, header: $header,ws:$ws);
                }

                $docto_pdf = array();
                $docto_pdf['ruta'] = $ruta_pdf;
                $docto_pdf['doc_extension_codigo'] ='pdf';
                $docto_pdf['fc_factura_folio'] =$fc_factura['fc_factura_folio'];


                $documentos_fc[$fc_factura['fc_factura_folio']][] = $docto_xml;
                $documentos_fc[$fc_factura['fc_factura_folio']][] = $docto_pdf;
            }
        }


        $destinos_zip = array();
        foreach ($documentos_fc as $fc_factura_folio=>$fc_documento){
            $doc_zip = array();
            foreach ($fc_documento as $documento) {
                $name_doc = $documento['fc_factura_folio'] . '.' . $documento['doc_extension_codigo'];
                $origen = $documento['ruta'];
                $doc_zip[$origen] = $name_doc;
            }


            $destino_zip = compresor::comprime_archivos(archivos: $doc_zip,name_zip: $fc_factura_folio.'.zip');
            if(errores::$error){
                return $this->retorno_error(mensaje: 'Error al comprimir',data:  $destino_zip,header:  $header,ws:  $ws);
            }
            $origen_zip = (new generales())->path_base.$destino_zip;
            $destinos_zip[$origen_zip] = $fc_factura_folio.'.zip';
        }

        $zip_completo = compresor::comprime_archivos(archivos: $destinos_zip);

        foreach ($destinos_zip as $origen_zip=>$destino_zip){
            unlink($origen_zip);
        }


        if($header) {
            ob_clean();
            header("Cache-Control: public");
            header("Content-Description: File Transfer");
            header("Content-Disposition: attachment; filename=$row_aut->descripcion.zip");
            header("Content-Type: application/zip");
            header("Content-Transfer-Encoding: binary");
            readfile((new generales())->path_base . $zip_completo);
            unlink((new generales())->path_base . $zip_completo);
            exit;
        }
        $content = file_get_contents((new generales())->path_base . $zip_completo);
        unlink((new generales())->path_base . $zip_completo);
        return $content;

    }

    public function envia_cfdi(bool $header, bool $ws = false){

        if(!isset($_POST['fc_facturas_id'])){
            $_POST['fc_facturas_id'] = array();
        }
        $fc_facturas = $_POST['fc_facturas_id'];
        if(count($fc_facturas) === 0){
            $facturas = $this->facturas_automaticas();

            foreach ($facturas as $fc_factura){
                $fc_facturas[] = $fc_factura['fc_factura_id'];
            }

        }

        $modelo_documento = new fc_factura_documento(link: $this->link);
        $modelo_partida = new fc_partida(link: $this->link);
        $modelo_predial = new fc_cuenta_predial(link: $this->link);
        $modelo_relacion = new fc_relacion(link: $this->link);
        $modelo_relacionada = new fc_factura_relacionada(link: $this->link);
        $modelo_retencion = new fc_retenido(link: $this->link);
        $modelo_sello = new fc_cfdi_sellado(link: $this->link);
        $modelo_traslado = new fc_traslado(link: $this->link);
        $modelo_uuid_ext = new fc_uuid_fc(link: $this->link);
        $modelo_entidad = new fc_factura(link: $this->link);
        $modelo_notificacion = new fc_notificacion(link: $this->link);
        $modelo_email = new fc_email(link: $this->link);




        foreach ($fc_facturas as $fc_factura_id){

            $genera_pdf = (new _doctos())->pdf(modelo_documento: $modelo_documento,modelo_entidad:  $modelo_entidad,
                modelo_partida: $modelo_partida,modelo_predial:  $modelo_predial,
                modelo_relacion: $modelo_relacion,modelo_relacionada:  $modelo_relacionada,
                modelo_retencion:  $modelo_retencion,modelo_sello:  $modelo_sello,
                modelo_traslado:  $modelo_traslado, modelo_uuid_ext: $modelo_uuid_ext,
                row_entidad_id: $fc_factura_id);

            if(errores::$error){
                return $this->retorno_error(mensaje: 'Error al generar pdf',data:  $genera_pdf, header: $header,ws:$ws);
            }

            $inserta_notificacion = $modelo_entidad->inserta_notificacion(modelo_doc: $modelo_documento,
                modelo_email: $modelo_email, modelo_notificacion: $modelo_notificacion, registro_id: $fc_factura_id);
            if(errores::$error){
                return $this->retorno_error(mensaje: 'Error al insertar notificacion',data:  $inserta_notificacion, header: $header,ws:$ws);
            }


            $envia_notificacion = $modelo_entidad->envia_factura(modelo_notificacion: $modelo_notificacion, registro_id: $fc_factura_id);
            if(errores::$error){
                return $this->retorno_error(mensaje: 'Error al enviar notificacion',data:  $envia_notificacion, header: $header,ws:$ws);
            }

        }

        if(isset($_GET['accion_retorno'])){
            $siguiente_view = $_GET['accion_retorno'];
        }
        else{
            $siguiente_view = (new actions())->init_alta_bd(siguiente_view: 'facturas');
            if(errores::$error){

                return $this->retorno_error(mensaje: 'Error al obtener siguiente view', data: $siguiente_view,
                    header:  $header, ws: $ws);
            }
        }
        $seccion_retorno = $this->tabla;
        if(isset($_GET['seccion_retorno'])){
            $seccion_retorno = $_GET['seccion_retorno'];
        }
        $id_retorno = $this->registro_id;
        if(isset($_GET['id_retorno'])){
            $id_retorno = $_GET['id_retorno'];
        }

        $header_retorno = $this->header_retorno(accion: $siguiente_view, seccion: $seccion_retorno, id_retorno: $id_retorno);
        if(errores::$error){

            return $this->retorno_error(mensaje: 'Error al maquetar retorno', data: $header_retorno,
                header:  $header, ws: $ws);
        }

        if($header){
            header('Location:' . $header_retorno);
            exit;
        }
        if($ws){
            header('Content-Type: application/json');
            try {
                echo json_encode($fc_facturas, JSON_THROW_ON_ERROR);
            }
            catch (Throwable $e){
                $error = $this->errores->error(mensaje: 'Error al dar salida json', data: $e);
                print_r($error);
                exit;
            }
            exit;
        }
        $fc_facturas->siguiente_view = $siguiente_view;

        return $fc_facturas;
    }

    public function facturas(bool $header, bool $ws = false): array|stdClass
    {
        $fc_ejecucion_automatica = $this->modelo->registro(registro_id: $this->registro_id,retorno_obj: true);
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener fc_ejecucion_automatica',
                data:  $fc_ejecucion_automatica,header:  $header,ws:  $ws);
        }

        $fc_factura_automaticas = $this->obten_facturas();
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener data_row', data: $fc_factura_automaticas, header: $header, ws: $ws);
        }


        $this->registros = $fc_factura_automaticas;

        $data_form = $this->data_form();
        if(errores::$error){
            return $this->retorno_error(mensaje: 'Error al obtener datos para form', data: $data_form, header: $header, ws: $ws);
        }
        $this->link_timbra = $data_form->link_timbra;
        $this->buttons['button_timbra'] = $data_form->button_timbra;
        $this->buttons['button_descarga'] = $data_form->button_descarga;
        $this->buttons['button_envia_cfdi'] = $data_form->button_envia_cfdi;

        return $fc_factura_automaticas;
    }

    private function facturas_automaticas(){
        $filtro[$this->modelo->key_filtro_id] = $this->registro_id;
        $r_fc_facturas = $this->modelo_automatico->filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener facturas', data:  $r_fc_facturas);
        }
        return $r_fc_facturas->registros;
    }

    private function fc_facturas_automaticas(array $fc_factura_automaticas): array
    {
        $controlador_fc_factura = new controlador_fc_factura(link: $this->link);
        $controlador_fc_factura->seccion = 'fc_factura';


        $fc_factura_automaticas = $this->integra_facturas(controlador_fc_factura: $controlador_fc_factura,fc_factura_automaticas:  $fc_factura_automaticas);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener data_row', data: $fc_factura_automaticas);
        }
        return $fc_factura_automaticas;
    }

    private function input_chk(array $fc_factura): string
    {
        return "<input type='checkbox' value='$fc_factura[fc_factura_id]' name='fc_facturas_id[]' class='fc_factura_chk'>";
    }

    private function integra_facturas(controlador_fc_factura $controlador_fc_factura,array $fc_factura_automaticas): array
    {
        foreach ($fc_factura_automaticas as $indice=>$fc_factura){

            $fc_factura_automaticas = $this->integra_fc_factura(controlador_fc_factura: $controlador_fc_factura,
                fc_factura:  $fc_factura, fc_factura_automaticas: $fc_factura_automaticas,indice:  $indice);
            if(errores::$error){
                return $this->errores->error(mensaje: 'Error al obtener data_row', data: $fc_factura_automaticas);
            }
        }
        return $fc_factura_automaticas;
    }

    private function integra_fc_factura(controlador_fc_factura $controlador_fc_factura, array $fc_factura,
                                                array $fc_factura_automaticas, int $indice): array
    {
        $controlador_fc_factura->registro = $fc_factura;

        $data_row = $this->data_rows_fc(controlador_fc_factura: $controlador_fc_factura,fc_factura:  $fc_factura);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener data_row', data: $data_row);
        }

        $fc_factura_automaticas[$indice]['fc_factura_acciones'] = $data_row->buttons_html;
        $fc_factura_automaticas[$indice]['fc_factura_selecciona'] = $data_row->input_chk;
        return $fc_factura_automaticas;
    }

    private function obten_facturas(): array
    {
        $fc_factura_automaticas = $this->facturas_automaticas();
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener facturas', data:  $fc_factura_automaticas);
        }

        $fc_factura_automaticas = $this->fc_facturas_automaticas(fc_factura_automaticas: $fc_factura_automaticas);
        if(errores::$error){
            return $this->errores->error(mensaje: 'Error al obtener data_row', data: $fc_factura_automaticas);
        }
        return $fc_factura_automaticas;
    }

    public function timbra(bool $header, bool $ws = false){

        if(!isset($_POST['fc_facturas_id'])){
            $_POST['fc_facturas_id'] = array();
        }
        $fc_facturas = $_POST['fc_facturas_id'];
        if(count($fc_facturas) === 0){
            $facturas = $this->facturas_automaticas();

            foreach ($facturas as $fc_factura){
                $fc_facturas[] = $fc_factura['fc_factura_id'];
            }

        }

        $modelo_documento = new fc_factura_documento(link: $this->link);
        $modelo_etapa = new fc_factura_etapa(link: $this->link);
        $modelo_partida = new fc_partida(link: $this->link);
        $modelo_predial = new fc_cuenta_predial(link: $this->link);
        $modelo_relacion = new fc_relacion(link: $this->link);
        $modelo_relacionada = new fc_factura_relacionada(link: $this->link);
        $modelo_retencion = new fc_retenido(link: $this->link);
        $modelo_sello = new fc_cfdi_sellado(link: $this->link);
        $modelo_traslado = new fc_traslado(link: $this->link);
        $modelo_uuid_ext = new fc_uuid_fc(link: $this->link);




        foreach ($fc_facturas as $fc_factura_id){

            $fc_factura = (new fc_factura(link: $this->link))->registro(registro_id: $fc_factura_id);
            if(errores::$error){
                return $this->retorno_error(mensaje: 'Error al obtener factura',data:  $fc_factura,header:  $header,ws:  $ws);
            }

            if($fc_factura['fc_factura_etapa'] !=='TIMBRADO'){
                $this->link->beginTransaction();
                $timbra = (new fc_factura(link: $this->link))->timbra_xml(modelo_documento: $modelo_documento,modelo_etapa:  $modelo_etapa,
                    modelo_partida: $modelo_partida,modelo_predial:  $modelo_predial,modelo_relacion:  $modelo_relacion,
                    modelo_relacionada:  $modelo_relacionada,modelo_retencion:  $modelo_retencion,
                    modelo_sello: $modelo_sello,modelo_traslado:  $modelo_traslado,modelo_uuid_ext:  $modelo_uuid_ext,registro_id:  $fc_factura_id);

                if(errores::$error){
                    $this->link->rollBack();
                    return $this->retorno_error(mensaje: 'Error al timbrar',data:  $timbra,header:  $header,ws:  $ws);
                }
                $this->link->commit();
            }

        }

        if(isset($_GET['accion_retorno'])){
            $siguiente_view = $_GET['accion_retorno'];
        }
        else{
            $siguiente_view = (new actions())->init_alta_bd(siguiente_view: 'facturas');
            if(errores::$error){

                return $this->retorno_error(mensaje: 'Error al obtener siguiente view', data: $siguiente_view,
                    header:  $header, ws: $ws);
            }
        }
        $seccion_retorno = $this->tabla;
        if(isset($_GET['seccion_retorno'])){
            $seccion_retorno = $_GET['seccion_retorno'];
        }
        $id_retorno = $this->registro_id;
        if(isset($_GET['id_retorno'])){
            $id_retorno = $_GET['id_retorno'];
        }

        $header_retorno = $this->header_retorno(accion: $siguiente_view, seccion: $seccion_retorno, id_retorno: $id_retorno);
        if(errores::$error){

            return $this->retorno_error(mensaje: 'Error al maquetar retorno', data: $header_retorno,
                header:  $header, ws: $ws);
        }

        if($header){
            header('Location:' . $header_retorno);
            exit;
        }
        if($ws){
            header('Content-Type: application/json');
            try {
                echo json_encode($fc_facturas, JSON_THROW_ON_ERROR);
            }
            catch (Throwable $e){
                $error = $this->errores->error(mensaje: 'Error al dar salida json', data: $e);
                print_r($error);
                exit;
            }
            exit;
        }
        $fc_facturas->siguiente_view = $siguiente_view;

        return $fc_facturas;
    }



}
