<?php
namespace base_movil\v1\src;

use gamboamartin\errores\errores;
use PDO;

/**
 * Sigue la misma convención que app_cobranza.php de em3: los métodos
 * públicos devuelven arrays (nunca hacen echo directo), y es el
 * enrutador (index.php) quien decide cómo mostrar la respuesta.
 *
 * Prototipo #3: todas las pantallas requieren sesión excepto las de
 * PANTALLAS_PUBLICAS.
 * Prototipo #4: sin sesión válida se responde 401; la app borra el token
 * y navega a login. Cada error lleva su http_code (error_http) y el router
 * decide la salida. Cada pantalla define su `ttl` (segundos que la app
 * puede servirla desde caché; 0 = no se cachea).
 */
class app_screens
{
    private errores $errores;
    private app_sesion $sesion;

    /** Pantallas que se pueden ver sin sesión. */
    private const PANTALLAS_PUBLICAS = array('login');

    public function __construct(PDO $link)
    {
        $this->errores = new errores();
        $this->sesion = new app_sesion($link);
    }

    public function obten_pantalla(): array
    {
        if (!isset($_GET['screenId']) || !is_string($_GET['screenId']) || trim($_GET['screenId']) === '') {
            return $this->error_http(400, 'Error $_GET[screenId] debe existir', $_GET);
        }

        $screenId = trim($_GET['screenId']);

        // Pantallas disponibles: screenId => método que la construye.
        // Agregar una pantalla = agregar una línea aquí + su método.
        $pantallas = array(
            'home' => 'pantalla_home',
            'registro' => 'pantalla_registro',
            'detalle' => 'pantalla_detalle',
            'confirmacion' => 'pantalla_confirmacion',
            'login' => 'pantalla_login',
            'mapa' => 'pantalla_mapa',
        );

        if (!isset($pantallas[$screenId])) {
            return $this->error_http(404, "No existe la pantalla '$screenId'", array('screenId' => $screenId));
        }

        if (!in_array($screenId, self::PANTALLAS_PUBLICAS, true) && !$this->sesion->tiene_sesion()) {
            return $this->error_http(401, 'Sesión no válida o expirada');
        }

        $metodo = $pantallas[$screenId];
        return $this->$metodo();
    }

    /**
     * Recibe el SUBMIT del formulario de registro.
     * Prototipo #2: sin base de datos, no se guarda nada. Se valida con las
     * MISMAS reglas que definen la pantalla (required y options) y se responde
     * con la siguiente acción: el servidor decide a dónde ir.
     */
    public function guarda_registro(): array
    {
        if (!$this->sesion->tiene_sesion()) {
            return $this->error_http(401, 'Sesión no válida o expirada');
        }

        $body = json_decode(file_get_contents('php://input'), true);

        if (!is_array($body) || !isset($body['values']) || !is_array($body['values'])) {
            return $this->error_http(400, 'Error el body debe traer "values"');
        }

        $pantalla = $this->pantalla_registro();
        $validacion = $this->valida_valores($pantalla['components'], $body['values']);

        if (isset($validacion['error'])) {
            // Datos inválidos del usuario: 400, no 500.
            $validacion['http_code'] = 400;
            return $validacion;
        }

        return array(
            'action' => array('type' => 'NAVIGATE', 'screenId' => 'confirmacion'),
        );
    }

    /**
     * Valida los valores recibidos contra las reglas de los campos de una
     * pantalla. Devuelve array vacío si todo es válido, o el error.
     * Reutilizable para cualquier formulario futuro.
     */
    private function valida_valores(array $componentes, array $values): array
    {
        $reglas = $this->reglas_campos($componentes);

        foreach ($reglas as $campo => $regla) {
            $valor = $values[$campo] ?? '';

            if (!is_string($valor)) {
                return $this->errores->error(
                    mensaje: "Error el campo $campo debe ser texto",
                    data: array('campo' => $campo)
                );
            }

            if ($regla['required'] && trim($valor) === '') {
                // No se incluye $values en data: puede traer la contraseña.
                return $this->errores->error(
                    mensaje: "Error el campo $campo es obligatorio",
                    data: array('campo' => $campo)
                );
            }

            if ($regla['options'] !== null && $valor !== '' && !in_array($valor, $regla['options'], true)) {
                return $this->errores->error(
                    mensaje: "Error el campo $campo no es válido",
                    data: array('campo' => $campo)
                );
            }
        }

        return array();
    }

    /**
     * Recorre la definición de una pantalla y junta las reglas de cada campo
     * (Input y Select), sin importar qué tan anidados estén (FormGroup, Container).
     * Resultado: array('usuario' => array('required' => true, 'options' => null), ...)
     */
    private function reglas_campos(array $componentes): array
    {
        $reglas = array();

        foreach ($componentes as $componente) {
            $tipo = $componente['type'] ?? '';

            if ($tipo === 'Input') {
                $reglas[$componente['name']] = array(
                    'required' => ($componente['required'] ?? false) === true,
                    'options' => null,
                );
                continue;
            }

            if ($tipo === 'Select') {
                $reglas[$componente['name']] = array(
                    'required' => ($componente['required'] ?? false) === true,
                    'options' => array_column($componente['options'], 'value'),
                );
                continue;
            }

            if (isset($componente['children']) && is_array($componente['children'])) {
                $reglas = array_merge($reglas, $this->reglas_campos($componente['children']));
            }
        }

        return $reglas;
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

    private function pantalla_detalle(): array
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

    private function pantalla_confirmacion(): array
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

    private function pantalla_login(): array
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
     * Error del framework + código HTTP para el router.
     * La clase clasifica el error; el router decide la salida.
     */
    private function error_http(int $http_code, string $mensaje, array $data = array()): array
    {
        $error = $this->errores->error(mensaje: $mensaje, data: $data);
        $error['http_code'] = $http_code;
        return $error;
    }

    /**
     * Prototipo #5: componente Map. Los marcadores van como lista de datos
     * (no como componentes sueltos) para que la app los dibuje como una sola
     * capa y escale a muchos puntos.
     */
    private function pantalla_mapa(): array
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

    /** Marcadores de prueba. Con un cliente real saldrán de la base de datos. */
    private function marcadores_demo(): array
    {
        return array(
            array('id' => 'm1', 'latitude' => 20.6767, 'longitude' => -103.3475, 'title' => 'Punto 1', 'description' => 'Centro'),
            array('id' => 'm2', 'latitude' => 20.6720, 'longitude' => -103.3390, 'title' => 'Punto 2', 'description' => 'Zona oriente'),
            array('id' => 'm3', 'latitude' => 20.6690, 'longitude' => -103.3510, 'title' => 'Punto 3'),
        );
    }
}