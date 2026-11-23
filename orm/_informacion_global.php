<?php
namespace gamboamartin\facturacion\models;

use DOMDocument;
use DateTimeImmutable;
use DateTimeZone;
use gamboamartin\errores\errores;
use JsonException;

/** Información fiscal capturada por factura; no consulta BD ni servicios externos. */
final class _informacion_global
{
    private const CAMPOS = ['global_periodicidad', 'global_meses', 'global_anio'];
    private int $anio_actual;

    public function __construct(?int $anio_actual = null)
    {
        $this->anio_actual = $anio_actual
            ?? (int)(new DateTimeImmutable('now', new DateTimeZone('America/Mexico_City')))->format('Y');
    }

    public function normaliza_registro(array $registro): array
    {
        if (array_intersect(self::CAMPOS, array_keys($registro)) === []) {
            return $registro;
        }
        foreach (self::CAMPOS as $campo) {
            $valor = $registro[$campo] ?? null;
            if ($valor !== null && !is_string($valor) && !is_int($valor)) {
                return $this->error('Los datos de información global deben ser valores simples.');
            }
            $registro[$campo] = $valor === null || trim((string)$valor) === ''
                ? null : trim((string)$valor);
        }
        $valores = array_intersect_key($registro, array_flip(self::CAMPOS));
        if (count(array_filter($valores, static fn ($valor) => $valor !== null)) === 0) {
            return $registro;
        }
        $validacion = $this->valida_formato($valores);
        return errores::$error ? $validacion : $registro;
    }

    /** Devuelve el nodo fiscal, vacío cuando no corresponde, o un error sin datos del comprobante. */
    public function resolver(array $registro, array $comprobante, array $emisor, array $receptor,
                             string $entidad = 'fc_factura'): array
    {
        if ($entidad !== 'fc_factura') {
            return [];
        }
        $campos = [];
        foreach (self::CAMPOS as $campo) {
            $campos[$campo] = $registro['fc_factura_'.$campo] ?? $registro[$campo] ?? null;
        }
        $campos = $this->normaliza_registro($campos);
        if (errores::$error) {
            return $campos;
        }
        $nombre = strtoupper(preg_replace('/\s+/u', ' ', trim((string)($receptor['nombre'] ?? ''))) ?? '');
        $rfc = strtoupper(trim((string)($receptor['rfc'] ?? '')));
        $tipo = strtoupper(trim((string)($comprobante['tipo_de_comprobante'] ?? '')));
        $es_global = $tipo === 'I' && $rfc === 'XAXX010101000' && $nombre === 'PUBLICO EN GENERAL';
        $hay_datos = $campos['global_periodicidad'] !== null;
        if (!$es_global) {
            return $hay_datos
                ? $this->error('La información global solo corresponde a una factura de ingreso para PUBLICO EN GENERAL con RFC genérico nacional.')
                : [];
        }
        if (!$hay_datos) {
            return $this->error('Para facturar a PUBLICO EN GENERAL captura periodicidad, mes o bimestre y año de la información global.');
        }
        if ($campos['global_periodicidad'] === '05'
            && (string)($emisor['regimen_fiscal'] ?? '') !== '621') {
            return $this->error('La periodicidad bimestral de información global requiere el régimen fiscal 621 del emisor.');
        }
        return ['Periodicidad' => $campos['global_periodicidad'], 'Meses' => $campos['global_meses'],
            'Año' => $campos['global_anio']];
    }

    public function aplicar(string $contenido, string $tipo, array $informacion): array|string
    {
        if ($informacion === []) {
            return $contenido;
        }
        if (array_keys($informacion) !== ['Periodicidad', 'Meses', 'Año']) {
            return $this->error('La estructura de información global no es válida.');
        }
        if ($tipo === 'json') {
            try {
                $datos = json_decode($contenido, true, 512, JSON_THROW_ON_ERROR);
                if (!is_array($datos) || !isset($datos['Comprobante']) || !is_array($datos['Comprobante'])) {
                    return $this->error('No se encontró el comprobante JSON para integrar la información global.');
                }
                if (array_key_exists('InformacionGlobal', $datos['Comprobante'])) {
                    return $this->error('El comprobante JSON ya contiene información global.');
                }
                $datos['Comprobante']['InformacionGlobal'] = $informacion;
                return json_encode($datos, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
            } catch (JsonException $error) {
                return $this->error('No se pudo integrar la información global en el comprobante JSON.');
            }
        }
        if ($tipo !== 'xml') {
            return $this->error('El formato del comprobante no permite integrar información global.');
        }
        $anterior = libxml_use_internal_errors(true);
        try {
            $dom = new DOMDocument();
            if (!$dom->loadXML($contenido, LIBXML_NONET) || $dom->doctype !== null) {
                return $this->error('No se pudo leer el comprobante XML para integrar la información global.');
            }
            $raiz = $dom->documentElement;
            $namespace = 'http://www.sat.gob.mx/cfd/4';
            if ($raiz === null || $raiz->localName !== 'Comprobante' || $raiz->namespaceURI !== $namespace) {
                return $this->error('La raíz del comprobante XML no corresponde a CFDI 4.0.');
            }
            $existentes = $raiz->getElementsByTagNameNS($namespace, 'InformacionGlobal');
            if ($existentes->length > 0) {
                return $this->error('El comprobante XML ya contiene información global.');
            }
            $nombre = $raiz->prefix ? $raiz->prefix.':InformacionGlobal' : 'InformacionGlobal';
            $nodo = $dom->createElementNS($namespace, $nombre);
            foreach ($informacion as $campo => $valor) {
                $nodo->setAttribute($campo, (string)$valor);
            }
            $raiz->insertBefore($nodo, $raiz->firstChild);
            $resultado = $dom->saveXML();
            return $resultado === false ? $this->error('No se pudo serializar la información global XML.') : $resultado;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($anterior);
        }
    }

    private function valida_formato(array $campos): array
    {
        if (in_array(null, $campos, true)) {
            return $this->error('La información global debe incluir periodicidad, mes o bimestre y año; no se permiten datos parciales.');
        }
        if (!in_array($campos['global_periodicidad'], ['01', '02', '03', '04', '05'], true)) {
            return $this->error('La periodicidad de información global no pertenece al catálogo SAT.');
        }
        $mes = $campos['global_meses'];
        $bimestral = $campos['global_periodicidad'] === '05';
        if (!preg_match('/^\d{2}$/D', $mes)
            || ($bimestral ? ((int)$mes < 13 || (int)$mes > 18) : ((int)$mes < 1 || (int)$mes > 12))) {
            return $this->error($bimestral
                ? 'La periodicidad bimestral requiere una clave de meses del 13 al 18.'
                : 'La información global requiere una clave de mes del 01 al 12.');
        }
        $anio = $campos['global_anio'];
        if (!preg_match('/^\d{4}$/D', $anio) || (int)$anio < max(2019, $this->anio_actual - 5)
            || (int)$anio > $this->anio_actual) {
            return $this->error('El año de información global debe tener cuatro dígitos y corresponder al año actual o a los cinco ejercicios anteriores, a partir de 2019.');
        }
        return [];
    }

    private function error(string $mensaje): array
    {
        return (new errores())->error(mensaje: $mensaje, data: []);
    }
}
