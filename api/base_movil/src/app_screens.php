<?php
namespace base_movil\v1\src;

use config\generales;
use gamboamartin\errores\errores;
use PDO;

/**
 * Sirve las pantallas de la app. Sigue la convención de app_cobranza.php de
 * em3: los métodos públicos devuelven arrays y el enrutador (index.php)
 * decide cómo responder.
 *
 * Las pantallas NO viven aquí: son del proyecto de este servidor
 * (base_movil_proyecto en config/generales.php → proyectos/<proyecto>/).
 * Esta clase es la misma para todos los clientes: sesión, caché sin
 * conexión y validación del SUBMIT. Ver src/proyecto.php y proyectos/README.md.
 *
 * - Prototipo #3/#4: sin sesión válida se responde 401 (salvo las pantallas
 *   públicas del proyecto); la app borra el token y navega a login.
 * - Cada pantalla define su `ttl` (segundos en caché; 0 = no se cachea).
 * - Modo sin conexión: pantallas_offline devuelve de una vez las pantallas
 *   que el proyecto marca para precargar.
 * - Compatibilidad con APKs ya instalados: una propiedad o un componente
 *   nuevos se ignoran o salen como "no disponible", pero un valor nuevo en
 *   una propiedad que ya existía (fontWeight '800') rompe la pantalla.
 */
class app_screens
{
    private errores $errores;
    private app_sesion $sesion;

    /** El proyecto de este servidor, o lo que le falta (para el log). */
    private proyecto|string $proyecto;

    public function __construct(PDO $link)
    {
        $this->errores = new errores();
        $this->sesion = new app_sesion($link);
        $this->proyecto = proyecto::activo(new generales());
    }

    public function obten_pantalla(): array
    {
        $proyecto = $this->proyecto();
        if (is_array($proyecto)) {
            return $proyecto;
        }

        if (!isset($_GET['screenId']) || !is_string($_GET['screenId']) || trim($_GET['screenId']) === '') {
            return $this->error_http(400, 'Error $_GET[screenId] debe existir', $_GET);
        }

        $screenId = trim($_GET['screenId']);

        if (!isset($proyecto->pantallas()[$screenId])) {
            return $this->error_http(404, "No existe la pantalla '$screenId'", array('screenId' => $screenId));
        }

        if (!in_array($screenId, $proyecto->pantallas_publicas(), true) && !$this->sesion->tiene_sesion()) {
            return $this->error_http(401, 'Sesión no válida o expirada');
        }

        return $this->construye($proyecto, $screenId);
    }

    /**
     * Modo sin conexión: devuelve en una sola respuesta las pantallas que el
     * proyecto precarga, para que la app las guarde después del login.
     * Una pantalla con ttl 0 se omite aunque esté en la lista: la app no la
     * guardaría.
     */
    public function pantallas_offline(): array
    {
        $proyecto = $this->proyecto();
        if (is_array($proyecto)) {
            return $proyecto;
        }

        if (!$this->sesion->tiene_sesion()) {
            return $this->error_http(401, 'Sesión no válida o expirada');
        }

        $screens = array();
        foreach ($proyecto->pantallas_offline() as $screenId) {
            $pantalla = $this->construye($proyecto, $screenId);
            if (isset($pantalla['error'])) {
                return $pantalla;
            }
            if (($pantalla['ttl'] ?? 0) <= 0) {
                continue;
            }
            $screens[] = $pantalla;
        }

        return array('screens' => $screens);
    }

    /**
     * Recibe el SUBMIT del formulario de registro (pantalla de demostración
     * del proyecto base). Se valida con las MISMAS reglas que definen la
     * pantalla (required y options) y se responde con la siguiente acción.
     */
    public function guarda_registro(): array
    {
        $proyecto = $this->proyecto();
        if (is_array($proyecto)) {
            return $proyecto;
        }

        if (!$this->sesion->tiene_sesion()) {
            return $this->error_http(401, 'Sesión no válida o expirada');
        }

        if (!isset($proyecto->pantallas()['registro'])) {
            return $this->error_http(404, 'Error metodo no habilitado');
        }

        $body = json_decode(file_get_contents('php://input'), true);

        if (!is_array($body) || !isset($body['values']) || !is_array($body['values'])) {
            return $this->error_http(400, 'Error el body debe traer "values"');
        }

        $pantalla = $this->construye($proyecto, 'registro');
        if (isset($pantalla['error'])) {
            return $pantalla;
        }
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
     * El proyecto ya cargado, o el error listo para el router. El detalle de
     * lo que falta va al log (es para el programador); a la app le llega un
     * mensaje genérico porque estos métodos son públicos.
     */
    private function proyecto(): proyecto|array
    {
        if ($this->proyecto instanceof proyecto) {
            return $this->proyecto;
        }
        error_log('base_movil CONFIGURACION: ' . $this->proyecto);
        return $this->error_http(500, proyecto::MENSAJE_PUBLICO);
    }

    private function construye(proyecto $proyecto, string $screenId): array
    {
        $pantalla = $proyecto->construye($screenId);
        if (isset($pantalla['error']) && is_string($pantalla['error'])) {
            error_log("base_movil CONFIGURACION: pantalla '$screenId': " . $pantalla['error']);
            return $this->error_http(500, proyecto::MENSAJE_PUBLICO);
        }
        return $pantalla;
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
}
