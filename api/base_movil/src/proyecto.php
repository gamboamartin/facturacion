<?php
namespace base_movil\v1\src;

use config\generales;

/**
 * Contrato de las pantallas de un proyecto (un cliente: base, aguaban...).
 *
 * El repo del backend es uno solo para todos los clientes. Cada servidor
 * dice de qué proyecto es con base_movil_proyecto en config/generales.php,
 * y app_screens sirve las pantallas de ese proyecto:
 *
 *   api/base_movil/proyectos/<proyecto>/pantallas.php
 *   namespace base_movil\v1\proyectos\<proyecto>;
 *   class pantallas extends \base_movil\v1\src\proyecto { ... }
 *
 * Si el proyecto no está configurado o le falta algo obligatorio, la app no
 * recibe ninguna pantalla: el error dice exactamente qué hacer (en el log del
 * servidor; al cliente solo le llega un mensaje genérico porque el método es
 * público). Ver proyectos/README.md.
 */
abstract class proyecto
{
    /** Toda app necesita estas dos: la de entrada y a la que manda el login. */
    public const PANTALLAS_OBLIGATORIAS = array('login', app_config::PANTALLA_INICIAL);

    private const PATRON_NOMBRE = '/^[a-z][a-z0-9_]*$/';

    /** Mensaje para la app cuando el servidor está mal configurado. Sin detalles: es público. */
    public const MENSAJE_PUBLICO = 'La app no está configurada en este servidor';

    /**
     * Pantallas del proyecto: screenId => nombre del método de esta clase que
     * la construye. Debe incluir PANTALLAS_OBLIGATORIAS.
     */
    abstract public function pantallas(): array;

    /** Pantallas que se ven sin sesión. Por defecto solo el login. */
    public function pantallas_publicas(): array
    {
        return array('login');
    }

    /** Pantallas que la app precarga después del login para usarlas sin conexión. */
    public function pantallas_offline(): array
    {
        return array();
    }

    /**
     * Construye una pantalla del proyecto y revisa su forma mínima.
     * Devuelve la pantalla o array('error' => ...) con el motivo para el log.
     */
    final public function construye(string $screenId): array
    {
        $metodo = $this->pantallas()[$screenId] ?? null;
        if ($metodo === null) {
            return array('error' => "el proyecto no tiene la pantalla '$screenId'");
        }

        $pantalla = $this->$metodo();
        if (!is_array($pantalla) || ($pantalla['screenId'] ?? null) !== $screenId
            || !isset($pantalla['components']) || !is_array($pantalla['components'])) {
            return array('error' => "$metodo() debe devolver array('screenId' => '$screenId', 'title' => ..., 'ttl' => ..., 'components' => array(...))");
        }

        if ($screenId === 'login') {
            $problema = self::revisa_login($pantalla['components']);
            if ($problema !== null) {
                return array('error' => $problema);
            }
        }
        return $pantalla;
    }

    /**
     * Carga el proyecto de este servidor. Devuelve el proyecto o un string con
     * lo que el programador tiene que hacer.
     */
    public static function activo(generales $generales): proyecto|string
    {
        $nombre = $generales->base_movil_proyecto ?? null;
        if ($nombre === null || $nombre === '') {
            return "falta base_movil_proyecto en config/generales.php. Agrega public string \$base_movil_proyecto = '<proyecto>'; "
                . "con el nombre de una carpeta de api/base_movil/proyectos/, por ejemplo 'base' o 'aguaban'.";
        }
        if (!is_string($nombre) || preg_match(self::PATRON_NOMBRE, $nombre) !== 1) {
            return "base_movil_proyecto en config/generales.php no es válido: debe ser el nombre de una carpeta de "
                . "api/base_movil/proyectos/, en minúsculas, con números y guion bajo (por ejemplo 'aguaban').";
        }

        $archivo = __DIR__ . '/../proyectos/' . $nombre . '/pantallas.php';
        if (!is_file($archivo)) {
            return "no existe api/base_movil/proyectos/$nombre/pantallas.php. Copia la carpeta proyectos/base como punto de partida y cambia su namespace a base_movil\\v1\\proyectos\\$nombre.";
        }
        require_once $archivo;

        $clase = 'base_movil\\v1\\proyectos\\' . $nombre . '\\pantallas';
        if (!class_exists($clase, false) || !is_subclass_of($clase, self::class)) {
            return "proyectos/$nombre/pantallas.php debe declarar namespace base_movil\\v1\\proyectos\\$nombre; y class pantallas extends \\base_movil\\v1\\src\\proyecto.";
        }

        $proyecto = new $clase();
        $problema = $proyecto->revisa_contrato();
        if ($problema !== null) {
            return "proyecto '$nombre': $problema";
        }
        return $proyecto;
    }

    /** Revisa que las listas del proyecto sean coherentes. null = todo bien. */
    private function revisa_contrato(): ?string
    {
        $pantallas = $this->pantallas();
        foreach (self::PANTALLAS_OBLIGATORIAS as $obligatoria) {
            if (!isset($pantallas[$obligatoria])) {
                return "falta la pantalla obligatoria '$obligatoria' en pantallas(). Toda app necesita 'login' y '"
                    . app_config::PANTALLA_INICIAL . "' (la pantalla a la que manda el login).";
            }
        }
        foreach ($pantallas as $screenId => $metodo) {
            if (!is_string($metodo) || !method_exists($this, $metodo)) {
                return "pantallas() dice que '$screenId' la construye $metodo(), pero ese método no existe en la clase.";
            }
        }
        if (!in_array('login', $this->pantallas_publicas(), true)) {
            return "pantallas_publicas() debe incluir 'login': sin él nadie puede entrar.";
        }
        foreach (array('pantallas_publicas' => $this->pantallas_publicas(), 'pantallas_offline' => $this->pantallas_offline()) as $lista => $ids) {
            foreach ($ids as $id) {
                if (!isset($pantallas[$id])) {
                    return "$lista() incluye '$id', que no está en pantallas().";
                }
            }
        }
        return null;
    }

    /**
     * El login de cualquier proyecto lo atiende app_sesion::login, que espera
     * values.usuario y values.password. La pantalla puede verse como el
     * cliente quiera, pero dentro de un mismo Form tiene que traer:
     * - un Input con name 'usuario';
     * - un Input con name 'password' e inputType 'password';
     * - un Button con action array('type' => 'SUBMIT', 'method' => 'login').
     */
    private static function revisa_login(array $componentes): ?string
    {
        $formularios = self::busca($componentes, fn(array $c) => ($c['type'] ?? '') === 'Form');
        foreach ($formularios as $form) {
            $hijos = $form['children'] ?? array();
            if (!is_array($hijos)) {
                continue;
            }
            $usuario = self::busca($hijos, fn(array $c) => ($c['type'] ?? '') === 'Input' && ($c['name'] ?? '') === 'usuario');
            $password = self::busca($hijos, fn(array $c) => ($c['type'] ?? '') === 'Input' && ($c['name'] ?? '') === 'password'
                && ($c['inputType'] ?? '') === 'password');
            $boton = self::busca($hijos, fn(array $c) => ($c['type'] ?? '') === 'Button'
                && (($c['action']['type'] ?? '') === 'SUBMIT') && (($c['action']['method'] ?? '') === 'login'));
            if ($usuario !== array() && $password !== array() && $boton !== array()) {
                return null;
            }
        }
        return "la pantalla 'login' debe tener un Form con: un Input name 'usuario', un Input name 'password' con inputType 'password' "
            . "y un Button con action array('type' => 'SUBMIT', 'method' => 'login'). Ver proyectos/base/pantallas.php.";
    }

    /** Componentes que cumplen $condicion, a cualquier profundidad (Container, FormGroup, Form). */
    private static function busca(array $componentes, callable $condicion): array
    {
        $encontrados = array();
        foreach ($componentes as $componente) {
            if (!is_array($componente)) {
                continue;
            }
            if ($condicion($componente)) {
                $encontrados[] = $componente;
            }
            if (isset($componente['children']) && is_array($componente['children'])) {
                $encontrados = array_merge($encontrados, self::busca($componente['children'], $condicion));
            }
        }
        return $encontrados;
    }
}
