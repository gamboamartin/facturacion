<?php

namespace base_movil\v1\src;

use gamboamartin\errores\errores;
use gamboamartin\facturacion\models\base_movil_dispositivo;
use config\generales; // O el namespace correspondiente a la clase generales de tu proyecto
use PDO;

class app_dispositivo
{
    private errores $errores;
    private PDO $link;
    private app_screens $screens;
    private generales $generales;


    public function __construct(PDO $link)
    {
        $this->errores = new errores();
        $this->link = $link;
        $this->screens = new app_screens($link);
        $this->generales = new generales();
    }

  /**
     * Valida si una dirección IP pertenece a un bloque CIDR en IPv4.
     */
    private function ip_en_rango_cidr(string $ip, string $cidr): bool
    {
        $ip = trim($ip);
        $cidr = trim($cidr);

        // Si pasan una IP individual sin máscara (ej. '127.0.0.1' o '192.168.31.29')
        if (!str_contains($cidr, '/')) {
            return $ip === $cidr;
        }

        $partes = explode('/', $cidr);
        if (count($partes) !== 2) {
            return false;
        }

        $subnet = trim($partes[0]);
        $mascara = (int)$partes[1];

        if ($mascara < 0 || $mascara > 32) {
            return false;
        }

        if ($mascara === 0) {
            return true;
        }

        $ip_long = ip2long($ip);
        $subnet_long = ip2long($subnet);

        if ($ip_long === false || $subnet_long === false) {
            return false;
        }

        // Cálculo seguro de máscara en 32 bits sin desbordamiento de bits en PHP 64-bit
        $netmask = -1 << (32 - $mascara);
        $subnet_masked = $subnet_long & $netmask;
        $ip_masked = $ip_long & $netmask;

        if ($ip_masked === $subnet_masked) {
            return true;
        }

        return false;
    }

    /**
     * Obtiene los rangos CIDR permitidos definidos en la configuración general del servidor.
     * Si no están configurados, devuelve array vacío para forzar denegación por defecto (fail-closed).
     *
     * @return array<int, string>
     */
    private function rangos_vpn_permitidos(): array
    {
        if (!property_exists($this->generales, 'wireguard_rangos_permitidos')) {
            return array();
        }

        $rangos = $this->generales->wireguard_rangos_permitidos;
        if (!is_array($rangos)) {
            return array();
        }

        return $rangos;
    }

    public function valida_dispositivo(): array
    {
        $raw_input = file_get_contents('php://input');
        $body = json_decode((string)$raw_input, true);
        if (!is_array($body)) {
            $body = array();
        }

        // Identifica si la llamada proviene del Form Submit (botón) o del arranque inicial (AppNavigator)
        $es_form_submit = isset($body['values']);

        // 1. Extracción limpia de UUID
        $uuid = $this->obten_parametro_payload($body, 'uuid');
        if ($uuid === '') {
            $err = $this->errores->error(mensaje: 'Error: el payload debe contener un "uuid" valido', data: $body);
            $err['http_code'] = 400;
            return $err;
        }

        // 2. IP real de transporte (REMOTE_ADDR es la única fuente confiable contra spoofing)
        $ip_remota = isset($_SERVER['REMOTE_ADDR']) ? trim((string)$_SERVER['REMOTE_ADDR']) : '';
        $ip_payload = $this->obten_parametro_payload($body, 'ip_tunel');
        $ip_mostrar = $ip_payload !== '' ? $ip_payload : $ip_remota;

        // 3. Validación de red privada WireGuard
        $rangos_permitidos = $this->rangos_vpn_permitidos();
        $en_red_vpn = false;

        foreach ($rangos_permitidos as $rango) {
            if (!is_string($rango)) {
                continue;
            }
            if ($this->ip_en_rango_cidr(ip: $ip_remota, cidr: $rango)) {
                $en_red_vpn = true;
                break;
            }
        }

        if (!$en_red_vpn) {
            $msj_vpn = 'Conexión no segura. Debes activar la VPN corporativa para ingresar.';
            if ($es_form_submit) {
                return array(
                    'autorizado' => false,
                    'motivo'     => 'red',
                    'errors'     => array(
                        'uuid' => $msj_vpn
                    )
                );
            }

            $pantalla_bloqueo = $this->screens->obten_pantalla_dispositivo_bloqueado(
                uuid: $uuid,
                ip_tunel: $ip_mostrar,
                mensaje: $msj_vpn
            );
            if (isset($pantalla_bloqueo['error'])) {
                return $pantalla_bloqueo;
            }

            return array(
                'autorizado' => false,
                'motivo'     => 'red',
                'pantalla'   => $pantalla_bloqueo
            );
        }

        // 4. Instancia del modelo base_movil_dispositivo
        $modelo = new base_movil_dispositivo(link: $this->link);
        if (errores::$error) {
            $err = $this->errores->error(mensaje: 'Error al inicializar base_movil_dispositivo', data: $this->link);
            $err['http_code'] = 500;
            return $err;
        }

        // 5. Consulta de existencia por UUID
        $filtro = array('base_movil_dispositivo.uuid' => $uuid);
        $r_busqueda = $modelo->filtro_and(filtro: $filtro);
        if (errores::$error || !is_object($r_busqueda) || !isset($r_busqueda->n_registros)) {
            $err = $this->errores->error(mensaje: 'Error al consultar dispositivo en base de datos', data: $r_busqueda);
            $err['http_code'] = 500;
            return $err;
        }

        // 6. Caso: Hardware NO registrado en la web central
        if ((int)$r_busqueda->n_registros === 0) {
            $msj_no_reg = 'Dispositivo no registrado. Entrega este UUID a tu supervisor para darlo de alta.';
            if ($es_form_submit) {
                return array(
                    'autorizado' => false,
                    'motivo'     => 'no_registrado',
                    'errors'     => array(
                        'uuid' => $msj_no_reg
                    )
                );
            }

            $pantalla_bloqueo = $this->screens->obten_pantalla_dispositivo_bloqueado(
                uuid: $uuid,
                ip_tunel: $ip_mostrar,
                mensaje: $msj_no_reg
            );
            if (isset($pantalla_bloqueo['error'])) {
                return $pantalla_bloqueo;
            }

            return array(
                'autorizado' => false,
                'motivo'     => 'no_registrado',
                'pantalla'   => $pantalla_bloqueo
            );
        }

        $dispositivo = (array)$r_busqueda->registros[0];
        $estado_raw = strtolower(trim((string)($dispositivo['base_movil_dispositivo_estado_dispositivo'] ?? $dispositivo['estado_dispositivo'] ?? '')));

        // Mapeo defensivo de estados
        $mapa_estados = array(
            'activo'    => 'activo',
            'active'    => 'activo',
            'pendiente' => 'pendiente',
            'pending'   => 'pendiente',
            'inactivo'  => 'inactivo',
            'inactive'  => 'inactivo',
            'revocado'  => 'revocado',
            'revoked'   => 'revocado',
            'bloqueado' => 'revocado',
        );

        $estado = $mapa_estados[$estado_raw] ?? $estado_raw;

        // 7. Caso: Hardware PENDIENTE, INACTIVO o REVOCADO
        if ($estado === 'revocado' || $estado === 'pendiente' || $estado === 'inactivo') {
            $mensaje = 'Dispositivo suspendido o inactivo. Contacta a tu supervisor.';
            if ($estado === 'revocado') {
                $mensaje = 'El acceso de este dispositivo ha sido revocado.';
            }
            if ($estado === 'pendiente') {
                $mensaje = 'Pendiente de autorización. Tu supervisor debe activarlo en la web central.';
            }

            if ($es_form_submit) {
                return array(
                    'autorizado' => false,
                    'motivo'     => 'inactivo',
                    'errors'     => array(
                        'uuid' => $mensaje
                    )
                );
            }

            $ip_bd = (string)($dispositivo['base_movil_dispositivo_ip_tunel'] ?? $dispositivo['ip_tunel'] ?? '');
            $ip_render = $ip_mostrar !== '' ? $ip_mostrar : $ip_bd;

            $pantalla_bloqueo = $this->screens->obten_pantalla_dispositivo_bloqueado(
                uuid: $uuid,
                ip_tunel: $ip_render,
                mensaje: $mensaje
            );
            if (isset($pantalla_bloqueo['error'])) {
                return $pantalla_bloqueo;
            }

            return array(
                'autorizado' => false,
                'motivo'     => 'inactivo',
                'pantalla'   => $pantalla_bloqueo
            );
        }

        // 8. Caso: Hardware ACTIVO -> Transicionar a pantalla login
        if ($estado === 'activo') {
            if ($es_form_submit) {
                return array(
                    'autorizado' => true,
                    'action'     => array(
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