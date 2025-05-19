<?php

namespace gamboamartin\facturacion\models;

use DOMDocument;
use DOMXPath;
use gamboamartin\errores\errores;

class _transferencia
{
    private const ENDPOINT_VALIDA = '/valida.do';
    private const ENDPOINT_DESCARGA = "/descargaComprobanteSPEI.do";

    public static function validar(string $fecha, string $clave_rastreo, string $insitucion_emisora, string $insitucion_receptora,
                                   string $cuenta, float $monto, string $receptor_participante): array
    {
        $body = [
            'fecha' => $fecha,
            'criterio' => $clave_rastreo,
            'emisor' => $insitucion_emisora,
            'receptor' => $insitucion_receptora,
            'cuenta' => $cuenta,
            'monto' => $monto,
            'receptorParticipante' => $receptor_participante,
        ];

        $response = (new _cliente())->post(_transferencia::ENDPOINT_VALIDA, $body);
        if (errores::$error) {
            return (new errores())->error(mensaje: 'Error al ejecutar la consulta', data: $response);
        }

        $valida = self::valida_salida($response);
        if (errores::$error) {
            return (new errores())->error(mensaje: 'La consulta no es correcta', data: $valida);
        }

        $descarga = self::descarga(html: $response);
        if (errores::$error) {
            return (new errores())->error(mensaje: 'Error al descargar el archivo', data: $descarga);
        }

        print_r($response);
        exit();
    }

    private static function descarga(string $html)
    {
        $html = str_replace("Lo sentimos, por el momento no es posible generar el CEP. ", "", $html);

        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        $div = $xpath->query('//*[@id="consultaMISPEI"]')->item(0);

        $html = "";

        if ($div) {
            foreach ($div->childNodes as $child) {
                $html .= $dom->saveHTML($child);
            }
        } else {
            return (new errores())->error(mensaje: "No se encontró el div con id 'consultaMISPEI'.", data: $html);
        }

        $body = [
            'htmlCEPImprimir' => $html
        ];

        $response = (new _cliente())->post(_transferencia::ENDPOINT_DESCARGA, $body);
        if (errores::$error) {
            return (new errores())->error(mensaje: 'Error al ejecutar la consulta', data: $response);
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="cep.pdf"');
        header('Content-Transfer-Encoding: binary');
        header('Accept-Ranges: bytes');
        echo $response;
        exit;
    }


private
static function valida_salida(string $html): ?array
{
    libxml_use_internal_errors(true);

    if (stripos($html, '<meta charset=') === false) {
        $html = '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">' . $html;
    }

    $dom = new DOMDocument();
    $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

    $xpath = new DOMXPath($dom);

    $mensaje = self::extraer_texto($xpath, [
        '//div[@id="#error_div"]//p',
        '//div[contains(@class, "cuerpo-msg")]//div[contains(@class, "info")]//strong[contains(text(), "El SPEI no ha recibido una orden de pago")]'
    ]);
    if ($mensaje !== null) {
        return (new errores())->error(mensaje: $mensaje, data: $html);
    }

    return null;
}

private
static function extraer_texto(DOMXPath $xpath, array $expresiones): ?string
{
    foreach ($expresiones as $expr) {
        $nodos = $xpath->query($expr);
        if ($nodos !== false && $nodos->length > 0) {
            return trim($nodos->item(0)->textContent);
        }
    }
    return null;
}
}
