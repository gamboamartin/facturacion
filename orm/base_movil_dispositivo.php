<?php
namespace gamboamartin\facturacion\models;

use base\orm\errores;
use base\orm\modelo;
use gamboamartin\errores\errores as GamboamartinErrores;
use PDO;

class base_movil_dispositivo extends modelo
{
    public function __construct(PDO $link)
    {
        $tabla = 'base_movil_dispositivo';
        $columnas = array($tabla => false);
        parent::__construct(link: $link, tabla: $tabla, columnas: $columnas);
    }
    
    /**
 * Actualiza la bitácora de conexión física del hardware (sin requerir sesión de usuario previa).
 */
public function actualiza_handshake(int $id, string $ip_tunel = ''): array|\stdClass
{
    if ($id <= 0) {
        return $this->error->error(mensaje: 'Error id de dispositivo inválido', data: $id);
    }

    $registro = array(
        'ultimo_acceso' => date('Y-m-d H:i:s')
    );
    if ($ip_tunel !== '') {
        $registro['ip_tunel'] = $ip_tunel;
    }

    // Llama directamente al método base de SQL sin pasar por las validaciones de sesión
    $resultado = $this->modifica_bd_base(registro: $registro, id: $id);
    if (GamboamartinErrores::$error) {
        return $this->error->error(mensaje: 'Error al actualizar handshake de hardware', data: $resultado);
    }

    return $resultado;
}
}