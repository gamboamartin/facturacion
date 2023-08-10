<?php
namespace gamboamartin\facturacion\controllers;

use gamboamartin\documento\models\doc_documento;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\_cuenta_predial;
use gamboamartin\facturacion\models\_data_impuestos;
use gamboamartin\facturacion\models\_doc;
use gamboamartin\facturacion\models\_partida;
use gamboamartin\facturacion\models\_pdf;
use gamboamartin\facturacion\models\_relacion;
use gamboamartin\facturacion\models\_relacionada;
use gamboamartin\facturacion\models\_sellado;
use gamboamartin\facturacion\models\_transacciones_fc;
use gamboamartin\facturacion\models\_uuid_ext;
use PDO;
use stdClass;

class _doctos{

    private errores $error;
    public function __construct(){
        $this->error = new errores();
    }

    private function doc_documento_id(PDO $link, string $pdf,stdClass $row_entidad, string $tabla_fc){
        $doc_documento_ins = array();

        $file = $this->file_doc_pdf(pdf: $pdf, row_entidad: $row_entidad, tabla_fc: $tabla_fc);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al maquetar file',data:  $file);
        }

        /**
         * AJUSTAR PARA ELIMIANR HARDCODEO
         */
        $doc_documento_ins['doc_tipo_documento_id'] = 8;

        $r_doc_documento = (new doc_documento(link: $link))->alta_documento(registro: $doc_documento_ins,file: $file);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al al insertar documento',data:  $r_doc_documento);
        }
        return $r_doc_documento->registro_id;
    }

    private function fc_factura_documento_ins(int $doc_documento_id, int $registro_id, string $tabla_fc): array
    {
        $key_id = $tabla_fc.'_id';
        $fc_factura_documento_ins[$key_id] = $registro_id;
        $fc_factura_documento_ins['doc_documento_id'] = $doc_documento_id;
        return $fc_factura_documento_ins;
    }

    private function file_doc_pdf(string $pdf, stdClass $row_entidad, string $tabla_fc): array
    {
        $key_folio = $tabla_fc.'_folio';
        $file['name'] = $row_entidad->$key_folio.'.pdf';
        $file['tmp_name'] = $pdf;
        return $file;
    }

    private function genera_factura_pdf(_doc $modelo_documento, string $pdf, int $registro_id,
                                        stdClass $row_entidad, string $tabla_fc): array|stdClass
    {
        $doc_documento_id = $this->doc_documento_id(link: $modelo_documento->link, pdf: $pdf,
            row_entidad: $row_entidad, tabla_fc: $tabla_fc);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al al insertar documento',data:  $doc_documento_id);
        }

        $r_fc_factura_documento = $this->inserta_fc_factura_documento(doc_documento_id: $doc_documento_id,
            modelo_documento: $modelo_documento, registro_id: $registro_id,tabla_fc: $tabla_fc);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al al insertar factura_doc',data:  $r_fc_factura_documento);
        }
        return $r_fc_factura_documento;
    }

    private function inicializa_factura_documento(_doc $modelo_documento, string $pdf,
                                                  int $registro_id, stdClass $row_entidad, string $tabla_fc): array|stdClass
    {

        $r_fc_factura_documento = $this->init_factura_documento(modelo_documento: $modelo_documento,
            registro_id: $registro_id,tabla_fc: $tabla_fc);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al al eliminar registro',data:  $r_fc_factura_documento);
        }

        $r_fc_factura_documento = $this->genera_factura_pdf(modelo_documento: $modelo_documento, pdf: $pdf,
            registro_id: $registro_id, row_entidad: $row_entidad,tabla_fc: $tabla_fc);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al al insertar factura_doc',data:  $r_fc_factura_documento);
        }
        return $r_fc_factura_documento;
    }

    /**
     * Inicializa los elementos de la lista get data
     * @param _doc $modelo_documento Modelo de tipo documento
     * @param int $registro_id Registro de factura o nota de credito
     * @param string $tabla_fc Nombre de la entidad de facturacion
     * @return bool|array
     * @version 4.3.0
     */


    private function init_factura_documento(_doc $modelo_documento, int $registro_id, string $tabla_fc): bool|array
    {
        $filtro[$tabla_fc.'.id'] = $registro_id;
        $filtro['doc_tipo_documento.id'] = 8;

        $existe_factura_documento = $modelo_documento->existe(filtro: $filtro);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al validar si existe documento',
                data:  $existe_factura_documento);
        }

        if($existe_factura_documento){
            $r_fc_factura_documento = $modelo_documento->elimina_con_filtro_and(filtro: $filtro);
            if(errores::$error){
                return $this->error->error(mensaje: 'Error al al eliminar registro',data:  $r_fc_factura_documento);
            }
        }
        return $existe_factura_documento;
    }

    private function inserta_fc_factura_documento(int $doc_documento_id, _doc $modelo_documento, int $registro_id,
                                                  string $tabla_fc): array|stdClass
    {
        $fc_factura_documento_ins = $this->fc_factura_documento_ins(doc_documento_id: $doc_documento_id,
            registro_id: $registro_id,tabla_fc: $tabla_fc);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener fc_factura_documento_ins',data:  $fc_factura_documento_ins);
        }

        $r_fc_factura_documento = $modelo_documento->alta_registro(registro: $fc_factura_documento_ins);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al al insertar factura_doc',data:  $r_fc_factura_documento);
        }
        return $r_fc_factura_documento;
    }

    final public function pdf(_doc $modelo_documento, _transacciones_fc $modelo_entidad, _partida $modelo_partida,
                              _cuenta_predial $modelo_predial, _relacion $modelo_relacion,
                              _relacionada $modelo_relacionada, _data_impuestos $modelo_retencion,
                              _sellado $modelo_sello, _data_impuestos $modelo_traslado, _uuid_ext $modelo_uuid_ext,
                              int $row_entidad_id){

        $pdf = (new _pdf())->pdf(descarga: false, guarda: true, link: $modelo_entidad->link,
            modelo_documento: $modelo_documento, modelo_entidad: $modelo_entidad, modelo_partida: $modelo_partida,
            modelo_predial: $modelo_predial, modelo_relacion: $modelo_relacion, modelo_relacionada: $modelo_relacionada,
            modelo_retencion: $modelo_retencion, modelo_sellado: $modelo_sello, modelo_traslado: $modelo_traslado,
            modelo_uuid_ext: $modelo_uuid_ext, registro_id: $row_entidad_id);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al generar pdf',data:  $pdf);
        }

        $row_entidad = $modelo_entidad->registro(registro_id: $row_entidad_id, retorno_obj: true);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al obtener factura',data:  $row_entidad);
        }

        $r_fc_factura_documento = $this->inicializa_factura_documento(modelo_documento: $modelo_documento, pdf: $pdf,
            registro_id: $row_entidad_id, row_entidad: $row_entidad,tabla_fc: $modelo_entidad->tabla);
        if(errores::$error){
            return $this->error->error(mensaje: 'Error al al insertar factura_doc',data:  $r_fc_factura_documento);
        }
        return $r_fc_factura_documento;
    }
}
