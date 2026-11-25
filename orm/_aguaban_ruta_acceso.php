<?php
declare(strict_types=1);

namespace gamboamartin\facturacion\models;

use PDO;
use RuntimeException;

/** Permisos actuales de la sección: la sesión no sustituye el catálogo de seguridad. */
final class _aguaban_ruta_acceso
{
    public const ACCIONES = ['lista', 'alta', 'alta_bd', 'modifica', 'modifica_bd', 'consulta', 'elimina', 'elimina_bd', 'status'];

    public function __construct(private readonly PDO $link, private readonly int $usuarioId)
    {
    }

    public static function usuario_sesion(): int
    {
        $id = $_SESSION['usuario_id'] ?? null;
        if ((!is_int($id) && !is_string($id)) || !preg_match('/^[1-9][0-9]*$/D', (string)$id)) {
            return 0;
        }
        $id = filter_var($id, FILTER_VALIDATE_INT);
        return is_int($id) && $id > 0 ? $id : 0;
    }

    public function permisos(): array
    {
        $permisos = array_fill_keys(self::ACCIONES, false);
        if ($this->usuarioId <= 0) {
            return $permisos;
        }
        $q = $this->link->prepare("SELECT DISTINCT a.descripcion
            FROM adm_usuario u
            INNER JOIN adm_grupo g ON g.id=u.adm_grupo_id
            INNER JOIN adm_accion_grupo ag ON ag.adm_grupo_id=g.id
            INNER JOIN adm_accion a ON a.id=ag.adm_accion_id
            INNER JOIN adm_seccion s ON s.id=a.adm_seccion_id
            WHERE u.id=? AND u.status='activo' AND g.status='activo'
              AND g.codigo='PA' AND g.descripcion='Administrador Sistema'
              AND ag.status='activo' AND a.status='activo' AND s.status='activo'
              AND s.descripcion='aguaban_ruta'");
        if (!$q || !$q->execute([$this->usuarioId])) {
            throw new RuntimeException('No se pudieron verificar los permisos de rutas.');
        }
        foreach ($q->fetchAll(PDO::FETCH_COLUMN) as $accion) {
            if (array_key_exists($accion, $permisos)) {
                $permisos[$accion] = true;
            }
        }
        return $permisos;
    }

    public function puede(string $accion): bool
    {
        return in_array($accion, self::ACCIONES, true) && $this->permisos()[$accion];
    }

    public function exigir(string $accion): void
    {
        if (!$this->puede($accion)) {
            throw new RuntimeException('No tienes permiso para realizar esta operación de rutas.', 403);
        }
    }
}
