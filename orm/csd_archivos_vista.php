<?php
declare(strict_types=1);
namespace gamboamartin\facturacion\models;

use gamboamartin\administrador\models\adm_accion;
use gamboamartin\errores\errores;
use PDO;

/** Metadatos CER para HTML autorizado; no abre archivos ni ofrece descargas. */
final class csd_archivos_vista
{
    public function cargar(PDO $link, int $csdId): array
    {
        $vacio = ['estado' => 'no_autorizado', 'filas' => []];
        if ($csdId <= 0 || ($_SESSION['activa'] ?? null) !== 1
            || (int)($_SESSION['usuario_id'] ?? 0) <= 0 || (int)($_SESSION['grupo_id'] ?? 0) <= 0) {
            return $vacio;
        }
        // Mismo permiso que la petición del DataTable de subir_cer.
        $permiso = (new adm_accion($link))->permiso(accion: 'get_data', seccion: 'fc_cer_csd');
        if (errores::$error || $permiso !== true) {
            return $vacio;
        }
        $consulta = (new fc_cer_csd($link))->filtro_and(
            columnas: ['fc_cer_csd_id', 'fc_cer_csd_fc_csd_id', 'doc_documento_descripcion'],
            filtro: ['fc_csd.id' => $csdId]
        );
        if (errores::$error || !is_object($consulta) || !isset($consulta->registros)
            || !is_array($consulta->registros)) {
            return ['estado' => 'error', 'filas' => []];
        }
        $filas = [];
        foreach ($consulta->registros as $registro) {
            if (!is_array($registro) || (int)($registro['fc_cer_csd_fc_csd_id'] ?? 0) !== $csdId
                || (int)($registro['fc_cer_csd_id'] ?? 0) <= 0
                || !is_string($registro['doc_documento_descripcion'] ?? null)) {
                return ['estado' => 'error', 'filas' => []];
            }
            $filas[] = ['id' => (int)$registro['fc_cer_csd_id'],
                'descripcion' => $registro['doc_documento_descripcion'], 'tipo' => 'CER'];
        }
        return ['estado' => 'disponible', 'filas' => $filas];
    }
}
