<?php
namespace gamboamartin\facturacion\models\_timbra_nomina;


use config\generales;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use gamboamartin\documento\models\doc_documento;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\fc_cfdi_sellado_nomina;
use gamboamartin\facturacion\models\fc_empleado;
use gamboamartin\facturacion\models\fc_row_nomina;
use gamboamartin\modelo\modelo;
use Dompdf\Dompdf;
use Dompdf\Options;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\Data\QRData;
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
        $rs = $this->upd_exito($uuid,$link,$fc_row_layout->fc_row_layout_id);
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

    final public function regenera_nomina_pdf(int $fc_row_layout_id, PDO $link): array
    {
        $ruta_nomina_xml_timbrado = (new fc_row_nomina($link))->obtener_ruta_documento(
            fc_row_layout_id: $fc_row_layout_id,
            doc_tipo_documento_id: 2
        );
        if(errores::$error) {
            return (new errores())->error("Error al obtener_ruta_documento xml", $ruta_nomina_xml_timbrado);
        }

        $ruta_nomina_pdf = (new fc_row_nomina($link))->obtener_ruta_documento(
            fc_row_layout_id: $fc_row_layout_id,
            doc_tipo_documento_id: 8
        );
        if(errores::$error) {
            return (new errores())->error("Error al obtener_ruta_documento pdf", $ruta_nomina_pdf);
        }

        $pdf_string = $this->generarReciboNomina_rutaXml(ruta_xml: $ruta_nomina_xml_timbrado);
        if(errores::$error){
            return (new errores())->error('Error al generarReciboNomina_rutaXml', $pdf_string);
        }

        file_put_contents($ruta_nomina_pdf, $pdf_string);

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
        $result_upl_pdf = $this->subir_pdf(string_pdf: $pdf, string_xml: $xml, fc_row_layout_id: $fc_row_layout->fc_row_layout_id, link: $link);
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
    private function subir_pdf(string $string_pdf, string $string_xml, int $fc_row_layout_id, PDO $link): array
    {
        $nombre_archivo = $fc_row_layout_id.'.pdf';
        $ruta = (new generales())->path_base.'archivos/'.$nombre_archivo;

        $registro['doc_tipo_documento_id'] = 8;
        $file = array();
        $file['name'] = $nombre_archivo;
        $file['tmp_name'] = $ruta;

        $pdf_string = $this->generarReciboNomina_stringXml(string_xml: $string_xml);
        if(errores::$error){
            return (new errores())->error('Error al generarReciboNomina_stringXml', $pdf_string);
        }

        file_put_contents($ruta, $pdf_string);

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


    private function upd_exito(string $uuid, PDO $link, int $fc_row_layout_id)
    {
        $sql = "UPDATE fc_row_layout SET fc_row_layout.uuid = '$uuid', fc_row_layout.error = '', 
                         esta_timbrado = 'activo' WHERE fc_row_layout.id = $fc_row_layout_id";
        $rs = modelo::ejecuta_transaccion($sql, $link);
        if(errores::$error) {
            return (new errores())->error("Error al actualizar UUID", $rs);

        }
        return true;

    }

    private function generarReciboNomina_rutaXml(string $ruta_xml): array|string|null
    {
        if (empty($ruta_xml)) {
            return (new errores())->error("La ruta del XML está vacía", $ruta_xml);
        }

        if (!is_file($ruta_xml)) {
            return (new errores())->error("No se encontró el archivo XML: $ruta_xml", $ruta_xml);
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_file($ruta_xml);
        if ($xml === false) {
            $errs = array_map(fn($e) => $e->message, libxml_get_errors());
            return (new errores())->error("Error al leer XML:\n".implode("\n", $errs), []);
        }

        $rs = $this->generarReciboNomina($xml);
        if (errores::$error) {
            return (new errores())->error("Error al generarReciboNomina", $rs);
        }

        return $rs;
    }

    private function generarReciboNomina_stringXml(string $string_xml): array|string|null
    {
        if (empty($string_xml)) {
            return (new errores())->error("El contenido del XML está vacío", $string_xml);
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($string_xml);
        if ($xml === false) {
            $errs = array_map(fn($e) => $e->message, libxml_get_errors());
            return (new errores())->error("Error al leer XML:\n".implode("\n", $errs), []);
        }

        $rs = $this->generarReciboNomina($xml);
        if (errores::$error) {
            return (new errores())->error("Error al generarReciboNomina", $rs);
        }

        return $rs;
    }

    private function generarReciboNomina($xml): array|string|null
    {

        // Namespaces
        $xml->registerXPathNamespace('cfdi', 'http://www.sat.gob.mx/cfd/4');
        $xml->registerXPathNamespace('nomina12', 'http://www.sat.gob.mx/nomina12');
        $xml->registerXPathNamespace('tfd', 'http://www.sat.gob.mx/TimbreFiscalDigital');

        // Nodos base
        $comprobante = $xml; // cfdi:Comprobante
        $emisor = $xml->xpath('cfdi:Emisor')[0] ?? null;
        $receptor = $xml->xpath('cfdi:Receptor')[0] ?? null;
        $conceptos = $xml->xpath('cfdi:Conceptos/cfdi:Concepto') ?? [];
        $timbre = $xml->xpath('cfdi:Complemento/tfd:TimbreFiscalDigital')[0] ?? null;
        $nomina = $xml->xpath('cfdi:Complemento/nomina12:Nomina')[0] ?? null;


        // Atributos Comprobante
        $compAttrs = $comprobante ? $comprobante->attributes() : null;
        $fecha = (string)($compAttrs['Fecha'] ?? '');
        $serie = (string)($compAttrs['Serie'] ?? '');
        $folio = (string)($compAttrs['Folio'] ?? '');
        $subtotal = (string)($compAttrs['SubTotal'] ?? '0');
        $descuento = (string)($compAttrs['Descuento'] ?? '0');
        $total = (string)($compAttrs['Total'] ?? '0');
        $lugarexp = (string)($compAttrs['LugarExpedicion'] ?? '');

        // Emisor/Receptor
        $em = $emisor ? $emisor->attributes() : null;
        $emNombre = (string)($em['Nombre'] ?? '');
        $emRfc = (string)($em['Rfc'] ?? '');
        $emRegimen = (string)($em['RegimenFiscal'] ?? '');

        $re = $receptor ? $receptor->attributes() : null;
        $reNombre = (string)($re['Nombre'] ?? '');
        $reRfc = (string)($re['Rfc'] ?? '');

        // Timbre
        $tf = $timbre ? $timbre->attributes() : null;
        $uuid = (string)($tf['UUID'] ?? '');
        $fechaTimbrado = (string)($tf['FechaTimbrado'] ?? '');
        $noCertSAT = (string)($tf['NoCertificadoSAT'] ?? '');
        $selloCFD = (string)($tf['SelloCFD'] ?? '');
        $selloSAT = (string)($tf['SelloSAT'] ?? '');

        // Nómina 1.2 (periodo, percepciones, deducciones)
        $no = $nomina ? $nomina->attributes() : null;
        $tipoNomina = (string)($no['TipoNomina'] ?? '');
        $fechaPago = (string)($no['FechaPago'] ?? '');
        $fechaInicialPago = (string)($no['FechaInicialPago'] ?? '');
        $fechaFinalPago = (string)($no['FechaFinalPago'] ?? '');
        $numDiasPagados = (string)($no['NumDiasPagados'] ?? '');
        $totalPercep = (string)($no['TotalPercepciones'] ?? '0');
        $totalDeduc = (string)($no['TotalDeducciones'] ?? '0');
        $totalOtrosPagos = (string)($no['TotalOtrosPagos'] ?? '0');

        // Empleado (Receptor de Nomina12:Receptor)
        $nomRec = $xml->xpath('cfdi:Complemento/nomina12:Nomina/nomina12:Receptor')[0] ?? null;
        $nr = $nomRec ? $nomRec->attributes() : null;
        $curp = (string)($nr['Curp'] ?? '');
        $numEmpleado = (string)($nr['NumEmpleado'] ?? '');

        // Percepciones
        $percepciones = [];
        $percs = $xml->xpath('cfdi:Complemento/nomina12:Nomina/nomina12:Percepciones/nomina12:Percepcion') ?? [];
        foreach ($percs as $p) {
            $a = $p->attributes();
            $percepciones[] = [
                'TipoPercepcion' => (string)$a['TipoPercepcion'],
                'Clave' => (string)$a['Clave'],
                'Concepto' => (string)$a['Concepto'],
                'ImporteGravado' => (string)$a['ImporteGravado'],
                'ImporteExento' => (string)$a['ImporteExento'],
            ];
        }

        // Deducciones
        $deducciones = [];
        $deds = $xml->xpath('cfdi:Complemento/nomina12:Nomina/nomina12:Deducciones/nomina12:Deduccion') ?? [];
        foreach ($deds as $d) {
            $a = $d->attributes();
            $deducciones[] = [
                'TipoDeduccion' => (string)$a['TipoDeduccion'],
                'Clave' => (string)$a['Clave'],
                'Concepto' => (string)$a['Concepto'],
                'Importe' => (string)$a['Importe'],
            ];
        }

        // Otros pagos (opcional)
        $otrosPagos = [];
        $ops = $xml->xpath('cfdi:Complemento/nomina12:Nomina/nomina12:OtrosPagos/nomina12:OtroPago') ?? [];
        foreach ($ops as $op) {
            $a = $op->attributes();
            $otrosPagos[] = [
                'TipoOtroPago' => (string)$a['TipoOtroPago'],
                'Clave' => (string)$a['Clave'],
                'Concepto' => (string)$a['Concepto'],
                'Importe' => (string)$a['Importe'],
            ];
        }

        /**
         * QR CFDI:
         * https://verificacfdi.facturaelectronica.sat.gob.mx/default.aspx
         * formato (CFDI 4.0): ?re=RFC_EMISOR&rr=RFC_RECEPTOR&tt=TOTAL_6DEC&id=UUID
         */

        $qrURL = 'https://verificacfdi.facturaelectronica.sat.gob.mx/default.aspx'
            . "?id=".urlencode($uuid)
            . "&re=".urlencode($emRfc)
            . "&rr=".urlencode($reRfc)
            . "&tt=". $this->formatearNumeroFijo($total)
            . "&fe=".$this->obtenerUltimosOcho($selloCFD);

        $result = Builder::create()
            ->writer(new PngWriter())
            ->data($qrURL)
            ->build();

        // --- 3. Obtiene el contenido del PNG y lo codifica en Base64
        $qrPngContent = $result->getString();
        $qrBase64 = 'data:image/png;base64,' . base64_encode($qrPngContent);

        /**
         * Genera HTML del recibo
         */
        $css = <<<CSS
        body{
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size:8px;              /* más pequeño */
            color:#111;
            margin:8px;                /* margen reducido */
        }
        h1{ font-size:11px; margin:0 0 3px; }
        h2{ font-size:9px; margin:4px 0 2px; border-bottom:1px solid #aaa; padding-bottom:1px;}
        table{ width:100%; border-collapse:collapse; margin:2px 0 4px; }
        th, td{ border:1px solid #ccc; padding:2px 3px; vertical-align:top; }
        th{ background:#eee; text-align:left; font-size:8px; }
        .right{ text-align:right; }
        .mono{ font-family:"DejaVu Sans Mono", monospace; font-size:7px; overflow-wrap:anywhere; }
        .small{ font-size:7px; color:#444; }
        .badge{ padding:1px 3px; border:1px solid #999; border-radius:3px; display:inline-block; font-size:7px;}
        .totals td{ font-weight:bold; font-size:8px; }
        CSS;

        $percepcionesRows = '';
        foreach($percepciones as $p){
            $percepcionesRows .= sprintf(
                '<tr><td>%s</td><td class="mono">%s</td><td>%s</td><td class="right">%s</td><td class="right">%s</td></tr>',
                htmlspecialchars($p['TipoPercepcion']),
                htmlspecialchars($p['Clave']),
                htmlspecialchars($p['Concepto']),
                $this->moneyMX($p['ImporteGravado']),
                $this->moneyMX($p['ImporteExento'])
            );
        }
        if($percepcionesRows===''){
            $percepcionesRows = '<tr><td colspan="5" class="small">Sin percepciones</td></tr>';
        }

        $deduccionesRows = '';
        foreach($deducciones as $d){
            $deduccionesRows .= sprintf(
                '<tr><td>%s</td><td class="mono">%s</td><td>%s</td><td class="right">%s</td></tr>',
                htmlspecialchars($d['TipoDeduccion']),
                htmlspecialchars($d['Clave']),
                htmlspecialchars($d['Concepto']),
                $this->moneyMX($d['Importe'])
            );
        }
        if($deduccionesRows===''){
            $deduccionesRows = '<tr><td colspan="4" class="small">Sin deducciones</td></tr>';
        }

        $otrosPagosRows = '';
        foreach($otrosPagos as $op){
            $otrosPagosRows .= sprintf(
                '<tr><td>%s</td><td class="mono">%s</td><td>%s</td><td class="right">%s</td></tr>',
                htmlspecialchars($op['TipoOtroPago']),
                htmlspecialchars($op['Clave']),
                htmlspecialchars($op['Concepto']),
                $this->moneyMX($op['Importe'])
            );
        }
        if($otrosPagosRows===''){
            $otrosPagosRows = '<tr><td colspan="4" class="small">Sin otros pagos</td></tr>';
        }

        // Conceptos (suele venir uno con Nómina)
        $conceptoRows = '';
        foreach($conceptos as $c){
            $a = $c->attributes();
            $qty = (string)($a['Cantidad'] ?? '1');
            $desc = 'CONVENIO MASC';
            $imp = (string)($a['Importe'] ?? '0');
            $valorUnit = (string)($a['ValorUnitario'] ?? '0');
            $conceptoRows .= sprintf(
                '<tr><td class="right">%s</td><td>%s</td><td class="right">%s</td><td class="right">%s</td></tr>',
                htmlspecialchars($qty),
                htmlspecialchars($desc),
                $this->moneyMX($valorUnit),
                $this->moneyMX($imp)
            );
        }

        $subtotalMX      =  $this->moneyMX($subtotal);
        $descuentoMX     =  $this->moneyMX($descuento);
        $totalPercepMX   =  $this->moneyMX($totalPercep);
        $totalDeducMX    =  $this->moneyMX($totalDeduc);
        $totalMX         =  $this->moneyMX($total);

        $html = <<<HTML
        <!doctype html>
        <html>
        <head>
        <meta charset="utf-8">
        <style>$css</style>
        <title>Recibo</title>
        </head>
        <body>
        <div class="wrap">
        
            <div class="row">
                <div class="col">
                    <h1>Recibo <span class="badge">CFDI 4.0</span></h1>
                    <div><strong>Serie/Folio:</strong> {$serie}{$folio}</div>
                    <div><strong>Lugar de expedición:</strong> {$lugarexp}</div>
                    <div><strong>Fecha CFDI:</strong> {$fecha}</div>
                    <div class="small" style="margin-top:8px;"><strong>UUID:</strong> <span class="mono">$uuid</span></div>
                    <div class="small"><strong>Timbrado:</strong> $fechaTimbrado</div>
                    <div class="small"><strong>Cert. SAT:</strong> <span class="mono">$noCertSAT</span></div>
                </div>
            </div>
        
            <h2>Emisor</h2>
            <table>
                <tr><th>RFC</th><th>Nombre</th><th>Régimen</th></tr>
                <tr><td class="mono">$emRfc</td><td>$emNombre</td><td class="mono">$emRegimen</td></tr>
            </table>
        
            <h2>DATOS DEL ADHERIDO</h2>
            <table>
                <tr><th>No. Adherido</th><th>RFC</th><th>Nombre</th><th>CURP</th></tr>
                <tr>
                    <td class="mono">$numEmpleado</td>
                    <td class="mono">$reRfc</td>
                    <td>$reNombre</td>
                    <td class="mono">$curp</td>
                </tr>
            </table>
        
            <h2>INFORMACIÓN DE PAGO</h2>
            <table>
                <tr><th>Fecha Pago</th><th>Inicial</th><th>Final</th></tr>
                <tr>
                    <td>$fechaPago</td>
                    <td>$fechaInicialPago</td>
                    <td>$fechaFinalPago</td>
                </tr>
            </table>
        
            <h2>Conceptos (CFDI)</h2>
            <table>
                <tr><th>Cantidad</th><th>Descripción</th><th>Valor Unitario</th><th>Importe</th></tr>
                $conceptoRows
            </table>
        
            <h2>Percepciones</h2>
            <table>
                <tr><th>Tipo</th><th>Clave</th><th>Concepto</th><th>Gravado</th><th>Exento</th></tr>
                $percepcionesRows
            </table>
        
            <h2>Deducciones</h2>
            <table>
                <tr><th>Tipo</th><th>Clave</th><th>Concepto</th><th>Importe</th></tr>
                $deduccionesRows
            </table>
        
            <table class="totals">
                <tr><td class="right">Subtotal</td><td class="right" style="width:150px;">\$ {$subtotalMX}</td></tr>
                <tr><td class="right">Descuento</td><td class="right">\$ {$descuentoMX}</td></tr>
                <tr><td class="right">Total Percepciones</td><td class="right">\$ {$totalPercepMX}</td></tr>
                <tr><td class="right">Total Deducciones</td><td class="right">\$ {$totalDeducMX}</td></tr>
                <tr><td class="right">Total (CFDI)</td><td class="right">\$ {$totalMX}</td></tr>
            </table>
        
            <h2>Sello Digital</h2>
            <div class="small">
                <div><strong>Sello CFDI:</strong> <span class="mono">$selloCFD</span></div>
                <div><strong>Sello SAT:</strong> <span class="mono">$selloSAT</span></div>
            </div>
        
            <div style="text-align:right; margin-top:8px;">
                <img src="$qrBase64" alt="QR CFDI" style="width:100px;height:100px;">
            </div>
        
            <div class="small" style="margin-top:4px; text-align:center;">
                <em>Este documento es una representación impresa de un CFDI. Para verificarlo escanee el QR o visite el portal del SAT.</em>
            </div>
        
        </div>
        </body>
        </html>
        HTML;
        // y configuras Dompdf:
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();

        return $dompdf->output();

    }

    private function moneyMX($v): string {
        return number_format((float)$v, 2, '.', ',');
    }

    private function satAmt6($v): string {
        return number_format((float)$v, 6, '.', '');
    }

    private function obtenerUltimosOcho($cadena): string
    {
        return substr($cadena, -8);
    }

    private function formatearNumeroFijo($numero): string
    {
        // Convierte el número a un tipo flotante para asegurar que se maneje correctamente
        $numero = floatval($numero);

            // Formatea el número con la precisión decimal deseada
        $formato = sprintf('%.'. 6 . 'f', $numero);

            // Separa la parte entera y la parte decimal
        list($parteEntera, $parteDecimal) = explode('.', $formato);

            // Rellena la parte entera con ceros a la izquierda
        $parteEnteraRellenada = str_pad($parteEntera, 10, '0', STR_PAD_LEFT);

            // Combina las partes y devuelve el resultado
        return $parteEnteraRellenada . '.' . $parteDecimal;
    }





}
