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
                        // Aquí envolvemos los inputs y el botón dentro de un Form
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
                                    )
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

   protected function pantalla_login(): array
    {
        return array(
            'screenId' => 'login',
            'title' => '',
            'ttl' => 0, // En 0 para invalidar la caché local en desarrollo
            'components' => array(
                // Logo oficial de Aguaban (s.logo: width 105, height 66)
                array(
                    'type' => 'Image',
                    'id' => 'img-login-logo',
                    'source' => 'logo',
                    'width' => 105,
                    'height' => 66,
                    'accessibilityLabel' => 'Logotipo de Aguaban',
                    'style' => array(
                        'alignSelf' => 'center',
                        'marginTop' => 24,
                        'marginBottom' => 16,
                    ),
                ),
                // Subtítulo / Eyebrow (s.stepEyebrow: C.blue, 10px, spacing 2, bold 800)
                array(
                    'type' => 'Text',
                    'id' => 'txt-login-eyebrow',
                    'content' => 'APLICACIÓN MÓVIL',
                    'style' => array(
                        'fontSize' => 10,
                        'fontWeight' => '800',
                        'letterSpacing' => 2,
                        'textAlign' => 'center',
                        'color' => '#1679B8',
                        'dark' => array('color' => '#5AAEC8'),
                    ),
                ),
                // Título principal (s.title: C.navy, 27px, spacing -0.8, bold 800)
                array(
                    'type' => 'Text',
                    'id' => 'txt-login-titulo',
                    'content' => 'Inicia sesión',
                    'style' => array(
                        'fontSize' => 27,
                        'fontWeight' => '800',
                        'letterSpacing' => -0.8,
                        'textAlign' => 'center',
                        'color' => '#0A3156',
                        'marginTop' => 13,
                        'marginBottom' => 9,
                        'dark' => array('color' => '#F4F7FA'),
                    ),
                ),
                // Descripción (s.description: C.secondary, 13px, lineHeight 21)
                array(
                    'type' => 'Text',
                    'id' => 'txt-login-descripcion',
                    'content' => 'Ingresa con el usuario y la contraseña que te asignaron.',
                    'style' => array(
                        'fontSize' => 13,
                        'lineHeight' => 21,
                        'textAlign' => 'center',
                        'color' => '#708391',
                        'marginBottom' => 24,
                        'dark' => array('color' => '#A2B2BB'),
                    ),
                ),
                // Tarjeta contenedora blanca (s.card: C.white, border C.border, radius 16, padding 14)
                array(
                    'type' => 'Container',
                    'id' => 'cnt-login-tarjeta',
                    'style' => array(
                        'backgroundColor' => '#FFFFFF',
                        'borderWidth' => 1,
                        'borderColor' => '#E0E9EE',
                        'borderRadius' => 16,
                        'padding' => 14,
                        'dark' => array('backgroundColor' => '#1E1E1E', 'borderColor' => '#2E3A44'),
                    ),
                    'children' => array(
                        array(
                            'type' => 'Form',
                            'id' => 'form-login',
                            'style' => array('gap' => 14),
                            'children' => array(
                                array(
                                    'type' => 'Input',
                                    'id' => 'in-login-usuario',
                                    'name' => 'usuario',
                                    'inputType' => 'text',
                                    'label' => 'Usuario',
                                    'required' => true,
                                    'placeholder' => 'Tu usuario',
                                    'style' => $this->estilo_campo(),
                                ),
                                array(
                                    'type' => 'Input',
                                    'id' => 'in-login-password',
                                    'name' => 'password',
                                    'inputType' => 'password',
                                    'label' => 'Contraseña',
                                    'required' => true,
                                    'placeholder' => 'Ingresa tu contraseña',
                                    'revealable' => true,
                                    'style' => $this->estilo_campo(),
                                ),
                                array(
                                    'type' => 'Button',
                                    'id' => 'btn-login',
                                    'label' => 'Iniciar sesión',
                                    'style' => $this->estilo_boton(),
                                    'action' => array('type' => 'SUBMIT', 'method' => 'login'),
                                ),
                            ),
                        ),
                    ),
                ),
                // Pie de página (s.footer: 9px, spacing 1.1, color #A2B2BB)
                array(
                    'type' => 'Text',
                    'id' => 'txt-login-pie',
                    'content' => 'SEGURIDAD Y DESARROLLO · IVITEC',
                    'style' => array(
                        'fontSize' => 9,
                        'letterSpacing' => 1.1,
                        'textAlign' => 'center',
                        'color' => '#A2B2BB',
                        'marginTop' => 22,
                        'dark' => array('color' => '#708391'),
                    ),
                ),
            ),
        );
    }

    protected function pantalla_home(): array
    {
        return array(
            'screenId' => 'home',
            'title' => 'Bienvenida',
            'ttl' => 60,
            'components' => array(
                array(
                    'type' => 'Image',
                    'id' => 'img-home-logo',
                    'source' => 'logo',
                    'height' => 84,
                    'width' => 110,
                    'accessibilityLabel' => 'Logotipo de Aguaban',
                    'style' => array('alignSelf' => 'center', 'marginTop' => 16, 'marginBottom' => 20),
                ),
                array(
                    'type' => 'Text',
                    'id' => 'txt-home-subtitulo',
                    'content' => 'TU ACCESO ESTÁ LISTO',
                    'style' => array(
                        'fontSize' => 12,
                        'fontWeight' => '600',
                        'textAlign' => 'center',
                        'marginBottom' => 8,
                    ),
                ),
                array(
                    'type' => 'Text',
                    'id' => 'txt-home-titulo',
                    'content' => '¡Bienvenidos!',
                    'style' => array(
                        'fontSize' => 28,
                        'fontWeight' => 'bold',
                        'textAlign' => 'center',
                        'marginBottom' => 12,
                    ),
                ),
                array(
                    'type' => 'Text',
                    'id' => 'txt-home-descripcion',
                    'content' => 'Nos alegra tenerte aquí. Tu espacio de trabajo en AGUABAN está listo para comenzar.',
                    'style' => array(
                        'fontSize' => 14,
                        'textAlign' => 'center',
                        'marginBottom' => 24,
                    ),
                ),
                array(
                    'type'  => 'UserSessionCard',
                    'id'    => 'card-sesion-usuario',
                    'style' => array('marginTop' => 20), // o marginTop => 'auto' si el contenedor es flex
                ),
            ),
        );
    }

   private function estilo_boton(): array
    {
        return array(
            'backgroundColor' => '#14527F', // C.primary
            'color'           => '#FFFFFF', // C.white
            'minHeight'       => 52,        // s.action
            'padding'         => 14,
            'borderRadius'    => 11,        // s.action.borderRadius
            'fontWeight'      => '800',     // s.actionText
            'fontSize'        => 14,        // s.actionText
            'textAlign'       => 'center',
            'marginTop'       => 6,
            'dark'            => array('backgroundColor' => '#5AAEC8', 'color' => '#0A3156'),
        );
    }

  private function estilo_campo(): array
    {
        return array(
            'borderColor'     => '#E0E9EE', // C.border
            'borderRadius'    => 10,        // s.input
            'minHeight'       => 48,        // s.input
            'padding'         => 12,
            'fontSize'        => 14,        // s.input
            'color'           => '#0A3156', // C.navy
            'dark'            => array(
                'borderColor'     => '#334155',
                'backgroundColor' => '#1E293B',
                'color'           => '#F4F7FA',
            ),
        );
    }
}