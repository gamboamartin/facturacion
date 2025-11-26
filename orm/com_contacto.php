<?php
namespace gamboamartin\facturacion\models;
use config\generales;
use DateTime;
use gamboamartin\errores\errores;
use gamboamartin\facturacion\controllers\controlador_com_contacto;
use gamboamartin\facturacion\controllers\controlador_fc_empleado_contacto;
use gamboamartin\validacion\validacion;
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

}