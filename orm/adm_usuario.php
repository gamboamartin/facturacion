<?php
namespace gamboamartin\facturacion\models;

use gamboamartin\errores\errores;
use stdClass;

class adm_usuario extends \gamboamartin\administrador\models\adm_usuario
{
   
    public function valida_telefono(int $registro_id): array
    {
        $this->registro_id = $registro_id;

        $filtro = [
            'adm_usuario.id' => $registro_id,
            'adm_usuario.status' => 'activo',
            'adm_grupo.status' => 'activo',
        ];

        $columnas = [
            'adm_usuario_id',
            'adm_usuario_user',
            'adm_usuario_email',
            'adm_usuario_telefono',
            'adm_usuario_codigo_pais',
            'adm_usuario_estatus_telefono',
            'adm_usuario_nombre',
            'adm_usuario_ap',
            'adm_usuario_am',
            'adm_usuario_adm_grupo_id',
            'adm_grupo_id',
            'adm_grupo_descripcion',
            'adm_grupo_codigo',
            'adm_grupo_codigo_bis',
            'adm_grupo_alias',
            'adm_grupo_root',
            'adm_grupo_status',
        ];

        $rs = $this->filtro_and(
            columnas: $columnas,
            filtro: $filtro
        );

        if (errores::$error) {
            return $this->error->error(
                mensaje: 'Error al obtener usuario administrador',
                data: $rs
            );
        }

        if ((int)$rs->n_registros === 0) {
            return $this->error->error(
                mensaje: 'El usuario no existe o no está activo en el sistema',
                data: $registro_id
            );
        }

        $usuario = $rs->registros[0];

        if (($usuario['adm_usuario_estatus_telefono'] ?? '') === 'validado') {
            return $this->error->error(
                mensaje: 'El telefono ya fue validado',
                data: []
            );
        }

        $telefono = $usuario['adm_usuario_telefono'] ?? '';
        $codigo_pais = $usuario['adm_usuario_codigo_pais'] ?? '52';

        if ($telefono === '' || $telefono === '1000000000') {
            return $this->error->error(
                mensaje: 'Error telefono no valido',
                data: $telefono
            );
        }

        if ($codigo_pais === '') {
            $codigo_pais = '52';
        }

        $telefono_whatsapp = $this->telefono_whatsapp(
            telefono: $telefono,
            codigo_pais: $codigo_pais
        );

        if (errores::$error) {
            return $this->error->error(
                mensaje: 'Error al normalizar telefono para WhatsApp',
                data: $telefono_whatsapp
            );
        }

        $url = $this->genera_link_validacion_telefono(
            telefono: $telefono,
            registro_id: $registro_id,
            data: $usuario
        );

        if (errores::$error) {
            return $this->error->error(
                mensaje: 'Error al obtener url',
                data: $url
            );
        }

        $nombre = trim(
            ($usuario['adm_usuario_nombre'] ?? '') . ' ' .
            ($usuario['adm_usuario_ap'] ?? '') . ' ' .
            ($usuario['adm_usuario_am'] ?? '')
        );

        if ($nombre === '') {
            $nombre = $usuario['adm_usuario_user'] ?? '';
        }

        return [
            'adm_usuario_id' => $registro_id,
            'nombre' => $nombre,
            'url_validacion' => $url,
            'codigo_pais' => $codigo_pais,
            'telefono' => $telefono_whatsapp,
            'telefono_original' => $telefono,
            'estatus_telefono' => $usuario['adm_usuario_estatus_telefono'] ?? '',
            'adm_grupo_id' => (int)($usuario['adm_usuario_adm_grupo_id'] ?? 0),
            'grupo_descripcion' => $usuario['adm_grupo_descripcion'] ?? '',
            'grupo_codigo' => $usuario['adm_grupo_codigo'] ?? '',
            'grupo_codigo_bis' => $usuario['adm_grupo_codigo_bis'] ?? '',
            'grupo_alias' => $usuario['adm_grupo_alias'] ?? '',
            'grupo_root' => $usuario['adm_grupo_root'] ?? '',
        ];
    }

    public function modifica_telefono(int $adm_usuario_id, string $telefono, string $codigo_pais): array|stdClass
    {
        $telefono = $this->limpia_telefono(telefono: $telefono);
        $codigo_pais = $this->limpia_telefono(telefono: $codigo_pais);

        if ($adm_usuario_id <= 0) {
            return $this->error->error(
                mensaje: 'Error adm_usuario_id es requerido',
                data: $adm_usuario_id
            );
        }

        if ($telefono === '') {
            return $this->error->error(
                mensaje: 'Error telefono esta vacio',
                data: $telefono
            );
        }

        if ($codigo_pais === '') {
            return $this->error->error(
                mensaje: 'Error codigo_pais esta vacio',
                data: $codigo_pais
            );
        }

        $telefono_nacional = $this->telefono_nacional(telefono: $telefono);

        $consulta = "UPDATE adm_usuario SET ";
        $consulta .= " telefono = '{$telefono_nacional}', ";
        $consulta .= " codigo_pais = '{$codigo_pais}', ";
        $consulta .= " estatus_telefono = 'no validado', ";
        $consulta .= " fecha_token_telefono = NULL, ";
        $consulta .= " token_telefono = NULL ";
        $consulta .= " WHERE id = {$adm_usuario_id}";

        $rs = $this->ejecuta_sql($consulta);
        if (errores::$error) {
            return $this->error->error(
                mensaje: 'Error al modificar telefono de usuario',
                data: $rs
            );
        }

        return $rs;
    }

    public function telefono_whatsapp(string $telefono, string $codigo_pais): string|array
    {
        $telefono = $this->limpia_telefono(telefono: $telefono);
        $codigo_pais = $this->limpia_telefono(telefono: $codigo_pais);

        if ($telefono === '') {
            return $this->error->error(
                mensaje: 'Error telefono esta vacio',
                data: $telefono
            );
        }

        if ($codigo_pais === '') {
            return $this->error->error(
                mensaje: 'Error codigo_pais esta vacio',
                data: $codigo_pais
            );
        }

        $telefono_nacional = $this->telefono_nacional(telefono: $telefono);

        return $codigo_pais . $telefono_nacional;
    }

  
    private function limpia_telefono(string $telefono): string
    {
        return preg_replace('/\D+/', '', $telefono);
    }

    private function telefono_nacional(string $telefono): string
    {
        if (strlen($telefono) > 10) {
            return substr($telefono, -10);
        }

        return $telefono;
    }

    public function genera_link_validacion_telefono(string $telefono, int $registro_id, array $data)
    {
        $token = $this->get_codigo_aleatorio(longitud: 16);
        $fecha_token_validacion = date('Y-m-d H:i:s');

        $url_validacion = (new \config\generales())->url_base;
        $url_validacion .= "api/valida_telefono_adm_usuario.php?telefono={$telefono}&token={$token}";

        $rs = $this->actualiza_info_token_telefono(
            registro_id: $registro_id,
            token: $token,
            fecha_token: $fecha_token_validacion
        );

        if (errores::$error) {
            return $this->error->error(
                mensaje: 'Error al actualizar token de telefono en adm_usuario',
                data: $rs
            );
        }

        return $url_validacion;
    }


    private function actualiza_info_token_telefono(int $registro_id, string $token, string $fecha_token)
    {
        $consulta = "UPDATE adm_usuario SET ";
        $consulta .= " adm_usuario.token_telefono = '{$token}', ";
        $consulta .= " adm_usuario.fecha_token_telefono = '{$fecha_token}' ";
        $consulta .= " WHERE adm_usuario.id = {$registro_id}";

        $rs = $this->ejecuta_sql($consulta);
        if (errores::$error) {
            return $this->error->error(
                mensaje: 'Error al modificar token de telefono en adm_usuario',
                data: $rs
            );
        }

        return $rs;
    }


    public function valida_token_telefono(int $adm_usuario_id)
    {
        $rs = $this->actualiza_telefono_validado(registro_id: $adm_usuario_id);
        if (errores::$error) {
            return $this->error->error(
                mensaje: 'Error al validar telefono del usuario',
                data: $rs
            );
        }

        return $rs;
    }


    private function actualiza_telefono_validado(int $registro_id)
    {
        $consulta = "UPDATE adm_usuario SET ";
        $consulta .= " adm_usuario.estatus_telefono = 'validado', ";
        $consulta .= " adm_usuario.token_telefono = '', ";
        $consulta .= " adm_usuario.fecha_token_telefono = NULL ";
        $consulta .= " WHERE adm_usuario.id = {$registro_id}";

        $rs = $this->ejecuta_sql($consulta);
        if (errores::$error) {
            return $this->error->error(
                mensaje: 'Error al modificar validacion de telefono en adm_usuario',
                data: $rs
            );
        }

        return $rs;
    }
}