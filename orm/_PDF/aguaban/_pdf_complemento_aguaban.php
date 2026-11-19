<?php

namespace gamboamartin\facturacion\models\_PDF\aguaban;

use config\generales;
use DateTime;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\_cuenta_predial;
use gamboamartin\facturacion\models\_data_impuestos;
use gamboamartin\facturacion\models\_doc;
use gamboamartin\facturacion\models\_partida;
use gamboamartin\facturacion\models\_PDF\__CUSTOM_FPDI;
use gamboamartin\facturacion\models\_relacion;
use gamboamartin\facturacion\models\_relacionada;
use gamboamartin\facturacion\models\_sellado;
use gamboamartin\facturacion\models\_transacciones_fc;
use gamboamartin\facturacion\models\_uuid_ext;
use gamboamartin\organigrama\models\org_logo;
use NumberFormatter;
use PDO;
use setasign\Fpdi\Fpdi;
use stdClass;
use Throwable;

class _pdf_complemento_aguaban
{

    public function __construct()
    {

    }



    private function base_pdf(__CUSTOM_FPDI $pdf): void
    {

        $init = $this->init($pdf);
        if (errores::$error) {
            (new errores())->error(mensaje: 'Error al cargar template', data: $init);
            return;
        }

        //$this->set_logo(pdf: $pdf);
        $this->set_folio(pdf: $pdf);
        if ($this->tipo_documento === 'factura') {
            $this->set_fecha_hora_emision(pdf: $pdf);
        }
        $this->set_pago(pdf: $pdf);
        $this->set_receptor(pdf: $pdf);
        $this->set_emisor(pdf: $pdf);


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

        if ($this->tabla === 'fc_nota_credito') {
            $this->tipo_documento = 'nota_credito';
        }

        $pdf = new __CUSTOM_FPDI();

        $pdf->AddFont('DejaVuSans', '', 'DejaVuSans.php');
        $pdf->AddFont('DejaVuSans', 'B', 'DejaVuSans-Bold.php');
        $pdf->SetFont('DejaVuSans', '', '9');
        $pdf->SetTextColor(0, 0, 0);

        $this->base_pdf(pdf: $pdf);

        $this->set_partidas(pdf: $pdf, tabla_partida: $tabla_partida);

        $this->set_montos(pdf: $pdf);
        $this->set_relacionadas(pdf: $pdf);
        $this->set_datos_fiscales(pdf: $pdf);
        $this->set_sellos(pdf: $pdf);
        $this->set_qrcode(pdf: $pdf);

        $key_serie = $modelo_entidad->tabla . '_serie';
        $key_folio = $modelo_entidad->tabla . '_folio';

        $nombre_documento = $this->factura[$key_serie] . $this->factura[$key_folio];

        if ($descarga) {
            $pdf->Output($nombre_documento . '.pdf', 'D');
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
        $plantilla = 'factura_aguaban.pdf';
        if ($this->tipo_documento === 'nota_credito') {
            $plantilla = 'nota_credito_aguaban.pdf';
        }

        try {
            $pdf->addPage();
            $pdf->setSourceFile($this->config_generales->path_base . '/plantillas/' . $plantilla);
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
            if ($fecha_hora_emision) {
                $fecha_hora_emision = $fecha_hora_emision->format('d/m/Y H:i:s');
            }
        }

        $pdf->SetFont('DejaVuSans', '', '6');
        $pdf->SetTextColor(86, 117, 154);
        $pdf->SetXY(166, 25);
        $pdf->cell(30,5, $fecha_hora_emision,0, 0, 'R');
    }

    private function set_folio(Fpdi $pdf): void
    {
        $pdf->SetFont('DejaVuSans', 'B', '10');
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

    private function set_receptor(__CUSTOM_FPDI $pdf): void
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

        $pdf->SetFont('DejaVuSans', 'B', '8');
        $pdf->SetTextColor(18, 61, 112);
        $pdf->SetXY($x, $y);
        $pdf->multicell(60,$h, $razon_social,$border, 'L');
        $lineas_razon_social = $pdf->NbLines(60, $razon_social);
        $y += $h * $lineas_razon_social;

        $pdf->SetFont('DejaVuSans', '', '8');
        $pdf->SetTextColor(18, 61, 112);
        $pdf->SetXY($x, $y);
        $pdf->cell(60,$h, 'RFC ' . $cliente_rfc,$border, 0, 'L');
        $y += $h;

        $pdf->SetFont('DejaVuSans', '', '7');
        $pdf->SetTextColor(86, 117, 154);
        $pdf->SetXY($x, $y);
        $pdf->cell(60,$h, 'Regimen Fiscal '.$regimen,$border, 0, 'L');
        $y += $h;

        $pdf->SetFont('DejaVuSans', '', '7');
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

        $pdf->SetFont('DejaVuSans', 'B', '8');
        $pdf->SetTextColor(18, 61, 112);
        $pdf->SetXY($x, $y);
        $pdf->cell(60,$h, mb_convert_encoding("AGUABAN · RFC AGU000614BI8", 'ISO-8859-1', 'UTF-8'),$border, 0, 'L');
        $y += $h;

        $pdf->SetFont('DejaVuSans', '', '8');
        $pdf->SetTextColor(86, 117, 154);
        $pdf->SetXY($x, $y);
        $pdf->cell(60,$h, mb_convert_encoding("Régimen fiscal 601 - General de Ley Personas Morales", 'ISO-8859-1', 'UTF-8'),$border, 0, 'L');
        $y += $h;

        $pdf->SetFont('DejaVuSans', '', '7');
        $pdf->SetTextColor(86, 117, 154);
        $pdf->SetXY($x, $y);
        $pdf->cell(60,$h, mb_convert_encoding("Lugar de expedición: CP 63734", 'ISO-8859-1', 'UTF-8'),$border, 0, 'L');
        $y += $h;

        $pdf->SetFont('DejaVuSans', '', '7');
        $pdf->SetTextColor(86, 117, 154);
        $pdf->SetXY($x, $y);
        $pdf->cell(60,$h, "CFDI 4.0 " . $tipo_de_comprobante, $border, 0, 'L');


    }



















}
