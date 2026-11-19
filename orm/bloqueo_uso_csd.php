<?php
declare(strict_types=1);

namespace gamboamartin\facturacion\models;

use PDO;
use RuntimeException;
use Throwable;

/** Coordina uso y baja del mismo CSD, sin alterar transacciones del llamador. */
final class bloqueo_uso_csd
{
    public function __construct(private readonly PDO $link)
    {
    }

    public function ejecutar(int $id, callable $operacion): mixed
    {
        if ($id <= 0) {
            throw new RuntimeException('El identificador de CSD debe ser positivo.');
        }
        $base = $this->link->query('SELECT DATABASE()');
        $nombreBase = $base === false ? false : $base->fetchColumn();
        if (!is_string($nombreBase) || $nombreBase === '') {
            throw new RuntimeException('No se pudo identificar la base del CSD.');
        }
        // 63 caracteres, sin exponer nombre de base ni datos del certificado.
        $nombre = 'fc_csd_uso_' . substr(hash('sha256', $nombreBase . ':' . $id), 0, 52);
        $adquirir = $this->link->prepare('SELECT GET_LOCK(?, 0)');
        if ($adquirir === false || !$adquirir->execute([$nombre]) || (int)$adquirir->fetchColumn() !== 1) {
            throw new RuntimeException('El CSD está en uso; intenta nuevamente al concluir la operación.');
        }

        $falloOriginal = null;
        try {
            $consulta = $this->link->prepare('SELECT id FROM fc_csd WHERE id = ? FOR UPDATE');
            if ($consulta === false || !$consulta->execute([$id])) {
                throw new RuntimeException('No se pudo comprobar la existencia del CSD.');
            }
            if ($consulta->fetchColumn() === false) {
                throw new RuntimeException('El CSD no existe.');
            }
            return $operacion();
        } catch (Throwable $error) {
            $falloOriginal = $error;
            throw $error;
        } finally {
            try {
                $liberar = $this->link->prepare('SELECT RELEASE_LOCK(?)');
                if ($liberar === false || !$liberar->execute([$nombre]) || (int)$liberar->fetchColumn() !== 1) {
                    throw new RuntimeException('No se pudo liberar el bloqueo de uso del CSD.');
                }
            } catch (Throwable $errorLiberacion) {
                if ($falloOriginal === null) {
                    throw new RuntimeException('No se pudo liberar el bloqueo de uso del CSD.');
                }
                // Mantener la excepción original; no imprimir SQL ni datos de ninguna excepción.
                error_log('[facturacion.csd] Fallo al liberar bloqueo tras una operacion fallida.');
            }
        }
    }
}
