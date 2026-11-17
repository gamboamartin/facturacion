<?php

namespace base_movil\v1\src;

use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\base_movil_dispositivo;
use PDO;

class app_dispositivo
{
    private errores $errores;
    private PDO $link;
    private app_screens $screens;

    public function __construct(PDO $link)
    {
        $this->errores = new errores();
        $this->link = $link;
        $this->screens = new app_screens($link);
    }

  public function valida_dispositivo(): array
    {
        $raw_input = file_get_contents('php://input');
        $body = json_decode((string)$raw_input, true);
        if (!is_array($body)) {
            $body = array();
        }

       // 1. Extracción limpia de UUID
        $uuid = $this->obten_parametro_payload($body, 'uuid');
        if ($uuid === '') {
            $err = $this->errores->error(mensaje: 'Error: el payload debe contener un "uuid" valido', data: $body);
            $err['http_code'] = 400;
            return $err;
        }

        // 2. Extracción de IP (prioriza el túnel recibido; fallback a la IP de conexión TCP)
        $ip_tunel = $this->obten_parametro_payload($body, 'ip_tunel');
        if ($ip_tunel === '' && isset($_SERVER['REMOTE_ADDR'])) {
            $ip_tunel = trim((string)$_SERVER['REMOTE_ADDR']);
        }

        // 3. Instancia del modelo base_movil_dispositivo
        $modelo = new base_movil_dispositivo(link: $this->link);
        if (errores::$error) {
            $err = $this->errores->error(mensaje: 'Error al inicializar base_movil_dispositivo', data: $this->link);
            $err['http_code'] = 500;
            return $err;
        }

        // 4. Consulta de existencia por UUID
        $filtro = array('base_movil_dispositivo.uuid' => $uuid);
        $r_busqueda = $modelo->filtro_and(filtro: $filtro);
        if (errores::$error || !is_object($r_busqueda) || !isset($r_busqueda->n_registros)) {
            $err = $this->errores->error(mensaje: 'Error al consultar dispositivo en base de datos', data: $r_busqueda);
            $err['http_code'] = 500;
            return $err;
        }

        // Identifica si la llamada proviene del Form Submit (botón) o del arranque inicial (AppNavigator)
        $es_form_submit = isset($body['values']);

        // 5. Caso: Hardware NO registrado en la web central
        if ((int)$r_busqueda->n_registros === 0) {
            if ($es_form_submit) {
                return array(
                    'errors' => array(
                        'uuid' => 'Dispositivo no registrado. Entrega este UUID a tu supervisor para darlo de alta.'
                    )
                );
            }

            $pantalla_bloqueo = $this->screens->obten_pantalla_dispositivo_bloqueado(
                uuid: $uuid,
                ip_tunel: $ip_tunel,
                mensaje: 'Este equipo no está registrado. Proporciona el UUID a tu supervisor para darlo de alta en el sistema central.'
            );
            if (isset($pantalla_bloqueo['error'])) {
                return $pantalla_bloqueo;
            }

            return array(
                'autorizado' => false,
                'pantalla'   => $pantalla_bloqueo
            );
        }

        $dispositivo = (array)$r_busqueda->registros[0];
        $estado_raw = strtolower(trim((string)($dispositivo['base_movil_dispositivo_estado_dispositivo'] ?? $dispositivo['estado_dispositivo'] ?? '')));

        // Mapeo defensivo: homologa mayúsculas, minúsculas, inglés y español al estándar en minúsculas
        $mapa_estados = array(
            'activo'     => 'activo',
            'active'     => 'activo',
            'pendiente'  => 'pendiente',
            'pending'    => 'pendiente',
            'revocado'   => 'revocado',
            'revoked'    => 'revocado',
            'bloqueado'  => 'revocado',
        );

        $estado = $mapa_estados[$estado_raw] ?? $estado_raw;

        // 6. Caso: Hardware PENDIENTE o REVOCADO
        if ($estado === 'revocado' || $estado === 'pendiente') {
            $mensaje = ($estado === 'revocado')
                ? 'El acceso de este dispositivo ha sido revocado.'
                : 'Pendiente de autorización. Tu supervisor debe activarlo en la web central.';

            if ($es_form_submit) {
                return array(
                    'errors' => array(
                        'uuid' => $mensaje
                    )
                );
            }

            $ip_mostrar = $ip_tunel !== '' 
                ? $ip_tunel 
                : (string)($dispositivo['base_movil_dispositivo_ip_tunel'] ?? $dispositivo['ip_tunel'] ?? '');

            $pantalla_bloqueo = $this->screens->obten_pantalla_dispositivo_bloqueado(
                uuid: $uuid,
                ip_tunel: $ip_mostrar,
                mensaje: $mensaje
            );
            if (isset($pantalla_bloqueo['error'])) {
                return $pantalla_bloqueo;
            }

            return array(
                'autorizado' => false,
                'pantalla'   => $pantalla_bloqueo
            );
        }

        // 7. Caso: Hardware ACTIVO -> Transicionar a pantalla login
        if ($estado === 'activo') {
            if ($es_form_submit) {
                return array(
                    'action' => array(
                        'type'     => 'NAVIGATE',
                        'screenId' => 'login',
                        'reset'    => true
                    )
                );
            }

            $_GET['screenId'] = 'login';
            $pantalla_login = $this->screens->obten_pantalla();
            if (isset($pantalla_login['error'])) {
                return $pantalla_login;
            }

            return array(
                'autorizado' => true,
                'pantalla'   => $pantalla_login
            );
        }

        $err = $this->errores->error(mensaje: 'Error: estado de hardware indeterminado', data: $dispositivo);
        $err['http_code'] = 400;
        return $err;
    }

    /**
     * Resuelve un parámetro del payload admitiendo el formato de formulario o raíz.
     */
    private function obten_parametro_payload(array $body, string $clave): string
    {
        $valor = $body['values'][$clave] ?? $body[$clave] ?? '';
        return is_string($valor) ? trim($valor) : '';
    }
}