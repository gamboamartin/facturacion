<?php
namespace base_movil\v1\proyectos\aguaban;

use base_movil\v1\src\proyecto;

/**
 * Proyecto `aguaban`. Se activa en el servidor de Aguaban con
 * base_movil_proyecto = 'aguaban' en config/generales.php.
 *
 * Flujo de entrada:
 * 1. dispositivo_bloqueado: Validación de hardware (UUID / WireGuard) previa al login.
 * 2. login: Formulario con el diseño de la propuesta (logo, tarjeta, estilos de marca).
 * 3. home: Inicio provisional con telemetría.
 */
class pantallas extends proyecto
{
    public function pantallas(): array
    {
        return array(
            'dispositivo_bloqueado' => 'pantalla_dispositivo_bloqueado',
            'login' => 'pantalla_login',
            'home' => 'pantalla_home',
        );
    }

    public function pantallas_offline(): array
    {
        return array('home');
    }

    /**
     * Pantallas accesibles sin token JWT / sesión activa en el sistema.
     * Permite la aduana de hardware previa y el formulario de login.
     */
    public function pantallas_publicas(): array
    {
        return array('dispositivo_bloqueado', 'login');
    }

    /**
     * Retorna la UI Server-Driven para dispositivos no autorizados o en espera.
     * Muestra el UUID generado y la IP asignada del túnel WireGuard.
     */
    public function construye_dispositivo_bloqueado(string $uuid = '', string $ip_tunel = '', string $mensaje = ''): array
    {
        return $this->pantalla_dispositivo_bloqueado($uuid, $ip_tunel, $mensaje);
    }

    public function construye_login(string $usuario_asignado = ''): array
    {
        return $this->pantalla_login($usuario_asignado);
    }

    public function construye_home(string $usuario_activo = ''): array
    {
        return $this->pantalla_home($usuario_activo);
    }

    protected function pantalla_dispositivo_bloqueado(string $uuid = '', string $ip_tunel = '', string $mensaje = ''): array
    {
        $alerta = $mensaje !== '' ? $mensaje : 'Este equipo requiere autorización del supervisor en sistema central para operar.';

        return array(
            'screenId' => 'dispositivo_bloqueado',
            'title' => '',
            'ttl' => 0,
            'components' => array(
                array(
                    'type' => 'Image',
                    'id' => 'img-bloqueo-logo',
                    'source' => 'logo',
                    'height' => 64,
                    'width' => 84,
                    'accessibilityLabel' => 'Logotipo de Aguaban',
                    'style' => array('alignSelf' => 'center', 'marginTop' => 20, 'marginBottom' => 16),
                ),
                array(
                    'type' => 'Text',
                    'id' => 'txt-bloqueo-eyebrow',
                    'content' => 'SEGURIDAD DE ACCESO',
                    'style' => array(
                        'fontSize' => 10,
                        'fontWeight' => 'bold',
                        'letterSpacing' => 2,
                        'textAlign' => 'center',
                        'color' => '#1679B8',
                        'dark' => array('color' => '#5AAEC8'),
                    ),
                ),
                array(
                    'type' => 'Text',
                    'id' => 'txt-bloqueo-titulo',
                    'content' => 'Equipo no autorizado',
                    'style' => array(
                        'fontSize' => 24,
                        'fontWeight' => 'bold',
                        'letterSpacing' => -0.8,
                        'textAlign' => 'center',
                        'color' => '#0A3156',
                        'marginTop' => 8,
                        'dark' => array('color' => '#F5F5F5'),
                    ),
                ),
                array(
                    'type' => 'Text',
                    'id' => 'txt-bloqueo-descripcion',
                    'content' => $alerta,
                    'style' => array(
                        'fontSize' => 13,
                        'lineHeight' => 20,
                        'textAlign' => 'center',
                        'color' => '#708391',
                        'marginTop' => 8,
                        'marginBottom' => 20,
                        'dark' => array('color' => '#B3B3B3'),
                    ),
                ),
                array(
                    'type' => 'Container',
                    'id' => 'cnt-bloqueo-tarjeta',
                    'style' => array(
                        'backgroundColor' => '#FFFFFF',
                        'borderWidth' => 1,
                        'borderColor' => '#E0E9EE',
                        'borderRadius' => 16,
                        'padding' => 16,
                        'gap' => 12,
                        'dark' => array('backgroundColor' => '#1E1E1E', 'borderColor' => '#2E3A44'),
                    ),
                    'children' => array(
                        array(
                            'type' => 'DeviceStatus',
                            'id' => 'estado-hardware-bloqueo',
                            'items' => array('network', 'gps'),
                            'layout' => 'list',
                        ),
                        array(
                            'type' => 'Form',
                            'id' => 'form-bloqueo',
                            'style' => array('gap' => 12),
                            'children' => array(
                                array(
                                    'type' => 'Input',
                                    'id' => 'in-bloqueo-uuid',
                                    'name' => 'uuid',
                                    'inputType' => 'text',
                                    'label' => 'UUID del Dispositivo',
                                    'value' => (string)$uuid,
                                    'readOnly' => true,
                                    'disabled' => true,
                                    'editable' => false,
                                    'copyable' => true,
                                    'style' => $this->estilo_campo(),
                                ),
                                array(
                                    'type' => 'Input',
                                    'id' => 'in-bloqueo-ip',
                                    'name' => 'ip_tunel',
                                    'inputType' => 'text',
                                    'label' => 'IP WireGuard',
                                    'value' => (string)$ip_tunel,
                                    'readOnly' => true,
                                    'disabled' => true,
                                    'editable' => false,
                                    'copyable' => true,
                                    'style' => $this->estilo_campo(),
                                ),
                                array(
                                    'type' => 'Button',
                                    'id' => 'btn-reintentar-bloqueo',
                                    'label' => 'Comprobar Autorización',
                                    'style' => $this->estilo_boton(),
                                    'action' => array(
                                        'type' => 'SUBMIT',
                                        'method' => 'valida_dispositivo',
                                        'values' => array(
                                            'uuid' => (string)$uuid,
                                            'ip_tunel' => (string)$ip_tunel,
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                array(
                    'type' => 'Text',
                    'id' => 'txt-bloqueo-pie',
                    'content' => 'PROPORCIONA ESTOS DATOS A TU SUPERVISOR',
                    'style' => array(
                        'fontSize' => 9,
                        'letterSpacing' => 1.1,
                        'textAlign' => 'center',
                        'color' => '#A2B2BB',
                        'marginTop' => 24,
                        'dark' => array('color' => '#6B7A84'),
                    ),
                ),
            ),
        );
    }

    protected function pantalla_login(string $usuario_asignado = ''): array
    {
        $usuario = $usuario_asignado;

        return array(
            'screenId' => 'login',
            'title' => '',
            'ttl' => 0,
            'components' => array(
                // 1. Header de barra horizontal: Logo a la izquierda y ACTIVACIÓN SEGURA a la derecha
                array(
                    'type' => 'Container',
                    'id' => 'cnt-login-header',
                    'style' => array(
                        'flexDirection' => 'row',
                        'justifyContent' => 'space-between',
                        'alignItems' => 'center',
                        'width' => '100%',
                        'marginBottom' => 24,
                    ),
                    'children' => array(
                        array(
                            'type' => 'Image',
                            'id' => 'img-login-logo',
                            'source' => 'logo',
                            'width' => 105,
                            'height' => 56,
                            'accessibilityLabel' => 'Logotipo de Aguaban',
                        ),
                        array(
                            'type' => 'Text',
                            'id' => 'txt-login-header-activacion',
                            'content' => 'ACTIVACIÓN SEGURA',
                            'style' => array(
                                'fontSize' => 11,
                                'fontWeight' => '800',
                                'color' => '#0A3156',
                                'letterSpacing' => 1.2,
                                'dark' => array('color' => '#F4F7FA'),
                            ),
                        ),
                    ),
                ),
                // 2. Título y Descripción
                array(
                    'type' => 'Text',
                    'id' => 'txt-login-titulo',
                    'content' => 'Inicia sesión',
                    'style' => array(
                        'fontSize' => 27,
                        'fontWeight' => '800',
                        'letterSpacing' => -0.8,
                        'color' => '#0A3156',
                        'marginBottom' => 8,
                        'dark' => array('color' => '#F4F7FA'),
                    ),
                ),
                array(
                    'type' => 'Text',
                    'id' => 'txt-login-descripcion',
                    'content' => 'Accede con la cuenta asignada a este dispositivo.',
                    'style' => array(
                        'fontSize' => 14,
                        'lineHeight' => 20,
                        'color' => '#708391',
                        'marginBottom' => 24,
                        'dark' => array('color' => '#A2B2BB'),
                    ),
                ),
                // 3. Formulario de Inicio de Sesión
                array(
                    'type' => 'Form',
                    'id' => 'form-login',
                    'style' => array('gap' => 12),
                    'children' => array(
                        array(
                            'type' => 'Input',
                            'id' => 'in-login-usuario',
                            'name' => 'usuario',
                            'label' => 'Usuario',
                            'inputType' => 'text',
                            'value' => $usuario,
                            'placeholder' => 'Ingresa tu usuario',
                            'required' => true,
                            'style' => array(
                                'borderColor' => '#E0E9EE',
                                'borderRadius' => 12,
                                'minHeight' => 52,
                                'borderWidth' => 1,
                                'padding' => 14,
                                'fontSize' => 14,
                                'color' => '#0A3156',
                                'backgroundColor' => '#FFFFFF',
                                'dark' => array(
                                    'borderColor' => '#334155',
                                    'backgroundColor' => '#1E293B',
                                    'color' => '#F4F7FA',
                                ),
                            ),
                        ),
                        array(
                            'type' => 'Input',
                            'id' => 'in-login-password',
                            'name' => 'password',
                            'label' => 'Contraseña',
                            'inputType' => 'password',
                            'required' => true,
                            'placeholder' => 'Ingresa tu contraseña',
                            'revealable' => true,
                            'style' => array(
                                'borderColor' => '#E0E9EE',
                                'borderRadius' => 12,
                                'minHeight' => 52,
                                'borderWidth' => 1,
                                'padding' => 14,
                                'fontSize' => 14,
                                'color' => '#0A3156',
                                'backgroundColor' => '#FFFFFF',
                                'dark' => array(
                                    'borderColor' => '#334155',
                                    'backgroundColor' => '#1E293B',
                                    'color' => '#F4F7FA',
                                ),
                            ),
                        ),
                        array(
                            'type' => 'Button',
                            'id' => 'btn-login',
                            'label' => 'Iniciar sesión',
                            'style' => array(
                                'backgroundColor' => '#14527F',
                                'color' => '#FFFFFF',
                                'minHeight' => 52,
                                'borderRadius' => 12,
                                'fontWeight' => '800',
                                'fontSize' => 15,
                                'textAlign' => 'center',
                                'justifyContent' => 'center',
                                'alignItems' => 'center',
                                'marginTop' => 8,
                                'dark' => array('backgroundColor' => '#5AAEC8', 'color' => '#0A3156'),
                            ),
                            'action' => array('type' => 'SUBMIT', 'method' => 'login'),
                        ),
                    ),
                ),
            ),
        );
    }

    protected function pantalla_home(string $usuario_activo = ''): array
    {
        $usuario = $this->usuario_autenticado($usuario_activo);

        return array(
            'screenId' => 'home',
            'title' => 'Bienvenida',
            'ttl' => 60,
            'components' => array(
                // 1. Caja de Logotipo Central
                array(
                    'type' => 'Container',
                    'id' => 'cnt-home-logo-central',
                    'style' => array(
                        'backgroundColor' => '#F1F7FA',
                        'borderRadius' => 24,
                        'padding' => 28,
                        'alignItems' => 'center',
                        'justifyContent' => 'center',
                        'alignSelf' => 'center',
                        'marginTop' => 16,
                        'marginBottom' => 24,
                        'dark' => array('backgroundColor' => '#1A2634'),
                    ),
                    'children' => array(
                        array(
                            'type' => 'Image',
                            'id' => 'img-home-logo-central',
                            'source' => 'logo',
                            'width' => 120,
                            'height' => 76,
                            'accessibilityLabel' => 'Logotipo corporativo de Aguaban',
                        ),
                    ),
                ),
                // 2. Subtítulo: TU ACCESO ESTÁ LISTO
                array(
                    'type' => 'Text',
                    'id' => 'txt-home-subtitulo',
                    'content' => 'TU ACCESO ESTÁ LISTO',
                    'style' => array(
                        'fontSize' => 12,
                        'fontWeight' => '800',
                        'letterSpacing' => 1.5,
                        'textAlign' => 'center',
                        'color' => '#1679B8',
                        'marginBottom' => 8,
                        'dark' => array('color' => '#5AAEC8'),
                    ),
                ),
                // 3. Título: ¡Bienvenidos!
                array(
                    'type' => 'Text',
                    'id' => 'txt-home-titulo',
                    'content' => '¡Bienvenidos!',
                    'style' => array(
                        'fontSize' => 32,
                        'fontWeight' => '800',
                        'textAlign' => 'center',
                        'color' => '#0A3156',
                        'marginBottom' => 12,
                        'dark' => array('color' => '#F4F7FA'),
                    ),
                ),
                // 4. Descripción
                array(
                    'type' => 'Text',
                    'id' => 'txt-home-descripcion',
                    'content' => "Nos alegra tenerte aquí.\nTu espacio de trabajo en AGUABAN está listo para comenzar.",
                    'style' => array(
                        'fontSize' => 15,
                        'lineHeight' => 22,
                        'color' => '#708391',
                        'textAlign' => 'center',
                        'marginBottom' => 32,
                        'dark' => array('color' => '#A2B2BB'),
                    ),
                ),
                // 5. Tarjeta de Sesión Activa (card-sesion-usuario)
                array(
                    'type' => 'Container',
                    'id' => 'card-sesion-usuario',
                    'style' => array(
                        'backgroundColor' => '#F1F7FA',
                        'borderRadius' => 14,
                        'padding' => 14,
                        'marginTop' => 24,
                        'dark' => array('backgroundColor' => '#1A2634'),
                    ),
                    'children' => array(
                        array(
                            'type' => 'Text',
                            'id' => 'lbl-sesion-usuario',
                            'content' => 'HAS INICIADO SESIÓN COMO',
                            'style' => array(
                                'fontSize' => 11,
                                'fontWeight' => '700',
                                'color' => '#708391',
                                'marginBottom' => 4,
                                'dark' => array('color' => '#94A3B8'),
                            ),
                        ),
                        array(
                            'type' => 'Text',
                            'id' => 'val-sesion-usuario',
                            'content' => $usuario,
                            'style' => array(
                                'fontSize' => 14,
                                'fontWeight' => 'bold',
                                'color' => '#0A3156',
                                'dark' => array('color' => '#F4F7FA'),
                            ),
                        ),
                    ),
                ),
            ),
        );
    }

    private function usuario_autenticado(string $fallback = ''): string
    {
        if ($fallback !== '') {
            return $fallback;
        }

        if (isset($_SESSION['usuario']) && is_string($_SESSION['usuario']) && trim($_SESSION['usuario']) !== '') {
            return trim($_SESSION['usuario']);
        }

        if (isset($_SESSION['user']) && is_string($_SESSION['user']) && trim($_SESSION['user']) !== '') {
            return trim($_SESSION['user']);
        }

        $token = $this->token_autorizacion();
        if ($token !== '') {
            $payload = $this->payload_jwt($token);
            if (isset($payload['usuario']) && is_string($payload['usuario']) && trim($payload['usuario']) !== '') {
                return trim($payload['usuario']);
            }
            if (isset($payload['user']) && is_string($payload['user']) && trim($payload['user']) !== '') {
                return trim($payload['user']);
            }
        }

        return 'admin';
    }

    private function token_autorizacion(): string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if ($header === '' && function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            $header = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }
        if (is_string($header) && preg_match('/^Bearer\s+(.+)$/i', $header, $matches) === 1) {
            return trim($matches[1]);
        }
        return '';
    }

    private function payload_jwt(string $token): array
    {
        $partes = explode('.', $token);
        if (count($partes) < 2) {
            return array();
        }
        $json = base64_decode(strtr($partes[1], '-_', '+/'));
        if (!is_string($json) || $json === '') {
            return array();
        }
        $data = json_decode($json, true);
        if (!is_array($data)) {
            return array();
        }
        return $data;
    }

    private function estilo_boton(): array
    {
        return array(
            'backgroundColor' => '#14527F',
            'color'           => '#FFFFFF',
            'minHeight'       => 52,
            'padding'         => 14,
            'borderRadius'    => 12,
            'fontWeight'      => '800',
            'fontSize'        => 15,
            'textAlign'       => 'center',
            'justifyContent'  => 'center',
            'alignItems'      => 'center',
            'marginTop'       => 12,
            'dark'            => array('backgroundColor' => '#5AAEC8', 'color' => '#0A3156'),
        );
    }

    private function estilo_campo(): array
    {
        return array(
            'borderColor'     => '#E0E9EE',
            'borderRadius'    => 12,
            'minHeight'       => 52,
            'borderWidth'     => 1,
            'padding'         => 12,
            'fontSize'        => 14,
            'color'           => '#0A3156',
            'backgroundColor' => '#FFFFFF',
            'dark'            => array(
                'borderColor'     => '#334155',
                'backgroundColor' => '#1E293B',
                'color'           => '#F4F7FA',
            ),
        );
    }
}
