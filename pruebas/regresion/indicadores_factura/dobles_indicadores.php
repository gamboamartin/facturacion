<?php
declare(strict_types=1);

namespace gamboamartin\facturacion\models;

/** Solo responde consultas de prueba; no carga configuración ni abre conexiones. */
class fc_cfdi_sellado
{
    public static array $registros = [];
    public static array $llamadas = [];
    public static bool $fallar = false;

    public function __construct(public \PDO $link)
    {
    }

    public function filtro_and(array $columnas = [], array $filtro = []): \stdClass
    {
        self::$llamadas[] = compact('columnas', 'filtro');
        \gamboamartin\errores\errores::$error = self::$fallar;
        return (object)['registros' => self::$registros];
    }
}
