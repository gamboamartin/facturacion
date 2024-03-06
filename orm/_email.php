<?php
namespace gamboamartin\facturacion\models;
use base\orm\modelo;
use gamboamartin\errores\errores;
use gamboamartin\notificaciones\models\not_adjunto;
use gamboamartin\notificaciones\models\not_emisor;
use gamboamartin\notificaciones\models\not_mensaje;
use gamboamartin\notificaciones\models\not_receptor;
use gamboamartin\notificaciones\models\not_rel_mensaje;
use gamboamartin\validacion\validacion;
use PDO;
use stdClass;

class _email{
    private errores $error;
    private validacion $validacion;

    public function __construct()
    {
        $this->error = new errores();
        $this->validacion = new validacion();
    }

    /**
     * Genera el asunto de un mensaje para notificaciones
     * @param stdClass $row_entidad Registro de la entidad a integrar asunto
     * @param string $uuid Identificador del SAT
     * @return string|array
     */
    private function asunto(stdClass $row_entidad, string $uuid): string|array
    {
        $keys = array('org_empresa_razon_social','org_empresa_rfc');
        $valida = $this->validacion->valida_existencia_keys(keys: $keys,registro:  $row_entidad);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar row_entidad',data:  $valida);
        }
        $uuid = trim($uuid);
        if($uuid === ''){
            return $this->error->error(mensaje: 'Error uuid esta vacio',data:  $uuid);
        }

        $asunto = "CFDI de $row_entidad->org_empresa_razon_social RFC: $row_entidad->org_empresa_rfc Folio: ";
        $asunto .= "$uuid";
        return $asunto;
    }

    private function com_emails_ctes(stdClass $registro_fc, PDO $link){
        $filtro = array();
        $filtro['com_cliente.id'] = $registro_fc->com_cliente_id;
        $filtro['com_email_cte.status'] = 'activo';

        $r_com_email_cte = (new com_email_cte(link: $link))->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener correos', data: $r_com_email_cte);
        }

        return $r_com_email_cte->registros;
    }

    final public function crear_notificaciones(_doc $modelo_doc, _data_mail $modelo_email,
                                               _transacciones_fc $modelo_entidad, _notificacion $modelo_notificacion,
                                               PDO $link, int $registro_entidad_id){

        $row_entidad = $modelo_entidad->registro(registro_id: $registro_entidad_id, retorno_obj: true);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener factura', data: $row_entidad);
        }
        $key_uuid = $modelo_entidad->tabla.'_uuid';
        $uuid = $row_entidad->$key_uuid;

        $not_mensaje_id = $this->inserta_mensaje(link: $link, modelo_notificacion: $modelo_notificacion,
            name_entidad_modelo: $modelo_entidad->tabla, row_entidad: $row_entidad, uuid: $uuid);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al insertar mensaje', data: $not_mensaje_id);
        }

        $r_not_rel_mensaje = $this->inserta_rels_mesajes(link: $link,modelo_email:  $modelo_email,
            name_entidad_modelo:  $modelo_entidad->tabla,not_mensaje_id:  $not_mensaje_id,
            registro_entidad_id: $registro_entidad_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al insertar relacion de mensaje', data: $r_not_rel_mensaje);
        }



        $r_not_adjunto = $this->inserta_adjuntos(modelo_doc: $modelo_doc,modelo_entidad:  $modelo_entidad,
            registro_id:  $registro_entidad_id,row_entidad:  $row_entidad,not_mensaje_id:  $not_mensaje_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al insertar adjunto', data: $r_not_adjunto);
        }


        $data = new stdClass();
        $data->row_entidad = $row_entidad;
        $data->not_mensaje_id = $not_mensaje_id;
        $data->r_not_rel_mensaje = $r_not_rel_mensaje;
        $data->r_not_adjunto = $r_not_adjunto;
        return $data;
    }

    private function data_email(string $name_entidad_modelo, stdClass $row_entidad, string $uuid){
        $asunto = $this->asunto(row_entidad: $row_entidad, uuid: $uuid);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al generar asunto', data: $asunto);

        }

        $mensaje = $this->mensaje(asunto: $asunto, name_entidad_modelo: $name_entidad_modelo, row_entidad: $row_entidad);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al generar asunto', data: $asunto);
        }

        $data = new stdClass();
        $data->asunto = $asunto;
        $data->mensaje = $mensaje;

        return $data;
    }

    private function documentos(_doc $modelo_doc, _transacciones_fc $modelo_entidad, int $registro_id){
        $filtro = array();
        $filtro[$modelo_entidad->key_filtro_id] = $registro_id;

        $r_fc_factura_documento = $modelo_doc->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener documentos', data: $r_fc_factura_documento);
        }

       return $r_fc_factura_documento->registros;
    }

    final public function envia_factura( string $key_filter_entidad_id, PDO $link, _notificacion $modelo_notificacion, int $registro_id){
        $fc_notificaciones = $this->get_notificaciones(key_filter_entidad_id: $key_filter_entidad_id, link: $link,
            registro_id: $registro_id, modelo_notificacion: $modelo_notificacion);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener r_fc_notificacion',data:  $fc_notificaciones);
        }
        $n_notificaciones_enviadas = 0;
        foreach ($fc_notificaciones as $fc_notificacion){
            $notifica = $this->notifica(fc_notificacion:  $fc_notificacion,link: $link);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al enviar notificacion',data:  $notifica);
            }
            if(!is_bool($notifica) && $notifica!==false){
                $n_notificaciones_enviadas++;
            }


        }
        if($n_notificaciones_enviadas === 0){
            return $this->error->error(mensaje: 'Error no existen notificaciones por enviar',data:  $n_notificaciones_enviadas);
        }
        return $fc_notificaciones;
    }

    private function existe_receptor(array $com_email_cte, PDO $link){
        $com_email_cte_descripcion = $com_email_cte['com_email_cte_descripcion'];
        $filtro = array();
        $filtro['not_receptor.email'] = $com_email_cte_descripcion;
        $existe_not_receptor = (new not_receptor(link: $link))->existe(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener receptor', data: $existe_not_receptor);
        }
        return $existe_not_receptor;
    }

    private function fc_email_ins(array $com_email_cte, string $key_fc_id, stdClass $registro_fc): array
    {
        $keys = array($key_fc_id);
        $valida = $this->validacion->valida_ids(keys: $keys,registro:  $registro_fc);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar registro', data: $valida);
        }

        $keys = array('com_email_cte_id');
        $valida = $this->validacion->valida_ids(keys: $keys,registro:  $com_email_cte);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar factura', data: $valida);
        }

        $keys = array('com_email_cte_status');
        $valida = $this->validacion->valida_statuses(keys: $keys,registro:  $com_email_cte);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar factura', data: $valida);
        }

        $fc_email[$key_fc_id] = $registro_fc->$key_fc_id;
        $fc_email['com_email_cte_id'] = $com_email_cte['com_email_cte_id'];
        $fc_email['status'] = $com_email_cte['com_email_cte_status'];
        return $fc_email;
    }

    private function fc_emails(_data_mail $modelo_email, string $name_entidad_modelo,
                               int $registro_entidad_id){
        $filtro[$name_entidad_modelo.'.id'] = $registro_entidad_id;
        $filtro[$modelo_email->tabla.'.status'] = 'activo';
        $r_fc_email = $modelo_email->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener receptores de correo', data: $r_fc_email);
        }

        if($r_fc_email->n_registros === 0){
            return $this->error->error(mensaje: 'Error  no hay receptores de correo', data: $r_fc_email);
        }
        return $r_fc_email->registros;
    }

    final public function genera_documentos(_doc $modelo_doc, _transacciones_fc $modelo_entidad, int $registro_id){

        $fc_factura_documentos = $this->documentos(modelo_doc: $modelo_doc, modelo_entidad: $modelo_entidad, registro_id: $registro_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener documentos', data: $fc_factura_documentos);
        }


        $docs = $this->maqueta_documentos(_fc_documentos: $fc_factura_documentos);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener documentos', data: $fc_factura_documentos);
        }
        return $docs;
    }

    private function genera_not_mensaje_ins( PDO $link, string $name_entidad_modelo, stdClass $row_entidad, string $uuid){
        $data_mensaje = $this->data_email(name_entidad_modelo: $name_entidad_modelo, row_entidad: $row_entidad, uuid: $uuid);
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

    final public function get_not_receptor_id(array $com_email_cte, PDO $link){
        $existe_not_receptor = $this->existe_receptor(com_email_cte:  $com_email_cte,link: $link);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener receptor', data: $existe_not_receptor);
        }
        if(!$existe_not_receptor){
            $not_receptor_id = $this->inserta_receptor(com_email_cte: $com_email_cte,link:  $link);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al insertar receptor', data: $not_receptor_id);
            }
        }
        else{
            $not_receptor_id = $this->not_receptor_id(com_email_cte: $com_email_cte, link: $link);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al obtener receptor', data: $not_receptor_id);
            }
        }
        return $not_receptor_id;
    }

    /**
     * Obtiene las notificaciones de una factura
     * @param string $key_filter_entidad_id
     * @param PDO $link Conexion a la base de datos
     * @param int $registro_id Factura a obtener notificaciones
     * @param _notificacion $modelo_notificacion
     * @return array
     */
    private function get_notificaciones(string $key_filter_entidad_id, PDO $link, int $registro_id, _notificacion $modelo_notificacion): array
    {
        $filtro[$key_filter_entidad_id] = $registro_id;
        $r_fc_notificacion = $modelo_notificacion->filtro_and(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener r_fc_notificacion',data:  $r_fc_notificacion);
        }
        if($r_fc_notificacion->n_registros === 0){
            return $this->error->error(mensaje: 'Error no hay notificaciones asignadas',data:  $r_fc_notificacion);
        }
        return $r_fc_notificacion->registros;
    }

    private function inserta_adjunto(array $doc, string $key_folio, stdClass $row_entidad, int $not_mensaje_id, PDO $link){

        $not_adjunto_ins['not_mensaje_id'] = $not_mensaje_id;
        $not_adjunto_ins['doc_documento_id'] = $doc['doc_documento_id'];
        $not_adjunto_ins['descripcion'] = $row_entidad->$key_folio.'.'.date('YmdHis').mt_rand(10000,99999).
            '.'.$doc['doc_extension_descripcion'];

        $not_adjunto_ins['name_out'] =  $doc['doc_documento_name_out'];
        if($doc['doc_tipo_documento_descripcion'] !=='ADJUNTO') {
            $not_adjunto_ins['name_out'] = $row_entidad->$key_folio . '.' . $doc['doc_extension_descripcion'];
        }

        $r_not_adjunto = (new not_adjunto(link: $link))->alta_registro(registro: $not_adjunto_ins);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al insertar adjunto', data: $r_not_adjunto);
        }
        return $r_not_adjunto;
    }

    private function inserta_adjuntos(_doc $modelo_doc, _transacciones_fc $modelo_entidad, int $registro_id,stdClass $row_entidad,  int $not_mensaje_id){
        $adjuntos = array();
        $docs = $this->genera_documentos(modelo_doc: $modelo_doc,modelo_entidad:  $modelo_entidad,registro_id:  $registro_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener documentos', data: $docs);
        }

        foreach ($docs as $doc){
            $key_folio = $modelo_entidad->tabla.'_folio';

            $r_not_adjunto = $this->inserta_adjunto(doc: $doc, key_folio: $key_folio,
                row_entidad: $row_entidad, not_mensaje_id: $not_mensaje_id, link: $modelo_entidad->link);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al insertar adjunto', data: $r_not_adjunto);
            }
            $adjuntos[] = $r_not_adjunto;
        }
        return $adjuntos;
    }

    private function inserta_fc_email(array $com_email_cte, string $key_fc_id,modelo $modelo_email, stdClass $registro_fc){
        $fc_email_ins = $this->fc_email_ins(com_email_cte: $com_email_cte, key_fc_id: $key_fc_id,
            registro_fc: $registro_fc);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener fc_email_ins', data: $fc_email_ins);
        }

        //print_r($fc_email_ins);exit;

        $r_alta_fc_email = $modelo_email->alta_registro(registro: $fc_email_ins);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al insertar correos', data: $r_alta_fc_email);
        }
        return $r_alta_fc_email;
    }

    final public function inserta_fc_emails( string $key_fc_id, _data_mail $modelo_email, PDO $link, stdClass $registro_fc){
        $com_emails_ctes = $this->com_emails_ctes(registro_fc: $registro_fc, link: $link);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener correos', data: $com_emails_ctes);
        }

        foreach ($com_emails_ctes as $com_email_cte){
            $r_alta_fc_email = $this->inserta_fc_email(com_email_cte: $com_email_cte, key_fc_id: $key_fc_id,
                modelo_email: $modelo_email, registro_fc: $registro_fc);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al insertar correos', data: $r_alta_fc_email);
            }
        }
        return $com_emails_ctes;
    }

    private function inserta_mensaje(PDO $link, _notificacion $modelo_notificacion, string $name_entidad_modelo,
                                     stdClass $row_entidad, string $uuid){
        $not_mensaje_ins = $this->genera_not_mensaje_ins(link: $link, name_entidad_modelo: $name_entidad_modelo,
            row_entidad: $row_entidad, uuid: $uuid);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener emisor', data: $not_mensaje_ins);
        }

        $r_not_mensaje = (new not_mensaje(link: $link))->alta_registro(registro: $not_mensaje_ins);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al insertar mensaje', data: $r_not_mensaje);
        }

        $key_entidad_id = $name_entidad_modelo.'_id';

        $fc_notificacion_ins[$key_entidad_id] = $row_entidad->$key_entidad_id;
        $fc_notificacion_ins['not_mensaje_id'] = $r_not_mensaje->registro_id;

        $r_fc_notificacion = $modelo_notificacion->alta_registro(registro: $fc_notificacion_ins);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al insertar fc_notificacion_ins', data: $r_fc_notificacion);
        }


        return $r_not_mensaje->registro_id;
    }

    private function inserta_receptor(array $com_email_cte, PDO $link){
        $not_receptor_ins['email'] = $com_email_cte['com_email_cte_descripcion'];
        $r_not_receptor = (new not_receptor(link: $link))->alta_registro(registro: $not_receptor_ins);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al insertar receptor', data: $r_not_receptor);
        }

        $filtro['not_receptor.id'] = $r_not_receptor->registro_id;
        $filtro['com_email_cte.id'] = $com_email_cte['com_email_cte_id'];
        $existe_fc_receptor_email = (new fc_receptor_email(link: $link))->existe(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al validar si existe', data: $existe_fc_receptor_email);
        }
        if(!$existe_fc_receptor_email) {

            $fc_receptor_email_ins['not_receptor_id'] = $r_not_receptor->registro_id;
            $fc_receptor_email_ins['com_email_cte_id'] = $com_email_cte['com_email_cte_id'];
            $r_fc_receptor_email = (new fc_receptor_email(link: $link))->alta_registro(registro: $fc_receptor_email_ins);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al insertar fc_receptor_email_ins', data: $r_fc_receptor_email);
            }
        }

        return $r_not_receptor->registro_id;
    }

    private function inserta_rel_mensaje(array $com_email_cte, PDO $link, int $not_mensaje_id){
        $not_receptor_id = $this->get_not_receptor_id(com_email_cte: $com_email_cte,link:  $link);
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

    private function inserta_rels_mesajes(PDO $link, _data_mail $modelo_email, string $name_entidad_modelo,
                                          int $not_mensaje_id, int $registro_entidad_id){
        $rels = array();
        $fc_emails = $this->fc_emails(modelo_email: $modelo_email, name_entidad_modelo: $name_entidad_modelo,
            registro_entidad_id: $registro_entidad_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener receptores de correo', data: $fc_emails);
        }
        foreach ($fc_emails as $fc_email){
            $r_not_rel_mensaje = $this->inserta_rel_mensaje(com_email_cte: $fc_email,link:  $link,not_mensaje_id:  $not_mensaje_id);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al insertar relacion de mensaje', data: $r_not_rel_mensaje);
            }
            $rels[] = $r_not_rel_mensaje;
        }
        return $rels;
    }

    /**
     * POR DOCUMENTAR EN WIKI
     * La función maqueta_documentos se encarga de formar un nuevo array con los documentos que cumplen con los
     * tipos de documentos especificados.
     *
     * @param array $_fc_documentos Array de Documentos a ser filtrado para la maqueta.
     * Los Documentos deben tener la llave 'doc_tipo_documento_descripcion' para ser evaluados.
     *
     * @return array Retorna un array con los documentos cuya 'doc_tipo_documento_descripcion'
     * coincide con alguno de los tipos de documentos especificados ('xml_sin_timbrar', 'CFDI PDF', 'ADJUNTO').
     * Si se encuentra alguna inconsistencia con los datos de entrada, retorna un array con la descripción del error.
     * @version 27.19.0
     */
    private function maqueta_documentos(array $_fc_documentos): array
    {

        $tipos_doc = array('xml_sin_timbrar','CFDI PDF','ADJUNTO');
        $docs = array();
        foreach ($_fc_documentos as $_fc_documento){
            if(!is_array($_fc_documento)){
                return $this->error->error(mensaje: 'Error _fc_documento debe ser array', data: $_fc_documento);
            }
            $keys = array('doc_tipo_documento_descripcion');
            $valida = $this->validacion->valida_existencia_keys(keys: $keys,registro:  $_fc_documento);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al validar _fc_documento', data: $valida);
            }
            if(in_array($_fc_documento['doc_tipo_documento_descripcion'], $tipos_doc)){
                $docs[] = $_fc_documento;
            }
        }
        return $docs;
    }

    /**
     * Integra el mensaje de envio de una factura
     * @param string $asunto Asunto de correo
     * @param string $name_entidad_modelo Nombre del modelo base facturacion complemento nota etc
     * @param stdClass $row_entidad Registro a integrar datos
     * @return string
     */
    private function mensaje(string $asunto, string $name_entidad_modelo, stdClass $row_entidad): string
    {
        $key_total = $name_entidad_modelo.'_total';
        return "Buen día se envia $asunto por un Total de: ".$row_entidad->$key_total;
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

    private function not_receptor_id(array $com_email_cte, PDO $link){
        $com_email_cte_descripcion = $com_email_cte['com_email_cte_descripcion'];
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

    private function notifica(array $fc_notificacion, PDO $link){
        $not_mensaje = (new not_mensaje(link: $link))->registro(registro_id: $fc_notificacion['not_mensaje_id']);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener mensaje',data:  $not_mensaje);
        }

        /**
         * crear data conf para validar ENVIADO
         */
        if(is_null($not_mensaje['not_mensaje_etapa'])){
            return false;
        }
        if(trim($not_mensaje['not_mensaje_etapa']) === ''){
            return false;
        }
        if($not_mensaje['not_mensaje_etapa'] === 'ENVIADO'){
            return false;
        }

        $notifica = (new not_mensaje(link: $link))->envia_mensaje($fc_notificacion['not_mensaje_id']);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al notificar',data:  $notifica);
        }
        return $notifica;
    }
}
