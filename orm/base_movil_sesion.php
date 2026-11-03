<?php
namespace gamboamartin\facturacion\models;

use base\orm\modelo;
use PDO;

/**
 * Sesiones de la app (API base_movil). Cada token JWT tiene aquí su fila
 * desde el login: un token vale solo si su sesión está activa. Cerrar
 * sesión, revocar un dispositivo o dar de baja a un usuario es marcar sus
 * sesiones como inactivas. También es la bitácora (última conexión, IP,
 * cuándo y por qué se cerró). Reemplaza a base_movil_token_revocado.
 */
class base_movil_sesion extends modelo
{
    public function __construct(PDO $link)
    {
        $tabla = 'base_movil_sesion';
        $columnas = array($tabla => false, 'adm_usuario' => $tabla);
        parent::__construct(link: $link, tabla: $tabla, columnas: $columnas);
    }
}
