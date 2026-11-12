<?php

namespace gamboamartin\facturacion\models;

use config\generales;
use DateTime;
use gamboamartin\cat_sat\models\cat_sat_regimen_fiscal;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\controllers\pdf;
use gamboamartin\organigrama\models\org_logo;
use NumberFormatter;
use PDO;
use setasign\Fpdi\Fpdi;
use stdClass;
use Throwable;

class _pdf_aguaban
{
    private errores $error;
    private generales $config_generales;
    private string $tabla;
    private stdClass $cfdi_sellado;
    private stdClass $data;
    private array $factura;
    private array $relacionadas;
    private string $ruta_logo;
    private string $ruta_qr;
    private NumberFormatter $fmt;

    public function __construct()
    {
        $this->config_generales = new generales();
        $this->error = new errores();
        $this->fmt = new NumberFormatter('es_MX', NumberFormatter::CURRENCY);
    }

    private function set_partidas(Fpdi $pdf, $tabla_partida): void
    {
        $border = 0;
        $h = 2.5;
        $y = 96.5;
        $partidas = 1;
        foreach ($this->factura['partidas'] as $partida) {
            $pdf->SetFont('Arial', '', '7');
            $x = 7;
            $pdf->SetXY($x, $y);
            $pdf->MultiCell(10, $h, $partida[$tabla_partida.'_cantidad'], $border, 'C');

            $x = 19;
            $pdf->SetXY($x, $y);
            $pdf->MultiCell(12, $h, $partida['cat_sat_unidad_codigo'], $border, 'C');

            $x = 33;
            $pdf->SetXY($x, $y);
            $pdf->MultiCell(18, $h, $partida['com_producto_codigo_sat'], $border, 'C');

            $x = 52;
            $pdf->SetXY($x, $y);
            $pdf->MultiCell(75, $h, mb_convert_encoding($partida[$tabla_partida.'_descripcion'], 'ISO-8859-1', 'UTF-8'), $border, 'L');

            $x = 127;
            $pdf->SetXY($x, $y);
            $pdf->MultiCell(27, $h,
                mb_convert_encoding($partida['cat_sat_obj_imp_descripcion'], 'ISO-8859-1', 'UTF-8'), $border, 'C');

            $fc_partida_valor_unitario = round($partida[$tabla_partida.'_valor_unitario'], 2);
            $fc_partida_valor_unitario = $this->fmt->formatCurrency($fc_partida_valor_unitario, "MXN");

            $x = 155;
            $pdf->SetXY($x, $y);
            $pdf->MultiCell(24, $h, $fc_partida_valor_unitario, $border, 'C');

            $fc_partida_sub_total = round($partida[$tabla_partida.'_sub_total'], 2);
            $fc_partida_sub_total = $this->fmt->formatCurrency($fc_partida_sub_total, "MXN");

            $x = 182;
            $pdf->SetXY($x, $y);
            $pdf->MultiCell(24, $h, $fc_partida_sub_total, $border, 'C');

            $y = $y + $h;

            $partidas++;
            $mod = $partidas % 6;
            if ($mod === 0) {
                $y = 96.5;
                $this->base_pdf(pdf: $pdf);
            }
        }
    }

    private function set_relacionadas(Fpdi $pdf)
    {
        if (count($this->relacionadas) > 0) {
            $y = 165;
            $pdf->SetXY(7, $y);
            $pdf->SetFont('Arial', 'B', '9');
            $pdf->SetTextColor(0, 0, 0);
            $pdf->MultiCell(90, 3, 'Relaciones:', 0);
            foreach ($this->relacionadas as $relacionada) {
                $pdf->SetFont('Arial', 'B', '8');
                $cat_sat_tipo_relacion_codigo = trim($relacionada['cat_sat_tipo_relacion_codigo']);
                $cat_sat_tipo_relacion_descripcion = trim($relacionada['cat_sat_tipo_relacion_descripcion']);

                $tipo_relacion = "$cat_sat_tipo_relacion_codigo $cat_sat_tipo_relacion_descripcion";
                $tipo_relacion = mb_convert_encoding($tipo_relacion, 'ISO-8859-1', 'UTF-8');
                $y = $y + 4;
                $pdf->SetFont('Arial', 'B', '8');
                $pdf->SetXY(8.5, $y);
                $pdf->MultiCell(90, 3.5, $tipo_relacion, 0);

                $folios_rel = $relacionada['fc_facturas_relacionadas'];

                foreach ($folios_rel as $folio_rel) {
                    $y = $y + 4;
                    $pdf->SetXY(9.5, $y);
                    $pdf->SetFont('Arial', '', '7');
                    $pdf->MultiCell(90, 3.5, $folio_rel['fc_factura_uuid'], 0);
                }

            }
        }
    }

    private function base_pdf(Fpdi $pdf): void
    {

        $init = $this->init($pdf);
        if (errores::$error) {
            (new errores())->error(mensaje: 'Error al cargar template', data: $init);
            return;
        }
        $this->set_folio(pdf: $pdf);
        $this->set_fecha_hora_emision(pdf: $pdf);
        $this->set_receptor(pdf: $pdf);
        $this->set_emisor(pdf: $pdf);

        $this->set_relacionadas(pdf: $pdf);

        $this->set_pago(pdf: $pdf);
        $this->set_montos(pdf: $pdf);
        $this->set_datos_fiscales(pdf: $pdf);
        $this->set_sellos(pdf: $pdf);
        $this->set_qrcode(pdf: $pdf);
        //$this->set_logo(pdf: $pdf);

    }

    final public function pdf(
        bool $descarga,
        bool $guarda,
        PDO $link,
        _doc $modelo_documento,
        _transacciones_fc $modelo_entidad,
        _partida $modelo_partida,
        _cuenta_predial $modelo_predial,
        _relacion $modelo_relacion,
        _relacionada $modelo_relacionada,
        _data_impuestos $modelo_retencion,
        _sellado $modelo_sellado,
        _data_impuestos $modelo_traslado,
        _uuid_ext $modelo_uuid_ext,
        int $registro_id):
    array|string|bool {

        $this->configuracion(
            link: $link,
            modelo_documento: $modelo_documento,
            modelo_entidad: $modelo_entidad,
            modelo_partida: $modelo_partida,
            modelo_predial: $modelo_predial,
            modelo_relacion: $modelo_relacion,
            modelo_relacionada: $modelo_relacionada,
            modelo_retencion: $modelo_retencion,
            modelo_sellado: $modelo_sellado,
            modelo_traslado: $modelo_traslado,
            modelo_uuid_ext: $modelo_uuid_ext,
            registro_id: $registro_id
        );

        $this->tabla = $modelo_entidad->tabla;
        $tabla_partida = $modelo_partida->tabla;

        $pdf = new Fpdi();

        $pdf->SetFont('Arial', '', '9');
        $pdf->SetTextColor(0, 0, 0);

        $this->base_pdf(pdf: $pdf);

//        $this->set_partidas(pdf: $pdf, tabla_partida: $tabla_partida);

        $key_serie = $modelo_entidad->tabla . '_serie';
        $key_folio = $modelo_entidad->tabla . '_folio';

        $nombre_documento = $this->factura[$key_serie] . $this->factura[$key_folio];

        if ($descarga) {
            $pdf->Output($nombre_documento . '.pdf', 'I');
            return $nombre_documento;
        }
        if ($guarda) {
            $path_base = $this->config_generales->path_base;
            $path_base_archivos = $path_base . 'archivos';
            if (!file_exists($path_base_archivos)) {
                mkdir($path_base_archivos);
            }
            $nombre_documento = $path_base_archivos . '/' . $nombre_documento . '.pdf';

            $pdf->Output('F', $nombre_documento);
            return $nombre_documento;
        }

        $pdf->Output();
        return $nombre_documento;

    }

    public function data_factura(string $name_entidad_sellado): array|stdClass
    {
        $data = new stdClass();

        // Valores por defecto
        $data->folio_fiscal = '-----';
        $data->sello_cfdi = '';
        $data->sello_sat = '';
        $data->complento = '---';
        $data->rfc_proveedor = '-----';
        $data->fecha_timbrado = 'xxxx-xx-xx 00:00:00';
        $data->no_certificado = '-----';
        $data->no_certificado_sat = '';

        $cfdi_sellado = $this->cfdi_sellado;

        if ($cfdi_sellado->n_registros > 0) {

            $registro = $cfdi_sellado->registros[0];

            $data->folio_fiscal = $registro[$name_entidad_sellado . '_uuid'];
            $data->sello_cfdi = $registro[$name_entidad_sellado . '_complemento_tfd_sello_cfd'];
            $data->sello_sat = $registro[$name_entidad_sellado . '_complemento_tfd_sello_sat'];
            $data->complento = $registro[$name_entidad_sellado . '_cadena_complemento_sat'];
            $data->rfc_proveedor = $registro[$name_entidad_sellado . '_complemento_tfd_rfc_prov_certif'];
            $data->fecha_timbrado = $registro[$name_entidad_sellado . '_complemento_tfd_fecha_timbrado'];
            $data->no_certificado = $registro[$name_entidad_sellado . '_comprobante_no_certificado'];
            $data->no_certificado_sat = $registro[$name_entidad_sellado . '_complemento_tfd_no_certificado_sat'];

        }

        return $data;
    }

    private function configuracion(
        PDO $link,
        _doc $modelo_documento,
        _transacciones_fc $modelo_entidad,
        _partida $modelo_partida,
        _cuenta_predial $modelo_predial,
        _relacion $modelo_relacion,
        _relacionada $modelo_relacionada,
        _data_impuestos $modelo_retencion,
        _sellado $modelo_sellado,
        _data_impuestos $modelo_traslado,
        _uuid_ext $modelo_uuid_ext,
        int $registro_id
    ): void
    {

        $this->factura = $modelo_entidad->get_factura(modelo_partida: $modelo_partida,
            modelo_predial: $modelo_predial, modelo_relacion: $modelo_relacion,
            modelo_relacionada: $modelo_relacionada, modelo_retencion: $modelo_retencion,
            modelo_traslado: $modelo_traslado, modelo_uuid_ext: $modelo_uuid_ext, registro_id: $registro_id);
        if (errores::$error) {
            $this->error->error(mensaje: 'Error al obtener factura', data: $this->factura);
            return;
        }

        $this->factura['org_empresa_cp_txt'] = (string)$this->factura['dp_cp_descripcion'] ?? '';

        $this->ruta_qr = $modelo_documento->get_factura_documento(key_entidad_filter_id: $modelo_entidad->key_filtro_id,
            registro_id: $registro_id, tipo_documento: "qr_cfdi");
        if (errores::$error) {
            $this->error->error(mensaje: 'Error al obtener QR', data: $this->ruta_qr);
            return;
        }

        // inicio del logo dinamico
        // ToDo en un futuro crear un metodo en el modelo de org_logo que obtenga directacmente la ruta_logo
        $filtro = array();
        $filtro['org_logo.status'] = 'activo';
        $filtro['org_logo.logo_pdf'] = 'activo';
        $filtro['org_logo.org_empresa_id'] = (int)$this->factura['org_empresa_id'];

        $r_org_logo = (new org_logo(link: $link))->filtro_and(
            aplica_seguridad: false,
            columnas: ['doc_documento_ruta_absoluta'],
            filtro: $filtro,
            limit: 1,
            order: ['org_logo.id' => 'DESC']
        );
        if (errores::$error) {
            $this->error->error(mensaje: 'Error al obtener Logo', data: $r_org_logo);
            return;
        }

        $this->ruta_logo = '';
        if ($r_org_logo->n_registros > 0) {
            $this->ruta_logo = (string)$r_org_logo->registros[0]['doc_documento_ruta_absoluta'];
        }
        // inicio del logo dinamico

        $filtro = array();
        $filtro[$modelo_entidad->key_id] = $this->factura[$modelo_entidad->key_id];
        $this->cfdi_sellado = $modelo_sellado->filtro_and(filtro: $filtro);
        if (errores::$error) {
            $this->error->error(mensaje: 'Error al obtener cfdi_sellado', data: $this->cfdi_sellado);
            return;
        }

        $this->data = $this->data_factura(name_entidad_sellado: $modelo_sellado->tabla);
        if (errores::$error) {
            $this->error->error(mensaje: 'Error al asignar datos', data: $this->data);
            return;
        }

        $this->relacionadas = $modelo_entidad->get_data_relaciones(modelo_relacion: $modelo_relacion,
            modelo_relacionada: $modelo_relacionada, modelo_uuid_ext: $modelo_uuid_ext, registro_entidad_id: $registro_id);
        if (errores::$error) {
            $this->error->error(mensaje: 'Error al obtener relacionadas', data: $this->relacionadas);
            return;
        }

        $key_observaciones = $modelo_entidad->tabla . '_observaciones';
        if (!isset($this->factura[$key_observaciones])) {
            $this->factura[$key_observaciones] = '';
        }

    }

    private function init(Fpdi $pdf): true|array
    {

        try {
            $pdf->addPage();
            $pdf->setSourceFile($this->config_generales->path_base.'/plantillas/factura_aguaban.pdf');
            $tplIdx = $pdf->importPage(1);
            $pdf->useTemplate($tplIdx, 0, 0, null, null, true);
        } catch (Throwable $e) {
            return (new errores())->error(mensaje: 'Error al cargar template', data: $e);
        }
        return true;
    }

    private function set_fecha_hora_emision(Fpdi $pdf): void
    {
        $fecha_hora_emision = '';
        if (isset($this->data->fecha_timbrado)) {
            $fecha_hora_emision = $this->data->fecha_timbrado;
            $fecha_hora_emision = DateTime::createFromFormat('Y-m-d\TH:i:s', $fecha_hora_emision);
            $fecha_hora_emision = $fecha_hora_emision->format('d/m/Y H:i:s');
        }

        $pdf->SetFont('Arial', '', '6');
        $pdf->SetTextColor(86, 117, 154);
        $pdf->SetXY(166, 25);
        $pdf->cell(30,5, $fecha_hora_emision,0, 0, 'R');
    }

    private function set_folio(Fpdi $pdf): void
    {
        $pdf->SetFont('Arial', 'B', '10');
        $pdf->SetTextColor(18, 61, 112);
        $pdf->SetXY(166, 20);
        $pdf->cell(30,5, $this->factura[$this->tabla . "_folio"], 0, 0, 'R');

    }

    private function set_logo(Fpdi $pdf)
    {
        if ($this->ruta_logo !== '') {
            if (file_exists($this->ruta_logo)) {
                $pdf->SetXY(10, 10);
                $pdf->Image($this->ruta_logo, null, null, 35, 0, 'png');
            }
        }
    }

    private function set_receptor(Fpdi $pdf): void
    {
        $factura = $this->factura;

        if (!isset($factura['com_sucursal_numero_interior'])) {
            $factura['com_sucursal_numero_interior'] = '';
        }


        $razon_social = mb_convert_encoding($factura['com_cliente_razon_social'], 'ISO-8859-1', 'UTF-8');
        $cliente_rfc = $factura['com_cliente_rfc'];

        $reg = mb_convert_encoding($factura['cat_sat_regimen_fiscal_cliente_codigo'] . ' ' . $factura['cat_sat_regimen_fiscal_cliente_descripcion'], 'ISO-8859-1', 'UTF-8');
        $regimen = trim($reg);

        $uso_cfdi = trim($factura['cat_sat_uso_cfdi_codigo']) . ' - ' . trim($factura['cat_sat_uso_cfdi_descripcion']);
        $uso_cfdi = trim($uso_cfdi);
        $uso_cfdi = mb_convert_encoding($uso_cfdi, 'ISO-8859-1', 'UTF-8');

        $border = 0;
        $x = 138;
        $h = 3.5;
        $y = 39;

        $pdf->SetFont('Arial', 'B', '8');
        $pdf->SetTextColor(18, 61, 112);
        $pdf->SetXY($x, $y);
        $pdf->cell(60,$h, $razon_social,$border, 0, 'L');
        $y += $h;

        $pdf->SetFont('Arial', '', '8');
        $pdf->SetTextColor(18, 61, 112);
        $pdf->SetXY($x, $y);
        $pdf->cell(60,$h, 'RFC ' . $cliente_rfc,$border, 0, 'L');
        $y += $h;

        $pdf->SetFont('Arial', '', '7');
        $pdf->SetTextColor(86, 117, 154);
        $pdf->SetXY($x, $y);
        $pdf->cell(60,$h, 'Regimen Fiscal '.$regimen,$border, 0, 'L');
        $y += $h;

        $pdf->SetFont('Arial', '', '7');
        $pdf->SetTextColor(86, 117, 154);
        $pdf->SetXY($x, $y);
        $pdf->cell(60,$h, 'Uso CFDI '.$uso_cfdi,$border, 0, 'L');

    }
    private function set_emisor(Fpdi $pdf): void
    {
        $tipo_de_comprobante = $this->factura['cat_sat_tipo_de_comprobante_codigo_bis']." - ";
        $tipo_de_comprobante .= $this->factura['cat_sat_tipo_de_comprobante_descripcion'];
        $tipo_de_comprobante = mb_convert_encoding($tipo_de_comprobante, 'ISO-8859-1', 'UTF-8');

        $border = 0;
        $x = 15;
        $h = 3.5;
        $y = 39;

        $pdf->SetFont('Arial', 'B', '8');
        $pdf->SetTextColor(18, 61, 112);
        $pdf->SetXY($x, $y);
        $pdf->cell(60,$h, mb_convert_encoding("AGUABAN · RFC AGU000614BI8", 'ISO-8859-1', 'UTF-8'),$border, 0, 'L');
        $y += $h;

        $pdf->SetFont('Arial', '', '8');
        $pdf->SetTextColor(86, 117, 154);
        $pdf->SetXY($x, $y);
        $pdf->cell(60,$h, mb_convert_encoding("Régimen fiscal 601 - General de Ley Personas Morales", 'ISO-8859-1', 'UTF-8'),$border, 0, 'L');
        $y += $h;

        $pdf->SetFont('Arial', '', '7');
        $pdf->SetTextColor(86, 117, 154);
        $pdf->SetXY($x, $y);
        $pdf->cell(60,$h, mb_convert_encoding("Lugar de expedición: CP 63734", 'ISO-8859-1', 'UTF-8'),$border, 0, 'L');
        $y += $h;

        $pdf->SetFont('Arial', '', '7');
        $pdf->SetTextColor(86, 117, 154);
        $pdf->SetXY($x, $y);
        $pdf->cell(60,$h, "CFDI 4.0 " . $tipo_de_comprobante, $border, 0, 'L');


    }

    private function set_pago(Fpdi $pdf): void
    {
        $factura = $this->factura;

        $exportacion = "EXPORTACIÓN 01 - No aplica";
        if ((int)$factura['fc_factura_exportacion'] !== 1) {
            $exportacion = "EXPORTACIÓN 00 - Aplica";
        }
        $exportacion = mb_convert_encoding($exportacion, 'ISO-8859-1', 'UTF-8');

        $forma_pago = mb_convert_encoding($factura['cat_sat_forma_pago_codigo'] . ' - ' . $factura['cat_sat_forma_pago_descripcion'], 'ISO-8859-1', 'UTF-8');
        $metodo_pago = mb_convert_encoding($factura['cat_sat_metodo_pago_codigo'] . ' - ' . $factura['cat_sat_metodo_pago_descripcion'], 'ISO-8859-1', 'UTF-8');
        $moneda = mb_convert_encoding($factura['cat_sat_moneda_codigo'] . ' - ' . $factura['cat_sat_moneda_descripcion'], 'ISO-8859-1', 'UTF-8');

        $pdf->SetFont('Arial', '', '8');
        $pdf->SetTextColor(90, 128, 192);

        $y = 62;
        $border = 0;
        $pdf->SetXY(15, $y);
        $pdf->cell(90,5, "FORMA DE PAGO ".$forma_pago, $border, 0, 'L');

        $pdf->SetXY(105, $y);
        $pdf->cell(90,5, "METODO DE PAGO ".$metodo_pago, $border, 0, 'L');

        $y = 67;
        $pdf->SetXY(15, $y);
        $pdf->cell(90,5, $exportacion, $border, 'L');

        $pdf->SetXY(105, $y);
        $pdf->cell(90,5, "MONEDA ".$moneda, $border, 0, 'L');

    }

    private function set_montos(Fpdi $pdf): void
    {
        $tabla = $this->tabla;
        $factura = $this->factura;

        $pdf->SetFont('Arial', 'B', '9');
        $pdf->SetTextColor(0, 128, 192);
        $fc_factura_sub_total = round($factura[$tabla.'_sub_total'], 2);
        $fc_factura_sub_total = $this->fmt->formatCurrency($fc_factura_sub_total, "MXN");
        $pdf->SetXY(165, 160.7);
        $pdf->cell(30,9, $fc_factura_sub_total,0, 0, 'R');


        $pdf->SetFont('Arial', 'B', '9');
        $pdf->SetTextColor(0, 128, 192);
        $fc_factura_total_traslados = round($factura[$tabla.'_total_traslados'], 2);
        $fc_factura_total_traslados = $this->fmt->formatCurrency($fc_factura_total_traslados, "MXN");
        $pdf->SetXY(165, 170.7);
        $pdf->cell(30,9, $fc_factura_total_traslados,0, 0, 'R');


        $pdf->SetFont('Arial', 'B', '9');
        $pdf->SetTextColor(0, 128, 192);
        $fc_factura_total = round($factura[$tabla.'_total'], 2);
        $fc_factura_total = $this->fmt->formatCurrency($fc_factura_total, "MXN");
        $pdf->SetXY(165, 181.7);
        $pdf->cell(30,9, $fc_factura_total,0, 0, 'R');


        $formatterES = new NumberFormatter("es-ES", NumberFormatter::SPELLOUT);
        $izquierda = intval(floor($factura[$tabla.'_total']));
        $derecha = intval(($factura[$tabla.'_total'] - floor($factura[$tabla.'_total'])) * 100);
        $letra = $formatterES->format($izquierda) . " pesos ";
        if ((float)$derecha === 0.0) {
            $derecha = '00';
        }
        $letra .= $derecha . "/100 M.N.";

        $pdf->SetFont('Arial', '', '7');
        $pdf->SetTextColor(0, 0, 0);
        $letra = strtoupper($letra);
        $letra = mb_convert_encoding($letra, 'ISO-8859-1', 'UTF-8');
        $pdf->SetXY(15, 190);
        $pdf->MultiCell(144, 5, $letra, 0, 'L');

    }

    private function set_datos_fiscales(Fpdi $pdf): void
    {
        $data = $this->data;

        $folio_fiscal = '';
        if (isset($data->folio_fiscal)) {
            $folio_fiscal = $data->folio_fiscal;
        }

        $fecha_timbrado = '';
        if (isset($data->fecha_timbrado)) {
            $fecha_timbrado = $data->fecha_timbrado;
            $fecha = DateTime::createFromFormat('Y-m-d\TH:i:s', $fecha_timbrado);
            $fecha_timbrado = $fecha->format('d/m/Y H:i:s');
        }

        $no_certificado = '';
        if (isset($data->no_certificado)) {
            $no_certificado = $data->no_certificado;
        }

        $no_certificado_sat = '';
        if (isset($data->no_certificado_sat)) {
            $no_certificado_sat = $data->no_certificado_sat;
        }

        $rfc_proveedor = '';
        if (isset($data->rfc_proveedor)) {
            $rfc_proveedor = $data->rfc_proveedor;
        }

        $pdf->SetFont('Arial', 'B', '7');
        $pdf->SetTextColor(100, 100, 100);
        $pdf->SetXY(36, 216);
        $pdf->cell(50,3, ': ' . mb_strtoupper($folio_fiscal),0, 0, 'L');


        $pdf->SetFont('Arial', 'B', '7');
        $pdf->SetTextColor(100, 100, 100);
        $pdf->SetXY(48, 220.1);
        $pdf->cell(50,3, ': ' . $fecha_timbrado, 0, 0, 'L');

        $pdf->SetFont('Arial', 'B', '7');
        $pdf->SetTextColor(100, 100, 100);
        $pdf->SetXY(41, 224);
        $pdf->cell(50,3, ': ' . $no_certificado,0, 0, 'L');

        $pdf->SetFont('Arial', 'B', '7');
        $pdf->SetTextColor(100, 100, 100);
        $pdf->SetXY(37, 228);
        $pdf->cell(50,3, ': ' . $no_certificado_sat,0, 0, 'L');

        $pdf->SetFont('Arial', 'B', '7');
        $pdf->SetTextColor(100, 100, 100);
        $pdf->SetXY(54.5, 232.1);
        $pdf->cell(50,3, ': ' . $rfc_proveedor,0, 0, 'L');

    }

    private function set_sellos(Fpdi $pdf): void
    {
        $data = $this->data;
        $x = 15;
        $pdf->SetFont('Arial', '', '6');
        $pdf->SetTextColor(0, 0, 0);

        $pdf->SetXY($x, 241);
        $pdf->MultiCell(140, 2.6, $data->sello_cfdi, 0);

        $pdf->SetXY($x, 256);
        $pdf->MultiCell(140, 2.6, $data->sello_sat, 0);

        $pdf->SetFont('Arial', '', '6');
        $pdf->SetAutoPageBreak(false);
        $pdf->SetXY($x, 270);
        $pdf->MultiCell(140, 2.6, $data->complento);
        $pdf->SetAutoPageBreak(true);

    }

    private function set_qrcode(Fpdi $pdf): void
    {
        if ($this->ruta_qr !== '') {
            if (file_exists($this->ruta_qr)) {
                $pdf->Image($this->ruta_qr, 159.5, 243, 29.5, 29.5, 'png');
            }
        }
    }

}
