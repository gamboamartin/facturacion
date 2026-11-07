<?php
namespace gamboamartin\facturacion\models;

use base\orm\modelo;
use PDO;

/**
 * Tokens de notificaciones push de la app (API base_movil, #7 parte 3).
 * Un token por dispositivo, ligado al usuario y a la sesión con la que se
 * registró. Cerrar la sesión lo deja inactivo.
 *
 * Relaciones: adm_usuario y base_movil_sesion, así filtro_and puede filtrar
 * por el estado y la vigencia de la sesión (base_movil_sesion.status,
 * base_movil_sesion.expira) en la misma consulta.
 */
class base_movil_push_token extends modelo
{
    public function __construct(PDO $link)
    {
        $tabla = 'base_movil_push_token';
        $columnas = array($tabla => false, 'adm_usuario' => $tabla, 'base_movil_sesion' => $tabla);
        parent::__construct(link: $link, tabla: $tabla, columnas: $columnas);
    }
}
