<?php

use gamboamartin\organigrama\models\org_empresa;
use gamboamartin\organigrama\models\org_logo;
use gamboamartin\documento\models\doc_documento;
use gamboamartin\errores\errores;

function logo_empresa_url_framework($link, string $base = '/facturacion/'): ?string
{
    static $cache = null;
    static $ya = false;

    if ($ya) return $cache;
    $ya = true;

    if (!$link) return null;

    try {
        $org_empresa_id = (int)($_GET['org_empresa_id'] ?? 0);

        // Si no viene en GET, tomamos 1 empresa activa (tu misma lógica)
        if ($org_empresa_id <= 0) {
            $modelo_empresa = new org_empresa($link);
            $r_emp = $modelo_empresa->filtro_and(
                aplica_seguridad: false,
                columnas: ['org_empresa_id'],
                filtro: ['org_empresa.status' => 'activo'],
                limit: 1,
                order: ['org_empresa.id' => 'ASC']
            );
            if (errores::$error) return null;
            $org_empresa_id = (int)($r_emp->registros[0]['org_empresa_id'] ?? 0);
        }

        if ($org_empresa_id <= 0) return null;

        $modelo_logo = new org_logo($link);
        $r_logo = $modelo_logo->filtro_and(
            aplica_seguridad: false,
            columnas: ['org_logo_doc_documento_id'],
            filtro: [
                'org_logo.status' => 'activo',
                'org_logo.es_principal' => 'activo',
                'org_logo.org_empresa_id' => $org_empresa_id
            ],
            limit: 1,
            order: ['org_logo.id' => 'DESC']
        );
        if (errores::$error) return null;

        $doc_id = (int)($r_logo->registros[0]['org_logo_doc_documento_id'] ?? 0);
        if ($doc_id <= 0) return null;

        $modelo_doc = new doc_documento($link);
        $r_doc = $modelo_doc->filtro_and(
            aplica_seguridad: false,
            columnas: ['doc_documento_ruta_relativa'],
            filtro: ['doc_documento.id' => $doc_id],
            limit: 1
        );
        if (errores::$error) return null;

        $ruta = (string)($r_doc->registros[0]['doc_documento_ruta_relativa'] ?? '');
        if ($ruta === '') return null;

        $cache = rtrim($base, '/') . '/' . ltrim($ruta, '/');
        return $cache;

    } catch (Throwable $e) {
        return null;
    }
}