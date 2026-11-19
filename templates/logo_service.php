<?php

use gamboamartin\organigrama\models\org_empresa;
use gamboamartin\organigrama\models\org_logo;
use gamboamartin\documento\models\doc_documento;
use gamboamartin\errores\errores;

if (!function_exists('base_url_framework')) {
    function base_url_framework(): string
    {
        $https = (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
        );

        $scheme = $https ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';


        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $dir = str_replace('\\', '/', dirname($scriptName));
        $dir = rtrim($dir, '/');

        if ($dir === '' || $dir === '.') {
            $dir = '';
        }

        return $scheme . '://' . $host . $dir . '/';
    }
}

if (!function_exists('asset_url_framework')) {
    function asset_url_framework(string $ruta_relativa): string
    {
        return rtrim(base_url_framework(), '/') . '/' . ltrim($ruta_relativa, '/');
    }
}

require_once __DIR__ . '/logo_principal_lector.php';

/** Aísla los errores de una consulta opcional, dejando diagnóstico solo en servidor. */
function consultar_branding_framework(callable $consulta, string &$etapa): ?string
{
    if (errores::$error) {
        return null;
    }
    $errorAnterior = errores::$error;
    $tieneSalida = property_exists(errores::class, 'out');
    $salidaAnterior = $tieneSalida ? errores::$out : null;
    $tieneResultado = isset($_SESSION) && array_key_exists('error_resultado', $_SESSION);
    $resultadoAnterior = $tieneResultado ? $_SESSION['error_resultado'] : null;
    try {
        $resultado = $consulta();
        if (errores::$error) {
            error_log('[facturacion.branding] Fallo de consulta; etapa=' . $etapa);
            return null;
        }
        return $resultado;
    } catch (Throwable $error) {
        error_log('[facturacion.branding] Excepcion al resolver branding; etapa=' . $etapa);
        return null;
    } finally {
        errores::$error = $errorAnterior;
        if ($tieneSalida) {
            errores::$out = $salidaAnterior;
        }
        if ($tieneResultado) {
            $_SESSION['error_resultado'] = $resultadoAnterior;
        } else {
            unset($_SESSION['error_resultado']);
        }
    }
}

/**
 * Branding público existente. No representa autorización de usuario ni selecciona
 * empresa, documento o archivo a partir de GET. Los modelos son la única fuente.
 */
function resolver_logo_principal_framework($link, string $almacen): ?string
{
    if (!$link || errores::$error) {
        return null;
    }
    $etapa = 'org_empresa';
    return consultar_branding_framework(static function () use ($link, $almacen, &$etapa): ?string {
        $empresas = (new org_empresa($link))->filtro_and(
            aplica_seguridad: false,
            columnas: ['org_empresa_id'],
            filtro: ['org_empresa.status' => 'activo'],
            limit: 2,
            order: ['org_empresa.id' => 'ASC']
        );
        if (errores::$error) {
            return null;
        }
        $filasEmpresa = $empresas->registros;
        if (count($filasEmpresa) !== 1) {
            return null;
        }
        $empresaId = (int)($filasEmpresa[0]['org_empresa_id'] ?? 0);
        if ($empresaId <= 0) {
            return null;
        }
        $etapa = 'org_logo';
        $logos = (new org_logo($link))->filtro_and(
            aplica_seguridad: false,
            columnas: ['org_logo_doc_documento_id', 'org_logo_org_empresa_id'],
            filtro: [
                'org_logo.status' => 'activo',
                'org_logo.es_principal' => 'activo',
                'org_logo.org_empresa_id' => $empresaId
            ],
            limit: 2,
            order: ['org_logo.id' => 'DESC']
        );
        if (errores::$error) {
            return null;
        }
        $filasLogo = $logos->registros;
        if (count($filasLogo) !== 1) {
            return null;
        }
        $logo = $filasLogo[0] ?? [];
        $documentoId = (int)($logo['org_logo_doc_documento_id'] ?? 0);
        if ($documentoId <= 0) {
            return null;
        }
        $etapa = 'doc_documento';
        $documentos = (new doc_documento($link))->filtro_and(
            aplica_seguridad: false,
            columnas: ['doc_documento_ruta_relativa', 'doc_documento_doc_tipo_documento_id'],
            filtro: ['doc_documento.id' => $documentoId],
            limit: 1
        );
        if (errores::$error) {
            return null;
        }
        $documento = $documentos->registros[0] ?? [];
        $ruta = (string)($documento['doc_documento_ruta_relativa'] ?? '');
        if ($ruta === '') {
            return null;
        }
        if ((int)($logo['org_logo_org_empresa_id'] ?? 0) !== $empresaId
            || (int)($documento['doc_documento_doc_tipo_documento_id'] ?? 0) !== 9) {
            return null;
        }
        $etapa = 'archivo_logo';
        return LectorLogoPrincipal::leer($almacen, $ruta);
    }, $etapa);
}

if (!function_exists('logo_empresa_url_framework')) {
    function logo_empresa_url_framework($link): ?string
    {
        static $cache = null;
        static $consultado = false;
        if (!$consultado) {
            $consultado = true;
            $cache = resolver_logo_principal_framework(
                $link,
                dirname(__DIR__) . '/archivos/doc_documento'
            );
        }
        return $cache;
    }
}

if (!function_exists('nombre_empresa_framework')) {
    function nombre_empresa_framework($link): ?string
    {
        static $cache = null;
        static $ya = false;

        if ($ya) {
            return $cache;
        }
        $ya = true;

        if (!$link || errores::$error) {
            return null;
        }

        $etapa = 'nombre_empresa';
        $cache = consultar_branding_framework(static function () use ($link): ?string {
            $modelo_empresa = new org_empresa($link);
            $r_emp = $modelo_empresa->filtro_and(
                aplica_seguridad: false,
                columnas: ['org_empresa_id', 'org_empresa_nombre_comercial'],
                filtro: ['org_empresa.status' => 'activo'],
                limit: 2,
                order: ['org_empresa.id' => 'ASC']
            );
            if (errores::$error) {
                return null;
            }

            if (count($r_emp->registros) !== 1) {
                return null;
            }
            $nombre = (string)($r_emp->registros[0]['org_empresa_nombre_comercial'] ?? '');
            if ($nombre === '') {
                return null;
            }

            return $nombre;
        }, $etapa);
        return $cache;
    }
}
