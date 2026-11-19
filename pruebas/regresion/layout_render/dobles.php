<?php
declare(strict_types=1);

namespace config;

/** Solo resuelve fragmentos ficticios creados por cada prueba; no carga config real. */
final class views
{
    public static string $rutaTemporal = '';
    public string $ruta_templates;

    public function __construct()
    {
        if (self::$rutaTemporal === '') {
            throw new \LogicException('Falta preparar los fragmentos aislados de la vista.');
        }
        $this->ruta_templates = self::$rutaTemporal . '/';
    }
}
