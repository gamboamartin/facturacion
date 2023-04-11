<?php
namespace gamboamartin\facturacion\models;
use gamboamartin\errores\errores;
use gamboamartin\notificaciones\models\not_adjunto;
use gamboamartin\notificaciones\models\not_emisor;
use gamboamartin\notificaciones\models\not_mensaje;
use gamboamartin\notificaciones\models\not_receptor;
use gamboamartin\notificaciones\models\not_rel_mensaje;
use PDO;
use stdClass;

class _email{
    private errores $error;

    public function __construct()
    {
        $this->error = new errores();
    }

    /**
     * Genera el asunto de un mensaje para notificaciones
     * @param stdClass $fc_factura Factura a enviar
     * @return string
     */
    private function asunto(stdClass $fc_factura): string
    {
        $asunto = "Factura de $fc_factura->org_empresa_razon_social RFC: $fc_factura->org_empresa_rfc Folio: ";
        $asunto .= "$fc_factura->fc_factura_uuid";
        return $asunto;
    }

    final public function crear_notificaciones(int $fc_factura_id, PDO $link){
        $fc_factura = (new fc_factura(link: $link))->registro(registro_id: $fc_factura_id, retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener factura', data: $fc_factura);
        }

        $not_mensaje_id = $this->inserta_mensaje(fc_factura: $fc_factura,link:  $link);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al insertar mensaje', data: $not_mensaje_id);
        }

        $r_not_rel_mensaje = $this->inserta_rels_mesajes(fc_factura_id: $fc_factura_id,link:  $link, not_mensaje_id:  $not_mensaje_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al insertar relacion de mensaje', data: $r_not_rel_mensaje);
        }


        $r_not_adjunto = $this->inserta_adjuntos(fc_factura: $fc_factura,fc_factura_id:  $fc_factura_id,link:  $link,not_mensaje_id:  $not_mensaje_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al insertar adjunto', data: $r_not_adjunto);
        }

        $data = new stdClass();
        $data->fc_factura = $fc_factura;
        $data->not_mensaje_id = $not_mensaje_id;
        $data->r_not_rel_mensaje = $r_not_rel_mensaje;
        $data->r_not_adjunto = $r_not_adjunto;
        return $data;
    }

    private function data_email(stdClass $fc_factura){
        $asunto = $this->asunto(fc_factura: $fc_factura);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al generar asunto', data: $asunto);

        }

        $mensaje = $this->mensaje(asunto: $asunto,fc_factura: $fc_factura);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al generar asunto', data: $asunto);
        }

        $data = new stdClass();
        $data->asunto = $asunto;
        $data->mensaje = $mensaje;

        return $data;
    }

    private function documentos(int $fc_factura_id, PDO $link){
        $filtro = array();
        $filtro['fc_factura.id'] = $fc_factura_id;

        $r_fc_factura_documento = (new fc_factura_documento(link: $link))->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener documentos', data: $r_fc_factura_documento);
        }

       return $r_fc_factura_documento->registros;
    }

    private function existe_receptor(array $fc_email, PDO $link){
        $com_email_cte_descripcion = $fc_email['com_email_cte_descripcion'];
        $filtro = array();
        $filtro['not_receptor.email'] = $com_email_cte_descripcion;
        $existe_not_receptor = (new not_receptor(link: $link))->existe(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener receptor', data: $existe_not_receptor);
        }
        return $existe_not_receptor;
    }

    private function fc_emails(int $fc_factura_id, PDO $link){
        $filtro['fc_factura.id'] = $fc_factura_id;
        $r_fc_email = (new fc_email(link: $link))->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener receptores de correo', data: $r_fc_email);
        }

        if($r_fc_email->n_registros === 0){
            return $this->error->error(mensaje: 'Error  no hay receptores de correo', data: $r_fc_email);
        }
        return $r_fc_email->registros;
    }

    private function genera_documentos(int $fc_factura_id, PDO $link){
        $fc_factura_documentos = $this->documentos(fc_factura_id: $fc_factura_id,link:  $link);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener documentos', data: $fc_factura_documentos);
        }

        $docs = $this->maqueta_documentos(fc_factura_documentos: $fc_factura_documentos);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener documentos', data: $fc_factura_documentos);
        }
        return $docs;
    }

    private function genera_not_mensaje_ins(stdClass $fc_factura, PDO $link){
        $data_mensaje = $this->data_email(fc_factura: $fc_factura);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al generar asunto', data: $data_mensaje);
        }

        $not_emisor = $this->not_emisor(link: $link);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener emisor', data: $not_emisor);
        }

        $not_mensaje_ins = $this->not_mensaje_ins(data_mensaje: $data_mensaje,not_emisor:  $not_emisor);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener emisor', data: $not_emisor);
        }
        return $not_mensaje_ins;
    }

    private function get_not_receptor_id(array $fc_email, PDO $link){
        $existe_not_receptor = $this->existe_receptor(fc_email:  $fc_email,link: $link);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener receptor', data: $existe_not_receptor);
        }
        if(!$existe_not_receptor){
            $not_receptor_id = $this->inserta_receptor(fc_email: $fc_email,link:  $link);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al insertar receptor', data: $not_receptor_id);
            }
        }
        else{
            $not_receptor_id = $this->not_receptor_id(fc_email: $fc_email, link: $link);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al obtener receptor', data: $not_receptor_id);
            }
        }
        return $not_receptor_id;
    }

    private function inserta_adjunto(array $doc, stdClass $fc_factura, int $not_mensaje_id, PDO $link){
        $not_adjunto_ins['not_mensaje_id'] = $not_mensaje_id;
        $not_adjunto_ins['doc_documento_id'] = $doc['doc_documento_id'];
        $not_adjunto_ins['descripcion'] = $fc_factura->fc_factura_folio.'.'.date('YmdHis').'.'.$doc['doc_extension_descripcion'];
        $r_not_adjunto = (new not_adjunto(link: $link))->alta_registro(registro: $not_adjunto_ins);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al insertar adjunto', data: $r_not_adjunto);
        }
        return $r_not_adjunto;
    }

    private function inserta_adjuntos(stdClass $fc_factura, int $fc_factura_id, PDO $link,  int $not_mensaje_id){
        $adjuntos = array();
        $docs = $this->genera_documentos(fc_factura_id: $fc_factura_id,link:  $link);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener documentos', data: $docs);
        }
        foreach ($docs as $doc){
            $r_not_adjunto = $this->inserta_adjunto(doc: $doc,fc_factura:  $fc_factura,not_mensaje_id:  $not_mensaje_id,link:  $link);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al insertar adjunto', data: $r_not_adjunto);
            }
            $adjuntos[] = $r_not_adjunto;
        }
        return $adjuntos;
    }

    private function inserta_mensaje(stdClass $fc_factura, PDO $link){
        $not_mensaje_ins = $this->genera_not_mensaje_ins(fc_factura: $fc_factura,link:  $link);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener emisor', data: $not_mensaje_ins);
        }

        $r_not_mensaje = (new not_mensaje(link: $link))->alta_registro(registro: $not_mensaje_ins);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al insertar mensaje', data: $r_not_mensaje);
        }

        return $r_not_mensaje->registro_id;
    }

    private function inserta_receptor(array $fc_email, PDO $link){
        $not_receptor_ins['email'] = $fc_email['com_email_cte_descripcion'];
        $r_not_receptor = (new not_receptor(link: $link))->alta_registro(registro: $not_receptor_ins);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al insertar receptor', data: $r_not_receptor);
        }
        return $r_not_receptor->registro_id;
    }

    private function inserta_rel_mensaje(array $fc_email, PDO $link, int $not_mensaje_id){
        $not_receptor_id = $this->get_not_receptor_id(fc_email: $fc_email,link:  $link);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener receptor', data: $not_receptor_id);
        }

        $not_rel_mensaje_ins['not_mensaje_id'] = $not_mensaje_id;
        $not_rel_mensaje_ins['not_receptor_id'] = $not_receptor_id;
        $r_not_rel_mensaje = (new not_rel_mensaje(link: $link))->alta_registro(registro: $not_rel_mensaje_ins);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al insertar relacion de mensaje', data: $r_not_rel_mensaje);
        }
        return $r_not_rel_mensaje;
    }

    private function inserta_rels_mesajes(int $fc_factura_id, PDO $link, int $not_mensaje_id){
        $rels = array();
        $fc_emails = $this->fc_emails(fc_factura_id: $fc_factura_id ,link:  $link);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener receptores de correo', data: $fc_emails);
        }
        foreach ($fc_emails as $fc_email){
            $r_not_rel_mensaje = $this->inserta_rel_mensaje(fc_email: $fc_email,link:  $link,not_mensaje_id:  $not_mensaje_id);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al insertar relacion de mensaje', data: $r_not_rel_mensaje);
            }
            $rels[] = $r_not_rel_mensaje;
        }
        return $rels;
    }

    private function maqueta_documentos(array $fc_factura_documentos): array
    {
        $docs = array();
        foreach ($fc_factura_documentos as $fc_factura_documento){
            /**
             * Refactorizar con conf
             */
            if($fc_factura_documento['doc_tipo_documento_descripcion'] === 'xml_sin_timbrar'){
                $docs[] = $fc_factura_documento;
                break;
            }
        }
        return $docs;
    }

    private function mensaje(string $asunto, stdClass $fc_factura): string
    {
        return "Buen día se envia $asunto por un Total de: $fc_factura->fc_factura_sub_total";
    }

    private function not_emisor(PDO $link){
        $not_emisores = (new not_emisor(link: $link))->registros_activos();
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener emisor', data: $not_emisores);
        }
        $n_emisores = count($not_emisores);
        $indice = mt_rand(0,$n_emisores-1);
        return $not_emisores[$indice];

    }

    private function not_mensaje_ins(stdClass $data_mensaje, array $not_emisor): array
    {
        $not_mensaje_ins['asunto'] =  $data_mensaje->asunto;
        $not_mensaje_ins['mensaje'] =  $data_mensaje->mensaje;
        $not_mensaje_ins['not_emisor_id'] =  $not_emisor['not_emisor_id'];
        return $not_mensaje_ins;
    }

    private function not_receptor_id(array $fc_email, PDO $link){
        $com_email_cte_descripcion = $fc_email['com_email_cte_descripcion'];
        $filtro = array();
        $filtro['not_receptor.email'] = $com_email_cte_descripcion;
        $r_not_receptor = (new not_receptor(link: $link))->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener receptor', data: $r_not_receptor);
        }
        if($r_not_receptor->n_registros > 1){
            return $this->error->error(mensaje: 'Error existe mas de un receptor', data: $r_not_receptor);
        }
        if($r_not_receptor->n_registros === 0){
            return $this->error->error(mensaje: 'Error no existe receptor', data: $r_not_receptor);
        }
        return $r_not_receptor->registros[0]['not_receptor_id'];
    }
}
