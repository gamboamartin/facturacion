<?php
namespace base_movil\v1\src;

use gamboamartin\errores\errores;

/**
 * Sigue la misma convención que app_cobranza.php de em3: los métodos
 * públicos devuelven arrays (nunca hacen echo directo), y es el
 * enrutador (index.php) quien decide cómo mostrar la respuesta.
 */
class app_screens
{
    private errores $errores;

    public function __construct()
    {
        $this->errores = new errores();
    }

    public function obten_pantalla(): array
    {
        if (!isset($_GET['screenId']) || !is_string($_GET['screenId']) || trim($_GET['screenId']) === '') {
            return $this->errores->error(
                mensaje: 'Error $_GET[screenId] debe existir',
                data: $_GET
            );
        }

        $screenId = trim($_GET['screenId']);

        // Pantallas disponibles: screenId => método que la construye.
        // Agregar una pantalla = agregar una línea aquí + su método.
        $pantallas = array(
            'home' => 'pantalla_home',
            'registro' => 'pantalla_registro',
            'detalle' => 'pantalla_detalle',
            'confirmacion' => 'pantalla_confirmacion',
        );

        if (!isset($pantallas[$screenId])) {
            return $this->errores->error(
                mensaje: "No existe la pantalla '$screenId'",
                data: array('screenId' => $screenId)
            );
        }

        $metodo = $pantallas[$screenId];
        return $this->$metodo();
    }

    /**
     * Recibe el SUBMIT del formulario de registro.
     * Prototipo #2: sin base de datos, no se guarda nada. Solo se valida
     * lo obligatorio (el cliente no es la fuente de verdad) y se responde
     * con la siguiente acción: el servidor decide a dónde ir.
     */
    public function guarda_registro(): array
    {
        $body = json_decode(file_get_contents('php://input'), true);

        if (!is_array($body) || !isset($body['values']) || !is_array($body['values'])) {
            return $this->errores->error(
                mensaje: 'Error el body debe traer "values"',
                data: array()
            );
        }

        $values = $body['values'];

        foreach (array('usuario', 'password') as $campo) {
            if (!isset($values[$campo]) || !is_string($values[$campo]) || trim($values[$campo]) === '') {
                // No se incluye $values en data: trae la contraseña.
                return $this->errores->error(
                    mensaje: "Error el campo $campo es obligatorio",
                    data: array('campo' => $campo)
                );
            }
        }

        $zonas_validas = array_column($this->opciones_zona(), 'value');

        if (!isset($values['zona']) || !in_array($values['zona'], $zonas_validas, true)) {
            return $this->errores->error(
                mensaje: 'Error el campo zona no es válido',
                data: array('campo' => 'zona')
            );
        }

        return array(
            'action' => array('type' => 'NAVIGATE', 'screenId' => 'confirmacion'),
        );
    }

    /** Fuente única de las zonas: la usan la pantalla y la validación del SUBMIT. */
    private function opciones_zona(): array
    {
        return array(
            array('value' => '1', 'label' => 'Norte'),
            array('value' => '2', 'label' => 'Sur'),
        );
    }

    /** Fuente única del estilo del botón principal: lo usan todas las pantallas. */
    private function estilo_boton_primario(): array
    {
        return array(
            'backgroundColor' => '#22A45D',
            'color' => '#FFFFFF',
            'padding' => 10,
            'borderRadius' => 6,
        );
    }

    private function pantalla_home(): array
    {
        return array(
            'screenId' => 'home',
            'title' => 'Pantalla de prueba',
            'components' => array(
                array(
                    'type' => 'Container',
                    'id' => 'card-1',
                    'style' => array(
                        'padding' => 16,
                        'backgroundColor' => '#F5F5F5',
                        'borderRadius' => 8,
                        'gap' => 8,
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
                            'style' => array('fontSize' => 14, 'color' => '#555555'),
                        ),
                        array(
                            'type' => 'Button',
                            'id' => 'btn-1',
                            'label' => 'Continuar',
                            'style' => $this->estilo_boton_primario(),
                            'action' => array('type' => 'NAVIGATE', 'screenId' => 'registro'),
                        ),
                    ),
                ),
            ),
        );
    }

    private function pantalla_registro(): array
    {
        return array(
            'screenId' => 'registro',
            'title' => 'Registro',
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

    private function pantalla_detalle(): array
    {
        return array(
            'screenId' => 'detalle',
            'title' => 'Detalle',
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

    private function pantalla_confirmacion(): array
    {
        return array(
            'screenId' => 'confirmacion',
            'title' => 'Registro enviado',
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
}