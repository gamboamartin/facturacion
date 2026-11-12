<?php
namespace base_movil\v1\proyectos\base;

use base_movil\v1\src\app_config;
use base_movil\v1\src\proyecto;

/**
 * Proyecto `base`: las pantallas de demostración de la aplicación base
 * (prototipos #1 a #7). Es el que usa el servidor de pruebas de la base y el
 * punto de partida para un cliente nuevo: copiar esta carpeta, cambiar el
 * namespace y reemplazar las pantallas.
 *
 * Login de Aguaban (base): Image, Input con revealable y estilos
 * letterSpacing y fontWeight '100'…'900'. Un APK anterior rechaza
 * fontWeight '800' (valor inválido) y muestra el aviso de "no disponible" en
 * lugar de Image. Por eso solo se usan en la pantalla de prueba `estilos`.
 */
class pantallas extends proyecto
{
    public function pantallas(): array
    {
        return array(
            'home' => 'pantalla_home',
            'registro' => 'pantalla_registro',
            'detalle' => 'pantalla_detalle',
            'confirmacion' => 'pantalla_confirmacion',
            'login' => 'pantalla_login',
            'mapa' => 'pantalla_mapa',
            'estilos' => 'pantalla_estilos',
            'estado' => 'pantalla_estado',
        );
    }

    /**
     * Solo las que el usuario necesita en campo; las de ttl 0 (resultados
     * como confirmacion) no van porque no se guardan.
     */
    public function pantallas_offline(): array
    {
        return array('home', 'registro', 'detalle', 'mapa', 'estado');
    }

    /** Fuente única de las zonas: la usan la pantalla y la validación del SUBMIT. */
    protected function opciones_zona(): array
    {
        return array(
            array('value' => '1', 'label' => 'Norte'),
            array('value' => '2', 'label' => 'Sur'),
        );
    }

    /** Fuente única del estilo del botón principal: lo usan todas las pantallas. */
    protected function estilo_boton_primario(): array
    {
        return array(
            'backgroundColor' => '#22A45D',
            'color' => '#FFFFFF',
            'padding' => 10,
            'borderRadius' => 6,
        );
    }

    protected function pantalla_home(): array
    {
        return array(
            'screenId' => 'home',
            'title' => 'Pantalla de prueba',
            'ttl' => 60, // corto: un cambio del backend se ve en máximo 1 minuto
            'components' => array(
                array(
                    'type' => 'Container',
                    'id' => 'card-1',
                    'style' => array(
                        'padding' => 16,
                        'backgroundColor' => '#F5F5F5',
                        'borderRadius' => 8,
                        'gap' => 8,
                        'dark' => array('backgroundColor' => '#1E1E1E'),
                    ),
                    'children' => array(
                        array(
                            'type' => 'Text',
                            'id' => 'title-1',
                            'content' => 'Proceso 2 prototipo 2 y 1 incluido',
                            'style' => array('fontSize' => 20, 'fontWeight' => 'bold'),
                        ),
                        array(
                            'type' => 'Text',
                            'id' => 'subtitle-1',
                            'content' => 'Este texto y este botón vienen del JSON del backend, no están hardcodeados en la app.',
                            'style' => array('fontSize' => 14, 'color' => '#555555', 'dark' => array('color' => '#B3B3B3')),
                        ),
                        array(
                            'type' => 'Button',
                            'id' => 'btn-1',
                            'label' => 'Continuar',
                            'style' => $this->estilo_boton_primario(),
                            'action' => array('type' => 'NAVIGATE', 'screenId' => 'registro'),
                        ),
                        array(
                            'type' => 'Button',
                            'id' => 'btn-prueba',
                            'label' => 'Función de prueba',
                            'style' => $this->estilo_boton_primario(),
                            'action' => array('type' => 'CALL', 'function' => 'funcion_prueba'),
                        ),
                        array(
                            'type' => 'Button',
                            'id' => 'btn-logout',
                            'label' => 'Cerrar sesión',
                            'action' => array('type' => 'LOGOUT'),
                        ),
                        array(
                            'type' => 'Text',
                            'id' => 'txt-desde-backend',
                            'content' => 'Este mensaje se agregó desde el backend sin generar un APK nuevo.',
                            'style' => array('fontSize' => 16, 'fontWeight' => 'bold', 'color' => '#22A45D'),
                        ),
                        array(
                            'type' => 'Button',
                            'id' => 'btn-ver-detalle',
                            'label' => 'Ver detalle',
                            'style' => $this->estilo_boton_primario(),
                            'action' => array('type' => 'NAVIGATE', 'screenId' => 'detalle'),
                        ),
                        array(
                            'type' => 'Button',
                            'id' => 'btn-ver-mapa',
                            'label' => 'Ver mapa',
                            'style' => $this->estilo_boton_primario(),
                            'action' => array('type' => 'NAVIGATE', 'screenId' => 'mapa'),
                        ),
                        array(
                            'type' => 'Button',
                            'id' => 'btn-ver-estilos',
                            'label' => 'Ver estilos',
                            'style' => $this->estilo_boton_primario(),
                            'action' => array('type' => 'NAVIGATE', 'screenId' => 'estilos'),
                        ),
                        array(
                            'type' => 'Button',
                            'id' => 'btn-ver-estado',
                            'label' => 'Estado del dispositivo',
                            'style' => $this->estilo_boton_primario(),
                            'action' => array('type' => 'NAVIGATE', 'screenId' => 'estado'),
                        ),
                    ),
                ),
            ),
        );
    }

    protected function pantalla_registro(): array
    {
        return array(
            'screenId' => 'registro',
            'title' => 'Registro',
            'ttl' => 300, // formulario estable: 5 minutos
            'components' => array(
                array(
                    'type' => 'Form',
                    'id' => 'form-registro',
                    'children' => array(
                        array(
                            'type' => 'FormGroup',
                            'id' => 'grp-acceso',
                            'label' => 'Datos de acceso',
                            'children' => array(
                                array(
                                    'type' => 'Input',
                                    'id' => 'in-usuario',
                                    'name' => 'usuario',
                                    'inputType' => 'text',
                                    'label' => 'Usuario',
                                    'required' => true,
                                    'placeholder' => 'Tu usuario',
                                ),
                                array(
                                    'type' => 'Input',
                                    'id' => 'in-password',
                                    'name' => 'password',
                                    'inputType' => 'password',
                                    'label' => 'Contraseña',
                                    'required' => true,
                                ),
                                array(
                                    'type' => 'Input',
                                    'id' => 'in-correo',
                                    'name' => 'correo',
                                    'inputType' => 'text',
                                    'label' => 'Correo electrónico',
                                    'required' => true,
                                    'placeholder' => 'Tu correo electrónico',
                                ),
                            ),
                        ),
                        array(
                            'type' => 'Select',
                            'id' => 'sel-zona',
                            'name' => 'zona',
                            'label' => 'Zona',
                            'required' => true,
                            'placeholder' => 'Selecciona tu zona',
                            'options' => $this->opciones_zona(),
                        ),
                        array(
                            'type' => 'Button',
                            'id' => 'btn-enviar',
                            'label' => 'Enviar',
                            'style' => $this->estilo_boton_primario(),
                            'action' => array('type' => 'SUBMIT', 'method' => 'guarda_registro'),
                        ),
                    ),
                ),
                array(
                    'type' => 'Button',
                    'id' => 'btn-detalle',
                    'label' => 'Ver detalle',
                    'action' => array('type' => 'NAVIGATE', 'screenId' => 'detalle'),
                ),
            ),
        );
    }

    protected function pantalla_detalle(): array
    {
        return array(
            'screenId' => 'detalle',
            'title' => 'Detalle',
            'ttl' => 30, // corto a propósito: permite probar el vencimiento del caché
            'components' => array(
                array(
                    'type' => 'Text',
                    'id' => 'txt-detalle',
                    'content' => 'Llegaste aquí con una acción NAVIGATE definida en el backend.',
                ),
                array(
                    'type' => 'Button',
                    'id' => 'btn-volver-registro',
                    'label' => 'Ir a registro',
                    'style' => $this->estilo_boton_primario(),
                    'action' => array('type' => 'NAVIGATE', 'screenId' => 'registro'),
                ),
            ),
        );
    }

    protected function pantalla_confirmacion(): array
    {
        return array(
            'screenId' => 'confirmacion',
            'title' => 'Registro enviado',
            'ttl' => 0, // es un resultado: no se cachea
            'components' => array(
                array(
                    'type' => 'Text',
                    'id' => 'txt-confirmacion',
                    'content' => 'El formulario se envió y el backend respondió con esta pantalla.',
                ),
                array(
                    'type' => 'Button',
                    'id' => 'btn-inicio',
                    'label' => 'Volver al inicio',
                    'style' => $this->estilo_boton_primario(),
                    'action' => array('type' => 'NAVIGATE', 'screenId' => 'home'),
                ),
            ),
        );
    }

    protected function pantalla_login(): array
    {
        return array(
            'screenId' => 'login',
            'title' => 'Iniciar sesión',
            'ttl' => 86400, // casi nunca cambia: 1 día
            'components' => array(
                array(
                    'type' => 'Form',
                    'id' => 'form-login',
                    'children' => array(
                        array(
                            'type' => 'Input',
                            'id' => 'in-login-usuario',
                            'name' => 'usuario',
                            'inputType' => 'text',
                            'label' => 'Usuario',
                            'required' => true,
                            'placeholder' => 'Tu usuario',
                        ),
                        array(
                            'type' => 'Input',
                            'id' => 'in-login-password',
                            'name' => 'password',
                            'inputType' => 'password',
                            'label' => 'Contraseña',
                            'required' => true,
                            // Botón Mostrar/Ocultar. Un APK anterior ignora la clave.
                            'revealable' => true,
                        ),
                        array(
                            'type' => 'Button',
                            'id' => 'btn-login',
                            'label' => 'Iniciar sesión',
                            'style' => $this->estilo_boton_primario(),
                            'action' => array('type' => 'SUBMIT', 'method' => 'login'),
                        ),
                    ),
                ),
            ),
        );
    }

    /**
     * Prototipo #5: componente Map. Los marcadores van como lista de datos
     * (no como componentes sueltos) para que la app los dibuje como una sola
     * capa y escale a muchos puntos.
     */
    protected function pantalla_mapa(): array
    {
        return array(
            'screenId' => 'mapa',
            'title' => 'Mapa',
            'ttl' => 300,
            'components' => array(
                array(
                    'type' => 'Text',
                    'id' => 'txt-mapa',
                    'content' => 'Las ubicaciones de este mapa vienen del backend.',
                ),
                array(
                    'type' => 'Map',
                    'id' => 'mapa-principal',
                    'height' => 320,
                    'region' => array('latitude' => 20.6736, 'longitude' => -103.3440, 'zoom' => 13),
                    'markers' => $this->marcadores_demo(),
                    'showUserLocation' => true,
                ),
                array(
                    'type' => 'Button',
                    'id' => 'btn-mapa-inicio',
                    'label' => 'Volver al inicio',
                    'style' => $this->estilo_boton_primario(),
                    'action' => array('type' => 'NAVIGATE', 'screenId' => 'home'),
                ),
            ),
        );
    }

    /**
     * Prototipo #7: estado del teléfono. El backend decide qué datos se ven y
     * en qué orden (items) y si van en lista o en una línea (layout).
     * cellular (chip, operador y señal en una frase) y airplaneMode los lee
     * el módulo nativo de la parte 2, sin permisos.
     */
    protected function pantalla_estado(): array
    {
        return array(
            'screenId' => 'estado',
            'title' => 'Estado del dispositivo',
            'ttl' => 300,
            'components' => array(
                array(
                    'type' => 'Text',
                    'id' => 'txt-estado',
                    'content' => 'Estos datos los lee el teléfono; el backend solo decide cuáles mostrar.',
                ),
                array(
                    'type' => 'DeviceStatus',
                    'id' => 'estado-lista',
                    'items' => array('network', 'wifi', 'cellular', 'airplaneMode', 'gps', 'location'),
                    'layout' => 'list',
                ),
                array(
                    'type' => 'Text',
                    'id' => 'txt-estado-compacto',
                    'content' => 'Versión compacta:',
                    'style' => array('marginTop' => 12),
                ),
                array(
                    'type' => 'DeviceStatus',
                    'id' => 'estado-compacto',
                    'items' => array('network', 'gps'),
                    'layout' => 'compact',
                ),
                array(
                    'type' => 'Button',
                    'id' => 'btn-estado-inicio',
                    'label' => 'Volver al inicio',
                    'style' => $this->estilo_boton_primario(),
                    'action' => array('type' => 'NAVIGATE', 'screenId' => app_config::PANTALLA_INICIAL),
                ),
            ),
        );
    }

    /** Marcadores de prueba. Con un cliente real saldrán de la base de datos. */
    protected function marcadores_demo(): array
    {
        return array(
            array('id' => 'm1', 'latitude' => 20.6767, 'longitude' => -103.3475, 'title' => 'Punto 1', 'description' => 'Centro'),
            array('id' => 'm2', 'latitude' => 20.6720, 'longitude' => -103.3390, 'title' => 'Punto 2', 'description' => 'Zona oriente'),
            array('id' => 'm3', 'latitude' => 20.6690, 'longitude' => -103.3510, 'title' => 'Punto 3'),
        );
    }

    /**
     * Pantalla de prueba del #5: estilos desde la API en todos los componentes.
     * ttl 0 para que cada cambio en el PHP se vea al instante.
     */
    protected function pantalla_estilos(): array
    {
        return array(
            'screenId' => 'estilos',
            'title' => 'Estilos desde la API',
            'ttl' => 0,
            'components' => array(
                // Imagen del registro del proyecto (src/project/images.ts): solo la clave.
                array(
                    'type' => 'Image',
                    'id' => 'img-logo',
                    'source' => 'logo',
                    'height' => 72,
                    'width' => 72,
                    'accessibilityLabel' => 'Logo de la app',
                    'style' => array('alignSelf' => 'center', 'marginBottom' => 8),
                ),
                array(
                    'type' => 'Text',
                    'id' => 'txt-espaciado',
                    'content' => 'APLICACIÓN MÓVIL · LETTERSPACING Y PESO 800',
                    'style' => array('fontSize' => 11, 'letterSpacing' => 1.5, 'fontWeight' => '800', 'textAlign' => 'center', 'color' => '#708391', 'marginBottom' => 8),
                ),
                array(
                    'type' => 'Text',
                    'id' => 'txt-titulo',
                    'content' => 'Estilos definidos por el backend',
                    'style' => array('fontSize' => 22, 'fontWeight' => 'bold', 'textAlign' => 'center', 'color' => '#22A45D', 'marginBottom' => 8, 'dark' => array('color' => '#4CD38A'))
                ),
                array(
                    'type' => 'Text',
                    'id' => 'txt-cursiva',
                    'content' => 'Texto en cursiva, subrayado y con una propiedad desconocida que se ignora',
                    'style' => array('fontStyle' => 'italic', 'textDecorationLine' => 'underline', 'color' => '#555555', 'shadowBlur' => 3, 'dark' => array('color' => '#B3B3B3'))
                ),
                array(
                    'type' => 'Container',
                    'id' => 'cnt-fila',
                    'style' => array('flexDirection' => 'row', 'gap' => 8, 'marginVertical' => 12),
                    'children' => array(
                        array(
                            'type' => 'Text',
                            'id' => 'txt-caja-1',
                            'content' => 'Caja 1',
                            'style' => array('flex' => 1, 'backgroundColor' => '#E8F5E9', 'borderWidth' => 1, 'borderColor' => '#22A45D', 'borderRadius' => 8, 'padding' => 12, 'textAlign' => 'center', 'dark' => array('backgroundColor' => '#1E3A28'))
                        ),
                        array(
                            'type' => 'Text',
                            'id' => 'txt-caja-2',
                            'content' => 'Caja 2',
                            'style' => array('flex' => 1, 'backgroundColor' => '#FFF3E0', 'borderWidth' => 1, 'borderColor' => '#FB8C00', 'borderRadius' => 8, 'padding' => 12, 'textAlign' => 'center', 'dark' => array('backgroundColor' => '#3A2A14'))
                        )
                    )
                ),
                array(
                    'type' => 'Form',
                    'id' => 'frm-estilos',
                    'style' => array('padding' => 12, 'borderWidth' => 1, 'borderColor' => '#CCCCCC', 'borderRadius' => 8),
                    'children' => array(
                        array(
                            'type' => 'FormGroup',
                            'id' => 'grp-estilos',
                            'label' => 'Campos con estilo',
                            'style' => array('gap' => 12),
                            'children' => array(
                                array(
                                    'type' => 'Input',
                                    'id' => 'inp-estilo',
                                    'name' => 'nombre',
                                    'inputType' => 'text',
                                    'label' => 'Input',
                                    'placeholder' => 'Escribe algo',
                                    'style' => array('backgroundColor' => '#F5F5F5', 'borderColor' => '#22A45D', 'borderRadius' => 12, 'fontSize' => 16, 'dark' => array('backgroundColor' => '#2A2A2A'))
                                ),
                                array(
                                    'type' => 'Select',
                                    'id' => 'sel-estilo',
                                    'name' => 'opcion',
                                    'label' => 'Select',
                                    'options' => array(
                                        array('value' => 'a', 'label' => 'Opción A'),
                                        array('value' => 'b', 'label' => 'Opción B')
                                    ),
                                    'style' => array('backgroundColor' => '#F5F5F5', 'borderColor' => '#FB8C00', 'color' => '#FB8C00', 'fontWeight' => 'bold', 'dark' => array('backgroundColor' => '#2A2A2A'))
                                )
                            )
                        )
                    )
                ),
                array(
                    'type' => 'Button',
                    'id' => 'btn-volver-estilos',
                    'label' => 'Volver al inicio',
                    'action' => array('type' => 'NAVIGATE', 'screenId' => 'home'),
                    'style' => array('backgroundColor' => '#22A45D', 'padding' => 14, 'borderRadius' => 8, 'marginTop' => 16, 'color' => '#FFFFFF', 'fontWeight' => 'bold', 'textAlign' => 'center')
                )
            )
        );
    }
}
