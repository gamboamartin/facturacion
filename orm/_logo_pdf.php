<?php
namespace gamboamartin\facturacion\models;

use PDO;
use Throwable;

require_once dirname(__DIR__).'/templates/logo_principal_lector.php';

/** Logo habilitado para PDF del emisor; una imagen opcional nunca sustituye a otra empresa. */
final class _logo_pdf
{
    private string $raiz;

    public function __construct(?string $raiz = null)
    {
        $this->raiz = rtrim($raiz ?? dirname(__DIR__), DIRECTORY_SEPARATOR);
    }

    /** @return array{ruta:string,uri:string,tipo:string,ancho:int,alto:int}|null */
    public function para_empresa(PDO $link, int $empresa_id): ?array
    {
        if ($empresa_id <= 0) {
            return null;
        }
        try {
            $consulta = $link->prepare(
                "SELECT d.ruta_relativa
                 FROM org_logo l INNER JOIN doc_documento d ON d.id = l.doc_documento_id
                 WHERE l.org_empresa_id = :empresa AND l.status = 'activo'
                   AND l.logo_pdf = 'activo' AND d.status = 'activo' AND d.doc_tipo_documento_id = 9
                 ORDER BY l.id DESC LIMIT 1"
            );
            if (!$consulta || !$consulta->execute(['empresa' => $empresa_id])) {
                error_log('[facturacion.pdf.logo] Consulta de logo no disponible.');
                return null;
            }
            $fila = $consulta->fetch(PDO::FETCH_ASSOC);
            if (!$fila) {
                return null;
            }
            $relativa = (string)($fila['ruta_relativa'] ?? '');
            $uri = \LectorLogoPrincipal::leer($this->raiz.'/archivos/doc_documento', $relativa);
            if ($uri === null) {
                return null;
            }
            $prefijo = str_starts_with($uri, 'data:image/jpeg;base64,')
                ? 'data:image/jpeg;base64,' : 'data:image/png;base64,';
            $contenido = base64_decode(substr($uri, strlen($prefijo)), true);
            $dimensiones = $contenido === false ? false : getimagesizefromstring($contenido);
            if ($dimensiones === false) {
                return null;
            }
            return [
                'ruta' => $this->raiz.'/'.$relativa,
                'uri' => $uri,
                'tipo' => $dimensiones['mime'] === 'image/jpeg' ? 'JPG' : 'PNG',
                'ancho' => (int)$dimensiones[0],
                'alto' => (int)$dimensiones[1]
            ];
        } catch (Throwable $error) {
            // No exponer rutas, datos de empresa ni errores SQL en un comprobante.
            error_log('[facturacion.pdf.logo] No se pudo cargar la imagen opcional.');
            return null;
        }
    }

    /** Resuelve la empresa del RFC que consta en el XML; nunca usa la empresa global de la interfaz. */
    public function para_rfc(PDO $link, string $rfc): ?array
    {
        $rfc = mb_strtoupper(trim($rfc), 'UTF-8');
        if ($rfc === '') {
            return null;
        }
        try {
            $consulta = $link->prepare(
                "SELECT id FROM org_empresa
                 WHERE UPPER(TRIM(rfc)) = :rfc AND status = 'activo' ORDER BY id LIMIT 2"
            );
            if (!$consulta || !$consulta->execute(['rfc' => $rfc])) {
                error_log('[facturacion.pdf.logo] Consulta de emisor no disponible.');
                return null;
            }
            $empresas = $consulta->fetchAll(PDO::FETCH_ASSOC);
            if (count($empresas) !== 1) {
                return null;
            }
            return $this->para_empresa($link, (int)$empresas[0]['id']);
        } catch (Throwable $error) {
            error_log('[facturacion.pdf.logo] No se pudo identificar el emisor.');
            return null;
        }
    }

    /** Dimensiones que caben en la caja propia de cada formato, sin deformar la imagen. */
    public static function caja(array $logo, float $max_ancho, float $max_alto): array
    {
        $ancho = (int)($logo['ancho'] ?? 0);
        $alto = (int)($logo['alto'] ?? 0);
        if ($ancho <= 0 || $alto <= 0 || $max_ancho <= 0 || $max_alto <= 0) {
            return ['ancho' => 0.0, 'alto' => 0.0];
        }
        $escala = min($max_ancho / $ancho, $max_alto / $alto);
        return ['ancho' => $ancho * $escala, 'alto' => $alto * $escala];
    }
}
