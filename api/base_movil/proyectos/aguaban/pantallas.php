<?php
namespace base_movil\v1\proyectos\aguaban;

use base_movil\v1\src\proyecto;

/**
 * Proyecto `aguaban`. Se activa en el servidor de Aguaban con
 * base_movil_proyecto = 'aguaban' en config/generales.php.
 *
 * Login con el diseño de la propuesta (AGUABAN_React_Native_Activacion_IVITEC):
 * logo, título, descripción, tarjeta con el formulario y pie. La barra de
 * pasos, "Dispositivo activado" y el usuario de solo lectura son de la
 * fase 2 (activación de dispositivos).
 *
 * Colores: todo lo que fija un color trae su bloque `dark`. Los botones usan
 * los de marca (#14527F con blanco en claro, #5AAEC8 con #0A3156 en oscuro),
 * los mismos de src/project/brand.ts en la app.
 *
 * fontWeight solo 'bold' a propósito: un APK anterior al 10/11 rechaza '800'
 * y rompería el login. Con 'bold' ese APK lo sigue mostrando (sin logo ni
 * letterSpacing, que ignora).
 */
class pantallas extends proyecto
{
    public function pantallas(): array
    {
        return array(
            'login' => 'pantalla_login',
            'home' => 'pantalla_home',
        );
    }

    public function pantallas_offline(): array
    {
        return array('home');
    }

    protected function pantalla_login(): array
    {
        return array(
            'screenId' => 'login',
            'title' => '',
            'ttl' => 86400, // casi nunca cambia: 1 día
            'components' => array(
                array(
                    'type' => 'Image',
                    'id' => 'img-login-logo',
                    'source' => 'logo',
                    'height' => 84,
                    'width' => 110,
                    'accessibilityLabel' => 'Logotipo de Aguaban',
                    'style' => array('alignSelf' => 'center', 'marginTop' => 16, 'marginBottom' => 20),
                ),
                array(
                    'type' => 'Text',
                    'id' => 'txt-login-eyebrow',
                    'content' => 'APLICACIÓN MÓVIL',
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
                    'id' => 'txt-login-titulo',
                    'content' => 'Inicia sesión',
                    'style' => array(
                        'fontSize' => 27,
                        'fontWeight' => 'bold',
                        'letterSpacing' => -0.8,
                        'textAlign' => 'center',
                        'color' => '#0A3156',
                        'marginTop' => 10,
                        'dark' => array('color' => '#F5F5F5'),
                    ),
                ),
                array(
                    'type' => 'Text',
                    'id' => 'txt-login-descripcion',
                    'content' => 'Ingresa con el usuario y la contraseña que te asignaron.',
                    'style' => array(
                        'fontSize' => 13,
                        'lineHeight' => 21,
                        'textAlign' => 'center',
                        'color' => '#708391',
                        'marginTop' => 8,
                        'marginBottom' => 24,
                        'dark' => array('color' => '#B3B3B3'),
                    ),
                ),
                array(
                    'type' => 'Container',
                    'id' => 'cnt-login-tarjeta',
                    'style' => array(
                        'backgroundColor' => '#FFFFFF',
                        'borderWidth' => 1,
                        'borderColor' => '#E0E9EE',
                        'borderRadius' => 16,
                        'padding' => 16,
                        'dark' => array('backgroundColor' => '#1E1E1E', 'borderColor' => '#2E3A44'),
                    ),
                    'children' => array(
                        array(
                            'type' => 'Form',
                            'id' => 'form-login',
                            'style' => array('gap' => 12),
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
                array(
                    'type' => 'Text',
                    'id' => 'txt-login-pie',
                    'content' => 'SEGURIDAD Y DESARROLLO · IVITEC',
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

    /**
     * Inicio provisional: el login necesita a dónde mandar. Se reemplaza
     * cuando estén definidas las pantallas de Aguaban (lecturas, checador).
     */
    protected function pantalla_home(): array
    {
        return array(
            'screenId' => 'home',
            'title' => 'Inicio',
            'ttl' => 60,
            'components' => array(
                array(
                    'type' => 'Text',
                    'id' => 'txt-home-bienvenida',
                    'content' => 'Bienvenido a Aguaban.',
                    'style' => array('fontSize' => 16, 'marginBottom' => 12),
                ),
                array(
                    'type' => 'DeviceStatus',
                    'id' => 'estado-home',
                    'items' => array('network', 'gps', 'location'),
                    'layout' => 'list',
                ),
                array(
                    'type' => 'Button',
                    'id' => 'btn-home-logout',
                    'label' => 'Cerrar sesión',
                    'style' => array_merge($this->estilo_boton(), array('marginTop' => 16)),
                    'action' => array('type' => 'LOGOUT'),
                ),
            ),
        );
    }

    /**
     * Botón principal con los colores de marca de Aguaban (los mismos de
     * src/project/brand.ts en la app). Un Button sin backgroundColor queda
     * transparente: el color tiene que venir aquí.
     */
    private function estilo_boton(): array
    {
        return array(
            'backgroundColor' => '#14527F',
            'color' => '#FFFFFF',
            'padding' => 14,
            'borderRadius' => 12,
            'fontWeight' => 'bold',
            'textAlign' => 'center',
            'marginTop' => 4,
            'dark' => array('backgroundColor' => '#5AAEC8', 'color' => '#0A3156'),
        );
    }

    private function estilo_campo(): array
    {
        return array(
            'borderColor' => '#E0E9EE',
            'borderRadius' => 10,
            'fontSize' => 14,
            'color' => '#0A3156',
            'dark' => array('borderColor' => '#3D3D3D', 'backgroundColor' => '#2A2A2A', 'color' => '#F5F5F5'),
        );
    }
}
