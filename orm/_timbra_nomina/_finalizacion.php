<?php
namespace gamboamartin\facturacion\models\_timbra_nomina;


use config\generales;
use gamboamartin\documento\models\doc_documento;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\fc_cfdi_sellado_nomina;
use gamboamartin\facturacion\models\fc_empleado;
use gamboamartin\facturacion\models\fc_row_nomina;
use gamboamartin\modelo\modelo;
use PDO;
use stdClass;

class _finalizacion{

    /**
     * out
     * @param stdClass $rs
     * @param stdClass $fc_row_layout
     * @param PDO $link
     * @return true|array
     */
    final public function cfdi_exito(stdClass $rs, stdClass $fc_row_layout, PDO $link): true|array
    {
        $datos = $rs->datos;
        $datos = json_decode($datos,false);
        $xml = $datos->XML;
        $pdf = base64_decode($datos->PDF);
        $uuid = $datos->UUID;

        $rs_sellos = $this->llenar_fc_cfdi_sellado_nomina(string_xml:  $xml, fc_row_layout: $fc_row_layout, link: $link);
        if (errores::$error) {
            return (new errores())->error(mensaje: 'Error en llenar_fc_cfdi_sellado_nomina', data: $rs_sellos);
        }
        $docs = $this->subir_docs_timbre(pdf: $pdf,xml:  $xml,fc_row_layout:  $fc_row_layout, link: $link);
        if (errores::$error) {
            return (new errores())->error(mensaje: 'Error al subir docs', data: $docs);
        }
        $rs = $this->upd_exito($uuid,$link);
        if(errores::$error) {
            return (new errores())->error("Error al actualizar UUID", $rs);
        }

        $fc_empleado_modelo = new fc_empleado($link);

        $fc_empleado_upd['validado_sat'] = 'activo';
        $fc_empleado_upd['nombre_completo'] = $fc_row_layout->fc_row_layout_nombre_completo;
        $fc_empleado_upd['rfc'] = $fc_row_layout->fc_row_layout_rfc;
        $fc_empleado_upd['cp'] = $fc_row_layout->fc_row_layout_cp;
        $fc_empleado_upd['nss'] = $fc_row_layout->fc_row_layout_nss;
        $fc_empleado_upd['curp'] = $fc_row_layout->fc_row_layout_curp;
        $fc_empleado_upd['banco'] = $fc_row_layout->fc_row_layout_banco;
        $fc_empleado_upd['cuenta'] = $fc_row_layout->fc_row_layout_cuenta;
        $fc_empleado_upd['tarjeta'] = $fc_row_layout->fc_row_layout_tarjeta;

        $rs_upd = $fc_empleado_modelo->modifica_bd($fc_empleado_upd, $fc_row_layout->fc_row_layout_fc_empleado_id);
        if(errores::$error) {
            return (new errores())->error("Error al actualizar empleado", $rs_upd);
        }

        return $rs;

    }

    /**
     * OUT
     * @param string $string_xml
     * @param stdClass $fc_row_layout
     * @param PDO $link
     * @return array
     */
    private function llenar_fc_cfdi_sellado_nomina(string $string_xml, stdClass $fc_row_layout, PDO $link): array
    {// ToDo: metho aun no terminado

        $xml = simplexml_load_string($string_xml);

        // Leer atributos del comprobante
        $comprobante = $xml->attributes();
        $comprobante_sello = (string) $comprobante['Sello'];
        $comprobante_certificado = (string) $comprobante['Certificado'];
        $comprobante_no_certificado = (string) $comprobante['NoCertificado'];

        // Leer TimbreFiscalDigital (complemento)
        $xml->registerXPathNamespace('tfd', 'http://www.sat.gob.mx/TimbreFiscalDigital');
        $tfd = $xml->xpath('//tfd:TimbreFiscalDigital')[0];
        $complemento_tfd_fecha_timbrado = (string) $tfd['FechaTimbrado'];
        $complemento_tfd_no_certificado_sat = (string) $tfd['NoCertificadoSAT'];
        $complemento_tfd_rfc_prov_certif = (string) $tfd['RfcProvCertif'];
        $complemento_tfd_sello_cfd = (string) $tfd['SelloCFD'];
        $complemento_tfd_sello_sat = (string) $tfd['SelloSAT'];
        $uuid = (string) $tfd['UUID'];
        $complemento_tfd_tfd = $tfd->asXML();

        // Leer FechaPago de nómina
        $nomina = $xml->xpath('//nomina12:Nomina')[0];
        $fecha_pago = (string) $nomina['FechaPago'];

        $registro = [
            'codigo' => 'test_codigo',
            'descripcion' => 'test_descripcion',
            'fc_row_layout_id' => $fc_row_layout->fc_row_layout_id,
            'comprobante_sello' => $comprobante_sello,
            'comprobante_certificado' => $comprobante_certificado,
            'comprobante_no_certificado' => $comprobante_no_certificado,
            'complemento_tfd_fecha_timbrado' => $complemento_tfd_fecha_timbrado,
            'complemento_tfd_no_certificado_sat' => $complemento_tfd_no_certificado_sat,
            'complemento_tfd_rfc_prov_certif' => $complemento_tfd_rfc_prov_certif,
            'complemento_tfd_sello_cfd' => $complemento_tfd_sello_cfd,
            'complemento_tfd_sello_sat' => $complemento_tfd_sello_sat,
            'uuid' => $uuid,
            'complemento_tfd_tfd' => $complemento_tfd_tfd,
            'fecha_pago' => $fecha_pago,
        ];

        $fc_cfdi_sellado_nomina = new fc_cfdi_sellado_nomina($link);
        $fc_cfdi_sellado_nomina->registro = $registro;
        $result = $fc_cfdi_sellado_nomina->alta_bd();
        if(errores::$error) {
            return (new errores())->error("Error en alta_bd de fc_cfdi_sellado_nomina", $result);
        }

        return [];
    }

    /**
     * OUT
     * @param string $pdf
     * @param string $xml
     * @param stdClass $fc_row_layout
     * @param PDO $link
     * @return array|stdClass
     */
    private function subir_docs_timbre(string $pdf, string $xml, stdClass $fc_row_layout, PDO $link): array|stdClass
    {
        $result_upl_pdf = $this->subir_pdf(string_pdf: $pdf, fc_row_layout_id: $fc_row_layout->fc_row_layout_id, link: $link);
        if (errores::$error) {
            return (new errores())->error(mensaje: 'Error al subir pdf', data: $result_upl_pdf);
        }

        $result_upl_xml = $this->subir_xml(string_xml: $xml, fc_row_layout_id: $fc_row_layout->fc_row_layout_id,link:  $link);
        if (errores::$error) {
            return (new errores())->error(mensaje: 'Error al subir pdf', data: $result_upl_xml);
        }

        $docs = new stdClass();
        $docs->pdf = $result_upl_pdf;
        $docs->xml = $result_upl_xml;

        return $docs;

    }

    /**
     * OUT
     * @param string $string_pdf
     * @param int $fc_row_layout_id
     * @param PDO $link
     * @return array
     */
    private function subir_pdf(string $string_pdf, int $fc_row_layout_id, PDO $link): array
    {
        $nombre_archivo = $fc_row_layout_id.'.pdf';
        $ruta = (new generales())->path_base.'archivos/'.$nombre_archivo;

        $registro['doc_tipo_documento_id'] = 8;
        $file = array();
        $file['name'] = $nombre_archivo;
        $file['tmp_name'] = $ruta;
        file_put_contents($ruta, $string_pdf);

        $alta = (new doc_documento(link: $link))->alta_documento(registro: $registro,file: $file);
        if(errores::$error){
            return (new errores())->error('Error al insertar', $alta);


        }

        unlink($ruta);
        $doc_documento_id = $alta->registro_id;

        $fc_row_nomina_registro = [
            'doc_documento_id' => $doc_documento_id,
            'fc_row_layout_id' => $fc_row_layout_id,
        ];

        $fc_row_nomina_modelo = new fc_row_nomina($link);
        $fc_row_nomina_modelo->registro = $fc_row_nomina_registro;
        $result = $fc_row_nomina_modelo->alta_bd();
        if(errores::$error){
            return (new errores())->error('Error al insertar fc_row_nomina', $result);
        }

        return [];
    }

    /**
     * OUT
     * @param string $string_xml
     * @param int $fc_row_layout_id
     * @param PDO $link
     * @return array
     */
    private function subir_xml(string $string_xml, int $fc_row_layout_id, PDO $link): array
    {
        $nombre_archivo = $fc_row_layout_id.'.xml';
        $ruta = (new generales())->path_base.'archivos/'.$nombre_archivo;

        $registro['doc_tipo_documento_id'] = 2;
        $file = array();
        $file['name'] = $nombre_archivo;
        $file['tmp_name'] = $ruta;
        file_put_contents($ruta, $string_xml);

        $alta = (new doc_documento(link: $link))->alta_documento(registro: $registro,file: $file);
        if(errores::$error){
            return (new errores())->error('Error al insertar', $alta);


        }

        unlink($ruta);
        $doc_documento_id = $alta->registro_id;

        $fc_row_nomina_registro = [
            'doc_documento_id' => $doc_documento_id,
            'fc_row_layout_id' => $fc_row_layout_id,
        ];

        $fc_row_nomina_modelo = new fc_row_nomina($link);
        $fc_row_nomina_modelo->registro = $fc_row_nomina_registro;
        $result = $fc_row_nomina_modelo->alta_bd();
        if(errores::$error){
            return (new errores())->error('Error al insertar fc_row_nomina', $result);
        }

        return [];
    }

    /**
     * out
     * @param int $fc_layout_nom_id
     * @param PDO $link
     * @return array
     */
    final public function upd_estado_timbrado_fc_layout_nom(int $fc_layout_nom_id, PDO $link): array
    {
        $sql = "UPDATE fc_layout_nom
                SET estado_timbrado = (
                    SELECT 
                      CASE 
                        WHEN SUM(esta_timbrado = 'activo') = 0 THEN 'SIN TIMBRAR'
                        WHEN SUM(esta_timbrado = 'activo') = COUNT(*) THEN 'TIMBRADO'
                        WHEN SUM(esta_timbrado = 'activo') > 0 
                             AND SUM(esta_timbrado = 'activo') < COUNT(*) 
                             AND SUM(error <> '') > 0 THEN 'TIMBRADO CON ERROR'
                        ELSE 'TIMBRADO PARCIAL'
                      END AS estado_timbrado
                    FROM fc_row_layout
                    WHERE fc_layout_nom_id = $fc_layout_nom_id
                )
                WHERE id = $fc_layout_nom_id";
        $rs = modelo::ejecuta_transaccion($sql, $link);
        if(errores::$error) {
            return (new errores())->error("Error al actualizar estado_timbrado_fc_layout_nom", $rs);
        }
        return [];
    }

    /**
     * OUT
     * @param string $uuid
     * @param PDO $link
     * @return array|true
     */
    private function upd_exito(string $uuid, PDO $link)
    {
        $sql = "UPDATE fc_row_layout SET fc_row_layout.uuid = '$uuid', fc_row_layout.error = '', 
                         esta_timbrado = 'activo' WHERE fc_row_layout.id = $_GET[fc_row_layout_id]";
        $rs = modelo::ejecuta_transaccion($sql, $link);
        if(errores::$error) {
            return (new errores())->error("Error al actualizar UUID", $rs);

        }
        return true;

    }





}
