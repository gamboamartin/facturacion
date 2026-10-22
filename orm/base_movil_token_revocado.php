<?php
namespace gamboamartin\facturacion\models;

use base\orm\modelo;
use PDO;

/**
 * Tokens JWT cerrados con logout (API base_movil). Un token cuyo jti está
 * aquí ya no es válido aunque no haya expirado.
 */
class base_movil_token_revocado extends modelo
{
    public function __construct(PDO $link)
    {
        $tabla = 'base_movil_token_revocado';
        $columnas = array($tabla => false, 'adm_usuario' => $tabla);
        parent::__construct(link: $link, tabla: $tabla, columnas: $columnas);
    }
}