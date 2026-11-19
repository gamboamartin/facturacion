<?php
declare(strict_types=1);

namespace gamboamartin\errores;

/** Contrato mínimo de errores para verificar rechazos antes de datos/PAC. */
final class errores
{
    public static bool $error = false;

    public function error(string $mensaje, mixed $data): array
    {
        self::$error = true;
        return ['mensaje' => $mensaje, 'data' => $data];
    }
}
