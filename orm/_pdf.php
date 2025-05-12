<?php

namespace gamboamartin\facturacion\models;

use config\generales;
use gamboamartin\cat_sat\models\cat_sat_regimen_fiscal;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\controllers\pdf;
use gamboamartin\organigrama\models\org_logo;
use NumberFormatter;
use PDO;
use setasign\Fpdi\Fpdi;
use stdClass;
use Throwable;

class _pdf
{
    private errores $error;

    public function __construct()
    {
        $this->error = new errores();
    }


    private function numero_a_letras(float $numero, string $moneda = 'pesos', string $centavos = 'centavos'): string
    {
        $unidades = ['', 'uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve'];
        $decenas = ['', 'diez', 'veinte', 'treinta', 'cuarenta', 'cincuenta', 'sesenta', 'setenta', 'ochenta', 'noventa'];
        $especiales = [11 => 'once', 12 => 'doce', 13 => 'trece', 14 => 'catorce', 15 => 'quince',
            16 => 'dieciséis', 17 => 'diecisiete', 18 => 'dieciocho', 19 => 'diecinueve'];
        $centenas = ['', 'cien', 'doscientos', 'trescientos', 'cuatrocientos', 'quinientos',
            'seiscientos', 'setecientos', 'ochocientos', 'novecientos'];

        if ($numero < 0) {
            return 'menos ' . $this->numero_a_letras(abs($numero), $moneda, $centavos);
        }

        $parte_entera = (int)$numero;
        $parte_decimal = round(($numero - $parte_entera) * 100);

        $texto = $this->convertir_mayores($parte_entera, $unidades, $decenas, $especiales, $centenas);

        if ($texto == '') {
            $texto = 'cero';
        }

        $resultado = ucfirst($texto) . " $moneda";

        if ($parte_decimal > 0) {
            $resultado .= " con " . $this->convertir_mayores($parte_decimal, $unidades, $decenas, $especiales, $centenas) . " $centavos";
        }

        return $resultado;
    }

    private function convertir_mayores($numero, $unidades, $decenas, $especiales, $centenas)
    {
        if ($numero == 0) return '';

        if ($numero < 1000) {
            return $this->convertir_cientos($numero, $unidades, $decenas, $especiales, $centenas);
        }

        if ($numero < 1000000) {
            $miles = (int)($numero / 1000);
            $resto = $numero % 1000;
            $texto_miles = ($miles == 1) ? 'mil' : $this->convertir_cientos($miles, $unidades, $decenas, $especiales, $centenas) . ' mil';
            return trim($texto_miles . ' ' . $this->convertir_cientos($resto, $unidades, $decenas, $especiales, $centenas));
        }

        if ($numero < 1000000000) {
            $millones = (int)($numero / 1000000);
            $resto = $numero % 1000000;
            $texto_millones = ($millones == 1) ? 'un millón' : $this->convertir_cientos($millones, $unidades, $decenas, $especiales, $centenas) . ' millones';
            return trim($texto_millones . ' ' . $this->convertir_mayores($resto, $unidades, $decenas, $especiales, $centenas));
        }

        return 'Número demasiado grande';
    }

    function convertir_cientos($numero, $unidades, $decenas, $especiales, $centenas)
    {
        if ($numero == 0) return '';

        if ($numero < 10) {
            return $unidades[$numero];
        }

        if ($numero < 20) {
            return $especiales[$numero] ?? '';
        }

        if ($numero < 100) {
            $unidad = $numero % 10;
            return $decenas[(int)($numero / 10)] . ($unidad ? ' y ' . $unidades[$unidad] : '');
        }

        if ($numero < 1000) {
            $centena = (int)($numero / 100);
            $resto = $numero % 100;
            return ($numero == 100) ? 'cien' : $centenas[$centena] . ($resto ? ' ' . $this->convertir_cientos($resto, $unidades, $decenas, $especiales, $centenas) : '');
        }

        return '';
    }


    public function data_factura(stdClass $cfdi_sellado, string $name_entidad_sellado)
    {
        $data = $this->data_init_limpio();
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al asignar datos', data: $data);
        }

        if ($cfdi_sellado->n_registros > 0) {

            $data = $this->data_init(cfdi_sellado: $cfdi_sellado, name_entidad_sellado: $name_entidad_sellado);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al asignar datos', data: $data);
            }

        }
        return $data;
    }

    private function data_init(stdClass $cfdi_sellado, string $name_entidad_sellado): stdClass
    {
        $folio_fiscal = $cfdi_sellado->registros[0][$name_entidad_sellado . '_uuid'];
        $sello_cfdi = $cfdi_sellado->registros[0][$name_entidad_sellado . '_complemento_tfd_sello_cfd'];
        $sello_sat = $cfdi_sellado->registros[0][$name_entidad_sellado . '_complemento_tfd_sello_sat'];
        $complento = $cfdi_sellado->registros[0][$name_entidad_sellado . '_cadena_complemento_sat'];
        $rfc_proveedor = $cfdi_sellado->registros[0][$name_entidad_sellado . '_complemento_tfd_rfc_prov_certif'];
        $fecha_timbrado = $cfdi_sellado->registros[0][$name_entidad_sellado . '_complemento_tfd_fecha_timbrado'];
        $no_certificado = $cfdi_sellado->registros[0][$name_entidad_sellado . '_comprobante_no_certificado'];
        $no_certificado_sat = $cfdi_sellado->registros[0][$name_entidad_sellado . '_complemento_tfd_no_certificado_sat'];

        $data = new stdClass();
        $data->folio_fiscal = $folio_fiscal;
        $data->sello_cfdi = $sello_cfdi;
        $data->sello_sat = $sello_sat;
        $data->complento = $complento;
        $data->rfc_proveedor = $rfc_proveedor;
        $data->fecha_timbrado = $fecha_timbrado;
        $data->no_certificado = $no_certificado;
        $data->no_certificado_sat = $no_certificado_sat;

        return $data;

    }

    private function data_init_limpio(): stdClass
    {
        $folio_fiscal = "-----";
        $sello_cfdi = "";
        $sello_sat = "";
        $complento = "---";
        $rfc_proveedor = "-----";
        $fecha_timbrado = "xxxx-xx-xx 00:00:00";
        $no_certificado = "-----";

        $data = new stdClass();
        $data->folio_fiscal = $folio_fiscal;
        $data->sello_cfdi = $sello_cfdi;
        $data->sello_sat = $sello_sat;
        $data->complento = $complento;
        $data->rfc_proveedor = $rfc_proveedor;
        $data->fecha_timbrado = $fecha_timbrado;
        $data->no_certificado = $no_certificado;
        $data->no_certificado_sat = '';

        return $data;

    }

    private function fecha_hora_emision(stdClass $data, Fpdi $pdf, int $x): true
    {
        $fecha_hora_emision = '';
        if (isset($data->fecha_timbrado)) {
            $fecha_hora_emision = $data->fecha_timbrado;
        }

        $pdf->SetXY($x, 22);
        $pdf->Write(10, $fecha_hora_emision);

        return true;

    }

    private function folio(string $tabla, array $factura, Fpdi $pdf, float $x): true
    {
        $pdf->SetFont('Arial', 'B', '10');
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY($x, 0);
        $pdf->Write(10, $factura[$tabla . "_folio"]);

        return true;

    }

    private function init(Fpdi $pdf): true|array
    {
        $generales = new generales();
        try {
            $pdf->addPage();
            $pdf->setSourceFile($generales->ruta_factura_pdf);
            $tplIdx = $pdf->importPage(1);
            $pdf->useTemplate($tplIdx, 0, 0, null, null, true);
        } catch (Throwable $e) {
            return (new errores())->error(mensaje: 'Error al cargar template', data: $e);
        }
        return true;
    }


    private function header(string $tabla, stdClass $data, array $factura, Fpdi $pdf)
    {
        $x = 179;

        $folio = $this->folio(tabla: $tabla, factura: $factura, pdf: $pdf, x: $x);
        if (errores::$error) {
            return (new errores())->error(mensaje: 'Error al escribir folio', data: $folio);
        }

        $pdf->SetFont('Arial', '', '9');
        $pdf->SetTextColor(0, 0, 0);

        $x = 68;
        $pdf->SetXY($x, 10.3);
        if ($factura['org_empresa_rfc'] === "TELJ470913GY5") {
            $x = 35;
            $pdf->SetXY($x, 8.5);
        }

        $pdf->Write(10, $factura['org_empresa_razon_social']);

        $pdf->SetXY($x, 15);
        if ($factura['org_empresa_rfc'] === "TELJ470913GY5") {
            $pdf->SetXY($x, 14.5);
        }
        $pdf->Write(10, $factura['org_empresa_nombre_comercial']);

        $pdf->SetXY($x, 19);
        if ($factura['org_empresa_rfc'] === "TELJ470913GY5") {
            $pdf->SetXY($x, 18);
        }
        $pdf->Write(10, $factura['org_empresa_rfc']);

        $pdf->SetXY($x, 27);
        if ($factura['org_empresa_rfc'] === "TELJ470913GY5") {
            $pdf->SetXY($x, 25.5);
        }


        $regimen = $this->regimen(factura: $factura);
        if (errores::$error) {
            return (new errores())->error(mensaje: 'Error al obtener $regimen', data: $regimen);
        }

        if ($factura['org_empresa_rfc'] === "FIN171207CKA") {
            $pdf->MultiCell(65, 2.5, $regimen, 0, 'L');
        }

        if ($factura['org_empresa_rfc'] === "TELJ470913GY5") {
            $pdf->MultiCell(90, 2.5, $regimen, 0, 'L');
        }


        if ($factura['org_empresa_rfc'] === "FIN171207CKA") {
            $pdf->SetXY($x, 31.5);
            $dom = "Av. Vallarta 6503 - Int. C2, Col. Ciudad Granja,";
            $dom .= "45010, Zapopan, Jalisco";

            $dom = mb_convert_encoding($dom, 'ISO-8859-1', 'UTF-8');

            $pdf->MultiCell(60, 3, $dom, 0);

            $pdf->SetXY($x, 40.5);
            $tel = "+52 3338523986";
            $pdf->MultiCell(60, 3, $tel, 0);

        }
        if ($factura['org_empresa_rfc'] === "TELJ470913GY5") {
            $pdf->SetXY($x, 31.5);
            $dom = "Calle: Carlos Villaseñor 885 Jardines Alcalde";
            $dom .= "44290, Guadalajara, Jalisco";
            $dom = mb_convert_encoding($dom, 'ISO-8859-1', 'UTF-8');
            $pdf->MultiCell(80, 3, $dom, 0, 'L');

            $pdf->SetXY($x, 39.5);
            $tel = "+52 3338523986";
            $pdf->MultiCell(60, 3, $tel, 0);

        }


        $x = 140;

        $pdf->SetXY($x, 14);
        $pdf->Write(10, $factura[$tabla.'_fecha']);

        $fecha_hora_emision = $this->fecha_hora_emision(data: $data, pdf: $pdf, x: $x);
        if (errores::$error) {
            return (new errores())->error(mensaje: 'Error al escribir $fecha_hora_emision', data: $fecha_hora_emision);
        }


    }

    private function receptor(array $factura, Fpdi $pdf): void
    {

        if (!isset($factura['com_sucursal_numero_interior'])) {
            $factura['com_sucursal_numero_interior'] = '';
        }

        $uso_cfdi = trim($factura['cat_sat_uso_cfdi_codigo']) . ' ' . trim($factura['cat_sat_uso_cfdi_descripcion']);
        $uso_cfdi = trim($uso_cfdi);
        $uso_cfdi = mb_convert_encoding($uso_cfdi, 'ISO-8859-1', 'UTF-8');

        $x = 33;


        $pdf->SetXY($x, 52.3);
        $pdf->Write(10, mb_convert_encoding($factura['com_cliente_razon_social'], 'ISO-8859-1', 'UTF-8'));


        $pdf->SetXY($x, 57.8);
        $pdf->Write(10, $factura['com_cliente_rfc']);

        $pdf->SetXY($x, 62);
        $pdf->Write(10, $uso_cfdi);

        $pdf->SetXY($x, 68.5);
        $domicilio_receptor = trim($factura['com_sucursal_calle']);
        $domicilio_receptor = trim($domicilio_receptor) . ' ' . trim($factura['com_sucursal_numero_exterior']);
        $domicilio_receptor = trim($domicilio_receptor) . ' ' . trim($factura['com_sucursal_numero_interior']);
        $domicilio_receptor = trim($domicilio_receptor) . ' ' . trim($factura['com_sucursal_municipio']);
        $domicilio_receptor = trim($domicilio_receptor) . ' ' . trim($factura['com_sucursal_estado']);
        $domicilio_receptor = trim($domicilio_receptor) . ' ' . $factura['com_sucursal_cp'];
        $domicilio_receptor = mb_convert_encoding($domicilio_receptor, 'ISO-8859-1', 'UTF-8');
        $pdf->MultiCell(72, 5, $domicilio_receptor, 0, 'L');

        $reg = mb_convert_encoding($factura['cat_sat_regimen_fiscal_cliente_codigo'] . ' ' . $factura['cat_sat_regimen_fiscal_cliente_descripcion'], 'ISO-8859-1', 'UTF-8');
        $reg = trim($reg);
        $pdf->SetXY($x, 82);
        $pdf->MultiCell(70, 2.7, $reg);

    }

    private function regimen(array $factura): array|false|string|null
    {
        return mb_convert_encoding(trim($factura['cat_sat_regimen_fiscal_codigo'] . ' ' . $factura['cat_sat_regimen_fiscal_descripcion']), 'ISO-8859-1', 'UTF-8');
    }

    private function fiscales(stdClass $data, Fpdi $pdf): void
    {
        $x = 109;
        $pdf->SetXY($x, 53);

        $folio_fiscal = '';
        if (isset($data->folio_fiscal)) {
            $folio_fiscal = $data->folio_fiscal;
        }

        $no_certificado = '';
        if (isset($data->no_certificado)) {
            $no_certificado = $data->no_certificado;
        }

        $no_certificado_sat = '';
        if (isset($data->no_certificado_sat)) {
            $no_certificado_sat = $data->no_certificado_sat;
        }

        $pdf->Write(10, $folio_fiscal);
        $pdf->SetXY($x, 61);
        $pdf->Write(10, $no_certificado);
        $pdf->SetXY($x, 69);
        $pdf->Write(10, $no_certificado_sat);

    }

    private function pago(array $factura, Fpdi $pdf): void
    {
        $x = 38;
        $pdf->SetXY($x, 138.8);
        $pdf->Write(10, mb_convert_encoding($factura['cat_sat_forma_pago_codigo'] . ' ' . $factura['cat_sat_forma_pago_descripcion'], 'ISO-8859-1', 'UTF-8'));

        $pdf->SetXY($x, 142);
        $pdf->Write(10, mb_convert_encoding($factura['cat_sat_metodo_pago_codigo'] . ' ' . $factura['cat_sat_metodo_pago_descripcion'], 'ISO-8859-1', 'UTF-8'));

        $pdf->SetXY($x, 146);
        $pdf->Write(10, mb_convert_encoding($factura['cat_sat_tipo_de_comprobante_codigo'] . ' ' . $factura['cat_sat_tipo_de_comprobante_descripcion'], 'ISO-8859-1', 'UTF-8'));

        $pdf->SetXY($x, 149);
        $pdf->Write(10, mb_convert_encoding($factura['cat_sat_metodo_pago_descripcion'], 'ISO-8859-1', 'UTF-8'));

        $pdf->SetXY($x, 153);
        $pdf->Write(10, mb_convert_encoding($factura['cat_sat_moneda_codigo'] . ' ' . $factura['cat_sat_moneda_descripcion'], 'ISO-8859-1', 'UTF-8'));

    }

    private function montos(string $tabla, array $factura, Fpdi $pdf): void
    {

        $pdf->SetFont('Arial', 'B', '9');
        $pdf->SetTextColor(255, 255, 255);
        $fmt = new NumberFormatter('es_MX', NumberFormatter::CURRENCY);
        $x = 172;
        $y = 136;

        $fc_factura_sub_total = round($factura[$tabla.'_sub_total'], 2);
        $fc_factura_sub_total = $fmt->formatCurrency($fc_factura_sub_total, "MXN");
        $pdf->SetXY($x, $y);
        $pdf->Write(10, $fc_factura_sub_total);

        $y = $y + 5.5;

        $fc_factura_descuento = round($factura[$tabla.'_descuento'], 2);
        $fc_factura_descuento = $fmt->formatCurrency($fc_factura_descuento, "MXN");
        $pdf->SetXY($x, $y);
        $pdf->Write(10, $fc_factura_descuento);

        $y = $y + 4.7;

        $fc_factura_total_traslados = round($factura[$tabla.'_total_traslados'], 2);
        $fc_factura_total_traslados = $fmt->formatCurrency($fc_factura_total_traslados, "MXN");
        $pdf->SetXY($x, $y);
        $pdf->Write(10, $fc_factura_total_traslados);
        $y = $y + 5;


        $fc_factura_total_retenciones = round($factura[$tabla.'_total_retenciones'], 2);
        $fc_factura_total_retenciones = $fmt->formatCurrency($fc_factura_total_retenciones, "MXN");
        $pdf->SetXY($x, $y);
        $pdf->Write(10, $fc_factura_total_retenciones);

        $y = $y + 5;

        $fc_factura_total_retenciones = round($factura[$tabla.'_total_retenciones'], 2);
        $fc_factura_total_retenciones = $fmt->formatCurrency($fc_factura_total_retenciones, "MXN");
        $pdf->SetXY($x, $y);
        $pdf->Write(10, $fc_factura_total_retenciones);

        $y = $y + 4.4;
        $fc_factura_total = round($factura[$tabla.'_total'], 2);
        $fc_factura_total = $fmt->formatCurrency($fc_factura_total, "MXN");
        $pdf->SetXY($x, $y);
        $pdf->Write(10, $fc_factura_total);

        $pdf->SetTextColor(0, 0, 0);
        $fc_factura_total = round($factura[$tabla.'_total'], 2);


        $formatterES = new NumberFormatter("es-ES", NumberFormatter::SPELLOUT);
        $izquierda = intval(floor($fc_factura_total));
        $derecha = intval(($fc_factura_total - floor($fc_factura_total)) * 100);
        $letra = $formatterES->format($izquierda) . " pesos ";
        if ((float)$derecha === 0.0) {
            $derecha = '00';
        }
        $letra .= $derecha . "/100 M.N.";

        $letra = strtoupper($letra);
        $letra = mb_convert_encoding($letra, 'ISO-8859-1', 'UTF-8');
        $x = 65;
        $y = $y + 12;
        $pdf->SetXY($x, $y);
        $pdf->MultiCell(144, 2, $letra, 0, 'R');

    }

    private function sellos(stdClass $data, Fpdi $pdf): void
    {
        $x = 59;
        $pdf->SetFont('Arial', '', '7');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY($x, 204);
        $pdf->MultiCell(149, 2.6, $data->complento, 0);

        $pdf->SetXY($x, 226);
        $pdf->MultiCell(149, 2.6, $data->sello_cfdi, 0);

        $pdf->SetXY($x, 248.5);
        $pdf->MultiCell(149, 2.6, $data->sello_sat, 0);

    }

    private function base_pdf(string $tabla, stdClass $data, array $factura, Fpdi $pdf, array $relacionadas,
                              string            $ruta_logo, string $ruta_qr)
    {
        $init = $this->init($pdf);
        if (errores::$error) {
            return (new errores())->error(mensaje: 'Error al cargar template', data: $init);
        }

        $this->header(tabla: $tabla, data: $data, factura: $factura, pdf: $pdf);
        $this->receptor(factura: $factura, pdf: $pdf);
        $this->fiscales(data: $data, pdf: $pdf);
        $this->pago(factura: $factura, pdf: $pdf);


        if (count($relacionadas) > 0) {
            $y = 165;
            $pdf->SetXY(7, $y);
            $pdf->SetFont('Arial', 'B', '9');
            $pdf->SetTextColor(0, 0, 0);
            $pdf->MultiCell(90, 3, 'Relaciones:', 0);
            foreach ($relacionadas as $relacionada) {
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


        $this->montos(tabla: $tabla,factura: $factura, pdf: $pdf);
        $this->sellos(data: $data, pdf: $pdf);
        if ($ruta_qr !== '') {
            if (file_exists($ruta_logo)) {
                $pdf->SetXY(10, 196);
                $pdf->Image($ruta_qr, null, null, 45, 45, 'png');
            }
        }
        if ($ruta_logo !== '') {
            if (file_exists($ruta_logo)) {
                $pdf->SetXY(10, 10);
                $pdf->Image($ruta_logo, null, null, 35, 0, 'png');
            }
        }


    }

    final public function pdf(bool              $descarga, bool $guarda, PDO $link, _doc $modelo_documento,
                              _transacciones_fc $modelo_entidad, _partida $modelo_partida,
                              _cuenta_predial   $modelo_predial, _relacion $modelo_relacion,
                              _relacionada      $modelo_relacionada, _data_impuestos $modelo_retencion,
                              _sellado          $modelo_sellado, _data_impuestos $modelo_traslado, _uuid_ext $modelo_uuid_ext,
                              int               $registro_id): array|string|bool
    {

        $factura = $modelo_entidad->get_factura(modelo_partida: $modelo_partida,
            modelo_predial: $modelo_predial, modelo_relacion: $modelo_relacion,
            modelo_relacionada: $modelo_relacionada, modelo_retencion: $modelo_retencion,
            modelo_traslado: $modelo_traslado, modelo_uuid_ext: $modelo_uuid_ext, registro_id: $registro_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener factura', data: $factura);
        }

        $ruta_qr = $modelo_documento->get_factura_documento(key_entidad_filter_id: $modelo_entidad->key_filtro_id,
            registro_id: $registro_id, tipo_documento: "qr_cfdi");
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener QR', data: $ruta_qr);
        }

        $filtro = array();
        $filtro['org_empresa.id'] = $factura['org_empresa_id'];
        $r_org_logo = (new org_logo(link: $link))->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener Logo', data: $r_org_logo);
        }
        $ruta_logo = '';
        if ($r_org_logo->n_registros > 0) {
            $ruta_logo = $r_org_logo->registros[0]['doc_documento_ruta_absoluta'];
        }

        $filtro = array();
        $filtro[$modelo_entidad->key_id] = $factura[$modelo_entidad->key_id];
        $cfdi_sellado = $modelo_sellado->filtro_and(filtro: $filtro);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener cfdi_sellado', data: $cfdi_sellado);
        }

        $cod_postal_receptor = $factura['com_sucursal_cp'];

        $rf_emisor = (new cat_sat_regimen_fiscal($link))->registro(
            $factura["org_empresa_cat_sat_regimen_fiscal_id"]);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener regimen fiscal emisor', data: $rf_emisor);
        }

        $rf_receptor = (new cat_sat_regimen_fiscal($link))->registro(
            $factura["com_cliente_cat_sat_regimen_fiscal_id"]);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener regimen fiscal receptor', data: $rf_receptor);
        }


        $data = $this->data_factura(cfdi_sellado: $cfdi_sellado, name_entidad_sellado: $modelo_sellado->tabla);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al asignar datos', data: $data);
        }

        $relacionadas = $modelo_entidad->get_data_relaciones(modelo_relacion: $modelo_relacion,
            modelo_relacionada: $modelo_relacionada, modelo_uuid_ext: $modelo_uuid_ext, registro_entidad_id: $registro_id);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener relacionadas', data: $relacionadas);
        }


        $key_observaciones = $modelo_entidad->tabla . '_observaciones';
        if (!isset($factura[$key_observaciones])) {
            $factura[$key_observaciones] = '';
        }

        $key_exportacion = $modelo_entidad->tabla . '_exportacion';
        $key_fecha = $modelo_entidad->tabla . '_fecha';
        $key_folio = $modelo_entidad->tabla . '_folio';

        $aplica_plantilla = false;
        $generales = new generales();

        if (!isset($generales->ruta_factura_pdf)) {
            return $this->error->error(mensaje: 'Error no existe la propiedad ruta_factura_pdf', data: $generales);
        }

        if ($generales->ruta_factura_pdf) {
            if (file_exists($generales->ruta_factura_pdf)) {
                $aplica_plantilla = true;
            }
        }


        if (!$aplica_plantilla) {
            $pdf = new pdf();
            $pdf->header(cfdi: $factura['cat_sat_uso_cfdi_descripcion'], cod_postal: $factura['dp_cp_descripcion'],
                cod_postal_receptor: $cod_postal_receptor, csd: $factura['fc_csd_serie'],
                efecto: $factura['cat_sat_tipo_de_comprobante_descripcion'],
                exportacion: $factura[$key_exportacion], fecha: $factura[$key_fecha],
                folio: $factura[$key_folio], folio_fiscal: $data->folio_fiscal,
                nombre_emisor: $factura['org_empresa_razon_social'], nombre_receptor: $factura['com_cliente_razon_social'],
                observaciones: $factura[$key_observaciones],
                regimen_fiscal: $rf_emisor['cat_sat_regimen_fiscal_descripcion'],
                regimen_fiscal_receptor: $rf_receptor['cat_sat_regimen_fiscal_descripcion'],
                rfc_emisor: $factura['org_empresa_rfc'], rfc_receptor: $factura['com_cliente_rfc'], ruta_logo: $ruta_logo);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al maquetar header', data: $pdf);
            }


        } else {
            ob_clean();

            $tabla = $modelo_entidad->tabla;
            $tabla_partida = $modelo_partida->tabla;

            $fmt = new NumberFormatter('es_MX', NumberFormatter::CURRENCY);
            $pdf = new Fpdi();


            $this->base_pdf(tabla: $tabla, data: $data, factura: $factura, pdf: $pdf, relacionadas: $relacionadas,
                ruta_logo: $ruta_logo, ruta_qr: $ruta_qr);

            $border = 0;
            $h = 2.5;
            $y = 96.5;
            $partidas = 1;
            foreach ($factura['partidas'] as $partida) {
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
                $fc_partida_valor_unitario = $fmt->formatCurrency($fc_partida_valor_unitario, "MXN");

                $x = 155;
                $pdf->SetXY($x, $y);
                $pdf->MultiCell(24, $h, $fc_partida_valor_unitario, $border, 'C');

                $fc_partida_sub_total = round($partida[$tabla_partida.'_sub_total'], 2);
                $fc_partida_sub_total = $fmt->formatCurrency($fc_partida_sub_total, "MXN");

                $x = 182;
                $pdf->SetXY($x, $y);
                $pdf->MultiCell(24, $h, $fc_partida_sub_total, $border, 'C');

                $y = $y + $h;

                $partidas++;
                $mod = $partidas % 6;
                if ($mod === 0) {
                    $y = 96.5;
                    $this->base_pdf(data: $data, factura: $factura, pdf: $pdf, relacionadas: $relacionadas, ruta_logo: $ruta_logo, ruta_qr: $ruta_qr);
                }
            }

            $key_serie = $modelo_entidad->tabla . '_serie';
            $key_folio = $modelo_entidad->tabla . '_folio';
            $nombre_documento = $factura[$key_serie] . $factura[$key_folio];


            if ($descarga) {
                $pdf->Output($nombre_documento . '.pdf', 'D');
                return $nombre_documento;
            }
            if ($guarda) {
                $path_base = (new generales())->path_base;
                $path_base_archivos = $path_base . 'archivos';
                if (!file_exists($path_base_archivos)) {
                    mkdir($path_base_archivos);
                }
                $nombre_documento = $path_base_archivos . '/' . $nombre_documento . '.pdf';


                $pdf->Output('F', $nombre_documento);
                return $nombre_documento;
            }

            $pdf->Output();

        }


        $rs = $pdf->data_relacionados(name_entidad: $modelo_entidad->tabla, relacionadas: $relacionadas);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al maquetar relacionadas', data: $rs);
        }

        if ($modelo_entidad->tabla !== 'fc_complemento_pago') {
            $rs = $pdf->conceptos(conceptos: $factura['partidas'], link: $link, name_entidad_partida: $modelo_partida->tabla);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al maquetar conceptos', data: $rs);
            }
        }


        if ($modelo_entidad->tabla === 'fc_complemento_pago') {

            $r_fc_pago_pago = (new fc_pago_pago(link: $link))->filtro_and(filtro: $filtro);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al maquetar conceptos', data: $r_fc_pago_pago);
            }
            $fc_pago_pagos = $r_fc_pago_pago->registros;

            $r_fc_pago_total = (new fc_pago_total(link: $link))->filtro_and(filtro: $filtro);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al maquetar conceptos', data: $r_fc_pago_total);
            }
            $fc_pago_totales = $r_fc_pago_total->registros;

            $r_fc_docto_relacionado = (new fc_docto_relacionado(link: $link))->filtro_and(filtro: $filtro);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al maquetar conceptos', data: $r_fc_docto_relacionado);
            }
            $fc_doctos_relacionados = $r_fc_docto_relacionado->registros;


            $rs = $pdf->complemento_pago(fc_doctos_relacionados: $fc_doctos_relacionados, fc_pago_pagos: $fc_pago_pagos,
                fc_pago_totales: $fc_pago_totales);
            if (errores::$error) {
                return $this->error->error(mensaje: 'Error al maquetar conceptos', data: $rs);
            }
        }


        $key_sub_total_base = $modelo_entidad->tabla . '_sub_total_base';
        $key_total = $modelo_entidad->tabla . '_total';

        $pdf->totales(descuento: $factura['total_descuento'], moneda: $factura['cat_sat_moneda_descripcion'],
            subtotal: $factura[$key_sub_total_base], forma_pago: $factura['cat_sat_forma_pago_descripcion'],
            imp_trasladados: $factura['total_impuestos_trasladados'],
            imp_retenidos: $factura['total_impuestos_retenidos'],
            metodo_pago: $factura['cat_sat_metodo_pago_descripcion'], total: $factura[$key_total]);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al maquetar totales', data: $pdf);
        }

        $pdf->complementos(ruta_documento: $ruta_qr, complento: $data->complento, rfc_proveedor: $data->rfc_proveedor,
            fecha: $data->fecha_timbrado, no_certificado: $data->no_certificado);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al maquetar complementos', data: $pdf);
        }

        $pdf->sellos(sello_cfdi: $data->sello_cfdi, sello_sat: $data->sello_sat);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al maquetar sellos', data: $pdf);
        }

        $pdf->footer(descripcion: "efacturacion.com.mx");
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al generar pdf', data: $pdf);
        }

        $key_serie = $modelo_entidad->tabla . '_serie';
        $key_folio = $modelo_entidad->tabla . '_folio';

        $nombre_documento = $factura[$key_serie] . $factura[$key_folio];

        $nombre_documento = $pdf->guardar(nombre_documento: $nombre_documento, descarga: $descarga, guarda: $guarda);
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al generar pdf', data: $pdf);
        }
        return $nombre_documento;
    }
}
