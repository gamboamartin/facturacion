<?php
namespace gamboamartin\facturacion\models;
use config\generales;
use DateTime;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\controllers\controlador_com_contacto;
use gamboamartin\facturacion\controllers\controlador_fc_empleado_contacto;
use gamboamartin\validacion\validacion;
use Override;
use stdClass;

class com_contacto extends \gamboamartin\comercial\models\com_contacto {

    public function genera_link_validacion(array $new_data, int $registro_id)
    {
        $token = $this->get_codigo_aleatorio(longitud: 16);
        $fecha_token_validacion = date('Y-m-d H:i:s');
        $url_validacion = (new generales())->url_base;
        $url_validacion .= "valida_correo_cliente.php?correo={$new_data['correo']}&token={$token}";


        $new_data['token_validacion'] = $token;
        $new_data['fecha_token_validacion'] = $fecha_token_validacion;

        $rs = $this->modifica_bd(
            registro: $new_data,
            id: $registro_id
        );
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al actualizar el token de validacion', data: $rs);
        }

        return $url_validacion;
    }

    public function actualiza_estado_correo(int $registro_id, string $estado, bool $borrar_token = false)
    {
        $this->registro_id = $registro_id;
        $data = $this->obten_data();
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al obtener la data del contacto', data: $data);
        }

        $new_data = [
            'correo' => $data['com_contacto_correo'],
            'telefono' => $data['com_contacto_telefono'],
            'nombre' => $data['com_contacto_nombre'],
            'ap' => $data['com_contacto_ap'],
            'am' => $data['com_contacto_am'],
        ];
        $new_data['estatus_correo'] = $estado;
        if ($borrar_token) {
            $new_data['token_validacion'] = '';
        }

        $rs = $this->modifica_bd(
            registro: $new_data,
            id: $registro_id
        );
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al actualizar el estatus_correo', data: $rs);
        }

        return [];
    }

    public function valida_tiempo_tokens(): void
    {
        $filtro = [
            'com_contacto.estatus_correo' => controlador_com_contacto::STATUS_LINK_ENVIADO,
        ];

        $columnas = [
            'com_contacto_id',
            'com_contacto_fecha_token_validacion',
        ];

        $response = $this->filtro_and(columnas: $columnas, filtro: $filtro);
        if (errores::$error) {
            exit;
        }

        foreach ($response->registros as $registro) {
            $fecha_token = $registro['com_contacto_fecha_token_validacion'];
            $registro_id = $registro['com_contacto_id'];

            $fecha_actual = new DateTime();

            try {
                $fecha_token_dt = new DateTime($fecha_token);
                $diferencia = $fecha_actual->diff($fecha_token_dt);
                if ($diferencia->days > 2 ) {
                    $rs = $this->actualiza_estado_correo(
                        registro_id:$registro_id,
                        estado: controlador_com_contacto::STATUS_NO_VALIDADO,
                        borrar_token: true
                    );
                    if (errores::$error) {
                        exit;
                    }
                }
            } catch (\DateMalformedStringException $e) {

            }
        }
    }

    public function genera_link_validacion_telefono(string $telefono, int $registro_id, 
    array $data)
    {
        $token = $this->get_codigo_aleatorio(longitud: 16);
        $fecha_token_validacion = date('Y-m-d H:i:s');
        $url_validacion = (new generales())->url_base;
        $url_validacion .= "valida_telefono_contacto.php?telefono={$telefono}&token={$token}";
        

        $rs = $this->actualiza_info_token_telefono(
            registro_id: $registro_id,
            token: $token,
            fecha_token: $fecha_token_validacion
        );
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al actualizar el token del  telefono', data: $rs);
        }
        if (errores::$error) {
            return $this->error->error(mensaje: 'Error al actualizar el token del  telefono', data: $rs);
        }

        return $url_validacion;
    }

    public function valida_telefono(int $registro_id): array
    {
        $this->registro_id = $registro_id;

        $rs = $this->obten_data();
        if (errores::$error) {
            return $this->error->error(
                mensaje: 'Error de obtener data',
                data: $rs
            );
        }

        if ($rs['com_contacto_estatus_telefono'] === controlador_com_contacto::STATUS_VALIDADO) {
            return $this->error->error(
                mensaje: 'El telefono ya fue validado',
                data: []
            );
        }

        $telefono = $rs['com_contacto_telefono'] ?? '';
        $codigo_pais = $rs['com_contacto_codigo_pais'] ?? '52';

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
            data: $rs
        );

        if (errores::$error) {
            return $this->error->error(
                mensaje: 'Error de obtener url',
                data: $url
            );
        }

            return [
            'com_contacto_id' => $registro_id,
            'com_cliente_id' => $rs['com_contacto_com_cliente_id'] ?? $rs['com_cliente_id'] ?? 0,
            'telefono' => $telefono_whatsapp,
            'telefono_original' => $telefono,
            'codigo_pais' => $codigo_pais,
            'nombre' => $rs['com_contacto_descripcion'] ?? '',
            'url_validacion' => $url,
            'estatus_telefono' => $rs['com_contacto_estatus_telefono'] ?? '',
        ];
    }

  private function actualiza_info_token_telefono(int $registro_id, string $token, string $fecha_token)
    {

        $consulta = "UPDATE com_contacto SET ";
        $consulta .= " com_contacto.token_telefono = '{$token}' ,";
        $consulta .= " com_contacto.fecha_token_telefono = '{$fecha_token}'";
        $consulta .= " WHERE com_contacto.id = {$registro_id}";
        $rs = $this->ejecuta_sql($consulta);
        if(errores::$error){
            return (new errores())->error('Error al modificar com_contacto', $rs);
        }

        return $rs;

    }

    public function valida_token_telefono(int $com_contacto_id)
    {
        $rs = $this->actualiza_telefono_validado(registro_id: $com_contacto_id);
        if (errores::$error) {
            return $this->error->error(
                mensaje: 'Error al validar telefono del contacto',
                data: $rs
            );
        }

        return $rs;
    }

    private function actualiza_telefono_validado(int $registro_id)
    {
        $status_validado = controlador_com_contacto::STATUS_VALIDADO;

        $consulta = "UPDATE com_contacto SET ";
        $consulta .= " com_contacto.estatus_telefono = '{$status_validado}', ";
        $consulta .= " com_contacto.token_telefono = '', ";
        $consulta .= " com_contacto.fecha_token_telefono = NULL ";
        $consulta .= " WHERE com_contacto.id = {$registro_id}";

        $rs = $this->ejecuta_sql($consulta);
        if (errores::$error) {
            return (new errores())->error(
                mensaje: 'Error al modificar validacion de telefono en com_contacto',
                data: $rs
            );
        }

        return $rs;
    }

    public function telefono_whatsapp(string $telefono, string $codigo_pais): string|array
    {
        $telefono = preg_replace('/\D+/', '', $telefono);
        $codigo_pais = preg_replace('/\D+/', '', $codigo_pais);

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

        return $codigo_pais . $telefono;
    }
    
    public function modifica_telefono(int $com_contacto_id, string $telefono, string $codigo_pais): array|stdClass
    {
        $telefono = preg_replace('/\D+/', '', $telefono);
        $codigo_pais = preg_replace('/\D+/', '', $codigo_pais);

        $consulta = "UPDATE com_contacto SET ";
        $consulta .= " com_contacto.telefono = '{$telefono}' ,";
        $consulta .= " com_contacto.codigo_pais = '{$codigo_pais}' ,";
        $consulta .= " com_contacto.estatus_telefono = 'no validado' ,";
        $consulta .= " com_contacto.fecha_token_telefono = NULL ,";
        $consulta .= " com_contacto.token_telefono = NULL";
        $consulta .= " WHERE com_contacto.id = {$com_contacto_id}";

        $rs = $this->ejecuta_sql($consulta);
        if (errores::$error) {
            return (new errores())->error('Error al modificar telefono', $rs);
        }

        return $rs;
    }

}